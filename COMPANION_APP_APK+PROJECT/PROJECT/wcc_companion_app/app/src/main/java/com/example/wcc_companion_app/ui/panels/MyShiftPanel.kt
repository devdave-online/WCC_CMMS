package com.example.wcc_companion_app.ui.panels

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.ui.profile.ProfileViewModel
import com.example.wcc_companion_app.ui.theme.TicketStatusEscalated
import com.example.wcc_companion_app.ui.theme.TicketStatusHold
import com.example.wcc_companion_app.ui.theme.TicketStatusOpen
import com.example.wcc_companion_app.ui.theme.TicketStatusPending

/**
 * Rail-START: "what am I on right now".
 *
 * Identity comes from the **same** [ProfileViewModel] / `GET me` payload as Profile
 * (name, username, user_id) — refreshed every time this panel opens.
 *
 * - MY ACTIVE TICKETS: live statuses where pic matches username OR full name.
 * - MY WORK ORDERS: Scheduled / In Progress / Missed with assigned_to == my user_id only.
 * - OPEN FLOOR: all live tickets on the board (same pool as the Tickets rail).
 */
@Composable
fun MyShiftPanel(
    isDark: Boolean,
    tickets: List<TicketDto>,
    workOrders: List<WorkOrderDto>,
    onOpenTicket: (TicketDto) -> Unit,
    onOpenWorkOrder: (WorkOrderDto) -> Unit = {},
    onClose: () -> Unit,
    profileViewModel: ProfileViewModel = hiltViewModel()
) {
    // Same request stack as Profile: GET /me (+ achievements if Profile loads them).
    val profileData by profileViewModel.userProfile.collectAsState()
    LaunchedEffect(Unit) {
        profileViewModel.refresh()
    }

    // Field keys mirror UserProfileView (name / full_name / username / user_id).
    val userName = profileData?.get("username")?.toString()?.takeIf { it.isNotBlank() }
    val fullName = profileData?.get("name")?.toString()?.takeIf { it.isNotBlank() }
        ?: profileData?.get("full_name")?.toString()?.takeIf { it.isNotBlank() }
    val userId = (profileData?.get("user_id") as? Number)?.toInt()
        ?: (profileData?.get("id") as? Number)?.toInt()

    // PIC may be username or full display name depending on which path wrote the row.
    val identities = remember(userName, fullName) {
        listOfNotNull(userName, fullName)
            .map { it.trim().lowercase() }
            .filter { it.isNotEmpty() }
            .toSet()
    }
    val displayName = fullName?.takeIf { it.isNotBlank() }
        ?: userName?.takeIf { it.isNotBlank() }

    val liveStatuses = setOf("OPEN", "PENDING", "ESCALATED", "HOLD")
    val myTickets = remember(tickets, identities) {
        tickets.filter { t ->
            val st = t.status?.uppercase() ?: "OPEN"
            if (st !in liveStatuses) return@filter false
            val pic = t.pic?.trim()?.lowercase() ?: return@filter false
            pic in identities
        }.sortedWith(
            compareBy(
                { t ->
                    when (t.status?.uppercase()) {
                        "ESCALATED" -> 0
                        "OPEN" -> 1
                        "PENDING" -> 2
                        "HOLD" -> 3
                        else -> 4
                    }
                },
                { t ->
                    when (t.priority?.uppercase()) {
                        "CRITICAL", "HIGH" -> 0
                        "MEDIUM" -> 1
                        "LOW" -> 2
                        else -> 3
                    }
                }
            )
        )
    }

    // tickets list is already only live statuses from TicketViewModel — floor count = size.
    val openTickets = remember(tickets) {
        tickets.filter {
            val st = it.status?.uppercase() ?: "OPEN"
            st in liveStatuses
        }
    }
    val escalated = remember(tickets) {
        tickets.count { it.status.equals("ESCALATED", ignoreCase = true) }
    }
    val onHold = remember(tickets) {
        tickets.count { it.status.equals("HOLD", ignoreCase = true) }
    }

    // Strict assignment: never show someone else's WO when we know userId.
    val myWos = remember(workOrders, userId) {
        if (userId == null) emptyList()
        else workOrders.filter { wo ->
            val st = wo.status ?: ""
            val active = st.equals("In Progress", true) ||
                st.equals("Missed", true) ||
                st.equals("Scheduled", true)
            active && wo.assigned_to == userId
        }.sortedBy { it.scheduled_date }
    }

    PanelScaffold(
        isDark = isDark,
        title = "My Shift",
        subtitle = displayName?.let { "Signed in as $it" } ?: "Not signed in",
        edge = PanelEdge.START,
        onClose = onClose
    ) {
        // ── AT A GLANCE ──
        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            StatTile(isDark, myTickets.size.toString(), "MY TICKETS", Modifier.weight(1f))
            StatTile(isDark, openTickets.size.toString(), "OPEN FLOOR", Modifier.weight(1f))
            StatTile(isDark, escalated.toString(), "ESCALATED", Modifier.weight(1f))
            StatTile(isDark, onHold.toString(), "ON HOLD", Modifier.weight(1f))
        }

        PanelCard(isDark, "MY ACTIVE TICKETS (${myTickets.size})") {
            if (myTickets.isEmpty()) {
                PanelEmpty(
                    if (identities.isEmpty()) "Not signed in — cannot match PIC."
                    else "Nothing with you as PIC right now."
                )
            } else {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    myTickets.forEach { ticket ->
                        TicketRow(isDark, ticket) { onOpenTicket(ticket) }
                    }
                }
            }
        }

        PanelCard(isDark, "MY WORK ORDERS (${myWos.size})") {
            if (myWos.isEmpty()) {
                PanelEmpty(
                    if (userId == null) "Profile not loaded — cannot match assigned work orders."
                    else "No work orders assigned to you."
                )
            } else {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    myWos.forEach { wo ->
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable(
                                    indication = null,
                                    interactionSource = remember { MutableInteractionSource() }
                                ) { onOpenWorkOrder(wo) }
                        ) {
                            Text(
                                text = "WO-${wo.wo_id} · ${wo.title}",
                                fontWeight = FontWeight.Bold,
                                fontSize = 14.sp,
                                maxLines = 2,
                                overflow = TextOverflow.Ellipsis,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                            Text(
                                text = listOfNotNull(
                                    wo.status,
                                    wo.equip_name,
                                    wo.scheduled_date
                                ).joinToString(" · "),
                                fontSize = 12.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun StatTile(isDark: Boolean, value: String, label: String, modifier: Modifier = Modifier) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(14.dp),
        color = if (isDark) androidx.compose.ui.graphics.Color.Black.copy(alpha = 0.30f)
        else androidx.compose.ui.graphics.Color.White.copy(alpha = 0.45f),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.18f))
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(vertical = 12.dp, horizontal = 4.dp)
        ) {
            Text(
                value,
                fontWeight = FontWeight.Black,
                fontSize = 18.sp,
                color = MaterialTheme.colorScheme.primary
            )
            Text(
                label,
                fontSize = 9.sp,
                fontWeight = FontWeight.Bold,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
            )
        }
    }
}

@Composable
private fun TicketRow(isDark: Boolean, ticket: TicketDto, onClick: () -> Unit) {
    val st = ticket.status?.uppercase() ?: "OPEN"
    val color = when (st) {
        "ESCALATED" -> TicketStatusEscalated
        "HOLD" -> TicketStatusHold
        "PENDING" -> TicketStatusPending
        else -> TicketStatusOpen
    }
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(
                indication = null,
                interactionSource = remember { MutableInteractionSource() },
                onClick = onClick
            ),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                ticket.ticket_id,
                fontWeight = FontWeight.Bold,
                fontSize = 14.sp,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            Text(
                ticket.fault_desc ?: "",
                fontSize = 12.sp,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
            )
        }
        Surface(
            color = color.copy(alpha = 0.15f),
            shape = RoundedCornerShape(8.dp)
        ) {
            Text(
                st,
                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                fontSize = 10.sp,
                fontWeight = FontWeight.Black,
                color = color
            )
        }
    }
}
