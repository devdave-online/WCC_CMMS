package com.example.wcc_companion_app.ui.toolings

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Construction
import androidx.compose.material.icons.filled.Info
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.data.remote.models.ToolingDto
import com.example.wcc_companion_app.ui.components.OrbiterAction
import com.example.wcc_companion_app.ui.components.OrbiterMenu
import com.example.wcc_companion_app.ui.rails.ToolingRailStyle

/** Marker for the Toolings rail until a backend table exists / is empty. */
data class ToolingPlaceholder(val id: String = "toolings-placeholder")

@Composable
fun ToolingMmmItem(
    isFocused: Boolean,
    tooling: ToolingDto? = null,
    onOpen: (ToolingDto) -> Unit = {}
) {
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE

    if (tooling != null) {
        ToolingCard(tooling, isFocused, isLandscape, onOpen)
        return
    }

    if (isLandscape) {
        Surface(
            modifier = Modifier
                .fillMaxWidth(0.95f)
                .windowInsetsPadding(WindowInsets.navigationBars)
                .padding(vertical = 8.dp),
            shape = RoundedCornerShape(24.dp),
            color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
            border = BorderStroke(1.5.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 16.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Icon(
                    Icons.Default.Construction,
                    contentDescription = null,
                    modifier = Modifier.size(40.dp),
                    tint = MaterialTheme.colorScheme.primary.copy(alpha = 0.9f)
                )
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        "Toolings",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Black,
                        maxLines = 1
                    )
                    Text(
                        "Registry empty or not online — filter still works once table is populated.",
                        fontSize = 13.sp,
                        lineHeight = 18.sp,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f)
                    )
                }
                Surface(
                    color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f),
                    shape = RoundedCornerShape(10.dp),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
                ) {
                    Text(
                        "SOON",
                        modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Black,
                        color = MaterialTheme.colorScheme.primary
                    )
                }
            }
        }
        return
    }

    Column(
        modifier = Modifier
            .width(320.dp)
            .fillMaxHeight()
            .padding(bottom = 8.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Surface(
            modifier = Modifier
                .weight(1f, fill = true)
                .fillMaxWidth(),
            shape = RoundedCornerShape(32.dp),
            color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
            border = BorderStroke(1.5.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
        ) {
            Column(
                modifier = Modifier.fillMaxSize().padding(28.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.Center
            ) {
                Icon(
                    Icons.Default.Construction,
                    contentDescription = null,
                    modifier = Modifier.size(72.dp),
                    tint = MaterialTheme.colorScheme.primary.copy(alpha = 0.85f)
                )
                Spacer(modifier = Modifier.height(20.dp))
                Text(
                    "Toolings",
                    style = MaterialTheme.typography.headlineMedium,
                    fontWeight = FontWeight.Black,
                    textAlign = TextAlign.Center
                )
                Spacer(modifier = Modifier.height(12.dp))
                Text(
                    "Registry empty or not online yet.\nUse the filter above once tooling rows exist.",
                    textAlign = TextAlign.Center,
                    fontSize = 14.sp,
                    lineHeight = 20.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f)
                )
                Spacer(modifier = Modifier.height(20.dp))
                Surface(
                    color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f),
                    shape = RoundedCornerShape(12.dp),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
                ) {
                    Text(
                        "PLACEHOLDER",
                        modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp),
                        style = MaterialTheme.typography.labelMedium,
                        fontWeight = FontWeight.Black,
                        color = MaterialTheme.colorScheme.primary
                    )
                }
            }
        }
    }
}

