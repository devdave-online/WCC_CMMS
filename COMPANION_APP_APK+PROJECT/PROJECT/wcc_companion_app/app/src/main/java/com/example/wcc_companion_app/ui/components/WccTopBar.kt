package com.example.wcc_companion_app.ui.components

import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.DarkMode
import androidx.compose.material.icons.filled.HelpOutline
import androidx.compose.material.icons.filled.LightMode
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.rotate
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalHapticFeedback
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.wcc_companion_app.R
import com.example.wcc_companion_app.data.locale.AppLocale
import com.example.wcc_companion_app.data.remote.models.FactoryHealthDto
import com.example.wcc_companion_app.data.sync.LiveBadgeState
import com.example.wcc_companion_app.data.sync.OfflineReason
import com.example.wcc_companion_app.ui.theme.WccError
import com.example.wcc_companion_app.ui.theme.WccSuccess
import com.example.wcc_companion_app.ui.theme.WccTokens
import com.example.wcc_companion_app.ui.theme.WccWarning

/**
 * Persistent top bar: Live chip, factory health, help, profile, **language**, theme.
 * MMM gestures stay the only way to move the rail — this bar never replaces them.
 */
@Composable
fun WccTopBar(
    isDark: Boolean,
    liveBadge: LiveBadgeState = LiveBadgeState.Live,
    factoryHealth: FactoryHealthDto? = null,
    factoryHealthStale: Boolean = false,
    currentLocale: AppLocale = AppLocale.ENGLISH,
    onToggleTheme: () -> Unit,
    onOpenProfile: () -> Unit,
    onResync: () -> Unit,
    /** Live chip: open outbox sheet when queue/conflict; otherwise resync. */
    onLiveBadgeClick: (() -> Unit)? = null,
    onLocaleSelected: (AppLocale) -> Unit = {},
    /** When false, parent already applied status-bar insets (e.g. under Open Beta banner). */
    applyStatusBars: Boolean = true,
    modifier: Modifier = Modifier
) {
    var showCoach by remember { mutableStateOf(false) }
    var showLanguage by remember { mutableStateOf(false) }
    val haptic = LocalHapticFeedback.current
    val context = LocalContext.current
    val isOnline = liveBadge is LiveBadgeState.Live || liveBadge is LiveBadgeState.Syncing

    Row(
        modifier = modifier
            .fillMaxWidth()
            .then(if (applyStatusBars) Modifier.statusBarsPadding().displayCutoutPadding() else Modifier)
            .padding(horizontal = 12.dp, vertical = 8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        ConnectionChip(
            isDark = isDark,
            state = liveBadge,
            onClick = {
                WccHaptics.confirm(haptic, context)
                if (onLiveBadgeClick != null) onLiveBadgeClick() else onResync()
            }
        )

        if (factoryHealth != null) {
            Spacer(modifier = Modifier.width(6.dp))
            FactoryHealthBadge(
                health = factoryHealth,
                isDark = isDark,
                stale = factoryHealthStale || !isOnline
            )
        }

        Spacer(modifier = Modifier.weight(1f))

        TopBarIcon(
            isDark = isDark,
            onClick = { showCoach = true },
            contentDescription = stringResource(R.string.cd_help)
        ) {
            Icon(
                Icons.Default.HelpOutline,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(20.dp)
            )
        }

        Spacer(modifier = Modifier.width(8.dp))

        TopBarIcon(
            isDark = isDark,
            onClick = onOpenProfile,
            contentDescription = stringResource(R.string.cd_profile)
        ) {
            Icon(
                Icons.Default.Person,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(20.dp)
            )
        }

        Spacer(modifier = Modifier.width(8.dp))

        // Language chip — between Profile and Theme (shows active code EN/HI/VI/ID)
        TopBarIcon(
            isDark = isDark,
            onClick = { showLanguage = true },
            contentDescription = stringResource(R.string.cd_language)
        ) {
            Text(
                text = currentLocale.chipCode,
                fontSize = 11.sp,
                fontWeight = FontWeight.Black,
                color = MaterialTheme.colorScheme.primary,
                maxLines = 1
            )
        }

        Spacer(modifier = Modifier.width(8.dp))

        TopBarIcon(
            isDark = isDark,
            onClick = onToggleTheme,
            contentDescription = stringResource(R.string.cd_theme)
        ) {
            Icon(
                imageVector = if (isDark) Icons.Default.LightMode else Icons.Default.DarkMode,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
                modifier = Modifier.size(20.dp)
            )
        }
    }

    if (showCoach) {
        GestureCoachDialog(onDismiss = { showCoach = false })
    }

    if (showLanguage) {
        LanguagePickerDialog(
            current = currentLocale,
            onDismiss = { showLanguage = false },
            onSelect = { locale ->
                WccHaptics.confirm(haptic)
                onLocaleSelected(locale)
                showLanguage = false
            }
        )
    }
}

@Composable
private fun LanguagePickerDialog(
    current: AppLocale,
    onDismiss: () -> Unit,
    onSelect: (AppLocale) -> Unit,
) {
    // Full 34-locale catalog (web SoT) — native labels, scrollable.
    val options = AppLocale.ALL
    AlertDialog(
        onDismissRequest = onDismiss,
        shape = RoundedCornerShape(WccTokens.radiusXxl),
        title = {
            Text(
                stringResource(R.string.language_picker_title),
                fontWeight = FontWeight.Black
            )
        },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 420.dp)
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(2.dp)
            ) {
                Text(
                    text = "${options.size} languages · same catalog as web",
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                    modifier = Modifier.padding(bottom = 6.dp)
                )
                options.forEach { locale ->
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { onSelect(locale) }
                            .padding(vertical = 6.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        RadioButton(
                            selected = locale == current,
                            onClick = { onSelect(locale) }
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = "${locale.chipCode}  ·  ${locale.nativeLabel}",
                                fontWeight = if (locale == current) FontWeight.Bold else FontWeight.SemiBold,
                                maxLines = 1
                            )
                            Text(
                                text = locale.englishLabel + if (locale.isRtl) " · RTL" else "",
                                fontSize = 11.sp,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                                maxLines = 1
                            )
                        }
                    }
                }
            }
        },
        confirmButton = {
            TextButton(onClick = onDismiss) {
                Text("OK")
            }
        }
    )
}

