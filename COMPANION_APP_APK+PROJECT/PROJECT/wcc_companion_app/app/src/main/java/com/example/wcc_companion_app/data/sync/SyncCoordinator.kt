package com.example.wcc_companion_app.data.sync

import com.example.wcc_companion_app.data.local.dao.PendingMediaDao
import com.example.wcc_companion_app.data.local.dao.PendingOpDao
import com.example.wcc_companion_app.data.local.dao.TicketDao
import com.example.wcc_companion_app.data.local.dao.WorkOrderDao
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.remote.models.AddCommentRequestDto
import com.example.wcc_companion_app.data.remote.models.CloseoutRequestDto
import com.example.wcc_companion_app.data.remote.models.CompanionWoRequestDto
import com.example.wcc_companion_app.data.remote.models.HoldRequestDto
import com.example.wcc_companion_app.data.remote.models.TakeoverRequestDto
import com.example.wcc_companion_app.data.repository.AuthRepository
import com.example.wcc_companion_app.data.repository.EvidenceRepository
import com.example.wcc_companion_app.data.repository.ReferenceCacheRepository
import com.example.wcc_companion_app.data.repository.TicketCycleRepository
import com.example.wcc_companion_app.data.repository.WorkOrderCycleRepository
import com.google.gson.Gson
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Pulls tickets / WOs / reference tables; drains outbox + evidence when Live.
 * Owns [LiveBadgeState]. Foreground loop + WorkManager both call [syncNow].
 */
