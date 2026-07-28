package com.example.wcc_companion_app.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.BuildConfig

/** Compact top strip: Open Beta channel + version for field support. */
@Composable
fun OpenBetaBanner(
    onDismiss: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        color = Color(0xFF0EA5E9).copy(alpha = 0.18f),
        shape = RoundedCornerShape(0.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 12.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = "Open Beta ${BuildConfig.DISPLAY_VERSION}",
                    fontWeight = FontWeight.Black,
                    fontSize = 12.sp,
                    color = Color(0xFF38BDF8)
                )
                Text(
                    text = "Plant companion pilot · report issues to your admin",
                    fontSize = 10.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f)
                )
            }
            TextButton(onClick = onDismiss) {
                Text("Got it", fontWeight = FontWeight.Bold, fontSize = 12.sp)
            }
        }
    }
}

/** First-run disclaimer for Open Beta 1.0.0 (in-app only). */
@Composable
fun OpenBetaDisclaimerDialog(
    onAccept: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = { /* must accept */ },
        title = {
            Text(
                "WCC Companion — Open Beta ${BuildConfig.DISPLAY_VERSION}",
                fontWeight = FontWeight.Black
            )
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text(
                    "This is beta software for plant floor pilots.",
                    fontWeight = FontWeight.SemiBold
                )
                Text(
                    "• Offline work queues on this device until the plant server is reachable.\n" +
                        "• Not a sole safety system of record — confirm critical actions on web when unsure.\n" +
                        "• Biometric lock stays on this handset only (no plant DB).\n" +
                        "• Language packs: web is full; companion localizes floor chrome first.",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f)
                )
                Text(
                    "Build ${BuildConfig.VERSION_NAME} (${BuildConfig.VERSION_CODE}) · ${BuildConfig.CHANNEL}",
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                )
            }
        },
        confirmButton = {
            TextButton(onClick = onAccept) {
                Text("I understand — continue", fontWeight = FontWeight.Bold)
            }
        }
    )
}

@Composable
fun AppVersionFooter(modifier: Modifier = Modifier) {
    Text(
        text = "Open Beta ${BuildConfig.DISPLAY_VERSION} · v${BuildConfig.VERSION_NAME} (${BuildConfig.VERSION_CODE})",
        modifier = modifier.padding(vertical = 4.dp),
        fontSize = 11.sp,
        fontWeight = FontWeight.SemiBold,
        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.45f)
    )
}
