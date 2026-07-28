package com.example.wcc_companion_app.ui.profile

import android.content.res.Configuration
import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.animateFloatAsState
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
import androidx.compose.material.icons.filled.AutoAwesome
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.EmojiEvents
import androidx.compose.material.icons.filled.HelpOutline
import androidx.compose.material.icons.filled.MilitaryTech
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Spa
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.WorkspacePremium
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.input.pointer.PointerEventPass
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.input.pointer.positionChange
import androidx.compose.foundation.gestures.awaitEachGesture
import androidx.compose.foundation.gestures.awaitFirstDown
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.PlatformTextStyle
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.LineHeightStyle
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.wcc_companion_app.data.remote.models.AchievementsDataDto
import com.example.wcc_companion_app.data.remote.models.GamifiedTierDto
import com.example.wcc_companion_app.data.remote.models.ManualSkillDto
import com.example.wcc_companion_app.data.remote.models.ProficiencyDto
import kotlin.math.abs

@Composable
fun UserProfileView(
    profileData: Map<String, Any>?,
    achievements: AchievementsDataDto? = null,
    achievementsError: String? = null,
    onExitProfile: () -> Unit,
    onLogout: () -> Unit = {},
    hapticsEnabled: Boolean = true,
    onHapticsEnabledChange: (Boolean) -> Unit = {},
    biometricLockEnabled: Boolean = false,
    biometricAvailable: Boolean = false,
    biometricStatusLabel: String = "",
    onBiometricLockEnabledChange: (Boolean) -> Unit = {},
    onOpenMyWork: (() -> Unit)? = null,
) {
    val scrollState = rememberScrollState()
    var showLogoutDialog by remember { mutableStateOf(false) }
    var selectedProficiency by remember { mutableStateOf<ProficiencyDto?>(null) }
    var showHelp by remember { mutableStateOf(false) }
    var showAboutDialog by remember { mutableStateOf(false) }
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE

    Box(
        modifier = Modifier
            .fillMaxSize()
            .pointerInput(isLandscape) {
                awaitEachGesture {
                    awaitFirstDown(pass = PointerEventPass.Initial)
                    var dragX = 0f
                    var dragY = 0f
                    do {
                        val event = awaitPointerEvent(pass = PointerEventPass.Initial)
                        val change = event.changes.firstOrNull()
                        if (change != null) {
                            dragX += change.positionChange().x
                            dragY += change.positionChange().y
                        }
                    } while (event.changes.any { it.pressed })

                    // Landscape: swipe LEFT closes Profile (entered via swipe RIGHT).
                    // Portrait: swipe UP at end of scroll closes (entered from top).
                    if (isLandscape) {
                        if (dragX < -120f && abs(dragX) > abs(dragY)) {
                            onExitProfile()
                        }
                    } else if (dragY < -100f && scrollState.value >= scrollState.maxValue) {
                        onExitProfile()
                    }
                }
            }
    ) {
        val scrollY = scrollState.value.toFloat()
        val shrinkRange = 400f
        val scrollProgress = (scrollY / shrinkRange).coerceIn(0f, 1f)
        val avatarSize = (160f - scrollProgress * 100f).coerceIn(60f, 160f)

        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(scrollState)
                .statusBarsPadding()
                .navigationBarsPadding()
                .padding(horizontal = 20.dp)
                .padding(bottom = 24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Room for fixed upper-right about control
            Spacer(modifier = Modifier.height(24.dp))
            Spacer(modifier = Modifier.height(16.dp))

            // Premium hero: pulsing halo + dual-ring avatar
            val heroPulse by rememberInfiniteTransition(label = "hero").animateFloat(
                initialValue = 0.55f,
                targetValue = 1f,
                animationSpec = infiniteRepeatable(
                    animation = tween(1800, easing = FastOutSlowInEasing),
                    repeatMode = RepeatMode.Reverse
                ),
                label = "hero_pulse"
            )
            val primary = MaterialTheme.colorScheme.primary
            Box(contentAlignment = Alignment.Center) {
                Box(
                    modifier = Modifier
                        .size((avatarSize + 28f).dp)
                        .background(
                            Brush.radialGradient(
                                listOf(
                                    primary.copy(alpha = 0.22f * heroPulse),
                                    Color.Transparent
                                )
                            ),
                            CircleShape
                        )
                )
                Box(
                    modifier = Modifier
                        .size(avatarSize.dp)
                        .shadow(
                            elevation = 12.dp,
                            shape = CircleShape,
                            ambientColor = primary.copy(alpha = 0.45f * heroPulse),
                            spotColor = primary.copy(alpha = 0.35f * heroPulse)
                        )
                        .clip(CircleShape)
                        .background(
                            Brush.linearGradient(
                                listOf(
                                    primary,
                                    primary.copy(alpha = 0.55f),
                                    Color(0xFF0C4A6E)
                                )
                            )
                        )
                        .border(
                            BorderStroke(3.dp, Color.White.copy(alpha = 0.35f)),
                            CircleShape
                        )
                        .border(
                            BorderStroke(1.5.dp, primary.copy(alpha = 0.9f)),
                            CircleShape
                        ),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        Icons.Default.Person,
                        contentDescription = "Profile",
                        modifier = Modifier.size((avatarSize * 0.55f).dp),
                        tint = Color.White
                    )
                }
            }

            Spacer(modifier = Modifier.height(18.dp))

            Text(
                profileData?.get("name")?.toString() ?: "---",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Black,
                color = MaterialTheme.colorScheme.primary
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                "Badge ${profileData?.get("badge")?.toString() ?: "---"}",
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f),
                fontSize = 15.sp,
                fontWeight = FontWeight.SemiBold,
                letterSpacing = 0.4.sp
            )

            Spacer(modifier = Modifier.height(12.dp))

            Surface(
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.9f),
                shape = RoundedCornerShape(50),
                border = BorderStroke(1.dp, primary.copy(alpha = 0.35f)),
                shadowElevation = 2.dp
            ) {
                Text(
                    profileData?.get("role")?.toString()
                        ?: profileData?.get("role_name")?.toString()
                        ?: "---",
                    modifier = Modifier.padding(horizontal = 24.dp, vertical = 8.dp),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onPrimaryContainer
                )
            }

            Spacer(modifier = Modifier.height(32.dp))

            // Floor-tech KPIs only (companion cannot announce/raise tickets):
            //  - Interventions = real ticket_actions you logged
            //  - Avg wrench   = mean duration of those sessions
            //  - Live as PIC  = open/pending/escalated/hold tickets with you as PIC
            //  - Closed out   = tickets you closed (closed_by)
            ProfileCard(title = "PERFORMANCE DASHBOARD") {
                Column(verticalArrangement = Arrangement.spacedBy(16.dp)) {
                    Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                        StatBox(
                            modifier = Modifier.weight(1f),
                            value = profileData?.get("interventions")?.toString() ?: "-",
                            label = "INTERVENTIONS"
                        )
                        StatBox(
                            modifier = Modifier.weight(1f),
                            value = profileData?.get("avg_wrench_time")?.toString() ?: "-",
                            label = "AVG WRENCH TIME"
                        )
                    }
                    Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                        StatBox(
                            modifier = Modifier.weight(1f),
                            value = profileData?.get("tickets_as_pic")?.toString() ?: "0",
                            label = "LIVE AS PIC"
                        )
                        StatBox(
                            modifier = Modifier.weight(1f),
                            value = profileData?.get("tickets_closed")?.toString() ?: "-",
                            label = "TICKETS CLOSED OUT"
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(24.dp))

            // Career totals across full history (not shift-scoped).
            val life = achievements?.lifetime
            ProfileCard(title = "LIFETIME") {
                Column(verticalArrangement = Arrangement.spacedBy(16.dp)) {
                    Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                        StatBox(
                            modifier = Modifier.weight(1f),
                            value = life?.tickets_worked?.toString() ?: "—",
                            label = "TICKETS WORKED"
                        )
                        StatBox(
                            modifier = Modifier.weight(1f),
                            value = life?.total_wrench_label ?: "—",
                            label = "TOTAL WRENCH"
                        )
                    }
                    Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                        StatBox(
                            modifier = Modifier.weight(1f),
                            value = life?.tickets_closed?.toString() ?: "—",
                            label = "CLOSED OUT"
                        )
                        StatBox(
                            modifier = Modifier.weight(1f),
                            value = life?.work_orders_completed?.toString() ?: "—",
                            label = "WOs COMPLETED"
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(24.dp))

            // ── ACHIEVEMENTS ──
            AchievementsSection(
                achievements = achievements,
                error = achievementsError,
                onOpenHelp = { showHelp = true },
                onSelect = { selectedProficiency = it }
            )

            Spacer(modifier = Modifier.height(24.dp))

            // ── CERTIFICATIONS ──
            CertificationsSection(
                skills = achievements?.manual_skills.orEmpty(),
                error = if (achievements == null) achievementsError else null
            )

            Spacer(modifier = Modifier.height(24.dp))

            ProfileCard(title = "DEVICE SETTINGS") {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                "Haptic feedback",
                                fontWeight = FontWeight.Bold,
                                fontSize = 14.sp
                            )
                            Text(
                                "Nav ticks + warn rumble on the floor menu",
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                            )
                        }
                        Switch(
                            checked = hapticsEnabled,
                            onCheckedChange = onHapticsEnabledChange
                        )
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                "Biometric app lock",
                                fontWeight = FontWeight.Bold,
                                fontSize = 14.sp
                            )
                            Text(
                                if (biometricAvailable) {
                                    "Fingerprint / face / device PIN — this phone only, no plant sync"
                                } else {
                                    biometricStatusLabel.ifBlank {
                                        "Unavailable on this device"
                                    }
                                },
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                            )
                        }
                        Switch(
                            checked = biometricLockEnabled && biometricAvailable,
                            enabled = biometricAvailable,
                            onCheckedChange = onBiometricLockEnabledChange
                        )
                    }
                    if (onOpenMyWork != null) {
                        Surface(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { onOpenMyWork() },
                            color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f),
                            shape = RoundedCornerShape(12.dp),
                            border = BorderStroke(
                                1.dp,
                                MaterialTheme.colorScheme.primary.copy(alpha = 0.35f)
                            )
                        ) {
                            Text(
                                "Open My Work",
                                modifier = Modifier
                                    .padding(vertical = 14.dp)
                                    .fillMaxWidth(),
                                textAlign = TextAlign.Center,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.primary
                            )
                        }
                    }
                    com.example.wcc_companion_app.ui.components.AppVersionFooter(
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            }

            Spacer(modifier = Modifier.height(32.dp))

            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable { showLogoutDialog = true },
                color = Color(0xFFEF4444).copy(alpha = 0.1f),
                shape = RoundedCornerShape(16.dp),
                border = BorderStroke(1.dp, Color(0xFFEF4444).copy(alpha = 0.3f))
            ) {
                Text(
                    text = "Sign Out",
                    modifier = Modifier
                        .padding(vertical = 16.dp)
                        .fillMaxWidth(),
                    textAlign = TextAlign.Center,
                    color = Color(0xFFEF4444),
                    fontWeight = FontWeight.Bold,
                    fontSize = 16.sp
                )
            }

            Spacer(modifier = Modifier.height(100.dp))
        }

        // Fixed upper-right: version //~ about — hold 3s to open About modal
        com.example.wcc_companion_app.ui.components.ProfileAboutHoldControl(
            onHoldComplete = { showAboutDialog = true },
            modifier = Modifier
                .align(Alignment.TopEnd)
                .statusBarsPadding()
                .padding(top = 4.dp, end = 8.dp)
        )
    }

    if (showAboutDialog) {
        com.example.wcc_companion_app.ui.components.AboutSoftwareDialog(
            onDismiss = { showAboutDialog = false }
        )
    }

    selectedProficiency?.let { prof ->
        ProficiencyDetailDialog(
            proficiency = prof,
            onDismiss = { selectedProficiency = null }
        )
    }

    if (showHelp) {
        AchievementsHelpDialog(
            ladder = achievements?.ladder.orEmpty(),
            onDismiss = { showHelp = false }
        )
    }

    if (showLogoutDialog) {
        AlertDialog(
            onDismissRequest = { showLogoutDialog = false },
            title = {
                Text("Sign Out of WCC Companion?", fontWeight = FontWeight.Black)
            },
            text = {
                Text(
                    "You will need to reconnect to the workshop server to access tickets.",
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f)
                )
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        showLogoutDialog = false
                        onLogout()
                    }
                ) {
                    Text("Log Out", color = Color(0xFFEF4444), fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = {
                TextButton(onClick = { showLogoutDialog = false }) {
                    Text("Cancel")
                }
            },
            containerColor = MaterialTheme.colorScheme.surface,
            shape = RoundedCornerShape(24.dp)
        )
    }
}

