-- ==============================================================
-- 0013 — Procurement approval workflow
--   * New permission `fulfill_purchase_orders` (post-approval logistics:
--     ship / transit / receive / close), split out from approve_purchase_orders.
--   * New role: Storekeeper (level 6) — fulfils approved POs + manages stock,
--     cannot approve spend.
--   * Settings: procurement_workflow_enabled (0/1) + po_auto_approve_limit ($).
-- Idempotent: safe to re-run.
-- ==============================================================

-- Add fulfill_purchase_orders to existing object-type role definitions
-- (admin=true, everyone else=false). Skips [] custom roles and rows already set.
UPDATE `role_definitions`
   SET `permissions_json` = JSON_SET(`permissions_json`, '$.fulfill_purchase_orders',
         CASE WHEN role_level = 4 THEN true ELSE false END)
 WHERE JSON_VALID(`permissions_json`)
   AND JSON_TYPE(`permissions_json`) = 'OBJECT'
   AND JSON_EXTRACT(`permissions_json`, '$.fulfill_purchase_orders') IS NULL;

-- Storekeeper role (level 6; level 5 is the pre-existing "Custom Viewer")
INSERT INTO `role_definitions` (`role_level`, `name`, `permissions_json`) VALUES
(6, 'Storekeeper',
 '{"view_tickets":false,"create_tickets":false,"takeover_tickets":false,"closeout_tickets":false,"view_history":false,"view_statistics":false,"view_equipment":true,"view_inventory":true,"view_vendors":true,"view_work_orders":false,"manage_work_orders":false,"view_purchase_requests":true,"create_purchase_requests":true,"approve_purchase_orders":false,"fulfill_purchase_orders":true,"manage_users":false,"manage_settings":false,"manage_equipment":false,"manage_inventory":true,"manage_vendors":false,"reset_passwords":false,"delete_users":false}')
ON DUPLICATE KEY UPDATE `permissions_json` = VALUES(`permissions_json`);

-- Procurement settings (enabled by default; 0 = no auto-approve limit)
INSERT IGNORE INTO `app_settings` (`category`, `setting_key`, `setting_value`) VALUES
('Procurement', 'procurement_workflow_enabled', '1'),
('Procurement', 'po_auto_approve_limit', '0');
