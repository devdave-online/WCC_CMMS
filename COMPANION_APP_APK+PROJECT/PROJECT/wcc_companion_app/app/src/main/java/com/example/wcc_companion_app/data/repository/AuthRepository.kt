package com.example.wcc_companion_app.data.repository

import android.content.Context
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    @ApplicationContext context: Context
) {
    private val prefs = context.getSharedPreferences("wcc_prefs", Context.MODE_PRIVATE)

    fun saveApiKey(key: String) {
        prefs.edit().putString("api_key", key).apply()
    }

    fun getApiKey(): String? {
        return prefs.getString("api_key", null)
    }

    fun saveServerUrl(url: String) {
        prefs.edit().putString("server_url", url).apply()
    }

    fun getServerUrl(): String? {
        return prefs.getString("server_url", "")
    }

    fun setCredentials(user: String, pass: String) {
        prefs.edit()
            .putString("session_user", user)
            .putString("session_pass", pass)
            .apply()
    }

    fun getCredentials(): Pair<String, String>? {
        val u = prefs.getString("session_user", null) ?: return null
        val p = prefs.getString("session_pass", null) ?: return null
        return u to p
    }

    /** Returns true if we have stored credentials + server URL (auto-login candidate) */
    fun hasSession(): Boolean {
        return getCredentials() != null && !getServerUrl().isNullOrBlank()
    }

    fun logout() {
        prefs.edit()
            .remove("api_key")
            .remove("session_user")
            .remove("session_pass")
            .apply()
    }

    // ── Theme hard-lock (local only; survives restarts; not cleared on logout) ──

    fun isDarkTheme(): Boolean = prefs.getBoolean("theme_dark", false)

    fun setDarkTheme(dark: Boolean) {
        prefs.edit().putBoolean("theme_dark", dark).apply()
    }

    fun toggleDarkTheme(): Boolean {
        val next = !isDarkTheme()
        setDarkTheme(next)
        return next
    }

    // ── Locale hard-lock (local only; survives restarts; not cleared on logout) ──

    fun getAppLocaleTag(): String = prefs.getString("app_locale", "en") ?: "en"

    fun setAppLocaleTag(tag: String) {
        prefs.edit().putString("app_locale", tag).apply()
    }

    // ── Floor prefs (local only; survive restarts; not cleared on logout) ──

    fun isHapticsEnabled(): Boolean = prefs.getBoolean("haptics_enabled", true)

    fun setHapticsEnabled(enabled: Boolean) {
        prefs.edit().putBoolean("haptics_enabled", enabled).apply()
        // Keep process-wide gate in sync for call sites that only pass Context sometimes.
        com.example.wcc_companion_app.ui.components.WccHaptics.setEnabled(enabled)
    }

    fun getLastRailCategoryId(): String? =
        prefs.getString("last_rail_category_id", null)?.takeIf { it.isNotBlank() }

    fun setLastRailCategoryId(id: String) {
        prefs.edit().putString("last_rail_category_id", id).apply()
    }

    /** Cached identity from last successful GET /me — offline My Shift matching. */
    fun cacheUserIdentity(
        userId: Int?,
        username: String?,
        fullName: String?,
    ) {
        prefs.edit()
            .apply {
                if (userId != null) putInt("cached_user_id", userId) else remove("cached_user_id")
                putString("cached_username", username)
                putString("cached_full_name", fullName)
            }
            .apply()
    }

    fun getCachedUserId(): Int? =
        if (prefs.contains("cached_user_id")) prefs.getInt("cached_user_id", -1).takeIf { it >= 0 }
        else null

    fun getCachedUsername(): String? =
        prefs.getString("cached_username", null)?.takeIf { it.isNotBlank() }

    fun getCachedFullName(): String? =
        prefs.getString("cached_full_name", null)?.takeIf { it.isNotBlank() }

    fun clearCachedUserIdentity() {
        prefs.edit()
            .remove("cached_user_id")
            .remove("cached_username")
            .remove("cached_full_name")
            .apply()
    }

    // ── Biometric app lock (DEVICE LOCAL ONLY — never API / Room / plant DB) ──
    // Preference lives in SharedPreferences on this handset. Enabling it does not
    // change server auth; it only gates opening the already-stored session on-device.

    fun isBiometricLockEnabled(): Boolean = prefs.getBoolean("biometric_lock", false)

    fun setBiometricLockEnabled(enabled: Boolean) {
        prefs.edit().putBoolean("biometric_lock", enabled).apply()
    }

    // ── Open Beta 1.0.0 (local only) ──

    fun isBetaDisclaimerAccepted(): Boolean =
        prefs.getBoolean("ob_1_0_0_disclaimer_ok", false)

    fun setBetaDisclaimerAccepted(accepted: Boolean) {
        prefs.edit().putBoolean("ob_1_0_0_disclaimer_ok", accepted).apply()
    }

    fun isBetaBannerDismissed(): Boolean =
        prefs.getBoolean("ob_1_0_0_banner_dismissed", false)

    fun setBetaBannerDismissed(dismissed: Boolean) {
        prefs.edit().putBoolean("ob_1_0_0_banner_dismissed", dismissed).apply()
    }
}
