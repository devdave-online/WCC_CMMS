<?php
/**
 * Runs deep_probe.php write paths (symbology, BOM, docs upload) with cleanup.
 * Only registered when --mutate is set.
 */
return function (WccAuditReport $report, array $ctx): void {
    $section = 'DeepProbe';
    $php = $ctx['php_bin'] ?? PHP_BINARY;
    $script = dirname(__DIR__) . '/deep_probe.php';
    if (!is_file($script)) {
        $report->fail($section, 'script_missing');
        return;
    }
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' 2>&1', $out, $code);
    foreach ($out as $line) {
        if (str_starts_with(trim($line), 'OK')) {
            $report->ok($section, preg_replace('/^\s*OK\s+/', '', trim($line)));
        } elseif (str_starts_with(trim($line), 'FAIL')) {
            $report->fail($section, preg_replace('/^\s*FAIL\s+/', '', trim($line)));
        }
    }
    if ($code !== 0 && $report->failCount() === 0) {
        $report->fail($section, 'exit_code', 'deep_probe exit ' . $code . ' ' . implode(' | ', array_slice($out, -5)));
    }
};
