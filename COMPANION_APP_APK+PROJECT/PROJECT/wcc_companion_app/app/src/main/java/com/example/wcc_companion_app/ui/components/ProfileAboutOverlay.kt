package com.example.wcc_companion_app.ui.components

import android.content.Intent
import android.net.Uri
import android.widget.Toast
import androidx.compose.animation.core.Animatable
import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.gestures.awaitEachGesture
import androidx.compose.foundation.gestures.awaitFirstDown
import androidx.compose.foundation.gestures.waitForUpOrCancellation
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.sizeIn
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.wcc_companion_app.BuildConfig
import kotlin.math.ceil
import kotlin.math.max
import kotlinx.coroutines.Job
import kotlinx.coroutines.launch

/** Hold duration before the About modal opens. */
private const val HOLD_MS = 3_000

/**
 * Contact destinations for About chips.
 * Leave blank for anonymous / public builds (chips stay hidden).
 * Fill locally only when you want in-app contact actions.
 */
private const val CONTACT_EMAIL = ""
private const val CONTACT_LINKEDIN_URL = ""

/** Chip labels — emojis live on the chip, not only in a system toast. */
private const val CHIP_EMAIL = "Found a bug? 🐛🍃"
private const val CHIP_LINKEDIN = "Wanna chat? 💬"

/**
 * Upper-right Profile control: shows version //~ about with a large hitbox.
 * Press-and-hold 3s drains a ring + countdown, then invokes [onHoldComplete].
 */
@Composable
fun ProfileAboutHoldControl(
    onHoldComplete: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val scope = rememberCoroutineScope()
    val view = LocalView.current
    val primary = MaterialTheme.colorScheme.primary
    val onSurface = MaterialTheme.colorScheme.onSurface

    val progress = remember { Animatable(1f) } // 1 = full ring, 0 = empty
    var isHolding by remember { mutableStateOf(false) }
    var holdJob by remember { mutableStateOf<Job?>(null) }

    val secondsLeft = if (isHolding) {
        max(1, ceil(progress.value * 3.0).toInt())
    } else {
        0
    }

    // Generous hitbox (~72dp tall, padded) so plant thumbs don't miss.
    Box(
        modifier = modifier
            .semantics {
                contentDescription =
                    "About WCC Companion. Hold for three seconds to open."
            }
            .sizeIn(minWidth = 96.dp, minHeight = 72.dp)
            .clip(RoundedCornerShape(18.dp))
            .pointerInput(Unit) {
                awaitEachGesture {
                    awaitFirstDown(requireUnconsumed = false)
                    isHolding = true
                    holdJob?.cancel()
                    holdJob = scope.launch {
                        progress.snapTo(1f)
                        progress.animateTo(
                            targetValue = 0f,
                            animationSpec = tween(
                                durationMillis = HOLD_MS,
                                easing = LinearEasing
                            )
                        )
                        isHolding = false
                        @Suppress("DEPRECATION")
                        view.performHapticFeedback(
                            android.view.HapticFeedbackConstants.LONG_PRESS
                        )
                        onHoldComplete()
                        progress.snapTo(1f)
                    }
                    try {
                        waitForUpOrCancellation()
                    } finally {
                        if (holdJob?.isActive == true) {
                            holdJob?.cancel()
                            holdJob = null
                            isHolding = false
                            scope.launch { progress.snapTo(1f) }
                        }
                    }
                }
            }
            .padding(horizontal = 8.dp, vertical = 6.dp),
        contentAlignment = Alignment.CenterEnd
    ) {
        if (isHolding) {
            Box(
                contentAlignment = Alignment.Center,
                modifier = Modifier.size(56.dp)
            ) {
                Canvas(modifier = Modifier.size(56.dp)) {
                    val stroke = 3.5.dp.toPx()
                    val diam = size.minDimension - stroke
                    val topLeft = Offset(
                        (size.width - diam) / 2f,
                        (size.height - diam) / 2f
                    )
                    val arcSize = Size(diam, diam)
                    drawArc(
                        color = primary.copy(alpha = 0.18f),
                        startAngle = -90f,
                        sweepAngle = 360f,
                        useCenter = false,
                        topLeft = topLeft,
                        size = arcSize,
                        style = Stroke(width = stroke, cap = StrokeCap.Round)
                    )
                    drawArc(
                        color = primary,
                        startAngle = -90f,
                        sweepAngle = 360f * progress.value,
                        useCenter = false,
                        topLeft = topLeft,
                        size = arcSize,
                        style = Stroke(width = stroke, cap = StrokeCap.Round)
                    )
                }
                Text(
                    text = "$secondsLeft",
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Black,
                    color = primary
                )
            }
        } else {
            Text(
                text = "${BuildConfig.DISPLAY_VERSION} //~ about",
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                letterSpacing = 0.3.sp,
                color = onSurface.copy(alpha = 0.45f),
                textAlign = TextAlign.End,
                maxLines = 1
            )
        }
    }
}

