package com.example.wcc_companion_app.ui.history

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.wcc_companion_app.data.local.dao.TicketDao
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

enum class HistoryFilter { EVENTS, WORK_ORDERS }

/** Rail item: filter chip card first, then closed events or completed WOs. */
sealed class HistoryRailItem {
    data class FilterCard(val mode: HistoryFilter) : HistoryRailItem()
    data class Event(val ticket: TicketDto) : HistoryRailItem()
    data class WorkOrder(val wo: WorkOrderDto) : HistoryRailItem()
}

@HiltViewModel
class HistoryViewModel @Inject constructor(
    private val apiService: WccApiService,
    private val ticketDao: TicketDao
) : ViewModel() {

    private val _filter = MutableStateFlow(HistoryFilter.EVENTS)
    val filter: StateFlow<HistoryFilter> = _filter.asStateFlow()

    private val _closedTickets = MutableStateFlow<List<TicketDto>>(emptyList())
    private val _completedWos = MutableStateFlow<List<WorkOrderDto>>(emptyList())

    private val _items = MutableStateFlow<List<HistoryRailItem>>(emptyList())
    val items: StateFlow<List<HistoryRailItem>> = _items.asStateFlow()

    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    init {
        loadHistory()
        // Keep offline-closed tickets visible in History as soon as local status flips.
        viewModelScope.launch {
            ticketDao.observeClosedTickets().collect { localClosed ->
                val localDtos = localClosed.map { it.toDto() }
                mergeClosed(server = _lastServerClosed, local = localDtos)
            }
        }
    }

    private var _lastServerClosed: List<TicketDto> = emptyList()

    fun setFilter(mode: HistoryFilter) {
        _filter.value = mode
        rebuild()
    }

    fun loadHistory() {
        viewModelScope.launch {
            _isLoading.value = true
            try {
                val tResp = apiService.getTickets(status = "CLOSED", page = 1, perPage = 40)
                if (tResp.isSuccessful) {
                    _lastServerClosed = tResp.body()?.data.orEmpty()
                }
                val localClosed = ticketDao.getClosedTickets().map { it.toDto() }
                mergeClosed(server = _lastServerClosed, local = localClosed)

                val wResp = apiService.getWorkOrders(status = "Completed", page = 1, perPage = 40)
                if (wResp.isSuccessful) {
                    _completedWos.value = sortCompletedWos(wResp.body()?.data.orEmpty())
                }
                rebuild()
            } catch (e: Exception) {
                // Offline: keep local closed + last known WOs
                val localClosed = try {
                    ticketDao.getClosedTickets().map { it.toDto() }
                } catch (_: Exception) {
                    emptyList()
                }
                mergeClosed(server = _lastServerClosed, local = localClosed)
                rebuild()
            } finally {
                _isLoading.value = false
            }
        }
    }

    private fun mergeClosed(server: List<TicketDto>, local: List<TicketDto>) {
        val byId = LinkedHashMap<String, TicketDto>()
        // Local first so just-closed offline events surface, then server fills rest.
        local.forEach { byId[it.ticket_id] = it }
        server.forEach { byId.putIfAbsent(it.ticket_id, it) }
        _closedTickets.value = sortClosedEvents(byId.values.toList())
        rebuild()
    }

    /** Most recently closed first (closed_at → created_at → report_date). */
    private fun sortClosedEvents(list: List<TicketDto>): List<TicketDto> =
        list.sortedByDescending { eventCloseKey(it) }

    private fun eventCloseKey(t: TicketDto): String =
        t.closed_at?.takeIf { it.isNotBlank() }
            ?: t.created_at?.takeIf { it.isNotBlank() }
            ?: t.report_date?.takeIf { it.isNotBlank() }
            ?: ""

    /** Most recently completed first. */
    private fun sortCompletedWos(list: List<WorkOrderDto>): List<WorkOrderDto> =
        list.sortedByDescending { woCompleteKey(it) }

    private fun woCompleteKey(wo: WorkOrderDto): String =
        wo.completed_date?.takeIf { it.isNotBlank() }
            ?: wo.started_at?.takeIf { it.isNotBlank() }
            ?: wo.scheduled_date?.takeIf { it.isNotBlank() }
            ?: ""

    private fun rebuild() {
        val mode = _filter.value
        val body: List<HistoryRailItem> = when (mode) {
            HistoryFilter.EVENTS -> _closedTickets.value.map { HistoryRailItem.Event(it) }
            HistoryFilter.WORK_ORDERS -> _completedWos.value.map { HistoryRailItem.WorkOrder(it) }
        }
        _items.value = listOf(HistoryRailItem.FilterCard(mode)) + body
    }
}