@Composable
private fun AchievementsSection(
    achievements: AchievementsDataDto?,
    error: String?,
    onOpenHelp: () -> Unit,
    onSelect: (ProficiencyDto) -> Unit
) {
    val summary = achievements?.summary
    val list = achievements?.proficiencies.orEmpty()

    ProfileCard(
        title = "ACHIEVEMENTS",
        trailing = {
            IconButton(onClick = onOpenHelp, modifier = Modifier.size(32.dp)) {
                Icon(
                    Icons.Default.HelpOutline,
                    contentDescription = "How achievements work",
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(20.dp)
                )
            }
        }
    ) {
        if (error != null && achievements == null) {
            Text(
                error,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                fontSize = 13.sp
            )
            return@ProfileCard
        }

        if (summary != null && summary.proficiency_count > 0) {
            Text(
                buildString {
                    append("${summary.proficiency_count} unlocked")
                    if (summary.expert_count > 0) append(" · ${summary.expert_count} Expert")
                    if (summary.master_count > 0) append(" · ${summary.master_count} Master")
                },
                fontSize = 12.sp,
                fontWeight = FontWeight.SemiBold,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                modifier = Modifier.padding(bottom = 12.dp)
            )
        }

        if (list.isEmpty()) {
            Text(
                "Close interventions to unlock machine badges. Wrench time on mapped equipment categories earns tiers automatically.",
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                fontSize = 13.sp,
                lineHeight = 18.sp
            )
        } else {
            val isLandscape =
                LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE
            val columns = if (isLandscape) 3 else 2
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                list.chunked(columns).forEach { row ->
                    Row(
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        row.forEach { p ->
                            AchievementMedal(
                                proficiency = p,
                                modifier = Modifier.weight(1f),
                                compact = isLandscape,
                                onClick = { onSelect(p) }
                            )
                        }
                        repeat(columns - row.size) {
                            Spacer(modifier = Modifier.weight(1f))
                        }
                    }
                }
            }
        }
    }
}

