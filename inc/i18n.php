<?php
/**
 * WCC CMMS — lightweight i18n (JSON language packs).
 *
 *   __('nav.tickets')
 *   __e('nav.tickets')                    // HTML-escaped
 *   __('search.n_of_total', ['visible'=>12,'total'=>148])
 *
 * Context for translators: lang/en.context.json + glossary.json + en.context.md
 * (not loaded at runtime).
 */

if (defined('WCC_I18N_LOADED')) {
    return;
}
define('WCC_I18N_LOADED', 1);

/**
 * Full catalog: code => [label, native, group, rtl, wave]
 * group used for profile <optgroup>.
 */
function wcc_locale_catalog(): array
{
    static $cat = null;
    if ($cat !== null) {
        return $cat;
    }
    // Groups are geographic only — equal weight in the profile picker (no priority tiers).
    $cat = [
        'en' => ['label' => 'English', 'native' => 'English', 'group' => 'English', 'rtl' => false, 'wave' => 0],
        // South & Southeast Asia
        'hi' => ['label' => 'Hindi', 'native' => 'हिन्दी', 'group' => 'South & Southeast Asia', 'rtl' => false, 'wave' => 1],
        'vi' => ['label' => 'Vietnamese', 'native' => 'Tiếng Việt', 'group' => 'South & Southeast Asia', 'rtl' => false, 'wave' => 1],
        'id' => ['label' => 'Bahasa Indonesia', 'native' => 'Bahasa Indonesia', 'group' => 'South & Southeast Asia', 'rtl' => false, 'wave' => 1],
        'bn' => ['label' => 'Bengali', 'native' => 'বাংলা', 'group' => 'South & Southeast Asia', 'rtl' => false, 'wave' => 2],
        'fil' => ['label' => 'Filipino', 'native' => 'Filipino', 'group' => 'South & Southeast Asia', 'rtl' => false, 'wave' => 2],
        'ms' => ['label' => 'Malay', 'native' => 'Bahasa Melayu', 'group' => 'South & Southeast Asia', 'rtl' => false, 'wave' => 3],
        // India
        'ta' => ['label' => 'Tamil', 'native' => 'தமிழ்', 'group' => 'India', 'rtl' => false, 'wave' => 3],
        'te' => ['label' => 'Telugu', 'native' => 'తెలుగు', 'group' => 'India', 'rtl' => false, 'wave' => 3],
        'mr' => ['label' => 'Marathi', 'native' => 'मराठी', 'group' => 'India', 'rtl' => false, 'wave' => 3],
        'gu' => ['label' => 'Gujarati', 'native' => 'ગુજરાતી', 'group' => 'India', 'rtl' => false, 'wave' => 3],
        'kn' => ['label' => 'Kannada', 'native' => 'ಕನ್ನಡ', 'group' => 'India', 'rtl' => false, 'wave' => 3],
        'ml' => ['label' => 'Malayalam', 'native' => 'മലയാളം', 'group' => 'India', 'rtl' => false, 'wave' => 3],
        'pa' => ['label' => 'Punjabi', 'native' => 'ਪੰਜਾਬੀ', 'group' => 'India', 'rtl' => false, 'wave' => 3],
        // Middle East & North Africa (RTL where noted)
        'ar' => ['label' => 'Arabic', 'native' => 'العربية', 'group' => 'Middle East & North Africa', 'rtl' => true, 'wave' => 2],
        'ur' => ['label' => 'Urdu', 'native' => 'اردو', 'group' => 'Middle East & North Africa', 'rtl' => true, 'wave' => 2],
        // Europe & Americas
        'fr' => ['label' => 'French', 'native' => 'Français', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 2],
        'es' => ['label' => 'Spanish', 'native' => 'Español', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 2],
        'de' => ['label' => 'German', 'native' => 'Deutsch', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 3],
        'pt' => ['label' => 'Portuguese', 'native' => 'Português', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 3],
        'pt-BR' => ['label' => 'Portuguese (Brazil)', 'native' => 'Português (Brasil)', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 3],
        'it' => ['label' => 'Italian', 'native' => 'Italiano', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 3],
        'nl' => ['label' => 'Dutch', 'native' => 'Nederlands', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 4],
        'pl' => ['label' => 'Polish', 'native' => 'Polski', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 4],
        'ru' => ['label' => 'Russian', 'native' => 'Русский', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 3],
        'tr' => ['label' => 'Turkish', 'native' => 'Türkçe', 'group' => 'Europe & Americas', 'rtl' => false, 'wave' => 3],
        // East Asia
        'zh-Hans' => ['label' => 'Chinese (Simplified)', 'native' => '简体中文', 'group' => 'East Asia', 'rtl' => false, 'wave' => 3],
        'ja' => ['label' => 'Japanese', 'native' => '日本語', 'group' => 'East Asia', 'rtl' => false, 'wave' => 3],
        'th' => ['label' => 'Thai', 'native' => 'ไทย', 'group' => 'East Asia', 'rtl' => false, 'wave' => 3],
        // Africa
        'sw' => ['label' => 'Swahili', 'native' => 'Kiswahili', 'group' => 'Africa', 'rtl' => false, 'wave' => 3],
        'ha' => ['label' => 'Hausa', 'native' => 'Hausa', 'group' => 'Africa', 'rtl' => false, 'wave' => 4],
        'yo' => ['label' => 'Yoruba', 'native' => 'Yorùbá', 'group' => 'Africa', 'rtl' => false, 'wave' => 4],
        'ig' => ['label' => 'Igbo', 'native' => 'Igbo', 'group' => 'Africa', 'rtl' => false, 'wave' => 4],
        'am' => ['label' => 'Amharic', 'native' => 'አማርኛ', 'group' => 'Africa', 'rtl' => false, 'wave' => 4],
    ];
    return $cat;
}

