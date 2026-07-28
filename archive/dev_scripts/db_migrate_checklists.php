<?php
require 'inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // 1. Create pm_checklists table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pm_checklists` (
            `checklist_id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`checklist_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created pm_checklists table.\n";

    // 2. Create pm_checklist_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pm_checklist_items` (
            `item_id` INT(11) NOT NULL AUTO_INCREMENT,
            `checklist_id` INT(11) NOT NULL,
            `task_desc` VARCHAR(255) NOT NULL,
            `expected_time_mins` INT(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (`item_id`),
            CONSTRAINT `fk_checklist_id` FOREIGN KEY (`checklist_id`) REFERENCES `pm_checklists` (`checklist_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "Created pm_checklist_items table.\n";

    // 3. Add checklist_id to pm_schedules table
    try {
        $pdo->exec("ALTER TABLE `pm_schedules` ADD COLUMN `checklist_id` INT(11) NULL DEFAULT NULL AFTER `parts_list`");
        echo "Added checklist_id to pm_schedules.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "checklist_id already exists in pm_schedules.\n";
        } else {
            throw $e;
        }
    }

    // 4. Add checklist_data and started_at to work_orders table
    try {
        $pdo->exec("ALTER TABLE `work_orders` ADD COLUMN `checklist_data` TEXT NULL DEFAULT NULL COMMENT 'JSON array snapshot of checklist items' AFTER `parts_list`");
        echo "Added checklist_data to work_orders.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "checklist_data already exists in work_orders.\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE `work_orders` ADD COLUMN `started_at` DATETIME NULL DEFAULT NULL AFTER `scheduled_date`");
        echo "Added started_at to work_orders.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "started_at already exists in work_orders.\n";
        } else {
            throw $e;
        }
    }

    // 5. Insert allow_checklist_photos to app_settings
    $stmt = $pdo->query("SELECT COUNT(*) FROM app_settings WHERE setting_key = 'allow_checklist_photos'");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO app_settings (category, setting_key, setting_value) VALUES ('Features', 'allow_checklist_photos', '0')");
        echo "Added allow_checklist_photos to app_settings.\n";
    } else {
        echo "allow_checklist_photos already exists in app_settings.\n";
    }

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
}
?>
