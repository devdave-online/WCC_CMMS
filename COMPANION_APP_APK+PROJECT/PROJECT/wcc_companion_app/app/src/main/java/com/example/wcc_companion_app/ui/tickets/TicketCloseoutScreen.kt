package com.example.wcc_companion_app.ui.tickets

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.systemBars
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.windowInsetsPadding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.wcc_companion_app.data.remote.models.CloseoutRequestDto
import com.example.wcc_companion_app.data.remote.models.TicketActionDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.formatPartsUsedField
import com.example.wcc_companion_app.ui.theme.WccPrimary

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TicketCloseoutScreen(
    ticket: TicketDto,
    onDismiss: () -> Unit,
    onComplete: () -> Unit,
    viewModel: TicketViewModel = hiltViewModel()
) {
    com.example.wcc_companion_app.ui.components.KeepScreenOn()
    var actions by remember { mutableStateOf<List<TicketActionDto>>(emptyList()) }
    var isLoading by remember { mutableStateOf(true) }
    var isSubmitting by remember { mutableStateOf(false) }
    var errorText by remember { mutableStateOf<String?>(null) }
    com.example.wcc_companion_app.ui.components.HapticOnError(errorText)

    LaunchedEffect(ticket.ticket_id) {
        actions = viewModel.fetchTicketActions(ticket.ticket_id)
        isLoading = false
    }

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Surface(
            modifier = Modifier
                .fillMaxWidth(0.9f)
                .fillMaxHeight(0.95f)
                .windowInsetsPadding(WindowInsets.systemBars)
                .padding(16.dp)
                .imePadding(),
            shape = RoundedCornerShape(24.dp),
            color = MaterialTheme.colorScheme.surface.copy(alpha = 0.95f),
            tonalElevation = 8.dp
        ) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(24.dp)
            ) {
                Text(
                    text = "Final Review & Close",
                    style = MaterialTheme.typography.headlineMedium,
                    fontWeight = FontWeight.Bold,
                    color = WccPrimary
                )
                
                Spacer(modifier = Modifier.height(12.dp))

                Surface(
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp).fillMaxWidth()) {
                        Text(
                            text = "Ticket ID: ${ticket.ticket_id} | Equip: ${ticket.equip_id}",
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            text = "Original Issue: ${ticket.fault_desc}",
                            color = MaterialTheme.colorScheme.error,
                            fontWeight = FontWeight.SemiBold,
                            modifier = Modifier.padding(top = 4.dp)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))
                
                Text(
                    text = "Intervention Timeline:",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary
                )

                Spacer(modifier = Modifier.height(12.dp))

                if (isLoading) {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.CenterHorizontally))
                } else if (actions.isEmpty()) {
                    Text("No actions logged.", color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f))
                } else {
                    LazyColumn(
                        modifier = Modifier
                            .weight(1f)
                            .fillMaxWidth(),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        items(actions) { action ->
                            ActionTimelineCard(action)
                        }
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))

                errorText?.let {
                    Text(
                        it,
                        color = MaterialTheme.colorScheme.error,
                        fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.padding(bottom = 8.dp)
                    )
                }

                com.example.wcc_companion_app.ui.components.WccStickyActionBar {

                    com.example.wcc_companion_app.ui.components.WccPrimaryButton(
                        label = "Confirm & Archive Ticket",
                        onClick = {
                            isSubmitting = true
                            errorText = null
                            viewModel.submitCloseout(
                                CloseoutRequestDto(
                                    ticket_id = ticket.ticket_id,
                                    supervisor = "" // ViewModel fills this
                                ),
                                onComplete = {
                                    isSubmitting = false
                                    onComplete()
                                },
                                onError = { msg ->
                                    isSubmitting = false
                                    errorText = msg
                                }
                            )
                        },
                        enabled = !isSubmitting && !isLoading,
                        loading = isSubmitting,
                        height = 60.dp,
                        modifier = Modifier.weight(1f)
                    )
                }
            }
        }
    }
}

@Composable
fun ActionTimelineCard(action: TicketActionDto) {
    val isEscalated = !action.escalated_to.isNullOrBlank() && action.escalated_to != "None"
    val borderColor = if (isEscalated) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.primary
    // API sometimes returns null ends (zero-minute hold rows, partial data).
    val startLabel = action.action_start?.take(16)?.replace('T', ' ') ?: "—"
    val endLabel = action.action_end?.let { e ->
        if (e.length >= 8) e.takeLast(8).take(5) else e
    } ?: "—"

    Surface(
        color = MaterialTheme.colorScheme.surface,
        shape = RoundedCornerShape(12.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
    ) {
        Column(modifier = Modifier.fillMaxWidth()) {
            // Header with color strip
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(borderColor.copy(alpha = 0.1f))
                    .border(BorderStroke(1.dp, borderColor.copy(alpha = 0.2f)))
                    .padding(12.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "👨‍🔧 ${action.tech_name ?: "Unknown"}",
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Surface(
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.1f),
                    shape = RoundedCornerShape(4.dp)
                ) {
                    Text(
                        text = "⏱️ $startLabel - $endLabel",
                        fontSize = 12.sp,
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                    )
                }
            }

            // Body
            Column(modifier = Modifier.padding(12.dp)) {
                Text(
                    text = "Action Taken: ${action.action_taken ?: "—"}",
                    style = MaterialTheme.typography.bodyMedium
                )

                val partsUsedLabel = formatPartsUsedField(action.parts_used)
                if (partsUsedLabel != "—") {
                    Spacer(modifier = Modifier.height(8.dp))
                    Surface(
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.05f),
                        shape = RoundedCornerShape(4.dp)
                    ) {
                        Text(
                            text = "📦 Parts Used: $partsUsedLabel",
                            fontSize = 12.sp,
                            modifier = Modifier.padding(horizontal = 6.dp, vertical = 4.dp),
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f)
                        )
                    }
                }

                if (isEscalated) {
                    Spacer(modifier = Modifier.height(8.dp))
                    Surface(
                        color = MaterialTheme.colorScheme.errorContainer,
                        shape = RoundedCornerShape(4.dp),
                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.error)
                    ) {
                        Text(
                            text = "⚠️ Escalated to: ${action.escalated_to}",
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onErrorContainer,
                            modifier = Modifier.padding(horizontal = 6.dp, vertical = 4.dp)
                        )
                    }
                }
            }
        }
    }
}
