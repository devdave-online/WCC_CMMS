package com.example.wcc_companion_app.ui.sync

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.systemBars
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.windowInsetsPadding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CloudUpload
import androidx.compose.material.icons.filled.PhotoCamera
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.Button
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.wcc_companion_app.data.local.entity.PendingMediaEntity
import com.example.wcc_companion_app.data.local.entity.PendingTicketOpEntity
import com.example.wcc_companion_app.ui.theme.WccError
import com.example.wcc_companion_app.ui.theme.WccPrimary
import com.example.wcc_companion_app.ui.theme.WccTokens
import com.example.wcc_companion_app.ui.theme.WccWarning
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * Outbox transparency — pending ops + photo uploads, retry, honest errors.
 * Opened from the Live badge when there is queue or on demand.
 */
@Composable
fun OutboxSheet(
    onDismiss: () -> Unit,
    onRetry: () -> Unit,
    viewModel: OutboxViewModel = hiltViewModel(),
) {
    val ops by viewModel.openOps.collectAsState()
    val media by viewModel.openMedia.collectAsState()
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE
    val failedOps = ops.count { it.status == "FAILED" || it.status == "CONFLICT" }
    val failedMedia = media.count { it.status == "FAILED" }
    val total = ops.size + media.size

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Color.Black.copy(alpha = 0.55f))
                .windowInsetsPadding(WindowInsets.systemBars),
            contentAlignment = Alignment.Center
        ) {
            Surface(
                modifier = Modifier
                    .fillMaxWidth(if (isLandscape) 0.72f else 0.94f)
                    .fillMaxHeight(if (isLandscape) 0.9f else 0.78f),
                shape = RoundedCornerShape(WccTokens.radiusXxl),
                color = MaterialTheme.colorScheme.surface.copy(alpha = 0.98f),
                border = BorderStroke(
                    WccTokens.borderThin,
                    MaterialTheme.colorScheme.primary.copy(alpha = 0.28f)
                ),
                shadowElevation = 10.dp
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(18.dp)
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.CloudUpload, contentDescription = null, tint = WccPrimary)
                        Spacer(modifier = Modifier.width(10.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                "Sync queue",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Black,
                                color = WccPrimary
                            )
                            Text(
                                when {
                                    total == 0 -> "Nothing waiting — all clear"
                                    failedOps + failedMedia > 0 ->
                                        "$total pending · ${failedOps + failedMedia} need attention"
                                    else -> "$total pending · will push when plant is reachable"
                                },
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f)
                            )
                        }
                    }

                    Spacer(modifier = Modifier.height(12.dp))

                    LazyColumn(
                        modifier = Modifier.weight(1f),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        if (ops.isEmpty() && media.isEmpty()) {
                            item {
                                Text(
                                    "No queued actions or photos.",
                                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                                    fontSize = 14.sp,
                                    modifier = Modifier.padding(vertical = 24.dp)
                                )
                            }
                        }
                        if (ops.isNotEmpty()) {
                            item { SectionHeader("ACTIONS (${ops.size})") }
                            items(ops, key = { it.opId }) { op -> OpRow(op) }
                        }
                        if (media.isNotEmpty()) {
                            item { SectionHeader("PHOTOS (${media.size})") }
                            items(media, key = { it.mediaId }) { m -> MediaRow(m) }
                        }
                    }

                    Spacer(modifier = Modifier.height(12.dp))
                    Row(
                        horizontalArrangement = Arrangement.spacedBy(10.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        OutlinedButton(
                            onClick = onDismiss,
                            modifier = Modifier
                                .weight(1f)
                                .height(48.dp),
                            shape = RoundedCornerShape(WccTokens.radiusMd)
                        ) {
                            Text("Close", fontWeight = FontWeight.Bold)
                        }
                        Button(
                            onClick = onRetry,
                            modifier = Modifier
                                .weight(1f)
                                .height(48.dp),
                            shape = RoundedCornerShape(WccTokens.radiusMd),
                            enabled = total > 0
                        ) {
                            Icon(
                                Icons.Default.Sync,
                                contentDescription = null,
                                modifier = Modifier.size(18.dp)
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Retry now", fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun SectionHeader(title: String) {
    Text(
        title,
        fontSize = 11.sp,
        fontWeight = FontWeight.Black,
        color = MaterialTheme.colorScheme.primary,
        modifier = Modifier.padding(top = 6.dp, bottom = 2.dp)
    )
}

@Composable
private fun OpRow(op: PendingTicketOpEntity) {
    val statusColor = when (op.status) {
        "FAILED", "CONFLICT" -> WccError
        "IN_FLIGHT" -> WccWarning
        else -> WccPrimary
    }
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(WccTokens.radiusMd),
        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
        border = BorderStroke(1.dp, statusColor.copy(alpha = 0.35f))
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Default.Sync,
                    contentDescription = null,
                    tint = statusColor,
                    modifier = Modifier.size(18.dp)
                )
                Spacer(modifier = Modifier.width(8.dp))
                Text(
                    op.type.replace('_', ' '),
                    fontWeight = FontWeight.Bold,
                    fontSize = 14.sp,
                    modifier = Modifier.weight(1f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                StatusPill(op.status, statusColor)
            }
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                op.ticketId,
                fontSize = 12.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f)
            )
            val err = op.lastError
            if (!err.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(4.dp))
                Row(verticalAlignment = Alignment.Top) {
                    Icon(
                        Icons.Default.Warning,
                        contentDescription = null,
                        tint = WccError,
                        modifier = Modifier.size(14.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        err,
                        fontSize = 11.sp,
                        color = WccError,
                        maxLines = 3,
                        overflow = TextOverflow.Ellipsis
                    )
                }
            }
            Text(
                "Queued ${formatAge(op.createdAt)}" +
                    if (op.retryCount > 0) " · ${op.retryCount} retries" else "",
                fontSize = 10.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.45f),
                modifier = Modifier.padding(top = 4.dp)
            )
        }
    }
}

@Composable
private fun MediaRow(m: PendingMediaEntity) {
    val statusColor = when (m.status) {
        "FAILED" -> WccError
        "IN_FLIGHT" -> WccWarning
        else -> WccPrimary
    }
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(WccTokens.radiusMd),
        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
        border = BorderStroke(1.dp, statusColor.copy(alpha = 0.35f))
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Default.PhotoCamera,
                    contentDescription = null,
                    tint = statusColor,
                    modifier = Modifier.size(18.dp)
                )
                Spacer(modifier = Modifier.width(8.dp))
                Text(
                    "Photo · ${m.parentKey}",
                    fontWeight = FontWeight.Bold,
                    fontSize = 14.sp,
                    modifier = Modifier.weight(1f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                StatusPill(m.status, statusColor)
            }
            val err = m.lastError
            if (!err.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text(err, fontSize = 11.sp, color = WccError, maxLines = 2)
            }
            Text(
                "Queued ${formatAge(m.createdAt)}" +
                    if (m.retryCount > 0) " · ${m.retryCount} retries" else "",
                fontSize = 10.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.45f),
                modifier = Modifier.padding(top = 4.dp)
            )
        }
    }
}

@Composable
private fun StatusPill(status: String, color: Color) {
    Surface(
        shape = RoundedCornerShape(50),
        color = color.copy(alpha = 0.15f)
    ) {
        Text(
            status,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
            fontSize = 10.sp,
            fontWeight = FontWeight.Black,
            color = color
        )
    }
}

private fun formatAge(createdAt: Long): String {
    val fmt = SimpleDateFormat("HH:mm", Locale.getDefault())
    return fmt.format(Date(createdAt))
}
