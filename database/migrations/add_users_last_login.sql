-- Migration: add_users_last_login.sql
-- Adds last_login column to users for tracking last login timestamp

ALTER TABLE users
  ADD COLUMN last_login DATETIME DEFAULT NULL;

-- Optional: add index to speed up sorting by last_login
CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login);
