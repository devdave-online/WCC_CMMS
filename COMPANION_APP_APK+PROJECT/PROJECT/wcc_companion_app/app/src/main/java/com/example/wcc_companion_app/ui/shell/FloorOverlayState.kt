package com.example.wcc_companion_app.ui.shell

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import com.example.wcc_companion_app.data.remote.models.EquipmentDto
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
import com.example.wcc_companion_app.data.remote.models.ScanLookupDataDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.ToolingDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.ui.components.RailFilterDomain

/**
 * Single owner of full-screen floor overlays (tickets, WO, scan, details).
 * Keeps [AppShell] free of a dozen independent `mutableStateOf`s.
 */
class FloorOverlayState {
    var selectedTicket by mutableStateOf<TicketDto?>(null)
    var takeoverTicket by mutableStateOf<TicketDto?>(null)
    var closeoutTicket by mutableStateOf<TicketDto?>(null)
    var holdTicket by mutableStateOf<TicketDto?>(null)
    var selectedEquipment by mutableStateOf<EquipmentDto?>(null)
    var selectedTooling by mutableStateOf<ToolingDto?>(null)
    var selectedPart by mutableStateOf<InventoryPartDto?>(null)
    var selectedHistoryEvent by mutableStateOf<TicketDto?>(null)
    var selectedHistoryWo by mutableStateOf<WorkOrderDto?>(null)
    var activeWorkOrder by mutableStateOf<WorkOrderDto?>(null)
    var showEquipScanner by mutableStateOf(false)
    var scanStatus by mutableStateOf<String?>(null)
    var isLookingUp by mutableStateOf(false)
    var scanResult by mutableStateOf<ScanLookupDataDto?>(null)
    var filterSheetDomain by mutableStateOf<RailFilterDomain?>(null)
    var showOutboxSheet by mutableStateOf(false)

    val anyOverlay: Boolean
        get() = selectedTicket != null || takeoverTicket != null || closeoutTicket != null ||
            holdTicket != null || selectedEquipment != null || selectedTooling != null ||
            selectedPart != null || selectedHistoryEvent != null || selectedHistoryWo != null ||
            activeWorkOrder != null || showEquipScanner || scanResult != null ||
            filterSheetDomain != null || showOutboxSheet

    /** Back-stack style dismiss: topmost overlay only. */
    fun popTop(): Boolean {
        if (showOutboxSheet) { showOutboxSheet = false; return true }
        if (filterSheetDomain != null) { filterSheetDomain = null; return true }
        if (scanResult != null) { scanResult = null; return true }
        if (showEquipScanner) { showEquipScanner = false; return true }
        if (selectedTicket != null) { selectedTicket = null; return true }
        if (takeoverTicket != null) { takeoverTicket = null; return true }
        if (closeoutTicket != null) { closeoutTicket = null; return true }
        if (holdTicket != null) { holdTicket = null; return true }
        if (selectedEquipment != null) { selectedEquipment = null; return true }
        if (selectedTooling != null) { selectedTooling = null; return true }
        if (selectedPart != null) { selectedPart = null; return true }
        if (selectedHistoryEvent != null) { selectedHistoryEvent = null; return true }
        if (selectedHistoryWo != null) { selectedHistoryWo = null; return true }
        if (activeWorkOrder != null) { activeWorkOrder = null; return true }
        return false
    }

    fun clearWorkOverlays() {
        selectedTicket = null
        takeoverTicket = null
        closeoutTicket = null
        holdTicket = null
        selectedEquipment = null
        selectedTooling = null
        selectedPart = null
        selectedHistoryEvent = null
        selectedHistoryWo = null
        activeWorkOrder = null
        showEquipScanner = false
        scanResult = null
        filterSheetDomain = null
        showOutboxSheet = false
        scanStatus = null
        isLookingUp = false
    }

    /**
     * Floor ticket routing by status (scan / search).
     * Caller focuses the tickets rail before/after as needed.
     */
    fun openTicketByStatus(t: TicketDto) {
        when (t.status?.uppercase()) {
            "PENDING" -> closeoutTicket = t
            "HOLD" -> takeoverTicket = t
            "CLOSED" -> selectedTicket = t
            else -> takeoverTicket = t
        }
    }
}
