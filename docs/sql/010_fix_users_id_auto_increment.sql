-- Fix users.id to be an AUTO_INCREMENT primary key
-- Run this once on your database if sign-up fails with:
--   Error creating account: Field 'id' doesn't have a default value

ALTER TABLE users
  MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (id);

-- If a primary key already exists on id, MySQL will ignore/complain about the ADD PRIMARY KEY;
-- you can safely run only the MODIFY if needed:
-- ALTER TABLE users MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT;