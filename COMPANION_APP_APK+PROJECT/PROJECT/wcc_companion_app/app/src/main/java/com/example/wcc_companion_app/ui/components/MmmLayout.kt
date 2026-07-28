package com.example.wcc_companion_app.ui.components

import androidx.activity.compose.BackHandler
import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.gestures.detectDragGestures
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.KeyboardArrowDown
import androidx.compose.material.icons.filled.KeyboardArrowLeft
import androidx.compose.material.icons.filled.KeyboardArrowRight
import androidx.compose.material.icons.filled.KeyboardArrowUp
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Swipe
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.rotate
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalHapticFeedback
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.IntOffset
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.zIndex
import com.example.wcc_companion_app.ui.theme.WccError
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlin.math.abs
import kotlin.math.roundToInt

data class MmmCategory(
    val id: String,
    val title: String,
    val icon: ImageVector,
    val description: String = "",
    val color: Color
)

enum class NavDepth {
    /** Panels reached off the ends of the category rail / depth axis. */
    MY_SHIFT, PROFILE, CATEGORY, ITEM, SEARCH;

    /** True for full-surface panels that own their own gestures (drag canvas disabled). */
    val isPanel: Boolean get() = this == PROFILE || this == MY_SHIFT || this == SEARCH
}

/** Idle coach lean direction (main menu only). Order is the cycle order. */
private enum class IdleCoachDir {
    LEFT, RIGHT, UP, DOWN
}

/** Main-menu tap WARN — floor copy until localization strings land. */
internal const val NAV_SWIPE_WARN =
    "Navigation is by swiping — tap ? to learn the gestures."

private const val NAV_WARN_DEBOUNCE_MS = 2_500L
/** How long the icon stays red after the shake (banner matches this). */
private const val WARN_RED_HOLD_MS = 2_200L
private const val IDLE_COACH_MS = 5_000L
private const val IDLE_LEAN_MS = 1_100
private const val IDLE_HOLD_MS = 400
private const val IDLE_GAP_MS = 320

/** Category focus scale range (original MMM feel). */
private const val CAT_SCALE_MAX = 1.5f
private const val CAT_SCALE_MIN = 0.8f
private const val CAT_DISC_BASE = 86f
/** Fixed vertical slot for the disc so growth is centered and never crops. */
private const val CAT_DISC_SLOT = CAT_DISC_BASE * CAT_SCALE_MAX // 129f

/**
 * Lets the host UI (top bar buttons) drive MM navigation from outside the gesture canvas.
 * Every gesture-reachable surface therefore also has a tap entry point, which is what
 * guarantees reachability regardless of orientation.
 */
@Stable
class MmmNavController {
    internal var requested by mutableStateOf<NavDepth?>(null)
        private set

    /** Jump category rail to this id (e.g. after scan → tickets). */
    internal var requestedCategoryId by mutableStateOf<String?>(null)
        private set

    /** Mirrors MmmLayout's live depth so host chrome (the top bar) can react to it. */
    var currentDepth by mutableStateOf(NavDepth.CATEGORY)
        internal set

    fun open(target: NavDepth) { requested = target }

    fun focusCategory(categoryId: String) {
        requestedCategoryId = categoryId
    }

    internal fun consume() { requested = null }

    internal fun consumeCategory() { requestedCategoryId = null }
}

@Composable
fun rememberMmmNavController(): MmmNavController = remember { MmmNavController() }

// ─────────────────────────────────────────────────────────────────────────────
// GESTURE TUNING — calibrated for gloved, industrial use.
//
// Why the rail used to bounce back and forth:
//   1. The snap spring ran at dampingRatio 0.85, which is UNDER-damped by definition:
//      the value overshoots the rest position and oscillates before settling. Android's
//      own default is Spring.DampingRatioNoBouncy (1.0) = critically damped, zero overshoot.
//   2. The commit threshold was spacing * 0.15 ≈ 24px ≈ 8.5dp on this device — which is
//      essentially Android's 8dp touch slop. Anything that registered as a drag at all
//      immediately flipped the index, so a gloved hand or machine vibration re-triggered it.
//
// References: Android ViewConfiguration (touch slop 8dp, minimum fling velocity 50dp/s);
// Compose spring defaults; Material motion (~300ms transitions, >400ms reads sluggish);
// industrial-HMI guidance to *lower* sensitivity to imprecise gloved input, not raise it.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * The rail moves at 35% of finger travel instead of 1:1. This is the single biggest
 * anti-wobble lever: with 1:1 tracking every tremor, glove slip or machine vibration
 * moved the carousel by the same number of pixels, so the whole rail visibly shook.
 *
 * Tuned on the floor: 1.0 -> 0.45 -> 0.35, the last step being a further ~1.3x slowdown
 * to reach the agreed visual sweet spot.
 */
private const val DRAG_RESISTANCE = 0.35f

/**
 * Dead-zone before the rail moves at all. Android's touch slop (8dp) only decides
 * "is this a drag" — it is far too small to absorb a gloved hand on a vibrating machine,
 * so nothing moves until the finger has clearly committed.
 */
private const val DRAG_ACTIVATION_DP = 16f


/**
 * Deliberate-flick threshold. Android's 50dp/s minimum fling is tuned for a bare
 * fingertip on a handheld; on the floor that fires on accidental twitches, so this sits
 * well above it and only a real flick counts.
 */
private const val FLING_DP_PER_SECOND = 200f


/**
 * Decides the index to settle on.
 *
 * Settles on whichever item is ACTUALLY NEAREST the rail's resting position — the exact
 * same rounding the focus ring uses — so you always land on the icon that was
 * highlighted, no matter how far you dragged.
 *
 * This previously returned only `startIndex ± 1`. A long swipe that visually travelled
 * past two icons therefore left the rail sitting on start+2 while the snap target was
 * computed as start+1, and the menu animated *backwards* onto the icon you had already
 * scrolled past. Rounding to nearest removes the ±1 clamp entirely.
 *
 * Nearest-rounding also subsumes the old positional commit threshold: rounding flips at
 * exactly half an item, which is the same 0.5 rule, so there is no separate constant.
 */
private fun resolveTargetIndex(
    startIndex: Int,
    currentScroll: Float,
    spacing: Float,
    velocityDpPerSecond: Float,
    lastIndex: Int
): Int {
    val nearest = (currentScroll / spacing).roundToInt()

    // A fast flick that didn't travel far enough to change the nearest item still
    // advances one step. Guarded on `nearest == startIndex` so a long fast swipe isn't
    // pushed a further step past the icon it already landed on.
    val target = if (nearest == startIndex && abs(velocityDpPerSecond) >= FLING_DP_PER_SECOND) {
        if (velocityDpPerSecond > 0) startIndex + 1 else startIndex - 1
    } else {
        nearest
    }
    return target.coerceIn(0, lastIndex.coerceAtLeast(0))
}

