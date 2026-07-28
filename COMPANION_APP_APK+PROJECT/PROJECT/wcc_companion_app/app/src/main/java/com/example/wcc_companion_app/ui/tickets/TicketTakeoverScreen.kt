package com.example.wcc_companion_app.ui.tickets

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Remove
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Warning
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
import com.example.wcc_companion_app.data.remote.models.TakeoverRequestDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.ui.theme.*
import java.time.Duration
import java.time.LocalDateTime
import java.time.format.DateTimeFormatter

/** A part chosen for consumption, with the quantity actually used. */
private data class SelectedPart(val part: InventoryPartDto, val qty: Int)

private val SQL_FMT: DateTimeFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")
// Time is the value that matters here, so it gets the prominent slot and the date is
// demoted to a caption — a combined "dd MMM · HH:mm" clipped in the narrow field.
private val TIME_FMT: DateTimeFormatter = DateTimeFormatter.ofPattern("HH:mm")
private val DATE_FMT: DateTimeFormatter = DateTimeFormatter.ofPattern("dd MMM")

/**
 * Log Intervention — the app's mirror of the web's `_maint/takeover.php`.
 *
 * Fixes three real data-capture gaps against that reference:
 *  1. Start/End time were hardcoded to `now`, so `ticket_actions.action_start/action_end`
 *     were identical and every intervention recorded ZERO wrench time. They are now
 *     first-class, editable inputs with a live duration readout.
 *  2. `parts_consumed_data` was always empty, so stock was never decremented and no
 *     `inventory_ledger` row was written even though the backend supports both. Parts are
 *     now a real multi-line selection with quantities.
 *  3. No validation and no double-submit lock.
 *
 * Escalation drives the action mode: pick nobody and you can only Finish; pick someone
 * and you can only Escalate.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TicketTakeoverScreen(
    ticket: TicketDto,
    onDismiss: () -> Unit,
    onComplete: () -> Unit,
    viewModel: TicketViewModel = hiltViewModel()
) {
    com.example.wcc_companion_app.ui.components.KeepScreenOn()
    val teamMembers by viewModel.teamMembers.collectAsState()
    val inventory by viewModel.inventory.collectAsState()

    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE

    // ── TIMING (the previously missing data) ──
    var startAt by remember { mutableStateOf(LocalDateTime.now().minusMinutes(30)) }
    var endAt by remember { mutableStateOf(LocalDateTime.now()) }
    var picking by remember { mutableStateOf<String?>(null) } // "start" | "end" | null

    val durationMinutes = remember(startAt, endAt) {
        Duration.between(startAt, endAt).toMinutes()
    }

    // ── CLASSIFICATION / DIAGNOSIS ──
    val faultTypes = listOf(
        "Mechanical", "Electrical", "Pneumatic/Hydraulic",
        "Software/Controls", "Tooling/Fixture", "Operator Error", "Other"
    )
    var faultType by remember { mutableStateOf("") } // no default: force a deliberate choice
    var faultTypeExpanded by remember { mutableStateOf(false) }
    var rootCause by remember { mutableStateOf("") }
    var actionTaken by remember { mutableStateOf("") }

    // ── PARTS ──
    var partQuery by remember { mutableStateOf("") }
    var selectedParts by remember { mutableStateOf<List<SelectedPart>>(emptyList()) }

    // ── ESCALATION (drives which action is available) ──
    val noEscalation = "-- No escalation --"
    var escalatedTo by remember { mutableStateOf(noEscalation) }
    var escalateExpanded by remember { mutableStateOf(false) }
    val isEscalating = escalatedTo != noEscalation

    var isSubmitting by remember { mutableStateOf(false) }
    var errorText by remember { mutableStateOf<String?>(null) }
    com.example.wcc_companion_app.ui.components.HapticOnError(errorText)

    LaunchedEffect(Unit) { viewModel.refreshReferenceData() }

    fun validate(): String? = when {
        faultType.isBlank() -> "Select a fault type."
        rootCause.isBlank() -> "Root cause is required."
        actionTaken.isBlank() -> "Action taken is required."
        durationMinutes < 0 -> "End time is before start time."
        selectedParts.any { it.qty > it.part.stock_level } ->
            "A part quantity exceeds available stock."
        else -> null
    }

    fun submit(actionType: String) {
        val problem = validate()
        if (problem != null) { errorText = problem; return }
        errorText = null
        isSubmitting = true
        viewModel.submitTakeover(
            TakeoverRequestDto(
                ticket_id = ticket.ticket_id,
                tech_name = "", // filled by VM from the signed-in user
                action_start = startAt.format(SQL_FMT),
                action_end = endAt.format(SQL_FMT),
                fault_type = faultType,
                root_cause = rootCause,
                action_taken = actionTaken,
                // Same human-readable shape the web writes into ticket_actions.parts_used
                parts_used = if (selectedParts.isEmpty()) "None" else selectedParts.joinToString("; ") {
                    "ID: ${it.part.part_id} | ${it.part.part_name} (${it.part.internal_code}) x${it.qty}"
                },
                escalated_to = if (isEscalating) escalatedTo else "None",
                action_type = actionType,
                // Actually consume stock — the backend decrements inventory_parts and
                // writes inventory_ledger from this list.
                parts_consumed_data = selectedParts.map {
                    PartConsumptionDto(part_id = it.part.part_id, qty = it.qty)
                }
            ),
            onComplete = onComplete,
            onError = { msg -> isSubmitting = false; errorText = msg }
        )
    }

    Dialog(onDismissRequest = onDismiss, properties = DialogProperties(usePlatformDefaultWidth = false)) {
        Surface(
            modifier = Modifier
                .fillMaxWidth(if (isLandscape) 0.95f else 0.94f)
                .fillMaxHeight(0.95f)
                .windowInsetsPadding(WindowInsets.systemBars)
                .imePadding(),
            shape = RoundedCornerShape(24.dp),
            color = MaterialTheme.colorScheme.surface.copy(alpha = 0.97f),
            tonalElevation = 8.dp
        ) {
            Column(modifier = Modifier.fillMaxSize()) {

                // ── HEADER (dismiss: Back / tap outside — no in-header X) ──
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 20.dp, end = 20.dp, top = 16.dp, bottom = 8.dp)
                ) {
                    Text(
                        // Web labels HOLD reopen as "Resume Job" — same form, clearer intent.
                        if (ticket.status.equals("HOLD", ignoreCase = true))
                            "Resume Job"
                        else
                            "Log Intervention",
                        style = MaterialTheme.typography.headlineSmall,
                        fontWeight = FontWeight.Black,
                        color = WccPrimary
                    )
                    Text(
                        ticket.ticket_id + if (ticket.status.equals("HOLD", ignoreCase = true))
                            "  ·  was on HOLD"
                        else "",
                        fontSize = 13.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f)
                    )
                }

                Column(
                    modifier = Modifier
                        .weight(1f)
                        .verticalScroll(rememberScrollState())
                        .padding(horizontal = 20.dp)
                ) {
                  // Landscape has width but very little height, so the form splits into two
                  // panes instead of becoming a scroll tunnel. Portrait stays one column.
                  AdaptiveTwoPane(isLandscape = isLandscape, left = {
                    // ── CONTEXT ──
                    ContextCard(ticket)

                    // ── TIMING ──
                    SectionCard("TIMING", accent = WccPrimary) {
                        FormRow(isLandscape) {
                            TimeField(
                                label = "Start time",
                                value = startAt,
                                modifier = Modifier.weight(1f),
                                onClick = { picking = "start" }
                            )
                            TimeField(
                                label = "End time",
                                value = endAt,
                                modifier = Modifier.weight(1f),
                                onClick = { picking = "end" }
                            )
                        }

                        Spacer(modifier = Modifier.height(10.dp))

                        // Glove-friendly backdating: far faster than a picker on the floor.
                        Text(
                            "Quick set start",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        // Scrollable so chips never wrap into unreadable stacked text.
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .horizontalScroll(rememberScrollState()),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            listOf(15, 30, 60, 120).forEach { mins ->
                                QuickChip("-${if (mins < 60) "${mins}m" else "${mins / 60}h"}") {
                                    startAt = endAt.minusMinutes(mins.toLong())
                                }
                            }
                            QuickChip("End now") { endAt = LocalDateTime.now() }
                        }

                        Spacer(modifier = Modifier.height(12.dp))
                        DurationBanner(durationMinutes)
                    }

                    // ── CLASSIFICATION ──
                    SectionCard("CLASSIFICATION", accent = StatusCriticalityHigh) {
                        ExposedDropdownMenuBox(
                            expanded = faultTypeExpanded,
                            onExpandedChange = { faultTypeExpanded = it }
                        ) {
                            OutlinedTextField(
                                value = faultType.ifBlank { "-- Select --" },
                                onValueChange = {},
                                readOnly = true,
                                isError = faultType.isBlank() && errorText != null,
                                label = { Text("Fault type *") },
                                trailingIcon = {
                                    ExposedDropdownMenuDefaults.TrailingIcon(expanded = faultTypeExpanded)
                                },
                                colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(),
                                modifier = Modifier.fillMaxWidth().menuAnchor()
                            )
                            ExposedDropdownMenu(
                                expanded = faultTypeExpanded,
                                onDismissRequest = { faultTypeExpanded = false }
                            ) {
                                faultTypes.forEach { option ->
                                    DropdownMenuItem(
                                        text = { Text(option) },
                                        onClick = { faultType = option; faultTypeExpanded = false }
                                    )
                                }
                            }
                        }
                    }

                  }, right = {
                    // ── DIAGNOSIS ──
                    SectionCard("DIAGNOSIS", accent = WccSuccess) {
                        OutlinedTextField(
                            value = rootCause,
                            onValueChange = { rootCause = it },
                            label = { Text("Root cause *") },
                            placeholder = { Text("Why did it break?") },
                            isError = rootCause.isBlank() && errorText != null,
                            modifier = Modifier.fillMaxWidth()
                        )
                        Spacer(modifier = Modifier.height(10.dp))
                        OutlinedTextField(
                            value = actionTaken,
                            onValueChange = { actionTaken = it },
                            label = { Text("Action taken *") },
                            placeholder = { Text("What exactly did you do to fix it?") },
                            isError = actionTaken.isBlank() && errorText != null,
                            minLines = if (isLandscape) 2 else 3,
                            modifier = Modifier.fillMaxWidth()
                        )
                    }

                    // ── PARTS CONSUMED ──
                    SectionCard("PARTS CONSUMED", accent = StatusCriticalityNormal) {
                        val matches = remember(partQuery, inventory, selectedParts) {
                            if (partQuery.isBlank()) emptyList()
                            else inventory.filter { p ->
                                (p.part_name.contains(partQuery, true) ||
                                    p.internal_code.contains(partQuery, true)) &&
                                    selectedParts.none { it.part.part_id == p.part_id }
                            }.take(4)
                        }

                        OutlinedTextField(
                            value = partQuery,
                            onValueChange = { partQuery = it },
                            label = { Text("Search parts to add") },
                            placeholder = { Text("Name or code…") },
                            modifier = Modifier.fillMaxWidth()
                        )

                        matches.forEach { p ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable(
                                        indication = null,
                                        interactionSource = remember { MutableInteractionSource() }
                                    ) {
                                        selectedParts = selectedParts + SelectedPart(p, 1)
                                        partQuery = ""
                                    }
                                    .padding(vertical = 10.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Icon(
                                    Icons.Default.Add,
                                    contentDescription = null,
                                    tint = WccPrimary,
                                    modifier = Modifier.size(18.dp)
                                )
                                Spacer(modifier = Modifier.width(8.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(
                                        p.part_name,
                                        fontSize = 14.sp,
                                        fontWeight = FontWeight.SemiBold,
                                        maxLines = 1,
                                        overflow = TextOverflow.Ellipsis
                                    )
                                    Text(
                                        "${p.internal_code} · ${p.stock_level} in stock",
                                        fontSize = 11.sp,
                                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                                    )
                                }
                            }
                        }

                        if (selectedParts.isEmpty()) {
                            Text(
                                "No parts consumed.",
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.45f),
                                modifier = Modifier.padding(top = 8.dp)
                            )
                        } else {
                            Spacer(modifier = Modifier.height(8.dp))
                            selectedParts.forEach { sel ->
                                val over = sel.qty > sel.part.stock_level
                                Row(
                                    modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Column(modifier = Modifier.weight(1f)) {
                                        Text(
                                            sel.part.part_name,
                                            fontSize = 14.sp,
                                            fontWeight = FontWeight.SemiBold,
                                            maxLines = 1,
                                            overflow = TextOverflow.Ellipsis
                                        )
                                        Text(
                                            if (over) "Only ${sel.part.stock_level} in stock!"
                                            else "${sel.part.internal_code} · ${sel.part.stock_level} in stock",
                                            fontSize = 11.sp,
                                            fontWeight = if (over) FontWeight.Bold else FontWeight.Normal,
                                            color = if (over) WccError
                                                    else MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                                        )
                                    }
                                    QtyStepper(
                                        qty = sel.qty,
                                        onChange = { newQty ->
                                            selectedParts = if (newQty <= 0) {
                                                selectedParts.filterNot { it.part.part_id == sel.part.part_id }
                                            } else {
                                                selectedParts.map {
                                                    if (it.part.part_id == sel.part.part_id) it.copy(qty = newQty) else it
                                                }
                                            }
                                        }
                                    )
                                }
                            }
                        }
                    }

                    // ── PHOTO EVIDENCE (offline-queued) ──
                    SectionCard("EVIDENCE PHOTOS", accent = WccPrimary) {
                        val evidence by viewModel.observeEvidence(ticket.ticket_id)
                            .collectAsState(initial = emptyList())
                        com.example.wcc_companion_app.ui.components.PhotoEvidenceStrip(
                            items = evidence,
                            onAddUri = { uri, mime ->
                                viewModel.addEvidence(ticket.ticket_id, uri, mime)
                            },
                            onRemove = { id -> viewModel.removeEvidence(id) },
                        )
                    }

                    // ── ESCALATION ──
                    SectionCard("ESCALATION", accent = TicketStatusEscalated) {
                        ExposedDropdownMenuBox(
                            expanded = escalateExpanded,
                            onExpandedChange = { escalateExpanded = it }
                        ) {
                            OutlinedTextField(
                                value = escalatedTo,
                                onValueChange = {},
                                readOnly = true,
                                label = { Text("Escalate to") },
                                trailingIcon = {
                                    ExposedDropdownMenuDefaults.TrailingIcon(expanded = escalateExpanded)
                                },
                                colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(),
                                modifier = Modifier.fillMaxWidth().menuAnchor()
                            )
                            ExposedDropdownMenu(
                                expanded = escalateExpanded,
                                onDismissRequest = { escalateExpanded = false }
                            ) {
                                DropdownMenuItem(
                                    text = { Text(noEscalation) },
                                    onClick = { escalatedTo = noEscalation; escalateExpanded = false }
                                )
                                teamMembers.forEach { m ->
                                    DropdownMenuItem(
                                        text = { Text(m.full_name) },
                                        onClick = { escalatedTo = m.full_name; escalateExpanded = false }
                                    )
                                }
                            }
                        }
                        Text(
                            if (isEscalating)
                                "Handing over to $escalatedTo — you can only Escalate."
                            else
                                "Nobody selected — you can only Finish the job.",
                            fontSize = 11.sp,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                            modifier = Modifier.padding(top = 8.dp)
                        )
                    }

                  })

                    Spacer(modifier = Modifier.height(8.dp))
                }

                // Validation / API errors above sticky CTAs (never covered by the dock).
                errorText?.let { msg ->
                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 20.dp),
                        shape = RoundedCornerShape(12.dp),
                        color = WccError.copy(alpha = 0.12f),
                        border = BorderStroke(1.dp, WccError.copy(alpha = 0.4f))
                    ) {
                        Row(
                            modifier = Modifier.padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                Icons.Default.Warning,
                                contentDescription = null,
                                tint = WccError,
                                modifier = Modifier.size(18.dp)
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Text(msg, color = WccError, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                        }
                    }
                }

                // ── STICKY ACTIONS (never scroll away) ──
                com.example.wcc_companion_app.ui.components.WccStickyActionBar {
                    com.example.wcc_companion_app.ui.components.WccPrimaryButton(
                        label = if (isSubmitting && isEscalating) "Escalating…" else "Escalate",
                        onClick = { submit("escalate") },
                        enabled = isEscalating && !isSubmitting,
                        loading = isSubmitting && isEscalating,
                        containerColor = TicketStatusEscalated,
                        height = if (isLandscape) 52.dp else 60.dp,
                        modifier = Modifier.weight(1f)
                    )
                    com.example.wcc_companion_app.ui.components.WccPrimaryButton(
                        label = if (isSubmitting && !isEscalating) "Saving…" else "Finish Job",
                        onClick = { submit("finish") },
                        enabled = !isEscalating && !isSubmitting,
                        loading = isSubmitting && !isEscalating,
                        containerColor = WccSuccess,
                        height = if (isLandscape) 52.dp else 60.dp,
                        modifier = Modifier.weight(1f)
                    )
                }
            }
        }
    }

    // ── TIME PICKER ──
    picking?.let { which ->
        val current = if (which == "start") startAt else endAt
        val state = rememberTimePickerState(
            initialHour = current.hour,
            initialMinute = current.minute,
            is24Hour = true
        )
        AlertDialog(
            onDismissRequest = { picking = null },
            title = { Text(if (which == "start") "Start time" else "End time") },
            text = { TimePicker(state = state) },
            confirmButton = {
                TextButton(onClick = {
                    val updated = current.withHour(state.hour).withMinute(state.minute)
                    if (which == "start") startAt = updated else endAt = updated
                    picking = null
                }) { Text("Set") }
            },
            dismissButton = { TextButton(onClick = { picking = null }) { Text("Cancel") } }
        )
    }
}

/** Ticket context — mirrors the web's ticket-info block (equipment + the issue). */
@Composable
private fun ContextCard(ticket: TicketDto) {
    val priorityColor = when (ticket.priority?.lowercase()) {
        "critical" -> StatusCriticalityCritical
        "high" -> StatusCriticalityHigh
        "low" -> StatusCriticalityLow
        else -> StatusCriticalityNormal
    }
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        color = priorityColor.copy(alpha = 0.08f),
        border = BorderStroke(1.dp, priorityColor.copy(alpha = 0.35f))
    ) {
        Column(modifier = Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "EQUIPMENT #${ticket.equip_id}",
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Black,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                    modifier = Modifier.weight(1f)
                )
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = priorityColor.copy(alpha = 0.18f)
                ) {
                    Text(
                        (ticket.priority ?: "NORMAL").uppercase(),
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Black,
                        color = priorityColor
                    )
                }
            }
            Spacer(modifier = Modifier.height(6.dp))
            Text(
                ticket.fault_desc ?: "No description",
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )
            val meta = listOfNotNull(
                ticket.announced_by?.let { "Reported by $it" },
                ticket.pic?.let { "PIC $it" },
                ticket.report_date
            )
            if (meta.isNotEmpty()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    meta.joinToString(" · "),
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                )
            }
        }
    }
}

