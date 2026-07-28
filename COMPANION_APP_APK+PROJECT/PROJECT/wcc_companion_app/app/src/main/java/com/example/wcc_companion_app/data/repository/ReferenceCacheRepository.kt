package com.example.wcc_companion_app.data.repository

import com.example.wcc_companion_app.data.local.dao.EquipmentDao
import com.example.wcc_companion_app.data.local.dao.PartDao
import com.example.wcc_companion_app.data.local.dao.ToolingDao
import com.example.wcc_companion_app.data.local.entity.LocalEquipmentEntity
import com.example.wcc_companion_app.data.local.entity.LocalPartEntity
import com.example.wcc_companion_app.data.local.entity.LocalToolingEntity
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.remote.models.EquipmentDto
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
import com.example.wcc_companion_app.data.remote.models.ToolingDto
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Infrequently changing reference tables (equip / parts / toolings).
 * Pull when online; Room feeds UI when offline.
 */
@Singleton
class ReferenceCacheRepository @Inject constructor(
    private val apiService: WccApiService,
    private val equipmentDao: EquipmentDao,
    private val partDao: PartDao,
    private val toolingDao: ToolingDao,
    private val stockHelper: StockMutationHelper,
) {
    val equipment: Flow<List<EquipmentDto>> =
        equipmentDao.observeAll().map { list -> list.map { it.toDto() } }

    val parts: Flow<List<InventoryPartDto>> =
        partDao.observeAll().map { list -> list.map { it.toDto() } }

    val toolings: Flow<List<ToolingDto>> =
        toolingDao.observeAll().map { list -> list.map { it.toDto() } }

    suspend fun pullEquipment(): Boolean = try {
        val resp = apiService.searchEquipment(search = null, perPage = 200)
        if (resp.isSuccessful) {
            val rows = resp.body()?.data.orEmpty().map { LocalEquipmentEntity.fromDto(it) }
            equipmentDao.replaceAll(rows)
            true
        } else false
    } catch (_: Exception) {
        false
    }

    suspend fun pullParts(): Boolean = try {
        val resp = apiService.getInventory(perPage = 500)
        if (resp.isSuccessful) {
            val rows = resp.body()?.data.orEmpty().map { LocalPartEntity.fromDto(it) }
            // Keep offline reservations so stock UI doesn't bounce back until outbox drains.
            val reserved = stockHelper.reservedByPartId()
            if (reserved.isEmpty()) {
                partDao.replaceAll(rows)
            } else {
                partDao.replaceAllWithReservations(rows, reserved)
            }
            true
        } else false
    } catch (_: Exception) {
        false
    }

    suspend fun pullToolings(): Boolean = try {
        val resp = apiService.searchToolings(search = null)
        if (resp.isSuccessful) {
            val rows = resp.body()?.data.orEmpty().mapNotNull { LocalToolingEntity.fromDto(it) }
            toolingDao.replaceAll(rows)
            true
        } else false
    } catch (_: Exception) {
        false
    }

    suspend fun pullAll(): Boolean {
        val e = pullEquipment()
        val p = pullParts()
        val t = pullToolings()
        return e || p || t
    }

    suspend fun clearAll() {
        equipmentDao.clearAll()
        partDao.clearAll()
        toolingDao.clearAll()
    }
}
