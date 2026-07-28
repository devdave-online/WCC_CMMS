package com.example.wcc_companion_app.ui.components

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Tune
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.platform.LocalSoftwareKeyboardController
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.wcc_companion_app.ui.theme.WccTokens

data class RailFilterChip(
    val id: String,
    val label: String
)

enum class RailFilterDomain {
    EQUIPMENT,
    TOOLINGS,
    INVENTORY
}

/**
 * Compact search pill — sits under the shrinked MMM category icons, above the cards.
 * Layout padding is owned by [MmmLayout]; this composable is only the control itself.
 * Tap opens [RailFilterSheet] for content-aware criteria.
 */
@Composable
fun RailFilterStrip(
    domain: RailFilterDomain,
    query: String,
    chipId: String?,
    matchCount: Int,
    totalCount: Int,
    onOpenSetup: () -> Unit,
    onClear: () -> Unit,
    modifier: Modifier = Modifier
) {
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE
    val active = query.isNotBlank() || chipId != null
    val hint = when (domain) {
        RailFilterDomain.EQUIPMENT -> "Search equipment…"
        RailFilterDomain.TOOLINGS -> "Search tooling…"
        RailFilterDomain.INVENTORY -> "Search parts…"
    }
    val summary = buildString {
        if (query.isNotBlank()) append("“$query”")
        if (chipId != null) {
            if (isNotEmpty()) append(" · ")
            append(chipId.uppercase())
        }
        if (isEmpty()) append(hint)
    }

    Surface(
        modifier = modifier
            .then(
                if (isLandscape) Modifier
                    .widthIn(max = 320.dp)
                    .padding(start = 112.dp, end = 12.dp)
                else Modifier
                    .fillMaxWidth()
                    .padding(horizontal = WccTokens.space2xl) // 24 dp — M3 side margin
            )
            .clickable(
                indication = null,
                interactionSource = remember { MutableInteractionSource() },
                onClick = onOpenSetup
            ),
        // M3 SearchBar height = 56 dp; full-width with 16 dp side margins (8 dp grid)
        shape = RoundedCornerShape(WccTokens.radiusXl),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.55f),
        border = BorderStroke(
            WccTokens.borderThin,
            MaterialTheme.colorScheme.primary.copy(alpha = if (active) 0.5f else 0.28f)
        ),
        shadowElevation = 0.dp
    ) {
        Row(
            modifier = Modifier
                .height(WccTokens.searchBarHeight)
                .defaultMinSize(minHeight = WccTokens.touchMin)
                .padding(horizontal = WccTokens.spaceLg),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(WccTokens.spaceMd)
        ) {
            Icon(
                Icons.Default.Search,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(24.dp)
            )
            Text(
                summary,
                modifier = Modifier.weight(1f),
                fontSize = 15.sp,
                fontWeight = if (active) FontWeight.SemiBold else FontWeight.Medium,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = if (active) 0.9f else 0.5f),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            Text(
                if (!active || matchCount == totalCount) "$totalCount"
                else "$matchCount/$totalCount",
                fontSize = 14.sp,
                fontWeight = FontWeight.Black,
                color = MaterialTheme.colorScheme.primary
            )
            if (active) {
                Box(
                    modifier = Modifier
                        .size(WccTokens.touchMin)
                        .clickable(
                            indication = null,
                            interactionSource = remember { MutableInteractionSource() },
                            onClick = onClear
                        ),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        Icons.Default.Close,
                        contentDescription = "Clear filter",
                        tint = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                        modifier = Modifier.size(20.dp)
                    )
                }
            } else {
                Icon(
                    Icons.Default.Tune,
                    contentDescription = "Open filter",
                    tint = MaterialTheme.colorScheme.primary.copy(alpha = 0.7f),
                    modifier = Modifier.size(22.dp)
                )
            }
        }
    }
}

/**
 * Content-aware filter setup. Equipment ≠ parts ≠ tooling.
 * Dismiss: Back / tap scrim (no header X).
 */
