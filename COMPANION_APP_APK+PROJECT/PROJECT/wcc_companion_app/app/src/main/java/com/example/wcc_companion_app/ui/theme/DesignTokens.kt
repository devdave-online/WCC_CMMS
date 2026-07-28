package com.example.wcc_companion_app.ui.theme

import androidx.compose.ui.unit.dp

/**
 * Flux Premium spacing / shape tokens.
 * Prefer these over one-off magic numbers so every rail matches Equipment quality.
 */
object WccTokens {
    val radiusSm = 12.dp
    val radiusMd = 16.dp
    val radiusLg = 20.dp
    val radiusXl = 24.dp
    val radiusXxl = 28.dp
    val radiusPill = 50.dp

    val borderThin = 1.dp
    val border = 1.5.dp
    val borderStrong = 2.dp

    val spaceXs = 4.dp
    val spaceSm = 8.dp
    val spaceMd = 12.dp
    val spaceLg = 16.dp
    val spaceXl = 20.dp
    val space2xl = 24.dp
    val space3xl = 32.dp

    val glassAlphaLight = 0.92f
    val glassAlphaDark = 0.88f
    val glassBorderAlpha = 0.22f

    /** M3 accessibility minimum touch target (48×48 dp). */
    val touchMin = 48.dp
    /**
     * Primary floor actions (orbiter). M3 standard FAB = 56 dp;
     * we use 64 dp for gloved industrial use while staying on the 8 dp grid.
     */
    val orbiterButton = 64.dp
    val orbiterIcon = 28.dp
    /** M3 SearchBar input height. */
    val searchBarHeight = 56.dp
    val iconLg = 28.dp
}
