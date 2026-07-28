<?php
/**
 * Companion — toolings list/search (stub-ready).
 *
 * If `toolings` table does not exist, returns empty success (UI placeholder).
 * When table is created, supports ?search= and ?barcode= filters.
 *
 * Expected columns (flexible): tooling_id, tooling_name, tooling_code, barcode,
 * asset_tag, category, status, location, notes
 */
require_once __DIR__ . '/../../inc/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../../inc/api_guard.php';
api_guard_login();

require_once __DIR__ . '/../../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $chk = $pdo->query("SHOW TABLES LIKE 'toolings'");
    if (!$chk || !$chk->fetch()) {
        echo json_encode([
            'status' => 'success',
            'data' => [],
            'meta' => ['table_exists' => false, 'message' => 'Toolings table not created yet'],
        ]);
        exit;
    }

    $search = trim((string)($_GET['search'] ?? ''));
    $barcode = trim((string)($_GET['barcode'] ?? ''));
    $sql = "SELECT * FROM toolings WHERE deleted_at IS NULL";
    $params = [];
    if ($barcode !== '') {
        $sql .= " AND (barcode = ? OR asset_tag = ? OR tooling_code = ?)";
        $params[] = $barcode;
        $params[] = $barcode;
        $params[] = $barcode;
    } elseif ($search !== '') {
        $sql .= " AND (tooling_name LIKE ? OR tooling_code LIKE ? OR barcode LIKE ? OR asset_tag LIKE ?)";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like];
    }
    $sql .= " ORDER BY tooling_id DESC LIMIT 50";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'status' => 'success',
        'data' => $rows,
        'meta' => ['table_exists' => true, 'returned' => count($rows)],
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'success', 'data' => [], 'meta' => ['table_exists' => false]]);
}
