package com.example.wcc_companion_app

import android.os.Bundle
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.DarkMode
import androidx.compose.material.icons.filled.LightMode
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.fragment.app.FragmentActivity
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.repository.AuthRepository
import com.example.wcc_companion_app.data.security.DeviceBiometricLock
import com.example.wcc_companion_app.data.sync.SyncCoordinator
import com.example.wcc_companion_app.ui.auth.BiometricGateScreen
import com.example.wcc_companion_app.ui.auth.LoginScreen
import com.example.wcc_companion_app.ui.shell.AppShell
import com.example.wcc_companion_app.ui.theme.WccTheme
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

/**
 * Process entry — theme/locale + login / biometric gate / main shell.
 * Biometric lock is device-local only (SharedPreferences + system prompt).
 */
@AndroidEntryPoint
class MainActivity : FragmentActivity() {

    @Inject
    lateinit var authRepository: AuthRepository

    @Inject
    lateinit var apiService: WccApiService

    @Inject
    lateinit var syncCoordinator: SyncCoordinator

    @Inject
    lateinit var localeController: com.example.wcc_companion_app.data.locale.LocaleController

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Re-lock when leaving the app if device lock is on (never persisted unlock).
        lifecycle.addObserver(LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_STOP) {
                if (authRepository.isBiometricLockEnabled() && authRepository.hasSession()) {
                    DeviceBiometricLock.markLocked()
                }
            }
        })

        setContent {
            var isDarkTheme by remember {
                mutableStateOf(authRepository.isDarkTheme())
            }
            var appLocale by remember {
                mutableStateOf(localeController.current())
            }

            WccTheme(darkTheme = isDarkTheme) {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    WccAppNavigation(
                        isDark = isDarkTheme,
                        onToggleTheme = {
                            isDarkTheme = authRepository.toggleDarkTheme()
                        },
                        appLocale = appLocale,
                        onLocaleSelected = { locale ->
                            localeController.setLocale(locale)
                            appLocale = locale
                        },
                        authRepository = authRepository,
                        apiService = apiService,
                        syncCoordinator = syncCoordinator
                    )
                }
            }
        }
    }
}

@Composable
fun WccAppNavigation(
    isDark: Boolean,
    onToggleTheme: () -> Unit,
    appLocale: com.example.wcc_companion_app.data.locale.AppLocale,
    onLocaleSelected: (com.example.wcc_companion_app.data.locale.AppLocale) -> Unit,
    authRepository: AuthRepository,
    apiService: WccApiService,
    syncCoordinator: SyncCoordinator
) {
    val navController = rememberNavController()

    // After password login this process is already trusted.
    // Gate only when: session exists + lock enabled + not yet unlocked.
    fun startRoute(): String {
        if (!authRepository.hasSession()) return "login"
        if (authRepository.isBiometricLockEnabled() && !DeviceBiometricLock.processUnlocked) {
            return "biometric"
        }
        return "main"
    }

    val startDestination = remember { startRoute() }

    NavHost(navController = navController, startDestination = startDestination) {
        composable("login") {
            Box {
                LoginScreen(
                    isDark = isDark,
                    onLoginSuccess = {
                        // Password login unlocks this process; bio still required next cold start.
                        DeviceBiometricLock.markUnlocked()
                        navController.navigate("main") {
                            popUpTo("login") { inclusive = true }
                        }
                    }
                )
                IconButton(
                    onClick = onToggleTheme,
                    modifier = Modifier
                        .align(Alignment.TopEnd)
                        .padding(16.dp)
                        .statusBarsPadding()
                ) {
                    Icon(
                        imageVector = if (isDark) Icons.Default.LightMode else Icons.Default.DarkMode,
                        contentDescription = "Toggle Theme",
                        tint = MaterialTheme.colorScheme.onSurface
                    )
                }
            }
        }
        composable("biometric") {
            BiometricGateScreen(
                onUnlocked = {
                    navController.navigate("main") {
                        popUpTo("biometric") { inclusive = true }
                    }
                },
                onUsePassword = {
                    DeviceBiometricLock.markLocked()
                    authRepository.logout()
                    navController.navigate("login") {
                        popUpTo(0) { inclusive = true }
                    }
                }
            )
        }
        composable("main") {
            // If user backgrounded while on main, re-lock and bounce to gate.
            MainShellWithReLock(
                authRepository = authRepository,
                onNeedBiometric = {
                    navController.navigate("biometric") {
                        popUpTo("main") { inclusive = true }
                    }
                }
            ) {
                AppShell(
                    isDark = isDark,
                    onToggleTheme = onToggleTheme,
                    appLocale = appLocale,
                    onLocaleSelected = onLocaleSelected,
                    authRepository = authRepository,
                    apiService = apiService,
                    syncCoordinator = syncCoordinator,
                    onLogout = {
                        DeviceBiometricLock.markLocked()
                        authRepository.logout()
                        navController.navigate("login") {
                            popUpTo("main") { inclusive = true }
                        }
                    }
                )
            }
        }
    }
}

/**
 * Watches lifecycle: if biometric lock is on and process was re-locked on STOP,
 * send the user back to the gate on START.
 */
@Composable
private fun MainShellWithReLock(
    authRepository: AuthRepository,
    onNeedBiometric: () -> Unit,
    content: @Composable () -> Unit,
) {
    val lifecycleOwner = androidx.compose.ui.platform.LocalLifecycleOwner.current
    androidx.compose.runtime.DisposableEffect(lifecycleOwner, authRepository) {
        val obs = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_START) {
                if (authRepository.hasSession() &&
                    authRepository.isBiometricLockEnabled() &&
                    !DeviceBiometricLock.processUnlocked
                ) {
                    onNeedBiometric()
                }
            }
        }
        lifecycleOwner.lifecycle.addObserver(obs)
        onDispose { lifecycleOwner.lifecycle.removeObserver(obs) }
    }
    content()
}
