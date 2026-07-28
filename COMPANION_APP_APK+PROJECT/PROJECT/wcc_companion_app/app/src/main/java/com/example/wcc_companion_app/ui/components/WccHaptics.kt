package com.example.wcc_companion_app.ui.components

import android.content.Context
import android.media.AudioAttributes
import android.os.Build
import android.os.Handler
import android.os.Looper
import android.os.SystemClock
import android.os.VibrationAttributes
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import android.util.Log
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.ui.hapticfeedback.HapticFeedback
import androidx.compose.ui.hapticfeedback.HapticFeedbackType
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalHapticFeedback

/**
 * Floor haptics via [Vibrator] (primary) + Compose (secondary).
 *
 * OEM notes (Samsung / Pixel / etc.):
 * - Calling [Vibrator.cancel] before *every* short tick can leave the motor dead
 *   after the first long effect. Only cancel when we must interrupt an active buzz.
 * - [VibrationAttributes.USAGE_TOUCH] is rate-limited / intensity-scaled; after a
 *   long "touch" rumble later effects are often dropped until process restart.
 *   We use hardware-feedback / notification usage so re-fires keep working.
 * - Predefined tick/click effects re-fire far more reliably on LRA motors than
 *   custom one-shots.
 * - All motor calls are serialized on the main looper.
 */
object WccHaptics {
    private const val TAG = "WccHaptics"
    private const val WARN_MS = 850L
    private const val PULSE_MS = 140L
    private const val TICK_MS = 40L

    private val mainHandler = Handler(Looper.getMainLooper())
    private val lock = Any()

    /** Uptime millis when the currently scheduled effect should finish. */
    @Volatile
    private var busyUntilUptimeMs = 0L

    /** Master switch — default on; Profile / AuthRepository keep this in sync. */
    @Volatile
    private var enabled: Boolean = true

    private var loggedMissing = false

    fun setEnabled(value: Boolean) {
        enabled = value
    }

    fun isEnabled(): Boolean = enabled

    /** Bootstrap from prefs once at app start. */
    fun initFromContext(context: Context) {
        try {
            val prefs = context.applicationContext
                .getSharedPreferences("wcc_prefs", Context.MODE_PRIVATE)
            enabled = prefs.getBoolean("haptics_enabled", true)
        } catch (_: Exception) {
            enabled = true
        }
    }

    /** Main-menu wrong tap — long hard rumble. */
    fun warn(haptic: HapticFeedback, context: Context? = null) {
        if (!enabled) return
        try {
            haptic.performHapticFeedback(HapticFeedbackType.LongPress)
        } catch (_: Exception) { /* ignore */ }
        context?.let { buzzWarnLong(it) }
    }

    fun select(haptic: HapticFeedback, context: Context? = null) {
        if (!enabled) return
        try {
            haptic.performHapticFeedback(HapticFeedbackType.TextHandleMove)
        } catch (_: Exception) { /* ignore */ }
        context?.let { buzzNavTick(it) }
    }

    fun navigate(haptic: HapticFeedback, context: Context? = null) {
        if (!enabled) return
        try {
            haptic.performHapticFeedback(HapticFeedbackType.TextHandleMove)
        } catch (_: Exception) { /* ignore */ }
        context?.let { buzzNavTick(it) }
    }

    fun confirm(haptic: HapticFeedback, context: Context? = null) {
        if (!enabled) return
        try {
            haptic.performHapticFeedback(HapticFeedbackType.LongPress)
        } catch (_: Exception) { /* ignore */ }
        context?.let { buzzNavTick(it) }
    }

    fun reject(haptic: HapticFeedback, context: Context? = null) {
        if (!enabled) return
        try {
            haptic.performHapticFeedback(HapticFeedbackType.LongPress)
        } catch (_: Exception) { /* ignore */ }
        context?.let { buzzPulse(it) }
    }

