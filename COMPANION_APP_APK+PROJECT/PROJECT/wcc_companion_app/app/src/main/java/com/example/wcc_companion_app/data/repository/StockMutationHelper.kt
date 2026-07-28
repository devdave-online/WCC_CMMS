package com.example.wcc_companion_app.data.repository

import com.example.wcc_companion_app.data.local.dao.PartDao
import com.example.wcc_companion_app.data.local.dao.PendingOpDao
import com.example.wcc_companion_app.data.remote.models.PartConsumptionDto
import com.example.wcc_companion_app.data.remote.models.TakeoverRequestDto
import com.example.wcc_companion_app.data.remote.models.CompanionWoRequestDto
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Optimistic local stock for offline ticket takeover / WO complete.
 * Reservations stay applied until outbox drains and inventory re-pulls.
 */
@Singleton
class StockMutationHelper @Inject constructor(
    private val partDao: PartDao,
    private val pendingOpDao: PendingOpDao,
) {
    private val gson = Gson()

    suspend fun applyConsumption(parts: List<PartConsumptionDto>) {
        for (p in parts) {
            if (p.qty > 0) partDao.adjustStock(p.part_id, -p.qty)
        }
    }

    /** Sum qty reserved by not-yet-synced ops (for inventory pull merge). */
    suspend fun reservedByPartId(): Map<Int, Int> {
        val ops = pendingOpDao.getAllOpenOrdered()
        val map = mutableMapOf<Int, Int>()
        for (op in ops) {
            val parts = extractParts(op.type, op.payloadJson) ?: continue
            for (p in parts) {
                if (p.qty > 0) {
                    map[p.part_id] = (map[p.part_id] ?: 0) + p.qty
                }
            }
        }
        return map
    }

    private fun extractParts(type: String, payloadJson: String): List<PartConsumptionDto>? {
        return try {
            when (type) {
                "TAKEOVER", "RESUME" -> {
                    gson.fromJson(payloadJson, TakeoverRequestDto::class.java)
                        ?.parts_consumed_data
                }
                "WO_COMPLETE" -> {
                    gson.fromJson(payloadJson, CompanionWoRequestDto::class.java)
                        ?.parts_consumed
                }
                else -> null
            }
        } catch (_: Exception) {
            // Fallback: bare list
            try {
                val typeToken = object : TypeToken<List<PartConsumptionDto>>() {}.type
                gson.fromJson<List<PartConsumptionDto>>(payloadJson, typeToken)
            } catch (_: Exception) {
                null
            }
        }
    }
}
