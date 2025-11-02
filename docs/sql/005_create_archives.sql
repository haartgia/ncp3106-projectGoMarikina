-- Add archive tables for soft-deletes/moving deleted records to archive
-- Run in your MySQL (XAMPP) targeting the same database used by the app.

CREATE TABLE IF NOT EXISTS reports_archive (
  id INT UNSIGNED PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255) NOT NULL,
  image_path VARCHAR(255) NULL,
  status ENUM('unresolved','in_progress','solved') NOT NULL DEFAULT 'unresolved',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  archived_by INT NULL,
  INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements_archive (
  id INT UNSIGNED PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  image_path VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  archived_by INT NULL,
  INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users_archive (
  id INT UNSIGNED PRIMARY KEY,
  first_name VARCHAR(50),
  last_name VARCHAR(50),
  email VARCHAR(100),
  password VARCHAR(255),
  mobile VARCHAR(20),
  created_at DATETIME NULL,
  archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  archived_by INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notes:
-- 1) These archive tables intentionally keep the original primary key value so it's easy
--    to trace archived rows back to the original id.
-- 2) After running this migration, server-side delete handlers should INSERT into the
--    corresponding _archive table and then remove the original row from the primary table.
-- 3) Image files are NOT deleted when archiving; that behavior is preserved so archived
--    records keep their references intact.
