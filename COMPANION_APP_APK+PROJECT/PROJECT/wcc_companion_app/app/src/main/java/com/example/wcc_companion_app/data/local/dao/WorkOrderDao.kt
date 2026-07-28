package com.example.wcc_companion_app.data.local.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Transaction
import com.example.wcc_companion_app.data.local.entity.LocalWorkOrderEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface WorkOrderDao {
    @Query(
        """
        SELECT * FROM local_work_order
        WHERE COALESCE(status,'') NOT IN ('Completed','Cancelled')
           OR syncState IN ('DIRTY','SYNCING','CONFLICT')
        ORDER BY COALESCE(scheduledDate,'') ASC
        """
    )
    fun observeLive(): Flow<List<LocalWorkOrderEntity>>

    @Query("SELECT * FROM local_work_order WHERE woId = :id LIMIT 1")
    suspend fun getById(id: Int): LocalWorkOrderEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(row: LocalWorkOrderEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(rows: List<LocalWorkOrderEntity>)

    @Query("DELETE FROM local_work_order WHERE woId = :id")
    suspend fun delete(id: Int)

    @Query("SELECT woId FROM local_work_order WHERE syncState IN ('DIRTY','SYNCING','CONFLICT')")
    suspend fun getProtectedIds(): List<Int>

    @Query(
        """
        SELECT woId FROM local_work_order
        WHERE syncState = 'CLEAN'
          AND COALESCE(status,'') NOT IN ('Completed','Cancelled')
        """
    )
    suspend fun getCleanLiveIds(): List<Int>

    @Query("SELECT COUNT(*) FROM local_work_order WHERE syncState = 'CONFLICT'")
    fun observeConflictCount(): Flow<Int>

    @Transaction
    suspend fun replaceLiveFromServer(serverRows: List<LocalWorkOrderEntity>) {
        val protected = getProtectedIds().toSet()
        val toUpsert = serverRows.filter { it.woId !in protected }
        if (toUpsert.isNotEmpty()) upsertAll(toUpsert)
        val serverIds = serverRows.map { it.woId }.toSet()
        getCleanLiveIds().filter { it !in serverIds }.forEach { delete(it) }
    }

    @Query("DELETE FROM local_work_order")
    suspend fun clearAll()
}
