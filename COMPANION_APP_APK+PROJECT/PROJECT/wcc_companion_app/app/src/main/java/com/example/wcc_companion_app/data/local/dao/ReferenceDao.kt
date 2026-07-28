package com.example.wcc_companion_app.data.local.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Transaction
import com.example.wcc_companion_app.data.local.entity.LocalEquipmentEntity
import com.example.wcc_companion_app.data.local.entity.LocalPartEntity
import com.example.wcc_companion_app.data.local.entity.LocalToolingEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface EquipmentDao {
    @Query("SELECT * FROM local_equipment ORDER BY equipName ASC")
    fun observeAll(): Flow<List<LocalEquipmentEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(rows: List<LocalEquipmentEntity>)

    @Query("DELETE FROM local_equipment")
    suspend fun clearAll()

    @Transaction
    suspend fun replaceAll(rows: List<LocalEquipmentEntity>) {
        clearAll()
        if (rows.isNotEmpty()) upsertAll(rows)
    }
}

@Dao
interface PartDao {
    @Query("SELECT * FROM local_part ORDER BY partName ASC")
    fun observeAll(): Flow<List<LocalPartEntity>>

    @Query("SELECT * FROM local_part WHERE partId = :id LIMIT 1")
    suspend fun getById(id: Int): LocalPartEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(row: LocalPartEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(rows: List<LocalPartEntity>)

    @Query(
        """
        UPDATE local_part
        SET stockLevel = CASE
            WHEN stockLevel + :delta < 0 THEN 0
            ELSE stockLevel + :delta
        END
        WHERE partId = :partId
        """
    )
    suspend fun adjustStock(partId: Int, delta: Int)

    @Query("DELETE FROM local_part")
    suspend fun clearAll()

    @Transaction
    suspend fun replaceAll(rows: List<LocalPartEntity>) {
        clearAll()
        if (rows.isNotEmpty()) upsertAll(rows)
    }

    /**
     * Server pull that keeps offline reservations: stock shown =
     * max(0, serverStock - reservedQty).
     */
    @Transaction
    suspend fun replaceAllWithReservations(
        rows: List<LocalPartEntity>,
        reservedByPartId: Map<Int, Int>,
    ) {
        val adjusted = rows.map { row ->
            val reserved = reservedByPartId[row.partId] ?: 0
            if (reserved <= 0) row
            else row.copy(stockLevel = (row.stockLevel - reserved).coerceAtLeast(0))
        }
        clearAll()
        if (adjusted.isNotEmpty()) upsertAll(adjusted)
    }
}

@Dao
interface ToolingDao {
    @Query("SELECT * FROM local_tooling ORDER BY toolingName ASC")
    fun observeAll(): Flow<List<LocalToolingEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(rows: List<LocalToolingEntity>)

    @Query("DELETE FROM local_tooling")
    suspend fun clearAll()

    @Transaction
    suspend fun replaceAll(rows: List<LocalToolingEntity>) {
        clearAll()
        if (rows.isNotEmpty()) upsertAll(rows)
    }
}
