package com.example.wcc_companion_app.ui.components

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.ui.theme.WccTokens

/**
 * Gesture coach for the MMM shell — does NOT change physics, only explains them.
 * Opened from the top-bar ? control.
 */
@Composable
fun GestureCoachDialog(onDismiss: () -> Unit) {
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE

    val tips = if (isLandscape) {
        listOf(
            "Categories" to "Swipe up / down along the left rail to change Tickets, WOs, Equipment…",
            "Open item" to "Swipe left on a category to enter its cards; swipe right to back out.",
            "My Shift" to "At the first category, swipe down from the top edge.",
            "Search & Scan" to "At History (last category), swipe up from the bottom edge.",
            "Profile" to "Swipe right to open Profile (slides in from the left). Swipe left to leave.",
            "Live chip" to "Tap Live to resync workshop data from the server."
        )
    } else {
        listOf(
            "Categories" to "Swipe left / right to move between Tickets, WOs, Equipment…",
            "Open item" to "Swipe up on a category to open its cards; swipe down to return.",
            "My Shift" to "On Tickets, swipe right from the left edge.",
            "Search & Scan" to "On History, swipe left from the right edge.",
            "Profile" to "Swipe down from the category menu (or tap the person icon). Swipe up at the end of the profile to leave.",
            "Live chip" to "Tap Live to resync workshop data from the server."
        )
    }

    AlertDialog(
        onDismissRequest = onDismiss,
        shape = RoundedCornerShape(WccTokens.radiusXxl),
        title = {
            Text(
                "How to navigate",
                fontWeight = FontWeight.Black,
                style = MaterialTheme.typography.titleLarge
            )
        },
        text = {
            Column(
                modifier = Modifier
                    .heightIn(max = 440.dp)
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text(
                    "This app uses a spatial menu (MMM). Gestures never change — this card only teaches them.",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f),
                    lineHeight = 18.sp
                )
                tips.forEach { (title, body) ->
                    Surface(
                        shape = RoundedCornerShape(WccTokens.radiusMd),
                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.55f),
                        border = BorderStroke(
                            WccTokens.borderThin,
                            MaterialTheme.colorScheme.primary.copy(alpha = 0.25f)
                        )
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text(
                                title,
                                fontWeight = FontWeight.Black,
                                color = MaterialTheme.colorScheme.primary,
                                fontSize = 13.sp
                            )
                            Text(
                                body,
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
                                lineHeight = 17.sp,
                                modifier = Modifier.padding(top = 4.dp)
                            )
                        }
                    }
                }
            }
        },
        confirmButton = {
            TextButton(onClick = onDismiss) {
                Text("Got it", fontWeight = FontWeight.Bold)
            }
        }
    )
}