/**
 * Dual-icon stack for achievements:
 *  - **Tier**: Material vector on a perfect circle (no hexagonal medal-emoji plate).
 *  - **Category**: Skill Configurator emoji on a satellite disc (uncut by tier clip).
 *  - **Rarity sheen**: every tier gets a grade of glow/shimmer (Novice → Master).
 */
@Composable
private fun DualIconBadge(
    tier: String,
    categoryIcon: String,
    tierColor: Color,
    bezelSize: Dp = 72.dp,
    satelliteSize: Dp = 32.dp,
    tierIconSize: Dp = 34.dp,
    categoryFontSp: Int = 15
) {
    val host = bezelSize + satelliteSize * 0.45f
    val disc = MaterialTheme.colorScheme.surface
    val tierKey = tier.trim().lowercase()
    val sheen = tierSheenSpec(tierKey)
    val emojiStyle = TextStyle(
        textAlign = TextAlign.Center,
        platformStyle = PlatformTextStyle(includeFontPadding = false),
        lineHeightStyle = LineHeightStyle(
            alignment = LineHeightStyle.Alignment.Center,
            trim = LineHeightStyle.Trim.Both
        )
    )

    val shimmer = if (sheen.animated) {
        val t = rememberInfiniteTransition(label = "badge_sheen_$tierKey")
        t.animateFloat(
            initialValue = 0.35f,
            targetValue = 1f,
            animationSpec = infiniteRepeatable(
                animation = tween(sheen.periodMs, easing = FastOutSlowInEasing),
                repeatMode = RepeatMode.Reverse
            ),
            label = "sheen_pulse"
        ).value
    } else {
        1f
    }

    Box(
        modifier = Modifier.size(host),
        contentAlignment = Alignment.Center
    ) {
        // Outer rarity halo (all tiers — intensity scales)
        Box(
            modifier = Modifier
                .size(bezelSize + sheen.haloExtra)
                .align(Alignment.Center)
                .shadow(
                    elevation = sheen.elevation,
                    shape = CircleShape,
                    ambientColor = tierColor.copy(alpha = sheen.glow * shimmer),
                    spotColor = tierColor.copy(alpha = sheen.glow * 0.85f * shimmer)
                )
                .background(
                    Brush.radialGradient(
                        listOf(
                            tierColor.copy(alpha = sheen.halo * shimmer),
                            Color.Transparent
                        )
                    ),
                    CircleShape
                )
        )

        // Perfect circular bezel — solid fill, tier-colored ring
        Box(
            modifier = Modifier
                .size(bezelSize)
                .align(Alignment.Center)
                .clip(CircleShape)
                .background(disc)
                .border(
                    BorderStroke(
                        width = sheen.ringWidth,
                        color = tierColor.copy(alpha = 0.75f + 0.25f * shimmer)
                    ),
                    CircleShape
                ),
            contentAlignment = Alignment.Center
        ) {
            // Soft inner wash
            Box(
                modifier = Modifier
                    .matchParentSize()
                    .background(
                        Brush.radialGradient(
                            listOf(
                                tierColor.copy(alpha = 0.12f + sheen.innerWash * shimmer),
                                Color.Transparent
                            )
                        )
                    )
            )
            // Specular diagonal sheen (Proficient+)
            if (sheen.specAlpha > 0f) {
                Box(
                    modifier = Modifier
                        .matchParentSize()
                        .background(
                            Brush.linearGradient(
                                colors = listOf(
                                    Color.Transparent,
                                    Color.White.copy(alpha = sheen.specAlpha * shimmer),
                                    Color.Transparent
                                ),
                                start = Offset.Zero,
                                end = Offset(220f, 220f)
                            )
                        )
                )
            }
            Icon(
                imageVector = tierMaterialIcon(tier),
                contentDescription = tier,
                tint = tierColor,
                modifier = Modifier.size(tierIconSize)
            )
        }

        // Category satellite — sibling of bezel so CircleShape clip never cuts it
        Box(
            modifier = Modifier
                .align(Alignment.BottomEnd)
                .size(satelliteSize)
                .shadow(4.dp, CircleShape, ambientColor = tierColor.copy(alpha = sheen.glow * 0.5f))
                .clip(CircleShape)
                .background(Color(0xFF0B1220))
                .border(
                    BorderStroke(2.dp, tierColor.copy(alpha = 0.85f + 0.15f * shimmer)),
                    CircleShape
                ),
            contentAlignment = Alignment.Center
        ) {
            Text(
                text = normalizeEmoji(categoryIcon, fallback = "⚙️"),
                style = emojiStyle.copy(fontSize = categoryFontSp.sp),
                maxLines = 1
            )
        }
    }
}

