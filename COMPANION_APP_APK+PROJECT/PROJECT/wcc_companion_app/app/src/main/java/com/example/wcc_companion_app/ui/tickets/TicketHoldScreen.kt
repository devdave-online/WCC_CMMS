package com.example.wcc_companion_app.ui.tickets

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.systemBars
import androidx.compose.foundation.layout.windowInsetsPadding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.wcc_companion_app.data.remote.models.HoldRequestDto
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.ui.theme.WccPrimary

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TicketHoldScreen(
    ticket: TicketDto,
    onDismiss: () -> Unit,
    onComplete: () -> Unit,
    viewModel: TicketViewModel = hiltViewModel()
) {
    com.example.wcc_companion_app.ui.components.KeepScreenOn()
    var reasonExpanded by remember { mutableStateOf(false) }
    val reasons = listOf(
        "Waiting for Parts",
        "Waiting for External Vendor",
        "Waiting for Production Clearance",
        "End of Shift / Handover",
        "Other"
    )
    var reason by remember { mutableStateOf(reasons[0]) }
    var explanation by remember { mutableStateOf("") }
    var isSubmitting by remember { mutableStateOf(false) }
    var errorText by remember { mutableStateOf<String?>(null) }

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
                    .verticalScroll(rememberScrollState())
            ) {
                Text(
                    text = "Put Ticket on Hold",
                    style = MaterialTheme.typography.headlineMedium,
                    fontWeight = FontWeight.Bold,
                    color = WccPrimary
                )
                Text(
                    text = "Ticket: ${ticket.ticket_id}",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f)
                )
                
                Spacer(modifier = Modifier.height(16.dp))

                ExposedDropdownMenuBox(
                    expanded = reasonExpanded,
                    onExpandedChange = { reasonExpanded = it }
                ) {
                    OutlinedTextField(
                        value = reason,
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("Reason for Hold") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = reasonExpanded) },
                        colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(),
                        modifier = Modifier.fillMaxWidth().menuAnchor()
                    )
                    ExposedDropdownMenu(
                        expanded = reasonExpanded,
                        onDismissRequest = { reasonExpanded = false }
                    ) {
                        reasons.forEach { selectionOption ->
                            DropdownMenuItem(
                                text = { Text(selectionOption) },
                                onClick = {
                                    reason = selectionOption
                                    reasonExpanded = false
                                }
                            )
                        }
                    }
                }
                
                Spacer(modifier = Modifier.height(12.dp))

                OutlinedTextField(
                    value = explanation,
                    onValueChange = { explanation = it; errorText = null },
                    label = { Text("Explanation / Comments") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 4
                )

                errorText?.let {
                    Spacer(modifier = Modifier.height(12.dp))
                    Text(it, color = MaterialTheme.colorScheme.error, fontWeight = FontWeight.SemiBold)
                }

                Spacer(modifier = Modifier.height(24.dp))

                Button(
                    onClick = {
                        if (explanation.isBlank()) {
                            errorText = "Explanation is required."
                            return@Button
                        }
                        isSubmitting = true
                        errorText = null
                        viewModel.submitHold(
                            HoldRequestDto(
                                ticket_id = ticket.ticket_id,
                                reason = reason,
                                explanation = explanation
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
                    enabled = !isSubmitting,
                    modifier = Modifier.fillMaxWidth().height(64.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = WccPrimary)
                ) {
                    if (isSubmitting) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(22.dp),
                            strokeWidth = 2.dp,
                            color = MaterialTheme.colorScheme.onPrimary
                        )
                    } else {
                        Text("Confirm HOLD Status", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}
