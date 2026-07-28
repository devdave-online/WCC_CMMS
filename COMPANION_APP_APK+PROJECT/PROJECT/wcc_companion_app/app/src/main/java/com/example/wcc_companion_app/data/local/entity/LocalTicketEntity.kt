package com.example.wcc_companion_app.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import com.example.wcc_companion_app.data.remote.models.TicketDto

@Entity(tableName = "local_ticket")
data class LocalTicketEntity(
    @PrimaryKey val ticketId: String,
    val equipId: Int,
    val reportDate: String?,
    val reportTime: String?,
    val announcedBy: String?,
    val pic: String?,
    val faultDesc: String?,
    val priority: String?,
    val status: String?,
    val createdAt: String?,
    val closedAt: String? = null,
    val closedBy: String? = null,
    /** CLEAN | DIRTY | SYNCING | CONFLICT */
    val syncState: String = "CLEAN",
    /** Server status at last successful CLEAN merge — multi-tech conflict base. */
    val baseServerStatus: String? = null,
    val conflictMessage: String? = null,
    val updatedLocallyAt: Long = System.currentTimeMillis(),
    val lastServerAt: Long? = null
) {
    fun toDto(): TicketDto = TicketDto(
        ticket_id = ticketId,
        equip_id = equipId,
        report_date = reportDate,
        report_time = reportTime,
        announced_by = announcedBy,
        pic = pic,
        fault_desc = faultDesc,
        priority = priority,
        status = status,
        created_at = createdAt,
        closed_at = closedAt,
        closed_by = closedBy
    )

    companion object {
        fun fromDto(
            dto: TicketDto,
            syncState: String = "CLEAN",
            lastServerAt: Long? = System.currentTimeMillis()
        ) = LocalTicketEntity(
            ticketId = dto.ticket_id,
            equipId = dto.equip_id,
            reportDate = dto.report_date,
            reportTime = dto.report_time,
            announcedBy = dto.announced_by,
            pic = dto.pic,
            faultDesc = dto.fault_desc,
            priority = dto.priority,
            status = dto.status,
            createdAt = dto.created_at,
            closedAt = dto.closed_at,
            closedBy = dto.closed_by,
            syncState = syncState,
            baseServerStatus = dto.status,
            updatedLocallyAt = System.currentTimeMillis(),
            lastServerAt = lastServerAt
        )
    }
}
