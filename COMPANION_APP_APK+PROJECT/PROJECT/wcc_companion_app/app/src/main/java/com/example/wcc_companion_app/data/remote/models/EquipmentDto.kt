package com.example.wcc_companion_app.data.remote.models

/**
 * Equipment record from /api/v1/equipment.
 *
 * `asset_uuid` is the scannable asset tag printed on the machine. Values come in two
 * shapes in the live DB: raw GUIDs (e.g. "78cd32d7-341b-…") and uuid_rules-generated
 * tags (e.g. "MCH-0001"), so scan lookups must match it exactly rather than parse it.
 */
data class EquipmentDto(
    val equip_id: Int,
    val asset_uuid: String?,
    val equip_name: String,
    val category: String?,
    val criticality: String?,
    val equipment_type: String?,
    val oem_brand: String?,
    val oem_model: String?,
    val oem_serial: String?,
    val plant_name: String?,
    val line_name: String?,
    val station_name: String?
)