@Composable
private fun ConnectionChip(
    isDark: Boolean,
    state: LiveBadgeState,
    onClick: () -> Unit
) {
    val statusColor = when (state) {
        is LiveBadgeState.Live -> WccSuccess
        is LiveBadgeState.Syncing -> WccWarning
        is LiveBadgeState.Conflict -> WccWarning
        is LiveBadgeState.Offline,
        is LiveBadgeState.OfflineUnsynced -> WccError
    }

    val pulseTarget = when (state) {
        is LiveBadgeState.Syncing -> 0.4f
        is LiveBadgeState.OfflineUnsynced -> 0.35f
        is LiveBadgeState.Conflict -> 0.4f
        is LiveBadgeState.Offline -> 0.55f
        else -> 1f
    }
    val pulse by rememberInfiniteTransition(label = "live_badge").animateFloat(
        initialValue = 1f,
        targetValue = pulseTarget,
        animationSpec = infiniteRepeatable(
            animation = tween(
                durationMillis = if (state is LiveBadgeState.OfflineUnsynced) 550 else 700,
                easing = FastOutSlowInEasing
            ),
            repeatMode = RepeatMode.Reverse
        ),
        label = "live_pulse"
    )

    val spin by rememberInfiniteTransition(label = "sync_spin").animateFloat(
        initialValue = 0f,
        targetValue = if (state is LiveBadgeState.Syncing) 360f else 0f,
        animationSpec = infiniteRepeatable(
            animation = tween(900, easing = LinearEasing),
            repeatMode = RepeatMode.Restart
        ),
        label = "sync_rot"
    )

    val label = when (state) {
        is LiveBadgeState.Live -> stringResource(R.string.badge_live)
        is LiveBadgeState.Syncing -> stringResource(R.string.badge_syncing)
        is LiveBadgeState.Conflict -> stringResource(R.string.badge_conflict, state.count)
        is LiveBadgeState.OfflineUnsynced -> stringResource(
            R.string.badge_offline_unsynced,
            offlineLabel(state.reason),
            state.count
        )
        is LiveBadgeState.Offline -> offlineLabel(state.reason)
    }

    Row(
        modifier = Modifier
            .clip(RoundedCornerShape(WccTokens.radiusPill))
            .background(
                if (isDark) Color.Black.copy(alpha = 0.35f) else Color.White.copy(alpha = 0.55f)
            )
            .border(
                BorderStroke(WccTokens.borderThin, statusColor.copy(alpha = 0.55f)),
                RoundedCornerShape(WccTokens.radiusPill)
            )
            .clickable(
                indication = null,
                interactionSource = remember { MutableInteractionSource() },
                onClick = onClick
            )
            .padding(horizontal = 10.dp, vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        if (state is LiveBadgeState.Syncing) {
            Icon(
                imageVector = Icons.Default.Sync,
                contentDescription = null,
                tint = statusColor,
                modifier = Modifier
                    .size(14.dp)
                    .rotate(spin)
            )
        } else {
            Box(
                modifier = Modifier
                    .size(8.dp)
                    .clip(CircleShape)
                    .background(
                        Brush.radialGradient(
                            listOf(
                                statusColor.copy(alpha = pulse),
                                statusColor.copy(alpha = pulse * 0.4f)
                            )
                        )
                    )
            )
        }
        Spacer(modifier = Modifier.width(6.dp))
        Text(
            text = label,
            fontSize = 11.sp,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.9f),
            maxLines = 1
        )
    }
}

@Composable
private fun offlineLabel(reason: OfflineReason): String = when (reason) {
    OfflineReason.CELLULAR -> stringResource(R.string.badge_off_plant)
    OfflineReason.PLANT_UNREACHABLE -> stringResource(R.string.badge_offline)
    OfflineReason.NO_NETWORK -> stringResource(R.string.badge_offline)
}

@Composable
private fun TopBarIcon(
    isDark: Boolean,
    onClick: () -> Unit,
    contentDescription: String,
    content: @Composable () -> Unit
) {
    Box(
        modifier = Modifier
            .size(40.dp)
            .clip(CircleShape)
            .background(
                if (isDark) Color.Black.copy(alpha = 0.35f) else Color.White.copy(alpha = 0.55f)
            )
            .border(
                BorderStroke(WccTokens.borderThin, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f)),
                CircleShape
            )
            .clickable(
                indication = null,
                interactionSource = remember { MutableInteractionSource() },
                onClick = onClick
            ),
        contentAlignment = Alignment.Center
    ) {
        content()
    }
}
