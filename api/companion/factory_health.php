<?php
/**
 * Companion — factory health snapshot (read-only).
 *
 * Maths cloned from _maint/active_tickets.php Factory Health panel
 * (does NOT modify that page):
 *   total_machines = COUNT(equipment)
 *   down_machines  = unique equip_id among live tickets
 *   health_pct     = (total - down) / total * 100  (100 if no equipment)
 *   band: healthy ≥90, degraded ≥75, critical <75
 *
 * GET → { status, data: { health_pct, band, total_machines, down_machines,
 *         live_tickets, by_status, updated_at } }
 */
require_once __DIR__ . '/../../inc/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/api_guard.php';

$pdo = get_wcc_db_connection();

// Companion may arrive with PHPSESSID (loginForm) OR Basic Auth only.
if (!isset($_SESSION['user_id']) && isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    $st = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $st->execute([$_SERVER['PHP_AUTH_USER']]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if ($u && password_verify($_SERVER['PHP_AUTH_PW'], $u['password_hash'])) {
        $st_status = strtolower(trim((string)($u['status'] ?? 'active')));
        if ($st_status === '' || $st_status === 'active') {
            $_SESSION['user_id'] = (int)$u['user_id'];
            $_SESSION['username'] = $u['username'];
            $_SESSION['full_name'] = $u['full_name'] ?? '';
            $_SESSION['role_level'] = (int)$u['role_level'];
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    api_guard_perm('view_tickets');

    // Live tickets — same filter as active_tickets.php dashboard
    $stmt = $pdo->query("
        SELECT equip_id, status
        FROM active_tickets
        WHERE status IN ('OPEN', 'ESCALATED', 'PENDING', 'HOLD')
    ");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_machines = (int)$pdo->query('SELECT COUNT(*) FROM equipment')->fetchColumn();

    $equipIds = [];
    $by_status = [
        'OPEN' => 0,
        'ESCALATED' => 0,
        'PENDING' => 0,
        'HOLD' => 0,
    ];
    foreach ($tickets as $t) {
        $st = strtoupper(trim((string)($t['status'] ?? '')));
        if (isset($by_status[$st])) {
            $by_status[$st]++;
        }
        $eid = $t['equip_id'] ?? null;
        if ($eid !== null && $eid !== '') {
            $equipIds[(string)$eid] = true;
        }
    }
    $down_machines = count($equipIds);
    $live_tickets = count($tickets);

    $health_pct = $total_machines > 0
        ? round((($total_machines - $down_machines) / $total_machines) * 100, 1)
        : 100.0;

    if ($health_pct >= 90) {
        $band = 'healthy';
    } elseif ($health_pct >= 75) {
        $band = 'degraded';
    } else {
        $band = 'critical';
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'health_pct' => $health_pct,
            'band' => $band,
            'total_machines' => $total_machines,
            'down_machines' => $down_machines,
            'live_tickets' => $live_tickets,
            'by_status' => $by_status,
            'updated_at' => date('c'),
        ],
    ]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
