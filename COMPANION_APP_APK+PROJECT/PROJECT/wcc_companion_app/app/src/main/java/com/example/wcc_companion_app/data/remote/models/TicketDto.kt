package com.example.wcc_companion_app.data.remote.models

data class TicketDto(
    val ticket_id: String,
    val equip_id: Int,
    val report_date: String?,
    val report_time: String?,
    val announced_by: String?,
    val pic: String?,
    val fault_desc: String?,
    val priority: String?,
    val status: String?,
    val created_at: String?,
    /** When the event was closed — use for History sort (not created_at). */
    val closed_at: String? = null,
    val closed_by: String? = null
)
