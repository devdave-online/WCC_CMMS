<?php
/**
 * DEPRECATED (Phase 5)
 * 
 * This was an early one-off migration script for the big equipment table expansion
 * (asset_uuid, oem_*, criticality, pm intervals, etc.) + equipment_bom.
 *
 * Superseded by the new formal migrations/ system.
 * See: migrations/ (0001+, migrate.php), README.md, and CMMS_QA_AND_FUTURE_PLAN.md
 *
 * Do not use for new setups. Kept for reference / historical audit only.
 * It still uses raw PDO (pre-centralization).
 */

// Migration Script for Equipment Ledger Overhaul (historical)
$host = 'localhost'; 
$db = 'workshop_db'; 
$user = 'root'; 
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Starting migration...\n";

    // 1. Alter Equipment Table
    $alterSQL = "
        ALTER TABLE equipment
        ADD COLUMN asset_uuid VARCHAR(36) UNIQUE AFTER equip_id,
        ADD COLUMN oem_brand VARCHAR(100) AFTER asset_uuid,
        ADD COLUMN oem_model VARCHAR(100) AFTER oem_brand,
        ADD COLUMN oem_serial VARCHAR(100) AFTER oem_model,
        ADD COLUMN criticality ENUM('A', 'B', 'C') DEFAULT 'B' AFTER category,
        
        ADD COLUMN plant_name VARCHAR(100) AFTER equipment_type,
        ADD COLUMN line_name VARCHAR(100) AFTER plant_name,
        ADD COLUMN station_name VARCHAR(100) AFTER line_name,
        ADD COLUMN geo_coords VARCHAR(100) AFTER station_name,
        
        ADD COLUMN vendor_id INT NULL AFTER date_of_purchase,
        ADD COLUMN po_value DECIMAL(12, 2) DEFAULT 0.00 AFTER vendor_id,
        ADD COLUMN fat_date DATE NULL AFTER po_value,
        ADD COLUMN sat_date DATE NULL AFTER fat_date,
        ADD COLUMN is_active BOOLEAN DEFAULT 0 AFTER sat_date,
        
        ADD COLUMN lifecycle_years INT DEFAULT 10 AFTER is_active,
        ADD COLUMN depreciation_rule VARCHAR(100) AFTER lifecycle_years,
        ADD COLUMN warranty_expiry DATE NULL AFTER depreciation_rule,
        ADD COLUMN eol_date DATE NULL AFTER warranty_expiry,
        
        ADD COLUMN base_speed VARCHAR(50) AFTER eol_date,
        ADD COLUMN base_pressure VARCHAR(50) AFTER base_speed,
        ADD COLUMN base_temp VARCHAR(50) AFTER base_pressure,
        ADD COLUMN base_voltage VARCHAR(50) AFTER base_temp,
        
        ADD COLUMN pm_hours_interval INT NULL AFTER base_voltage,
        ADD COLUMN pm_days_interval INT NULL AFTER pm_hours_interval,
        ADD COLUMN last_pm_date DATE NULL AFTER pm_days_interval,
        
        ADD COLUMN loto_protocol TEXT AFTER last_pm_date,
        ADD COLUMN sop_link VARCHAR(255) AFTER loto_protocol;
    ";

    try {
        $pdo->exec($alterSQL);
        echo "Successfully altered 'equipment' table.\n";
        
        // Generate UUIDs for existing equipment
        $stmt = $pdo->query("SELECT equip_id FROM equipment WHERE asset_uuid IS NULL");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $upd = $pdo->prepare("UPDATE equipment SET asset_uuid = ? WHERE equip_id = ?");
            $upd->execute([$uuid, $row['equip_id']]);
        }
        echo "Generated UUIDs for existing equipment.\n";
        
        // Setup initial dummy data for existing equipment to prevent them from being completely empty
        $pdo->exec("UPDATE equipment SET 
            criticality = 'A', 
            is_active = 1, 
            warranty_expiry = DATE_ADD(CURDATE(), INTERVAL 2 YEAR) 
            WHERE equip_id IN (1, 4)");
            
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') { // Duplicate column
            echo "Columns already exist in 'equipment'. Skipping alter...\n";
        } else {
            throw $e;
        }
    }

    // 2. Create BOM Table
    $createBOM = "
        CREATE TABLE IF NOT EXISTS equipment_bom (
            bom_id INT AUTO_INCREMENT PRIMARY KEY,
            equip_id INT NOT NULL,
            part_id INT NOT NULL,
            quantity INT DEFAULT 1,
            FOREIGN KEY (equip_id) REFERENCES equipment(equip_id) ON DELETE CASCADE,
            FOREIGN KEY (part_id) REFERENCES inventory_parts(part_id) ON DELETE CASCADE,
            UNIQUE KEY unique_bom_part (equip_id, part_id)
        ) ENGINE=InnoDB;
    ";
    
    $pdo->exec($createBOM);
    echo "Successfully created 'equipment_bom' table.\n";

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    die("Migration Failed: " . $e->getMessage() . "\n");
}
?>
