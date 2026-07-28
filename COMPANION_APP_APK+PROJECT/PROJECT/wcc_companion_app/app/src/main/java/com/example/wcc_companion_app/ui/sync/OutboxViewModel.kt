package com.example.wcc_companion_app.ui.sync

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.wcc_companion_app.data.local.dao.PendingMediaDao
import com.example.wcc_companion_app.data.local.dao.PendingOpDao
import com.example.wcc_companion_app.data.local.entity.PendingMediaEntity
import com.example.wcc_companion_app.data.local.entity.PendingTicketOpEntity
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.stateIn
import javax.inject.Inject

@HiltViewModel
class OutboxViewModel @Inject constructor(
    pendingOpDao: PendingOpDao,
    pendingMediaDao: PendingMediaDao,
) : ViewModel() {

    val openOps: StateFlow<List<PendingTicketOpEntity>> =
        pendingOpDao.observeOpenOps()
            .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), emptyList())

    val openMedia: StateFlow<List<PendingMediaEntity>> =
        pendingMediaDao.observeOpenMedia()
            .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), emptyList())
}
