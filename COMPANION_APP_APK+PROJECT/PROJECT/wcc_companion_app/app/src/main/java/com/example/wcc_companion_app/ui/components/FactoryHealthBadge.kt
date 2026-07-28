package com.example.wcc_companion_app.ui.components

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.data.remote.models.FactoryHealthDto
import com.example.wcc_companion_app.ui.theme.WccError
import com.example.wcc_companion_app.ui.theme.WccSuccess
import com.example.wcc_companion_app.ui.theme.WccTokens
import com.example.wcc_companion_app.ui.theme.WccWarning

/**
 * Tiny factory-health chip: **FH → 92%**
 * Bands match web (≥90 green, ≥75 amber, else red).
 * Sits between Live status and the profile cluster.
 */
@Composable
fun FactoryHealthBadge(
    health: FactoryHealthDto?,
    isDark: Boolean,
    stale: Boolean = false,
    modifier: Modifier = Modifier
) {
    if (health == null) return

    val bandColor = when {
        health.isHealthy -> WccSuccess
        health.isDegraded -> WccWarning
        else -> WccError
    }
    val alpha = if (stale) 0.55f else 1f
    val pct = if (health.health_pct == health.health_pct.toLong().toDouble()) {
        "${health.health_pct.toLong()}%"
    } else {
        "${"%.1f".format(health.health_pct)}%"
    }
    val label = "FH → $pct"
    val a11y =
        "Factory health $pct, ${health.band}. " +
            "${health.down_machines} down of ${health.total_machines}. " +
            "${health.live_tickets} live tickets."

    Text(
        text = label,
        modifier = modifier
            .semantics { contentDescription = a11y }
            .clip(RoundedCornerShape(WccTokens.radiusPill))
            .background(
                if (isDark) Color.Black.copy(alpha = 0.35f * alpha)
                else Color.White.copy(alpha = 0.55f * alpha)
            )
            .border(
                BorderStroke(1.dp, bandColor.copy(alpha = 0.5f * alpha)),
                RoundedCornerShape(WccTokens.radiusPill)
            )
            .padding(horizontal = 7.dp, vertical = 4.dp),
        fontSize = 10.sp,
        fontWeight = FontWeight.Black,
        color = bandColor.copy(alpha = 0.95f * alpha),
        maxLines = 1
    )
}