/** Per-tier rarity presentation — every rank feels graded, Master feels legendary. */
private data class TierSheenSpec(
    val glow: Float,
    val halo: Float,
    val haloExtra: Dp,
    val elevation: Dp,
    val ringWidth: Dp,
    val innerWash: Float,
    val specAlpha: Float,
    val animated: Boolean,
    val periodMs: Int
)

private fun tierSheenSpec(tierKey: String): TierSheenSpec = when (tierKey) {
    "master" -> TierSheenSpec(
        glow = 0.55f, halo = 0.28f, haloExtra = 14.dp, elevation = 14.dp,
        ringWidth = 3.5.dp, innerWash = 0.22f, specAlpha = 0.18f,
        animated = true, periodMs = 1400
    )
    "expert" -> TierSheenSpec(
        glow = 0.42f, halo = 0.20f, haloExtra = 10.dp, elevation = 10.dp,
        ringWidth = 3.dp, innerWash = 0.16f, specAlpha = 0.12f,
        animated = true, periodMs = 1600
    )
    "proficient" -> TierSheenSpec(
        glow = 0.32f, halo = 0.14f, haloExtra = 8.dp, elevation = 8.dp,
        ringWidth = 2.5.dp, innerWash = 0.12f, specAlpha = 0.06f,
        animated = true, periodMs = 2000
    )
    "competent" -> TierSheenSpec(
        glow = 0.24f, halo = 0.10f, haloExtra = 6.dp, elevation = 6.dp,
        ringWidth = 2.5.dp, innerWash = 0.10f, specAlpha = 0.04f,
        animated = false, periodMs = 0
    )
    "advanced" -> TierSheenSpec(
        glow = 0.28f, halo = 0.12f, haloExtra = 7.dp, elevation = 7.dp,
        ringWidth = 2.5.dp, innerWash = 0.11f, specAlpha = 0.05f,
        animated = false, periodMs = 0
    )
    else -> TierSheenSpec( // Novice
        glow = 0.14f, halo = 0.06f, haloExtra = 4.dp, elevation = 4.dp,
        ringWidth = 2.dp, innerWash = 0.06f, specAlpha = 0f,
        animated = false, periodMs = 0
    )
}

