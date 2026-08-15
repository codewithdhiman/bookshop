-- Migration: add_two_factor_auth.sql
-- Adds two-factor fields to users table and creates recovery codes table

ALTER TABLE users
  ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN two_factor_secret VARCHAR(128) DEFAULT NULL,
  ADD COLUMN two_factor_verified_at DATETIME DEFAULT NULL;

CREATE TABLE IF NOT EXISTS user_recovery_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_urc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
