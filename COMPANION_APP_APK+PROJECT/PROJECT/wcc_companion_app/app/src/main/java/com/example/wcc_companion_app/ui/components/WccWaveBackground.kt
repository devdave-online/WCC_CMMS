package com.example.wcc_companion_app.ui.components

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.material3.MaterialTheme
import kotlin.math.sin

@Composable
fun WccWaveBackground(isDark: Boolean) {
    var time by remember { mutableFloatStateOf(0f) }
    
    LaunchedEffect(Unit) {
        var lastFrameTime = withFrameNanos { it }
        while (true) {
            withFrameNanos { frameTime ->
                val delta = (frameTime - lastFrameTime) / 1_000_000_000f
                time += delta
                lastFrameTime = frameTime
            }
        }
    }

    // Cache colors outside Canvas to avoid recomposition overhead
    val bgColorTop = if (isDark) Color(0xFF0A0F1D) else Color(0xFFE2E8F0)
    val bgColorBottom = if (isDark) Color(0xFF1E293B) else Color(0xFFCBD5E1)
    val primary = MaterialTheme.colorScheme.primary
    val ribbon1 = primary.copy(alpha = 0.22f)
    val ribbon2 = primary.copy(alpha = 0.13f)
    val ribbon3 = primary.copy(alpha = 0.07f)

    // Depth vignette: darkens the outer edges so the focused card/menu reads as
    // the foreground and the animated ribbons stay subordinate. Tuned per theme.
    val vignetteColor = if (isDark) Color(0xFF05070D).copy(alpha = 0.55f)
                        else Color(0xFF64748B).copy(alpha = 0.16f)

    // Pre-allocate paths ONCE — reuse every frame to avoid GC pressure
    val path1 = remember { Path() }
    val path2 = remember { Path() }
    val path3 = remember { Path() }
    
    // Pre-allocate stroke objects — immutable, safe to cache
    val stroke1 = remember { Stroke(width = 14f) }
    val stroke2 = remember { Stroke(width = 18f) }
    val stroke3 = remember { Stroke(width = 22f) }

    // Pre-compute constant for 2*PI to avoid repeated multiplication
    val twoPi = remember { 2f * Math.PI.toFloat() }

    Canvas(modifier = Modifier.fillMaxSize()) {
        // Background gradient
        drawRect(brush = Brush.verticalGradient(listOf(bgColorTop, bgColorBottom)))
        
        val h = size.height
        val w = size.width
        val steps = 40 // Reduced from 50 — imperceptible quality loss at 14-22px stroke widths
        val stepX = w / steps

        // Draw each ribbon by reusing pre-allocated paths
        computeRibbon(path1, w, h, steps, stepX, twoPi, time, 0.35f, 220f, 0f, 0.5f, -h * 0.15f)
        drawPath(path1, ribbon1, style = stroke1)

        computeRibbon(path2, w, h, steps, stepX, twoPi, time, 0.45f, 260f, 3.5f, 0.4f, 0f)
        drawPath(path2, ribbon2, style = stroke2)

        computeRibbon(path3, w, h, steps, stepX, twoPi, time, 0.3f, 200f, 7f, 0.35f, h * 0.2f)
        drawPath(path3, ribbon3, style = stroke3)

        // Depth vignette overlay (transparent center -> tinted edges)
        drawRect(
            brush = Brush.radialGradient(
                colors = listOf(Color.Transparent, vignetteColor),
                center = Offset(w * 0.5f, h * 0.42f),
                radius = maxOf(w, h) * 0.75f
            )
        )
    }
}

/**
 * Compute ribbon path in-place, reusing the Path object to eliminate per-frame allocations.
 */
private fun computeRibbon(
    path: Path,
    w: Float, h: Float, steps: Int, stepX: Float, twoPi: Float,
    time: Float, frequency: Float, amplitude: Float, phase: Float, speed: Float, verticalOffset: Float
) {
    path.reset()
    val centerY = h * 0.5f + verticalOffset
    val timeSpeed = time * speed
    val timeHalf = time * 0.5f

    val startY = centerY + amplitude * sin(-timeSpeed + phase) * sin(timeHalf)
    path.moveTo(0f, startY)

    for (i in 1..steps) {
        val x = i * stepX
        val t = (x / w) * twoPi * frequency
        val y = centerY + amplitude * sin(t - timeSpeed + phase) * sin(t * 0.4f + timeHalf)
        path.lineTo(x, y)
    }
}
