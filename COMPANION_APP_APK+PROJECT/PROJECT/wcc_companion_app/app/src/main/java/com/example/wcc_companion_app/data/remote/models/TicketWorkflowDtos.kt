package com.example.wcc_companion_app.data.remote.models

data class TakeoverRequestDto(
    val ticket_id: String,
    val tech_name: String,
    val action_start: String,
    val action_end: String,
    val fault_type: String,
    val root_cause: String,
    val action_taken: String,
    val parts_used: String,
    val escalated_to: String,
    val action_type: String, // 'finish' or 'escalate'
    val parts_consumed_data: List<PartConsumptionDto>
)

data class PartConsumptionDto(
    val part_id: Int,
    val qty: Int
)

data class CloseoutRequestDto(
    val ticket_id: String,
    val supervisor: String
)

data class HoldRequestDto(
    val ticket_id: String,
    val reason: String,
    val explanation: String
)

data class TeamMemberDto(
    val full_name: String
)

data class LegacyTeamResponseDto(
    val status: String,
    val data: List<TeamMemberDto>
)

data class TicketActionDto(
    val action_id: Int? = null,
    val ticket_id: String? = null,
    val tech_name: String? = null,
    val action_start: String? = null,
    val action_end: String? = null,
    val fault_type: String? = null,
    val root_cause: String? = null,
    val action_taken: String? = null,
    val parts_used: String? = null,
    val escalated_to: String? = null
)

data class InventoryPartDto(
    val part_id: Int,
    val part_name: String,
    val internal_code: String = "",
    val stock_level: Int = 0,
    val minimum_threshold: Int? = null,
    val maximum_stock: Int? = null,
    val lifecycle_status: String? = null,
    val part_condition: String? = null,
    val uom: String? = null,
    val oem_name: String? = null,
    val oem_part_number: String? = null,
    val aisle: String? = null,
    val rack: String? = null,
    val shelf: String? = null,
    val bin_code: String? = null,
    val primary_vendor_id: Int? = null,
    val auto_reorder: Int? = null,
    val cost_per_unit: String? = null,
    val standardized_desc: String? = null
)

/** JSON comment row from /api/companion/ticket_comments.php */
data class TicketCommentDto(
    val comment_id: Int? = null,
    val ticket_id: String? = null,
    val user_name: String? = null,
    val comment_text: String? = null,
    val created_at: String? = null
)

data class CompanionListResponseDto<T>(
    val status: String,
    val data: List<T>? = null,
    val message: String? = null
)

data class CompanionStatusResponseDto(
    val status: String,
    val message: String? = null
)

data class AddCommentRequestDto(
    val ticket_id: String,
    val comment_text: String
)

/** Response from /api/companion/evidence_upload.php */
data class EvidenceUploadResponseDto(
    val status: String,
    val message: String? = null,
    val url: String? = null,
    val path: String? = null,
)

/** Universal scan hit from /api/companion/scan_lookup.php */
data class ScanLookupHitDto(
    val kind: String? = null,
    val data: Map<String, Any?>? = null
)

/**
 * Scan lookup payload.
 * - hits: equipment / part / tooling matches for the raw code
 * - open_tickets / open_work_orders: live floor work for matched equipment (DB-backed)
 */
data class ScanLookupDataDto(
    val code: String? = null,
    val kind: String? = null,
    val hits: List<ScanLookupHitDto>? = null,
    val count: Int? = null,
    val open_tickets: List<TicketDto>? = null,
    val open_work_orders: List<WorkOrderDto>? = null,
    val open_ticket_count: Int? = null,
    val open_wo_count: Int? = null
)

data class ScanLookupResponseDto(
    val status: String,
    val data: ScanLookupDataDto? = null,
    val message: String? = null
)

data class ToolingDto(
    val tooling_id: Int? = null,
    val tooling_name: String? = null,
    val tooling_code: String? = null,
    val barcode: String? = null,
    val asset_tag: String? = null,
    val category: String? = null,
    val status: String? = null,
    val location: String? = null
)

/** Ladder tier from /api/companion/profile_achievements.php */
data class GamifiedTierDto(
    val min: Int = 0,
    val tier: String = "",
    val icon: String = "",
    val color: String = "#94a3b8",
    val blurb: String = ""
)

data class ProficiencyNextDto(
    val tier: String? = null,
    val tier_icon: String? = null,
    val tier_color: String? = null,
    val min: Int? = null,
    val remaining_hours: Double? = null
)

/** Earned machine proficiency (both icons required for UI). */
data class ProficiencyDto(
    val category: String = "",
    val skill_name: String = "",
    val category_icon: String = "",
    val hours: Double = 0.0,
    val tier: String = "Novice",
    val tier_icon: String = "🌱",
    val tier_color: String = "#94a3b8",
    val tier_blurb: String = "",
    val tier_min: Int = 0,
    val next: ProficiencyNextDto? = null,
    val progress_01: Double = 0.0
)

data class ManualSkillDto(
    val name: String = "",
    val expiry_date: String? = null,
    val state: String = "none",
    val label: String = "",
    val color: String = "#94a3b8",
    val icon: String = "",
    val days: Int? = null
)

data class AchievementsSummaryDto(
    val proficiency_count: Int = 0,
    val master_count: Int = 0,
    val expert_count: Int = 0,
    val cert_count: Int = 0,
    val certs_expiring: Int = 0,
    val certs_expired: Int = 0
)

/** Career totals across full DB history (demo seed excluded from wrench/actions). */
data class LifetimeStatsDto(
    val tickets_worked: Int = 0,
    val total_wrench_minutes: Int = 0,
    val total_wrench_label: String = "0m",
    val tickets_closed: Int = 0,
    val work_orders_completed: Int = 0
)

data class AchievementsDataDto(
    val ladder: List<GamifiedTierDto> = emptyList(),
    val proficiencies: List<ProficiencyDto> = emptyList(),
    val manual_skills: List<ManualSkillDto> = emptyList(),
    val summary: AchievementsSummaryDto? = null,
    val lifetime: LifetimeStatsDto? = null
)

data class AchievementsResponseDto(
    val status: String,
    val data: AchievementsDataDto? = null,
    val message: String? = null
)
