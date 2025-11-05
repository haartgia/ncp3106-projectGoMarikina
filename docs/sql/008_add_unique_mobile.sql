-- Ensure mobile numbers are unique per user
-- Run this after cleaning up any duplicate mobile values in the users table.
-- If duplicates exist, this ALTER will fail with error 1062; resolve duplicates first.

ALTER TABLE users
  ADD UNIQUE INDEX idx_users_mobile (mobile);
