-- Fix reports.id to be AUTO_INCREMENT primary key
-- Run this in your MySQL database once.
-- This resolves errors like: "Field 'id' doesn't have a default value" when inserting into reports.

ALTER TABLE reports
  MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT;
