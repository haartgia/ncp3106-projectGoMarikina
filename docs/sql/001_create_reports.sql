-- Schema for reports feature
-- Run in your MySQL (XAMPP) targeting the `user_db` database.

CREATE TABLE IF NOT EXISTS reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255) NOT NULL,
  image_path VARCHAR(255) NULL,
  status ENUM('unresolved','in_progress','solved') NOT NULL DEFAULT 'unresolved',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_category (category),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: simple users foreign key if you already have users.id integer PK
-- ALTER TABLE reports ADD CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;