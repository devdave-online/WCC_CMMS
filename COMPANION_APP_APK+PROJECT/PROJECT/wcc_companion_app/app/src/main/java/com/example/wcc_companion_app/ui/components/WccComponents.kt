package com.example.wcc_companion_app.ui.components

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.wcc_companion_app.ui.theme.WccTokens

@Composable
fun WccGlassCard(
    modifier: Modifier = Modifier,
    accent: Color = MaterialTheme.colorScheme.primary,
    content: @Composable ColumnScope.() -> Unit
) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(WccTokens.radiusXl),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.92f),
        border = BorderStroke(WccTokens.border, accent.copy(alpha = WccTokens.glassBorderAlpha)),
        shadowElevation = 2.dp
    ) {
        Column(modifier = Modifier.padding(WccTokens.space2xl), content = content)
    }
}

@Composable
fun WccSectionHeader(
    title: String,
    modifier: Modifier = Modifier,
    trailing: @Composable (() -> Unit)? = null
) {
    Row(
        modifier = modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(
            title,
            style = MaterialTheme.typography.labelMedium,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.primary,
            modifier = Modifier.weight(1f)
        )
        trailing?.invoke()
    }
}

@Composable
fun WccStatusChip(
    label: String,
    color: Color,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier,
        color = color.copy(alpha = 0.15f),
        shape = RoundedCornerShape(WccTokens.radiusSm),
        border = BorderStroke(WccTokens.borderThin, color.copy(alpha = 0.4f))
    ) {
        Text(
            label,
            modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp),
            fontWeight = FontWeight.Black,
            color = color,
            fontSize = 11.sp,
            maxLines = 1
        )
    }
}

@Composable
fun WccStatTile(
    value: String,
    label: String,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier,
        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.55f),
        shape = RoundedCornerShape(WccTokens.radiusMd),
        border = BorderStroke(WccTokens.borderThin, MaterialTheme.colorScheme.outline.copy(alpha = 0.2f))
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(vertical = 14.dp, horizontal = 8.dp)
        ) {
            Text(
                value,
                fontSize = 22.sp,
                fontWeight = FontWeight.Black,
                color = MaterialTheme.colorScheme.primary,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            Text(
                label,
                fontSize = 10.sp,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                maxLines = 2,
                overflow = TextOverflow.Ellipsis
            )
        }
    }
}

@Composable
fun WccEmptyState(
    message: String,
    modifier: Modifier = Modifier
) {
    Text(
        message,
        modifier = modifier.padding(vertical = WccTokens.spaceMd),
        fontSize = 14.sp,
        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
        lineHeight = 20.sp
    )
}

/**
 * Primary floor CTA — 56–60dp tall, glove-friendly, never buried in scroll.
 */
@Composable
fun WccPrimaryButton(
    label: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    loading: Boolean = false,
    containerColor: Color = MaterialTheme.colorScheme.primary,
    contentColor: Color = MaterialTheme.colorScheme.onPrimary,
    height: Dp = 56.dp
) {
    val haptic = androidx.compose.ui.platform.LocalHapticFeedback.current
    Button(
        onClick = {
            WccHaptics.select(haptic)
            onClick()
        },
        enabled = enabled && !loading,
        modifier = modifier
            .fillMaxWidth()
            .height(height)
            .defaultMinSize(minHeight = WccTokens.touchMin),
        shape = RoundedCornerShape(WccTokens.radiusMd),
        colors = ButtonDefaults.buttonColors(
            containerColor = containerColor,
            contentColor = contentColor,
            disabledContainerColor = containerColor.copy(alpha = 0.4f),
            disabledContentColor = contentColor.copy(alpha = 0.7f)
        ),
        elevation = ButtonDefaults.buttonElevation(defaultElevation = 2.dp, pressedElevation = 0.dp)
    ) {
        if (loading) {
            CircularProgressIndicator(
                modifier = Modifier.size(22.dp),
                strokeWidth = 2.dp,
                color = contentColor
            )
        } else {
            Text(label, fontWeight = FontWeight.Black, fontSize = 16.sp, maxLines = 1)
        }
    }
}

/**
 * Sticky bottom action dock — stays pinned under scrollable form content.
 * Use for takeover / closeout / WO complete so gloves never hunt for the CTA.
 */
