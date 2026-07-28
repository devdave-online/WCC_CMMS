package com.example.wcc_companion_app.data.sync

import android.content.Context
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import android.net.NetworkRequest
import com.example.wcc_companion_app.data.repository.AuthRepository
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import okhttp3.HttpUrl.Companion.toHttpUrlOrNull
import java.net.InetSocketAddress
import java.net.Socket
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Plant-LAN reachability, not "has public internet".
 *
 * The WCC server lives on the plant network (typically a private IP like
 * 192.168.x.x). Cellular data reports [NetworkCapabilities.NET_CAPABILITY_INTERNET]
 * but cannot reach that host — so ticket work must behave as offline and queue
 * to the outbox, same as airplane mode.
 *
 * Watchdog:
 * 1. NetworkCallback on any link change
 * 2. Periodic TCP probe of the configured plant host while the process lives
 * 3. Cellular-only → immediately [PlantLink.CELLULAR] (no probe wait)
 */
enum class PlantLink {
    /** Configured plant server is reachable — live sync is allowed. */
    ONLINE,
    /** No usable radio / cable. */
    NO_NETWORK,
    /** Cellular only (or no LAN transport) — treat as offline for tickets. */
    CELLULAR,
    /** Wi‑Fi/Ethernet present but plant host not answering (wrong SSID / server down). */
    PLANT_UNREACHABLE,
}

