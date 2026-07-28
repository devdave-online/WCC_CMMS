<?php
/**
 * Verify all wcc_locale_catalog locales (except en) match en.json keys.
 * Run: php lang/_check_missing.php
 */
$en = json_decode(file_get_contents(__DIR__ . '/en.json'), true);
if (!is_array($en)) {
    fwrite(STDERR, "bad en.json\n");
    exit(1);
}
echo 'en keys: ' . count($en) . PHP_EOL;
$locales = [
    'hi', 'vi', 'id', 'bn', 'fil', 'ms', 'ta', 'te', 'mr', 'gu', 'kn', 'ml', 'pa',
    'ar', 'ur', 'es', 'fr', 'de', 'pt', 'pt-BR', 'it', 'nl', 'pl', 'ru', 'tr',
    'zh-Hans', 'ja', 'th', 'sw', 'ha', 'yo', 'ig', 'am',
];
$fail = 0;
foreach ($locales as $code) {
    $path = __DIR__ . '/' . $code . '.json';
    if (!is_file($path)) {
        echo "$code: FILE MISSING\n";
        $fail++;
        continue;
    }
    $loc = json_decode(file_get_contents($path), true);
    if (!is_array($loc)) {
        echo "$code: INVALID JSON\n";
        $fail++;
        continue;
    }
    $missing = array_diff_key($en, $loc);
    $extra = array_diff_key($loc, $en);
    $empty = 0;
    foreach ($loc as $v) {
        if ($v === '' || $v === null) {
            $empty++;
        }
    }
    $ok = count($missing) === 0 && count($extra) === 0 && $empty === 0;
    if (!$ok) {
        $fail++;
    }
    echo sprintf(
        "%s: %s have=%d missing=%d extra=%d empty=%d\n",
        $code,
        $ok ? 'OK' : 'FAIL',
        count($loc),
        count($missing),
        count($extra),
        $empty
    );
}
if ($fail === 0) {
    echo "ALL LOCALES COMPLETE — 0 missing keys vs en.json (" . count($en) . " keys)\n";
    exit(0);
}
echo "FAILED locales: $fail\n";
exit(1);
