package com.example.wcc_companion_app.data.local.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import com.example.wcc_companion_app.data.local.entity.PendingTicketOpEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface PendingOpDao {
    @Query(
        """
        SELECT COUNT(*) FROM pending_ticket_op
        WHERE status IN ('PENDING','FAILED')
        """
    )
    fun observeUnsyncedCount(): Flow<Int>

    @Query(
        """
        SELECT COUNT(*) FROM pending_ticket_op
        WHERE status = 'CONFLICT'
        """
    )
    fun observeConflictOpCount(): Flow<Int>

    @Query(
        """
        SELECT * FROM pending_ticket_op
        WHERE status IN ('PENDING','FAILED')
        ORDER BY createdAt ASC
        """
    )
    suspend fun getPendingOrdered(): List<PendingTicketOpEntity>

    @Query(
        """
        SELECT * FROM pending_ticket_op
        WHERE status IN ('PENDING','FAILED','CONFLICT')
        ORDER BY createdAt ASC
        """
    )
    suspend fun getAllOpenOrdered(): List<PendingTicketOpEntity>

    @Query(
        """
        SELECT * FROM pending_ticket_op
        WHERE status IN ('PENDING','FAILED','CONFLICT','IN_FLIGHT')
        ORDER BY createdAt ASC
        """
    )
    fun observeOpenOps(): Flow<List<PendingTicketOpEntity>>

    @Query(
        """
        SELECT COUNT(*) FROM pending_ticket_op
        WHERE status = 'FAILED'
        """
    )
    fun observeFailedCount(): Flow<Int>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(op: PendingTicketOpEntity)

    @Query("UPDATE pending_ticket_op SET status = :status, lastError = :error, retryCount = retryCount + :retryInc WHERE opId = :opId")
    suspend fun updateStatus(opId: String, status: String, error: String? = null, retryInc: Int = 0)

    @Query("DELETE FROM pending_ticket_op WHERE opId = :opId")
    suspend fun delete(opId: String)

    @Query("DELETE FROM pending_ticket_op")
    suspend fun clearAll()
}
