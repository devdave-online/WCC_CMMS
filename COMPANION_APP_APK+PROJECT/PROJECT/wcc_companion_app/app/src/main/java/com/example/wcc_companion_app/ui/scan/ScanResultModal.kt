package com.example.wcc_companion_app.ui.scan

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.ConfirmationNumber
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.wcc_companion_app.data.remote.models.EquipmentDto
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
import com.example.wcc_companion_app.data.remote.models.ScanLookupDataDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.ui.theme.WccPrimary
import com.example.wcc_companion_app.ui.theme.WccTokens
import com.example.wcc_companion_app.ui.theme.WccWarning

/**
 * Consolidated scan result — shows what the DB resolved for a QR/DataMatrix,
 * plus open tickets (OT) and open work orders on matched equipment.
 *
 * Floor jump: if the scan resolves to exactly one actionable target, open it
 * immediately. Otherwise show a primary CTA strip for the best candidate.
 */
@Composable
fun ScanResultModal(
    result: ScanLookupDataDto,
    onDismiss: () -> Unit,
    onScanAgain: () -> Unit,
    onOpenEquipment: (EquipmentDto) -> Unit = {},
    onOpenPart: (InventoryPartDto) -> Unit = {},
    onOpenTicket: (TicketDto) -> Unit = {},
    onOpenWorkOrder: (WorkOrderDto) -> Unit = {}
) {
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE
    val hits = result.hits.orEmpty()
    val tickets = result.open_tickets.orEmpty()
    val wos = result.open_work_orders.orEmpty()
    val equipmentHits = hits.mapNotNull { hitToEquipment(it.kind, it.data) }
    val partHits = hits.mapNotNull { hitToPart(it.kind, it.data) }
    val toolingLines = hits.filter { it.kind == "tooling" }.mapNotNull { h ->
        h.data?.get("tooling_name")?.toString()
            ?: h.data?.get("tooling_code")?.toString()
    }

    // Prefer floor work over asset browse: ticket > WO > single equip/part.
    val primaryTicket = tickets.firstOrNull()
    val primaryWo = wos.firstOrNull()
    val primaryEquip = equipmentHits.singleOrNull()
    val primaryPart = partHits.singleOrNull()
    var didAutoJump by remember(result.code) { mutableStateOf(false) }

    LaunchedEffect(result.code, tickets.size, wos.size, equipmentHits.size, partHits.size) {
        if (didAutoJump) return@LaunchedEffect
        val actionables =
            tickets.size + wos.size +
                (if (tickets.isEmpty() && wos.isEmpty()) equipmentHits.size + partHits.size else 0)
        if (actionables != 1) return@LaunchedEffect
        didAutoJump = true
        when {
            tickets.size == 1 -> onOpenTicket(tickets[0])
            wos.size == 1 -> onOpenWorkOrder(wos[0])
            equipmentHits.size == 1 && partHits.isEmpty() -> onOpenEquipment(equipmentHits[0])
            partHits.size == 1 && equipmentHits.isEmpty() -> onOpenPart(partHits[0])
            else -> didAutoJump = false
        }
    }

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Color.Black.copy(alpha = 0.62f))
                .windowInsetsPadding(WindowInsets.systemBars),
            contentAlignment = Alignment.Center
        ) {
            Surface(
                modifier = Modifier
                    .fillMaxWidth(if (isLandscape) 0.92f else 0.94f)
                    .fillMaxHeight(if (isLandscape) 0.96f else 0.92f)
                    .imePadding(),
                shape = RoundedCornerShape(WccTokens.radiusXxl),
                color = MaterialTheme.colorScheme.surface.copy(alpha = 0.98f),
                border = BorderStroke(
                    WccTokens.borderThin,
                    MaterialTheme.colorScheme.primary.copy(alpha = 0.28f)
                ),
                shadowElevation = 10.dp
            ) {
                Column(modifier = Modifier.fillMaxSize()) {
                    // Header
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(start = 20.dp, end = 8.dp, top = 14.dp, bottom = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            Icons.Default.QrCodeScanner,
                            contentDescription = null,
                            tint = WccPrimary,
                            modifier = Modifier.size(26.dp)
                        )
                        Spacer(modifier = Modifier.width(10.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                "Scan result",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Black,
                                color = WccPrimary
                            )
                            Text(
                                result.code ?: "—",
                                fontSize = 12.sp,
                                fontWeight = FontWeight.SemiBold,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    }

                    // Summary chips
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(rememberScrollState())
                            .padding(horizontal = 20.dp),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        SummaryChip("${equipmentHits.size} equip")
                        SummaryChip("${tickets.size} open OT")
                        SummaryChip("${wos.size} open WO")
                        if (partHits.isNotEmpty()) SummaryChip("${partHits.size} parts")
                        if (toolingLines.isNotEmpty()) SummaryChip("${toolingLines.size} tooling")
                    }

                    Spacer(modifier = Modifier.height(10.dp))

                    // Primary floor jump CTA when multiple hits — one-tap open best target.
                    val jumpLabel = when {
                        primaryTicket != null ->
                            "Open ${primaryTicket.ticket_id} · ${primaryTicket.status?.uppercase() ?: "OT"}"
                        primaryWo != null ->
                            "Open WO-${primaryWo.wo_id}"
                        primaryEquip != null && tickets.isEmpty() && wos.isEmpty() ->
                            "Open ${primaryEquip.equip_name}"
                        primaryPart != null && tickets.isEmpty() && wos.isEmpty() ->
                            "Open part ${primaryPart.internal_code}"
                        else -> null
                    }
                    if (jumpLabel != null) {
                        Button(
                            onClick = {
                                when {
                                    primaryTicket != null -> onOpenTicket(primaryTicket)
                                    primaryWo != null -> onOpenWorkOrder(primaryWo)
                                    primaryEquip != null -> onOpenEquipment(primaryEquip)
                                    primaryPart != null -> onOpenPart(primaryPart)
                                }
                            },
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 16.dp)
                                .height(48.dp),
                            shape = RoundedCornerShape(WccTokens.radiusMd)
                        ) {
                            Text(jumpLabel, fontWeight = FontWeight.Black, maxLines = 1)
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                    }

                    if (isLandscape) {
                        Row(
                            modifier = Modifier
                                .weight(1f)
                                .fillMaxWidth()
                                .padding(horizontal = 16.dp),
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Column(
                                modifier = Modifier
                                    .weight(1f)
                                    .verticalScroll(rememberScrollState()),
                                verticalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                MatchedAssetsSection(
                                    equipmentHits = equipmentHits,
                                    partHits = partHits,
                                    toolingLines = toolingLines,
                                    empty = hits.isEmpty(),
                                    onOpenEquipment = onOpenEquipment,
                                    onOpenPart = onOpenPart
                                )
                            }
                            Column(
                                modifier = Modifier
                                    .weight(1f)
                                    .verticalScroll(rememberScrollState()),
                                verticalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                OpenTicketsSection(tickets, onOpenTicket)
                                OpenWorkOrdersSection(wos, onOpenWorkOrder)
                            }
                        }
                    } else {
                        Column(
                            modifier = Modifier
                                .weight(1f)
                                .verticalScroll(rememberScrollState())
                                .padding(horizontal = 16.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            MatchedAssetsSection(
                                equipmentHits = equipmentHits,
                                partHits = partHits,
                                toolingLines = toolingLines,
                                empty = hits.isEmpty(),
                                onOpenEquipment = onOpenEquipment,
                                onOpenPart = onOpenPart
                            )
                            OpenTicketsSection(tickets, onOpenTicket)
                            OpenWorkOrdersSection(wos, onOpenWorkOrder)
                            Spacer(modifier = Modifier.height(8.dp))
                        }
                    }

                    // Footer actions
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 12.dp)
                            .navigationBarsPadding(),
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        OutlinedButton(
                            onClick = onScanAgain,
                            modifier = Modifier
                                .weight(1f)
                                .height(52.dp),
                            shape = RoundedCornerShape(WccTokens.radiusMd)
                        ) {
                            Text("Scan again", fontWeight = FontWeight.Bold)
                        }
                        Button(
                            onClick = onDismiss,
                            modifier = Modifier
                                .weight(1f)
                                .height(52.dp),
                            shape = RoundedCornerShape(WccTokens.radiusMd)
                        ) {
                            Text("Done", fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun MatchedAssetsSection(
    equipmentHits: List<EquipmentDto>,
    partHits: List<InventoryPartDto>,
    toolingLines: List<String>,
    empty: Boolean,
    onOpenEquipment: (EquipmentDto) -> Unit,
    onOpenPart: (InventoryPartDto) -> Unit
) {
    SectionCard(
        title = "MATCHED IN DATABASE",
        icon = Icons.Default.Settings,
        accent = WccPrimary
    ) {
        when {
            empty -> EmptyLine("No equipment, part, or tooling matched this code.")
            else -> {
                equipmentHits.forEach { eq ->
                    AssetRow(
                        icon = Icons.Default.Settings,
                        title = eq.equip_name,
                        subtitle = listOfNotNull(
                            eq.asset_uuid,
                            eq.criticality?.let { "CRIT $it" },
                            listOfNotNull(eq.plant_name, eq.line_name).joinToString(" · ").ifBlank { null }
                        ).joinToString(" · "),
                        badge = "EQ",
                        onClick = { onOpenEquipment(eq) }
                    )
                }
                partHits.forEach { p ->
                    AssetRow(
                        icon = Icons.Default.Inventory2,
                        title = p.part_name,
                        subtitle = "${p.internal_code} · stock ${p.stock_level}",
                        badge = "PART",
                        onClick = { onOpenPart(p) }
                    )
                }
                toolingLines.forEach { name ->
                    AssetRow(
                        icon = Icons.Default.Build,
                        title = name,
                        subtitle = "Tooling registry",
                        badge = "TOOL",
                        onClick = null
                    )
                }
            }
        }
    }
}

@Composable
private fun OpenTicketsSection(
    tickets: List<TicketDto>,
    onOpenTicket: (TicketDto) -> Unit
) {
    SectionCard(
        title = "OPEN TICKETS (OT)",
        icon = Icons.Default.ConfirmationNumber,
        accent = Color(0xFFEF4444),
        count = tickets.size
    ) {
        if (tickets.isEmpty()) {
            EmptyLine("No open tickets on this asset.")
        } else {
            tickets.forEach { t ->
                WorkRow(
                    id = t.ticket_id,
                    title = t.fault_desc ?: "No description",
                    meta = listOfNotNull(
                        t.status?.uppercase(),
                        t.priority?.uppercase()?.let { "$it priority" },
                        t.pic?.let { "PIC $it" }
                    ).joinToString(" · "),
                    statusColor = ticketStatusColor(t.status),
                    onClick = { onOpenTicket(t) }
                )
            }
        }
    }
}

@Composable
private fun OpenWorkOrdersSection(
    wos: List<WorkOrderDto>,
    onOpenWorkOrder: (WorkOrderDto) -> Unit
) {
    SectionCard(
        title = "OPEN WORK ORDERS",
        icon = Icons.Default.Build,
        accent = Color(0xFFF59E0B),
        count = wos.size
    ) {
        if (wos.isEmpty()) {
            EmptyLine("No open work orders on this asset.")
        } else {
            wos.forEach { wo ->
                WorkRow(
                    id = "WO-${wo.wo_id}",
                    title = wo.title.ifBlank { "Work order" },
                    meta = listOfNotNull(
                        wo.status,
                        wo.scheduled_date?.let { "due $it" },
                        wo.equip_name
                    ).joinToString(" · "),
                    statusColor = WccWarning,
                    onClick = { onOpenWorkOrder(wo) }
                )
            }
        }
    }
}

@Composable
private fun SectionCard(
    title: String,
    icon: ImageVector,
    accent: Color,
    count: Int? = null,
    content: @Composable ColumnScope.() -> Unit
) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(WccTokens.radiusLg),
        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f),
        border = BorderStroke(WccTokens.borderThin, accent.copy(alpha = 0.3f))
    ) {
        Column(modifier = Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(icon, contentDescription = null, tint = accent, modifier = Modifier.size(18.dp))
                Spacer(modifier = Modifier.width(8.dp))
                Text(
                    title,
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Black,
                    color = accent,
                    modifier = Modifier.weight(1f)
                )
                count?.let {
                    Surface(
                        shape = RoundedCornerShape(50),
                        color = accent.copy(alpha = 0.15f)
                    ) {
                        Text(
                            "$it",
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Black,
                            color = accent
                        )
                    }
                }
            }
            Spacer(modifier = Modifier.height(10.dp))
            content()
        }
    }
}

@Composable
private fun AssetRow(
    icon: ImageVector,
    title: String,
    subtitle: String,
    badge: String,
    onClick: (() -> Unit)?
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .then(
                if (onClick != null) Modifier.clickable(
                    indication = null,
                    interactionSource = remember { MutableInteractionSource() },
                    onClick = onClick
                ) else Modifier
            )
            .padding(vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Box(
            modifier = Modifier
                .size(40.dp)
                .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.12f), CircleShape),
            contentAlignment = Alignment.Center
        ) {
            Icon(icon, contentDescription = null, tint = WccPrimary, modifier = Modifier.size(20.dp))
        }
        Spacer(modifier = Modifier.width(10.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(
                title,
                fontWeight = FontWeight.Bold,
                fontSize = 14.sp,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis
            )
            if (subtitle.isNotBlank()) {
                Text(
                    subtitle,
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
        Surface(
            shape = RoundedCornerShape(8.dp),
            color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f)
        ) {
            Text(
                badge,
                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                fontSize = 10.sp,
                fontWeight = FontWeight.Black,
                color = WccPrimary
            )
        }
    }
}

@Composable
private fun WorkRow(
    id: String,
    title: String,
    meta: String,
    statusColor: Color,
    onClick: () -> Unit
) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(
                indication = null,
                interactionSource = remember { MutableInteractionSource() },
                onClick = onClick
            ),
        shape = RoundedCornerShape(WccTokens.radiusMd),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.75f),
        border = BorderStroke(WccTokens.borderThin, statusColor.copy(alpha = 0.35f))
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    id,
                    fontWeight = FontWeight.Black,
                    fontSize = 13.sp,
                    color = statusColor,
                    modifier = Modifier.weight(1f)
                )
                Text(
                    "Open ›",
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary
                )
            }
            Text(
                title,
                fontWeight = FontWeight.SemiBold,
                fontSize = 13.sp,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis,
                modifier = Modifier.padding(top = 4.dp)
            )
            if (meta.isNotBlank()) {
                Text(
                    meta,
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                    modifier = Modifier.padding(top = 2.dp)
                )
            }
        }
    }
    Spacer(modifier = Modifier.height(6.dp))
}

