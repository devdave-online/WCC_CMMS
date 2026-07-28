package com.example.wcc_companion_app.data.sync

/**
 * Top-bar connection chip — plant link + outbox + multi-tech conflicts.
 * "Online" means plant server reachable (LAN), not mere cellular internet.
 */
enum class OfflineReason {
    NO_NETWORK,
    CELLULAR,
    PLANT_UNREACHABLE,
}

sealed class LiveBadgeState {
    data object Live : LiveBadgeState()
    data class Offline(val reason: OfflineReason = OfflineReason.NO_NETWORK) : LiveBadgeState()
    data class OfflineUnsynced(
        val count: Int,
        val reason: OfflineReason = OfflineReason.NO_NETWORK,
    ) : LiveBadgeState()
    data class Syncing(val remaining: Int = 0) : LiveBadgeState()
    /** Local edits clash with server — tech must keep or discard. */
    data class Conflict(val count: Int) : LiveBadgeState()
}