/** Clean circular tier glyphs — avoids Samsung medal emojis (hexagonal plates). */
private fun tierMaterialIcon(tier: String): ImageVector = when (tier.trim().lowercase()) {
    "master" -> Icons.Filled.WorkspacePremium
    "expert" -> Icons.Filled.AutoAwesome
    "proficient" -> Icons.Filled.EmojiEvents
    "competent" -> Icons.Filled.MilitaryTech
    "advanced" -> Icons.Filled.Star
    else -> Icons.Filled.Spa // Novice
}

@Composable
private fun AchievementMedal(
    proficiency: ProficiencyDto,
    modifier: Modifier = Modifier,
    compact: Boolean = false,
    onClick: () -> Unit
) {
    val tierColor = parseHexColor(proficiency.tier_color)
    var pressed by remember { mutableStateOf(false) }
    val scale by animateFloatAsState(
        targetValue = if (pressed) 0.97f else 1f,
        animationSpec = tween(80),
        label = "medal_press"
    )
    val isMaster = proficiency.tier.equals("Master", ignoreCase = true)
    val sheen = tierSheenSpec(proficiency.tier.trim().lowercase())
    val pad = if (compact) 10.dp else 14.dp
    val bezel = if (compact) 60.dp else 72.dp
    val sat = if (compact) 28.dp else 32.dp

    Surface(
        modifier = modifier
            .scale(scale)
            .shadow(
                elevation = sheen.elevation,
                shape = RoundedCornerShape(20.dp),
                ambientColor = tierColor.copy(alpha = sheen.glow),
                spotColor = tierColor.copy(alpha = sheen.glow * 0.85f)
            )
            .clickable(
                interactionSource = remember { MutableInteractionSource() },
                indication = null,
                onClick = {
                    pressed = true
                    onClick()
                    pressed = false
                }
            ),
        shape = RoundedCornerShape(20.dp),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.55f),
        border = BorderStroke(
            width = if (isMaster) 2.dp else 1.5.dp,
            color = tierColor.copy(alpha = 0.45f + sheen.glow * 0.5f)
        )
    ) {
        Box(
            modifier = Modifier
                .background(
                    Brush.verticalGradient(
                        listOf(
                            tierColor.copy(alpha = 0.10f + sheen.halo),
                            Color.Transparent,
                            tierColor.copy(alpha = 0.04f + sheen.halo * 0.35f)
                        )
                    )
                )
                .padding(pad)
        ) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                DualIconBadge(
                    tier = proficiency.tier,
                    categoryIcon = proficiency.category_icon,
                    tierColor = tierColor,
                    bezelSize = bezel,
                    satelliteSize = sat,
                    tierIconSize = if (compact) 28.dp else 34.dp,
                    categoryFontSp = if (compact) 13 else 15
                )

                Spacer(modifier = Modifier.height(if (compact) 6.dp else 10.dp))

                Text(
                    proficiency.skill_name,
                    fontWeight = FontWeight.Bold,
                    fontSize = if (compact) 12.sp else 13.sp,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                    textAlign = TextAlign.Center,
                    color = MaterialTheme.colorScheme.onSurface,
                    lineHeight = 16.sp,
                    modifier = Modifier.fillMaxWidth()
                )

                Spacer(modifier = Modifier.height(4.dp))

                Text(
                    proficiency.tier.uppercase(),
                    fontWeight = FontWeight.Black,
                    fontSize = 11.sp,
                    color = tierColor,
                    letterSpacing = 0.6.sp
                )

                Text(
                    "${formatHours(proficiency.hours)} h · ${proficiency.category}",
                    fontSize = 10.sp,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                )

                Spacer(modifier = Modifier.height(8.dp))

                if (isMaster || proficiency.next == null) {
                    Text(
                        "MAX TIER",
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold,
                        color = tierColor.copy(alpha = 0.85f)
                    )
                } else {
                    val progress = proficiency.progress_01.toFloat().coerceIn(0f, 1f)
                    LinearProgressIndicator(
                        progress = { progress },
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(6.dp)
                            .clip(RoundedCornerShape(3.dp)),
                        color = tierColor,
                        trackColor = tierColor.copy(alpha = 0.15f),
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        "${formatHours(proficiency.next.remaining_hours ?: 0.0)}h → ${normalizeEmoji(proficiency.next.tier_icon.orEmpty(), "◆")} ${proficiency.next.tier.orEmpty()}",
                        fontSize = 10.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                    )
                }
            }
        }
    }
}

