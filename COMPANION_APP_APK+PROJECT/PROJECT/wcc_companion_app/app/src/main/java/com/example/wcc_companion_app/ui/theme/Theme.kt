package com.example.wcc_companion_app.ui.theme

import android.app.Activity
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalView
import androidx.core.view.WindowCompat

private val DarkColorScheme = darkColorScheme(
    primary = WccPrimary,
    onPrimary = WccOnPrimary,
    primaryContainer = Color(0xFF0C4A6E),
    onPrimaryContainer = Color(0xFFE0F2FE),
    secondary = WccSecondary,
    background = WccBackgroundDark,
    surface = GlassSurfaceDark,
    surfaceVariant = GlassElevatedDark,
    onBackground = WccTextPrimaryDark,
    onSurface = WccTextPrimaryDark,
    onSurfaceVariant = WccTextSecondaryDark,
    outline = WccSecondary.copy(alpha = 0.35f),
    error = WccError,
    errorContainer = Color(0xFF7F1D1D)
)

private val LightColorScheme = lightColorScheme(
    primary = WccPrimary,
    onPrimary = WccOnPrimary,
    primaryContainer = Color(0xFFE0F2FE),
    onPrimaryContainer = Color(0xFF0C4A6E),
    secondary = WccSecondary,
    background = WccBackgroundLight,
    surface = GlassSurfaceLight,
    surfaceVariant = Color(0xFFF8FAFC),
    onBackground = WccTextPrimaryLight,
    onSurface = WccTextPrimaryLight,
    onSurfaceVariant = WccTextSecondaryLight,
    outline = WccSecondary.copy(alpha = 0.35f),
    error = WccError
)

@Composable
fun WccTheme(
    darkTheme: Boolean = false,
    content: @Composable () -> Unit
) {
    val colorScheme = if (darkTheme) DarkColorScheme else LightColorScheme
    val view = LocalView.current
    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            window.statusBarColor = colorScheme.background.toArgb()
            window.navigationBarColor = colorScheme.background.toArgb()
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = !darkTheme
            WindowCompat.getInsetsController(window, view).isAppearanceLightNavigationBars = !darkTheme
        }
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
