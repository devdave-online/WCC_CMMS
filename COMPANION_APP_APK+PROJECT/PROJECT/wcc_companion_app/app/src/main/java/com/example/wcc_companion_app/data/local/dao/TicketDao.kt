package com.example.wcc_companion_app.data.local.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Transaction
import com.example.wcc_companion_app.data.local.entity.LocalTicketEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface TicketDao {
    @Query(
        """
        SELECT * FROM local_ticket
        WHERE UPPER(COALESCE(status,'')) IN ('OPEN','PENDING','ESCALATED','HOLD')
        ORDER BY COALESCE(createdAt, reportDate, '') DESC
        """
    )
    fun observeLiveTickets(): Flow<List<LocalTicketEntity>>

    /** Locally closed / pending-sync events for History — most recently closed first. */
    @Query(
        """
        SELECT * FROM local_ticket
        WHERE UPPER(COALESCE(status,'')) = 'CLOSED'
        ORDER BY COALESCE(closedAt, createdAt, '') DESC, updatedLocallyAt DESC
        LIMIT 40
        """
    )
    fun observeClosedTickets(): Flow<List<LocalTicketEntity>>

    @Query(
        """
        SELECT * FROM local_ticket
        WHERE UPPER(COALESCE(status,'')) = 'CLOSED'
        ORDER BY COALESCE(closedAt, createdAt, '') DESC, updatedLocallyAt DESC
        LIMIT 40
        """
    )
    suspend fun getClosedTickets(): List<LocalTicketEntity>

    @Query("SELECT * FROM local_ticket WHERE ticketId = :id LIMIT 1")
    suspend fun getById(id: String): LocalTicketEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(ticket: LocalTicketEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(tickets: List<LocalTicketEntity>)

    @Query("DELETE FROM local_ticket WHERE ticketId = :id")
    suspend fun delete(id: String)

    @Query(
        """
        DELETE FROM local_ticket
        WHERE syncState = 'CLEAN'
          AND UPPER(COALESCE(status,'')) NOT IN ('OPEN','PENDING','ESCALATED','HOLD')
        """
    )
    suspend fun purgeClosedClean()

    /** Replace live set from server without wiping DIRTY/SYNCING rows. */
    @Transaction
    suspend fun replaceLiveFromServer(serverTickets: List<LocalTicketEntity>) {
        val dirtyIds = getDirtyOrSyncingIds().toSet()
        val toUpsert = serverTickets.filter { it.ticketId !in dirtyIds }
        if (toUpsert.isNotEmpty()) upsertAll(toUpsert)
        // Remove CLEAN tickets no longer on server live board
        val serverIds = serverTickets.map { it.ticketId }.toSet()
        getCleanLiveIds().filter { it !in serverIds }.forEach { delete(it) }
    }

    @Query("SELECT ticketId FROM local_ticket WHERE syncState IN ('DIRTY','SYNCING','CONFLICT')")
    suspend fun getDirtyOrSyncingIds(): List<String>

    @Query("SELECT COUNT(*) FROM local_ticket WHERE syncState = 'CONFLICT'")
    fun observeConflictCount(): Flow<Int>

    @Query(
        """
        UPDATE local_ticket
        SET syncState = :syncState, conflictMessage = :message, updatedLocallyAt = :now
        WHERE ticketId = :id
        """
    )
    suspend fun setConflict(id: String, syncState: String, message: String?, now: Long = System.currentTimeMillis())

    @Query(
        """
        SELECT ticketId FROM local_ticket
        WHERE syncState = 'CLEAN'
          AND UPPER(COALESCE(status,'')) IN ('OPEN','PENDING','ESCALATED','HOLD')
        """
    )
    suspend fun getCleanLiveIds(): List<String>

    @Query("DELETE FROM local_ticket")
    suspend fun clearAll()
}
