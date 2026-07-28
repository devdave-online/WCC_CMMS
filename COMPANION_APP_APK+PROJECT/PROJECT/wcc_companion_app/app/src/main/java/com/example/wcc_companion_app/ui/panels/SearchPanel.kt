package com.example.wcc_companion_app.ui.panels

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.platform.LocalSoftwareKeyboardController
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.remote.models.EquipmentDto
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.ToolingDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

/**
 * Rail-END panel: global search + QR/DataMatrix scan across equipment, parts, toolings.
 * Local filter: tickets / work orders already in memory.
 */
@Composable
fun SearchPanel(
    isDark: Boolean,
    tickets: List<TicketDto>,
    workOrders: List<WorkOrderDto>,
    apiService: WccApiService,
    onOpenTicket: (TicketDto) -> Unit,
    onOpenEquipment: (EquipmentDto) -> Unit = {},
    onOpenPart: (InventoryPartDto) -> Unit = {},
    onOpenWorkOrder: (WorkOrderDto) -> Unit = {},
    onClose: () -> Unit
) {
    var query by remember { mutableStateOf("") }
    var parts by remember { mutableStateOf<List<InventoryPartDto>>(emptyList()) }
    var equipment by remember { mutableStateOf<List<EquipmentDto>>(emptyList()) }
    var toolings by remember { mutableStateOf<List<ToolingDto>>(emptyList()) }
    var remoteError by remember { mutableStateOf<String?>(null) }
    var isSearching by remember { mutableStateOf(false) }
    var toolingsNote by remember { mutableStateOf<String?>(null) }

    var showScanner by remember { mutableStateOf(false) }
    var scanStatus by remember { mutableStateOf<String?>(null) }
    var isLookingUp by remember { mutableStateOf(false) }
    var scanSummary by remember { mutableStateOf<String?>(null) }
    var scanResult by remember {
        mutableStateOf<com.example.wcc_companion_app.data.remote.models.ScanLookupDataDto?>(null)
    }
    val scope = rememberCoroutineScope()
    val focusManager = LocalFocusManager.current
    val keyboard = LocalSoftwareKeyboardController.current

    val q = query.trim()

    val matchedTickets = remember(q, tickets) {
        if (q.length < 2) emptyList() else tickets.filter {
            it.ticket_id.contains(q, true) ||
                (it.fault_desc?.contains(q, true) == true) ||
                (it.pic?.contains(q, true) == true)
        }
    }
    val matchedWos = remember(q, workOrders) {
        if (q.length < 2) emptyList() else workOrders.filter {
            it.title.contains(q, true) ||
                (it.equip_name?.contains(q, true) == true) ||
                "WO-${it.wo_id}".contains(q, true)
        }
    }

    LaunchedEffect(q) {
        if (q.length < 2) {
            parts = emptyList()
            equipment = emptyList()
            toolings = emptyList()
            remoteError = null
            toolingsNote = null
            isSearching = false
            return@LaunchedEffect
        }
        isSearching = true
        delay(350)
        remoteError = null
        try {
            val partsResp = apiService.getInventory(search = q)
            parts = if (partsResp.isSuccessful) partsResp.body()?.data.orEmpty() else emptyList()

            val eqResp = apiService.searchEquipment(search = q)
            equipment = if (eqResp.isSuccessful) eqResp.body()?.data.orEmpty() else emptyList()

            val tResp = apiService.searchToolings(search = q)
            if (tResp.isSuccessful) {
                toolings = tResp.body()?.data.orEmpty()
                // empty + no table → soft note
                if (toolings.isEmpty()) {
                    toolingsNote = "Toolings registry not online yet (table under development)."
                } else toolingsNote = null
            } else {
                toolings = emptyList()
                toolingsNote = "Toolings lookup unavailable."
            }
        } catch (e: Exception) {
            parts = emptyList()
            equipment = emptyList()
            toolings = emptyList()
            remoteError = "Server unreachable — showing local results only"
        }
        isSearching = false
    }

    Box(modifier = Modifier.fillMaxSize()) {
        PanelScaffold(
            isDark = isDark,
            title = "Search & Scan",
            subtitle = "Tickets · WOs · equipment · parts · toolings",
            edge = PanelEdge.END,
            onClose = onClose,
            header = {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    OutlinedTextField(
                        value = query,
                        onValueChange = { query = it },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        shape = RoundedCornerShape(16.dp),
                        placeholder = { Text("Search…") },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
                        keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search)
                    )
                    Spacer(modifier = Modifier.width(12.dp))
                    Surface(
                        modifier = Modifier
                            .size(56.dp)
                            .clickable(
                                indication = null,
                                interactionSource = remember { MutableInteractionSource() },
                                onClick = {
                                    // Keyboard was covering the reticle / Close in portrait QA.
                                    focusManager.clearFocus(force = true)
                                    keyboard?.hide()
                                    scanStatus = null
                                    scanSummary = null
                                    val typed = query.trim()
                                    if (typed.length >= 2) {
                                        // Treat field text as a scanned code (same DB lookup + modal).
                                        scope.launch {
                                            isLookingUp = true
                                            scanStatus = "Looking up $typed…"
                                            try {
                                                val resp = apiService.scanLookup(typed)
                                                val body = resp.body()
                                                if (resp.isSuccessful && body?.status == "success" && body.data != null) {
                                                    val data = body.data
                                                    val hits = data.hits.orEmpty()
                                                    if (hits.isEmpty()) {
                                                        scanSummary = "No hit for $typed"
                                                        scanStatus = null
                                                    } else {
                                                        val ot = data.open_ticket_count
                                                            ?: data.open_tickets?.size ?: 0
                                                        val ow = data.open_wo_count
                                                            ?: data.open_work_orders?.size ?: 0
                                                        scanSummary =
                                                            "DB hit · ${hits.size} asset(s) · $ot open OT · $ow open WO"
                                                        scanStatus = null
                                                        scanResult = data
                                                    }
                                                } else {
                                                    scanStatus = body?.message ?: "Lookup failed"
                                                }
                                            } catch (_: Exception) {
                                                scanStatus = "Server unreachable — could not look up \"$typed\""
                                            }
                                            isLookingUp = false
                                        }
                                    } else {
                                        showScanner = true
                                    }
                                }
                            ),
                        shape = RoundedCornerShape(16.dp),
                        color = MaterialTheme.colorScheme.primary.copy(alpha = 0.18f),
                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.45f))
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(
                                Icons.Default.QrCodeScanner,
                                contentDescription = "Scan QR or DataMatrix",
                                tint = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(26.dp)
                            )
                        }
                    }
                }
                Spacer(modifier = Modifier.height(16.dp))
            }
        ) {
            scanSummary?.let { summary ->
                PanelCard(isDark, "LAST SCAN") {
                    Text(summary, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface)
                }
            }

            when {
                q.length < 2 && scanSummary == null ->
                    PanelEmpty("Type at least 2 characters, or scan QR / DataMatrix.")
                q.length < 2 -> Unit
                else -> {
                    if (isSearching) {
                        LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
                    }
                    remoteError?.let { PanelEmpty(it) }

                    val total = matchedTickets.size + matchedWos.size +
                        equipment.size + parts.size + toolings.size
                    if (total == 0 && !isSearching) {
                        PanelEmpty("No matches for \"$q\".")
                    }

                    if (matchedTickets.isNotEmpty()) {
                        PanelCard(isDark, "TICKETS (${matchedTickets.size})") {
                            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                                matchedTickets.take(12).forEach { t ->
                                    ResultRow(
                                        primary = t.ticket_id,
                                        secondary = t.fault_desc ?: "No description",
                                        trailing = (t.status ?: "").uppercase(),
                                        onClick = { onOpenTicket(t) }
                                    )
                                }
                            }
                        }
                    }
                    if (matchedWos.isNotEmpty()) {
                        PanelCard(isDark, "WORK ORDERS (${matchedWos.size})") {
                            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                                matchedWos.take(12).forEach { w ->
                                    ResultRow(
                                        primary = "WO-${w.wo_id} · ${w.title}",
                                        secondary = w.equip_name ?: "Unassigned equipment",
                                        trailing = w.status ?: ""
                                    )
                                }
                            }
                        }
                    }
                    if (equipment.isNotEmpty()) {
                        PanelCard(isDark, "EQUIPMENT (${equipment.size})") {
                            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                                equipment.take(12).forEach { e ->
                                    ResultRow(
                                        primary = e.equip_name,
                                        secondary = listOfNotNull(e.asset_uuid, e.line_name)
                                            .joinToString(" · ").ifBlank { "No asset tag" },
                                        trailing = e.criticality ?: "",
                                        onClick = { onOpenEquipment(e) }
                                    )
                                }
                            }
                        }
                    }
                    if (parts.isNotEmpty()) {
                        PanelCard(isDark, "PARTS (${parts.size})") {
                            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                                parts.take(12).forEach { p ->
                                    ResultRow(
                                        primary = p.part_name,
                                        secondary = p.internal_code.ifBlank { "No code" },
                                        trailing = "x${p.stock_level}",
                                        onClick = { onOpenPart(p) }
                                    )
                                }
                            }
                        }
                    }
                    PanelCard(isDark, "TOOLINGS (${toolings.size})") {
                        if (toolings.isEmpty()) {
                            PanelEmpty(toolingsNote ?: "No tooling matches.")
                        } else {
                            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                                toolings.take(12).forEach { t ->
                                    ResultRow(
                                        primary = t.tooling_name ?: "Tooling",
                                        secondary = listOfNotNull(t.tooling_code, t.barcode, t.location)
                                            .joinToString(" · "),
                                        trailing = t.status ?: ""
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }

        if (showScanner) {
            com.example.wcc_companion_app.ui.scan.ScannerScreen(
                title = "Scan QR or DataMatrix",
                statusMessage = scanStatus,
                isLookingUp = isLookingUp,
                onClose = { showScanner = false },
                onCodeScanned = { code ->
                    if (!isLookingUp) {
                        scope.launch {
                            isLookingUp = true
                            scanStatus = "Looking up $code…"
                            try {
                                val resp = apiService.scanLookup(code)
                                val body = resp.body()
                                if (resp.isSuccessful && body?.status == "success" && body.data != null) {
                                    val data = body.data
                                    val hits = data.hits.orEmpty()
                                    if (hits.isEmpty()) {
                                        scanStatus = "No equipment / part / tooling matches \"$code\""
                                        scanSummary = "No hit for $code"
                                    } else {
                                        val ot = data.open_ticket_count
                                            ?: data.open_tickets?.size
                                            ?: 0
                                        val ow = data.open_wo_count
                                            ?: data.open_work_orders?.size
                                            ?: 0
                                        scanSummary =
                                            "DB hit · ${hits.size} asset(s) · $ot open OT · $ow open WO"
                                        scanStatus = null
                                        showScanner = false
                                        query = code
                                        scanResult = data
                                    }
                                } else {
                                    scanStatus = body?.message ?: "Lookup failed"
                                }
                            } catch (e: Exception) {
                                scanStatus = "Server unreachable — could not look up \"$code\""
                            }
                            isLookingUp = false
                        }
                    }
                }
            )
        }

        scanResult?.let { result ->
            com.example.wcc_companion_app.ui.scan.ScanResultModal(
                result = result,
                onDismiss = { scanResult = null },
                onScanAgain = {
                    scanResult = null
                    scanStatus = null
                    showScanner = true
                },
                onOpenEquipment = {
                    scanResult = null
                    onOpenEquipment(it)
                },
                onOpenPart = {
                    scanResult = null
                    onOpenPart(it)
                },
                onOpenTicket = {
                    scanResult = null
                    onOpenTicket(it)
                },
                onOpenWorkOrder = {
                    scanResult = null
                    onOpenWorkOrder(it)
                }
            )
        }
    }
}

@Composable
private fun ResultRow(
    primary: String,
    secondary: String,
    trailing: String,
    onClick: (() -> Unit)? = null
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
            ),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                primary,
                fontWeight = FontWeight.Bold,
                fontSize = 14.sp,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
                color = MaterialTheme.colorScheme.onSurface
            )
            Text(
                secondary,
                fontSize = 12.sp,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
            )
        }
        if (trailing.isNotBlank()) {
            Spacer(modifier = Modifier.width(8.dp))
            Text(
                trailing,
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
        }
    }
}
