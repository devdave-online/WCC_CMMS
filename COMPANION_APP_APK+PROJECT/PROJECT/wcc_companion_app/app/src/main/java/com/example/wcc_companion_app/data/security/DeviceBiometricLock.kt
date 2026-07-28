package com.example.wcc_companion_app.data.security

import android.content.Context
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricManager.Authenticators
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity

/**
 * Device-local biometric / device-credential gate.
 *
 * - Preference + unlock state never leave the handset (no API, no Room, no plant DB).
 * - [processUnlocked] is in-memory only: cold start and background re-lock require
 *   a fresh prompt when the user has enabled the lock in Profile.
 */
object DeviceBiometricLock {

    /** Authenticators: biometrics preferred, device PIN/pattern/password as fallback. */
    const val ALLOWED_AUTHENTICATORS =
        Authenticators.BIOMETRIC_WEAK or Authenticators.DEVICE_CREDENTIAL

    /**
     * Process-scoped unlock. Cleared on background (see MainActivity) so returning
     * to the app re-prompts when lock is enabled. Never persisted.
     */
    @Volatile
    var processUnlocked: Boolean = false
        private set

    fun markUnlocked() {
        processUnlocked = true
    }

    fun markLocked() {
        processUnlocked = false
    }

    fun canAuthenticate(context: Context): Boolean {
        val mgr = BiometricManager.from(context)
        return when (mgr.canAuthenticate(ALLOWED_AUTHENTICATORS)) {
            BiometricManager.BIOMETRIC_SUCCESS -> true
            else -> false
        }
    }

    fun statusLabel(context: Context): String {
        val mgr = BiometricManager.from(context)
        return when (mgr.canAuthenticate(ALLOWED_AUTHENTICATORS)) {
            BiometricManager.BIOMETRIC_SUCCESS -> "Ready on this device"
            BiometricManager.BIOMETRIC_ERROR_NONE_ENROLLED ->
                "Set a screen lock or fingerprint in system settings first"
            BiometricManager.BIOMETRIC_ERROR_HW_UNAVAILABLE,
            BiometricManager.BIOMETRIC_ERROR_NO_HARDWARE ->
                "No biometric hardware on this device"
            BiometricManager.BIOMETRIC_ERROR_SECURITY_UPDATE_REQUIRED ->
                "Update device security settings first"
            else -> "Unavailable on this device"
        }
    }

    /**
     * Shows the system biometric sheet. [onSuccess] runs on main thread after auth.
     * Does not touch network or local DB.
     */
    fun prompt(
        activity: FragmentActivity,
        title: String = "Unlock WCC Companion",
        subtitle: String = "Confirm it's you — stays on this device only",
        onSuccess: () -> Unit,
        onError: (String) -> Unit = {},
        onCancel: () -> Unit = {},
    ) {
        if (!canAuthenticate(activity)) {
            onError(statusLabel(activity))
            return
        }
        val executor = ContextCompat.getMainExecutor(activity)
        val prompt = BiometricPrompt(
            activity,
            executor,
            object : BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                    markUnlocked()
                    onSuccess()
                }

                override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                    if (errorCode == BiometricPrompt.ERROR_USER_CANCELED ||
                        errorCode == BiometricPrompt.ERROR_NEGATIVE_BUTTON ||
                        errorCode == BiometricPrompt.ERROR_CANCELED
                    ) {
                        onCancel()
                    } else {
                        onError(errString.toString())
                    }
                }

                override fun onAuthenticationFailed() {
                    // Wrong finger — system keeps sheet open; no-op.
                }
            }
        )
        val info = BiometricPrompt.PromptInfo.Builder()
            .setTitle(title)
            .setSubtitle(subtitle)
            .setAllowedAuthenticators(ALLOWED_AUTHENTICATORS)
            .build()
        prompt.authenticate(info)
    }
}
