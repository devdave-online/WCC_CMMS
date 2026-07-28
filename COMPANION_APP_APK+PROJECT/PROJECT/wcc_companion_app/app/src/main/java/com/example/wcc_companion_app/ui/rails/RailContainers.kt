package com.example.wcc_companion_app.ui.rails

import androidx.compose.runtime.Composable
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.TextUnit
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.data.remote.models.EquipmentDto
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.ToolingDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.ui.equipment.EquipmentMmmItem
import com.example.wcc_companion_app.ui.history.HistoryFilter
import com.example.wcc_companion_app.ui.history.HistoryMmmItem
import com.example.wcc_companion_app.ui.history.HistoryRailItem
import com.example.wcc_companion_app.ui.inventory.InventoryMmmItem
import com.example.wcc_companion_app.ui.tickets.TicketMmmItem
import com.example.wcc_companion_app.ui.toolings.ToolingMmmItem
import com.example.wcc_companion_app.ui.toolings.ToolingPlaceholder
import com.example.wcc_companion_app.ui.workorders.WorkOrderMmmItem

/**
 * Six independent rail containers + per-rail chip/layout tokens.
 * Tune each segment here without cross-talk.
 */

/**
 * Tickets — glass CARD is the “main chip” (outer border / size).
 * Inner status/priority pills stay global baseline — do not fatten their borders.
 * See ai_ctxt/STYLING_DESIGN_STANDARDS.md §3.
 */
object TicketRailStyle {
    // Inner pills (status / priority) — global baseline
    val chipPadH: Dp = 16.dp
    val chipPadV: Dp = 9.dp
    val chipRadius: Dp = 12.dp
    val chipFont: TextUnit = 13.sp
    val chipIcon: Dp = 15.dp
    val chipBorder: Dp = 2.dp
    /** Internal card spacing + gap card → orbiter (10–15 px band). */
    val chipGap: Dp = 12.dp
    /** Portrait: glass card fills band under icons; orbiter docks below card. */
    val stretchCard: Boolean = true
    val heroIcon: Dp = 72.dp
    val heroGlyph: Dp = 34.dp
    // Main glass card — a bit bigger + slightly thicker outer stroke
    val cardPad: Dp = 22.dp
    val cardRadius: Dp = 30.dp
    val cardBorder: Dp = 2.5.dp
    val cardWidth: Dp = 336.dp
    /**
     * Top clearance so glass starts UNDER shrinked category icons only.
     * Does not move the orbiter (orbiter stays docked at band bottom).
     */
    val cardTopClearance: Dp = 44.dp
}

/** Work Orders — chips INSIDE card; 12 dp to orbiter. */
object WorkOrderRailStyle {
    val chipPadH: Dp = 14.dp
    val chipPadV: Dp = 8.dp
    val chipRadius: Dp = 12.dp
    val chipFont: TextUnit = 12.sp
    val chipIcon: Dp = 14.dp
    val chipBorder: Dp = 2.dp
    val chipGap: Dp = 12.dp
    val stretchCard: Boolean = true
    val heroIcon: Dp = 68.dp
    val heroGlyph: Dp = 32.dp
    val cardPad: Dp = 22.dp
    val cardRadius: Dp = 30.dp
    val cardBorder: Dp = 2.5.dp
    val cardWidth: Dp = 336.dp
    val cardTopClearance: Dp = 44.dp
}

/** Equipment — CRIT / category chips. */
object EquipmentRailStyle {
    val chipPadH: Dp = 14.dp
    val chipPadV: Dp = 8.dp
    val chipRadius: Dp = 12.dp
    val chipFont: TextUnit = 12.sp
    val stretchCard: Boolean = true
    val heroIcon: Dp = 92.dp
    val heroGlyph: Dp = 44.dp
    val cardPad: Dp = 22.dp
}