/**
 * Friendly About / say-hi modal — open via hold on Profile //~ about.
 */
@Composable
fun AboutSoftwareDialog(
    onDismiss: () -> Unit,
) {
    val context = LocalContext.current
    val scroll = rememberScrollState()
    val config = LocalConfiguration.current
    val isLandscape = config.screenWidthDp > config.screenHeightDp
    // Landscape was clipping the top of the dialog — cap height + scroll from the top.
    val maxDialogHeight = (config.screenHeightDp * 0.92f).dp
    val hPad = if (isLandscape) 16.dp else 20.dp
    val vPad = if (isLandscape) 12.dp else 18.dp
    val chipGap = if (isLandscape) 8.dp else 10.dp
    val compactChips = isLandscape

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(
            usePlatformDefaultWidth = false,
            decorFitsSystemWindows = true
        )
    ) {
        Surface(
            modifier = Modifier
                .fillMaxWidth(if (isLandscape) 0.72f else 0.92f)
                .heightIn(max = maxDialogHeight)
                .padding(horizontal = 8.dp),
            shape = RoundedCornerShape(24.dp),
            color = MaterialTheme.colorScheme.surface,
            tonalElevation = 8.dp,
            shadowElevation = 12.dp
        ) {
            Column(
                modifier = Modifier
                    .heightIn(max = maxDialogHeight)
                    .verticalScroll(scroll)
                    .padding(horizontal = hPad, vertical = vPad)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Box(
                        modifier = Modifier
                            .size(if (isLandscape) 34.dp else 40.dp)
                            .clip(CircleShape)
                            .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.15f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Default.Favorite,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.primary,
                            modifier = Modifier.size(if (isLandscape) 18.dp else 20.dp)
                        )
                    }
                    Spacer(Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            "Hey — you're on WCC Companion",
                            fontWeight = FontWeight.Black,
                            fontSize = if (isLandscape) 15.sp else 17.sp,
                            lineHeight = if (isLandscape) 19.sp else 22.sp
                        )
                        Text(
                            "Open Beta ${BuildConfig.DISPLAY_VERSION}",
                            fontSize = 12.sp,
                            fontWeight = FontWeight.SemiBold,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                    IconButton(onClick = onDismiss) {
                        Icon(
                            Icons.Default.Close,
                            contentDescription = "Close",
                            tint = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                        )
                    }
                }

                Spacer(Modifier.height(if (isLandscape) 8.dp else 12.dp))

                Text(
                    "Built for the plant floor — tickets, work orders, and offline outbox when the server blinks.",
                    fontSize = if (isLandscape) 12.sp else 13.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
                    lineHeight = 17.sp
                )

                Spacer(Modifier.height(if (isLandscape) 10.dp else 14.dp))

                Surface(
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.55f),
                    shape = RoundedCornerShape(14.dp)
                ) {
                    if (isLandscape) {
                        // Two-column meta so landscape stays short
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 14.dp, vertical = 10.dp),
                            horizontalArrangement = Arrangement.spacedBy(16.dp)
                        ) {
                            Column(
                                modifier = Modifier.weight(1f),
                                verticalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                AboutMetaRow("Version", BuildConfig.VERSION_NAME)
                                AboutMetaRow("Build", BuildConfig.VERSION_CODE.toString())
                            }
                            Column(
                                modifier = Modifier.weight(1f),
                                verticalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                AboutMetaRow("Channel", BuildConfig.CHANNEL)
                                AboutMetaRow("Edition", BuildConfig.DISPLAY_VERSION)
                            }
                        }
                    } else {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 14.dp, vertical = 12.dp),
                            verticalArrangement = Arrangement.spacedBy(6.dp)
                        ) {
                            AboutMetaRow("Version", BuildConfig.VERSION_NAME)
                            AboutMetaRow("Build", BuildConfig.VERSION_CODE.toString())
                            AboutMetaRow("Channel", BuildConfig.CHANNEL)
                            AboutMetaRow("Edition", BuildConfig.DISPLAY_VERSION)
                        }
                    }
                }

                val emailOk = CONTACT_EMAIL.isNotBlank()
                val linkedInOk = CONTACT_LINKEDIN_URL.isNotBlank()
                if (emailOk || linkedInOk) {
                    Spacer(Modifier.height(if (isLandscape) 12.dp else 18.dp))

                    Text(
                        "SAY HI ANYTIME",
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Black,
                        letterSpacing = 0.8.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.45f)
                    )
                    Spacer(Modifier.height(4.dp))
                    Text(
                        "Tap a chip — no form, no ticket queue.",
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                        lineHeight = 16.sp
                    )
                    Spacer(Modifier.height(if (isLandscape) 8.dp else 12.dp))

                    if (isLandscape) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(chipGap)
                        ) {
                            if (emailOk) {
                                FriendlyToastChip(
                                    modifier = Modifier.weight(1f),
                                    emoji = "🐛🍃",
                                    title = "Found a bug?",
                                    subtitle = CONTACT_EMAIL,
                                    compact = true,
                                    onClick = {
                                        Toast.makeText(context, CHIP_EMAIL, Toast.LENGTH_SHORT)
                                            .show()
                                        val intent = Intent(Intent.ACTION_SENDTO).apply {
                                            data = Uri.parse("mailto:$CONTACT_EMAIL")
                                            putExtra(
                                                Intent.EXTRA_SUBJECT,
                                                "WCC Companion ${BuildConfig.DISPLAY_VERSION}"
                                            )
                                        }
                                        runCatching { context.startActivity(intent) }
                                    }
                                )
                            }
                            if (linkedInOk) {
                                FriendlyToastChip(
                                    modifier = Modifier.weight(1f),
                                    emoji = "💬",
                                    title = "Wanna chat?",
                                    subtitle = "LinkedIn",
                                    compact = true,
                                    onClick = {
                                        Toast.makeText(context, CHIP_LINKEDIN, Toast.LENGTH_SHORT)
                                            .show()
                                        val intent = Intent(
                                            Intent.ACTION_VIEW,
                                            Uri.parse(CONTACT_LINKEDIN_URL)
                                        )
                                        runCatching { context.startActivity(intent) }
                                    }
                                )
                            }
                        }
                    } else {
                        Column(verticalArrangement = Arrangement.spacedBy(chipGap)) {
                            if (emailOk) {
                                FriendlyToastChip(
                                    emoji = "🐛🍃",
                                    title = "Found a bug?",
                                    subtitle = "Email me",
                                    compact = compactChips,
                                    onClick = {
                                        Toast.makeText(context, CHIP_EMAIL, Toast.LENGTH_SHORT)
                                            .show()
                                        val intent = Intent(Intent.ACTION_SENDTO).apply {
                                            data = Uri.parse("mailto:$CONTACT_EMAIL")
                                            putExtra(
                                                Intent.EXTRA_SUBJECT,
                                                "WCC Companion ${BuildConfig.DISPLAY_VERSION}"
                                            )
                                        }
                                        runCatching { context.startActivity(intent) }
                                    }
                                )
                            }
                            if (linkedInOk) {
                                FriendlyToastChip(
                                    emoji = "💬",
                                    title = "Wanna chat?",
                                    subtitle = "LinkedIn",
                                    compact = compactChips,
                                    onClick = {
                                        Toast.makeText(context, CHIP_LINKEDIN, Toast.LENGTH_SHORT)
                                            .show()
                                        val intent = Intent(
                                            Intent.ACTION_VIEW,
                                            Uri.parse(CONTACT_LINKEDIN_URL)
                                        )
                                        runCatching { context.startActivity(intent) }
                                    }
                                )
                            }
                        }
                    }
                }

                Spacer(Modifier.height(4.dp))
                TextButton(
                    onClick = onDismiss,
                    modifier = Modifier.align(Alignment.End)
                ) {
                    Text("Got it ✨", fontWeight = FontWeight.Bold)
                }
                // Keep close CTA fully on-screen in short landscape heights
                Spacer(Modifier.height(if (isLandscape) 6.dp else 2.dp))
            }
        }
    }
}