@Composable
fun <T> MmmLayout(
    isDark: Boolean,
    categories: List<MmmCategory>,
    itemsProvider: (MmmCategory) -> List<T>,
    itemContent: @Composable (T, Boolean) -> Unit,
    onItemSelected: (T) -> Unit,
    onRefresh: () -> Unit = {},
    onLogout: () -> Unit = {},
    snackbarHostState: SnackbarHostState = remember { SnackbarHostState() },
    // Rail-end panels. Supplied as slots so MmmLayout stays generic over T while the
    // caller injects real data. Each receives an onClose callback (tap affordance).
    myShiftContent: @Composable (onClose: () -> Unit) -> Unit = {},
    searchContent: @Composable (onClose: () -> Unit) -> Unit = {},
    /**
     * Optional chrome above the item carousel (search / filter chips).
     * Host decides per category — Equipment / Toolings / Inventory show a filter bar;
     * Tickets / WOs / History leave this empty.
     */
    itemFilterBar: @Composable (MmmCategory) -> Unit = {},
    /**
     * When false, portrait item band starts higher (no reserved search-strip gap).
     * True only for Equipment / Toolings / Inventory.
     */
    hasItemFilterBar: (MmmCategory) -> Boolean = { false },
    // Lets the host drive navigation from the top bar (e.g. avatar -> Profile).
    navController: MmmNavController? = null,
    /**
     * Shared ProfileViewModel from the host (MainMmmScreen). Required so Profile and
     * My Shift always hit the same GET /me user — never a second orphan ViewModel.
     */
    profileViewModel: com.example.wcc_companion_app.ui.profile.ProfileViewModel? = null,
    /** Cold-start rail restore from prefs (category id, e.g. "tickets"). */
    initialCategoryId: String? = null,
    /** Persist last focused category when the rail settles. */
    onCategorySettled: (categoryId: String) -> Unit = {},
) {
    var depth by rememberSaveable { mutableStateOf(NavDepth.CATEGORY) }
    var isRefreshing by remember { mutableStateOf(false) }
    
    val scope = rememberCoroutineScope()
    val configuration = LocalConfiguration.current
    val haptic = LocalHapticFeedback.current
    val context = LocalContext.current
    val isLandscape = configuration.orientation == android.content.res.Configuration.ORIENTATION_LANDSCAPE
    val screenWidth = configuration.screenWidthDp.dp
    val screenHeight = configuration.screenHeightDp.dp
    val navSwipeWarn = androidx.compose.ui.res.stringResource(
        com.example.wcc_companion_app.R.string.nav_swipe_warn
    )

    // Debounce main-menu swipe WARN so rapid re-taps don't spam.
    var lastNavWarnAtMs by remember { mutableLongStateOf(0L) }
    var previousDepth by remember { mutableStateOf(depth) }
    /** Which category icon is shaking/red after a dead open-tap. */
    var warnIconIndex by remember { mutableIntStateOf(-1) }
    var warnBannerVisible by remember { mutableStateOf(false) }
    val warnShake = remember { Animatable(0f) }
    // One tick per index cross while dragging (not per pixel).
    var lastHapticCatIndex by remember { mutableIntStateOf(-1) }
    var lastHapticItemIndex by remember { mutableIntStateOf(-1) }

    // ── Idle coach: 5s quiet on main menu → lean + arrow L→R→U→D ──
    var lastInteractionMs by remember { mutableLongStateOf(System.currentTimeMillis()) }
    var coachActive by remember { mutableStateOf(false) }
    var coachDir by remember { mutableStateOf(IdleCoachDir.LEFT) }
    val coachTilt = remember { Animatable(0f) }      // rotationZ degrees
    val coachNudgeX = remember { Animatable(0f) }    // px
    val coachNudgeY = remember { Animatable(0f) }
    val coachArrowAlpha = remember { Animatable(0f) }

    fun bumpIdle() {
        lastInteractionMs = System.currentTimeMillis()
        coachActive = false
        scope.launch {
            coachTilt.snapTo(0f)
            coachNudgeX.snapTo(0f)
            coachNudgeY.snapTo(0f)
            coachArrowAlpha.snapTo(0f)
        }
    }

    fun isStillIdle(): Boolean {
        if (depth != NavDepth.CATEGORY) return false
        if (warnBannerVisible || warnIconIndex >= 0) return false
        return System.currentTimeMillis() - lastInteractionMs >= IDLE_COACH_MS
    }

    /**
     * Red shake + mid banner share one lifetime: banner stays while the icon is red.
     * Total ≈ shake (~450ms) + hold (WARN_RED_HOLD_MS).
     */
    LaunchedEffect(warnIconIndex) {
        if (warnIconIndex < 0) {
            warnShake.snapTo(0f)
            warnBannerVisible = false
            return@LaunchedEffect
        }
        bumpIdle()
        warnBannerVisible = true
        try {
            repeat(5) {
                warnShake.animateTo(14f, animationSpec = tween(45, easing = LinearEasing))
                warnShake.animateTo(-14f, animationSpec = tween(45, easing = LinearEasing))
            }
            warnShake.animateTo(0f, animationSpec = tween(50, easing = LinearEasing))
            // Keep red (and banner) for the same window
            delay(WARN_RED_HOLD_MS)
        } finally {
            warnBannerVisible = false
            if (warnIconIndex >= 0) {
                warnIconIndex = -1
            }
        }
    }

    // Subtle haptic on every depth / panel navigation.
    LaunchedEffect(depth) {
        val prev = previousDepth
        if (prev != depth) {
            bumpIdle()
            WccHaptics.navigate(haptic, context)
            previousDepth = depth
        }
    }

    // Idle coach loop — only main menu, pauses during red warn.
    LaunchedEffect(depth) {
        coachActive = false
        coachTilt.snapTo(0f)
        coachNudgeX.snapTo(0f)
        coachNudgeY.snapTo(0f)
        coachArrowAlpha.snapTo(0f)
        if (depth != NavDepth.CATEGORY) return@LaunchedEffect

        while (true) {
            // Wait until 5s idle
            while (true) {
                val remaining = IDLE_COACH_MS - (System.currentTimeMillis() - lastInteractionMs)
                if (remaining <= 0L && depth == NavDepth.CATEGORY &&
                    !warnBannerVisible && warnIconIndex < 0
                ) break
                delay(remaining.coerceIn(80L, IDLE_COACH_MS))
                if (depth != NavDepth.CATEGORY) return@LaunchedEffect
            }
            if (!isStillIdle()) continue

            coachActive = true
            for (dir in IdleCoachDir.entries) {
                if (!isStillIdle()) break
                coachDir = dir
                val (tilt, nx, ny) = when (dir) {
                    IdleCoachDir.LEFT -> Triple(-14f, -18f, 0f)
                    IdleCoachDir.RIGHT -> Triple(14f, 18f, 0f)
                    IdleCoachDir.UP -> Triple(-6f, 0f, -18f)
                    IdleCoachDir.DOWN -> Triple(6f, 0f, 18f)
                }
                val ease = tween<Float>(IDLE_LEAN_MS, easing = FastOutSlowInEasing)
                // Lean in
                launch { coachTilt.animateTo(tilt, ease) }
                launch { coachNudgeX.animateTo(nx, ease) }
                launch { coachNudgeY.animateTo(ny, ease) }
                coachArrowAlpha.animateTo(0.55f, tween(400, easing = FastOutSlowInEasing))
                delay(IDLE_LEAN_MS.toLong() + IDLE_HOLD_MS)
                if (!isStillIdle()) break
                // Settle
                val settle = tween<Float>(420, easing = FastOutSlowInEasing)
                launch { coachTilt.animateTo(0f, settle) }
                launch { coachNudgeX.animateTo(0f, settle) }
                launch { coachNudgeY.animateTo(0f, settle) }
                coachArrowAlpha.animateTo(0f, tween(280))
                delay(420L + IDLE_GAP_MS)
            }
            coachActive = false
            coachTilt.snapTo(0f)
            coachNudgeX.snapTo(0f)
            coachNudgeY.snapTo(0f)
            coachArrowAlpha.snapTo(0f)
            // After a full cycle, require another full 5s idle (bump clock to "now")
            // so we don't immediately re-fire.
            if (isStillIdle()) {
                lastInteractionMs = System.currentTimeMillis()
            }
        }
    }
    
    // Core Scrolling State (Survives Rotation)
    val savedCategoryScroll = rememberSaveable { mutableFloatStateOf(0f) }
    val savedItemScroll = rememberSaveable { mutableFloatStateOf(0f) }
    var didRestoreLastRail by rememberSaveable { mutableStateOf(false) }
    var railPersistReady by remember { mutableStateOf(false) }
    
    val categoryScroll = remember { Animatable(savedCategoryScroll.floatValue) }
    val itemScroll = remember { Animatable(savedItemScroll.floatValue) }
    
    val categorySpacing = 160f
    val itemSpacing = if (isLandscape) 180f else 320f

    // Cold start: restore last rail from prefs once (SavedState already covers rotation).
    LaunchedEffect(categories, initialCategoryId) {
        if (!didRestoreLastRail) {
            didRestoreLastRail = true
            if (savedCategoryScroll.floatValue == 0f && !initialCategoryId.isNullOrBlank()) {
                val idx = categories.indexOfFirst { it.id == initialCategoryId }
                if (idx > 0) {
                    val target = idx * categorySpacing
                    categoryScroll.snapTo(target)
                    savedCategoryScroll.floatValue = target
                }
            }
        }
        railPersistReady = true
    }
    
    // Focused Indices
    // Focus always lands on the NEAREST icon — plain rounding, no lag.
    //
    // An earlier version deliberately lagged this (only flipping at 65% of the way across)
    // to stop the focus ring oscillating at the midpoint. That fixed the flicker but broke
    // the more important expectation: the highlighted icon must be the one nearest centre.
    // Wobble is now prevented upstream instead — dead-zone, drag resistance and a
    // critically damped spring mean the rail no longer hovers on a boundary at all.
    val focusedCategoryIndex = remember {
        derivedStateOf {
            (categoryScroll.value / categorySpacing)
                .roundToInt()
                .coerceIn(0, (categories.size - 1).coerceAtLeast(0))
        }
    }
    
    val currentCategory = categories.getOrNull(focusedCategoryIndex.value) ?: categories.first()
    val items = itemsProvider(currentCategory)
    
    val focusedItemIndex = remember(items.size, itemScroll.value) {
        derivedStateOf {
            if (items.isEmpty()) 0 else (itemScroll.value / itemSpacing).roundToInt().coerceIn(0, items.size - 1)
        }
    }
    
    // Smooth depth transitions.
    // The rail slides away along whichever axis the panel lives on, so the motion reads
    // the same in both orientations: MY_SHIFT sits before the rail START, SEARCH after
    // the rail END. Rail axis is X in portrait and Y in landscape.
    val warpOffsetY by animateDpAsState(
        targetValue = when (depth) {
            NavDepth.PROFILE -> if (isLandscape) 0.dp else (screenHeight / 2 + 100.dp)
            NavDepth.CATEGORY -> 0.dp
            NavDepth.ITEM -> if (isLandscape) 0.dp else -(screenHeight / 2 - 140.dp)
            NavDepth.MY_SHIFT -> if (isLandscape) (screenHeight / 2 + 100.dp) else 0.dp
            NavDepth.SEARCH -> if (isLandscape) -(screenHeight / 2 + 100.dp) else 0.dp
        },
        animationSpec = spring(dampingRatio = 0.8f, stiffness = 100f),
        label = "warp_y"
    )

    val warpOffsetX by animateDpAsState(
        targetValue = when (depth) {
            NavDepth.PROFILE -> if (isLandscape) -(screenWidth / 2 + 100.dp) else 0.dp
            NavDepth.CATEGORY -> 0.dp
            NavDepth.ITEM -> if (isLandscape) -(screenWidth / 2 - 140.dp) else 0.dp
            NavDepth.MY_SHIFT -> if (isLandscape) 0.dp else (screenWidth / 2 + 100.dp)
            NavDepth.SEARCH -> if (isLandscape) 0.dp else -(screenWidth / 2 + 100.dp)
        },
        animationSpec = spring(dampingRatio = 0.8f, stiffness = 100f),
        label = "warp_x"
    )
    
    val warpScale by animateFloatAsState(
        targetValue = if (depth == NavDepth.CATEGORY) 1f else 0.6f,
        animationSpec = spring(dampingRatio = 0.8f, stiffness = 100f),
        label = "warp_scale"
    )

    // Gesture Accumulators
    var totalDragDepth by remember { mutableStateOf(0f) }
    var overscrollScroll by remember { mutableStateOf(0f) }

    // Drag start timestamp + density, used to derive fling velocity in dp/s on release.
    var dragStartMs by remember { mutableStateOf(0L) }
    val densityScale = androidx.compose.ui.platform.LocalDensity.current.density

    // Raw (undamped) finger travel along the rail, used only for the dead-zone test.
    var rawScrollTravel by remember { mutableStateOf(0f) }
    val activationPx = remember(densityScale) { DRAG_ACTIVATION_DP * densityScale }

    // Critically damped so it settles with NO overshoot, and deliberately slow: on a
    // shop floor a calm, readable settle beats a snappy one.
    val snapSpec = remember {
        spring<Float>(
            dampingRatio = Spring.DampingRatioNoBouncy,
            stiffness = Spring.StiffnessLow
        )
    }

    // Live snapshot of the focused category's items so the gesture detector does
    // NOT restart (and swallow an in-progress swipe) every time async data loads
    // or a refresh toggles. This is what made the first 2 categories feel sticky:
    // Tickets/Work Orders load asynchronously (size changes), the empty categories
    // never do. Reading through rememberUpdatedState keeps the detector alive.
    val currentItems by rememberUpdatedState(items)

    // ── BACK BUTTON HANDLER ──
    // Guaranteed escape hatch from EVERY surface, in either orientation, so a panel can
    // never trap the user if its exit gesture feels awkward on one axis.
    BackHandler(enabled = depth != NavDepth.CATEGORY) {
        depth = NavDepth.CATEGORY
    }

    // ── TAP-DRIVEN NAVIGATION (top bar) ──
    // Keep LaunchedEffect always composed (key on requested). Composing it only
    // inside `requested?.let { }` cancels the effect when consume() clears the
    // request and has caused profile-open races / crashes on some devices.
    val navRequest = navController?.requested
    LaunchedEffect(navRequest) {
        val target = navRequest ?: return@LaunchedEffect
        depth = target
        navController?.consume()
    }

    val catRequest = navController?.requestedCategoryId
    LaunchedEffect(catRequest) {
        val id = catRequest ?: return@LaunchedEffect
        val idx = categories.indexOfFirst { it.id == id }
        if (idx >= 0) {
            val target = idx * categorySpacing
            categoryScroll.snapTo(target)
            savedCategoryScroll.floatValue = target
            depth = NavDepth.CATEGORY
            onCategorySettled(id)
        }
        navController?.consumeCategory()
    }

    // Persist last focused rail for next cold start (after restore so we don't clobber prefs).
    LaunchedEffect(focusedCategoryIndex.value, railPersistReady) {
        if (!railPersistReady) return@LaunchedEffect
        val cat = categories.getOrNull(focusedCategoryIndex.value) ?: return@LaunchedEffect
        onCategorySettled(cat.id)
    }

    // Publish depth so the host can hide its chrome while a full-surface panel is open
    // (panels carry their own header + close button and would otherwise collide).
    LaunchedEffect(depth, navController) {
        navController?.currentDepth = depth
    }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .then(
                if (!depth.isPanel) {
                    Modifier.pointerInput(depth, isLandscape) {
                        detectDragGestures(
                            onDragStart = {
                                bumpIdle()
                                totalDragDepth = 0f
                                overscrollScroll = 0f
                                rawScrollTravel = 0f
                                dragStartMs = System.currentTimeMillis()
                                savedCategoryScroll.floatValue = categoryScroll.value
                                savedItemScroll.floatValue = itemScroll.value
                                lastHapticCatIndex = (categoryScroll.value / categorySpacing)
                                    .roundToInt()
                                    .coerceIn(0, (categories.size - 1).coerceAtLeast(0))
                                lastHapticItemIndex = if (currentItems.isEmpty()) {
                                    0
                                } else {
                                    (itemScroll.value / itemSpacing)
                                        .roundToInt()
                                        .coerceIn(0, currentItems.lastIndex)
                                }
                            },
                            onDrag = { change, dragAmount ->
                                change.consume()
                                // Any real finger motion resets idle coach
                                if (abs(dragAmount.x) + abs(dragAmount.y) > 0.5f) {
                                    lastInteractionMs = System.currentTimeMillis()
                                    if (coachActive) bumpIdle()
                                }

                                val rawScroll = if (isLandscape) dragAmount.y else dragAmount.x
                                val depthDrag = if (isLandscape) dragAmount.x else dragAmount.y

                                // Dead-zone, then resistance: the rail ignores small
                                // movement entirely and afterwards trails the finger at
                                // less than half speed, so tremor and vibration cannot
                                // shake it.
                                rawScrollTravel += rawScroll
                                val scrollDrag = if (abs(rawScrollTravel) < activationPx) {
                                    0f
                                } else {
                                    rawScroll * DRAG_RESISTANCE
                                }

                                if (scrollDrag != 0f && abs(rawScroll) > abs(depthDrag)) {
                                    // Scroll Drag — tick once each time focus index crosses
                                    scope.launch {
                                        if (depth == NavDepth.CATEGORY) {
                                            val newOffset = categoryScroll.value - scrollDrag
                                            if (newOffset < 0f) {
                                                overscrollScroll += scrollDrag
                                                categoryScroll.snapTo(0f)
                                            } else if (newOffset > (categories.size - 1) * categorySpacing) {
                                                overscrollScroll += scrollDrag
                                                categoryScroll.snapTo((categories.size - 1) * categorySpacing)
                                            } else {
                                                categoryScroll.snapTo(newOffset)
                                                overscrollScroll = 0f
                                            }
                                            val idx = (categoryScroll.value / categorySpacing)
                                                .roundToInt()
                                                .coerceIn(0, (categories.size - 1).coerceAtLeast(0))
                                            if (idx != lastHapticCatIndex) {
                                                lastHapticCatIndex = idx
                                                WccHaptics.navigate(haptic, context)
                                            }
                                        } else if (depth == NavDepth.ITEM && currentItems.isNotEmpty()) {
                                            val newOffset = itemScroll.value - scrollDrag
                                            if (newOffset < 0f) {
                                                overscrollScroll += scrollDrag
                                                itemScroll.snapTo(0f)
                                            } else if (newOffset > (currentItems.size - 1) * itemSpacing) {
                                                overscrollScroll += scrollDrag
                                                itemScroll.snapTo((currentItems.size - 1) * itemSpacing)
                                            } else {
                                                itemScroll.snapTo(newOffset)
                                                overscrollScroll = 0f
                                            }
                                            val idx = (itemScroll.value / itemSpacing)
                                                .roundToInt()
                                                .coerceIn(0, currentItems.lastIndex)
                                            if (idx != lastHapticItemIndex) {
                                                lastHapticItemIndex = idx
                                                WccHaptics.navigate(haptic, context)
                                            }
                                        }
                                    }
                                } else if (abs(depthDrag) > abs(rawScroll)) {
                                    // Depth Drag — only when the gesture is genuinely
                                    // perpendicular, so a rail drag sitting inside the
                                    // dead-zone can't leak into the depth accumulator.
                                    totalDragDepth += depthDrag
                                }
                            },
                            onDragEnd = {
                                // 1. Snap Scroll (With Momentum Threshold based on initial drag position)
                                // Fling velocity in dp/s, derived from how far the rail moved
                                // over how long the finger was down.
                                val elapsedSec =
                                    ((System.currentTimeMillis() - dragStartMs).coerceAtLeast(1L)) / 1000f

                                scope.launch {
                                    if (depth == NavDepth.CATEGORY) {
                                        val startIndex = (savedCategoryScroll.floatValue / categorySpacing).roundToInt()
                                        val delta = categoryScroll.value - savedCategoryScroll.floatValue
                                        val velocityDp = (delta / densityScale) / elapsedSec
                                        val targetIndex = resolveTargetIndex(
                                            startIndex = startIndex,
                                            currentScroll = categoryScroll.value,
                                            spacing = categorySpacing,
                                            velocityDpPerSecond = velocityDp,
                                            lastIndex = categories.size - 1
                                        )

                                        categoryScroll.animateTo(
                                            targetValue = targetIndex * categorySpacing,
                                            animationSpec = snapSpec
                                        )
                                        savedCategoryScroll.floatValue = targetIndex * categorySpacing
                                        // Subtle tick when the rail actually lands on a new category
                                        if (targetIndex != startIndex) {
                                            WccHaptics.navigate(haptic, context)
                                        }
                                    } else if (depth == NavDepth.ITEM && currentItems.isNotEmpty()) {
                                        val startIndex = (savedItemScroll.floatValue / itemSpacing).roundToInt()
                                        val delta = itemScroll.value - savedItemScroll.floatValue
                                        val velocityDp = (delta / densityScale) / elapsedSec
                                        val targetIndex = resolveTargetIndex(
                                            startIndex = startIndex,
                                            currentScroll = itemScroll.value,
                                            spacing = itemSpacing,
                                            velocityDpPerSecond = velocityDp,
                                            lastIndex = currentItems.size - 1
                                        )

                                        itemScroll.animateTo(
                                            targetValue = targetIndex * itemSpacing,
                                            animationSpec = snapSpec
                                        )
                                        savedItemScroll.floatValue = targetIndex * itemSpacing
                                        if (targetIndex != startIndex) {
                                            WccHaptics.navigate(haptic, context)
                                        }
                                    }
                                }

                                // 2. DEPTH AXIS — identical semantics in both orientations.
                                //    "in"  = portrait swipe UP    / landscape swipe LEFT
                                //    "out" = portrait swipe DOWN  / landscape swipe RIGHT
                                if (totalDragDepth < -150f) {
                                    if (depth == NavDepth.CATEGORY) {
                                        depth = NavDepth.ITEM
                                        scope.launch { itemScroll.snapTo(0f) }
                                    } else if (depth == NavDepth.ITEM && !isRefreshing) {
                                        // Deep-swipe inside a submenu refreshes it (both orientations).
                                        isRefreshing = true
                                        onRefresh()
                                        scope.launch { delay(1500); isRefreshing = false }
                                    }
                                } else if (totalDragDepth > 150f) {
                                    if (depth == NavDepth.ITEM) {
                                        depth = NavDepth.CATEGORY
                                    } else if (depth == NavDepth.CATEGORY) {
                                        // Pulling back out of the main menu opens Profile.
                                        depth = NavDepth.PROFILE
                                    }
                                }

                                // 3. RAIL-END OVERSCROLL — now active in BOTH orientations.
                                //    overscrollScroll is positive past the rail START and negative
                                //    past the rail END, whichever axis the rail is on:
                                //      portrait  : far-LEFT + swipe RIGHT -> MY_SHIFT
                                //                  far-RIGHT + swipe LEFT -> SEARCH
                                //      landscape : TOP + swipe DOWN       -> MY_SHIFT
                                //                  BOTTOM + swipe UP      -> SEARCH
                                if (depth == NavDepth.CATEGORY) {
                                    if (overscrollScroll > 80f && focusedCategoryIndex.value == 0) {
                                        depth = NavDepth.MY_SHIFT
                                    } else if (overscrollScroll < -80f && focusedCategoryIndex.value == categories.size - 1) {
                                        depth = NavDepth.SEARCH
                                    }
                                } else if (depth == NavDepth.ITEM) {
                                    val itemIdx = if (currentItems.isEmpty()) 0
                                                  else (itemScroll.value / itemSpacing).roundToInt().coerceIn(0, currentItems.size - 1)
                                    if (overscrollScroll < -80f && itemIdx == currentItems.size - 1 && !isRefreshing) {
                                        isRefreshing = true // Pulled past the end of the submenu list
                                        onRefresh()
                                        scope.launch { delay(1500); isRefreshing = false }
                                    }
                                }
                            }
                        )
                    }
                } else Modifier
            )
    ) {
        WccWaveBackground(isDark)

        // Prefer host-injected ProfileViewModel (same instance as My Shift / MainMmmScreen).
        val resolvedProfileVm = profileViewModel
            ?: androidx.hilt.navigation.compose.hiltViewModel()
        val profileData by resolvedProfileVm.userProfile.collectAsState()
        val profileAchievements by resolvedProfileVm.achievements.collectAsState()
        val profileAchievementsError by resolvedProfileVm.achievementsError.collectAsState()
        val hapticsEnabled by resolvedProfileVm.hapticsEnabled.collectAsState()
        val biometricLockEnabled by resolvedProfileVm.biometricLockEnabled.collectAsState()
        val biometricAvailable = remember(context) {
            com.example.wcc_companion_app.data.security.DeviceBiometricLock.canAuthenticate(context)
        }
        val biometricStatusLabel = remember(context) {
            com.example.wcc_companion_app.data.security.DeviceBiometricLock.statusLabel(context)
        }

        // Re-fetch GET /me whenever Profile opens (same request My Shift uses).
        androidx.compose.runtime.LaunchedEffect(depth) {
            if (depth == NavDepth.PROFILE || depth == NavDepth.MY_SHIFT) {
                resolvedProfileVm.refresh()
            }
        }

        // Profile enter/exit:
        //  - portrait: still from top (depth swipe is vertical "out")
        //  - landscape: swipe RIGHT opens Profile → slide in from LEFT;
        //               swipe LEFT closes → slide out to the left
        AnimatedVisibility(
            visible = depth == NavDepth.PROFILE,
            enter = fadeIn(tween(350)) + (
                if (isLandscape) slideInHorizontally(animationSpec = tween(350)) { -it }
                else slideInVertically(animationSpec = tween(400)) { -it }
            ),
            exit = fadeOut(tween(300)) + (
                if (isLandscape) slideOutHorizontally(animationSpec = tween(300)) { -it }
                else slideOutVertically(animationSpec = tween(400)) { -it }
            ),
            modifier = Modifier.fillMaxSize()
        ) {
            com.example.wcc_companion_app.ui.profile.UserProfileView(
                profileData = profileData,
                achievements = profileAchievements,
                achievementsError = profileAchievementsError,
                onExitProfile = { depth = NavDepth.CATEGORY },
                onLogout = onLogout,
                hapticsEnabled = hapticsEnabled,
                onHapticsEnabledChange = { resolvedProfileVm.setHapticsEnabled(it) },
                biometricLockEnabled = biometricLockEnabled,
                biometricAvailable = biometricAvailable,
                biometricStatusLabel = biometricStatusLabel,
                onBiometricLockEnabledChange = { enable ->
                    if (!enable) {
                        resolvedProfileVm.setBiometricLockEnabled(false)
                    } else {
                        // Prove biometrics work on this device before enabling (local only).
                        val act = context as? androidx.fragment.app.FragmentActivity
                        if (act != null && biometricAvailable) {
                            com.example.wcc_companion_app.data.security.DeviceBiometricLock.prompt(
                                activity = act,
                                title = "Enable app lock",
                                subtitle = "Confirm once — stays on this handset only",
                                onSuccess = {
                                    resolvedProfileVm.setBiometricLockEnabled(true)
                                    com.example.wcc_companion_app.data.security.DeviceBiometricLock
                                        .markUnlocked()
                                },
                            )
                        }
                    }
                },
                onOpenMyWork = { depth = NavDepth.MY_SHIFT },
            )
        }

        // ── RAIL-START PANEL: MY SHIFT ──
        // Slides in from the rail START, so from the left in portrait and from the top
        // in landscape — matching the direction the user overscrolled from.
        AnimatedVisibility(
            visible = depth == NavDepth.MY_SHIFT,
            enter = fadeIn(tween(350)) + (if (isLandscape) slideInVertically(initialOffsetY = { -it })
                                          else slideInHorizontally(initialOffsetX = { -it })),
            exit = fadeOut(tween(300)) + (if (isLandscape) slideOutVertically(targetOffsetY = { -it })
                                          else slideOutHorizontally(targetOffsetX = { -it })),
            modifier = Modifier.fillMaxSize()
        ) {
            myShiftContent { depth = NavDepth.CATEGORY }
        }

        // ── RAIL-END PANEL: SEARCH & SCAN ──
        AnimatedVisibility(
            visible = depth == NavDepth.SEARCH,
            enter = fadeIn(tween(350)) + (if (isLandscape) slideInVertically(initialOffsetY = { it })
                                          else slideInHorizontally(initialOffsetX = { it })),
            exit = fadeOut(tween(300)) + (if (isLandscape) slideOutVertically(targetOffsetY = { it })
                                          else slideOutHorizontally(targetOffsetX = { it })),
            modifier = Modifier.fillMaxSize()
        ) {
            searchContent { depth = NavDepth.CATEGORY }
        }

        AnimatedVisibility(
            visible = depth == NavDepth.ITEM,
            enter = fadeIn(tween(600)) + slideInVertically(initialOffsetY = { it / 3 }),
            exit = fadeOut(tween(500)) + slideOutVertically(targetOffsetY = { it / 3 })
        ) {
            Box(modifier = Modifier.fillMaxSize()) {
                AnimatedVisibility(
                    visible = isRefreshing,
                    enter = fadeIn() + expandVertically(),
                    exit = fadeOut() + shrinkVertically(),
                    modifier = Modifier.align(if (isLandscape) Alignment.CenterEnd else Alignment.TopCenter).padding(if (isLandscape) PaddingValues(end = 40.dp) else PaddingValues(top = 80.dp))
                ) {
                    val rotation by rememberInfiniteTransition(label = "").animateFloat(
                        initialValue = 0f, targetValue = 360f,
                        animationSpec = infiniteRepeatable(tween(1000, easing = LinearEasing)), label = ""
                    )
                    Surface(
                        shape = CircleShape,
                        color = if (isDark) Color.Black.copy(alpha=0.6f) else Color.White.copy(alpha=0.6f),
                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha=0.5f))
                    ) {
                        Icon(
                            Icons.Default.Refresh,
                            contentDescription = "Refreshing",
                            tint = MaterialTheme.colorScheme.primary,
                            modifier = Modifier.padding(12.dp).rotate(rotation)
                        )
                    }
                }

                // Portrait chrome (M3-aligned):
                //  - Search bar lower under shrinked icons (not hugging the top bar)
                //  - M3 search height 56 dp; 8 dp gap to cards
                //  - Card fills remaining band; large orbiter docks at bottom (use all space)
                val hasFilter = hasItemFilterBar(currentCategory)
                val portraitSearchTop = 200.dp
                val portraitSearchHeight = 56.dp // Material 3 SearchBar
                val portraitItemTop = if (hasFilter) {
                    portraitSearchTop + portraitSearchHeight + 12.dp // ~268
                } else {
                    148.dp // under shrinked icons when no search strip
                }

                if (hasFilter) {
                    Box(
                        modifier = Modifier
                            .align(if (isLandscape) Alignment.TopStart else Alignment.TopCenter)
                            .zIndex(2f)
                            .padding(top = if (isLandscape) 16.dp else portraitSearchTop)
                    ) {
                        itemFilterBar(currentCategory)
                    }
                }

                if (items.isEmpty()) {
                    Surface(
                        modifier = Modifier.align(Alignment.Center).padding(horizontal = 48.dp),
                        shape = RoundedCornerShape(24.dp),
                        color = if (isDark) Color.Black.copy(alpha = 0.3f) else Color.White.copy(alpha = 0.3f),
                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.15f))
                    ) {
                        Text(
                            when (currentCategory.id) {
                                "equipment", "toolings", "inventory" ->
                                    "Nothing here — swipe down to leave, or adjust the filter"
                                else ->
                                    "All clear on the workshop floor"
                            },
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
                            style = MaterialTheme.typography.bodyLarge,
                            textAlign = TextAlign.Center,
                            modifier = Modifier.padding(32.dp)
                        )
                    }
                } else {
                    Surface(
                        modifier = Modifier.fillMaxSize(),
                        color = if (isDark) Color.Black.copy(alpha = 0.2f) else Color.White.copy(alpha = 0.15f)
                    ) {}

                    // Safe band under shrinked icons / search → above system nav bar.
                    // All portrait rails: TopCenter + fillMaxHeight so glass card can
                    // weight-fill under icons and orbiter docks above nav insets.
                    val stretchBand = !isLandscape
                    Box(
                        modifier = Modifier
                            .fillMaxSize()
                            .then(
                                if (isLandscape) Modifier
                                else Modifier
                                    .padding(top = portraitItemTop)
                                    .navigationBarsPadding()
                            )
                    ) {
                        val focusIdx = focusedItemIndex.value
                        val composeFrom = (focusIdx - 2).coerceAtLeast(0)
                        val composeTo = (focusIdx + 2).coerceAtMost(items.lastIndex)
                        for (index in composeFrom..composeTo) {
                            val item = items[index]
                            val distance = abs(itemScroll.value / itemSpacing - index)
                            val isFocused = focusIdx == index
                            
                            val itemScale = (1.0f - (distance * 0.15f)).coerceIn(0.7f, 1.0f)
                            val itemAlpha = (1.0f - (distance * 0.3f)).coerceIn(0f, 1.0f)
                            
                            val scrollOffset = if (isLandscape) (screenHeight.value / 2) - (itemSpacing / 2) + (index * itemSpacing - itemScroll.value)
                                               else (screenWidth.value / 2) - (itemSpacing / 2) + (index * itemSpacing - itemScroll.value)

                            val landscapeRailInset = 120f
                            val landscapeCardWidth = (screenWidth.value - (landscapeRailInset + 40f))
                                .coerceAtLeast(280f)

                            val itemWidth = if (isLandscape) landscapeCardWidth.dp else itemSpacing.dp
                            val xPos = if (isLandscape) landscapeRailInset.dp else scrollOffset.dp
                            val yPos = if (isLandscape) scrollOffset.dp else 0.dp

                            if (itemAlpha > 0.05f) {
                                Box(
                                    modifier = Modifier
                                        .offset { IntOffset(xPos.roundToPx(), yPos.roundToPx()) }
                                        .width(itemWidth)
                                        .then(
                                            if (isLandscape) {
                                                // Cap height so history/tall rows never paint under the
                                                // gesture nav bar (scale shrinks visual, not layout budget).
                                                Modifier
                                                    .fillMaxHeight(0.92f)
                                                    .navigationBarsPadding()
                                                    .wrapContentHeight(align = Alignment.CenterVertically)
                                            } else {
                                                Modifier.fillMaxHeight()
                                            }
                                        )
                                        .graphicsLayer {
                                            scaleX = itemScale
                                            scaleY = itemScale
                                            alpha = itemAlpha
                                            clip = isLandscape
                                        }
                                        .clickable(
                                            indication = null,
                                            interactionSource = remember { MutableInteractionSource() }
                                        ) {
                                            if (isFocused) {
                                                onItemSelected(item)
                                            } else {
                                                scope.launch { itemScroll.animateTo(index * itemSpacing) }
                                            }
                                        },
                                    contentAlignment = when {
                                        isLandscape -> Alignment.CenterStart
                                        stretchBand -> Alignment.TopCenter
                                        else -> Alignment.Center
                                    }
                                ) {
                                    androidx.compose.runtime.key(index) {
                                        itemContent(item, isFocused)
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Category rail (shrinks / slides when entering ITEM — old style, keep visible).
        // PERF: one shared breathe transition (was N infinite loops — one per category icon).
        val categoryBreathe by rememberInfiniteTransition(label = "cat_breathe").animateFloat(
            initialValue = 1f,
            targetValue = 1.04f,
            animationSpec = infiniteRepeatable(
                animation = tween(1500, easing = FastOutSlowInEasing),
                repeatMode = RepeatMode.Reverse
            ),
            label = "cat_breathe"
        )
        val focusedCatIdx = focusedCategoryIndex.value

        Box(
            modifier = Modifier
                .fillMaxSize()
                .offset(x = warpOffsetX, y = warpOffsetY)
                .graphicsLayer {
                    scaleX = warpScale
                    scaleY = warpScale
                    clip = false
                }
        ) {
            // Virtualize category icons (±2).
            val catFrom = (focusedCatIdx - 2).coerceAtLeast(0)
            val catTo = (focusedCatIdx + 2).coerceAtMost(categories.lastIndex)

            // Focused disc center in dp (same units as screenWidthDp / categorySpacing).
            val focusScrollOff = if (isLandscape) {
                (screenHeight.value / 2) - (categorySpacing / 2) +
                    (focusedCatIdx * categorySpacing - categoryScroll.value)
            } else {
                (screenWidth.value / 2) - (categorySpacing / 2) +
                    (focusedCatIdx * categorySpacing - categoryScroll.value)
            }
            val focusItemXDp = if (isLandscape) 80f else focusScrollOff
            val focusItemYDp = if (isLandscape) focusScrollOff else (screenHeight.value / 2f - 60f)
            // coachNudge* is in px — convert when placing the arrow.
            val density = androidx.compose.ui.platform.LocalDensity.current

            for (index in catFrom..catTo) {
                val category = categories[index]
                val distance = abs(categoryScroll.value / categorySpacing - index)

                val scale = (CAT_SCALE_MAX - (distance * 0.5f)).coerceIn(CAT_SCALE_MIN, CAT_SCALE_MAX)
                val alpha = (1.0f - (distance * 0.4f)).coerceIn(0.2f, 1.0f)

                val scrollOffset = if (isLandscape) {
                    (screenHeight.value / 2) - (categorySpacing / 2) +
                        (index * categorySpacing - categoryScroll.value)
                } else {
                    (screenWidth.value / 2) - (categorySpacing / 2) +
                        (index * categorySpacing - categoryScroll.value)
                }

                val xPos = if (isLandscape) 80.dp else scrollOffset.dp
                // Original vertical anchor (top of item column).
                val yPos = if (isLandscape) scrollOffset.dp else (screenHeight.value / 2 - 60f).dp

                val isFocusedCatLayer = depth == NavDepth.CATEGORY
                val focusRatio = if (isFocusedCatLayer) (1f - distance).coerceIn(0f, 1f) else 0f

                val primaryColor = MaterialTheme.colorScheme.primary
                val isWarnIcon = warnIconIndex == index && depth == NavDepth.CATEGORY
                val isCoachFocus = coachActive &&
                    depth == NavDepth.CATEGORY &&
                    index == focusedCatIdx &&
                    !isWarnIcon

                val bgColor = when {
                    isWarnIcon -> WccError.copy(alpha = 0.35f)
                    isDark -> Color.Black.copy(alpha = 0.15f)
                    else -> Color.White.copy(alpha = 0.25f)
                }
                val borderColor = if (isWarnIcon) {
                    WccError
                } else {
                    primaryColor.copy(alpha = 0.2f + (0.8f * focusRatio))
                }
                val iconTint = if (isWarnIcon) {
                    WccError
                } else {
                    primaryColor.copy(alpha = 0.5f + (0.5f * focusRatio))
                }
                // Original pre-scale glyph metrics (match first good MMM look).
                val borderWidth = if (isWarnIcon) 3.dp else (1f + focusRatio).dp
                val iconSize = (32f + 10f * focusRatio).dp

                val breathe = if (focusRatio > 0.5f) {
                    1f + (focusRatio * (categoryBreathe - 1f))
                } else {
                    1f
                }
                val warnBoost = if (isWarnIcon) 1.06f else 1f
                // Layout size = visual size → no graphicsLayer scale-up → no crop.
                val discSizeDp = (CAT_DISC_BASE * scale * breathe * warnBoost).dp

                val shakePx = if (isWarnIcon) warnShake.value else 0f
                val coachNx = if (isCoachFocus) coachNudgeX.value else 0f
                val coachNy = if (isCoachFocus) coachNudgeY.value else 0f
                val coachRot = if (isCoachFocus) coachTilt.value else 0f

                // Title grows with focus the same way the old graphicsLayer scale did
                // (titleMedium ~16sp × scale), without scaling the whole column in a layer.
                val titleSp = (16f * scale).sp

                Box(
                    modifier = Modifier
                        .offset {
                            IntOffset(
                                xPos.roundToPx() + shakePx.roundToInt() + coachNx.roundToInt(),
                                yPos.roundToPx() + coachNy.roundToInt()
                            )
                        }
                        .width(categorySpacing.dp)
                        .wrapContentHeight()
                        .graphicsLayer {
                            this.alpha = alpha
                            rotationZ = when {
                                isWarnIcon -> warnShake.value * 0.6f
                                isCoachFocus -> coachRot
                                else -> 0f
                            }
                            clip = false
                        }
                        .clickable(
                            enabled = depth == NavDepth.CATEGORY,
                            indication = null,
                            interactionSource = remember { MutableInteractionSource() }
                        ) {
                            bumpIdle()
                            val focused = focusedCategoryIndex.value
                            if (index == focused) {
                                // Always rumble on dead open-tap (re-fireable).
                                // Banner/red is debounced so rapid re-taps don't spam UI.
                                WccHaptics.warn(haptic, context)
                                warnIconIndex = index
                                val now = System.currentTimeMillis()
                                if (now - lastNavWarnAtMs >= NAV_WARN_DEBOUNCE_MS) {
                                    lastNavWarnAtMs = now
                                    warnBannerVisible = true
                                }
                            } else {
                                WccHaptics.select(haptic, context)
                                scope.launch {
                                    categoryScroll.animateTo(
                                        targetValue = index * categorySpacing,
                                        animationSpec = snapSpec,
                                    )
                                    savedCategoryScroll.floatValue = index * categorySpacing
                                }
                            }
                        },
                    contentAlignment = Alignment.TopCenter
                ) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        // Fixed slot = max disc; disc grows by size inside → no top crop.
                        Box(
                            modifier = Modifier.size(CAT_DISC_SLOT.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(discSizeDp)
                                    .clip(CircleShape)
                                    .background(bgColor)
                                    .border(BorderStroke(borderWidth, borderColor), CircleShape),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    category.icon,
                                    contentDescription = category.title,
                                    modifier = Modifier.size(iconSize),
                                    tint = iconTint
                                )
                            }
                        }

                        if (focusRatio > 0.01f) {
                            Text(
                                text = category.title,
                                fontSize = titleSp,
                                fontWeight = FontWeight.Black,
                                lineHeight = (titleSp.value * 1.15f).sp,
                                modifier = Modifier
                                    .padding(top = 16.dp)
                                    .graphicsLayer { this.alpha = focusRatio },
                                color = if (isWarnIcon) WccError else MaterialTheme.colorScheme.onSurface,
                                maxLines = 2,
                                textAlign = TextAlign.Center
                            )
                        }
                    }
                }
            }

            // Coach arrows on the full-size rail layer — well clear of max disc radius (~65dp).
            if (coachActive &&
                depth == NavDepth.CATEGORY &&
                warnIconIndex < 0 &&
                coachArrowAlpha.value > 0.02f
            ) {
                val arrowIcon = when (coachDir) {
                    IdleCoachDir.LEFT -> Icons.Default.KeyboardArrowLeft
                    IdleCoachDir.RIGHT -> Icons.Default.KeyboardArrowRight
                    IdleCoachDir.UP -> Icons.Default.KeyboardArrowUp
                    IdleCoachDir.DOWN -> Icons.Default.KeyboardArrowDown
                }
                // Gap from disc center to arrow center (dp). Max disc radius ≈ 65dp.
                val gapDp = 96f
                val (oxDp, oyDp) = when (coachDir) {
                    IdleCoachDir.LEFT -> -gapDp to 0f
                    IdleCoachDir.RIGHT -> gapDp to 0f
                    IdleCoachDir.UP, IdleCoachDir.DOWN -> 0f to -gapDp
                }
                val halfArrow = 22f
                Icon(
                    imageVector = arrowIcon,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary.copy(alpha = coachArrowAlpha.value),
                    modifier = Modifier
                        .offset {
                            with(density) {
                                val cx = focusItemXDp.dp.roundToPx() +
                                    (categorySpacing / 2f).dp.roundToPx() +
                                    coachNudgeX.value.roundToInt()
                                val cy = focusItemYDp.dp.roundToPx() +
                                    (CAT_DISC_SLOT / 2f).dp.roundToPx() +
                                    coachNudgeY.value.roundToInt()
                                IntOffset(
                                    cx + oxDp.dp.roundToPx() - halfArrow.dp.roundToPx(),
                                    cy + oyDp.dp.roundToPx() - halfArrow.dp.roundToPx()
                                )
                            }
                        }
                        .size(44.dp)
                        .zIndex(5f)
                )
            }
        }

        // Mid-screen swipe WARN — high contrast (bottom snackbar was too easy to miss).
        AnimatedVisibility(
            visible = warnBannerVisible && depth == NavDepth.CATEGORY,
            enter = fadeIn(tween(160)) + scaleIn(initialScale = 0.88f, animationSpec = tween(180)),
            exit = fadeOut(tween(220)) + scaleOut(targetScale = 0.92f, animationSpec = tween(200)),
            modifier = Modifier
                .align(Alignment.Center)
                .zIndex(40f)
                .padding(horizontal = 28.dp)
        ) {
            Surface(
                shape = RoundedCornerShape(22.dp),
                color = Color.Black.copy(alpha = 0.92f),
                border = BorderStroke(2.5.dp, WccError),
                shadowElevation = 16.dp,
                tonalElevation = 0.dp
            ) {
                Column(
                    modifier = Modifier
                        .padding(horizontal = 22.dp, vertical = 20.dp)
                        .widthIn(max = 340.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Icon(
                        imageVector = Icons.Default.Warning,
                        contentDescription = null,
                        tint = WccError,
                        modifier = Modifier.size(36.dp)
                    )
                    Spacer(Modifier.height(10.dp))
                    Text(
                        text = navSwipeWarn,
                        color = Color.White,
                        fontWeight = FontWeight.Black,
                        fontSize = 16.sp,
                        lineHeight = 22.sp,
                        textAlign = TextAlign.Center
                    )
                    Spacer(Modifier.height(8.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.Swipe,
                            contentDescription = null,
                            tint = WccError.copy(alpha = 0.9f),
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(Modifier.width(6.dp))
                        Text(
                            text = "Swipe · don’t tap",
                            color = WccError,
                            fontWeight = FontWeight.Bold,
                            fontSize = 13.sp
                        )
                    }
                }
            }
        }

        // ── SNACKBAR HOST (Action Feedback) ──
        SnackbarHost(
            hostState = snackbarHostState,
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .navigationBarsPadding()
                .padding(bottom = 16.dp)
        ) { data ->
            Surface(
                shape = RoundedCornerShape(16.dp),
                color = if (isDark) Color.Black.copy(alpha = 0.85f) else Color.White.copy(alpha = 0.9f),
                border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f)),
                shadowElevation = 8.dp
            ) {
                Text(
                    text = data.visuals.message,
                    modifier = Modifier.padding(horizontal = 24.dp, vertical = 14.dp),
                    color = MaterialTheme.colorScheme.onSurface,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.SemiBold
                )
            }
        }
    }
}