@Composable
private fun CertificationsSection(
    skills: List<ManualSkillDto>,
    error: String?
) {
    ProfileCard(title = "CERTIFICATIONS") {
        if (skills.isEmpty()) {
            Text(
                error ?: "No certifications on file.",
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f),
                fontSize = 13.sp
            )
        } else {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                skills.forEach { skill ->
                    CertPlaque(skill)
                }
            }
        }
    }
}

@Composable
private fun CertPlaque(skill: ManualSkillDto) {
    val stateColor = parseHexColor(skill.color)
    val expired = skill.state == "expired"
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        color = MaterialTheme.colorScheme.background.copy(alpha = 0.45f),
        border = BorderStroke(1.dp, stateColor.copy(alpha = 0.45f))
    ) {
        Row(
            modifier = Modifier.padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .width(4.dp)
                    .height(40.dp)
                    .clip(RoundedCornerShape(2.dp))
                    .background(stateColor)
            )
            Spacer(modifier = Modifier.width(12.dp))
            Box(
                modifier = Modifier
                    .size(36.dp)
                    .clip(CircleShape)
                    .background(Color(0xFF1E293B).copy(alpha = 0.85f))
                    .border(BorderStroke(1.dp, stateColor.copy(alpha = 0.5f)), CircleShape),
                contentAlignment = Alignment.Center
            ) {
                Text("🛠️", fontSize = 16.sp)
            }
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    skill.name,
                    fontWeight = FontWeight.Bold,
                    fontSize = 14.sp,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                    textDecoration = if (expired) TextDecoration.LineThrough else null,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = if (expired) 0.65f else 1f)
                )
                if (skill.state != "none") {
                    Text(
                        "${skill.icon} ${skill.label}".trim(),
                        fontSize = 11.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = stateColor
                    )
                } else {
                    Text(
                        skill.label.ifBlank { "No expiry" },
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.45f)
                    )
                }
            }
        }
    }
}

