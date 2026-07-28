<?php
/**
 * Apply the demo BOM + lifecycle overrides to an ALREADY-seeded database, without a
 * full reseed (which would wipe accounts like admin/admin). Re-runnable and CLI-only.
 *
 *   php demo/apply_bom.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/bom_map.php';

$pdo = get_wcc_db_connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$r = wcc_demo_apply_bom($pdo, false);
echo "  equipment_bom rows added: {$r['bom_rows']}\n";
echo "  lifecycle overrides set:  {$r['lifecycle_set']}\n";
echo "  skipped (unmatched keys): {$r['skipped']}\n";
