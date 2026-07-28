<?php
require __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();
$n = (int)$pdo->query("SELECT COUNT(*) FROM uuid_rules WHERE target_entity = 'Tooling'")->fetchColumn();
echo "existing_rules=$n\n";
if ($n > 0) {
    exit(0);
}
$defaults = [
    ['Die', 'TL-DIE-', 3],
    ['Mold', 'TL-MLD-', 3],
    ['Fixture', 'TL-FIX-', 3],
    ['Jig', 'TL-JIG-', 3],
    ['Gauge', 'TL-GAU-', 3],
    ['Hand Tool', 'TL-HND-', 3],
    ['Cutting Tool', 'TL-CUT-', 3],
    ['Other', 'TL-GEN-', 3],
    ['GLOBAL_DEFAULT', 'TL-GEN-', 3],
];
$ins = $pdo->prepare("INSERT INTO uuid_rules (target_entity, category, prefix, serial_length, current_serial, random_chars, char_type) VALUES ('Tooling', ?, ?, ?, ?, 0, 'NUMERIC')");
foreach ($defaults as $d) {
    $st = $pdo->prepare("SELECT tooling_code FROM toolings WHERE tooling_code LIKE ? ORDER BY tooling_id DESC LIMIT 1");
    $st->execute([$d[1] . '%']);
    $last = $st->fetchColumn();
    $serial = 1;
    if ($last && preg_match('/(\d+)$/', (string)$last, $m)) {
        $serial = (int)$m[1] + 1;
    }
    $ins->execute([$d[0], $d[1], $d[2], $serial]);
    echo "seeded {$d[0]} serial=$serial\n";
}
echo "done\n";
