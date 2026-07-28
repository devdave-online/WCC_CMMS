package com.example.wcc_companion_app.data.repository

import com.example.wcc_companion_app.data.local.dao.PendingOpDao
import com.example.wcc_companion_app.data.local.dao.TicketDao
import com.example.wcc_companion_app.data.local.entity.LocalTicketEntity
import com.example.wcc_companion_app.data.local.entity.PendingTicketOpEntity
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.remote.models.AddCommentRequestDto
import com.example.wcc_companion_app.data.remote.models.CloseoutRequestDto
import com.example.wcc_companion_app.data.remote.models.HoldRequestDto
import com.example.wcc_companion_app.data.remote.models.TakeoverRequestDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.sync.NetworkMonitor
import com.example.wcc_companion_app.data.sync.SyncScheduler
import com.google.gson.Gson
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TicketCycleRepository @Inject constructor(
    private val ticketDao: TicketDao,
    private val pendingOpDao: PendingOpDao,
    private val apiService: WccApiService,
    private val authRepository: AuthRepository,
    private val networkMonitor: NetworkMonitor,
    private val stockHelper: StockMutationHelper,
    private val syncScheduler: SyncScheduler,
) {
    private val gson = Gson()

    val liveTickets: Flow<List<TicketDto>> = ticketDao.observeLiveTickets().map { list ->
        list.map { it.toDto() }
    }

    val unsyncedCount: Flow<Int> = pendingOpDao.observeUnsyncedCount()

    val conflictCount: Flow<Int> = ticketDao.observeConflictCount()

    fun isOnlineNow(): Boolean = networkMonitor.checkOnline()

    /**
     * Pull active tickets from server into Room. Never wipes DIRTY/SYNCING/CONFLICT rows.
     * Marks CONFLICT when server drifted away from our base while we have local edits.
     */
    suspend fun pullActiveTickets(): Boolean {
        return try {
            val activeStatuses = listOf("OPEN", "PENDING", "ESCALATED", "HOLD")
            val combined = mutableListOf<TicketDto>()
            var anyOk = false
            for (status in activeStatuses) {
                val response = apiService.getTickets(status = status)
                if (response.isSuccessful) {
                    anyOk = true
                    response.body()?.data?.let { combined.addAll(it) }
                }
            }
            if (!anyOk) return false

            for (server in combined) {
                val local = ticketDao.getById(server.ticket_id) ?: continue
                if (local.syncState == "DIRTY" || local.syncState == "SYNCING") {
                    val base = local.baseServerStatus
                    val serverStatus = server.status
                    if (base != null && serverStatus != null &&
                        !serverStatus.equals(base, ignoreCase = true) &&
                        !serverStatus.equals(local.status, ignoreCase = true)
                    ) {
                        ticketDao.upsert(
                            local.copy(
                                syncState = "CONFLICT",
                                conflictMessage =
                                    "Server moved this ticket to $serverStatus while you had offline changes (was $base).",
                            )
                        )
                    }
                }
            }

            val entities = combined
                .distinctBy { it.ticket_id }
                .map { LocalTicketEntity.fromDto(it, syncState = "CLEAN") }
            ticketDao.replaceLiveFromServer(entities)
            true
        } catch (_: Exception) {
            false
        }
    }

    suspend fun applyLocalStatus(ticketId: String, status: String, syncState: String = "DIRTY") {
        val existing = ticketDao.getById(ticketId) ?: return
        ticketDao.upsert(
            existing.copy(
                status = status,
                syncState = syncState,
                conflictMessage = if (syncState == "CONFLICT") existing.conflictMessage else null,
                updatedLocallyAt = System.currentTimeMillis()
            )
        )
    }

    suspend fun enqueueOp(
        ticketId: String,
        type: String,
        payload: Any,
        expectedBaseStatus: String? = null,
    ) {
        val base = expectedBaseStatus
            ?: ticketDao.getById(ticketId)?.baseServerStatus
            ?: ticketDao.getById(ticketId)?.status
        pendingOpDao.insert(
            PendingTicketOpEntity(
                opId = UUID.randomUUID().toString(),
                ticketId = ticketId,
                type = type,
                payloadJson = gson.toJson(payload),
                status = "PENDING",
                expectedBaseStatus = base,
            )
        )
        syncScheduler.enqueueOutboxDrain()
    }

    suspend fun submitTakeover(
        request: TakeoverRequestDto,
        onComplete: () -> Unit,
        onError: (String) -> Unit
    ) {
        val finalRequest = if (request.tech_name.isBlank()) {
            request.copy(tech_name = authRepository.getApiKey() ?: "Unknown User")
        } else request

        if (isOnlineNow()) {
            try {
                val response = apiService.submitTakeover(finalRequest)
                val body = response.body()
                if (response.isSuccessful && body?.get("status") == "success") {
                    if (finalRequest.parts_consumed_data.isNotEmpty()) {
                        stockHelper.applyConsumption(finalRequest.parts_consumed_data)
                    }
                    applyLocalStatus(finalRequest.ticket_id, "PENDING", "CLEAN")
                    pullActiveTickets()
                    onComplete()
                } else {
                    onError(body?.get("message") ?: "Submit failed (HTTP ${response.code()})")
                }
            } catch (_: Exception) {
                saveTakeoverOffline(finalRequest)
                onComplete()
            }
        } else {
            saveTakeoverOffline(finalRequest)
            onComplete()
        }
    }

    private suspend fun saveTakeoverOffline(request: TakeoverRequestDto) {
        if (request.parts_consumed_data.isNotEmpty()) {
            stockHelper.applyConsumption(request.parts_consumed_data)
        }
        applyLocalStatus(request.ticket_id, "PENDING", "DIRTY")
        enqueueOp(request.ticket_id, "TAKEOVER", request)
    }

    suspend fun submitHold(
        request: HoldRequestDto,
        onComplete: () -> Unit,
        onError: (String) -> Unit
    ) {
        if (isOnlineNow()) {
            try {
                val response = apiService.submitHold(request)
                val body = response.body()
                if (response.isSuccessful && body?.get("status") == "success") {
                    applyLocalStatus(request.ticket_id, "HOLD", "CLEAN")
                    pullActiveTickets()
                    onComplete()
                } else {
                    onError(body?.get("message") ?: "Hold failed (HTTP ${response.code()})")
                }
            } catch (_: Exception) {
                saveHoldOffline(request)
                onComplete()
            }
        } else {
            saveHoldOffline(request)
            onComplete()
        }
    }

    private suspend fun saveHoldOffline(request: HoldRequestDto) {
        applyLocalStatus(request.ticket_id, "HOLD", "DIRTY")
        enqueueOp(request.ticket_id, "HOLD", request)
    }

    suspend fun submitCloseout(
        request: CloseoutRequestDto,
        onComplete: () -> Unit,
        onError: (String) -> Unit
    ) {
        val finalRequest = if (request.supervisor.isBlank()) {
            request.copy(supervisor = authRepository.getApiKey() ?: "Unknown User")
        } else request

        if (isOnlineNow()) {
            try {
                val response = apiService.submitCloseout(finalRequest)
                val body = response.body()
                if (response.isSuccessful && body?.get("status") == "success") {
                    ticketDao.delete(finalRequest.ticket_id)
                    onComplete()
                } else {
                    onError(body?.get("message") ?: "Closeout failed (HTTP ${response.code()})")
                }
            } catch (_: Exception) {
                saveCloseoutOffline(finalRequest)
                onComplete()
            }
        } else {
            saveCloseoutOffline(finalRequest)
            onComplete()
        }
    }

    private suspend fun saveCloseoutOffline(request: CloseoutRequestDto) {
        val existing = ticketDao.getById(request.ticket_id)
        val nowIso = java.time.Instant.now().toString()
            .replace('T', ' ')
            .take(19)
        if (existing != null) {
            ticketDao.upsert(
                existing.copy(
                    status = "CLOSED",
                    closedAt = nowIso,
                    syncState = "DIRTY",
                    updatedLocallyAt = System.currentTimeMillis()
                )
            )
        } else {
            applyLocalStatus(request.ticket_id, "CLOSED", "DIRTY")
        }
        enqueueOp(request.ticket_id, "CLOSEOUT", request)
    }

    suspend fun addComment(
        ticketId: String,
        text: String,
        onComplete: () -> Unit,
        onError: (String) -> Unit
    ) {
        val payload = AddCommentRequestDto(ticket_id = ticketId, comment_text = text)
        if (isOnlineNow()) {
            try {
                val response = apiService.addTicketComment(payload)
                val body = response.body()
                if (response.isSuccessful && body?.status == "success") {
                    onComplete()
                } else {
                    onError(body?.message ?: "Comment failed (HTTP ${response.code()})")
                }
            } catch (_: Exception) {
                enqueueOp(ticketId, "COMMENT", payload)
                onComplete()
            }
        } else {
            enqueueOp(ticketId, "COMMENT", payload)
            onComplete()
        }
    }

    /** Keep local offline edits; drop conflicting server view until next pull after resolve. */
    suspend fun keepLocalOnConflict(ticketId: String) {
        val t = ticketDao.getById(ticketId) ?: return
        ticketDao.upsert(
            t.copy(
                syncState = "DIRTY",
                conflictMessage = null,
                // Re-base so we don't re-flag until server moves again
                baseServerStatus = t.status,
            )
        )
        // Re-open CONFLICT ops as PENDING for retry
        pendingOpDao.getAllOpenOrdered()
            .filter { it.ticketId == ticketId && it.status == "CONFLICT" }
            .forEach { pendingOpDao.updateStatus(it.opId, "PENDING", error = null) }
        syncScheduler.enqueueOutboxDrain()
    }

    /** Discard local edits and re-adopt server. */
    suspend fun discardLocalOnConflict(ticketId: String) {
        pendingOpDao.getAllOpenOrdered()
            .filter { it.ticketId == ticketId }
            .forEach { pendingOpDao.delete(it.opId) }
        ticketDao.delete(ticketId)
        if (isOnlineNow()) pullActiveTickets()
    }

    suspend fun clearAllLocal() {
        ticketDao.clearAll()
        pendingOpDao.clearAll()
    }

    fun parsePayload(op: PendingTicketOpEntity): String = op.payloadJson

    fun gson(): Gson = gson
}