@Singleton
class NetworkMonitor @Inject constructor(
    @ApplicationContext context: Context,
    private val authRepository: AuthRepository,
) {
    private val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.IO)
    private val evalMutex = Mutex()

    private val _plantLink = MutableStateFlow(PlantLink.NO_NETWORK)
    val plantLink: StateFlow<PlantLink> = _plantLink.asStateFlow()

    private val _isOnline = MutableStateFlow(false)
    /**
     * True only when the plant server is reachable.
     * Ticket cycle + sync must use this — not raw cellular internet.
     */
    val isOnline: StateFlow<Boolean> = _isOnline.asStateFlow()

    private var callback: ConnectivityManager.NetworkCallback? = null
    private var periodicJob: Job? = null

    init {
        startWatchdog()
    }

    fun checkOnline(): Boolean = _isOnline.value

    /** Snapshot of why we are offline (or ONLINE). */
    fun currentLink(): PlantLink = _plantLink.value

    /** Force an immediate re-check (e.g. after login saves a new server URL). */
    fun refreshNow() {
        scope.launch { reevaluate(reason = "manual") }
    }

    private fun startWatchdog() {
        val cb = object : ConnectivityManager.NetworkCallback() {
            override fun onAvailable(network: Network) {
                scope.launch { reevaluate(reason = "available") }
            }

            override fun onLost(network: Network) {
                scope.launch { reevaluate(reason = "lost") }
            }

            override fun onCapabilitiesChanged(network: Network, caps: NetworkCapabilities) {
                scope.launch { reevaluate(reason = "caps") }
            }
        }
        callback = cb

        // Listen for any transport — plant Wi‑Fi may lack INTERNET/VALIDATED.
        val req = NetworkRequest.Builder().build()
        try {
            cm.registerNetworkCallback(req, cb)
        } catch (_: Exception) {
            try {
                cm.registerDefaultNetworkCallback(cb)
            } catch (_: Exception) {
                // Periodic probe still covers us.
            }
        }

        periodicJob?.cancel()
        periodicJob = scope.launch {
            // Immediate first pass, then keep probing so walking onto plant Wi‑Fi
            // or server restart is noticed without a full network flap.
            while (isActive) {
                reevaluate(reason = "periodic")
                delay(PROBE_INTERVAL_MS)
            }
        }
    }

    @Suppress("UNUSED_PARAMETER")
    private suspend fun reevaluate(reason: String) = evalMutex.withLock {
        val next = computeLink()
        _plantLink.value = next
        _isOnline.value = next == PlantLink.ONLINE
    }

    private fun computeLink(): PlantLink {
        val scan = scanTransports()
        if (!scan.hasAny) return PlantLink.NO_NETWORK

        // Cellular without Wi‑Fi/Ethernet ⇒ practically off the plant LAN.
        // Do not probe: public internet is irrelevant and wastes radio time.
        if (!scan.hasLan && scan.hasCellular) {
            return PlantLink.CELLULAR
        }

        // LAN (or unknown transport with a configured host) — verify plant answers.
        return if (probePlantHost()) {
            PlantLink.ONLINE
        } else if (scan.hasLan) {
            PlantLink.PLANT_UNREACHABLE
        } else {
            // e.g. VPN-only / weird transport without clear LAN bit
            PlantLink.CELLULAR
        }
    }

    private data class TransportScan(
        val hasAny: Boolean,
        val hasLan: Boolean,
        val hasCellular: Boolean,
    )

    /**
     * Inspect every network, not only [ConnectivityManager.getActiveNetwork].
     * Android often prefers validated cellular for "default" while plant Wi‑Fi
     * is still up but unvalidated (no public internet route).
     */
    private fun scanTransports(): TransportScan {
        var hasLan = false
        var hasCellular = false
        var hasAny = false

        val networks = try {
            cm.allNetworks
        } catch (_: Exception) {
            emptyArray()
        }

        for (network in networks) {
            val caps = try {
                cm.getNetworkCapabilities(network)
            } catch (_: Exception) {
                null
            } ?: continue

            // Plant Wi‑Fi: count TRANSPORT_WIFI even without INTERNET/VALIDATED.
            if (caps.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) ||
                caps.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET)
            ) {
                hasLan = true
                hasAny = true
            }
            if (caps.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR)) {
                hasCellular = true
                hasAny = true
            }
            // Other transports (VPN, etc.) still count as "some link".
            if (!hasAny && (
                    caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) ||
                        caps.hasTransport(NetworkCapabilities.TRANSPORT_VPN)
                    )
            ) {
                hasAny = true
            }
        }

        // Fallback: active network only (older / restricted devices).
        if (!hasAny) {
            val active = cm.activeNetwork
            val caps = active?.let { cm.getNetworkCapabilities(it) }
            if (caps != null) {
                hasAny = true
                if (caps.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) ||
                    caps.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET)
                ) {
                    hasLan = true
                }
                if (caps.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR)) {
                    hasCellular = true
                }
            }
        }

        return TransportScan(hasAny = hasAny, hasLan = hasLan, hasCellular = hasCellular)
    }

    /**
     * Cheap TCP connect to the configured plant host:port.
     * Avoids HTTP stack / auth; just answers "is the LAN host there?".
     */
    private fun probePlantHost(): Boolean {
        val raw = authRepository.getServerUrl()?.trim().orEmpty()
        if (raw.isBlank()) return false

        val normalized = if (raw.startsWith("http://") || raw.startsWith("https://")) {
            raw
        } else {
            "http://$raw"
        }
        val url = normalized.toHttpUrlOrNull() ?: return false
        val host = url.host
        if (host.isBlank() || host == "localhost" || host == "127.0.0.1") {
            // Placeholder Retrofit base — not a real plant yet.
            if (authRepository.getCredentials() == null) return false
        }
        val port = when {
            url.port > 0 -> url.port
            url.scheme == "https" -> 443
            else -> 80
        }

        return try {
            Socket().use { socket ->
                socket.tcpNoDelay = true
                socket.connect(InetSocketAddress(host, port), PROBE_TIMEOUT_MS)
                true
            }
        } catch (_: Exception) {
            false
        }
    }

    companion object {
        /** How often to re-probe plant while process is alive. */
        private const val PROBE_INTERVAL_MS = 15_000L
        /** TCP connect budget — keep UI watchdog snappy. */
        private const val PROBE_TIMEOUT_MS = 1_500
    }
}
