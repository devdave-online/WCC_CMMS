-- 0005_add_soft_delete_columns.sql
-- Phase 5: Soft deletes foundation.
-- Adds deleted_at to core entities so records can be "deleted" without hard removal.
-- This preserves history and audit trail.
--
-- Tables affected: active_tickets, work_orders, equipment, inventory_parts
-- Queries will need to filter `deleted_at IS NULL` going forward.

ALTER TABLE `active_tickets`    ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `work_orders`       ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `equipment`         ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `inventory_parts`   ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL;

-- Add helpful indexes
ALTER TABLE `active_tickets`  ADD INDEX IF NOT EXISTS `idx_deleted_at` (`deleted_at`);
ALTER TABLE `work_orders`     ADD INDEX IF NOT EXISTS `idx_deleted_at` (`deleted_at`);
ALTER TABLE `equipment`       ADD INDEX IF NOT EXISTS `idx_deleted_at` (`deleted_at`);
ALTER TABLE `inventory_parts` ADD INDEX IF NOT EXISTS `idx_deleted_at` (`deleted_at`);
