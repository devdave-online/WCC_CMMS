package com.example.wcc_companion_app.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto

@Entity(tableName = "local_part")
data class LocalPartEntity(
    @PrimaryKey val partId: Int,
    val partName: String,
    val internalCode: String,
    val stockLevel: Int,
    val minimumThreshold: Int?,
    val maximumStock: Int?,
    val lifecycleStatus: String?,
    val partCondition: String?,
    val uom: String?,
    val oemName: String?,
    val oemPartNumber: String?,
    val aisle: String?,
    val rack: String?,
    val shelf: String?,
    val binCode: String?,
    val costPerUnit: String?,
    val standardizedDesc: String?,
    val cachedAt: Long = System.currentTimeMillis()
) {
    fun toDto(): InventoryPartDto = InventoryPartDto(
        part_id = partId,
        part_name = partName,
        internal_code = internalCode,
        stock_level = stockLevel,
        minimum_threshold = minimumThreshold,
        maximum_stock = maximumStock,
        lifecycle_status = lifecycleStatus,
        part_condition = partCondition,
        uom = uom,
        oem_name = oemName,
        oem_part_number = oemPartNumber,
        aisle = aisle,
        rack = rack,
        shelf = shelf,
        bin_code = binCode,
        cost_per_unit = costPerUnit,
        standardized_desc = standardizedDesc
    )

    companion object {
        fun fromDto(dto: InventoryPartDto) = LocalPartEntity(
            partId = dto.part_id,
            partName = dto.part_name,
            internalCode = dto.internal_code,
            stockLevel = dto.stock_level,
            minimumThreshold = dto.minimum_threshold,
            maximumStock = dto.maximum_stock,
            lifecycleStatus = dto.lifecycle_status,
            partCondition = dto.part_condition,
            uom = dto.uom,
            oemName = dto.oem_name,
            oemPartNumber = dto.oem_part_number,
            aisle = dto.aisle,
            rack = dto.rack,
            shelf = dto.shelf,
            binCode = dto.bin_code,
            costPerUnit = dto.cost_per_unit,
            standardizedDesc = dto.standardized_desc
        )
    }
}