@Composable
private fun AboutMetaRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(
            label,
            fontSize = 12.sp,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
        )
        Text(
            value,
            fontSize = 12.sp,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.85f)
        )
    }
}

/** Toast-style contact row: big emoji + friendly title (always visible on chip). */
@Composable
private fun FriendlyToastChip(
    emoji: String,
    title: String,
    subtitle: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    compact: Boolean = false,
) {
    val primary = MaterialTheme.colorScheme.primary
    val emojiSize = if (compact) 22.sp else 28.sp
    val titleSize = if (compact) 13.sp else 15.sp
    val subSize = if (compact) 11.sp else 12.sp
    val minH = if (compact) 52.dp else 64.dp
    val vPad = if (compact) 8.dp else 12.dp

    Surface(
        onClick = onClick,
        modifier = modifier.heightIn(min = minH),
        shape = RoundedCornerShape(18.dp),
        color = primary.copy(alpha = 0.12f),
        border = androidx.compose.foundation.BorderStroke(1.dp, primary.copy(alpha = 0.35f))
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 12.dp, vertical = vPad),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = emoji,
                fontSize = emojiSize,
                modifier = Modifier.padding(end = if (compact) 8.dp else 12.dp)
            )
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = title,
                    fontWeight = FontWeight.Black,
                    fontSize = titleSize,
                    color = MaterialTheme.colorScheme.onSurface,
                    maxLines = 1
                )
                Text(
                    text = subtitle,
                    fontSize = subSize,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                    maxLines = if (compact) 1 else 2
                )
            }
        }
    }
}