/** @var array<string, string>|null */
$GLOBALS['WCC_I18N_DICT'] = null;
/** @var array<string, string>|null */
$GLOBALS['WCC_I18N_FALLBACK'] = null;
/** @var string|null */
$GLOBALS['WCC_I18N_LOCALE'] = null;

function wcc_locale_normalize(?string $code): string
{
    $code = trim((string)$code);
    if ($code === '') {
        return 'en';
    }
    // Preserve case for pt-BR / zh-Hans style tags
    $catalog = wcc_locale_catalog();
    if (isset($catalog[$code])) {
        return $code;
    }
    $lower = strtolower($code);
    foreach (array_keys($catalog) as $k) {
        if (strtolower($k) === $lower) {
            return $k;
        }
    }
    return 'en';
}

function wcc_locale(): string
{
    if (!empty($GLOBALS['WCC_I18N_LOCALE'])) {
        return (string)$GLOBALS['WCC_I18N_LOCALE'];
    }
    $fromSession = (session_status() === PHP_SESSION_ACTIVE) ? ($_SESSION['locale'] ?? null) : null;
    $loc = wcc_locale_normalize(is_string($fromSession) ? $fromSession : 'en');
    $GLOBALS['WCC_I18N_LOCALE'] = $loc;
    return $loc;
}

function wcc_locale_dir(?string $locale = null): string
{
    $loc = wcc_locale_normalize($locale ?? wcc_locale());
    $cat = wcc_locale_catalog();
    return !empty($cat[$loc]['rtl']) ? 'rtl' : 'ltr';
}

/** Whether a dedicated pack file exists and has at least one key */
function wcc_locale_pack_ready(string $locale): bool
{
    $locale = wcc_locale_normalize($locale);
    if ($locale === 'en') {
        return true;
    }
    $path = wcc_i18n_lang_dir() . DIRECTORY_SEPARATOR . $locale . '.json';
    if (!is_readable($path)) {
        return false;
    }
    $data = json_decode((string)file_get_contents($path), true);
    return is_array($data) && count($data) > 0;
}

