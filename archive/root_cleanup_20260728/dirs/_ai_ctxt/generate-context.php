<?php
/**
 * AI Context Generator for WCC CMMS
 * 
 * Usage:
 *   php _ai_ctxt/generate-context.php
 *   php _ai_ctxt/generate-context.php --live   (includes safe live stats)
 *
 * This script keeps the AI context layer fresh.
 */

$includeLive = in_array('--live', $argv ?? []);

echo "=== WCC AI Context Generator ===\n";
echo "Live data: " . ($includeLive ? 'YES' : 'NO') . "\n\n";

$root = dirname(__DIR__);
$schemaFile = $root . '/schema.sql';
$ctxtDir = $root . '/_ai_ctxt';
$dataModelFile = $ctxtDir . '/DATA_MODEL.md';

if (!file_exists($schemaFile)) {
    die("ERROR: schema.sql not found at $schemaFile\n");
}

$schema = file_get_contents($schemaFile);

// Extract tables with comments
$tables = [];
preg_match_all('/CREATE TABLE IF NOT EXISTS `([^`]+)`\s*\((.*?)\)\s*ENGINE.*?COMMENT\s*=\s*\'([^\']*)\'/s', $schema, $matches, PREG_SET_ORDER);

foreach ($matches as $m) {
    $table = $m[1];
    $comment = $m[3] ?? '';
    $tables[$table] = trim($comment);
}

// Also catch tables without explicit COMMENT in that format
preg_match_all('/CREATE TABLE IF NOT EXISTS `([^`]+)`/', $schema, $simpleMatches);
foreach ($simpleMatches[1] as $t) {
    if (!isset($tables[$t])) $tables[$t] = '';
}

ksort($tables);

// Build generated section
$generated = "\n\n## Auto-Generated from schema.sql\n\n";
$generated .= "**Generated:** " . date('Y-m-d H:i:s') . "\n\n";
$generated .= "### Core Tables\n\n";

foreach ($tables as $table => $comment) {
    $generated .= "- **`{$table}`**";
    if ($comment) $generated .= " — {$comment}";
    $generated .= "\n";
}

$generated .= "\n> Full column definitions and foreign keys are in `schema.sql`.\n";

// Live data section (if requested)
if ($includeLive) {
    require_once $root . '/inc/db.php';
    $pdo = get_wcc_db_connection();

    $generated .= "\n\n### Live Snapshot (safe, anonymized)\n\n";

    $countQueries = [
        'users' => 'SELECT COUNT(*) FROM users',
        'equipment' => 'SELECT COUNT(*) FROM equipment',
        'active_tickets_open' => "SELECT COUNT(*) FROM active_tickets WHERE status IN ('OPEN','PENDING')",
        'work_orders' => 'SELECT COUNT(*) FROM work_orders',
        'inventory_parts' => 'SELECT COUNT(*) FROM inventory_parts',
        'purchase_orders_open' => "SELECT COUNT(*) FROM purchase_orders WHERE status NOT IN ('Completed','Cancelled')",
    ];

    foreach ($countQueries as $label => $sql) {
        try {
            $cnt = $pdo->query($sql)->fetchColumn();
            $generated .= "- {$label}: **{$cnt}**\n";
        } catch (Exception $e) {
            $generated .= "- {$label}: error\n";
        }
    }

    $generated .= "\n*Live data is limited for security. Full details via the API `/api/v1/ai-context?live=1`*\n";
}

// Update DATA_MODEL.md
if (file_exists($dataModelFile)) {
    $current = file_get_contents($dataModelFile);
    $marker = '## Auto-Generated from schema.sql';

    if (strpos($current, $marker) !== false) {
        $current = preg_replace('/' . preg_quote($marker, '/') . '.*$/s', trim($generated), $current);
    } else {
        $current .= "\n" . $generated;
    }

    file_put_contents($dataModelFile, $current);
    echo "✓ Updated DATA_MODEL.md\n";
} else {
    echo "⚠ DATA_MODEL.md not found. Creating basic version...\n";
    file_put_contents($dataModelFile, "# DATA_MODEL.md\n\n" . $generated);
}

// Update manifest last_updated
$manifestFile = $ctxtDir . '/manifest.json';
if (file_exists($manifestFile)) {
    $manifest = json_decode(file_get_contents($manifestFile), true) ?: [];
    $manifest['last_updated'] = date('Y-m-d');
    file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
    echo "✓ Updated manifest.json\n";
}

// Update timestamp in the main ai_agent.ini
$iniFile = $root . '/ai_agent.ini';
if (file_exists($iniFile)) {
    $iniContent = file_get_contents($iniFile);
    $date = date('Y-m-d H:i:s');
    if (preg_match('/last_updated\s*=\s*.*/', $iniContent)) {
        $iniContent = preg_replace('/last_updated\s*=\s*.*/', "last_updated = $date", $iniContent);
    } else {
        $iniContent = preg_replace('/(\[project\])/', "$1\nlast_updated = $date", $iniContent, 1);
    }
    file_put_contents($iniFile, $iniContent);
    echo "✓ Updated last_updated in ai_agent.ini\n";
}

echo "\n✅ AI context generation complete.\n";
echo "Recommend running with --live periodically for fresh counts.\n";