@Composable
private fun SectionCard(
    title: String,
    accent: Color,
    content: @Composable ColumnScope.() -> Unit
) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.5f),
        border = BorderStroke(1.dp, accent.copy(alpha = 0.25f))
    ) {
        Column(modifier = Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(modifier = Modifier.size(6.dp).background(accent, CircleShape))
                Spacer(modifier = Modifier.width(8.dp))
                Text(
                    title,
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Black,
                    color = accent
                )
            }
            Spacer(modifier = Modifier.height(12.dp))
            content()
        }
    }
}

/**
 * Two panes side-by-side in landscape, one stacked column in portrait.
 *
 * A phone in landscape has ~1080px of height for a 95%-height dialog, which fits barely
 * one section — splitting across the abundant width keeps the whole form reachable.
 */
@Composable
private fun AdaptiveTwoPane(
    isLandscape: Boolean,
    left: @Composable ColumnScope.() -> Unit,
    right: @Composable ColumnScope.() -> Unit
) {
    if (isLandscape) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) { left() }
            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) { right() }
        }
    } else {
        Column(verticalArrangement = Arrangement.spacedBy(14.dp)) {
            left()
            right()
        }
    }
}

/** Side-by-side in landscape (like the web's grid-2), stacked in portrait. */
@Composable
private fun FormRow(isLandscape: Boolean, content: @Composable RowScope.() -> Unit) {
    if (isLandscape) {
        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) { content() }
    } else {
        Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) { content() }
        }
    }
}