function wcc_i18n_lang_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lang';
}

/** @return array<string, string> */
function wcc_i18n_load_file(string $locale): array
{
    $path = wcc_i18n_lang_dir() . DIRECTORY_SEPARATOR . $locale . '.json';
    if (!is_readable($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        error_log('[WCC i18n] Invalid JSON: ' . $path);
        return [];
    }
    $out = [];
    foreach ($data as $k => $v) {
        if (is_string($k) && (is_string($v) || is_numeric($v))) {
            $out[$k] = (string)$v;
        }
    }
    return $out;
}

function wcc_i18n_boot(?string $locale = null): void
{
    $loc = wcc_locale_normalize($locale ?? wcc_locale());
    $GLOBALS['WCC_I18N_LOCALE'] = $loc;
    if ($GLOBALS['WCC_I18N_FALLBACK'] === null) {
        $GLOBALS['WCC_I18N_FALLBACK'] = wcc_i18n_load_file('en');
    }
    if ($loc === 'en') {
        $GLOBALS['WCC_I18N_DICT'] = $GLOBALS['WCC_I18N_FALLBACK'];
    } else {
        $dict = wcc_i18n_load_file($loc);
        $GLOBALS['WCC_I18N_DICT'] = array_merge($GLOBALS['WCC_I18N_FALLBACK'] ?? [], $dict);
    }
}

/**
 * @param array<string, string|int|float> $vars
 */
function __(string $key, array $vars = []): string
{
    if ($GLOBALS['WCC_I18N_DICT'] === null) {
        wcc_i18n_boot();
    }
    $dict = $GLOBALS['WCC_I18N_DICT'] ?? [];
    $fb   = $GLOBALS['WCC_I18N_FALLBACK'] ?? [];
    $str  = $dict[$key] ?? $fb[$key] ?? $key;
    if ($vars !== []) {
        foreach ($vars as $name => $value) {
            $str = str_replace(':' . $name, (string)$value, $str);
        }
    }
    return $str;
}

function __e(string $key, array $vars = []): string
{
    return htmlspecialchars(__($key, $vars), ENT_QUOTES, 'UTF-8');
}

function wcc_set_locale(string $locale, bool $persist = false, ?int $userId = null): string
{
    $loc = wcc_locale_normalize($locale);
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['locale'] = $loc;
    }
    $GLOBALS['WCC_I18N_LOCALE'] = $loc;
    $GLOBALS['WCC_I18N_DICT'] = null;
    wcc_i18n_boot($loc);

    if ($persist && $userId !== null && $userId > 0) {
        try {
            require_once __DIR__ . '/db.php';
            $pdo = get_wcc_db_connection();
            $pdo->prepare('UPDATE users SET locale = ? WHERE user_id = ?')->execute([$loc, $userId]);
        } catch (Throwable $e) {
            error_log('[WCC i18n] persist locale: ' . $e->getMessage());
        }
    }
    return $loc;
}

function wcc_i18n_sync_from_user(array $userRow): void
{
    if (!empty($userRow['locale'])) {
        wcc_set_locale((string)$userRow['locale'], false);
        return;
    }
    if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['locale'])) {
        wcc_set_locale('en', false);
    } else {
        wcc_i18n_boot();
    }
}

/** Runtime dict for JS inject */
function wcc_i18n_js_dict(): array
{
    if ($GLOBALS['WCC_I18N_DICT'] === null) {
        wcc_i18n_boot();
    }
    return $GLOBALS['WCC_I18N_DICT'] ?? [];
}

function wcc_i18n_js_fallback(): array
{
    if ($GLOBALS['WCC_I18N_FALLBACK'] === null) {
        wcc_i18n_boot();
    }
    return $GLOBALS['WCC_I18N_FALLBACK'] ?? [];
}

if (session_status() === PHP_SESSION_ACTIVE) {
    wcc_i18n_boot();
}
