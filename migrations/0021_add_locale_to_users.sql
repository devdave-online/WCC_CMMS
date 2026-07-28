-- 0021_add_locale_to_users.sql
-- Purpose: Per-user UI language preference (web i18n JSON packs).

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `locale` VARCHAR(16) NOT NULL DEFAULT 'en'
        COMMENT 'UI language BCP-47-ish code: en, hi, zh-Hans, pt-BR, …';
