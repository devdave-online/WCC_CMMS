package com.example.wcc_companion_app.data.local.entity

import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

@Entity(
    tableName = "pending_ticket_op",
    indices = [Index("status"), Index("ticketId"), Index("createdAt")]
)
data class PendingTicketOpEntity(
    @PrimaryKey val opId: String,
    /** ticket_id, or "wo:{id}" for work-order ops */
    val ticketId: String,
    /**
     * COMMENT | TAKEOVER | HOLD | CLOSEOUT | RESUME
     * WO_START | WO_COMPLETE
     */
    val type: String,
    val payloadJson: String,
    val createdAt: Long = System.currentTimeMillis(),
    val retryCount: Int = 0,
    val lastError: String? = null,
    /** PENDING | IN_FLIGHT | FAILED | CONFLICT */
    val status: String = "PENDING",
    /** Server status snapshot expected when op was enqueued (conflict detect). */
    val expectedBaseStatus: String? = null,
)
