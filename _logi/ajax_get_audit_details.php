<?php
// Details for the inventory audit overlay — same permission as the audit page.
require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap
require_once __DIR__ . '/../rbac.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized');
}
if (!can('view_inventory')) {
    http_response_code(403);
    die('<div style="color:#ef4444;">Access Denied: inventory permission required.</div>');
}

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!$type || !$id) {
    die('<div style="color:#ef4444;">Invalid parameters.</div>');
}

try {
    if ($type === 'work_orders') {
        $stmt = $pdo->prepare("
            SELECT w.*, e.equip_name as equipment_name, u.username as assigned_user
            FROM work_orders w
            LEFT JOIN equipment e ON w.equipment_id = e.equip_id
            LEFT JOIN users u ON w.assigned_to = u.user_id
            WHERE w.wo_id = ?
        ");
        $stmt->execute([$id]);
        $wo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wo) {
            die('<div style="color:#ef4444;">Work Order not found.</div>');
        }

        $badgeStyle = 'background:rgba(255,255,255,0.1);';
        if ($wo['status'] === 'Completed') $badgeStyle = 'background:rgba(16,185,129,0.2); color:#10b981; border:1px solid #10b981;';
        if ($wo['status'] === 'In Progress') $badgeStyle = 'background:rgba(59,130,246,0.2); color:#3b82f6; border:1px solid #3b82f6;';

        echo '<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-bottom: 15px;">';
        echo '<div><strong style="color:var(--text-secondary); display:block; font-size:0.85em; text-transform:uppercase;">WO Title</strong><span style="color:var(--text-primary);">'.htmlspecialchars($wo['title']).'</span></div>';
        echo '<div><strong style="color:var(--text-secondary); display:block; font-size:0.85em; text-transform:uppercase;">Equipment</strong><span style="color:var(--text-primary);">'.htmlspecialchars($wo['equipment_name'] ?? 'N/A').'</span></div>';
        echo '<div><strong style="color:var(--text-secondary); display:block; font-size:0.85em; text-transform:uppercase;">Assigned Tech</strong><span style="color:var(--text-primary);">'.htmlspecialchars($wo['assigned_user'] ?? 'Unassigned').'</span></div>';
        echo '<div><strong style="color:var(--text-secondary); display:block; font-size:0.85em; text-transform:uppercase;">Status</strong><span style="padding:2px 8px; border-radius:12px; font-size:0.85em; font-weight:bold; '.$badgeStyle.'">'.htmlspecialchars($wo['status']).'</span></div>';
        echo '</div>';

        if (!empty($wo['description'])) {
            echo '<div style="margin-top:10px; padding:15px; background:rgba(0,0,0,0.2); border-radius:8px; border-left:3px solid var(--text-accent);">';
            echo '<strong style="color:var(--text-secondary); font-size:0.85em; text-transform:uppercase;">Instructions</strong><br>';
            echo '<span style="color:var(--text-primary); font-size:0.95em;">'.nl2br(htmlspecialchars($wo['description'])).'</span>';
            echo '</div>';
        }

    } elseif ($type === 'purchase_orders') {
        $stmt = $pdo->prepare("
            SELECT p.*, v.vendor_name 
            FROM purchase_orders p
            LEFT JOIN vendors_suppliers v ON p.vendor_id = v.vendor_id
            WHERE p.po_id = ?
        ");
        $stmt->execute([$id]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$po) {
            die('<div style="color:#ef4444;">Purchase Order not found.</div>');
        }

        echo '<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-bottom: 15px;">';
        echo '<div><strong style="color:var(--text-secondary); display:block; font-size:0.85em; text-transform:uppercase;">PO Number</strong><span style="color:var(--text-primary);">'.htmlspecialchars($po['po_number']).'</span></div>';
        echo '<div><strong style="color:var(--text-secondary); display:block; font-size:0.85em; text-transform:uppercase;">Vendor</strong><span style="color:var(--text-primary);">'.htmlspecialchars($po['vendor_name'] ?? 'Unknown').'</span></div>';
        echo '<div><strong style="color:var(--text-secondary); display:block; font-size:0.85em; text-transform:uppercase;">Total Amount</strong><span style="color:var(--text-accent); font-family:monospace; font-size:1.1em;">$'.number_format($po['total_amount'], 2).'</span></div>';
        echo '<div><strong style="color:var(--text-secondary); display:block; font-size:0.85em; text-transform:uppercase;">Status</strong><span style="color:var(--text-primary);">'.htmlspecialchars($po['status']).'</span></div>';
        echo '</div>';

        // Fetch PO Items
        $itemsStmt = $pdo->prepare("
            SELECT pi.*, p.part_name, p.internal_code
            FROM po_items pi
            LEFT JOIN inventory_parts p ON pi.part_id = p.part_id
            WHERE pi.po_id = ?
        ");
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($items) {
            echo '<table class="items-table" style="background:rgba(0,0,0,0.2); border-radius:8px; overflow:hidden;">';
            echo '<thead><tr><th>SKU</th><th>Part Name</th><th>Ordered</th><th>Received</th><th>Unit Price</th><th>Status</th></tr></thead><tbody>';
            foreach ($items as $i) {
                echo '<tr>';
                echo '<td style="color:var(--text-accent);">'.htmlspecialchars($i['internal_code']).'</td>';
                echo '<td>'.htmlspecialchars($i['part_name']).'</td>';
                echo '<td>'.(int)$i['ordered_qty'].'</td>';
                echo '<td>'.(int)$i['received_qty'].'</td>';
                echo '<td style="font-family:monospace;">$'.number_format($i['unit_price'], 2).'</td>';
                echo '<td>'.htmlspecialchars($i['status']).'</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

    } else {
        echo '<div style="color:var(--text-secondary);">Details not available for this reference type.</div>';
    }
} catch (Exception $e) {
    echo '<div style="color:#ef4444;">Error fetching details: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
