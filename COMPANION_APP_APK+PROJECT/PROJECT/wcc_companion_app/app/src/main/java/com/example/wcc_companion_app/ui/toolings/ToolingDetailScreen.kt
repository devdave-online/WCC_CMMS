package com.example.wcc_companion_app.ui.toolings

import androidx.compose.runtime.Composable
import com.example.wcc_companion_app.data.remote.models.ToolingDto
import com.example.wcc_companion_app.ui.components.WccDetailHeader
import com.example.wcc_companion_app.ui.components.WccDetailInfoRow
import com.example.wcc_companion_app.ui.components.WccDetailModal

/**
 * Tooling detail — same modal language as Equipment (content-sized, no empty void).
 * Dismiss: Back / tap scrim.
 */
@Composable
fun ToolingDetailScreen(
    tooling: ToolingDto,
    onClose: () -> Unit
) {
    val name = tooling.tooling_name?.takeIf { it.isNotBlank() } ?: "Tooling"
    val code = listOfNotNull(tooling.tooling_code, tooling.barcode, tooling.asset_tag)
        .firstOrNull { !it.isNullOrBlank() } ?: "—"

    WccDetailModal(onDismiss = onClose) {
        WccDetailHeader(
            eyebrow = "TOOLING",
            title = name
        )
        WccDetailInfoRow("Code", code)
        WccDetailInfoRow("Tooling ID", tooling.tooling_id?.toString() ?: "—")
        WccDetailInfoRow("Barcode", tooling.barcode?.takeIf { it.isNotBlank() } ?: "—")
        WccDetailInfoRow("Asset tag", tooling.asset_tag?.takeIf { it.isNotBlank() } ?: "—")
        WccDetailInfoRow("Category", tooling.category?.takeIf { it.isNotBlank() } ?: "—")
        WccDetailInfoRow("Status", tooling.status?.takeIf { it.isNotBlank() } ?: "—")
        WccDetailInfoRow(
            "Location",
            tooling.location?.takeIf { it.isNotBlank() } ?: "—",
            showDivider = false
        )
    }
}