/** Toolings — status / category chips. */
object ToolingRailStyle {
    val chipPadH: Dp = 14.dp
    val chipPadV: Dp = 8.dp
    val chipRadius: Dp = 12.dp
    val chipFont: TextUnit = 12.sp
    val stretchCard: Boolean = true
    val heroIcon: Dp = 84.dp
    val heroGlyph: Dp = 40.dp
    val cardPad: Dp = 22.dp
}

/** Inventory — stock health chip. */
object InventoryRailStyle {
    val chipPadH: Dp = 16.dp
    val chipPadV: Dp = 9.dp
    val chipRadius: Dp = 12.dp
    val chipFont: TextUnit = 13.sp
    val stretchCard: Boolean = true
    val heroIcon: Dp = 88.dp
    val heroGlyph: Dp = 42.dp
    val cardPad: Dp = 22.dp
}

/** History — chips/filters INSIDE card; 12 dp to orbiter when present. */
object HistoryRailStyle {
    // Inner pills / filter buttons — global baseline (not fat borders)
    val chipPadH: Dp = 14.dp
    val chipPadV: Dp = 8.dp
    val chipRadius: Dp = 12.dp
    val chipFont: TextUnit = 12.sp
    val chipBorder: Dp = 2.dp
    val chipGap: Dp = 12.dp
    val filterBtnH: Dp = 48.dp
    val filterBtnFont: TextUnit = 14.sp
    val stretchCard: Boolean = true
    val heroIcon: Dp = 56.dp
    // Main glass card
    val cardPad: Dp = 22.dp
    val cardRadius: Dp = 30.dp
    val cardBorder: Dp = 2.5.dp
    val cardWidth: Dp = 336.dp
    /** Clear shrinked category icons; orbiter stays put at bottom. */
    val cardTopClearance: Dp = 44.dp
}

// ── Containers ──────────────────────────────────────────────────────────────

@Composable
fun TicketRailContainer(
    ticket: TicketDto,
    isFocused: Boolean,
    onTakeover: (TicketDto) -> Unit,
    onCloseout: (TicketDto) -> Unit,
    onHold: (TicketDto) -> Unit
) {
    TicketMmmItem(
        ticket = ticket,
        isFocused = isFocused,
        onTakeover = onTakeover,
        onCloseout = onCloseout,
        onHold = onHold
    )
}

@Composable
fun WorkOrderRailContainer(
    wo: WorkOrderDto,
    isFocused: Boolean,
    onTakeover: (WorkOrderDto) -> Unit
) {
    WorkOrderMmmItem(wo = wo, isFocused = isFocused, onTakeover = onTakeover)
}

@Composable
fun EquipmentRailContainer(
    equipment: EquipmentDto,
    isFocused: Boolean,
    onOpen: (EquipmentDto) -> Unit,
    onScan: () -> Unit
) {
    EquipmentMmmItem(
        equipment = equipment,
        isFocused = isFocused,
        onOpen = onOpen,
        onScan = onScan
    )
}

@Composable
fun ToolingRailContainer(
    tooling: ToolingDto?,
    isFocused: Boolean,
    onOpen: (ToolingDto) -> Unit = {}
) {
    ToolingMmmItem(isFocused = isFocused, tooling = tooling, onOpen = onOpen)
}

@Composable
fun InventoryRailContainer(
    part: InventoryPartDto,
    isFocused: Boolean,
    onOpen: (InventoryPartDto) -> Unit
) {
    InventoryMmmItem(part = part, isFocused = isFocused, onOpen = onOpen)
}

@Composable
fun HistoryRailContainer(
    item: HistoryRailItem,
    isFocused: Boolean,
    onFilter: (HistoryFilter) -> Unit,
    onOpenEvent: (TicketDto) -> Unit,
    onOpenWo: (WorkOrderDto) -> Unit
) {
    HistoryMmmItem(
        item = item,
        isFocused = isFocused,
        onFilter = onFilter,
        onOpenEvent = onOpenEvent,
        onOpenWo = onOpenWo
    )
}

typealias ToolingRailPlaceholder = ToolingPlaceholder
