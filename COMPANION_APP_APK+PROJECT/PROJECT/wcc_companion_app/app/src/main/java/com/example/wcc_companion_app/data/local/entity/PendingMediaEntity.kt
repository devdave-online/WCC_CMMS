package com.example.wcc_companion_app.data.local.entity

import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

/**
 * Local photo evidence waiting for plant upload.
 * Survives process death; drained by [com.example.wcc_companion_app.data.sync.SyncCoordinator]
 * and WorkManager.
 */
@Entity(
    tableName = "pending_media",
    indices = [Index("status"), Index("parentKey"), Index("createdAt")]
)
data class PendingMediaEntity(
    @PrimaryKey val mediaId: String,
    /** TICKET or WO */
    val parentType: String,
    /** ticket_id or wo_id string */
    val parentId: String,
    /** Composite key: "TICKET:OT-1" / "WO:35" */
    val parentKey: String,
    val localPath: String,
    val mimeType: String = "image/jpeg",
    val caption: String? = null,
    val createdAt: Long = System.currentTimeMillis(),
    val retryCount: Int = 0,
    val lastError: String? = null,
    /** PENDING | IN_FLIGHT | FAILED | UPLOADED */
    val status: String = "PENDING",
    val remoteUrl: String? = null,
)
