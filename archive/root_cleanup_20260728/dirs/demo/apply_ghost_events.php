<?php
/**
 * demo/apply_ghost_events.php — CLI-only. Applies demo/ghost_events.php to the current
 * database without a full reseed (so the live demo shows Ghost/On-Hold + classified
 * events immediately). Idempotent.
 *
 *   php demo/apply_ghost_events.php
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/ghost_events.php';

$pdo = get_wcc_db_connection();
$res = wcc_demo_apply_ghost_events($pdo);

echo "Ghost/On-Hold demo events applied:\n";
echo "  ghost tickets : {$res['ghost_tickets']}\n";
echo "  ghost actions : {$res['ghost_actions']}\n";
echo "  reclassified  : {$res['reclassified']} (to inspection / no_fault / request)\n";
