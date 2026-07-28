package com.example.wcc_companion_app

import android.app.Application
import androidx.hilt.work.HiltWorkerFactory
import androidx.work.Configuration
import com.example.wcc_companion_app.data.locale.LocaleController
import dagger.hilt.android.HiltAndroidApp
import javax.inject.Inject

@HiltAndroidApp
class WccApplication : Application(), Configuration.Provider {

    @Inject lateinit var workerFactory: HiltWorkerFactory
    @Inject lateinit var localeController: LocaleController

    override fun onCreate() {
        super.onCreate()
        // Hilt injects members before onCreate body for @HiltAndroidApp.
        localeController.applyStored()
        com.example.wcc_companion_app.ui.components.WccHaptics.initFromContext(this)
    }

    override val workManagerConfiguration: Configuration
        get() = Configuration.Builder()
            .setWorkerFactory(workerFactory)
            .build()
}
