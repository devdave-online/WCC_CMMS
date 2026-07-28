package com.example.wcc_companion_app.ui.components

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.ui.theme.WccTokens

/**
 * All | Mine toggle for Tickets / Work Orders rails — large floor-friendly chips.
 */
@Composable
fun MineFilterStrip(
    mineOnly: Boolean,
    mineCount: Int,
    totalCount: Int,
    onMineOnlyChange: (Boolean) -> Unit,
    modifier: Modifier = Modifier,
) {
    val primary = MaterialTheme.colorScheme.primary
    Row(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 14.dp, vertical = 8.dp),
        horizontalArrangement = Arrangement.spacedBy(12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        FilterChip(
            selected = !mineOnly,
            onClick = { onMineOnlyChange(false) },
            label = {
                Text(
                    "All · $totalCount",
                    fontWeight = FontWeight.Black,
                    fontSize = 15.sp,
                    maxLines = 1
                )
            },
            shape = RoundedCornerShape(WccTokens.radiusPill),
            border = BorderStroke(
                1.5.dp,
                primary.copy(alpha = if (!mineOnly) 0.65f else 0.28f)
            ),
            colors = FilterChipDefaults.filterChipColors(
                selectedContainerColor = primary.copy(alpha = 0.28f),
                containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.45f),
                labelColor = MaterialTheme.colorScheme.onSurface,
                selectedLabelColor = MaterialTheme.colorScheme.onSurface
            ),
            modifier = Modifier
                .weight(1f)
                .height(52.dp)
        )
        FilterChip(
            selected = mineOnly,
            onClick = { onMineOnlyChange(true) },
            label = {
                Text(
                    "Mine · $mineCount",
                    fontWeight = FontWeight.Black,
                    fontSize = 15.sp,
                    maxLines = 1
                )
            },
            shape = RoundedCornerShape(WccTokens.radiusPill),
            border = BorderStroke(
                1.5.dp,
                primary.copy(alpha = if (mineOnly) 0.65f else 0.28f)
            ),
            colors = FilterChipDefaults.filterChipColors(
                selectedContainerColor = primary.copy(alpha = 0.28f),
                containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.45f),
                labelColor = MaterialTheme.colorScheme.onSurface,
                selectedLabelColor = MaterialTheme.colorScheme.onSurface
            ),
            modifier = Modifier
                .weight(1f)
                .height(52.dp)
        )
    }
}
