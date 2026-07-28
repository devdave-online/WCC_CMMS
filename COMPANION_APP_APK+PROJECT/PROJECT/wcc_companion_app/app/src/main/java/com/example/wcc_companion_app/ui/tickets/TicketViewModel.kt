package com.example.wcc_companion_app.ui.tickets

import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.wcc_companion_app.data.local.entity.PendingMediaEntity
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.remote.models.*
import com.example.wcc_companion_app.data.repository.AuthRepository
import com.example.wcc_companion_app.data.repository.EvidenceRepository
import com.example.wcc_companion_app.data.repository.ReferenceCacheRepository
import com.example.wcc_companion_app.data.repository.TicketCycleRepository
import com.example.wcc_companion_app.data.sync.SyncCoordinator
import com.example.wcc_companion_app.data.sync.SyncScheduler
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class TicketViewModel @Inject constructor(
    private val apiService: WccApiService,
    private val authRepository: AuthRepository,
    private val ticketRepo: TicketCycleRepository,
    private val referenceRepo: ReferenceCacheRepository,
    private val syncCoordinator: SyncCoordinator,
    private val evidenceRepo: EvidenceRepository,
    private val syncScheduler: SyncScheduler,
) : ViewModel() {

    /** Always from Room — survives offline. */
    val tickets: StateFlow<List<TicketDto>> = ticketRepo.liveTickets.stateIn(
        viewModelScope,
        SharingStarted.WhileSubscribed(5_000),
        emptyList()
    )

    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    private val _teamMembers = MutableStateFlow<List<TeamMemberDto>>(emptyList())
    val teamMembers: StateFlow<List<TeamMemberDto>> = _teamMembers.asStateFlow()

    /** Inventory for takeover parts picker — Room-backed with network refresh. */
    val inventory: StateFlow<List<InventoryPartDto>> = referenceRepo.parts.stateIn(
        viewModelScope,
        SharingStarted.WhileSubscribed(5_000),
        emptyList()
    )

    init {
        syncCoordinator.start()
        loadTickets()
        loadReferenceData()
    }

    fun refreshReferenceData() {
        viewModelScope.launch {
            try {
                val teamResponse = apiService.getTeamMembers("technical")
                if (teamResponse.isSuccessful) {
                    teamResponse.body()?.data?.let { _teamMembers.value = it }
                }
                referenceRepo.pullParts()
            } catch (_: Exception) {
                // Keep local parts
            }
        }
    }

    private fun loadReferenceData() {
        refreshReferenceData()
    }

    fun loadTickets() {
        viewModelScope.launch {
            _isLoading.value = true
            try {
                ticketRepo.pullActiveTickets()
            } finally {
                _isLoading.value = false
            }
        }
    }

    fun submitTakeover(
        request: TakeoverRequestDto,
        onComplete: () -> Unit,
        onError: (String) -> Unit = {}
    ) {
        viewModelScope.launch {
            ticketRepo.submitTakeover(
                request = request,
                onComplete = {
                    if (!ticketRepo.isOnlineNow()) {
                        onComplete()
                    } else {
                        onComplete()
                    }
                },
                onError = onError
            )
        }
    }

    fun submitHold(
        request: HoldRequestDto,
        onComplete: () -> Unit,
        onError: (String) -> Unit = {}
    ) {
        viewModelScope.launch {
            ticketRepo.submitHold(request, onComplete, onError)
        }
    }

    fun submitCloseout(
        request: CloseoutRequestDto,
        onComplete: () -> Unit,
        onError: (String) -> Unit = {}
    ) {
        viewModelScope.launch {
            ticketRepo.submitCloseout(request, onComplete, onError)
        }
    }

    suspend fun fetchTicketActions(ticketId: String): List<TicketActionDto> {
        return try {
            val response = apiService.getTicketActions(ticketId)
            if (response.isSuccessful) {
                response.body()?.data ?: emptyList()
            } else {
                emptyList()
            }
        } catch (e: Exception) {
            emptyList()
        }
    }

    suspend fun fetchEquipmentName(equipId: Int): String? {
        return try {
            val response = apiService.getEquipment(equipId)
            if (response.isSuccessful) response.body()?.data?.equip_name else null
        } catch (e: Exception) {
            null
        }
    }

    suspend fun fetchTicketComments(ticketId: String): List<TicketCommentDto> {
        return try {
            val response = apiService.getTicketComments(ticketId)
            if (response.isSuccessful && response.body()?.status == "success") {
                response.body()?.data ?: emptyList()
            } else {
                emptyList()
            }
        } catch (e: Exception) {
            emptyList()
        }
    }

    fun addComment(
        ticketId: String,
        text: String,
        onComplete: () -> Unit,
        onError: (String) -> Unit = {}
    ) {
        viewModelScope.launch {
            ticketRepo.addComment(ticketId, text, onComplete, onError)
        }
    }

    fun observeEvidence(ticketId: String): Flow<List<PendingMediaEntity>> =
        evidenceRepo.observeForTicket(ticketId)

    fun addEvidence(ticketId: String, uri: Uri, mimeType: String) {
        viewModelScope.launch {
            try {
                evidenceRepo.enqueueFromUri("TICKET", ticketId, uri, mimeType)
                syncScheduler.enqueueOutboxDrain()
            } catch (e: Exception) {
                android.util.Log.e("WccEvidence", "Ticket photo enqueue failed", e)
            }
        }
    }

    fun removeEvidence(mediaId: String) {
        viewModelScope.launch { evidenceRepo.deleteLocal(mediaId) }
    }

    fun keepLocalConflict(ticketId: String) {
        viewModelScope.launch { ticketRepo.keepLocalOnConflict(ticketId) }
    }

    fun discardLocalConflict(ticketId: String) {
        viewModelScope.launch { ticketRepo.discardLocalOnConflict(ticketId) }
    }
}
