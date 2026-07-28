package com.example.wcc_companion_app.data.remote.models

data class WorkOrderDto(
    val wo_id: Int,
    val title: String = "",
    val description: String? = null,
    val equipment_id: Int? = null,
    val equip_name: String? = null,
    val assigned_to: Int? = null,
    val status: String? = null,
    val scheduled_date: String? = null,
    val completed_date: String? = null,
    val completed_by: Int? = null,
    val started_at: String? = null,
    val parts_list: String? = null,
    val checklist_data: String? = null
)

/** Companion WO action envelope */
data class CompanionWoRequestDto(
    val action: String,
    val wo_id: Int,
    val notes: String? = null,
    val parts_consumed: List<PartConsumptionDto>? = null,
    val checklist_data: String? = null
)

data class CompanionWoResponseDto(
    val status: String,
    val message: String? = null,
    val data: WorkOrderDto? = null
)
