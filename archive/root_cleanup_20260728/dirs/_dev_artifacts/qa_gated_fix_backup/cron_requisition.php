<?php
// cron_requisition.php - Automated Requisition Engine

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Find parts below threshold with auto_reorder enabled
    $stmt = $pdo->query("SELECT * FROM inventory_parts WHERE stock_level <= minimum_threshold AND auto_reorder = 1 AND lifecycle_status = 'Active'");
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requisitions_created = 0;
    
    foreach ($parts as $part) {
        $vendor_id = $part['primary_vendor_id'];
        if (!$vendor_id) continue;
        
        // Calculate reorder quantity based on max stock or MOQ
        $qty_to_order = $part['maximum_stock'] > $part['stock_level'] 
            ? ($part['maximum_stock'] - $part['stock_level']) 
            : max(1, (int)$part['moq']);
            
        // Check if there is already a draft or pending PO for this part/vendor to avoid duplicates
        $check_stmt = $pdo->prepare("
            SELECT COUNT(*) FROM po_items i
            JOIN purchase_orders p ON i.po_id = p.po_id
            WHERE i.part_id = ? AND p.status IN ('Draft', 'Pending Approval', 'Issued')
        ");
        $check_stmt->execute([$part['part_id']]);
        if ($check_stmt->fetchColumn() > 0) {
            continue; // Already ordered
        }
        
        $total_amount = $qty_to_order * $part['cost_per_unit'];
        $po_number = "PO-AUTO-" . date("Ymd") . "-" . rand(100,999);
        
        $approval_level = "Auto-Approved";
        $status = "Draft"; // Let someone review it before issuing
        
        $pdo->beginTransaction();
        $ins_po = $pdo->prepare("INSERT INTO purchase_orders (po_number, vendor_id, created_by, total_amount, status, approval_level) VALUES (?, ?, 1, ?, ?, ?)");
        $ins_po->execute([$po_number, $vendor_id, $total_amount, $status, $approval_level]);
        $po_id = $pdo->lastInsertId();
        
        $ins_itm = $pdo->prepare("INSERT INTO po_items (po_id, part_id, ordered_qty, unit_price) VALUES (?, ?, ?, ?)");
        $ins_itm->execute([$po_id, $part['part_id'], $qty_to_order, $part['cost_per_unit']]);
        
        $pdo->commit();
        $requisitions_created++;
        echo "Auto-Generated $po_number for Part ID {$part['part_id']} (Qty: $qty_to_order)\n";
    }
    
    echo "Cron Complete. Generated $requisitions_created requisitions.\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Cron Error: " . $e->getMessage());
}
?>
