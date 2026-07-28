package com.example.wcc_companion_app.ui.workorders

import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.wcc_companion_app.data.local.entity.PendingMediaEntity
import com.example.wcc_companion_app.data.remote.models.PartConsumptionDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.data.repository.EvidenceRepository
import com.example.wcc_companion_app.data.repository.WorkOrderCycleRepository
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
class WorkOrderViewModel @Inject constructor(
    private val workOrderRepo: WorkOrderCycleRepository,
    private val syncCoordinator: SyncCoordinator,
    private val evidenceRepo: EvidenceRepository,
    private val syncScheduler: SyncScheduler,
) : ViewModel() {

    /** Room-backed — survives off-plant / process death. */
    val workOrders: StateFlow<List<WorkOrderDto>> = workOrderRepo.liveWorkOrders.stateIn(
        viewModelScope,
        SharingStarted.WhileSubscribed(5_000),
        emptyList()
    )

    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    init {
        syncCoordinator.start()
        loadWorkOrders()
    }

    fun loadWorkOrders() {
        viewModelScope.launch {
            _isLoading.value = true
            try {
                workOrderRepo.pullActiveWorkOrders()
            } finally {
                _isLoading.value = false
            }
        }
    }

    fun startWorkOrder(
        woId: Int,
        onComplete: (WorkOrderDto?) -> Unit = {},
        onError: (String) -> Unit = {}
    ) {
        viewModelScope.launch {
            workOrderRepo.startWorkOrder(woId, onComplete, onError)
        }
    }

    fun completeWorkOrder(
        woId: Int,
        notes: String,
        parts: List<PartConsumptionDto>,
        onComplete: () -> Unit,
        onError: (String) -> Unit = {}
    ) {
        viewModelScope.launch {
            workOrderRepo.completeWorkOrder(woId, notes, parts, onComplete, onError)
        }
    }

    suspend fun fetchWorkOrderDetail(woId: Int): WorkOrderDto? =
        workOrderRepo.fetchDetail(woId)

    fun observeEvidence(woId: Int): Flow<List<PendingMediaEntity>> =
        evidenceRepo.observeForWorkOrder(woId)

    fun addEvidence(woId: Int, uri: Uri, mimeType: String) {
        viewModelScope.launch {
            try {
                evidenceRepo.enqueueFromUri("WO", woId.toString(), uri, mimeType)
                syncScheduler.enqueueOutboxDrain()
            } catch (e: Exception) {
                android.util.Log.e("WccEvidence", "WO photo enqueue failed", e)
            }
        }
    }

    fun removeEvidence(mediaId: String) {
        viewModelScope.launch { evidenceRepo.deleteLocal(mediaId) }
    }
}