@Composable
fun WccStickyActionBar(
    modifier: Modifier = Modifier,
    content: @Composable RowScope.() -> Unit
) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.98f),
        shadowElevation = 8.dp,
        tonalElevation = 2.dp
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .navigationBarsPadding()
                .padding(horizontal = WccTokens.spaceXl, vertical = WccTokens.spaceMd),
            horizontalArrangement = Arrangement.spacedBy(WccTokens.spaceMd),
            verticalAlignment = Alignment.CenterVertically,
            content = content
        )
    }
}

/**
 * Full-screen modal shell for detail / filter sub-menus.
 *
 * Rules (from screenshot QA + M3):
 *  - Scrim is full-bleed and dark enough to kill underlay chrome (orbiter, Live chip).
 *  - Sheet sizes to **content** (no empty 90% height voids) with a max height cap.
 *  - Dismiss: Back / tap scrim (no header X).
 */
@Composable
fun WccDetailModal(
    onDismiss: () -> Unit,
    borderColor: Color = MaterialTheme.colorScheme.outline.copy(alpha = 0.4f),
    content: @Composable ColumnScope.() -> Unit
) {
    val config = LocalConfiguration.current
    val isLandscape = config.orientation == Configuration.ORIENTATION_LANDSCAPE
    val maxSheetH = (config.screenHeightDp * if (isLandscape) 0.92f else 0.86f).dp

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(
            usePlatformDefaultWidth = false,
            decorFitsSystemWindows = false
        )
    ) {
        Box(modifier = Modifier.fillMaxSize()) {
            // Full-bleed scrim — must cover status + nav so underlay actions cannot read as active
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Color.Black.copy(alpha = 0.78f))
                    .clickable(
                        indication = null,
                        interactionSource = remember { MutableInteractionSource() },
                        onClick = onDismiss
                    )
            )
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .windowInsetsPadding(WindowInsets.systemBars)
                    .imePadding()
                    .padding(horizontal = 14.dp, vertical = 12.dp),
                contentAlignment = Alignment.Center
            ) {
                Surface(
                    modifier = Modifier
                        .fillMaxWidth(if (isLandscape) 0.88f else 0.96f)
                        .wrapContentHeight()
                        .heightIn(max = maxSheetH)
                        .clickable(
                            indication = null,
                            interactionSource = remember { MutableInteractionSource() },
                            onClick = { /* consume — do not dismiss when tapping sheet */ }
                        ),
                    shape = RoundedCornerShape(WccTokens.radiusXxl),
                    color = MaterialTheme.colorScheme.surface.copy(alpha = 0.98f),
                    border = BorderStroke(WccTokens.borderThin, borderColor),
                    shadowElevation = 12.dp,
                    tonalElevation = 4.dp
                ) {
                    Column(
                        modifier = Modifier
                            .verticalScroll(rememberScrollState())
                            .padding(horizontal = 20.dp, vertical = 18.dp),
                        content = content
                    )
                }
            }
        }
    }
}

/** Dense label/value row for detail sub-menus (no sparse empty columns). */
@Composable
fun WccDetailInfoRow(
    label: String,
    value: String,
    showDivider: Boolean = true
) {
    Column(modifier = Modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 10.dp),
            verticalAlignment = Alignment.Top
        ) {
            Text(
                label,
                modifier = Modifier.width(108.dp),
                fontSize = 12.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                fontWeight = FontWeight.SemiBold
            )
            Text(
                value,
                modifier = Modifier.weight(1f),
                fontSize = 14.sp,
                fontWeight = FontWeight.Medium,
                color = MaterialTheme.colorScheme.onSurface
            )
        }
        if (showDivider) {
            HorizontalDivider(
                thickness = 0.5.dp,
                color = MaterialTheme.colorScheme.outline.copy(alpha = 0.18f)
            )
        }
    }
}

@Composable
fun WccDetailHeader(
    eyebrow: String,
    title: String,
    subtitle: String? = "Tap outside or Back to close"
) {
    Column(modifier = Modifier.fillMaxWidth()) {
        Text(
            eyebrow,
            style = MaterialTheme.typography.labelLarge,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.primary
        )
        Text(
            title,
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.Black,
            modifier = Modifier.padding(top = 2.dp)
        )
        if (!subtitle.isNullOrBlank()) {
            Text(
                subtitle,
                fontSize = 11.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.45f),
                modifier = Modifier.padding(top = 4.dp, bottom = 8.dp)
            )
        } else {
            Spacer(Modifier.height(8.dp))
        }
    }
}
