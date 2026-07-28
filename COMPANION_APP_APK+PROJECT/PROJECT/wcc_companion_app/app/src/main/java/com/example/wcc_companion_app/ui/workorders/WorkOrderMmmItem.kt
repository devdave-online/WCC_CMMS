package com.example.wcc_companion_app.ui.workorders

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Engineering
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.*
import android.content.res.Configuration
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.ui.components.OrbiterAction
import com.example.wcc_companion_app.ui.components.OrbiterMenu
import com.example.wcc_companion_app.ui.rails.WorkOrderRailStyle
import com.example.wcc_companion_app.ui.theme.WccPrimary
import java.time.LocalDate
import java.time.format.DateTimeFormatter

// Status color palette matching the website's CSS classes
private val StatusScheduled = Color(0xFFF59E0B)  // Amber
private val StatusInProgress = Color(0xFF3B82F6)  // Blue
private val StatusOverdue = Color(0xFFEF4444)     // Red
private val StatusCompleted = Color(0xFF22C55E)   // Green
private val StatusCancelled = Color(0xFF6B7280)   // Grey

private fun getStatusColor(status: String?, scheduledDate: String?): Color {
    val isOverdue = try {
        val sched = LocalDate.parse(scheduledDate, DateTimeFormatter.ISO_LOCAL_DATE)
        (status == "Scheduled" || status == "In Progress") && sched.isBefore(LocalDate.now())
    } catch (_: Exception) { false }

    return when {
        isOverdue -> StatusOverdue
        status == "Scheduled" -> StatusScheduled
        status == "In Progress" -> StatusInProgress
        status == "Completed" -> StatusCompleted
        else -> StatusCancelled
    }
}

private fun getDisplayStatus(status: String?, scheduledDate: String?): String {
    val isOverdue = try {
        val sched = LocalDate.parse(scheduledDate, DateTimeFormatter.ISO_LOCAL_DATE)
        (status == "Scheduled" || status == "In Progress") && sched.isBefore(LocalDate.now())
    } catch (_: Exception) { false }

    return if (isOverdue) "OVERDUE" else (status ?: "Unknown").uppercase()
}

@Composable
fun WorkOrderMmmItem(
    wo: WorkOrderDto,
    isFocused: Boolean,
    onTakeover: (WorkOrderDto) -> Unit
) {
    val statusColor = getStatusColor(wo.status, wo.scheduled_date)
    val displayStatus = getDisplayStatus(wo.status, wo.scheduled_date)
    val isOverdue = displayStatus == "OVERDUE"
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE

    val actions = listOf(
        OrbiterAction(
            icon = Icons.Default.PlayArrow,
            label = "Open",
            onClick = { onTakeover(wo) },
            color = WccPrimary
        )
    )

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
                WoCardContent(
                    wo = wo,
                    statusColor = statusColor,
                    displayStatus = displayStatus,
                    isOverdue = isOverdue,
                    compact = true,
                    modifier = Modifier.fillMaxWidth()
                )
            }
            Spacer(modifier = Modifier.width(20.dp))
            OrbiterMenu(visible = isFocused, actions = actions)
        }
        return
    }

    // Portrait: top clearance under category icons; orbiter stays docked at bottom
    Column(
        modifier = Modifier
            .width(WorkOrderRailStyle.cardWidth)
            .fillMaxHeight()
            .padding(bottom = 8.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Spacer(modifier = Modifier.height(WorkOrderRailStyle.cardTopClearance))
        WoCardPortrait(
            wo = wo,
            statusColor = statusColor,
            displayStatus = displayStatus,
            isOverdue = isOverdue,
            modifier = Modifier
                .weight(1f, fill = true)
                .fillMaxWidth()
        )
        Spacer(modifier = Modifier.height(WorkOrderRailStyle.chipGap))
        OrbiterMenu(visible = isFocused, actions = actions)
    }
}

