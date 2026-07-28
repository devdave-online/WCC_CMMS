package com.example.wcc_companion_app.data.locale

import androidx.appcompat.app.AppCompatDelegate
import androidx.core.os.LocaleListCompat
import com.example.wcc_companion_app.data.repository.AuthRepository
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Applies in-app locale via AppCompat and persists preference.
 * Translations for non-English can land later; tags still switch correctly.
 */
@Singleton
class LocaleController @Inject constructor(
    private val authRepository: AuthRepository,
) {
    fun current(): AppLocale = AppLocale.fromTag(authRepository.getAppLocaleTag())

    fun applyStored() {
        apply(current(), persist = false)
    }

    fun setLocale(locale: AppLocale) {
        apply(locale, persist = true)
    }

    private fun apply(locale: AppLocale, persist: Boolean) {
        if (persist) authRepository.setAppLocaleTag(locale.tag)
        val list = LocaleListCompat.forLanguageTags(locale.tag)
        AppCompatDelegate.setApplicationLocales(list)
    }
}
