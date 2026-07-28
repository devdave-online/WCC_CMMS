package com.example.wcc_companion_app.ui.tickets

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Assignment
import androidx.compose.material.icons.filled.PriorityHigh
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalConfiguration
import android.content.res.Configuration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.ui.components.OrbiterAction
import com.example.wcc_companion_app.ui.components.OrbiterMenu
import com.example.wcc_companion_app.ui.rails.TicketRailStyle
import com.example.wcc_companion_app.ui.theme.*

@Composable
fun TicketMmmItem(
    ticket: TicketDto,
    isFocused: Boolean,
    onTakeover: (TicketDto) -> Unit,
    onCloseout: (TicketDto) -> Unit,
    onHold: (TicketDto) -> Unit
) {
    val status = ticket.status?.uppercase() ?: "OPEN"
    val isPending = status == "PENDING"
    val isHold = status == "HOLD"
    val isClosed = status == "CLOSED"

    val statusColor = when (status) {
        "ESCALATED" -> TicketStatusEscalated
        "OPEN" -> TicketStatusOpen
        "CLOSED" -> TicketStatusClosed
        "PENDING" -> TicketStatusPending
        "HOLD" -> TicketStatusHold
        else -> TicketStatusOpen
    }

    val priorityColor = when (ticket.priority?.lowercase()) {
        "critical" -> StatusCriticalityCritical
        "high" -> StatusCriticalityHigh
        "normal" -> StatusCriticalityNormal
        "low" -> StatusCriticalityLow
        else -> StatusCriticalityNormal
    }
    // Web parity (_maint/active_tickets.php Action column):
    //   OPEN / ESCALATED → Takeover
    //   PENDING          → Review/Close + Put on Hold
    //   HOLD             → Resume Job (= same takeover form)
    val actions = mutableListOf<OrbiterAction>()
    if (!isClosed) {
        when {
            isHold -> {
                actions.add(
                    OrbiterAction(
                        icon = Icons.Default.PlayArrow,
                        label = "Resume",
                        onClick = { onTakeover(ticket) },
                        color = TicketStatusHold
                    )
                )
            }
            isPending -> {
                actions.add(
                    OrbiterAction(
                        icon = Icons.Default.CheckCircle,
                        label = "Close",
                        onClick = { onCloseout(ticket) },
                        color = TicketStatusClosed
                    )
                )
                actions.add(
                    OrbiterAction(
                        icon = Icons.Default.Lock,
                        label = "Hold",
                        onClick = { onHold(ticket) },
                        color = TicketStatusHold
                    )
                )
            }
            else -> {
                // OPEN, ESCALATED, and any other live status
                actions.add(
                    OrbiterAction(
                        icon = Icons.Default.PlayArrow,
                        label = "Takeover",
                        onClick = { onTakeover(ticket) },
                        color = TicketStatusOpen
                    )
                )
            }
        }
    }

    val configuration = LocalConfiguration.current
    val isLandscape = configuration.orientation == Configuration.ORIENTATION_LANDSCAPE

    if (isLandscape) {
        Row(
            modifier = Modifier
                .fillMaxWidth(0.95f)
                .windowInsetsPadding(WindowInsets.navigationBars)
                .wrapContentHeight(),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.Center
        ) {
            Box(modifier = Modifier.weight(1f).padding(vertical = 8.dp)) {
                TicketCardContent(
                    ticket = ticket,
                    statusColor = statusColor,
                    priorityColor = priorityColor,
                    modifier = Modifier.fillMaxWidth()
                )
            }
            Spacer(modifier = Modifier.width(20.dp))
            OrbiterMenu(
                visible = isFocused && actions.isNotEmpty(),
                actions = actions
            )
        }
    } else {
        // Portrait stack (nav insets owned by MmmLayout band):
        // top clearance → glass under category icons → 12dp → orbiter (bottom fixed)
        Column(
            modifier = Modifier
                .width(TicketRailStyle.cardWidth)
                .fillMaxHeight()
                .padding(bottom = 8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(modifier = Modifier.height(TicketRailStyle.cardTopClearance))
            TicketCardPortrait(
                ticket = ticket,
                statusColor = statusColor,
                priorityColor = priorityColor,
                modifier = Modifier
                    .weight(1f, fill = true)
                    .fillMaxWidth()
            )
            Spacer(modifier = Modifier.height(TicketRailStyle.chipGap))
            OrbiterMenu(
                visible = isFocused && actions.isNotEmpty(),
                actions = actions
            )
        }
    }
}

/**
 * Portrait main glass card — fills band under category icons.
 * Header content top-anchored; status/priority pills stay baseline at card bottom.
 */
@Composable
private fun TicketCardPortrait(
    ticket: TicketDto,
    statusColor: Color,
    priorityColor: Color,
    modifier: Modifier = Modifier
) {
    val statusLabel = (ticket.status ?: "OPEN").uppercase()
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(TicketRailStyle.cardRadius),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
        border = BorderStroke(TicketRailStyle.cardBorder, statusColor.copy(alpha = 0.4f))
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(TicketRailStyle.cardPad),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Box(
                modifier = Modifier
                    .size(TicketRailStyle.heroIcon)
                    .clip(CircleShape)
                    .background(
                        Brush.radialGradient(
                            listOf(statusColor.copy(alpha = 0.32f), statusColor.copy(alpha = 0.04f))
                        )
                    )
                    .border(BorderStroke(1.dp, statusColor.copy(alpha = 0.35f)), CircleShape),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    Icons.Default.Assignment,
                    contentDescription = null,
                    modifier = Modifier.size(TicketRailStyle.heroGlyph),
                    tint = statusColor
                )
            }
            Spacer(modifier = Modifier.height(TicketRailStyle.chipGap))
            Text(
                ticket.ticket_id,
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Black,
                textAlign = TextAlign.Center,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            Spacer(modifier = Modifier.height(TicketRailStyle.chipGap))
            Text(
                ticket.fault_desc ?: "No active workshop event description",
                style = MaterialTheme.typography.bodyMedium,
                textAlign = TextAlign.Center,
                maxLines = 3,
                overflow = TextOverflow.Ellipsis,
                lineHeight = 20.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f)
            )
            // Push pills to bottom of glass — do not fatten pill borders
            Spacer(modifier = Modifier.weight(1f))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(TicketRailStyle.chipGap),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Surface(
                    modifier = Modifier.weight(1f),
                    color = statusColor.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(TicketRailStyle.chipRadius),
                    border = BorderStroke(TicketRailStyle.chipBorder, statusColor.copy(alpha = 0.4f))
                ) {
                    Text(
                        statusLabel,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(
                                horizontal = TicketRailStyle.chipPadH,
                                vertical = TicketRailStyle.chipPadV
                            ),
                        fontSize = TicketRailStyle.chipFont,
                        fontWeight = FontWeight.Black,
                        color = statusColor,
                        textAlign = TextAlign.Center,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }
                Surface(
                    modifier = Modifier.weight(1f),
                    color = priorityColor.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(TicketRailStyle.chipRadius),
                    border = BorderStroke(TicketRailStyle.chipBorder, priorityColor.copy(alpha = 0.4f))
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(
                                horizontal = TicketRailStyle.chipPadH,
                                vertical = TicketRailStyle.chipPadV
                            ),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center
                    ) {
                        if (priorityColor == StatusCriticalityHigh ||
                            priorityColor == StatusCriticalityCritical
                        ) {
                            val tint =
                                if (priorityColor == StatusCriticalityCritical) StatusCriticalityCritical
                                else StatusCriticalityHigh
                            Icon(
                                Icons.Default.PriorityHigh,
                                contentDescription = null,
                                modifier = Modifier.size(TicketRailStyle.chipIcon),
                                tint = tint
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                        }
                        Text(
                            (ticket.priority ?: "NORMAL").uppercase(),
                            fontSize = TicketRailStyle.chipFont,
                            fontWeight = FontWeight.Black,
                            color = priorityColor,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun TicketCardContent(ticket: TicketDto, statusColor: Color, priorityColor: Color, modifier: Modifier = Modifier) {
    val configuration = LocalConfiguration.current
    val isLandscape = configuration.orientation == Configuration.ORIENTATION_LANDSCAPE

    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(if (isLandscape) 24.dp else 32.dp),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
        border = BorderStroke(1.5.dp, statusColor.copy(alpha = 0.35f)),
        shadowElevation = 0.dp
    ) {
        if (isLandscape) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 12.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Icon
                Box(
                    modifier = Modifier
                        .size(64.dp)
                        .clip(CircleShape)
                        .background(
                            Brush.radialGradient(
                                listOf(statusColor.copy(alpha = 0.30f), statusColor.copy(alpha = 0.04f))
                            )
                        )
                        .border(BorderStroke(1.dp, statusColor.copy(alpha = 0.35f)), CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.Assignment,
                        contentDescription = null,
                        modifier = Modifier.size(32.dp),
                        tint = statusColor
                    )
                }

                // Text Details
                Column(
                    modifier = Modifier.weight(1f),
                    verticalArrangement = Arrangement.Center
                ) {
                    Text(
                        text = ticket.ticket_id,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Black,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = ticket.fault_desc ?: "No active workshop event description",
                        style = MaterialTheme.typography.bodyMedium,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f)
                    )
                }

                // Compact priority chip (TicketRailStyle)
                Surface(
                    color = priorityColor.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(TicketRailStyle.chipRadius),
                    border = BorderStroke(1.dp, priorityColor.copy(alpha = 0.3f))
                ) {
                    Row(
                        modifier = Modifier.padding(
                            horizontal = TicketRailStyle.chipPadH,
                            vertical = TicketRailStyle.chipPadV
                        ),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        if (priorityColor == StatusCriticalityHigh || priorityColor == StatusCriticalityCritical) {
                            val tint = if (priorityColor == StatusCriticalityCritical) StatusCriticalityCritical else StatusCriticalityHigh
                            Icon(
                                Icons.Default.PriorityHigh,
                                contentDescription = null,
                                modifier = Modifier.size(TicketRailStyle.chipIcon),
                                tint = tint
                            )
                            Spacer(modifier = Modifier.width(3.dp))
                        }
                        Text(
                            text = (ticket.priority ?: "NORMAL").uppercase(),
                            fontSize = TicketRailStyle.chipFont,
                            fontWeight = FontWeight.Black,
                            color = priorityColor,
                            maxLines = 1
                        )
                    }
                }
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(TicketRailStyle.cardPad),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(16.dp, Alignment.CenterVertically)
            ) {
                Box(
                    modifier = Modifier
                        .size(TicketRailStyle.heroIcon)
                        .clip(CircleShape)
                        .background(
                            Brush.radialGradient(
                                listOf(statusColor.copy(alpha = 0.32f), statusColor.copy(alpha = 0.04f))
                            )
                        )
                        .border(BorderStroke(1.dp, statusColor.copy(alpha = 0.35f)), CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.Assignment,
                        contentDescription = null,
                        modifier = Modifier.size(TicketRailStyle.heroGlyph),
                        tint = statusColor
                    )
                }

                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(
                        text = ticket.ticket_id,
                        style = MaterialTheme.typography.headlineSmall,
                        fontWeight = FontWeight.Black,
                        textAlign = TextAlign.Center,
                        color = MaterialTheme.colorScheme.onSurface,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Spacer(modifier = Modifier.height(10.dp))
                    Text(
                        text = ticket.fault_desc ?: "No active workshop event description",
                        style = MaterialTheme.typography.bodyLarge,
                        textAlign = TextAlign.Center,
                        maxLines = 3,
                        overflow = TextOverflow.Ellipsis,
                        lineHeight = 22.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f)
                    )
                }

                // Larger priority chip (TicketRailStyle) — fills band, no wrap overflow
                Surface(
                    color = priorityColor.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(TicketRailStyle.chipRadius),
                    border = BorderStroke(1.5.dp, priorityColor.copy(alpha = 0.35f))
                ) {
                    Row(
                        modifier = Modifier.padding(
                            horizontal = TicketRailStyle.chipPadH,
                            vertical = TicketRailStyle.chipPadV
                        ),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        if (priorityColor == StatusCriticalityHigh || priorityColor == StatusCriticalityCritical) {
                            val tint = if (priorityColor == StatusCriticalityCritical) StatusCriticalityCritical else StatusCriticalityHigh
                            Icon(
                                Icons.Default.PriorityHigh,
                                contentDescription = null,
                                modifier = Modifier.size(TicketRailStyle.chipIcon),
                                tint = tint
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                        }
                        Text(
                            text = (ticket.priority ?: "NORMAL").uppercase(),
                            fontSize = TicketRailStyle.chipFont,
                            fontWeight = FontWeight.Black,
                            color = priorityColor,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                }
            }
        }
    }
}
