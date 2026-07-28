# WCC languages — 34-locale catalog

**Sole author:** Project owner  

**Source of truth (web):** `C:\xampp\htdocs\lang\` + `inc/i18n.php` → `wcc_locale_catalog()`  
**Companion:** `AppLocale` enum + `res/values*/strings.xml` (gen: `tools/gen_locale_strings.py`)  
**Status:** Catalog complete for Open Beta 1.0.0; companion chrome localized; overlays still partly English.

## Rule

- **34 packs** (en + 33). Same codes on web and companion.  
- Web: full UI keys in `lang/{code}.json` (747 keys each, verified complete).  
- Companion: floor chrome/login strings in Android resources; picker uses native labels from `AppLocale`.  
- Missing Android string → falls back to English `values/`.  
- **No plant DB sync of biometrics.** Locale preference may sync via `users.locale` on web; companion stores tag in local prefs (`app_locale`).

## Catalog (34)

| # | Code | English | Native | RTL | Android folder |
|---|------|---------|--------|-----|----------------|
| 1 | en | English | English | | values/ |
| 2 | hi | Hindi | हिन्दी | | values-hi |
| 3 | vi | Vietnamese | Tiếng Việt | | values-vi |
| 4 | id | Bahasa Indonesia | Bahasa Indonesia | | values-in |
| 5 | bn | Bengali | বাংলা | | values-bn |
| 6 | ar | Arabic | العربية | ✓ | values-ar |
| 7 | ur | Urdu | اردو | ✓ | values-ur |
| 8 | fil | Filipino | Filipino | | values-fil |
| 9 | fr | French | Français | | values-fr |
| 10 | es | Spanish | Español | | values-es |
| 11 | ta | Tamil | தமிழ் | | values-ta |
| 12 | te | Telugu | తెలుగు | | values-te |
| 13 | mr | Marathi | मराठी | | values-mr |
| 14 | gu | Gujarati | ગુજરાતી | | values-gu |
| 15 | kn | Kannada | ಕನ್ನಡ | | values-kn |
| 16 | ml | Malayalam | മലയാളം | | values-ml |
| 17 | pa | Punjabi | ਪੰਜਾਬੀ | | values-pa |
| 18 | ms | Malay | Bahasa Melayu | | values-ms |
| 19 | de | German | Deutsch | | values-de |
| 20 | pt | Portuguese | Português | | values-pt |
| 21 | pt-BR | Portuguese (Brazil) | Português (Brasil) | | values-pt-rBR |
| 22 | zh-Hans | Chinese (Simplified) | 简体中文 | | values-zh-rCN |
| 23 | ru | Russian | Русский | | values-ru |
| 24 | ja | Japanese | 日本語 | | values-ja |
| 25 | it | Italian | Italiano | | values-it |
| 26 | tr | Turkish | Türkçe | | values-tr |
| 27 | th | Thai | ไทย | | values-th |
| 28 | sw | Swahili | Kiswahili | | values-sw |
| 29 | nl | Dutch | Nederlands | | values-nl |
| 30 | pl | Polish | Polski | | values-pl |
| 31 | ha | Hausa | Hausa | | values-ha |
| 32 | yo | Yoruba | Yorùbá | | values-yo |
| 33 | ig | Igbo | Igbo | | values-ig |
| 34 | am | Amharic | አማርኛ | | values-am |

## Web status (verified)

- All 34 `lang/{code}.json` present  
- Each has **747 keys** matching `en.json` (0 missing / 0 empty)  
- Profile picker: `wcc_locale_catalog()` in `inc/i18n.php`  

## Companion status

- `AppLocale.ALL` = 34  
- Top-bar language chip opens scrollable picker  
- Regenerate strings: `python tools/gen_locale_strings.py`  

---

**Sole author: Project owner**
