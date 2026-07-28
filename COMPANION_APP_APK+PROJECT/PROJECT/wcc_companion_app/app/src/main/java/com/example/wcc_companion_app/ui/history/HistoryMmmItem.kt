package com.example.wcc_companion_app.ui.history

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.EventNote
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Info
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.ui.components.OrbiterAction
import com.example.wcc_companion_app.ui.components.OrbiterMenu
import com.example.wcc_companion_app.ui.rails.HistoryRailStyle

private val Teal = Color(0xFF1DE9B6)
private val Orange = Color(0xFFFF9100)

@Composable
fun HistoryMmmItem(
    item: HistoryRailItem,
    isFocused: Boolean,
    onFilter: (HistoryFilter) -> Unit,
    onOpenEvent: (TicketDto) -> Unit,
    onOpenWo: (WorkOrderDto) -> Unit
) {
    when (item) {
        is HistoryRailItem.FilterCard -> FilterCard(item.mode, isFocused, onFilter)
        is HistoryRailItem.Event -> EventCard(item.ticket, isFocused, onOpenEvent)
        is HistoryRailItem.WorkOrder -> WoCard(item.wo, isFocused, onOpenWo)
    }
}

@Composable
private fun FilterCard(
    mode: HistoryFilter,
    isFocused: Boolean,
    onFilter: (HistoryFilter) -> Unit
) {
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE

    if (isLandscape) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .heightIn(max = 120.dp)
                .padding(vertical = 6.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Surface(
                modifier = Modifier.weight(1f),
                shape = RoundedCornerShape(24.dp),
                color = MaterialTheme.colorScheme.surface.copy(alpha = 0.45f),
                border = BorderStroke(1.5.dp, Teal.copy(alpha = 0.4f))
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    HistoryIconBadge(Icons.Default.History, Teal, compact = true)
                    Column(modifier = Modifier.weight(1f)) {
                        Text("History filter", fontWeight = FontWeight.Black, fontSize = 16.sp, maxLines = 1)
                        Text(
                            "Latest closed records",
                            fontSize = 12.sp,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f)
                        )
                    }
                    FilterButton(
                        label = "Events",
                        selected = mode == HistoryFilter.EVENTS,
                        color = Teal,
                        onClick = { onFilter(HistoryFilter.EVENTS) },
                        compact = true
                    )
                    FilterButton(
                        label = "WOs",
                        selected = mode == HistoryFilter.WORK_ORDERS,
                        color = Orange,
                        onClick = { onFilter(HistoryFilter.WORK_ORDERS) },
                        compact = true
                    )
                }
            }
        }
        return
    }

    // Portrait: top clearance under category icons; card fills remaining (no orbiter)
    Column(
        modifier = Modifier
            .width(HistoryRailStyle.cardWidth)
            .fillMaxHeight()
            .padding(bottom = 8.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Spacer(modifier = Modifier.height(HistoryRailStyle.cardTopClearance))
        Surface(
            modifier = Modifier
                .weight(1f, fill = true)
                .fillMaxWidth(),
            shape = RoundedCornerShape(HistoryRailStyle.cardRadius),
            color = MaterialTheme.colorScheme.surface.copy(alpha = 0.45f),
            border = BorderStroke(HistoryRailStyle.cardBorder, Teal.copy(alpha = 0.45f))
        ) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(HistoryRailStyle.cardPad),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Icon(Icons.Default.History, null, Modifier.size(HistoryRailStyle.heroIcon), tint = Teal)
                Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
                Text(
                    "History filter",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Black,
                    textAlign = TextAlign.Center
                )
                Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
                Text(
                    "Latest closed records — pick Events or Work Orders",
                    fontSize = 13.sp,
                    textAlign = TextAlign.Center,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f)
                )
                Spacer(modifier = Modifier.weight(1f))
                // Filter buttons — baseline borders (global standard)
                FilterButton(
                    label = "Events",
                    selected = mode == HistoryFilter.EVENTS,
                    color = Teal,
                    onClick = { onFilter(HistoryFilter.EVENTS) }
                )
                Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
                FilterButton(
                    label = "Work Orders",
                    selected = mode == HistoryFilter.WORK_ORDERS,
                    color = Orange,
                    onClick = { onFilter(HistoryFilter.WORK_ORDERS) }
                )
            }
        }
    }
}