    /** ~800ms solid rumble for dead menu tap. */
    fun buzzWarnLong(context: Context) {
        if (!enabled) return
        enqueue(context, durationMs = WARN_MS, interrupt = true) { v ->
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                // Continuous one-shot — more reliable than multi-gap waveforms on OEMs.
                play(
                    v,
                    VibrationEffect.createOneShot(WARN_MS, VibrationEffect.DEFAULT_AMPLITUDE)
                )
            } else {
                @Suppress("DEPRECATION")
                v.vibrate(WARN_MS)
            }
        }
    }

    fun buzzLong(context: Context) = buzzWarnLong(context)

    /** Short navigation tick — re-fireable every time. */
    fun buzzNavTick(context: Context) {
        if (!enabled) return
        enqueue(context, durationMs = TICK_MS, interrupt = false) { v ->
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                // Predefined LRA effects are designed to re-fire; custom one-shots
                // often get dropped after a long rumble on Samsung / Pixel.
                play(v, VibrationEffect.createPredefined(VibrationEffect.EFFECT_TICK))
            } else if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                play(
                    v,
                    VibrationEffect.createOneShot(TICK_MS, VibrationEffect.DEFAULT_AMPLITUDE)
                )
            } else {
                @Suppress("DEPRECATION")
                v.vibrate(TICK_MS)
            }
        }
    }

    fun buzzPulse(context: Context) {
        if (!enabled) return
        enqueue(context, durationMs = PULSE_MS, interrupt = true) { v ->
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                play(v, VibrationEffect.createPredefined(VibrationEffect.EFFECT_HEAVY_CLICK))
            } else if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                play(
                    v,
                    VibrationEffect.createOneShot(PULSE_MS, VibrationEffect.DEFAULT_AMPLITUDE)
                )
            } else {
                @Suppress("DEPRECATION")
                v.vibrate(PULSE_MS)
            }
        }
    }

    /**
     * Serialize motor access on the main thread.
     * [interrupt] = true only for warn/pulse that must cut a previous effect.
     * Short ticks never cancel — that cancel path is what killed re-fire on OEMs.
     */
    private fun enqueue(
        context: Context,
        durationMs: Long,
        interrupt: Boolean,
        block: (Vibrator) -> Unit,
    ) {
        val app = context.applicationContext
        mainHandler.post {
            val v = vibrator(app) ?: return@post
            try {
                val now = SystemClock.uptimeMillis()
                synchronized(lock) {
                    val stillBusy = now < busyUntilUptimeMs
                    if (interrupt && stillBusy) {
                        // Only cancel when replacing an active long buzz.
                        try {
                            v.cancel()
                        } catch (_: Exception) { /* ignore */ }
                    }
                    block(v)
                    busyUntilUptimeMs = SystemClock.uptimeMillis() + durationMs
                }
                Log.d(TAG, "played duration=${durationMs}ms interrupt=$interrupt")
            } catch (e: Exception) {
                Log.w(TAG, "enqueue vibrate failed", e)
            }
        }
    }

    private fun play(v: Vibrator, effect: VibrationEffect) {
        if (Build.VERSION.SDK_INT >= 33) {
            // USAGE_TOUCH is rate-limited after long effects and is the main
            // "fire once, never again" culprit. Hardware feedback / notification
            // stay re-fireable for UI ticks and warn rumbles.
            val usage = try {
                // API 33+ constant; fall back if OEM strips it.
                VibrationAttributes.USAGE_HARDWARE_FEEDBACK
            } catch (_: Throwable) {
                VibrationAttributes.USAGE_NOTIFICATION
            }
            val attrs = VibrationAttributes.Builder()
                .setUsage(usage)
                .build()
            v.vibrate(effect, attrs)
        } else if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            @Suppress("DEPRECATION")
            val audio = AudioAttributes.Builder()
                .setUsage(AudioAttributes.USAGE_ASSISTANCE_SONIFICATION)
                .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                .build()
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                @Suppress("DEPRECATION")
                v.vibrate(effect, audio)
            }
        } else {
            @Suppress("DEPRECATION")
            v.vibrate(50)
        }
    }

    private fun vibrator(context: Context): Vibrator? {
        return try {
            val v = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                val mgr = context.getSystemService(Context.VIBRATOR_MANAGER_SERVICE) as? VibratorManager
                mgr?.defaultVibrator
            } else {
                @Suppress("DEPRECATION")
                context.getSystemService(Context.VIBRATOR_SERVICE) as? Vibrator
            }
            if (v == null || !v.hasVibrator()) {
                if (!loggedMissing) {
                    loggedMissing = true
                    Log.w(TAG, "No vibrator available")
                }
                null
            } else {
                v
            }
        } catch (e: Exception) {
            Log.w(TAG, "vibrator() failed", e)
            null
        }
    }
}

@Composable
fun HapticOnError(error: String?) {
    val haptic = LocalHapticFeedback.current
    val context = LocalContext.current
    LaunchedEffect(error) {
        if (!error.isNullOrBlank()) WccHaptics.reject(haptic, context)
    }
}
