-- 0009_add_role_definitions.sql
-- Move role permission presets out of hardcoded PHP into the database.
-- This allows a proper UI to define/edit role presets (the "presets" used for new users and "Reset to Role").
-- Role levels remain 1-4 for simplicity and performance.
-- Per-user permissions_json still allows fine-grained overrides on top of the role default.

CREATE TABLE IF NOT EXISTS `role_definitions` (
    `role_level` TINYINT(1) NOT NULL PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `permissions_json` TEXT NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed with current production defaults (from rbac.php ROLE_PERMISSIONS)
-- These match the existing behavior exactly.

INSERT IGNORE INTO `role_definitions` (role_level, name, permissions_json, description) VALUES
(1, 'Operator', 
 '{"view_tickets":true,"create_tickets":true,"takeover_tickets":false,"closeout_tickets":false,"view_history":true,"view_statistics":false,"view_equipment":true,"view_inventory":false,"view_vendors":false,"view_work_orders":false,"manage_work_orders":false,"view_purchase_requests":false,"create_purchase_requests":false,"approve_purchase_orders":false,"manage_users":false,"manage_settings":false,"manage_equipment":false,"manage_inventory":false,"manage_vendors":false,"reset_passwords":false}',
 'Basic access - create and view own tickets, limited visibility.'),

(2, 'Technician', 
 '{"view_tickets":true,"create_tickets":true,"takeover_tickets":true,"closeout_tickets":false,"view_history":true,"view_statistics":true,"view_equipment":true,"view_inventory":true,"view_vendors":true,"view_work_orders":true,"manage_work_orders":false,"view_purchase_requests":true,"create_purchase_requests":true,"approve_purchase_orders":false,"manage_users":false,"manage_settings":false,"manage_equipment":false,"manage_inventory":false,"manage_vendors":false,"reset_passwords":false}',
 'Field technicians - can take over and view most operational data.'),

(3, 'Supervisor', 
 '{"view_tickets":true,"create_tickets":true,"takeover_tickets":true,"closeout_tickets":true,"view_history":true,"view_statistics":true,"view_equipment":true,"view_inventory":true,"view_vendors":true,"view_work_orders":true,"manage_work_orders":true,"view_purchase_requests":true,"create_purchase_requests":true,"approve_purchase_orders":false,"manage_users":false,"manage_settings":false,"manage_equipment":true,"manage_inventory":false,"manage_vendors":false,"reset_passwords":false}',
 'Supervisors - full ticket lifecycle + manage equipment and work orders.'),

(4, 'Admin', 
 '{"view_tickets":true,"create_tickets":true,"takeover_tickets":true,"closeout_tickets":true,"view_history":true,"view_statistics":true,"view_equipment":true,"view_inventory":true,"view_vendors":true,"view_work_orders":true,"manage_work_orders":true,"view_purchase_requests":true,"create_purchase_requests":true,"approve_purchase_orders":true,"manage_users":true,"manage_settings":true,"manage_equipment":true,"manage_inventory":true,"manage_vendors":true,"reset_passwords":true}',
 'Full system access including user management and settings.');
