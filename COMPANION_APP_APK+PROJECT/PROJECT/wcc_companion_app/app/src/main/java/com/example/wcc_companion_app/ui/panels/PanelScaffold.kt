package com.example.wcc_companion_app.ui.panels

import android.content.res.Configuration
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.ScrollState
import androidx.compose.foundation.gestures.awaitEachGesture
import androidx.compose.foundation.gestures.awaitFirstDown
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.input.pointer.PointerEventPass
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.input.pointer.positionChange
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/** Which end of the category rail a panel is entered from. */
enum class PanelEdge { START, END }

/**
 * Shared chrome for the rail-end panels (My Shift, Search).
 *
 * NO close button by design — this is a shop-floor app and a gloved hand cannot hit a
 * small target. A panel is dismissed by swiping back the way you came:
 *
 *   portrait  START (My Shift) entered swiping RIGHT -> close by swiping LEFT
 *             END   (Search)   entered swiping LEFT  -> close by swiping RIGHT
 *   landscape START entered swiping DOWN -> close by swiping UP
 *             END   entered swiping UP   -> close by swiping DOWN
 *
 * In landscape the close axis collides with the body's vertical scrolling, so there the
 * gesture only fires once the scroll has reached the matching boundary — the same
 * "overscroll to leave" rule UserProfileView already uses. Portrait has no conflict
 * (close is horizontal, scroll is vertical) so it fires anywhere on the panel.
 *
 * The system Back button remains a second, equally target-free way out.
 */
@Composable
fun PanelScaffold(
    isDark: Boolean,
    title: String,
    subtitle: String? = null,
    edge: PanelEdge,
    onClose: () -> Unit,
    header: @Composable ColumnScope.() -> Unit = {},
    content: @Composable ColumnScope.() -> Unit
) {
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE
    val scrollState = rememberScrollState()

    Surface(
        modifier = Modifier
            .fillMaxSize()
            .swipeToClose(edge, isLandscape, scrollState, onClose),
        color = if (isDark) Color.Black.copy(alpha = 0.55f) else Color.White.copy(alpha = 0.55f)
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .statusBarsPadding()
                .displayCutoutPadding()
                .navigationBarsPadding()
                .imePadding()
                .padding(horizontal = 20.dp)
        ) {
            Column(modifier = Modifier.padding(top = 12.dp, bottom = 16.dp)) {
                Text(
                    text = title,
                    style = MaterialTheme.typography.headlineSmall,
                    fontWeight = FontWeight.Black,
                    color = MaterialTheme.colorScheme.onSurface
                )
                if (subtitle != null) {
                    Text(
                        text = subtitle,
                        fontSize = 13.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f)
                    )
                }
                // Replaces the close button: tells the user how to get out, without
                // asking them to hit anything.
                Text(
                    text = swipeHint(edge, isLandscape),
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary.copy(alpha = 0.7f),
                    modifier = Modifier.padding(top = 6.dp)
                )
            }

            header()

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                content()
                Spacer(modifier = Modifier.height(32.dp))
            }
        }
    }
}

private fun swipeHint(edge: PanelEdge, isLandscape: Boolean): String = when {
    isLandscape && edge == PanelEdge.START -> "▲  Swipe up at the end to go back"
    isLandscape -> "▼  Swipe down at the top to go back"
    edge == PanelEdge.START -> "◀  Swipe left to go back"
    else -> "▶  Swipe right to go back"
}

/**
 * Observes drags on the Initial pass without consuming them, so the body can still
 * scroll normally while we watch for the dismiss gesture.
 */
private fun Modifier.swipeToClose(
    edge: PanelEdge,
    isLandscape: Boolean,
    scrollState: ScrollState,
    onClose: () -> Unit
): Modifier = this.pointerInput(edge, isLandscape) {
    val threshold = 120f
    awaitEachGesture {
        awaitFirstDown(pass = PointerEventPass.Initial)
        var dx = 0f
        var dy = 0f
        do {
            val event = awaitPointerEvent(pass = PointerEventPass.Initial)
            event.changes.firstOrNull()?.let { change ->
                dx += change.positionChange().x
                dy += change.positionChange().y
            }
        } while (event.changes.any { it.pressed })

        val shouldClose = if (!isLandscape) {
            // Horizontal close swipe — never fights the vertical body scroll.
            if (edge == PanelEdge.START) dx < -threshold else dx > threshold
        } else {
            // Vertical close swipe — only past the matching scroll boundary.
            if (edge == PanelEdge.START) {
                dy < -threshold && scrollState.value >= scrollState.maxValue
            } else {
                dy > threshold && scrollState.value <= 0
            }
        }
        if (shouldClose) onClose()
    }
}

/** Translucent grouping card used inside panels. */
@Composable
fun PanelCard(
    isDark: Boolean,
    title: String,
    content: @Composable ColumnScope.() -> Unit
) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(20.dp),
        color = if (isDark) Color.Black.copy(alpha = 0.30f) else Color.White.copy(alpha = 0.45f),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.18f))
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = title,
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
            Spacer(modifier = Modifier.height(12.dp))
            content()
        }
    }
}

/** Empty-state line so a panel never renders as a blank void. */
@Composable
fun PanelEmpty(text: String) {
    Text(
        text = text,
        fontSize = 14.sp,
        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f),
        modifier = Modifier.padding(vertical = 8.dp)
    )
}
