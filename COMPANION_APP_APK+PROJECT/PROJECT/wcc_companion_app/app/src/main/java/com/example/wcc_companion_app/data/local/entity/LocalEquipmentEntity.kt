package com.example.wcc_companion_app.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import com.example.wcc_companion_app.data.remote.models.EquipmentDto

@Entity(tableName = "local_equipment")
data class LocalEquipmentEntity(
    @PrimaryKey val equipId: Int,
    val assetUuid: String?,
    val equipName: String,
    val category: String?,
    val criticality: String?,
    val equipmentType: String?,
    val oemBrand: String?,
    val oemModel: String?,
    val oemSerial: String?,
    val plantName: String?,
    val lineName: String?,
    val stationName: String?,
    val cachedAt: Long = System.currentTimeMillis()
) {
    fun toDto(): EquipmentDto = EquipmentDto(
        equip_id = equipId,
        asset_uuid = assetUuid,
        equip_name = equipName,
        category = category,
        criticality = criticality,
        equipment_type = equipmentType,
        oem_brand = oemBrand,
        oem_model = oemModel,
        oem_serial = oemSerial,
        plant_name = plantName,
        line_name = lineName,
        station_name = stationName
    )

    companion object {
        fun fromDto(dto: EquipmentDto) = LocalEquipmentEntity(
            equipId = dto.equip_id,
            assetUuid = dto.asset_uuid,
            equipName = dto.equip_name,
            category = dto.category,
            criticality = dto.criticality,
            equipmentType = dto.equipment_type,
            oemBrand = dto.oem_brand,
            oemModel = dto.oem_model,
            oemSerial = dto.oem_serial,
            plantName = dto.plant_name,
            lineName = dto.line_name,
            stationName = dto.station_name
        )
    }
}
