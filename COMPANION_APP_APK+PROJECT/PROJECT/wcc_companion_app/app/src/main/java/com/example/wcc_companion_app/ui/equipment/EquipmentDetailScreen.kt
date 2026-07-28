package com.example.wcc_companion_app.ui.equipment

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.data.remote.models.EquipmentDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.ui.components.WccDetailHeader
import com.example.wcc_companion_app.ui.components.WccDetailInfoRow
import com.example.wcc_companion_app.ui.components.WccDetailModal

@Composable
fun EquipmentDetailScreen(
    equipment: EquipmentDto,
    openTickets: List<TicketDto> = emptyList(),
    onClose: () -> Unit,
    onOpenTicket: (TicketDto) -> Unit = {}
) {
    WccDetailModal(onDismiss = onClose) {
        WccDetailHeader(
            eyebrow = "EQUIPMENT",
            title = equipment.equip_name
        )

        WccDetailInfoRow("Asset tag", equipment.asset_uuid ?: "—")
        WccDetailInfoRow("Type", equipment.equipment_type ?: "—")
        WccDetailInfoRow("Category", equipment.category ?: "—")
        WccDetailInfoRow("Criticality", equipment.criticality ?: "—")
        WccDetailInfoRow(
            "OEM",
            listOfNotNull(equipment.oem_brand, equipment.oem_model)
                .joinToString(" ")
                .ifBlank { "—" }
        )
        WccDetailInfoRow("Serial", equipment.oem_serial ?: "—")
        WccDetailInfoRow(
            "Location",
            listOfNotNull(equipment.plant_name, equipment.line_name, equipment.station_name)
                .joinToString(" / ")
                .ifBlank { "—" },
            showDivider = false
        )

        Spacer(modifier = Modifier.height(16.dp))
        Text(
            "OPEN TICKETS ON THIS ASSET",
            style = MaterialTheme.typography.labelSmall,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
        )
        Spacer(modifier = Modifier.height(8.dp))
        if (openTickets.isEmpty()) {
            Text(
                "No open tickets.",
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                fontSize = 13.sp
            )
        } else {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                openTickets.forEach { t ->
                    Surface(
                        shape = RoundedCornerShape(12.dp),
                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f),
                        border = BorderStroke(
                            1.dp,
                            MaterialTheme.colorScheme.primary.copy(alpha = 0.2f)
                        ),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable(
                                indication = null,
                                interactionSource = remember { MutableInteractionSource() }
                            ) { onOpenTicket(t) }
                    ) {
                        Column(modifier = Modifier.padding(12.dp).fillMaxWidth()) {
                            Text(t.ticket_id, fontWeight = FontWeight.Bold)
                            Text(
                                t.fault_desc ?: "",
                                fontSize = 12.sp,
                                maxLines = 2,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f)
                            )
                        }
                    }
                }
            }
        }
    }
}
