<?php
// seed_mock_data.php
require_once __DIR__ . '/inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $pdo->beginTransaction();

    // 1. Departments
    $depts = [
        ['dept_name' => 'Maintenance', 'budget_allocated' => 500000.00, 'budget_consumed' => 125000.00],
        ['dept_name' => 'Production', 'budget_allocated' => 850000.00, 'budget_consumed' => 400000.00],
        ['dept_name' => 'IT / Automation', 'budget_allocated' => 200000.00, 'budget_consumed' => 85000.00]
    ];
    $stmt = $pdo->prepare("INSERT INTO departments (dept_name, budget_allocated, budget_consumed) VALUES (?, ?, ?)");
    foreach ($depts as $d) {
        $stmt->execute([$d['dept_name'], $d['budget_allocated'], $d['budget_consumed']]);
    }

    // 2. Vendors
    $vendors = [
        ['Rockwell Automation', 'Industrial Controls & PLCs', 'automation@rockwell.test', 'Net 30'],
        ['Grainger', 'MRO Supplies & Tools', 'sales@grainger.test', 'Net 15'],
        ['Fastenal', 'Fasteners & Safety Gear', 'b2b@fastenal.test', 'Net 30'],
        ['SMC Corporation', 'Pneumatics & Actuators', 'orders@smc.test', 'Net 45'],
        ['Siemens', 'Drives & Motors', 'industrial@siemens.test', 'Net 60']
    ];
    
    // Store inserted IDs dynamically to avoid constraint failures
    $inserted_vendors = [];
    $stmt = $pdo->prepare("INSERT INTO vendors_suppliers (vendor_name, vendor_type, contact_email, payment_terms) VALUES (?, ?, ?, ?)");
    foreach ($vendors as $v) {
        $stmt->execute($v);
        $inserted_vendors[] = $pdo->lastInsertId();
    }

    // 3. Inventory Parts
    $parts = [
        ['Allen-Bradley ControlLogix 5580', 'PLC-AB-5580', 1, 12, 1250.00, 'Each', 'Active'],
        ['SKF Explorer Deep Groove Ball Bearing', 'BRG-SKF-6205', 2, 50, 24.50, 'Each', 'Active'],
        ['Omron E2E Proximity Sensor', 'SNR-OMR-E2E', 1, 30, 85.00, 'Each', 'Active'],
        ['SMC Pneumatic Cylinder', 'CYL-SMC-C85', 4, 15, 145.00, 'Each', 'Active'],
        ['Siemens SINAMICS V20 Drive', 'DRV-SIE-V20', 5, 5, 450.00, 'Each', 'Active'],
        ['Fluke 87V Multimeter', 'TOL-FLK-87V', 2, 3, 499.00, 'Each', 'Active'],
        ['Festo Solenoid Valve', 'VAL-FST-VUVS', 4, 25, 112.00, 'Each', 'Active'],
        ['3M Safety Glasses (Box of 20)', 'PPE-3M-GLS', 3, 10, 45.00, 'Box', 'Active'],
        ['Loctite 242 Threadlocker', 'CHM-LOC-242', 3, 15, 18.50, 'Bottle', 'Active'],
        ['Banner Engineering Safety Light Curtain', 'SAF-BAN-LC', 1, 2, 850.00, 'Set', 'Active']
    ];
    
    $inserted_parts = [];
    $stmt = $pdo->prepare("INSERT INTO inventory_parts (part_name, internal_code, manufacturer_id, stock_level, cost_per_unit, uom, lifecycle_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($parts as $idx => $p) {
        $p[2] = $inserted_vendors[$idx % count($inserted_vendors)];
        $stmt->execute($p);
        $inserted_parts[] = $pdo->lastInsertId();
    }
    
    $stmt = $pdo->query("SELECT dept_id FROM departments LIMIT 10");
    $all_depts = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Ensure Admin exists
    $stmt = $pdo->query("SELECT user_id FROM users WHERE username = 'admin' LIMIT 1");
    $admin_id = $stmt->fetchColumn();
    if (!$admin_id) {
        $hash = password_hash('password1', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password_hash, role_level, badge_number) VALUES ('admin', ?, 4, 'ADM-001')")->execute([$hash]);
        $admin_id = $pdo->lastInsertId();
    }

    // 4. Purchase Orders
    $statuses = ['Draft', 'Pending Approval', 'Issued', 'In Transit', 'Partially Received', 'Fully Received'];
    $po_stmt = $pdo->prepare("INSERT INTO purchase_orders (po_number, vendor_id, dept_id, created_by, total_amount, status, approval_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $po_item_stmt = $pdo->prepare("INSERT INTO po_items (po_id, part_id, ordered_qty, received_qty, unit_price, status) VALUES (?, ?, ?, ?, ?, ?)");
    $log_stmt = $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, changed_by) VALUES (?, ?, ?, ?, ?)");

    for ($i = 1; $i <= 15; $i++) {
        $vendor_id = $inserted_vendors[array_rand($inserted_vendors)];
        $dept_id = $all_depts[array_rand($all_depts)];
        $status = $statuses[array_rand($statuses)];
        $po_number = "PR-2026" . str_pad($i, 4, '0', STR_PAD_LEFT);
        
        $total = 0;
        $items = [];
        $num_items = rand(1, 3);
        for ($j = 0; $j < $num_items; $j++) {
            $part_id = $inserted_parts[array_rand($inserted_parts)];
            $qty = rand(1, 10);
            $price = rand(20, 1000) + (rand(0, 99) / 100);
            $total += ($qty * $price);
            $rcv_qty = ($status === 'Fully Received') ? $qty : (($status === 'Partially Received') ? rand(1, $qty - 1) : 0);
            $item_status = ($rcv_qty == $qty) ? 'Received' : 'Pending';
            $items[] = [$part_id, $qty, $rcv_qty, $price, $item_status];
        }

        $po_stmt->execute([$po_number, $vendor_id, $dept_id, $admin_id, $total, $status, 'Auto-Approved']);
        $po_id = $pdo->lastInsertId();

        foreach ($items as $item) {
            $po_item_stmt->execute([$po_id, $item[0], $item[1], $item[2], $item[3], $item[4]]);
        }

        $log_stmt->execute([$po_id, 'Mock Data Generated', 'Draft', $status, $admin_id]);
    }

    $pdo->commit();
    echo "Mock data seeded successfully.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Failed: " . $e->getMessage() . "\n";
}
