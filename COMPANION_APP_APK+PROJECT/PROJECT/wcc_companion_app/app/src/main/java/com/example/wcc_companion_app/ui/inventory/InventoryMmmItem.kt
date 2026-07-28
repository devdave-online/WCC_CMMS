package com.example.wcc_companion_app.ui.inventory

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
import com.example.wcc_companion_app.ui.components.OrbiterAction
import com.example.wcc_companion_app.ui.components.OrbiterMenu
import com.example.wcc_companion_app.ui.rails.InventoryRailStyle

@Composable
fun InventoryMmmItem(
    part: InventoryPartDto,
    isFocused: Boolean,
    onOpen: (InventoryPartDto) -> Unit
) {
    val status = classifyStock(part)
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE

    val actions = listOf(
        OrbiterAction(
            icon = Icons.Default.Info,
            label = "Info",
            onClick = { onOpen(part) },
            color = status.color
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
                PartCard(part, status, Modifier.fillMaxWidth(), compact = true)
            }
            Spacer(modifier = Modifier.width(20.dp))
            OrbiterMenu(visible = isFocused, actions = actions)
        }
    } else {
        Column(
            modifier = Modifier
                .width(320.dp)
                .fillMaxHeight()
                .padding(bottom = 8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            PartCard(
                part,
                status,
                Modifier
                    .weight(1f, fill = true)
                    .fillMaxWidth(),
                compact = false
            )
            Spacer(modifier = Modifier.height(12.dp))
            OrbiterMenu(visible = isFocused, actions = actions)
        }
    }
}

@Composable
private fun PartCard(
    part: InventoryPartDto,
    status: StockState,
    modifier: Modifier = Modifier,
    compact: Boolean = false
) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(if (compact) 24.dp else 32.dp),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
        border = BorderStroke(1.5.dp, status.color.copy(alpha = 0.4f))
    ) {
        if (compact) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 18.dp, vertical = 14.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(52.dp)
                        .clip(CircleShape)
                        .background(
                            Brush.radialGradient(
                                listOf(status.color.copy(alpha = 0.3f), status.color.copy(alpha = 0.04f))
                            )
                        )
                        .border(BorderStroke(1.dp, status.color.copy(alpha = 0.35f)), CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(Icons.Default.Inventory2, null, Modifier.size(26.dp), tint = status.color)
                }
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        part.part_name,
                        fontWeight = FontWeight.Black,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                    Text(
                        part.internal_code.ifBlank { "No code" },
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f)
                    )
                    Text(
                        "Stock ${part.stock_level}" +
                            (part.minimum_threshold?.let { " · Min $it" } ?: "") +
                            " · Bin ${binLocation(part)}",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }
                Surface(
                    color = status.color.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(InventoryRailStyle.chipRadius),
                    border = BorderStroke(1.5.dp, status.color.copy(alpha = 0.4f))
                ) {
                    Text(
                        when (status) {
                            StockState.HEALTHY -> "OK"
                            StockState.APPROACHING -> "LOW"
                            StockState.REORDER, StockState.NO_VENDOR -> "REORDER"
                            StockState.OUT -> "OUT"
                            StockState.OBSOLETE -> "OBS"
                        },
                        modifier = Modifier.padding(
                            horizontal = InventoryRailStyle.chipPadH,
                            vertical = InventoryRailStyle.chipPadV
                        ),
                        fontSize = InventoryRailStyle.chipFont,
                        fontWeight = FontWeight.Black,
                        color = status.color,
                        maxLines = 1
                    )
                }
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(InventoryRailStyle.cardPad),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(14.dp, Alignment.CenterVertically)
            ) {
                Box(
                    modifier = Modifier
                        .size(InventoryRailStyle.heroIcon)
                        .clip(CircleShape)
                        .background(
                            Brush.radialGradient(
                                listOf(status.color.copy(alpha = 0.3f), status.color.copy(alpha = 0.04f))
                            )
                        )
                        .border(BorderStroke(1.dp, status.color.copy(alpha = 0.35f)), CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        Icons.Default.Inventory2,
                        contentDescription = null,
                        modifier = Modifier.size(InventoryRailStyle.heroGlyph),
                        tint = status.color
                    )
                }

                Text(
                    part.part_name,
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Black,
                    textAlign = TextAlign.Center,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis
                )

                Text(
                    part.internal_code.ifBlank { "No code" },
                    fontSize = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )

                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    color = status.color.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(InventoryRailStyle.chipRadius),
                    border = BorderStroke(2.dp, status.color.copy(alpha = 0.45f))
                ) {
                    Text(
                        status.label.uppercase(),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(
                                horizontal = InventoryRailStyle.chipPadH,
                                vertical = InventoryRailStyle.chipPadV
                            ),
                        fontSize = InventoryRailStyle.chipFont,
                        fontWeight = FontWeight.Black,
                        color = status.color,
                        textAlign = TextAlign.Center,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }

                Text(
                    "Stock: ${part.stock_level}" +
                        (part.minimum_threshold?.let { "  ·  Min: $it" } ?: "") +
                        (part.uom?.let { " $it" } ?: ""),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )

                Text(
                    "Bin: ${binLocation(part)}",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
    }
}