@Composable
private fun TimeField(
    label: String,
    value: LocalDateTime,
    modifier: Modifier = Modifier,
    onClick: () -> Unit
) {
    Surface(
        modifier = modifier.clickable(
            indication = null,
            interactionSource = remember { MutableInteractionSource() },
            onClick = onClick
        ),
        shape = RoundedCornerShape(12.dp),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.7f),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
    ) {
        Column(modifier = Modifier.padding(horizontal = 12.dp, vertical = 10.dp)) {
            Text(
                label,
                fontSize = 10.sp,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
            )
            Spacer(modifier = Modifier.height(2.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Default.Schedule,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(15.dp)
                )
                Spacer(modifier = Modifier.width(6.dp))
                Text(
                    value.format(TIME_FMT),
                    fontSize = 19.sp,
                    fontWeight = FontWeight.Black,
                    maxLines = 1,
                    color = MaterialTheme.colorScheme.onSurface
                )
            }
            // Own line so the month is never clipped by the narrow field.
            Text(
                value.format(DATE_FMT),
                fontSize = 11.sp,
                maxLines = 1,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
            )
        }
    }
}

/** The wrench time that was previously always zero. */
@Composable
private fun DurationBanner(minutes: Long) {
    val invalid = minutes < 0
    val color = if (invalid) WccError else WccPrimary
    val label = when {
        invalid -> "End is before start"
        minutes < 60 -> "$minutes min"
        else -> "${minutes / 60}h ${minutes % 60}m"
    }
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        color = color.copy(alpha = 0.12f),
        border = BorderStroke(1.dp, color.copy(alpha = 0.4f))
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                "WRENCH TIME",
                fontSize = 10.sp,
                fontWeight = FontWeight.Black,
                color = color.copy(alpha = 0.8f),
                modifier = Modifier.weight(1f)
            )
            Text(label, fontSize = 20.sp, fontWeight = FontWeight.Black, color = color)
        }
    }
}