@Singleton
class SyncCoordinator @Inject constructor(
    private val ticketRepo: TicketCycleRepository,
    private val workOrderRepo: WorkOrderCycleRepository,
    private val referenceRepo: ReferenceCacheRepository,
    private val evidenceRepo: EvidenceRepository,
    private val pendingOpDao: PendingOpDao,
    private val pendingMediaDao: PendingMediaDao,
    private val ticketDao: TicketDao,
    private val workOrderDao: WorkOrderDao,
    private val apiService: WccApiService,
    private val authRepository: AuthRepository,
    private val networkMonitor: NetworkMonitor,
    private val syncScheduler: SyncScheduler,
) {
    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.IO)
    private val mutex = Mutex()
    private val gson = Gson()

    private val _syncing = MutableStateFlow(false)

    /** One-shot UI events (failed drain → snackbar with Retry). */
    private val _syncEvents = MutableSharedFlow<SyncUiEvent>(extraBufferCapacity = 4)
    val syncEvents: SharedFlow<SyncUiEvent> = _syncEvents.asSharedFlow()

    private data class BadgeCore(
        val online: Boolean,
        val link: PlantLink,
        val unsynced: Int,
        val mediaPending: Int,
        val ticketConflicts: Int,
    )

    val badgeState: StateFlow<LiveBadgeState> = combine(
        combine(
            networkMonitor.isOnline,
            networkMonitor.plantLink,
            ticketRepo.unsyncedCount,
            evidenceRepo.observePendingCount(),
            ticketRepo.conflictCount,
        ) { online, link, unsynced, mediaPending, ticketConflicts ->
            BadgeCore(online, link, unsynced, mediaPending, ticketConflicts)
        },
        workOrderDao.observeConflictCount(),
        _syncing,
    ) { core, woConflicts, syncing ->
        val reason = core.link.toOfflineReason()
        val queue = core.unsynced + core.mediaPending
        val conflicts = core.ticketConflicts + woConflicts
        when {
            conflicts > 0 -> LiveBadgeState.Conflict(conflicts)
            syncing -> LiveBadgeState.Syncing(queue)
            !core.online && queue > 0 -> LiveBadgeState.OfflineUnsynced(queue, reason)
            !core.online -> LiveBadgeState.Offline(reason)
            queue > 0 -> LiveBadgeState.Syncing(queue)
            else -> LiveBadgeState.Live
        }
    }.stateIn(scope, SharingStarted.Eagerly, LiveBadgeState.Live)

    private fun PlantLink.toOfflineReason(): OfflineReason = when (this) {
        PlantLink.CELLULAR -> OfflineReason.CELLULAR
        PlantLink.PLANT_UNREACHABLE -> OfflineReason.PLANT_UNREACHABLE
        PlantLink.NO_NETWORK, PlantLink.ONLINE -> OfflineReason.NO_NETWORK
    }

    private var started = false
    private var periodicJob: Job? = null

    fun start() {
        if (started) return
        started = true
        syncScheduler.ensurePeriodic()
        scope.launch {
            networkMonitor.isOnline.collect { online ->
                if (online && authRepository.getCredentials() != null) {
                    syncNow(pullReferences = true)
                }
            }
        }
        periodicJob?.cancel()
        periodicJob = scope.launch {
            while (isActive) {
                delay(PERIODIC_MS)
                if (networkMonitor.checkOnline() && authRepository.getCredentials() != null) {
                    syncNow(pullReferences = false)
                }
            }
        }
    }

    /**
     * Live chip tap / boot resync / WorkManager.
     * @param pullReferences also refresh equip/parts/toolings
     */
    suspend fun syncNow(pullReferences: Boolean = true): Boolean = mutex.withLock {
        if (authRepository.getCredentials() == null) return false
        if (!networkMonitor.checkOnline()) {
            val queue = pendingQueueSize()
            if (queue > 0) {
                _syncEvents.tryEmit(
                    SyncUiEvent.Failed(
                        message = "Plant offline — $queue still queued",
                        pendingCount = queue,
                    )
                )
            }
            return false
        }

        _syncing.value = true
        return try {
            try {
                val creds = authRepository.getCredentials()
                if (creds != null) apiService.loginForm(creds.first, creds.second)
            } catch (_: Exception) { /* continue */ }

            val failedBefore = countFailed()
            drainOutbox()
            drainMedia()
            ticketRepo.pullActiveTickets()
            workOrderRepo.pullActiveWorkOrders()
            try {
                apiService.getTickets(status = "CLOSED", page = 1, perPage = 40)
            } catch (_: Exception) { /* HistoryViewModel also pulls */ }
            if (pullReferences) {
                referenceRepo.pullAll()
            }
            val failedAfter = countFailed()
            val remaining = pendingQueueSize()
            if (failedAfter > failedBefore || (remaining > 0 && failedAfter > 0)) {
                _syncEvents.tryEmit(
                    SyncUiEvent.Failed(
                        message = "Sync incomplete — $remaining still queued",
                        pendingCount = remaining,
                    )
                )
            }
            true
        } catch (e: Exception) {
            val remaining = pendingQueueSize()
            _syncEvents.tryEmit(
                SyncUiEvent.Failed(
                    message = e.localizedMessage?.takeIf { it.isNotBlank() }
                        ?: "Sync failed",
                    pendingCount = remaining,
                )
            )
            false
        } finally {
            _syncing.value = false
        }
    }

    private suspend fun pendingQueueSize(): Int {
        val ops = pendingOpDao.getPendingOrdered().size
        val media = pendingMediaDao.getPendingOrdered().size
        return ops + media
    }

    private suspend fun countFailed(): Int {
        return pendingOpDao.getAllOpenOrdered().count {
            it.status == "FAILED" || it.status == "CONFLICT"
        } + pendingMediaDao.getPendingOrdered().count { it.status == "FAILED" }
    }

    private suspend fun drainOutbox() {
        val ops = pendingOpDao.getPendingOrdered()
        for (op in ops) {
            // Pre-flight conflict check against live server status
            val conflict = detectPushConflict(op.type, op.ticketId, op.expectedBaseStatus)
            if (conflict != null) {
                pendingOpDao.updateStatus(op.opId, "CONFLICT", error = conflict)
                markEntityConflict(op.ticketId, conflict)
                continue
            }

            pendingOpDao.updateStatus(op.opId, "IN_FLIGHT")
            val pushResult = try {
                pushOp(op.type, op.payloadJson)
            } catch (e: Exception) {
                pendingOpDao.updateStatus(
                    op.opId,
                    "PENDING",
                    error = e.localizedMessage ?: "Network error",
                    retryInc = 1
                )
                return
            }

            when (pushResult) {
                PushResult.OK -> {
                    pendingOpDao.delete(op.opId)
                    applyLocalAfterSuccess(op.type, op.ticketId)
                }
                PushResult.CONFLICT -> {
                    val msg = "Server rejected — another tech may have changed this record."
                    pendingOpDao.updateStatus(op.opId, "CONFLICT", error = msg)
                    markEntityConflict(op.ticketId, msg)
                }
                PushResult.FAILED -> {
                    pendingOpDao.updateStatus(op.opId, "FAILED", error = "Server rejected", retryInc = 1)
                }
            }
        }
    }

    private suspend fun detectPushConflict(
        type: String,
        entityKey: String,
        expectedBase: String?,
    ): String? {
        if (expectedBase.isNullOrBlank()) return null
        return try {
            when {
                entityKey.startsWith("wo:") -> {
                    val woId = entityKey.removePrefix("wo:").toIntOrNull() ?: return null
                    val resp = apiService.getWorkOrderDetail(woId)
                    val serverStatus = resp.body()?.data?.status ?: return null
                    if (serverStatus.equals(expectedBase, ignoreCase = true)) null
                    else when (type) {
                        "WO_START" -> if (serverStatus in listOf("Completed", "Cancelled", "In Progress"))
                            "WO is $serverStatus on server (expected $expectedBase)"
                        else null
                        "WO_COMPLETE" -> if (serverStatus == "Completed") null // idempotent
                        else if (serverStatus == "Cancelled")
                            "WO cancelled on server"
                        else null
                        else -> null
                    }
                }
                else -> {
                    val resp = apiService.getTicket(entityKey)
                    val serverStatus = resp.body()?.data?.status ?: return null
                    if (serverStatus.equals(expectedBase, ignoreCase = true)) return null
                    when (type) {
                        "TAKEOVER", "RESUME" -> {
                            if (serverStatus.equals("CLOSED", true))
                                "Ticket already closed on server"
                            else if (serverStatus.equals("PENDING", true) &&
                                !expectedBase.equals("PENDING", true)
                            )
                                "Another tech already took over this ticket"
                            else null
                        }
                        "CLOSEOUT" -> {
                            if (serverStatus.equals("CLOSED", true)) null // idempotent
                            else null
                        }
                        "HOLD" -> {
                            if (serverStatus.equals("CLOSED", true))
                                "Ticket already closed on server"
                            else null
                        }
                        else -> null
                    }
                }
            }
        } catch (_: Exception) {
            null // network — let push try
        }
    }

    private suspend fun markEntityConflict(entityKey: String, message: String) {
        if (entityKey.startsWith("wo:")) {
            val woId = entityKey.removePrefix("wo:").toIntOrNull() ?: return
            val w = workOrderDao.getById(woId) ?: return
            workOrderDao.upsert(
                w.copy(syncState = "CONFLICT", conflictMessage = message)
            )
        } else {
            val t = ticketDao.getById(entityKey) ?: return
            ticketDao.upsert(
                t.copy(syncState = "CONFLICT", conflictMessage = message)
            )
        }
    }

    private suspend fun applyLocalAfterSuccess(type: String, entityKey: String) {
        when (type) {
            "CLOSEOUT" -> ticketDao.delete(entityKey)
            "TAKEOVER", "RESUME" -> {
                val t = ticketDao.getById(entityKey)
                if (t != null) {
                    ticketDao.upsert(
                        t.copy(
                            status = "PENDING",
                            syncState = "CLEAN",
                            baseServerStatus = "PENDING",
                            conflictMessage = null,
                            lastServerAt = System.currentTimeMillis(),
                        )
                    )
                }
            }
            "HOLD" -> {
                val t = ticketDao.getById(entityKey)
                if (t != null) {
                    ticketDao.upsert(
                        t.copy(
                            status = "HOLD",
                            syncState = "CLEAN",
                            baseServerStatus = "HOLD",
                            conflictMessage = null,
                            lastServerAt = System.currentTimeMillis(),
                        )
                    )
                }
            }
            "WO_START" -> {
                val woId = entityKey.removePrefix("wo:").toIntOrNull() ?: return
                val w = workOrderDao.getById(woId)
                if (w != null) {
                    workOrderDao.upsert(
                        w.copy(
                            status = "In Progress",
                            syncState = "CLEAN",
                            baseServerStatus = "In Progress",
                            conflictMessage = null,
                            lastServerAt = System.currentTimeMillis(),
                        )
                    )
                }
            }
            "WO_COMPLETE" -> {
                val woId = entityKey.removePrefix("wo:").toIntOrNull() ?: return
                workOrderDao.delete(woId)
            }
            else -> {
                if (!entityKey.startsWith("wo:")) {
                    val t = ticketDao.getById(entityKey)
                    if (t != null) {
                        ticketDao.upsert(
                            t.copy(
                                syncState = "CLEAN",
                                conflictMessage = null,
                                lastServerAt = System.currentTimeMillis(),
                            )
                        )
                    }
                }
            }
        }
    }

    private enum class PushResult { OK, FAILED, CONFLICT }

    private suspend fun pushOp(type: String, payloadJson: String): PushResult {
        return when (type) {
            "COMMENT" -> {
                val body = gson.fromJson(payloadJson, AddCommentRequestDto::class.java)
                val resp = apiService.addTicketComment(body)
                if (resp.isSuccessful && resp.body()?.status == "success") PushResult.OK
                else PushResult.FAILED
            }
            "TAKEOVER", "RESUME" -> {
                val body = gson.fromJson(payloadJson, TakeoverRequestDto::class.java)
                val resp = apiService.submitTakeover(body)
                val status = resp.body()?.get("status")
                val msg = resp.body()?.get("message").orEmpty()
                when {
                    resp.isSuccessful && status == "success" -> PushResult.OK
                    msg.contains("already", ignoreCase = true) ||
                        msg.contains("closed", ignoreCase = true) ||
                        msg.contains("conflict", ignoreCase = true) -> PushResult.CONFLICT
                    else -> PushResult.FAILED
                }
            }
            "HOLD" -> {
                val body = gson.fromJson(payloadJson, HoldRequestDto::class.java)
                val resp = apiService.submitHold(body)
                if (resp.isSuccessful && resp.body()?.get("status") == "success") PushResult.OK
                else PushResult.FAILED
            }
            "CLOSEOUT" -> {
                val body = gson.fromJson(payloadJson, CloseoutRequestDto::class.java)
                val resp = apiService.submitCloseout(body)
                val status = resp.body()?.get("status")
                val msg = resp.body()?.get("message").orEmpty()
                when {
                    resp.isSuccessful && status == "success" -> PushResult.OK
                    msg.contains("already", ignoreCase = true) ||
                        msg.contains("closed", ignoreCase = true) -> PushResult.OK // idempotent
                    else -> PushResult.FAILED
                }
            }
            "WO_START", "WO_COMPLETE" -> {
                val body = gson.fromJson(payloadJson, CompanionWoRequestDto::class.java)
                val resp = apiService.workOrderAction(body)
                val status = resp.body()?.status
                val msg = resp.body()?.message.orEmpty()
                when {
                    resp.isSuccessful && status == "success" -> PushResult.OK
                    msg.contains("locked", ignoreCase = true) ||
                        msg.contains("already", ignoreCase = true) ||
                        msg.contains("cancelled", ignoreCase = true) -> PushResult.CONFLICT
                    else -> PushResult.FAILED
                }
            }
            else -> PushResult.FAILED
        }
    }

    private suspend fun drainMedia() {
        val pending = evidenceRepo.getPendingOrdered()
        for (media in pending) {
            evidenceRepo.markInFlight(media.mediaId)
            try {
                val file = File(media.localPath)
                if (!file.exists()) {
                    evidenceRepo.markFailed(media.mediaId, "File missing")
                    continue
                }
                val body = file.asRequestBody(media.mimeType.toMediaTypeOrNull())
                val part = MultipartBody.Part.createFormData("file", file.name, body)
                val parentType = media.parentType.toRequestBody("text/plain".toMediaTypeOrNull())
                val parentId = media.parentId.toRequestBody("text/plain".toMediaTypeOrNull())
                val caption = (media.caption ?: "")
                    .toRequestBody("text/plain".toMediaTypeOrNull())
                val resp = apiService.uploadEvidence(parentType, parentId, caption, part)
                if (resp.isSuccessful && resp.body()?.status == "success") {
                    evidenceRepo.markUploaded(media.mediaId, resp.body()?.url)
                    // delete local file
                    file.delete()
                } else {
                    evidenceRepo.markFailed(
                        media.mediaId,
                        resp.body()?.message ?: "Upload failed HTTP ${resp.code()}"
                    )
                }
            } catch (e: Exception) {
                evidenceRepo.markFailed(media.mediaId, e.localizedMessage ?: "Upload error")
                return // stop on network
            }
        }
    }

    suspend fun clearLocalData() {
        ticketRepo.clearAllLocal()
        workOrderRepo.clearAllLocal()
        referenceRepo.clearAll()
        evidenceRepo.clearAll()
    }

    companion object {
        private const val PERIODIC_MS = 90_000L
    }
}

/** UI-facing one-shots from [SyncCoordinator] (snackbar + Retry). */
sealed class SyncUiEvent {
    data class Failed(val message: String, val pendingCount: Int) : SyncUiEvent()
}