@Composable
private fun FilterButton(
    label: String,
    selected: Boolean,
    color: Color,
    onClick: () -> Unit,
    compact: Boolean = false
) {
    Button(
        onClick = onClick,
        modifier = if (compact) {
            Modifier.height(36.dp)
        } else {
            Modifier.fillMaxWidth().height(HistoryRailStyle.filterBtnH)
        },
        shape = RoundedCornerShape(HistoryRailStyle.chipRadius),
        contentPadding = if (compact) {
            PaddingValues(horizontal = 10.dp, vertical = 4.dp)
        } else {
            PaddingValues(horizontal = 14.dp, vertical = 8.dp)
        },
        colors = ButtonDefaults.buttonColors(
            containerColor = if (selected) color else color.copy(alpha = 0.18f),
            contentColor = if (selected) Color.Black else color
        ),
        border = BorderStroke(2.dp, color.copy(alpha = 0.55f))
    ) {
        Text(
            label,
            fontWeight = FontWeight.Black,
            fontSize = if (compact) 11.sp else HistoryRailStyle.filterBtnFont,
            maxLines = 1
        )
    }
}

@Composable
private fun EventCard(
    ticket: TicketDto,
    isFocused: Boolean,
    onOpen: (TicketDto) -> Unit
) {
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE
    val actions = listOf(
        OrbiterAction(Icons.Default.Info, "Open", { onOpen(ticket) }, Teal)
    )

    if (isLandscape) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .heightIn(max = 148.dp)
                .wrapContentHeight(),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.Center
        ) {
            Box(modifier = Modifier.weight(1f).padding(vertical = 6.dp)) {
                EventCardBody(ticket, Modifier.fillMaxWidth(), compact = true)
            }
            Spacer(modifier = Modifier.width(16.dp))
            OrbiterMenu(visible = isFocused, actions = actions)
        }
    } else {
        // Top clearance under category icons; orbiter stays at bottom
        Column(
            modifier = Modifier
                .width(HistoryRailStyle.cardWidth)
                .fillMaxHeight()
                .padding(bottom = 8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(modifier = Modifier.height(HistoryRailStyle.cardTopClearance))
            EventCardBody(
                ticket,
                Modifier
                    .weight(1f, fill = true)
                    .fillMaxWidth(),
                compact = false,
                chipsOutside = false
            )
            Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
            OrbiterMenu(visible = isFocused, actions = actions)
        }
    }
}

@Composable
private fun EventCardBody(
    ticket: TicketDto,
    modifier: Modifier = Modifier,
    compact: Boolean,
    chipsOutside: Boolean = false
) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(
            if (compact) 24.dp else HistoryRailStyle.cardRadius
        ),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
        border = BorderStroke(
            if (compact) 1.5.dp else HistoryRailStyle.cardBorder,
            Teal.copy(alpha = 0.4f)
        )
    ) {
        if (compact) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 18.dp, vertical = 14.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                HistoryIconBadge(Icons.Default.EventNote, Teal, compact = true)
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        ticket.ticket_id,
                        fontWeight = FontWeight.Black,
                        fontSize = 16.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Text(
                        ticket.fault_desc ?: "No description",
                        fontSize = 13.sp,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f)
                    )
                    Text(
                        listOfNotNull(ticket.report_date, ticket.pic).joinToString(" · "),
                        fontSize = 11.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                    )
                }
                StatusChip("CLOSED", Teal)
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(HistoryRailStyle.cardPad),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Icon(Icons.Default.EventNote, null, Modifier.size(HistoryRailStyle.heroIcon), tint = Teal)
                Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
                Text(
                    ticket.ticket_id,
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Black,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
                Text(
                    ticket.fault_desc ?: "No description",
                    textAlign = TextAlign.Center,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f)
                )
                Spacer(modifier = Modifier.weight(1f))
                if (!chipsOutside) {
                    StatusChip("CLOSED", Teal, expand = true)
                    Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
                }
                Text(
                    listOfNotNull(ticket.report_date, ticket.pic).joinToString(" · "),
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
    }
}

