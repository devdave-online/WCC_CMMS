<?php
require 'inc/db.php';
$pdo = get_wcc_db_connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    $uuid = 'test-uuid-1234';
    $name = 'Test Name';
    $oem_brand = '';
    $oem_model = '';
    $oem_serial = '';
    $cat = 'SomeCategory';
    $criticality = 'B';
    $workshop_id = null;
    $line_id = null;
    $po_value = 0.00;
    $lifecycle = 10;
    $is_active = 0;
    $fat_date = null;
    $sat_date = null;
    $b_speed = '';
    $b_press = '';
    $pm_days = null;
    $tech_details = '[]';
    
    $sql = "INSERT INTO equipment (
        asset_uuid, equip_name, oem_brand, oem_model, oem_serial,
        category, criticality, workshop_id, line_id,
        po_value, lifecycle_years, is_active, fat_date, sat_date,
        base_speed, base_pressure, pm_days_interval, technical_details
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $uuid, $name, $oem_brand, $oem_model, $oem_serial,
        $cat, $criticality, $workshop_id, $line_id,
        $po_value, $lifecycle, $is_active, $fat_date, $sat_date,
        $b_speed, $b_press, $pm_days, $tech_details
    ]);
    echo "Success!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
