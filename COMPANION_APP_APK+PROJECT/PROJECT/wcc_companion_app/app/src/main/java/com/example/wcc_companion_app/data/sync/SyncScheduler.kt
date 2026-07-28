package com.example.wcc_companion_app.data.sync

import android.content.Context
import androidx.work.Constraints
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.OutOfQuotaPolicy
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import dagger.hilt.android.qualifiers.ApplicationContext
import java.util.concurrent.TimeUnit
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Durable outbox scheduling via WorkManager — runs after process death / screen off.
 *
 * Note: [NetworkType.CONNECTED] means *any* network (incl. cellular). The worker
 * itself re-checks plant reachability via [NetworkMonitor] before draining.
 */
@Singleton
class SyncScheduler @Inject constructor(
    @ApplicationContext private val context: Context,
) {
    private val wm get() = WorkManager.getInstance(context)

    fun ensurePeriodic() {
        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()
        val request = PeriodicWorkRequestBuilder<OutboxSyncWorker>(15, TimeUnit.MINUTES)
            .setConstraints(constraints)
            .addTag(TAG)
            .build()
        wm.enqueueUniquePeriodicWork(
            PERIODIC_NAME,
            ExistingPeriodicWorkPolicy.KEEP,
            request,
        )
    }

    /** Immediate / expedited drain attempt when ops or media are enqueued. */
    fun enqueueOutboxDrain() {
        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()
        val request = OneTimeWorkRequestBuilder<OutboxSyncWorker>()
            .setConstraints(constraints)
            .setExpedited(OutOfQuotaPolicy.RUN_AS_NON_EXPEDITED_WORK_REQUEST)
            .addTag(TAG)
            .build()
        wm.enqueueUniqueWork(
            ONCE_NAME,
            ExistingWorkPolicy.REPLACE,
            request,
        )
    }

    companion object {
        const val TAG = "wcc_outbox"
        const val PERIODIC_NAME = "wcc_outbox_periodic"
        const val ONCE_NAME = "wcc_outbox_once"
    }
}
