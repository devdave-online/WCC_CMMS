package com.example.wcc_companion_app.ui.inventory

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
import com.example.wcc_companion_app.ui.components.WccDetailHeader
import com.example.wcc_companion_app.ui.components.WccDetailInfoRow
import com.example.wcc_companion_app.ui.components.WccDetailModal

@Composable
fun InventoryDetailScreen(
    part: InventoryPartDto,
    onClose: () -> Unit
) {
    val status = classifyStock(part)

    WccDetailModal(
        onDismiss = onClose,
        borderColor = status.color.copy(alpha = 0.4f)
    ) {
        WccDetailHeader(
            eyebrow = "PART DETAIL",
            title = part.part_name
        )
        Surface(
            color = status.color.copy(alpha = 0.15f),
            shape = RoundedCornerShape(12.dp),
            border = BorderStroke(1.dp, status.color.copy(alpha = 0.4f))
        ) {
            Text(
                status.label.uppercase(),
                modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Black,
                color = status.color
            )
        }
        Spacer(modifier = Modifier.height(12.dp))

        WccDetailInfoRow("Code", part.internal_code.ifBlank { "—" })
        WccDetailInfoRow(
            "Stock",
            "${part.stock_level}${part.uom?.let { " $it" } ?: ""}"
        )
        WccDetailInfoRow("Minimum", part.minimum_threshold?.toString() ?: "—")
        WccDetailInfoRow("Maximum", part.maximum_stock?.toString() ?: "—")
        WccDetailInfoRow("Lifecycle", part.lifecycle_status ?: "—")
        WccDetailInfoRow("Condition", part.part_condition ?: "—")
        WccDetailInfoRow("Location", binLocation(part))
        WccDetailInfoRow(
            "OEM",
            listOfNotNull(part.oem_name, part.oem_part_number).joinToString(" · ").ifBlank { "—" }
        )
        WccDetailInfoRow("Description", part.standardized_desc ?: "—")
        WccDetailInfoRow("Unit cost", part.cost_per_unit ?: "—")
        WccDetailInfoRow(
            "Auto reorder",
            if (part.auto_reorder == 1) "Yes" else "No",
            showDivider = false
        )
    }
}
