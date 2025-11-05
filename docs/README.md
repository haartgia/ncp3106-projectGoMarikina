# Go Marikina — Codebase Notes

For a complete walkthrough for your project defense (architecture, flows, debugging, Q&A), see `docs/DEFENSE_GUIDE.md`.

This project has been cleaned up to reduce duplication and simplify includes. Key changes:

- New shared bootstraps
  - `includes/bootstrap.php` — Start session/auth, open DB connection, and load helpers for web pages.
  - `includes/api_bootstrap.php` — Sets JSON header and includes auth + DB for API endpoints; includes simple `json_response()`/`json_error()` helpers.
- Pages and APIs now require these bootstraps instead of repeating `require` blocks.
- Developer-only utilities (not used in production):
  - `db_test.php` (DB health check)
  - `check_reports.php` (lat/lng diagnostics)
- Optional feature endpoint:
  - `api/weather_today.php` (Open‑Meteo proxy; can be disabled without affecting core app)
- Notes on includes:
  - `includes/header.php` currently exists but is empty; safe to ignore or remove if unused.

## How to use the bootstraps

- In pages (e.g., `index.php`):
  ```php
  require_once __DIR__ . '/includes/bootstrap.php';
  ```
- In APIs (e.g., `api/reports_list.php`):
  ```php
  require_once __DIR__ . '/../includes/api_bootstrap.php';
  ```

This ensures sessions, auth helpers, DB connection (`$conn`), and shared helpers are consistently available.

## Running locally

- PHP: Use XAMPP (PHP 8+ recommended) on Windows.
- Database: MariaDB/MySQL running with a `user_db` schema. See `docs/sql/` migrations and `docs/SENSOR_DATABASE_SETUP.md`.
- App root: `http://localhost/ncp3106-projectGoMarikina/`

After pulling recent changes, apply the latest SQL migration to enforce unique mobile numbers:

1. Clean up any duplicate `users.mobile` values (keep one per number).
2. Run the migration `docs/sql/008_add_unique_mobile.sql` in your DB.
  - If duplicates exist, MySQL will error with 1062 (duplicate key) — resolve and rerun.

## Notes

- `includes/footer.php` exists for future shared footer content; it is included in some pages.
- `api/geocode_proxy.php` is used by `assets/js/script.js` for geocoding/autocomplete.
- If you add new APIs, prefer `api_bootstrap.php` and return JSON via the helper functions.
