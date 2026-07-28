package com.example.wcc_companion_app.data.local.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import com.example.wcc_companion_app.data.local.entity.PendingMediaEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface PendingMediaDao {
    @Query(
        """
        SELECT COUNT(*) FROM pending_media
        WHERE status IN ('PENDING','FAILED')
        """
    )
    fun observePendingCount(): Flow<Int>

    @Query(
        """
        SELECT * FROM pending_media
        WHERE status IN ('PENDING','FAILED')
        ORDER BY createdAt ASC
        """
    )
    suspend fun getPendingOrdered(): List<PendingMediaEntity>

    @Query(
        """
        SELECT * FROM pending_media
        WHERE status IN ('PENDING','FAILED','IN_FLIGHT')
        ORDER BY createdAt ASC
        """
    )
    fun observeOpenMedia(): Flow<List<PendingMediaEntity>>

    @Query(
        """
        SELECT COUNT(*) FROM pending_media
        WHERE status = 'FAILED'
        """
    )
    fun observeFailedCount(): Flow<Int>

    @Query(
        """
        SELECT * FROM pending_media
        WHERE parentKey = :parentKey
        ORDER BY createdAt ASC
        """
    )
    fun observeForParent(parentKey: String): Flow<List<PendingMediaEntity>>

    @Query(
        """
        SELECT * FROM pending_media
        WHERE parentKey = :parentKey
        ORDER BY createdAt ASC
        """
    )
    suspend fun getForParent(parentKey: String): List<PendingMediaEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(row: PendingMediaEntity)

    @Query(
        """
        UPDATE pending_media
        SET status = :status, lastError = :error, retryCount = retryCount + :retryInc,
            remoteUrl = COALESCE(:remoteUrl, remoteUrl)
        WHERE mediaId = :mediaId
        """
    )
    suspend fun updateStatus(
        mediaId: String,
        status: String,
        error: String? = null,
        retryInc: Int = 0,
        remoteUrl: String? = null,
    )

    @Query("DELETE FROM pending_media WHERE mediaId = :mediaId")
    suspend fun delete(mediaId: String)

    @Query("DELETE FROM pending_media")
    suspend fun clearAll()
}
