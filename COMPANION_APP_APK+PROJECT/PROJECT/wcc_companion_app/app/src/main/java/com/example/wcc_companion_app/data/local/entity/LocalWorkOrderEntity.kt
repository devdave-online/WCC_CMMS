package com.example.wcc_companion_app.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto

@Entity(tableName = "local_work_order")
data class LocalWorkOrderEntity(
    @PrimaryKey val woId: Int,
    val title: String,
    val description: String?,
    val equipmentId: Int?,
    val equipName: String?,
    val assignedTo: Int?,
    val status: String?,
    val scheduledDate: String?,
    val completedDate: String?,
    val completedBy: Int?,
    val startedAt: String?,
    val partsList: String?,
    val checklistData: String?,
    /** CLEAN | DIRTY | SYNCING | CONFLICT */
    val syncState: String = "CLEAN",
    /** Server status when last CLEAN pull applied — conflict base. */
    val baseServerStatus: String? = null,
    val conflictMessage: String? = null,
    val updatedLocallyAt: Long = System.currentTimeMillis(),
    val lastServerAt: Long? = null,
) {
    fun toDto(): WorkOrderDto = WorkOrderDto(
        wo_id = woId,
        title = title,
        description = description,
        equipment_id = equipmentId,
        equip_name = equipName,
        assigned_to = assignedTo,
        status = status,
        scheduled_date = scheduledDate,
        completed_date = completedDate,
        completed_by = completedBy,
        started_at = startedAt,
        parts_list = partsList,
        checklist_data = checklistData,
    )

    companion object {
        fun fromDto(
            dto: WorkOrderDto,
            syncState: String = "CLEAN",
            lastServerAt: Long? = System.currentTimeMillis(),
        ) = LocalWorkOrderEntity(
            woId = dto.wo_id,
            title = dto.title,
            description = dto.description,
            equipmentId = dto.equipment_id,
            equipName = dto.equip_name,
            assignedTo = dto.assigned_to,
            status = dto.status,
            scheduledDate = dto.scheduled_date,
            completedDate = dto.completed_date,
            completedBy = dto.completed_by,
            startedAt = dto.started_at,
            partsList = dto.parts_list,
            checklistData = dto.checklist_data,
            syncState = syncState,
            baseServerStatus = dto.status,
            lastServerAt = lastServerAt,
        )
    }
}