@Composable
private fun QuickChip(text: String, onClick: () -> Unit) {
    Surface(
        modifier = Modifier.clickable(
            indication = null,
            interactionSource = remember { MutableInteractionSource() },
            onClick = onClick
        ),
        shape = RoundedCornerShape(50),
        color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.35f))
    ) {
        Text(
            text,
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 7.dp),
            fontSize = 12.sp,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.primary
        )
    }
}

/** Large +/- targets — usable with gloves. */
@Composable
private fun QtyStepper(qty: Int, onChange: (Int) -> Unit) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        StepButton(Icons.Default.Remove, "Decrease") { onChange(qty - 1) }
        Text(
            qty.toString(),
            modifier = Modifier.widthIn(min = 36.dp).padding(horizontal = 6.dp),
            fontSize = 17.sp,
            fontWeight = FontWeight.Black,
            color = MaterialTheme.colorScheme.onSurface,
            textAlign = androidx.compose.ui.text.style.TextAlign.Center
        )
        StepButton(Icons.Default.Add, "Increase") { onChange(qty + 1) }
    }
}

@Composable
private fun StepButton(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    description: String,
    onClick: () -> Unit
) {
    Box(
        modifier = Modifier
            .size(40.dp)
            .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.14f), CircleShape)
            .border(
                BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.35f)),
                CircleShape
            )
            .clickable(
                indication = null,
                interactionSource = remember { MutableInteractionSource() },
                onClick = onClick
            ),
        contentAlignment = Alignment.Center
    ) {
        Icon(
            icon,
            contentDescription = description,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(20.dp)
        )
    }
}
