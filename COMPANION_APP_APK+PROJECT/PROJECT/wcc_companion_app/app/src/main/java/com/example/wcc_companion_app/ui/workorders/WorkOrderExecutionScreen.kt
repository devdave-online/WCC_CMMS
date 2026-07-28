package com.example.wcc_companion_app.ui.workorders

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
import com.example.wcc_companion_app.data.remote.models.PartConsumptionDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.data.remote.models.parsePartsList
import com.example.wcc_companion_app.ui.theme.WccPrimary
import com.example.wcc_companion_app.ui.tickets.TicketViewModel
import kotlinx.coroutines.launch

/**
 * Floor WO execution overlay — mirrors `_maint/wo_takeover.php`:
 * Start Work → notes + optional parts → Complete.
 */
@Composable
fun WorkOrderExecutionScreen(
    wo: WorkOrderDto,
    onDismiss: () -> Unit,
    onCompleted: () -> Unit,
    workOrderViewModel: WorkOrderViewModel = hiltViewModel(),
    ticketViewModel: TicketViewModel = hiltViewModel()
) {
    com.example.wcc_companion_app.ui.components.KeepScreenOn()
    var detail by remember { mutableStateOf(wo) }
    var notes by remember { mutableStateOf("") }
    var isBusy by remember { mutableStateOf(false) }
    var errorText by remember { mutableStateOf<String?>(null) }
    com.example.wcc_companion_app.ui.components.HapticOnError(errorText)
    val inventory by ticketViewModel.inventory.collectAsState()
    val scope = rememberCoroutineScope()

    // part_id -> qty for both planned + free-searched extras
    var consumeQty by remember { mutableStateOf<Map<Int, Int>>(emptyMap()) }
    // Planned part ids (from WO parts_list) — shown with "plan N" caption
    var plannedQty by remember { mutableStateOf<Map<Int, Int>>(emptyMap()) }
    // Extra parts added via search (not on the original WO plan)
    var extraPartIds by remember { mutableStateOf<Set<Int>>(emptySet()) }
    var partQuery by remember { mutableStateOf("") }

    LaunchedEffect(wo.wo_id) {
        ticketViewModel.refreshReferenceData()
        val fresh = workOrderViewModel.fetchWorkOrderDetail(wo.wo_id)
        if (fresh != null) detail = fresh
        val planned = parsePartsList(fresh?.parts_list ?: wo.parts_list)
        plannedQty = planned.toMap()
        // Seed planned at qty 0; tech raises qty for what they actually used
        consumeQty = planned.associate { it.first to 0 }
        extraPartIds = emptySet()
        partQuery = ""
    }

    val status = detail.status ?: wo.status ?: "Scheduled"
    val isStarted = status.equals("In Progress", true) || detail.started_at != null
    val isLocked = status.equals("Completed", true) || status.equals("Cancelled", true)

    val landscape =
        LocalConfiguration.current.orientation ==
            android.content.res.Configuration.ORIENTATION_LANDSCAPE

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Color.Black.copy(alpha = 0.78f))
                .padding(if (landscape) 8.dp else 0.dp),
            contentAlignment = Alignment.Center
        ) {
            Surface(
                modifier = Modifier
                    .windowInsetsPadding(WindowInsets.systemBars)
                    .fillMaxWidth(if (landscape) 0.92f else 0.94f)
                    .fillMaxHeight(if (landscape) 0.98f else 0.92f)
                    .imePadding(),
                shape = RoundedCornerShape(28.dp),
                color = MaterialTheme.colorScheme.surface.copy(alpha = 0.97f),
                border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline.copy(alpha = 0.35f))
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(20.dp)
                ) {
                    Column(modifier = Modifier.fillMaxWidth()) {
                        Text(
                            "WORK ORDER",
                            style = MaterialTheme.typography.labelLarge,
                            fontWeight = FontWeight.Bold,
                            color = WccPrimary
                        )
                        Text(
                            "WO-${detail.wo_id}",
                            style = MaterialTheme.typography.headlineSmall,
                            fontWeight = FontWeight.Black
                        )
                    }

                    Text(
                        detail.title.ifBlank { wo.title },
                        fontWeight = FontWeight.SemiBold,
                        fontSize = 16.sp,
                        maxLines = 3
                    )
                    // Separate rows so long equip names never split mid-phrase after the status.
                    Text(
                        "Status: $status",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f)
                    )
                    Text(
                        detail.equip_name ?: wo.equip_name ?: "Unassigned equipment",
                        fontSize = 13.sp,
                        maxLines = 2,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f)
                    )
                    detail.scheduled_date?.let {
                        Text("Scheduled: $it", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f))
                    }
                    detail.started_at?.let {
                        Text("Started: $it", fontSize = 12.sp, color = MaterialTheme.colorScheme.primary)
                    }

                    Spacer(modifier = Modifier.height(12.dp))

                    // Single scroll column (incl. actions) so landscape never clips
                    // description / parts under a fixed footer button.
                    Column(
                        modifier = Modifier
                            .weight(1f, fill = true)
                            .fillMaxWidth()
                            .verticalScroll(rememberScrollState()),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text(
                            "DESCRIPTION",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                        )
                        Text(
                            detail.description ?: wo.description ?: "No description",
                            lineHeight = 22.sp
                        )

                        // ── PARTS: planned list + free search for unplanned allocations ──
                        Text(
                            "PARTS USED",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                        )
                        Text(
                            "Planned for this WO, plus any extra parts you search and allocate.",
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                            lineHeight = 16.sp
                        )

                        // Planned rows
                        plannedQty.forEach { (partId, plan) ->
                            val inv = inventory.find { it.part_id == partId }
                            val name = inv?.part_name ?: "Part #$partId"
                            val stock = inv?.stock_level ?: 0
                            val qty = consumeQty[partId] ?: 0
                            PartQtyRow(
                                label = "$name\nplan $plan · stock $stock",
                                qty = qty,
                                max = maxOf(stock, 1),
                                enabled = isStarted && !isLocked && !isBusy,
                                onChange = { n ->
                                    consumeQty = consumeQty + (partId to n.coerceAtLeast(0))
                                }
                            )
                        }

                        // Extra (search-added) rows
                        extraPartIds.forEach { partId ->
                            val inv = inventory.find { it.part_id == partId }
                            if (inv == null) return@forEach
                            val qty = consumeQty[partId] ?: 1
                            PartQtyRow(
                                label = "${inv.part_name}\n${inv.internal_code} · stock ${inv.stock_level} · extra",
                                qty = qty,
                                max = maxOf(inv.stock_level, 1),
                                enabled = isStarted && !isLocked && !isBusy,
                                onChange = { n ->
                                    if (n <= 0) {
                                        extraPartIds = extraPartIds - partId
                                        consumeQty = consumeQty - partId
                                    } else {
                                        consumeQty = consumeQty + (partId to n)
                                    }
                                }
                            )
                        }

                        if (plannedQty.isEmpty() && extraPartIds.isEmpty()) {
                            Text(
                                "No planned parts. Search below to allocate any stock item.",
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.45f)
                            )
                        }

                        // Free-text search — same idea as takeover / web wo_takeover
                        if (isStarted && !isLocked) {
                            val matches = remember(partQuery, inventory, consumeQty) {
                                if (partQuery.length < 2) emptyList()
                                else inventory.filter { p ->
                                    (p.part_name.contains(partQuery, true) ||
                                        p.internal_code.contains(partQuery, true)) &&
                                        !consumeQty.containsKey(p.part_id)
                                }.take(5)
                            }

                            OutlinedTextField(
                                value = partQuery,
                                onValueChange = { partQuery = it },
                                modifier = Modifier.fillMaxWidth(),
                                label = { Text("Search parts to add") },
                                placeholder = { Text("Name or code not on the plan…") },
                                singleLine = true,
                                shape = RoundedCornerShape(14.dp),
                                enabled = !isBusy
                            )

                            matches.forEach { p ->
                                PartSearchHit(
                                    part = p,
                                    onAdd = {
                                        extraPartIds = extraPartIds + p.part_id
                                        consumeQty = consumeQty + (p.part_id to 1)
                                        partQuery = ""
                                    }
                                )
                            }
                        }

                        if (isStarted && !isLocked) {
                            OutlinedTextField(
                                value = notes,
                                onValueChange = { notes = it; errorText = null },
                                modifier = Modifier.fillMaxWidth(),
                                label = { Text("Technician notes / action taken") },
                                minLines = if (landscape) 2 else 3,
                                shape = RoundedCornerShape(14.dp)
                            )

                            val evidence by workOrderViewModel.observeEvidence(detail.wo_id)
                                .collectAsState(initial = emptyList())
                            com.example.wcc_companion_app.ui.components.PhotoEvidenceStrip(
                                items = evidence,
                                onAddUri = { uri, mime ->
                                    workOrderViewModel.addEvidence(detail.wo_id, uri, mime)
                                },
                                onRemove = { id -> workOrderViewModel.removeEvidence(id) },
                            )
                        }

                        if (isLocked) {
                            Text(
                                "This work order is locked ($status).",
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f)
                            )
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                    }

                    // Errors sit above the sticky CTA so they are never covered by the dock.
                    errorText?.let {
                        Text(
                            it,
                            color = MaterialTheme.colorScheme.error,
                            fontWeight = FontWeight.SemiBold,
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 4.dp, vertical = 6.dp)
                        )
                    }

                    // Sticky CTA dock — never buried under notes/parts scroll
                    if (!isLocked) {
                        com.example.wcc_companion_app.ui.components.WccStickyActionBar {
                            if (!isStarted) {
                                com.example.wcc_companion_app.ui.components.WccPrimaryButton(
                                    label = "START WORK",
                                    onClick = {
                                        isBusy = true
                                        errorText = null
                                        workOrderViewModel.startWorkOrder(
                                            woId = detail.wo_id,
                                            onComplete = {
                                                scope.launch {
                                                    val fresh = workOrderViewModel.fetchWorkOrderDetail(detail.wo_id)
                                                    if (fresh != null) detail = fresh
                                                    isBusy = false
                                                }
                                            },
                                            onError = { msg ->
                                                isBusy = false
                                                errorText = msg
                                            }
                                        )
                                    },
                                    enabled = !isBusy,
                                    loading = isBusy,
                                    containerColor = WccPrimary,
                                    height = 56.dp,
                                    modifier = Modifier.weight(1f)
                                )
                            } else {
                                com.example.wcc_companion_app.ui.components.WccPrimaryButton(
                                    label = "COMPLETE WORK ORDER",
                                    onClick = {
                                        if (notes.isBlank()) {
                                            errorText = "Notes are required to complete."
                                        } else {
                                            isBusy = true
                                            errorText = null
                                            val parts = consumeQty
                                                .filter { it.value > 0 }
                                                .map { PartConsumptionDto(part_id = it.key, qty = it.value) }
                                            workOrderViewModel.completeWorkOrder(
                                                woId = detail.wo_id,
                                                notes = notes,
                                                parts = parts,
                                                onComplete = {
                                                    isBusy = false
                                                    onCompleted()
                                                },
                                                onError = { msg ->
                                                    isBusy = false
                                                    errorText = msg
                                                }
                                            )
                                        }
                                    },
                                    enabled = !isBusy,
                                    loading = isBusy,
                                    containerColor = Color(0xFF10B981),
                                    height = 56.dp,
                                    modifier = Modifier.weight(1f)
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun PartSearchHit(part: InventoryPartDto, onAdd: () -> Unit) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(
                indication = null,
                interactionSource = remember { MutableInteractionSource() },
                onClick = onAdd
            )
            .padding(vertical = 8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            Icons.Default.Add,
            contentDescription = "Add part",
            tint = WccPrimary,
            modifier = Modifier.size(20.dp)
        )
        Spacer(modifier = Modifier.width(10.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(
                part.part_name,
                fontSize = 14.sp,
                fontWeight = FontWeight.SemiBold,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            Text(
                "${part.internal_code} · ${part.stock_level} in stock",
                fontSize = 11.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
            )
        }
    }
}

@Composable
private fun PartQtyRow(
    label: String,
    qty: Int,
    max: Int,
    enabled: Boolean,
    onChange: (Int) -> Unit
) {
    Surface(
        shape = RoundedCornerShape(12.dp),
        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f)
    ) {
        // Stack label above steppers so long part names never crush the +/- controls.
        Column(
            modifier = Modifier.fillMaxWidth().padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Text(label, fontSize = 13.sp, lineHeight = 18.sp)
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.End
            ) {
                IconButton(
                    onClick = { onChange(qty - 1) },
                    enabled = enabled && qty > 0,
                    modifier = Modifier.size(48.dp)
                ) {
                    Text("−", fontSize = 22.sp, fontWeight = FontWeight.Black)
                }
                Text(
                    "$qty",
                    fontWeight = FontWeight.Bold,
                    fontSize = 16.sp,
                    modifier = Modifier.widthIn(min = 32.dp)
                )
                IconButton(
                    onClick = { onChange(qty + 1) },
                    enabled = enabled && qty < max,
                    modifier = Modifier.size(48.dp)
                ) {
                    Text("+", fontSize = 22.sp, fontWeight = FontWeight.Black)
                }
            }
        }
    }
}

// parsePartsList lives in data.remote.models.WorkOrderParts (shared with history detail)
