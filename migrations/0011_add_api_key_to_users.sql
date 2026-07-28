-- 0011_add_api_key_to_users.sql
-- Adds the api_key column the REST v1 API authenticates against (api/v1/bootstrap.php).
-- Without it, every X-API-Key request threw a SQL error. Nullable + unique.

ALTER TABLE `users`
  ADD COLUMN `api_key` VARCHAR(64) NULL UNIQUE
  COMMENT 'REST API v1 key (X-API-Key). NULL = no key issued.'
  AFTER `permissions_json`;
