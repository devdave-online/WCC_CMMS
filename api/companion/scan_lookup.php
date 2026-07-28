<?php
/**
 * Companion — universal barcode / QR / DataMatrix lookup.
 *
 * Resolves a scanned code against:
 *   1. equipment.asset_uuid (exact) + WCC|id|uuid|… label payloads
 *   2. inventory_parts.internal_code / serial_number / vendor_sku
 *   3. toolings table IF it exists
 *
 * When equipment is found, also returns live floor work for those assets:
 *   - open_tickets  (active_tickets not CLOSED)  — OT / interventions
 *   - open_work_orders (work_orders not Completed/Cancelled)
 *
 * GET ?code=XXXX
 * → { status, data: { code, kind, hits, count, open_tickets, open_work_orders } }
 */
require_once __DIR__ . '/../../inc/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/api_guard.php';
$pdo = get_wcc_db_connection();

try {
    api_guard_perm('view_equipment');

    $raw = trim((string)($_GET['code'] ?? ''));
    if ($raw === '') {
        echo json_encode(['status' => 'error', 'message' => 'code required']);
        exit;
    }

    // Normalize: strip common label wrapper WCC|<id>|<uuid>|<name>|SN:…
    $code = $raw;
    $uuidFromLabel = null;
    $idFromLabel = null;
    if (stripos($raw, 'WCC|') === 0 || preg_match('/^WCC\|/i', $raw)) {
        $parts = explode('|', $raw);
        if (count($parts) >= 3) {
            $idFromLabel = (int)$parts[1];
            $uuidFromLabel = $parts[2];
            $code = $uuidFromLabel !== '' ? $uuidFromLabel : $code;
        }
    }

    $hits = [];

    // ── Equipment ──
    $eq = null;
    if ($uuidFromLabel) {
        $st = $pdo->prepare("SELECT * FROM equipment WHERE asset_uuid = ? LIMIT 1");
        $st->execute([$uuidFromLabel]);
        $eq = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if (!$eq && $idFromLabel > 0) {
        $st = $pdo->prepare("SELECT * FROM equipment WHERE equip_id = ? LIMIT 1");
        $st->execute([$idFromLabel]);
        $eq = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if (!$eq) {
        $st = $pdo->prepare("SELECT * FROM equipment WHERE asset_uuid = ? LIMIT 1");
        $st->execute([$code]);
        $eq = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if (!$eq) {
        $st = $pdo->prepare(
            "SELECT * FROM equipment
             WHERE asset_uuid LIKE ? OR equip_name LIKE ? OR oem_serial = ?
             LIMIT 5"
        );
        $like = '%' . $code . '%';
        $st->execute([$like, $like, $code]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hits[] = ['kind' => 'equipment', 'data' => $row];
        }
    } else {
        $hits[] = ['kind' => 'equipment', 'data' => $eq];
    }

    // ── Inventory parts ──
    try {
        $st = $pdo->prepare(
            "SELECT * FROM inventory_parts
             WHERE internal_code = ? OR vendor_sku = ? OR serial_number = ?
                OR internal_code LIKE ? OR part_name LIKE ?
             LIMIT 8"
        );
        $like = '%' . $code . '%';
        $st->execute([$code, $code, $code, $like, $like]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hits[] = ['kind' => 'part', 'data' => $row];
        }
    } catch (PDOException $e) {
        // soft — older schemas
    }

    // ── Toolings (table may not exist yet) ──
    try {
        $chk = $pdo->query("SHOW TABLES LIKE 'toolings'");
        if ($chk && $chk->fetch()) {
            $st = $pdo->prepare(
                "SELECT * FROM toolings
                 WHERE deleted_at IS NULL
                   AND (barcode = ? OR asset_tag = ? OR tooling_code = ?
                    OR tooling_name LIKE ?)
                 LIMIT 8"
            );
            $like = '%' . $code . '%';
            $st->execute([$code, $code, $code, $like]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $hits[] = ['kind' => 'tooling', 'data' => $row];
            }
        }
    } catch (PDOException $e) {
        // under development
    }

    // ── Open tickets (OT) + open WOs for every matched equipment ──
    $openTickets = [];
    $openWorkOrders = [];
    $equipIds = [];
    foreach ($hits as $h) {
        if (($h['kind'] ?? '') === 'equipment' && !empty($h['data']['equip_id'])) {
            $equipIds[] = (int)$h['data']['equip_id'];
        }
    }
    $equipIds = array_values(array_unique(array_filter($equipIds)));

    if (!empty($equipIds)) {
        $placeholders = implode(',', array_fill(0, count($equipIds), '?'));

        // Live tickets / OT — not CLOSED
        try {
            $st = $pdo->prepare(
                "SELECT ticket_id, equip_id, report_date, report_time, announced_by, pic,
                        fault_desc, priority, status, created_at
                 FROM active_tickets
                 WHERE equip_id IN ($placeholders)
                   AND UPPER(COALESCE(status, '')) <> 'CLOSED'
                 ORDER BY
                   FIELD(UPPER(status), 'ESCALATED', 'OPEN', 'PENDING', 'HOLD') ASC,
                   created_at DESC
                 LIMIT 40"
            );
            $st->execute($equipIds);
            $openTickets = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $openTickets = [];
        }

        // Open work orders — not Completed / Cancelled
        try {
            $st = $pdo->prepare(
                "SELECT w.wo_id, w.title, w.description, w.equipment_id, e.equip_name,
                        w.assigned_to, w.status, w.scheduled_date, w.completed_date,
                        w.completed_by, w.started_at, w.parts_list, w.checklist_data
                 FROM work_orders w
                 LEFT JOIN equipment e ON w.equipment_id = e.equip_id
                 WHERE w.equipment_id IN ($placeholders)
                   AND UPPER(COALESCE(w.status, '')) NOT IN ('COMPLETED', 'CANCELLED', 'CANCELED')
                 ORDER BY
                   CASE
                     WHEN w.scheduled_date IS NOT NULL AND w.scheduled_date < CURDATE() THEN 0
                     WHEN UPPER(COALESCE(w.status, '')) = 'IN PROGRESS' THEN 1
                     ELSE 2
                   END ASC,
                   w.scheduled_date ASC
                 LIMIT 40"
            );
            $st->execute($equipIds);
            $openWorkOrders = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $openWorkOrders = [];
        }
    }

    $primary = $hits[0]['kind'] ?? 'unknown';
    echo json_encode([
        'status' => 'success',
        'data' => [
            'code' => $raw,
            'kind' => count($hits) === 1 ? $primary : (count($hits) > 1 ? 'mixed' : 'unknown'),
            'hits' => $hits,
            'count' => count($hits),
            'open_tickets' => $openTickets,
            'open_work_orders' => $openWorkOrders,
            'open_ticket_count' => count($openTickets),
            'open_wo_count' => count($openWorkOrders),
        ],
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
