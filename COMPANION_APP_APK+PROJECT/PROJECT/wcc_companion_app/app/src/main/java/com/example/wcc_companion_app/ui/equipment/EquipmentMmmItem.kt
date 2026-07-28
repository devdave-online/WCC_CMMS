package com.example.wcc_companion_app.ui.equipment

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.QrCodeScanner
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
import com.example.wcc_companion_app.data.remote.models.EquipmentDto
import com.example.wcc_companion_app.ui.components.OrbiterAction
import com.example.wcc_companion_app.ui.components.OrbiterMenu
import com.example.wcc_companion_app.ui.rails.EquipmentRailStyle

private fun criticalityColor(c: String?): Color = when (c?.uppercase()) {
    "A" -> Color(0xFFEF4444)
    "B" -> Color(0xFFF59E0B)
    "C" -> Color(0xFF3B82F6)
    else -> Color(0xFF8B5CF6)
}

@Composable
fun EquipmentMmmItem(
    equipment: EquipmentDto,
    isFocused: Boolean,
    onOpen: (EquipmentDto) -> Unit,
    onScan: () -> Unit
) {
    val accent = criticalityColor(equipment.criticality)
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE

    val actions = listOf(
        OrbiterAction(
            icon = Icons.Default.Info,
            label = "Info",
            onClick = { onOpen(equipment) },
            color = accent
        ),
        OrbiterAction(
            icon = Icons.Default.QrCodeScanner,
            label = "Scan",
            onClick = onScan,
            color = MaterialTheme.colorScheme.primary
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
                EquipmentCard(equipment, accent, Modifier.fillMaxWidth(), compact = true)
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
            EquipmentCard(
                equipment,
                accent,
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
private fun EquipmentCard(
    eq: EquipmentDto,
    accent: Color,
    modifier: Modifier = Modifier,
    compact: Boolean = false
) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(if (compact) 24.dp else 32.dp),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
        border = BorderStroke(1.5.dp, accent.copy(alpha = 0.35f)),
        shadowElevation = 0.dp
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
                                listOf(accent.copy(alpha = 0.32f), accent.copy(alpha = 0.04f))
                            )
                        )
                        .border(BorderStroke(1.dp, accent.copy(alpha = 0.35f)), CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(Icons.Default.Settings, null, Modifier.size(26.dp), tint = accent)
                }
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        eq.equip_name,
                        fontWeight = FontWeight.Black,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                    Text(
                        eq.asset_uuid ?: "No asset tag",
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    val place = listOfNotNull(eq.plant_name, eq.line_name, eq.station_name)
                        .joinToString(" · ")
                    if (place.isNotBlank()) {
                        Text(
                            place,
                            fontSize = 11.sp,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                        )
                    }
                }
                eq.criticality?.let { Chip("CRIT $it", accent) }
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(EquipmentRailStyle.cardPad),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(14.dp, Alignment.CenterVertically)
            ) {
                Box(
                    modifier = Modifier
                        .size(EquipmentRailStyle.heroIcon)
                        .clip(CircleShape)
                        .background(
                            Brush.radialGradient(
                                listOf(accent.copy(alpha = 0.32f), accent.copy(alpha = 0.04f))
                            )
                        )
                        .border(BorderStroke(1.dp, accent.copy(alpha = 0.35f)), CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        Icons.Default.Settings,
                        contentDescription = null,
                        modifier = Modifier.size(EquipmentRailStyle.heroGlyph),
                        tint = accent
                    )
                }

                Text(
                    text = eq.equip_name,
                    style = MaterialTheme.typography.headlineSmall,
                    fontWeight = FontWeight.Black,
                    textAlign = TextAlign.Center,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis,
                    color = MaterialTheme.colorScheme.onSurface
                )

                Text(
                    text = eq.asset_uuid ?: "No asset tag",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )

                // Full-width chip borders (equal share) — more label before truncate
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    val crit = eq.criticality
                    val cat = eq.category
                    when {
                        crit != null && cat != null -> {
                            Chip("CRIT $crit", accent, Modifier.weight(1f))
                            Chip(cat, MaterialTheme.colorScheme.primary, Modifier.weight(1f))
                        }
                        crit != null -> Chip("CRIT $crit", accent, Modifier.fillMaxWidth())
                        cat != null -> Chip(cat, MaterialTheme.colorScheme.primary, Modifier.fillMaxWidth())
                    }
                }

                val place = listOfNotNull(eq.plant_name, eq.line_name, eq.station_name)
                    .joinToString(" · ")
                if (place.isNotBlank()) {
                    Text(
                        text = place,
                        fontSize = 12.sp,
                        textAlign = TextAlign.Center,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                }
            }
        }
    }
}

@Composable
private fun Chip(text: String, color: Color, modifier: Modifier = Modifier) {
    Surface(
        modifier = modifier,
        color = color.copy(alpha = 0.15f),
        shape = RoundedCornerShape(EquipmentRailStyle.chipRadius),
        border = BorderStroke(2.dp, color.copy(alpha = 0.4f))
    ) {
        Text(
            text = text.uppercase(),
            modifier = Modifier
                .fillMaxWidth()
                .padding(
                    horizontal = EquipmentRailStyle.chipPadH,
                    vertical = EquipmentRailStyle.chipPadV
                ),
            fontSize = EquipmentRailStyle.chipFont,
            fontWeight = FontWeight.Black,
            color = color,
            textAlign = TextAlign.Center,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
    }
}
