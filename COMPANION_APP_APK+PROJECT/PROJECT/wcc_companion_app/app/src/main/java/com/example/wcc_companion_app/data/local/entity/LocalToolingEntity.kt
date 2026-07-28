package com.example.wcc_companion_app.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import com.example.wcc_companion_app.data.remote.models.ToolingDto

@Entity(tableName = "local_tooling")
data class LocalToolingEntity(
    @PrimaryKey val toolingId: Int,
    val toolingName: String?,
    val toolingCode: String?,
    val barcode: String?,
    val assetTag: String?,
    val category: String?,
    val status: String?,
    val location: String?,
    val cachedAt: Long = System.currentTimeMillis()
) {
    fun toDto(): ToolingDto = ToolingDto(
        tooling_id = toolingId,
        tooling_name = toolingName,
        tooling_code = toolingCode,
        barcode = barcode,
        asset_tag = assetTag,
        category = category,
        status = status,
        location = location
    )

    companion object {
        fun fromDto(dto: ToolingDto): LocalToolingEntity? {
            val id = dto.tooling_id ?: return null
            return LocalToolingEntity(
                toolingId = id,
                toolingName = dto.tooling_name,
                toolingCode = dto.tooling_code,
                barcode = dto.barcode,
                assetTag = dto.asset_tag,
                category = dto.category,
                status = dto.status,
                location = dto.location
            )
        }
    }
}
