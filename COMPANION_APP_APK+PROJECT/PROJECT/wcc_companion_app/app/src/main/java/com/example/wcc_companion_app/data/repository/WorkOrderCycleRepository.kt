package com.example.wcc_companion_app.data.repository

import com.example.wcc_companion_app.data.local.dao.PendingOpDao
import com.example.wcc_companion_app.data.local.dao.WorkOrderDao
import com.example.wcc_companion_app.data.local.entity.LocalWorkOrderEntity
import com.example.wcc_companion_app.data.local.entity.PendingTicketOpEntity
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.remote.models.CompanionWoRequestDto
import com.example.wcc_companion_app.data.remote.models.PartConsumptionDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.data.sync.NetworkMonitor
import com.example.wcc_companion_app.data.sync.SyncScheduler
import com.google.gson.Gson
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class WorkOrderCycleRepository @Inject constructor(
    private val workOrderDao: WorkOrderDao,
    private val pendingOpDao: PendingOpDao,
    private val apiService: WccApiService,
    private val networkMonitor: NetworkMonitor,
    private val stockHelper: StockMutationHelper,
    private val syncScheduler: SyncScheduler,
) {
    private val gson = Gson()

    val liveWorkOrders: Flow<List<WorkOrderDto>> = workOrderDao.observeLive().map { list ->
        list.map { it.toDto() }
    }

    fun isOnlineNow(): Boolean = networkMonitor.checkOnline()

    suspend fun pullActiveWorkOrders(): Boolean {
        return try {
            val statuses = listOf("Scheduled", "In Progress", "Missed")
            val combined = mutableListOf<WorkOrderDto>()
            var anyOk = false
            for (status in statuses) {
                val response = apiService.getWorkOrders(status = status, perPage = 50)
                if (response.isSuccessful) {
                    anyOk = true
                    response.body()?.data?.let { combined.addAll(it) }
                }
            }
            if (!anyOk) return false

            // Conflict detect for DIRTY rows before replace skips them
            for (server in combined) {
                val local = workOrderDao.getById(server.wo_id) ?: continue
                if (local.syncState == "DIRTY" || local.syncState == "SYNCING") {
                    val base = local.baseServerStatus
                    val serverStatus = server.status
                    if (base != null && serverStatus != null &&
                        !serverStatus.equals(base, ignoreCase = true) &&
                        !serverStatus.equals(local.status, ignoreCase = true)
                    ) {
                        workOrderDao.upsert(
                            local.copy(
                                syncState = "CONFLICT",
                                conflictMessage =
                                    "Another tech changed this WO to $serverStatus while you were offline.",
                            )
                        )
                    }
                }
            }

            val entities = combined
                .distinctBy { it.wo_id }
                .map { LocalWorkOrderEntity.fromDto(it, syncState = "CLEAN") }
            workOrderDao.replaceLiveFromServer(entities)
            true
        } catch (_: Exception) {
            false
        }
    }

    suspend fun startWorkOrder(
        woId: Int,
        onComplete: (WorkOrderDto?) -> Unit,
        onError: (String) -> Unit,
    ) {
        val request = CompanionWoRequestDto(action = "start", wo_id = woId)
        if (isOnlineNow()) {
            try {
                val response = apiService.workOrderAction(request)
                val body = response.body()
                if (response.isSuccessful && body?.status == "success") {
                    body.data?.let {
                        workOrderDao.upsert(LocalWorkOrderEntity.fromDto(it, syncState = "CLEAN"))
                    } ?: applyLocalStart(woId, dirty = false)
                    pullActiveWorkOrders()
                    onComplete(body.data)
                } else {
                    onError(body?.message ?: "Start failed (HTTP ${response.code()})")
                }
            } catch (_: Exception) {
                saveStartOffline(woId)
                onComplete(workOrderDao.getById(woId)?.toDto())
            }
        } else {
            saveStartOffline(woId)
            onComplete(workOrderDao.getById(woId)?.toDto())
        }
    }

    private suspend fun saveStartOffline(woId: Int) {
        applyLocalStart(woId, dirty = true)
        enqueueWoOp(woId, "WO_START", CompanionWoRequestDto(action = "start", wo_id = woId))
        syncScheduler.enqueueOutboxDrain()
    }

    private suspend fun applyLocalStart(woId: Int, dirty: Boolean) {
        val existing = workOrderDao.getById(woId)
        val now = java.time.Instant.now().toString().replace('T', ' ').take(19)
        if (existing != null) {
            workOrderDao.upsert(
                existing.copy(
                    status = "In Progress",
                    startedAt = existing.startedAt ?: now,
                    syncState = if (dirty) "DIRTY" else "CLEAN",
                    updatedLocallyAt = System.currentTimeMillis(),
                )
            )
        }
    }

    suspend fun completeWorkOrder(
        woId: Int,
        notes: String,
        parts: List<PartConsumptionDto>,
        onComplete: () -> Unit,
        onError: (String) -> Unit,
    ) {
        val request = CompanionWoRequestDto(
            action = "complete",
            wo_id = woId,
            notes = notes,
            parts_consumed = parts,
        )
        if (isOnlineNow()) {
            try {
                val response = apiService.workOrderAction(request)
                val body = response.body()
                if (response.isSuccessful && body?.status == "success") {
                    if (parts.isNotEmpty()) stockHelper.applyConsumption(parts)
                    workOrderDao.delete(woId)
                    onComplete()
                } else {
                    onError(body?.message ?: "Complete failed (HTTP ${response.code()})")
                }
            } catch (_: Exception) {
                saveCompleteOffline(woId, request, parts)
                onComplete()
            }
        } else {
            saveCompleteOffline(woId, request, parts)
            onComplete()
        }
    }

    private suspend fun saveCompleteOffline(
        woId: Int,
        request: CompanionWoRequestDto,
        parts: List<PartConsumptionDto>,
    ) {
        if (parts.isNotEmpty()) stockHelper.applyConsumption(parts)
        val existing = workOrderDao.getById(woId)
        val now = java.time.Instant.now().toString().replace('T', ' ').take(19)
        if (existing != null) {
            workOrderDao.upsert(
                existing.copy(
                    status = "Completed",
                    completedDate = now,
                    syncState = "DIRTY",
                    updatedLocallyAt = System.currentTimeMillis(),
                )
            )
        }
        enqueueWoOp(woId, "WO_COMPLETE", request)
        syncScheduler.enqueueOutboxDrain()
    }

    private suspend fun enqueueWoOp(woId: Int, type: String, payload: Any) {
        val local = workOrderDao.getById(woId)
        pendingOpDao.insert(
            PendingTicketOpEntity(
                opId = UUID.randomUUID().toString(),
                ticketId = "wo:$woId",
                type = type,
                payloadJson = gson.toJson(payload),
                status = "PENDING",
                expectedBaseStatus = local?.baseServerStatus ?: local?.status,
            )
        )
    }

    suspend fun fetchDetail(woId: Int): WorkOrderDto? {
        if (isOnlineNow()) {
            try {
                val response = apiService.getWorkOrderDetail(woId)
                if (response.isSuccessful && response.body()?.status == "success") {
                    val data = response.body()?.data
                    if (data != null) {
                        val local = workOrderDao.getById(woId)
                        if (local?.syncState !in listOf("DIRTY", "SYNCING", "CONFLICT")) {
                            workOrderDao.upsert(LocalWorkOrderEntity.fromDto(data))
                        }
                        return data
                    }
                }
            } catch (_: Exception) { /* fall through */ }
        }
        return workOrderDao.getById(woId)?.toDto()
    }

    suspend fun discardLocalConflict(woId: Int) {
        // Drop local DIRTY/CONFLICT and re-pull if online
        workOrderDao.delete(woId)
        pendingOpDao.getAllOpenOrdered()
            .filter { it.ticketId == "wo:$woId" }
            .forEach { pendingOpDao.delete(it.opId) }
        if (isOnlineNow()) pullActiveWorkOrders()
    }

    suspend fun clearAllLocal() {
        workOrderDao.clearAll()
    }
}
