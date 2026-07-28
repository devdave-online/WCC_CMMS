package com.example.wcc_companion_app.data.remote.models

import org.json.JSONArray
import org.json.JSONObject

/**
 * Parse WO `parts_list` JSON from the server.
 * Accepts either `[1,2]` or `[{"part_id":1,"qty":2}]`.
 */
fun parsePartsList(raw: String?): List<Pair<Int, Int>> {
    if (raw.isNullOrBlank()) return emptyList()
    return try {
        val arr = JSONArray(raw)
        buildList {
            for (i in 0 until arr.length()) {
                when (val el = arr.get(i)) {
                    is Int -> add(el to 1)
                    is Number -> add(el.toInt() to 1)
                    is JSONObject -> {
                        val id = el.optInt("part_id", el.optInt("id", 0))
                        val qty = el.optInt("qty", 1)
                        if (id > 0) add(id to qty)
                    }
                    is String -> {
                        // rare: numeric string
                        el.toIntOrNull()?.let { add(it to 1) }
                    }
                }
            }
        }
    } catch (_: Exception) {
        emptyList()
    }
}

/**
 * Human-readable parts line for detail modals.
 * Resolves names from inventory catalog when available.
 * Example: "Air Filter Panel G4 ×4 · Bearing 6205 ×2"
 */
fun formatPartsList(
    raw: String?,
    catalog: List<InventoryPartDto> = emptyList()
): String {
    val items = parsePartsList(raw)
    if (items.isEmpty()) {
        // Avoid dumping raw JSON if parse failed but string looks like JSON
        val trimmed = raw?.trim().orEmpty()
        if (trimmed.isEmpty()) return "—"
        if (trimmed.startsWith("[") || trimmed.startsWith("{")) return "—"
        return trimmed
    }
    return items.joinToString(" · ") { (id, qty) ->
        val inv = catalog.find { it.part_id == id }
        val label = when {
            inv == null -> "Part #$id"
            inv.internal_code.isNotBlank() -> "${inv.part_name} (${inv.internal_code})"
            else -> inv.part_name
        }
        if (qty != 1) "$label ×$qty" else label
    }
}

/**
 * Ticket-action `parts_used` may be human text ("Filter x2; Seal x1") from the app/web,
 * or legacy JSON like parts_list. Never show raw JSON brackets in UI.
 */
fun formatPartsUsedField(
    raw: String?,
    catalog: List<InventoryPartDto> = emptyList()
): String {
    val trimmed = raw?.trim().orEmpty()
    if (trimmed.isEmpty() || trimmed.equals("None", ignoreCase = true)) return "—"
    if (trimmed.startsWith("[") || trimmed.startsWith("{")) {
        return formatPartsList(trimmed, catalog)
    }
    return trimmed
}
