<?php
/**
 * Companion App — work order start / complete (JSON).
 *
 * Complementary to web `_maint/wo_takeover.php` form posts. Does not modify that page.
 *
 * POST JSON:
 *   { "action": "start", "wo_id": 35 }
 *   { "action": "complete", "wo_id": 35, "notes": "...",
 *     "parts_consumed": [ { "part_id": 10, "qty": 1 } ],
 *     "checklist_data": [ ... optional updated checklist JSON array ... ] }
 *
 * GET ?wo_id=35  → full WO row + equip_name
 */
require_once __DIR__ . '/../../inc/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/api_guard.php';
require_once __DIR__ . '/../../inc/audit.php';
$pdo = get_wcc_db_connection();

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        api_guard_perm('view_work_orders');
        $wo_id = (int)($_GET['wo_id'] ?? 0);
        if ($wo_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'wo_id required']);
            exit;
        }
        $stmt = $pdo->prepare(
            "SELECT w.*, e.equip_name
             FROM work_orders w
             LEFT JOIN equipment e ON w.equipment_id = e.equip_id
             WHERE w.wo_id = ?"
        );
        $stmt->execute([$wo_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Work order not found']);
            exit;
        }
        echo json_encode(['status' => 'success', 'data' => $row]);
        exit;
    }

    if ($method !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    api_guard_perm('manage_work_orders');
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';
    $wo_id = (int)($data['wo_id'] ?? 0);
    if ($wo_id <= 0 || $action === '') {
        echo json_encode(['status' => 'error', 'message' => 'action and wo_id required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM work_orders WHERE wo_id = ?");
    $stmt->execute([$wo_id]);
    $wo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$wo) {
        echo json_encode(['status' => 'error', 'message' => 'Work order not found']);
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];

    if ($action === 'start') {
        if (in_array($wo['status'], ['Completed', 'Cancelled'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order is locked']);
            exit;
        }
        $pdo->prepare(
            "UPDATE work_orders
             SET started_at = COALESCE(started_at, NOW()),
                 status = 'In Progress',
                 assigned_to = ?
             WHERE wo_id = ?"
        )->execute([$user_id, $wo_id]);

        wcc_audit_log(
            'work_order.start',
            'work_orders',
            (string)$wo_id,
            ['status' => $wo['status']],
            ['status' => 'In Progress', 'assigned_to' => $user_id],
            'Companion start work'
        );

        echo json_encode(['status' => 'success', 'message' => 'Work started', 'data' => ['wo_id' => $wo_id, 'status' => 'In Progress']]);
        exit;
    }

    if ($action === 'complete') {
        if (in_array($wo['status'], ['Completed', 'Cancelled'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order is already closed']);
            exit;
        }

        $notes = trim((string)($data['notes'] ?? ''));
        $parts = $data['parts_consumed'] ?? [];
        if (!is_array($parts)) $parts = [];

        // Optional checklist update (array or JSON string)
        $checklist_json = $wo['checklist_data'];
        if (isset($data['checklist_data'])) {
            if (is_string($data['checklist_data'])) {
                $checklist_json = $data['checklist_data'];
            } else {
                $checklist_json = json_encode($data['checklist_data']);
            }
        }

        // Consume stock — same ledger pattern as submit_takeover / wo_takeover
        $used_parts_data = [];
        if (!empty($parts)) {
            require_once __DIR__ . '/../../inc/reorder.php';
            $lookup = $pdo->prepare("SELECT stock_level, part_name, internal_code FROM inventory_parts WHERE part_id = ?");
            $decr   = $pdo->prepare("UPDATE inventory_parts SET stock_level = GREATEST(stock_level - ?, 0) WHERE part_id = ?");
            $ledger = $pdo->prepare(
                "INSERT INTO inventory_ledger (part_id, change_qty, reason, reference_type, reference_id, actor_user_id)
                 VALUES (?, ?, 'wo_consume', 'work_orders', ?, ?)"
            );
            $reorder_check = [];
            foreach ($parts as $p) {
                $pid = (int)($p['part_id'] ?? 0);
                $qty = (int)($p['qty'] ?? 0);
                if ($pid <= 0 || $qty <= 0) continue;
                $lookup->execute([$pid]);
                $row = $lookup->fetch(PDO::FETCH_ASSOC);
                if (!$row) continue;
                $actual = min($qty, (int)$row['stock_level']);
                if ($actual <= 0) continue;
                $decr->execute([$actual, $pid]);
                $ledger->execute([$pid, -$actual, (string)$wo_id, $user_id]);
                $used_parts_data[] = [
                    'part_id' => $pid,
                    'qty' => $actual,
                    'part_name' => $row['part_name'],
                    'internal_code' => $row['internal_code'],
                ];
                $reorder_check[$pid] = true;
            }
            foreach (array_keys($reorder_check) as $rpid) {
                wcc_check_and_reorder($pdo, (int)$rpid, $user_id);
            }
        }

        if (!empty($used_parts_data)) {
            $strs = [];
            foreach ($used_parts_data as $u) {
                $strs[] = $u['part_name'] . ' (' . $u['internal_code'] . ') x' . $u['qty'];
            }
            $notes .= ($notes !== '' ? "\n" : '') . 'Parts actually consumed: ' . implode(', ', $strs);
        }

        $parts_json = json_encode($used_parts_data);
        $stmt = $pdo->prepare(
            "UPDATE work_orders
             SET status = 'Completed',
                 description = CONCAT(IFNULL(description,''), '\n\nTechnician Notes: ', ?),
                 parts_list = ?,
                 checklist_data = ?,
                 completed_date = NOW(),
                 completed_by = ?
             WHERE wo_id = ?"
        );
        $stmt->execute([$notes, $parts_json, $checklist_json, $user_id, $wo_id]);

        wcc_audit_log(
            'work_order.completed',
            'work_orders',
            (string)$wo_id,
            ['status' => $wo['status']],
            ['status' => 'Completed', 'completed_by' => $user_id],
            'Companion complete work'
        );

        require_once __DIR__ . '/../../inc/notifications.php';
        $woTitle = $wo['title'] ?? ('WO #' . $wo_id);
        $msg = 'Work order completed: ' . $woTitle . ' (#' . $wo_id . ')';
        $link = '/_maint/work_orders.php';
        if (!empty($wo['assigned_to']) && (int)$wo['assigned_to'] !== $user_id) {
            wcc_notify((int)$wo['assigned_to'], 'wo_completed', $msg, $link, 'success');
        }
        // Union-dedupe: view_work_orders + view_statistics → one row per user
        wcc_notify_perms(['view_work_orders', 'view_statistics'], 'wo_completed', $msg, $link, 'success', $user_id);

        echo json_encode([
            'status' => 'success',
            'message' => 'Work order completed',
            'data' => ['wo_id' => $wo_id, 'status' => 'Completed']
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