@Composable
private fun WoCard(
    wo: WorkOrderDto,
    isFocused: Boolean,
    onOpen: (WorkOrderDto) -> Unit
) {
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE
    val actions = listOf(
        OrbiterAction(Icons.Default.Info, "Open", { onOpen(wo) }, Orange)
    )

    if (isLandscape) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .heightIn(max = 148.dp)
                .wrapContentHeight(),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.Center
        ) {
            Box(modifier = Modifier.weight(1f).padding(vertical = 6.dp)) {
                WoCardBody(wo, Modifier.fillMaxWidth(), compact = true)
            }
            Spacer(modifier = Modifier.width(16.dp))
            OrbiterMenu(visible = isFocused, actions = actions)
        }
    } else {
        Column(
            modifier = Modifier
                .width(HistoryRailStyle.cardWidth)
                .fillMaxHeight()
                .padding(bottom = 8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(modifier = Modifier.height(HistoryRailStyle.cardTopClearance))
            WoCardBody(
                wo,
                Modifier
                    .weight(1f, fill = true)
                    .fillMaxWidth(),
                compact = false,
                chipsOutside = false
            )
            Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
            OrbiterMenu(visible = isFocused, actions = actions)
        }
    }
}

@Composable
private fun WoCardBody(
    wo: WorkOrderDto,
    modifier: Modifier = Modifier,
    compact: Boolean,
    chipsOutside: Boolean = false
) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(
            if (compact) 24.dp else HistoryRailStyle.cardRadius
        ),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
        border = BorderStroke(
            if (compact) 1.5.dp else HistoryRailStyle.cardBorder,
            Orange.copy(alpha = 0.45f)
        )
    ) {
        if (compact) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 18.dp, vertical = 14.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                HistoryIconBadge(Icons.Default.Build, Orange, compact = true)
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        "WO-${wo.wo_id}",
                        fontWeight = FontWeight.Black,
                        fontSize = 16.sp,
                        maxLines = 1
                    )
                    Text(
                        wo.title,
                        fontSize = 13.sp,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                    Text(
                        listOfNotNull(
                            wo.equip_name ?: wo.equipment_id?.let { "Equip #$it" },
                            wo.completed_date ?: wo.scheduled_date
                        ).joinToString(" · "),
                        fontSize = 11.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                    )
                }
                StatusChip("DONE", Orange)
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(HistoryRailStyle.cardPad),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Icon(Icons.Default.Build, null, Modifier.size(HistoryRailStyle.heroIcon), tint = Orange)
                Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
                Text(
                    "WO-${wo.wo_id}",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Black,
                    maxLines = 1
                )
                Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
                Text(
                    wo.title,
                    textAlign = TextAlign.Center,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    wo.equip_name ?: "Equipment #${wo.equipment_id ?: "?"}",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                Spacer(modifier = Modifier.weight(1f))
                if (!chipsOutside) {
                    StatusChip("COMPLETED", Orange, expand = true)
                    Spacer(modifier = Modifier.height(HistoryRailStyle.chipGap))
                }
                Text(
                    wo.completed_date ?: wo.scheduled_date ?: "",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                    maxLines = 1
                )
            }
        }
    }
}

@Composable
private fun HistoryIconBadge(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    color: Color,
    compact: Boolean
) {
    val size = if (compact) 52.dp else 64.dp
    val iconSize = if (compact) 26.dp else 32.dp
    Box(
        modifier = Modifier
            .size(size)
            .clip(CircleShape)
            .background(
                Brush.radialGradient(
                    listOf(color.copy(alpha = 0.32f), color.copy(alpha = 0.04f))
                )
            )
            .border(BorderStroke(1.dp, color.copy(alpha = 0.35f)), CircleShape),
        contentAlignment = Alignment.Center
    ) {
        Icon(icon, null, Modifier.size(iconSize), tint = color)
    }
}

@Composable
private fun StatusChip(label: String, color: Color, expand: Boolean = false) {
    Surface(
        modifier = if (expand) Modifier.fillMaxWidth() else Modifier,
        color = color.copy(alpha = 0.15f),
        shape = RoundedCornerShape(HistoryRailStyle.chipRadius),
        border = BorderStroke(HistoryRailStyle.chipBorder, color.copy(alpha = 0.45f))
    ) {
        Text(
            label,
            modifier = Modifier
                .then(if (expand) Modifier.fillMaxWidth() else Modifier)
                .padding(
                    horizontal = HistoryRailStyle.chipPadH,
                    vertical = HistoryRailStyle.chipPadV
                ),
            fontWeight = FontWeight.Black,
            color = color,
            fontSize = HistoryRailStyle.chipFont,
            textAlign = if (expand) TextAlign.Center else TextAlign.Start,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
    }
}