@Composable
private fun ProficiencyDetailDialog(
    proficiency: ProficiencyDto,
    onDismiss: () -> Unit
) {
    val tierColor = parseHexColor(proficiency.tier_color)
    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Surface(
            modifier = Modifier
                .fillMaxWidth(0.92f)
                .wrapContentHeight(),
            shape = RoundedCornerShape(28.dp),
            color = MaterialTheme.colorScheme.surface,
            border = BorderStroke(1.5.dp, tierColor.copy(alpha = 0.5f)),
            shadowElevation = 16.dp
        ) {
            Column(
                modifier = Modifier
                    .background(
                        Brush.verticalGradient(
                            listOf(tierColor.copy(alpha = 0.2f), Color.Transparent)
                        )
                    )
                    .padding(24.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "Close")
                    }
                }

                DualIconBadge(
                    tier = proficiency.tier,
                    categoryIcon = proficiency.category_icon,
                    tierColor = tierColor,
                    bezelSize = 100.dp,
                    satelliteSize = 40.dp,
                    tierIconSize = 48.dp,
                    categoryFontSp = 18
                )

                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    proficiency.skill_name,
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Black,
                    textAlign = TextAlign.Center
                )
                Text(
                    "${proficiency.category} · ${proficiency.tier.uppercase()}",
                    color = tierColor,
                    fontWeight = FontWeight.Bold,
                    fontSize = 14.sp
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    "${formatHours(proficiency.hours)} hours logged",
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Black,
                    color = MaterialTheme.colorScheme.onSurface
                )

                Spacer(modifier = Modifier.height(16.dp))
                if (proficiency.next != null) {
                    LinearProgressIndicator(
                        progress = { proficiency.progress_01.toFloat().coerceIn(0f, 1f) },
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(10.dp)
                            .clip(RoundedCornerShape(5.dp)),
                        color = tierColor,
                        trackColor = tierColor.copy(alpha = 0.15f)
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        "${formatHours(proficiency.next.remaining_hours ?: 0.0)}h to ${proficiency.next.tier_icon} ${proficiency.next.tier}",
                        fontSize = 13.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f)
                    )
                } else {
                    Text(
                        "Top tier reached",
                        fontWeight = FontWeight.Bold,
                        color = tierColor
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    proficiency.tier_blurb,
                    textAlign = TextAlign.Center,
                    fontSize = 14.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
                    lineHeight = 20.sp
                )
                Spacer(modifier = Modifier.height(12.dp))
                Text(
                    "Earned from closed interventions on this equipment category. Both the tier medal and the category icon come from the plant skill ladder.",
                    textAlign = TextAlign.Center,
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                    lineHeight = 16.sp
                )
            }
        }
    }
}

