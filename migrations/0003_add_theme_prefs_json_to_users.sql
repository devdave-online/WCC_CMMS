-- 0003_add_theme_prefs_json_to_users.sql
-- Phase 5
-- Purpose: Add the theme_prefs_json column used by the server-persisted theming system
--          (introduced in Phase 4). Allows per-user storage of custom color preferences
--          that sync between localStorage and the server session.
--
-- Used by: auth.php, login.php, nav.php (load), app_settings.php (save)
-- See: users.theme_prefs_json in code + CMMS_QA_AND_FUTURE_PLAN.md
--
-- Safe / idempotent.

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `theme_prefs_json` LONGTEXT
        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
        COMMENT 'JSON object for dark/light custom theme color prefs (Phase 4+)'
        CHECK (JSON_VALID(`theme_prefs_json`) OR `theme_prefs_json` IS NULL);

-- Optional backfill example (commented - do not run blindly):
-- UPDATE users SET theme_prefs_json = '{}' WHERE theme_prefs_json IS NULL;
