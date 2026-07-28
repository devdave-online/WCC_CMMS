package com.example.wcc_companion_app.ui.components

import androidx.compose.animation.*
import androidx.compose.animation.core.spring
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import com.example.wcc_companion_app.ui.theme.WccTokens

data class OrbiterAction(
    val icon: ImageVector,
    val label: String,
    val onClick: () -> Unit,
    val color: androidx.compose.ui.graphics.Color? = null
)

@Composable
fun OrbiterMenu(
    actions: List<OrbiterAction>,
    visible: Boolean
) {
    AnimatedVisibility(
        visible = visible,
        enter = expandHorizontally(spring()) + fadeIn(),
        exit = shrinkHorizontally(spring()) + fadeOut()
    ) {
        Row(
            modifier = Modifier
                .padding(horizontal = WccTokens.spaceMd)
                // Nav clearance owned by portrait item band / landscape parent.
                .padding(bottom = WccTokens.spaceSm)
                .shadow(10.dp, CircleShape)
                .background(
                    color = MaterialTheme.colorScheme.surface.copy(alpha = 0.94f),
                    shape = CircleShape
                )
                .border(
                    BorderStroke(
                        WccTokens.border,
                        MaterialTheme.colorScheme.primary.copy(alpha = 0.32f)
                    ),
                    CircleShape
                )
                // M3: ≥8 dp between targets; pad dock so 64 dp buttons breathe
                .padding(horizontal = WccTokens.spaceLg, vertical = WccTokens.spaceMd),
            horizontalArrangement = Arrangement.spacedBy(WccTokens.spaceMd),
            verticalAlignment = Alignment.CenterVertically
        ) {
            actions.forEach { action ->
                OrbiterButton(action)
            }
        }
    }
}

@Composable
fun OrbiterButton(action: OrbiterAction) {
    // M3 FAB = 56 dp; industrial orbiter = 64 dp (WccTokens) for gloves + primary CTA emphasis
    FilledIconButton(
        onClick = action.onClick,
        modifier = Modifier
            .size(WccTokens.orbiterButton)
            .defaultMinSize(minWidth = WccTokens.touchMin, minHeight = WccTokens.touchMin),
        colors = IconButtonDefaults.filledIconButtonColors(
            containerColor = action.color ?: MaterialTheme.colorScheme.primary,
            contentColor = MaterialTheme.colorScheme.onPrimary
        )
    ) {
        Icon(
            imageVector = action.icon,
            contentDescription = action.label,
            modifier = Modifier.size(WccTokens.orbiterIcon)
        )
    }
}
