package com.example.wcc_companion_app.ui.inventory

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
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
class InventoryViewModel @Inject constructor(
    private val referenceRepo: ReferenceCacheRepository
) : ViewModel() {

    val parts: StateFlow<List<InventoryPartDto>> = referenceRepo.parts.stateIn(
        viewModelScope,
        SharingStarted.WhileSubscribed(5_000),
        emptyList()
    )

    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    init {
        loadParts()
    }

    fun loadParts(search: String? = null) {
        viewModelScope.launch {
            _isLoading.value = true
            try {
                // Full catalog cache; search is client-side on rail
                referenceRepo.pullParts()
            } finally {
                _isLoading.value = false
            }
        }
    }
}