/** Portrait main glass card — fills band; inner pills stay baseline. */
@Composable
private fun WoCardPortrait(
    wo: WorkOrderDto,
    statusColor: Color,
    displayStatus: String,
    isOverdue: Boolean,
    modifier: Modifier = Modifier
) {
    val equipLabel = wo.equip_name ?: "Equipment #${wo.equipment_id ?: "?"}"
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(WorkOrderRailStyle.cardRadius),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
        border = BorderStroke(WorkOrderRailStyle.cardBorder, statusColor.copy(alpha = 0.45f))
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(WorkOrderRailStyle.cardPad),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Box(
                modifier = Modifier
                    .size(WorkOrderRailStyle.heroIcon)
                    .clip(CircleShape)
                    .background(statusColor.copy(alpha = 0.12f)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = when {
                        isOverdue -> Icons.Default.Warning
                        wo.status == "In Progress" -> Icons.Default.Engineering
                        else -> Icons.Default.Build
                    },
                    contentDescription = null,
                    modifier = Modifier.size(WorkOrderRailStyle.heroGlyph),
                    tint = statusColor
                )
            }
            Spacer(modifier = Modifier.height(WorkOrderRailStyle.chipGap))
            Text(
                "WO-${wo.wo_id}",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Black,
                textAlign = TextAlign.Center,
                maxLines = 1
            )
            Spacer(modifier = Modifier.height(WorkOrderRailStyle.chipGap))
            Text(
                wo.title,
                style = MaterialTheme.typography.bodyMedium,
                textAlign = TextAlign.Center,
                maxLines = 3,
                overflow = TextOverflow.Ellipsis,
                lineHeight = 20.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f)
            )
            Spacer(modifier = Modifier.weight(1f))
            // Inner pills — baseline borders (not fattened)
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.4f),
                shape = RoundedCornerShape(WorkOrderRailStyle.chipRadius),
                border = BorderStroke(
                    WorkOrderRailStyle.chipBorder,
                    MaterialTheme.colorScheme.primary.copy(alpha = 0.28f)
                )
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(
                            horizontal = WorkOrderRailStyle.chipPadH,
                            vertical = WorkOrderRailStyle.chipPadV
                        ),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.Center
                ) {
                    Text("⚙️", fontSize = 13.sp)
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        equipLabel,
                        fontSize = WorkOrderRailStyle.chipFont,
                        fontWeight = FontWeight.SemiBold,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }
            }
            Spacer(modifier = Modifier.height(WorkOrderRailStyle.chipGap))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(WorkOrderRailStyle.chipGap),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Surface(
                    modifier = Modifier.weight(1f),
                    color = statusColor.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(WorkOrderRailStyle.chipRadius),
                    border = BorderStroke(WorkOrderRailStyle.chipBorder, statusColor.copy(alpha = 0.4f))
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(
                                horizontal = WorkOrderRailStyle.chipPadH,
                                vertical = WorkOrderRailStyle.chipPadV
                            ),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center
                    ) {
                        if (isOverdue) {
                            Text("⚠️", fontSize = 12.sp)
                            Spacer(modifier = Modifier.width(4.dp))
                        }
                        Text(
                            displayStatus,
                            fontSize = WorkOrderRailStyle.chipFont,
                            fontWeight = FontWeight.Black,
                            color = statusColor,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                }
                Surface(
                    modifier = Modifier.weight(1f),
                    color = MaterialTheme.colorScheme.surface.copy(alpha = 0.55f),
                    shape = RoundedCornerShape(WorkOrderRailStyle.chipRadius),
                    border = BorderStroke(
                        WorkOrderRailStyle.chipBorder,
                        MaterialTheme.colorScheme.outline.copy(alpha = 0.3f)
                    )
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(
                                horizontal = WorkOrderRailStyle.chipPadH,
                                vertical = WorkOrderRailStyle.chipPadV
                            ),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center
                    ) {
                        Icon(
                            Icons.Default.Schedule,
                            null,
                            Modifier.size(WorkOrderRailStyle.chipIcon),
                            tint = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            wo.scheduled_date ?: "TBD",
                            fontSize = WorkOrderRailStyle.chipFont,
                            fontWeight = FontWeight.Bold,
                            color = if (isOverdue) StatusOverdue
                            else MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f),
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
private fun WoCardContent(
    wo: WorkOrderDto,
    statusColor: Color,
    displayStatus: String,
    isOverdue: Boolean,
    compact: Boolean,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(if (compact) 24.dp else 32.dp),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
        border = BorderStroke(1.dp, statusColor.copy(alpha = 0.4f)),
        shadowElevation = 0.dp
    ) {
        if (compact) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 14.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(56.dp)
                        .clip(CircleShape)
                        .background(statusColor.copy(alpha = 0.12f)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = when {
                            isOverdue -> Icons.Default.Warning
                            wo.status == "In Progress" -> Icons.Default.Engineering
                            else -> Icons.Default.Build
                        },
                        contentDescription = null,
                        modifier = Modifier.size(28.dp),
                        tint = statusColor
                    )
                }
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        "WO-${wo.wo_id}",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Black,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Text(
                        wo.title,
                        style = MaterialTheme.typography.bodyMedium,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f)
                    )
                    Text(
                        wo.equip_name ?: "Equipment #${wo.equipment_id ?: "?"}",
                        fontSize = 12.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                    )
                }
                Surface(
                    color = statusColor.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(WorkOrderRailStyle.chipRadius),
                    border = BorderStroke(1.dp, statusColor.copy(alpha = 0.4f))
                ) {
                    Text(
                        displayStatus,
                        modifier = Modifier.padding(
                            horizontal = WorkOrderRailStyle.chipPadH,
                            vertical = WorkOrderRailStyle.chipPadV
                        ),
                        fontSize = WorkOrderRailStyle.chipFont,
                        fontWeight = FontWeight.Black,
                        color = statusColor,
                        maxLines = 1
                    )
                }
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(WorkOrderRailStyle.cardPad),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(14.dp, Alignment.CenterVertically)
            ) {
                Box(
                    modifier = Modifier
                        .size(WorkOrderRailStyle.heroIcon)
                        .clip(CircleShape)
                        .background(statusColor.copy(alpha = 0.12f)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = when {
                            isOverdue -> Icons.Default.Warning
                            wo.status == "In Progress" -> Icons.Default.Engineering
                            else -> Icons.Default.Build
                        },
                        contentDescription = null,
                        modifier = Modifier.size(WorkOrderRailStyle.heroGlyph),
                        tint = statusColor
                    )
                }

                Text(
                    text = "WO-${wo.wo_id}",
                    style = MaterialTheme.typography.headlineSmall,
                    fontWeight = FontWeight.Black,
                    textAlign = TextAlign.Center,
                    color = MaterialTheme.colorScheme.onSurface,
                    maxLines = 1
                )

                Text(
                    text = wo.title,
                    style = MaterialTheme.typography.bodyLarge,
                    textAlign = TextAlign.Center,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis,
                    lineHeight = 22.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f)
                )

                val equipLabel = wo.equip_name ?: "Equipment #${wo.equipment_id ?: "?"}"
                Surface(
                    color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.4f),
                    shape = RoundedCornerShape(WorkOrderRailStyle.chipRadius)
                ) {
                    Row(
                        modifier = Modifier
                            .padding(
                                horizontal = WorkOrderRailStyle.chipPadH,
                                vertical = WorkOrderRailStyle.chipPadV
                            )
                            .widthIn(max = 280.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("⚙️", fontSize = 13.sp)
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = equipLabel,
                            fontSize = WorkOrderRailStyle.chipFont,
                            fontWeight = FontWeight.SemiBold,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f),
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                }

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp, Alignment.CenterHorizontally),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Surface(
                        color = statusColor.copy(alpha = 0.15f),
                        shape = RoundedCornerShape(WorkOrderRailStyle.chipRadius),
                        border = BorderStroke(1.5.dp, statusColor.copy(alpha = 0.4f))
                    ) {
                        Row(
                            modifier = Modifier.padding(
                                horizontal = WorkOrderRailStyle.chipPadH,
                                vertical = WorkOrderRailStyle.chipPadV
                            ),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            if (isOverdue) {
                                Text("⚠️", fontSize = 12.sp)
                                Spacer(modifier = Modifier.width(4.dp))
                            }
                            Text(
                                text = displayStatus,
                                fontSize = WorkOrderRailStyle.chipFont,
                                fontWeight = FontWeight.Black,
                                color = statusColor,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    }

                    Surface(
                        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.5f),
                        shape = RoundedCornerShape(WorkOrderRailStyle.chipRadius),
                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline.copy(alpha = 0.25f))
                    ) {
                        Row(
                            modifier = Modifier.padding(
                                horizontal = WorkOrderRailStyle.chipPadH,
                                vertical = WorkOrderRailStyle.chipPadV
                            ),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                Icons.Default.Schedule,
                                contentDescription = null,
                                modifier = Modifier.size(WorkOrderRailStyle.chipIcon),
                                tint = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Text(
                                text = wo.scheduled_date ?: "TBD",
                                fontSize = WorkOrderRailStyle.chipFont,
                                fontWeight = FontWeight.Bold,
                                color = if (isOverdue) StatusOverdue else MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f),
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    }
                }
            }
        }
    }
}
