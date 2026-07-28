package com.example.wcc_companion_app.ui.components

import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.layout.size
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.DrawScope
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.rotate
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import kotlin.math.cos
import kotlin.math.sin

/**
 * Pure Compose brand mark — glass orb + flowing silk ribbons.
 * No photo / no dark square; transparent outside the orb.
 */
@Composable
fun WccBrandOrb(
    modifier: Modifier = Modifier,
    size: Dp = 64.dp,
    animated: Boolean = true,
) {
    val transition = rememberInfiniteTransition(label = "brand_orb")

    val floatY by transition.animateFloat(
        initialValue = if (animated) -3f else 0f,
        targetValue = if (animated) 3f else 0f,
        animationSpec = infiniteRepeatable(
            animation = tween(2800, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "float_y"
    )
    val pulse by transition.animateFloat(
        initialValue = if (animated) 0.85f else 1f,
        targetValue = if (animated) 1f else 1f,
        animationSpec = infiniteRepeatable(
            animation = tween(2400, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "pulse"
    )
    val ribbonPhase by transition.animateFloat(
        initialValue = 0f,
        targetValue = if (animated) (Math.PI * 2).toFloat() else 0f,
        animationSpec = infiniteRepeatable(
            animation = tween(7_200, easing = LinearEasing),
            repeatMode = RepeatMode.Restart
        ),
        label = "ribbon_phase"
    )
    val spin by transition.animateFloat(
        initialValue = 0f,
        targetValue = if (animated) 360f else 0f,
        animationSpec = infiniteRepeatable(
            animation = tween(22_000, easing = LinearEasing),
            repeatMode = RepeatMode.Restart
        ),
        label = "spin"
    )

    Canvas(
        modifier = modifier
            .size(size)
            .graphicsLayer {
                translationY = floatY
                scaleX = pulse
                scaleY = pulse
            }
    ) {
        val cx = this.size.width / 2f
        val cy = this.size.height / 2f
        // Leave room for ribbons outside the glass sphere
        val orbR = this.size.minDimension * 0.30f

        // Soft halo only (no filled square)
        drawCircle(
            brush = Brush.radialGradient(
                colors = listOf(
                    Color(0xFF0EA5E9).copy(alpha = 0.22f),
                    Color(0xFF0EA5E9).copy(alpha = 0.06f),
                    Color.Transparent
                ),
                center = Offset(cx, cy),
                radius = orbR * 1.85f
            ),
            radius = orbR * 1.85f,
            center = Offset(cx, cy)
        )

        // Silk ribbons — behind the glass so the orb sits on top
        rotate(degrees = spin * 0.25f, pivot = Offset(cx, cy)) {
            drawSilkRibbon(
                cx = cx,
                cy = cy,
                radius = orbR * 1.05f,
                phase = ribbonPhase,
                color = Color(0xFF7DD3FC).copy(alpha = 0.70f),
                width = orbR * 0.16f
            )
            drawSilkRibbon(
                cx = cx,
                cy = cy,
                radius = orbR * 0.98f,
                phase = ribbonPhase + 2.0f,
                color = Color(0xFFF0F9FF).copy(alpha = 0.55f),
                width = orbR * 0.11f
            )
        }

        // Glass orb body
        drawCircle(
            brush = Brush.radialGradient(
                colors = listOf(
                    Color(0xFFF0F9FF).copy(alpha = 0.95f),
                    Color(0xFFBAE6FD).copy(alpha = 0.75f),
                    Color(0xFF38BDF8).copy(alpha = 0.55f),
                    Color(0xFF0284C7).copy(alpha = 0.45f)
                ),
                center = Offset(cx - orbR * 0.28f, cy - orbR * 0.32f),
                radius = orbR * 1.35f
            ),
            radius = orbR,
            center = Offset(cx, cy)
        )

        // Inner refraction ring
        drawCircle(
            color = Color.White.copy(alpha = 0.35f),
            radius = orbR * 0.92f,
            center = Offset(cx, cy),
            style = Stroke(width = orbR * 0.04f)
        )

        // Specular highlight (glass catch-light)
        drawCircle(
            brush = Brush.radialGradient(
                colors = listOf(
                    Color.White.copy(alpha = 0.85f),
                    Color.White.copy(alpha = 0.15f),
                    Color.Transparent
                ),
                center = Offset(cx - orbR * 0.32f, cy - orbR * 0.38f),
                radius = orbR * 0.42f
            ),
            radius = orbR * 0.38f,
            center = Offset(cx - orbR * 0.28f, cy - orbR * 0.34f)
        )

        // Soft bottom shade inside the sphere
        drawCircle(
            brush = Brush.radialGradient(
                colors = listOf(
                    Color.Transparent,
                    Color(0xFF0C4A6E).copy(alpha = 0.18f)
                ),
                center = Offset(cx + orbR * 0.15f, cy + orbR * 0.35f),
                radius = orbR * 0.7f
            ),
            radius = orbR * 0.7f,
            center = Offset(cx + orbR * 0.12f, cy + orbR * 0.28f)
        )

        // Front silk ribbon pass (slightly thinner) so ribbons wrap “around”
        rotate(degrees = spin * 0.25f + 18f, pivot = Offset(cx, cy)) {
            drawSilkRibbon(
                cx = cx,
                cy = cy,
                radius = orbR * 1.02f,
                phase = ribbonPhase + 0.9f,
                color = Color(0xFFE0F2FE).copy(alpha = 0.45f),
                width = orbR * 0.09f,
                arcFraction = 0.55f
            )
        }
    }
}

/**
 * Open silk ribbon path — elliptical, wavy, not a hard closed ring.
 * [arcFraction] < 1 draws a partial ribbon (ends feel like fabric tips).
 */
private fun DrawScope.drawSilkRibbon(
    cx: Float,
    cy: Float,
    radius: Float,
    phase: Float,
    color: Color,
    width: Float,
    arcFraction: Float = 0.82f,
) {
    val path = Path()
    val steps = 56
    val end = (steps * arcFraction).toInt().coerceAtLeast(8)
    for (i in 0..end) {
        val t = i / steps.toFloat()
        val angle = t * (Math.PI * 2).toFloat() + phase
        val wobble = sin((angle * 2.2f + phase).toDouble()).toFloat() * radius * 0.22f
        val rx = radius * 1.35f + wobble
        val ry = radius * 0.68f - wobble * 0.4f
        val x = cx + cos(angle.toDouble()).toFloat() * rx
        val y = cy + sin(angle.toDouble()).toFloat() * ry
        if (i == 0) path.moveTo(x, y) else path.lineTo(x, y)
    }
    drawPath(
        path = path,
        color = color,
        style = Stroke(width = width, cap = StrokeCap.Round)
    )
}
