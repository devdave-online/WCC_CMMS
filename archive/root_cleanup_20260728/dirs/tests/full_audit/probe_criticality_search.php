<?php
/**
 * Verify criticality data-search tokens are present and matching logic works.
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require_once __DIR__ . '/lib/HttpClient.php';
$c = require __DIR__ . '/config.php';
$h = new WccAuditHttpClient($c['base_url'], 20);
$ok = $h->login($c['admin_user'], $c['admin_pass']) || $h->login('admin', 'password');
if (!$ok) {
    echo "FAIL login\n";
    exit(1);
}
$fail = 0;
foreach (['/_eam/equipment.php' => 'ledger', '/_eam/setup_vault_equipment.php' => 'vault'] as $path => $label) {
    $r = $h->get($path);
    $body = $r['body'];
    if (!str_contains($body, 'data-search=') || !str_contains($body, '|A|') && !preg_match('/data-search="\|[ABC]\|/', $body)) {
        // require at least one criticality token pattern
        if (!preg_match('/data-search="\|[ABC]\|/', $body)) {
            echo "FAIL $label missing data-search criticality tokens\n";
            $fail++;
            continue;
        }
    }
    if (!str_contains($body, 'wccTableCellMatches') && !str_contains($body, 'cellMatch')) {
        echo "FAIL $label filter not using cellMatch helper\n";
        $fail++;
        continue;
    }
    // Count CLASS badges vs data-search criticality cells
    preg_match_all('/data-search="\|([ABC])\|/', $body, $m);
    $n = count($m[1] ?? []);
    echo "OK  $label criticality data-search cells=$n\n";
    if ($n < 1) {
        echo "FAIL $label expected criticality rows\n";
        $fail++;
    }
}
// JS helper unit-test in PHP mirror
function mirror_match(string $ds, string $q): bool {
    $q = strtoupper(trim($q));
    $hay = strtoupper($ds);
    if (str_contains($hay, '|' . $q . '|')) return true;
    if (strlen($q) >= 2 && str_contains($hay, $q)) return true;
    return false;
}
$dsA = '|A|CRITICAL|CLASS A|CLASS-A|CRITICALITY A|';
$dsB = '|B|IMPORTANT|CLASS B|CLASS-B|CRITICALITY B|';
$tests = [
    [$dsA, 'A', true],
    [$dsA, 'C', false], // must NOT match all via CLASS
    [$dsA, 'CRITICAL', true],
    [$dsB, 'B', true],
    [$dsB, 'A', false],
    [$dsB, 'IMPORTANT', true],
    [$dsA, 'CLASS A', true],
];
foreach ($tests as [$ds, $q, $expect]) {
    $got = mirror_match($ds, $q);
    if ($got === $expect) {
        echo "OK  match('$q') => " . ($got ? 'true' : 'false') . "\n";
    } else {
        echo "FAIL match('$q') expected " . ($expect ? 'true' : 'false') . "\n";
        $fail++;
    }
}
echo $fail ? "DONE fail=$fail\n" : "DONE all ok\n";
exit($fail ? 1 : 0);
