package com.example.wcc_companion_app.ui.history

import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.wcc_companion_app.data.remote.models.TicketActionDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.data.remote.models.formatPartsList
import com.example.wcc_companion_app.ui.components.WccDetailHeader
import com.example.wcc_companion_app.ui.components.WccDetailInfoRow
import com.example.wcc_companion_app.ui.components.WccDetailModal
import com.example.wcc_companion_app.ui.inventory.InventoryViewModel
import com.example.wcc_companion_app.ui.tickets.ActionTimelineCard
import com.example.wcc_companion_app.ui.tickets.TicketViewModel

@Composable
fun HistoryEventDetailScreen(
    ticket: TicketDto,
    onClose: () -> Unit,
    ticketViewModel: TicketViewModel = hiltViewModel()
) {
    var actions by remember { mutableStateOf<List<TicketActionDto>>(emptyList()) }
    var equipName by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(ticket.ticket_id) {
        equipName = ticketViewModel.fetchEquipmentName(ticket.equip_id)
        actions = ticketViewModel.fetchTicketActions(ticket.ticket_id)
    }

    WccDetailModal(onDismiss = onClose) {
        WccDetailHeader(
            eyebrow = "CLOSED EVENT",
            title = ticket.ticket_id
        )
        WccDetailInfoRow("Equipment", equipName ?: "EQ-${ticket.equip_id}")
        WccDetailInfoRow("PIC", ticket.pic ?: "—")
        WccDetailInfoRow(
            "Fault",
            ticket.fault_desc ?: "No description",
            showDivider = false
        )
        Spacer(modifier = Modifier.height(12.dp))
        Text(
            "TIMELINE",
            style = MaterialTheme.typography.labelSmall,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
        )
        Spacer(modifier = Modifier.height(8.dp))
        if (actions.isEmpty()) {
            Text(
                "No actions logged.",
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                fontSize = 13.sp
            )
        } else {
            actions.forEach { ActionTimelineCard(it) }
        }
    }
}

@Composable
fun HistoryWoDetailScreen(
    wo: WorkOrderDto,
    onClose: () -> Unit,
    inventoryViewModel: InventoryViewModel = hiltViewModel()
) {
    val catalog by inventoryViewModel.parts.collectAsState()
    // Ensure catalog is loaded so part_id resolves to names (user: part naming is OK)
    LaunchedEffect(Unit) {
        if (catalog.isEmpty()) inventoryViewModel.loadParts()
    }
    val partsLabel = remember(wo.parts_list, catalog) {
        formatPartsList(wo.parts_list, catalog)
    }

    WccDetailModal(onDismiss = onClose) {
        WccDetailHeader(
            eyebrow = "COMPLETED WORK ORDER",
            title = "WO-${wo.wo_id}"
        )
        Text(
            wo.title,
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Black,
            modifier = Modifier.padding(bottom = 8.dp)
        )
        WccDetailInfoRow("Description", wo.description ?: "No description")
        WccDetailInfoRow(
            "Equipment",
            wo.equip_name ?: wo.equipment_id?.toString() ?: "—"
        )
        WccDetailInfoRow("Scheduled", wo.scheduled_date ?: "—")
        WccDetailInfoRow("Completed", wo.completed_date ?: "—")
        WccDetailInfoRow(
            "Parts",
            partsLabel,
            showDivider = false
        )
    }
}
