package com.example.wcc_companion_app.ui.theme

import androidx.compose.ui.graphics.Color

// ── WCC Flux Premium palette ──────────────────────────────────────────

val WccBackgroundLight = Color(0xFFF1F5F9)
/** OLED-first near-black (not muddy grey). */
val WccBackgroundDark = Color(0xFF0A0F1A)

val WccPrimary = Color(0xFF0EA5E9)
val WccPrimaryDim = Color(0xFF0284C7)
val WccOnPrimary = Color(0xFFFFFFFF)
val WccSecondary = Color(0xFF94A3B8)

/** Glass surfaces — solid enough for plant glare, translucent enough for depth. */
val GlassSurfaceDark = Color(0xFF141C2B)
val GlassSurfaceLight = Color(0xFFFFFFFF)
val GlassElevatedDark = Color(0xFF1C2740)
val GlassElevatedLight = Color(0xFFFFFFFF)

val WccTextPrimaryLight = Color(0xFF0F172A)
val WccTextSecondaryLight = Color(0xFF64748B)
val WccTextPrimaryDark = Color(0xFFF8FAFC)
val WccTextSecondaryDark = Color(0xFF94A3B8)

val WccError = Color(0xFFEF4444)
val WccSuccess = Color(0xFF22C55E)
val WccWarning = Color(0xFFF59E0B)

// Ticket criticality
val StatusCriticalityCritical = Color(0xFFEF4444)
val StatusCriticalityHigh = Color(0xFFEA580C)
val StatusCriticalityNormal = Color(0xFF3B82F6)
val StatusCriticalityLow = Color(0xFF22C55E)

// Ticket status
val TicketStatusEscalated = Color(0xFFEF4444)
val TicketStatusOpen = Color(0xFF0EA5E9)
val TicketStatusClosed = Color(0xFF22C55E)
val TicketStatusPending = Color(0xFFF59E0B)
val TicketStatusHold = Color(0xFF6B7280)

// Achievement tier colors (match gamification.php)
val TierMaster = Color(0xFFEAB308)
val TierExpert = Color(0xFFA855F7)
val TierProficient = Color(0xFF3B82F6)
val TierCompetent = Color(0xFF10B981)
val TierAdvanced = Color(0xFFF97316)
val TierNovice = Color(0xFF94A3B8)

// Legacy aliases used elsewhere
val Purple80 = Color(0xFFD0BCFF)
val PurpleGrey80 = Color(0xFFCCC2DC)
val Pink80 = Color(0xFFEFB8C8)
val Purple40 = Color(0xFF6650a4)
val PurpleGrey40 = Color(0xFF625b71)
val Pink40 = Color(0xFF7D5260)
