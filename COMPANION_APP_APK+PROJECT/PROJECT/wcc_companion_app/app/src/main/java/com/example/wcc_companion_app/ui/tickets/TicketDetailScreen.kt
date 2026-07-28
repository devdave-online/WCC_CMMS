package com.example.wcc_companion_app.ui.tickets

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Send
import androidx.compose.material3.*
import androidx.compose.runtime.*
import android.content.res.Configuration
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.wcc_companion_app.data.remote.models.TicketCommentDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.ui.theme.WccPrimary

/**
 * Ticket inspection overlay — mirrors the expanded child-row on
 * `_maint/active_tickets.php`: fault, equipment, PIC, timeline, live comments.
 */
@Composable
fun TicketDetailScreen(
    ticket: TicketDto,
    onClose: () -> Unit,
    viewModel: TicketViewModel = hiltViewModel()
) {
    com.example.wcc_companion_app.ui.components.KeepScreenOn()
    var equipName by remember { mutableStateOf<String?>(null) }
    var actions by remember {
        mutableStateOf<List<com.example.wcc_companion_app.data.remote.models.TicketActionDto>>(emptyList())
    }
    var comments by remember { mutableStateOf<List<TicketCommentDto>>(emptyList()) }
    var loading by remember { mutableStateOf(true) }
    var commentDraft by remember { mutableStateOf("") }
    var sending by remember { mutableStateOf(false) }
    var errorText by remember { mutableStateOf<String?>(null) }
    var commentsEpoch by remember { mutableIntStateOf(0) }

    LaunchedEffect(ticket.ticket_id, commentsEpoch) {
        if (commentsEpoch == 0) loading = true
        if (commentsEpoch == 0) {
            equipName = viewModel.fetchEquipmentName(ticket.equip_id)
            actions = viewModel.fetchTicketActions(ticket.ticket_id)
        }
        comments = viewModel.fetchTicketComments(ticket.ticket_id)
        loading = false
    }

    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE
    val canComment = !ticket.status.equals("CLOSED", ignoreCase = true)

    Dialog(
        onDismissRequest = onClose,
        properties = DialogProperties(
            usePlatformDefaultWidth = false,
            dismissOnBackPress = true,
            dismissOnClickOutside = true
        )
    ) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Color.Black.copy(alpha = 0.78f))
                .padding(if (isLandscape) 8.dp else 0.dp),
            contentAlignment = Alignment.Center
        ) {
            // Landscape is short — almost full screen + single scroll so fault/timeline
            // are never crushed under a sticky composer (weight(1f) collapsed to ~0).
            Surface(
                modifier = Modifier
                    .windowInsetsPadding(WindowInsets.systemBars)
                    .fillMaxWidth(if (isLandscape) 0.94f else 0.94f)
                    .fillMaxHeight(if (isLandscape) 0.98f else 0.92f)
                    .imePadding(),
                shape = RoundedCornerShape(if (isLandscape) 20.dp else 28.dp),
                color = MaterialTheme.colorScheme.surface.copy(alpha = 0.97f),
                border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline.copy(alpha = 0.4f)),
                shadowElevation = 24.dp
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(if (isLandscape) 14.dp else 20.dp)
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = "TICKET INSPECTION",
                                style = MaterialTheme.typography.labelLarge,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.primary
                            )
                            Text(
                                text = ticket.ticket_id,
                                style = if (isLandscape) MaterialTheme.typography.titleLarge
                                else MaterialTheme.typography.headlineSmall,
                                fontWeight = FontWeight.Black,
                                maxLines = 1
                            )
                        }
                    }

                    Spacer(modifier = Modifier.height(if (isLandscape) 6.dp else 8.dp))

                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        StatusChip((ticket.status ?: "OPEN").uppercase())
                        PriorityChip((ticket.priority ?: "normal").uppercase())
                    }

                    Spacer(modifier = Modifier.height(if (isLandscape) 6.dp else 12.dp))

                    if (isLandscape) {
                        // Two panes: meta left, fault/timeline/comments right — both fully usable
                        // without fighting the short height of landscape phones.
                        Row(
                            modifier = Modifier
                                .weight(1f, fill = true)
                                .fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Column(
                                modifier = Modifier
                                    .weight(0.42f)
                                    .fillMaxHeight()
                                    .verticalScroll(rememberScrollState()),
                                verticalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                MetaGrid(
                                    equipName = equipName,
                                    equipId = ticket.equip_id,
                                    pic = ticket.pic,
                                    announcedBy = ticket.announced_by,
                                    reportDate = ticket.report_date,
                                    reportTime = ticket.report_time
                                )
                            }
                            Column(
                                modifier = Modifier
                                    .weight(0.58f)
                                    .fillMaxHeight()
                                    .verticalScroll(rememberScrollState()),
                                verticalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                SectionLabel("FAULT DESCRIPTION")
                                Text(
                                    text = ticket.fault_desc ?: "No description provided.",
                                    style = MaterialTheme.typography.bodyMedium,
                                    lineHeight = 20.sp
                                )
                                SectionLabel("INTERVENTION TIMELINE")
                                if (loading) {
                                    LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
                                } else if (actions.isEmpty()) {
                                    Text(
                                        "No actions logged yet.",
                                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                                        fontSize = 13.sp
                                    )
                                } else {
                                    actions.forEach { ActionTimelineCard(it) }
                                }
                                SectionLabel("💬 LIVE COMMENTS")
                                if (!loading && comments.isEmpty()) {
                                    Text(
                                        "No comments yet.",
                                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                                        fontSize = 13.sp,
                                        fontStyle = FontStyle.Italic
                                    )
                                } else {
                                    comments.forEach { CommentBubble(it) }
                                }
                                if (canComment) {
                                    CommentComposer(
                                        draft = commentDraft,
                                        sending = sending,
                                        errorText = errorText,
                                        onDraftChange = { commentDraft = it; errorText = null },
                                        onSend = {
                                            val text = commentDraft.trim()
                                            if (text.isEmpty()) {
                                                errorText = "Comment cannot be empty."
                                            } else {
                                                sending = true
                                                viewModel.addComment(
                                                    ticketId = ticket.ticket_id,
                                                    text = text,
                                                    onComplete = {
                                                        sending = false
                                                        commentDraft = ""
                                                        commentsEpoch++
                                                    },
                                                    onError = { msg ->
                                                        sending = false
                                                        errorText = msg
                                                    }
                                                )
                                            }
                                        }
                                    )
                                    Spacer(modifier = Modifier.height(8.dp))
                                }
                            }
                        }
                    } else {
                        Column(
                            modifier = Modifier
                                .weight(1f, fill = true)
                                .fillMaxWidth()
                                .verticalScroll(rememberScrollState()),
                            verticalArrangement = Arrangement.spacedBy(14.dp)
                        ) {
                            MetaGrid(
                                equipName = equipName,
                                equipId = ticket.equip_id,
                                pic = ticket.pic,
                                announcedBy = ticket.announced_by,
                                reportDate = ticket.report_date,
                                reportTime = ticket.report_time
                            )
                            SectionLabel("FAULT DESCRIPTION")
                            Text(
                                text = ticket.fault_desc ?: "No description provided.",
                                style = MaterialTheme.typography.bodyLarge,
                                lineHeight = 26.sp
                            )
                            SectionLabel("INTERVENTION TIMELINE")
                            if (loading) {
                                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
                            } else if (actions.isEmpty()) {
                                Text(
                                    "No actions logged yet.",
                                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                                    fontSize = 13.sp
                                )
                            } else {
                                actions.forEach { ActionTimelineCard(it) }
                            }
                            SectionLabel("💬 LIVE COMMENTS")
                            if (!loading && comments.isEmpty()) {
                                Text(
                                    "No comments yet.",
                                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                                    fontSize = 13.sp,
                                    fontStyle = FontStyle.Italic
                                )
                            } else {
                                comments.forEach { CommentBubble(it) }
                            }
                        }
                        if (canComment) {
                            Spacer(modifier = Modifier.height(10.dp))
                            CommentComposer(
                                draft = commentDraft,
                                sending = sending,
                                errorText = errorText,
                                onDraftChange = { commentDraft = it; errorText = null },
                                onSend = {
                                    val text = commentDraft.trim()
                                    if (text.isEmpty()) {
                                        errorText = "Comment cannot be empty."
                                    } else {
                                        sending = true
                                        viewModel.addComment(
                                            ticketId = ticket.ticket_id,
                                            text = text,
                                            onComplete = {
                                                sending = false
                                                commentDraft = ""
                                                commentsEpoch++
                                            },
                                            onError = { msg ->
                                                sending = false
                                                errorText = msg
                                            }
                                        )
                                    }
                                }
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun CommentComposer(
    draft: String,
    sending: Boolean,
    errorText: String?,
    onDraftChange: (String) -> Unit,
    onSend: () -> Unit
) {
    errorText?.let {
        Text(it, color = MaterialTheme.colorScheme.error, fontSize = 12.sp)
        Spacer(modifier = Modifier.height(4.dp))
    }
    Row(
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        OutlinedTextField(
            value = draft,
            onValueChange = onDraftChange,
            modifier = Modifier.weight(1f),
            singleLine = true,
            shape = RoundedCornerShape(14.dp),
            placeholder = { Text("Type a comment…") },
            enabled = !sending
        )
        FilledIconButton(
            onClick = onSend,
            enabled = !sending && draft.isNotBlank(),
            colors = IconButtonDefaults.filledIconButtonColors(containerColor = WccPrimary)
        ) {
            if (sending) {
                CircularProgressIndicator(
                    modifier = Modifier.size(18.dp),
                    strokeWidth = 2.dp,
                    color = Color.White
                )
            } else {
                Icon(Icons.Default.Send, contentDescription = "Send")
            }
        }
    }
}

@Composable
private fun SectionLabel(text: String) {
    Text(
        text = text,
        style = MaterialTheme.typography.labelSmall,
        fontWeight = FontWeight.Bold,
        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
    )
}

@Composable
private fun StatusChip(status: String) {
    Surface(
        color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f),
        shape = RoundedCornerShape(12.dp)
    ) {
        Text(
            text = status,
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
            style = MaterialTheme.typography.labelMedium,
            fontWeight = FontWeight.Bold
        )
    }
}

@Composable
private fun PriorityChip(priority: String) {
    Surface(
        color = MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.45f),
        shape = RoundedCornerShape(12.dp)
    ) {
        Text(
            text = priority,
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
            style = MaterialTheme.typography.labelMedium,
            fontWeight = FontWeight.Bold
        )
    }
}

@Composable
private fun MetaGrid(
    equipName: String?,
    equipId: Int,
    pic: String?,
    announcedBy: String?,
    reportDate: String?,
    reportTime: String?
) {
    Surface(
        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f),
        shape = RoundedCornerShape(16.dp)
    ) {
        Column(
            modifier = Modifier.padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            MetaRow("Equipment", equipName ?: "EQ-$equipId")
            MetaRow("PIC", pic?.takeIf { it.isNotBlank() } ?: "Unassigned")
            MetaRow("Announced by", announcedBy ?: "—")
            MetaRow(
                "Reported",
                listOfNotNull(reportDate, reportTime).joinToString(" ").ifBlank { "—" }
            )
        }
    }
}