@Composable
fun RailFilterSheet(
    domain: RailFilterDomain,
    query: String,
    chipId: String?,
    chips: List<RailFilterChip>,
    matchCount: Int,
    totalCount: Int,
    onQueryChange: (String) -> Unit,
    onChipSelect: (String?) -> Unit,
    onDismiss: () -> Unit,
    onApply: () -> Unit = onDismiss
) {
    val focus = LocalFocusManager.current
    val keyboard = LocalSoftwareKeyboardController.current
    val title = when (domain) {
        RailFilterDomain.EQUIPMENT -> "Filter equipment"
        RailFilterDomain.TOOLINGS -> "Filter tooling"
        RailFilterDomain.INVENTORY -> "Filter parts"
    }
    val placeholder = when (domain) {
        RailFilterDomain.EQUIPMENT -> "Asset tag, name, line, plant, OEM…"
        RailFilterDomain.TOOLINGS -> "Tooling name, code, barcode…"
        RailFilterDomain.INVENTORY -> "Part name, internal code, bin…"
    }
    val helper = when (domain) {
        RailFilterDomain.EQUIPMENT ->
            "Only searches machines in this rail — not parts or work orders."
        RailFilterDomain.TOOLINGS ->
            "Only tooling registry fields — not equipment or inventory."
        RailFilterDomain.INVENTORY ->
            "Only spare parts & stock — not machines or tickets."
    }

    // Shared modal shell: full-bleed dark scrim (orbiter not readable underneath),
    // content-sized sheet (no empty voids).
    WccDetailModal(onDismiss = onDismiss) {
        Text(
            title,
            style = MaterialTheme.typography.titleLarge,
            fontWeight = FontWeight.Black,
            color = MaterialTheme.colorScheme.primary
        )
        Text(
            helper,
            fontSize = 12.sp,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
            lineHeight = 16.sp,
            modifier = Modifier.padding(top = 4.dp, bottom = 4.dp)
        )

        OutlinedTextField(
            value = query,
            onValueChange = onQueryChange,
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 8.dp),
            singleLine = true,
            shape = RoundedCornerShape(14.dp),
            placeholder = { Text(placeholder, fontSize = 13.sp) },
            leadingIcon = {
                Icon(Icons.Default.Search, contentDescription = null)
            },
            trailingIcon = {
                if (query.isNotEmpty()) {
                    IconButton(onClick = { onQueryChange("") }) {
                        Icon(Icons.Default.Close, contentDescription = "Clear")
                    }
                }
            },
            keyboardOptions = KeyboardOptions(imeAction = ImeAction.Done),
            keyboardActions = KeyboardActions(
                onDone = {
                    focus.clearFocus()
                    keyboard?.hide()
                    onApply()
                }
            )
        )

        if (chips.isNotEmpty()) {
            Text(
                when (domain) {
                    RailFilterDomain.EQUIPMENT -> "Criticality"
                    RailFilterDomain.INVENTORY -> "Stock health"
                    else -> "Filters"
                },
                fontSize = 11.sp,
                fontWeight = FontWeight.Black,
                color = MaterialTheme.colorScheme.primary,
                modifier = Modifier.padding(top = 12.dp, bottom = 6.dp)
            )
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                chips.forEach { chip ->
                    val selected = chipId == chip.id
                    FilterChip(
                        selected = selected,
                        onClick = {
                            onChipSelect(if (selected) null else chip.id)
                        },
                        label = {
                            Text(
                                chip.label,
                                fontWeight = FontWeight.Bold,
                                fontSize = 12.sp
                            )
                        },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor =
                                MaterialTheme.colorScheme.primary.copy(alpha = 0.22f),
                            selectedLabelColor = MaterialTheme.colorScheme.primary
                        )
                    )
                }
            }
        }

        Text(
            if (query.isBlank() && chipId == null) {
                "No filter — showing all $totalCount"
            } else {
                "$matchCount of $totalCount match"
            },
            fontSize = 12.sp,
            fontWeight = FontWeight.SemiBold,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
            modifier = Modifier.padding(top = 12.dp, bottom = 8.dp)
        )

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            OutlinedButton(
                onClick = {
                    onQueryChange("")
                    onChipSelect(null)
                },
                modifier = Modifier
                    .weight(1f)
                    .height(WccTokens.touchMin),
                shape = RoundedCornerShape(14.dp)
            ) {
                Text("Clear", fontWeight = FontWeight.Bold)
            }
            Button(
                onClick = {
                    focus.clearFocus()
                    keyboard?.hide()
                    onApply()
                },
                modifier = Modifier
                    .weight(1.15f)
                    .height(WccTokens.touchMin),
                shape = RoundedCornerShape(14.dp)
            ) {
                Text(
                    "Apply",
                    fontWeight = FontWeight.Bold,
                    maxLines = 1
                )
            }
        }
    }
}