@Composable
private fun ToolingCard(
    tooling: ToolingDto,
    isFocused: Boolean,
    isLandscape: Boolean,
    onOpen: (ToolingDto) -> Unit
) {
    val accent = MaterialTheme.colorScheme.primary
    val name = tooling.tooling_name ?: "Tooling"
    val code = listOfNotNull(tooling.tooling_code, tooling.barcode, tooling.asset_tag)
        .firstOrNull { !it.isNullOrBlank() } ?: "No code"
    val place = listOfNotNull(tooling.location, tooling.category).joinToString(" · ")
    val status = tooling.status ?: "Active"

    val actions = listOf(
        OrbiterAction(
            icon = Icons.Default.Info,
            label = "Info",
            onClick = { onOpen(tooling) },
            color = accent
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
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(24.dp),
                    color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
                    border = BorderStroke(1.5.dp, accent.copy(alpha = 0.35f))
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 18.dp, vertical = 14.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(14.dp)
                    ) {
                        Icon(
                            Icons.Default.Construction,
                            contentDescription = null,
                            modifier = Modifier.size(36.dp),
                            tint = accent
                        )
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                name,
                                fontWeight = FontWeight.Black,
                                maxLines = 2,
                                overflow = TextOverflow.Ellipsis
                            )
                            Text(
                                code,
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
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
                        Surface(
                            color = accent.copy(alpha = 0.12f),
                            shape = RoundedCornerShape(10.dp),
                            border = BorderStroke(1.dp, accent.copy(alpha = 0.35f))
                        ) {
                            Text(
                                status.uppercase(),
                                modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Black,
                                color = accent
                            )
                        }
                    }
                }
            }
            Spacer(modifier = Modifier.width(20.dp))
            OrbiterMenu(visible = isFocused, actions = actions)
        }
        return
    }

    Column(
        modifier = Modifier
            .width(320.dp)
            .fillMaxHeight()
            .padding(bottom = 8.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Surface(
            modifier = Modifier
                .weight(1f, fill = true)
                .fillMaxWidth(),
            shape = RoundedCornerShape(32.dp),
            color = MaterialTheme.colorScheme.surface.copy(alpha = 0.4f),
            border = BorderStroke(1.5.dp, accent.copy(alpha = 0.35f))
        ) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(ToolingRailStyle.cardPad),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(14.dp, Alignment.CenterVertically)
            ) {
                Icon(
                    Icons.Default.Construction,
                    contentDescription = null,
                    modifier = Modifier.size(ToolingRailStyle.heroGlyph),
                    tint = accent
                )
                Text(
                    name,
                    style = MaterialTheme.typography.headlineSmall,
                    fontWeight = FontWeight.Black,
                    textAlign = TextAlign.Center,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis
                )
                Text(
                    code,
                    fontSize = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Surface(
                        modifier = Modifier.weight(1f),
                        color = accent.copy(alpha = 0.12f),
                        shape = RoundedCornerShape(ToolingRailStyle.chipRadius),
                        border = BorderStroke(2.dp, accent.copy(alpha = 0.4f))
                    ) {
                        Text(
                            status.uppercase(),
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(
                                    horizontal = ToolingRailStyle.chipPadH,
                                    vertical = ToolingRailStyle.chipPadV
                                ),
                            fontSize = ToolingRailStyle.chipFont,
                            fontWeight = FontWeight.Black,
                            color = accent,
                            textAlign = TextAlign.Center,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                    tooling.category?.let { cat ->
                        Surface(
                            modifier = Modifier.weight(1f),
                            color = MaterialTheme.colorScheme.primary.copy(alpha = 0.1f),
                            shape = RoundedCornerShape(ToolingRailStyle.chipRadius),
                            border = BorderStroke(2.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.35f))
                        ) {
                            Text(
                                cat.uppercase(),
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(
                                        horizontal = ToolingRailStyle.chipPadH,
                                        vertical = ToolingRailStyle.chipPadV
                                    ),
                                fontSize = ToolingRailStyle.chipFont,
                                fontWeight = FontWeight.Black,
                                color = MaterialTheme.colorScheme.primary,
                                textAlign = TextAlign.Center,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    }
                }
                if (place.isNotBlank()) {
                    Text(
                        place,
                        fontSize = 12.sp,
                        textAlign = TextAlign.Center,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                }
            }
        }
        Spacer(modifier = Modifier.height(12.dp))
        OrbiterMenu(visible = isFocused, actions = actions)
    }
}
