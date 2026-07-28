<?php
/**
 * Pre-ship master gate runner — exit 1 if any gate fails.
 *
 *   C:\xampp\php\php.exe tests\full_audit\run_all_gates.php
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

$root = dirname(__DIR__, 2);
$audit = __DIR__;
$php = PHP_BINARY;
foreach (['C:\\xampp\\php\\php.exe', 'C:/xampp/php/php.exe', PHP_BINARY] as $c) {
    if ($c && is_file($c)) {
        $php = $c;
        break;
    }
}

$gates = [
    'security_gates' => $root . '/tests/security_gates.php',
    'cqa_static' => $audit . '/cqa_static.php',
    'full_audit_mutate' => $audit . '/run.php',
    'fqa_manual' => $audit . '/fqa_manual_path.php',
    'rest_v1' => $audit . '/rest_v1_full.php',
    'preship_deep' => $audit . '/pre_ship_deep.php',
];

$results = [];
$failed = 0;
$stamp = date('Ymd_His');
echo "═══════════════════════════════════════════════════════════\n";
echo "  WCC PRE-SHIP ALL GATES  $stamp\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($gates as $name => $script) {
    if (!is_file($script)) {
        echo " FAIL  $name — missing $script\n";
        $results[$name] = ['code' => 127, 'tail' => 'missing'];
        $failed++;
        continue;
    }
    echo ">>> $name\n";
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script);
    if ($name === 'full_audit_mutate') {
        $cmd .= ' --mutate';
    }
    $out = [];
    $code = 0;
    exec($cmd . ' 2>&1', $out, $code);
    $tail = array_slice($out, -15);
    foreach ($tail as $line) {
        echo $line . "\n";
    }
    echo "<<< $name exit=$code\n\n";
    $results[$name] = ['code' => $code, 'tail' => implode("\n", $tail)];
    if ($code !== 0) {
        $failed++;
    }
}

$md = $audit . '/reports/PRES HIP_ALL_' . $stamp . '.md';
// fix typo path
$md = $audit . '/reports/PRESHIP_ALL_' . $stamp . '.md';
$lines = ["# Pre-ship all gates\n", "Generated: " . date('c') . "\n", "Failed gates: $failed\n\n"];
foreach ($results as $name => $r) {
    $status = $r['code'] === 0 ? 'PASS' : 'FAIL';
    $lines[] = "## $name — $status (exit {$r['code']})\n\n```\n{$r['tail']}\n```\n\n";
}
file_put_contents($md, implode('', $lines));

echo "═══════════════════════════════════════════════════════════\n";
echo $failed === 0 ? "  ALL GATES GREEN\n" : "  $failed GATE(S) FAILED\n";
echo "  Report: $md\n";
echo "═══════════════════════════════════════════════════════════\n";
exit($failed > 0 ? 1 : 0);
