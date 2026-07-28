package com.example.wcc_companion_app.ui.inventory

import androidx.compose.ui.graphics.Color
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto

/**
 * Client-side mirror of `inc/stock_status.php` (without on-order PO set —
 * that needs a join the list API does not return). Precedence matches web.
 */
enum class StockState(
    val label: String,
    val color: Color
) {
    HEALTHY("Healthy", Color(0xFF10B981)),
    APPROACHING("Low — approaching minimum", Color(0xFFF59E0B)),
    REORDER("At/below minimum — reorder", Color(0xFFEF4444)),
    NO_VENDOR("Below minimum — no supplier set", Color(0xFFEF4444)),
    OUT("Out of stock", Color(0xFFEF4444)),
    OBSOLETE("Obsolete — do not reorder", Color(0xFF6B7280))
}

fun classifyStock(part: InventoryPartDto, warnPct: Int = 25): StockState {
    val stock = part.stock_level
    val min = (part.minimum_threshold ?: 0).coerceAtLeast(0)
    val life = part.lifecycle_status ?: "Active"
    val hasVendor = (part.primary_vendor_id ?: 0) > 0
    val buffer = maxOf(1, kotlin.math.ceil(min * (warnPct / 100.0)).toInt())
    val band = min + buffer

    return when {
        life.equals("Obsolete", true) || life.equals("Phasing Out", true) -> StockState.OBSOLETE
        stock == 0 -> StockState.OUT
        stock <= band -> when {
            !hasVendor && stock <= min -> StockState.NO_VENDOR
            stock <= min -> StockState.REORDER
            else -> StockState.APPROACHING
        }
        else -> StockState.HEALTHY
    }
}

fun binLocation(part: InventoryPartDto): String {
    val bin = part.bin_code?.takeIf { it.isNotBlank() }
    if (bin != null) return bin
    return listOfNotNull(part.aisle, part.rack, part.shelf)
        .joinToString("-")
        .ifBlank { "—" }
}
