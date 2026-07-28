package com.example.wcc_companion_app.ui.equipment

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.remote.models.EquipmentDto
import com.example.wcc_companion_app.data.repository.ReferenceCacheRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class EquipmentViewModel @Inject constructor(
    private val apiService: WccApiService,
    private val referenceRepo: ReferenceCacheRepository
) : ViewModel() {

    val equipment: StateFlow<List<EquipmentDto>> = referenceRepo.equipment.stateIn(
        viewModelScope,
        SharingStarted.WhileSubscribed(5_000),
        emptyList()
    )

    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    init {
        loadEquipment()
    }

    fun loadEquipment() {
        viewModelScope.launch {
            _isLoading.value = true
            try {
                referenceRepo.pullEquipment()
            } finally {
                _isLoading.value = false
            }
        }
    }

    suspend fun findByAssetTag(code: String): EquipmentDto? {
        return try {
            val resp = apiService.findEquipmentByAssetTag(code)
            if (resp.isSuccessful) resp.body()?.data?.firstOrNull() else null
        } catch (e: Exception) {
            null
        }
    }
}
