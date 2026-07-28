package com.example.wcc_companion_app.data.sync

import android.content.Context
import androidx.hilt.work.HiltWorker
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject

/**
 * Background outbox + media drain. Safe when app UI is dead.
 * Plant-LAN gate is inside [SyncCoordinator.syncNow].
 */
@HiltWorker
class OutboxSyncWorker @AssistedInject constructor(
    @Assisted appContext: Context,
    @Assisted params: WorkerParameters,
    private val syncCoordinator: SyncCoordinator,
) : CoroutineWorker(appContext, params) {

    override suspend fun doWork(): Result {
        return try {
            val ok = syncCoordinator.syncNow(pullReferences = false)
            if (ok) Result.success()
            else Result.retry()
        } catch (_: Exception) {
            Result.retry()
        }
    }
}