@Composable
private fun MetaRow(label: String, value: String) {
    Row(modifier = Modifier.fillMaxWidth()) {
        Text(
            text = label,
            modifier = Modifier.width(110.dp),
            fontSize = 12.sp,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
            fontWeight = FontWeight.SemiBold
        )
        Text(
            text = value,
            fontSize = 13.sp,
            fontWeight = FontWeight.Medium,
            color = MaterialTheme.colorScheme.onSurface
        )
    }
}

@Composable
private fun CommentBubble(comment: TicketCommentDto) {
    Surface(
        color = MaterialTheme.colorScheme.surface,
        shape = RoundedCornerShape(12.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.25f))
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(
                    text = comment.user_name ?: "Unknown",
                    fontWeight = FontWeight.Bold,
                    fontSize = 13.sp
                )
                Text(
                    text = comment.created_at?.take(16)?.replace('T', ' ') ?: "",
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                )
            }
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = comment.comment_text ?: "",
                fontSize = 14.sp,
                lineHeight = 20.sp
            )
        }
    }
}

@Composable
fun HorizontalDivider(modifier: Modifier = Modifier, alpha: Float = 0.1f) {
    Box(
        modifier = modifier
            .fillMaxWidth()
            .height(1.dp)
            .background(MaterialTheme.colorScheme.outline.copy(alpha = alpha))
    )
}
