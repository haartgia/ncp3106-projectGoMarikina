-- Robust fix for environments where `reports.id` is not a key yet.
-- Steps:
-- 1) Ensure `id` is NOT NULL and an integer
-- 2) Make `id` the PRIMARY KEY (drop an existing PK if it's on another column)
-- 3) Mark `id` as AUTO_INCREMENT

-- 1) Normalize the column type and nullability first
ALTER TABLE reports
  MODIFY COLUMN id INT UNSIGNED NOT NULL;

-- 2) Ensure `id` is the PRIMARY KEY. If a PK already exists on another column,
--    drop it first. If there is no existing PK, the DROP PRIMARY KEY will fail;
--    in that case, run the next ADD PRIMARY KEY statement only.
-- NOTE: If your MySQL client stops on error 1091 (can't drop primary), you can
--       instead run the statements one by one:
--       ALTER TABLE reports DROP PRIMARY KEY;
--       ALTER TABLE reports ADD PRIMARY KEY (id);
ALTER TABLE reports DROP PRIMARY KEY;
ALTER TABLE reports ADD PRIMARY KEY (id);

-- 3) Finally, set AUTO_INCREMENT on the `id` column
ALTER TABLE reports
  MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT;