@Composable
private fun EmptyLine(text: String) {
    Text(
        text,
        fontSize = 13.sp,
        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
        lineHeight = 18.sp
    )
}

@Composable
private fun SummaryChip(label: String) {
    Surface(
        shape = RoundedCornerShape(50),
        color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
    ) {
        Text(
            label,
            modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp),
            fontSize = 11.sp,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.primary
        )
    }
}

private fun ticketStatusColor(status: String?): Color = when (status?.uppercase()) {
    "ESCALATED" -> Color(0xFFEF4444)
    "OPEN" -> Color(0xFF0EA5E9)
    "PENDING" -> Color(0xFFF59E0B)
    "HOLD" -> Color(0xFF6B7280)
    else -> Color(0xFF0EA5E9)
}

internal fun hitToEquipment(kind: String?, data: Map<String, Any?>?): EquipmentDto? {
    if (kind != "equipment" || data == null) return null
    val id = (data["equip_id"] as? Number)?.toInt() ?: return null
    return EquipmentDto(
        equip_id = id,
        asset_uuid = data["asset_uuid"]?.toString(),
        equip_name = data["equip_name"]?.toString() ?: "Equipment",
        category = data["category"]?.toString(),
        criticality = data["criticality"]?.toString(),
        equipment_type = data["equipment_type"]?.toString(),
        oem_brand = data["oem_brand"]?.toString(),
        oem_model = data["oem_model"]?.toString(),
        oem_serial = data["oem_serial"]?.toString(),
        plant_name = data["plant_name"]?.toString(),
        line_name = data["line_name"]?.toString(),
        station_name = data["station_name"]?.toString()
    )
}

internal fun hitToPart(kind: String?, data: Map<String, Any?>?): InventoryPartDto? {
    if (kind != "part" || data == null) return null
    val id = (data["part_id"] as? Number)?.toInt() ?: return null
    return InventoryPartDto(
        part_id = id,
        part_name = data["part_name"]?.toString() ?: "Part",
        internal_code = data["internal_code"]?.toString() ?: "",
        stock_level = (data["stock_level"] as? Number)?.toInt() ?: 0
    )
}
