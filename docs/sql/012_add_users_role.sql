-- Add role column for users and index it
-- Run once in your MySQL database.

ALTER TABLE users
  ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password,
  ADD INDEX idx_users_role (role);

-- Promote a specific user to admin (replace the email)
-- UPDATE users SET role='admin' WHERE email='admin@example.com';
