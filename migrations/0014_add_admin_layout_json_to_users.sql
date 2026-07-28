-- 0014_add_admin_layout_json_to_users.sql
-- Purpose: Per-user admin panel board layout. Stores an ordered JSON array of
--          tile ids (e.g. ["users","purchase_orders",...]); NULL = default order.
--          Saved via the "Edit Layout" mode on the admin panel.
--
-- Used by: _mgmt/admin_panel.php (load order + save/reset handlers)
--
-- Safe / idempotent.

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `admin_layout_json` LONGTEXT
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
        COMMENT 'Ordered JSON array of admin_panel tile ids; NULL = default order'
        CHECK (JSON_VALID(`admin_layout_json`) OR `admin_layout_json` IS NULL);