@Composable
private fun AchievementsHelpDialog(
    ladder: List<GamifiedTierDto>,
    onDismiss: () -> Unit
) {
    val tiers = ladder.ifEmpty {
        listOf(
            GamifiedTierDto(200, "Master", "👑", "#eab308", "Deep specialist."),
            GamifiedTierDto(100, "Expert", "💎", "#a855f7", "Hard faults unaided."),
            GamifiedTierDto(40, "Proficient", "🥇", "#3b82f6", "Routine + most non-routine."),
            GamifiedTierDto(20, "Competent", "🥈", "#10b981", "Unsupervised standard faults."),
            GamifiedTierDto(10, "Advanced", "🥉", "#f97316", "Past the basics."),
            GamifiedTierDto(0, "Novice", "🌱", "#94a3b8", "Getting started.")
        )
    }
    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Text("How Achievements Work", fontWeight = FontWeight.Black)
        },
        text = {
            Column(
                modifier = Modifier
                    .heightIn(max = 420.dp)
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text(
                    "Proficiencies are earned automatically from closed intervention wrench time on equipment categories mapped in the Skill Configurator. Each badge shows the tier medal and the category icon.",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
                    lineHeight = 18.sp
                )
                tiers.forEach { t ->
                    val c = parseHexColor(t.color)
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(12.dp))
                            .background(c.copy(alpha = 0.08f))
                            .border(BorderStroke(1.dp, c.copy(alpha = 0.35f)), RoundedCornerShape(12.dp))
                            .padding(10.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(t.icon, fontSize = 22.sp)
                        Spacer(modifier = Modifier.width(10.dp))
                        Column {
                            Text(
                                "${t.tier} · ${if (t.min == 0) "under 10 h" else "${t.min}+ h"}",
                                fontWeight = FontWeight.Bold,
                                color = c,
                                fontSize = 13.sp
                            )
                            Text(
                                t.blurb,
                                fontSize = 11.sp,
                                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f)
                            )
                        }
                    }
                }
                Text(
                    "Certifications are separate — admin-granted, may expire. They are not earned from wrench time.",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
                )
            }
        },
        confirmButton = {
            TextButton(onClick = onDismiss) {
                Text("Got it", fontWeight = FontWeight.Bold)
            }
        },
        shape = RoundedCornerShape(24.dp)
    )
}

@Composable
private fun ProfileCard(
    title: String,
    trailing: @Composable (() -> Unit)? = null,
    content: @Composable () -> Unit
) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.5f),
        shape = RoundedCornerShape(24.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline.copy(alpha = 0.15f))
    ) {
        Column(modifier = Modifier.padding(24.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
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
            Spacer(modifier = Modifier.height(16.dp))
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(1.dp)
                    .background(MaterialTheme.colorScheme.outline.copy(alpha = 0.1f))
            )
            Spacer(modifier = Modifier.height(16.dp))
            content()
        }
    }
}

@Composable
private fun StatBox(modifier: Modifier = Modifier, value: String, label: String) {
    Surface(
        modifier = modifier,
        color = MaterialTheme.colorScheme.background.copy(alpha = 0.5f),
        shape = RoundedCornerShape(16.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline.copy(alpha = 0.15f))
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(16.dp)
        ) {
            Text(
                value,
                fontSize = 28.sp,
                fontWeight = FontWeight.Black,
                color = MaterialTheme.colorScheme.primary
            )
            Text(
                label,
                fontSize = 10.sp,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
            )
        }
    }
}

private fun parseHexColor(hex: String): Color {
    return try {
        val cleaned = hex.trim().removePrefix("#")
        val value = when (cleaned.length) {
            6 -> cleaned.toLong(16) or 0xFF000000L
            8 -> cleaned.toLong(16)
            else -> 0xFF94A3B8L
        }
        Color(value)
    } catch (_: Exception) {
        Color(0xFF94A3B8)
    }
}

private fun formatHours(h: Double): String {
    return if (h >= 10) h.toInt().toString()
    else String.format("%.1f", h).trimEnd('0').trimEnd('.')
}

/** Keep emoji displayable; strip empty / replacement chars. */
private fun normalizeEmoji(raw: String?, fallback: String): String {
    val t = raw?.trim().orEmpty()
    if (t.isEmpty() || t == "?" || t == "??" || t.contains('\uFFFD')) return fallback
    return t
}
