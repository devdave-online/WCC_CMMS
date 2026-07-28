package com.example.wcc_companion_app.data.locale

/**
 * Full 34-locale catalog — same codes as web `lang/*` + `wcc_locale_catalog()`.
 * UI strings live in `res/values-*/strings.xml` (generated from tools/gen_locale_strings.py).
 * Picker labels use [nativeLabel] (not translated per-locale).
 */
enum class AppLocale(
    /** BCP-47 tag for AppCompat [LocaleListCompat]. */
    val tag: String,
    /** Short code on the top-bar chip. */
    val chipCode: String,
    /** English name (docs / support). */
    val englishLabel: String,
    /** Native name shown in the language picker. */
    val nativeLabel: String,
    val isRtl: Boolean = false,
) {
    ENGLISH("en", "EN", "English", "English"),
    HINDI("hi", "HI", "Hindi", "हिन्दी"),
    VIETNAMESE("vi", "VI", "Vietnamese", "Tiếng Việt"),
    INDONESIAN("id", "ID", "Bahasa Indonesia", "Bahasa Indonesia"),
    BENGALI("bn", "BN", "Bengali", "বাংলা"),
    ARABIC("ar", "AR", "Arabic", "العربية", isRtl = true),
    URDU("ur", "UR", "Urdu", "اردو", isRtl = true),
    FILIPINO("fil", "FIL", "Filipino", "Filipino"),
    FRENCH("fr", "FR", "French", "Français"),
    SPANISH("es", "ES", "Spanish", "Español"),
    TAMIL("ta", "TA", "Tamil", "தமிழ்"),
    TELUGU("te", "TE", "Telugu", "తెలుగు"),
    MARATHI("mr", "MR", "Marathi", "मराठी"),
    GUJARATI("gu", "GU", "Gujarati", "ગુજરાતી"),
    KANNADA("kn", "KN", "Kannada", "ಕನ್ನಡ"),
    MALAYALAM("ml", "ML", "Malayalam", "മലയാളം"),
    PUNJABI("pa", "PA", "Punjabi", "ਪੰਜਾਬੀ"),
    MALAY("ms", "MS", "Malay", "Bahasa Melayu"),
    GERMAN("de", "DE", "German", "Deutsch"),
    PORTUGUESE("pt", "PT", "Portuguese", "Português"),
    PORTUGUESE_BR("pt-BR", "BR", "Portuguese (Brazil)", "Português (Brasil)"),
    CHINESE_SIMPLIFIED("zh-Hans", "ZH", "Chinese (Simplified)", "简体中文"),
    RUSSIAN("ru", "RU", "Russian", "Русский"),
    JAPANESE("ja", "JA", "Japanese", "日本語"),
    ITALIAN("it", "IT", "Italian", "Italiano"),
    TURKISH("tr", "TR", "Turkish", "Türkçe"),
    THAI("th", "TH", "Thai", "ไทย"),
    SWAHILI("sw", "SW", "Swahili", "Kiswahili"),
    DUTCH("nl", "NL", "Dutch", "Nederlands"),
    POLISH("pl", "PL", "Polish", "Polski"),
    HAUSA("ha", "HA", "Hausa", "Hausa"),
    YORUBA("yo", "YO", "Yoruba", "Yorùbá"),
    IGBO("ig", "IG", "Igbo", "Igbo"),
    AMHARIC("am", "AM", "Amharic", "አማርኛ");

    companion object {
        /** Same order as web profile catalog (geographic groups). */
        val ALL: List<AppLocale> = entries.toList()

        fun fromTag(tag: String?): AppLocale {
            if (tag.isNullOrBlank()) return ENGLISH
            val normalized = tag.trim().replace('_', '-')
            // Exact
            entries.firstOrNull { it.tag.equals(normalized, ignoreCase = true) }?.let { return it }
            val lower = normalized.lowercase()
            // Indonesian legacy "in"
            if (lower == "in" || lower.startsWith("in-")) return INDONESIAN
            // zh-CN / zh-SG → simplified
            if (lower.startsWith("zh-hans") || lower == "zh-cn" || lower.startsWith("zh-cn-") ||
                lower == "zh-sg" || lower == "zh"
            ) {
                return CHINESE_SIMPLIFIED
            }
            // pt-BR
            if (lower == "pt-br" || lower.startsWith("pt-br")) return PORTUGUESE_BR
            // Language-only prefix
            val lang = lower.substringBefore('-')
            return entries.firstOrNull {
                it.tag.equals(lang, ignoreCase = true) ||
                    it.tag.lowercase().startsWith("$lang-")
            } ?: ENGLISH
        }
    }
}
