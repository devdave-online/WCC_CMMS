-- 0022_create_donation_prompt_prefs.sql
-- Per-user "buy me a coffee" prompt state for the About modal.
-- status: shown (default/no row) | snoozed (hide until snooze_until) | dismissed (forever)
-- Date: 2026-07-28

CREATE TABLE IF NOT EXISTS `donation_prompt_prefs` (
  `user_id`     INT(11)      NOT NULL,
  `status`      ENUM('shown','snoozed','dismissed') NOT NULL DEFAULT 'shown',
  `snooze_until` DATETIME    DEFAULT NULL,
  `last_action` VARCHAR(40)  DEFAULT NULL COMMENT 'coffee | coffee_snooze | no_coffee',
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_donation_prompt_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
