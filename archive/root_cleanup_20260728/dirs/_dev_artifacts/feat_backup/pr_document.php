<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('approve_purchase_orders');

if (!isset($_GET['po_id'])) {
    die("No Document ID provided.");
}

$po_id = (int)$_GET['po_id'];

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Fetch PO
    $stmt = $pdo->prepare("
        SELECT p.*, v.vendor_name, v.primary_contact_name AS contact_name, v.contact_email AS email, u.username, u.email as requestor_email
        FROM purchase_orders p
        LEFT JOIN vendors_suppliers v ON p.vendor_id = v.vendor_id
        LEFT JOIN users u ON p.created_by = u.user_id
        WHERE p.po_id = ?
    ");
    $stmt->execute([$po_id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$po) die("Document not found.");
    
    // Fetch Items
    $stmt = $pdo->prepare("
        SELECT i.*, p.part_name, p.internal_code 
        FROM po_items i
        LEFT JOIN inventory_parts p ON i.part_id = p.part_id
        WHERE i.po_id = ?
    ");
    $stmt->execute([$po_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch Audit Log
    $stmt = $pdo->prepare("
        SELECT l.*, u.username 
        FROM po_status_logs l
        LEFT JOIN users u ON l.changed_by = u.user_id
        WHERE l.po_id = ?
        ORDER BY l.created_at ASC
    ");
    $stmt->execute([$po_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("DB Error.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($po['po_number']) ?> - PR Document</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #fff; color: #000; margin: 0; padding: 40px; }
        .doc-container { max-width: 800px; margin: 0 auto; border: 1px solid #ccc; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .header-left h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
        .header-left p { margin: 5px 0 0 0; font-size: 14px; color: #555; }
        .header-right { text-align: right; }
        .header-right h2 { margin: 0; font-size: 28px; color: #333; }
        .header-right p { margin: 5px 0 0 0; font-size: 14px; font-weight: bold; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .info-block { border: 1px solid #ddd; padding: 15px; }
        .info-block h3 { margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: #666; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .info-block p { margin: 5px 0; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 14px; }
        th { background: #f9f9f9; text-transform: uppercase; font-size: 12px; }
        .total-row td { font-weight: bold; background: #f1f1f1; }
        
        .audit-trail { border: 1px solid #ddd; padding: 15px; margin-bottom: 30px; }
        .audit-trail h3 { margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: #666; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .audit-item { font-size: 13px; margin: 5px 0; display: flex; justify-content: space-between; border-bottom: 1px dashed #eee; padding: 5px 0; }
        
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #888; border-top: 1px solid #ddd; padding-top: 20px; }
        
        @media print {
            body { padding: 0; background: none; }
            .doc-container { border: none; box-shadow: none; padding: 0; }
            button.print-btn { display: none; }
        }
        
        .print-btn { background: #000; color: #fff; border: none; padding: 10px 20px; cursor: pointer; font-size: 14px; border-radius: 4px; display: block; margin: 0 auto 20px auto; }
        .print-btn:hover { background: #333; }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨️ Print Document</button>

<div class="doc-container">
    <div class="header">
        <div class="header-left">
            <h1>Enterprise Manufacturing</h1>
            <p>123 Industrial Parkway<br>Detroit, MI 48201</p>
        </div>
        <div class="header-right">
            <h2>PURCHASE REQUISITION</h2>
            <p>Document #: <?= htmlspecialchars($po['po_number']) ?></p>
            <p style="color:#666;">Date: <?= htmlspecialchars(date('F j, Y', strtotime($po['created_at']))) ?></p>
        </div>
    </div>
    
    <div class="info-grid">
        <div class="info-block">
            <h3>Vendor Information</h3>
            <p><strong><?= htmlspecialchars($po['vendor_name'] ?? 'Unknown Vendor') ?></strong></p>
            <p>Contact: <?= htmlspecialchars($po['contact_name'] ?? 'N/A') ?></p>
            <p>Email: <?= htmlspecialchars($po['email'] ?? 'N/A') ?></p>
        </div>
        <div class="info-block">
            <h3>Requisition Details</h3>
            <p><strong>Requestor:</strong> <?= htmlspecialchars($po['username']) ?></p>
            <p><strong>Routing Level:</strong> <?= htmlspecialchars($po['approval_level']) ?></p>
            <p><strong>Current Status:</strong> <?= htmlspecialchars($po['status']) ?></p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Description</th>
                <th>Qty Ordered</th>
                <th>Qty Received</th>
                <th>Unit Price</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $calculated_total = 0;
            foreach($items as $itm): 
                $line_total = $itm['ordered_qty'] * $itm['unit_price'];
                $calculated_total += $line_total;
            ?>
            <tr>
                <td><?= htmlspecialchars($itm['internal_code'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($itm['part_name'] ?? 'Unknown Part') ?></td>
                <td><?= htmlspecialchars($itm['ordered_qty']) ?></td>
                <td><?= htmlspecialchars($itm['received_qty']) ?></td>
                <td>$<?= number_format($itm['unit_price'], 2) ?></td>
                <td>$<?= number_format($line_total, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="5" style="text-align: right;">GRAND TOTAL:</td>
                <td>$<?= number_format($calculated_total, 2) ?></td>
            </tr>
        </tbody>
    </table>
    
    <div class="audit-trail">
        <h3>IATF/VDA Compliance Audit Trail</h3>
        <?php if(count($logs) > 0): ?>
            <?php foreach($logs as $log): ?>
                <div class="audit-item">
                    <span><strong><?= htmlspecialchars($log['created_at']) ?></strong></span>
                    <span>Action: <?= htmlspecialchars($log['action_type']) ?> <?php if($log['status_to']) echo "(&rarr; " . htmlspecialchars($log['status_to']) . ")"; ?></span>
                    <span>User: <strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="font-size:13px; color:#888;">No immutable logs recorded yet.</p>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        This document is electronically generated and recorded by the Enterprise PO System.<br>
        Validation Signature Hash: <?= hash('sha256', $po['po_number'] . $po['created_at'] . $calculated_total) ?>
    </div>
</div>

</body>
</html>

