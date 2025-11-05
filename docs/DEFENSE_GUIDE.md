# GO! MARIKINA — Defense Guide

Use this as a quick, confident walkthrough of how the whole system works: what it does, how it’s built, how data flows, and how to debug issues live during your defense.

## 1) Purpose and Scope
- Mission: A city platform for reporting incidents (citizen reports), publishing city announcements, and visualizing environmental telemetry (ESP32 sensors per barangay and river).
- Users:
  - Residents: Submit reports and view public info.
  - Admins: Moderate reports, publish announcements, monitor telemetry.
- Outcomes:
  - Faster reporting and triage of local issues.
  - Timely, centralized advisories for residents.
  - City metrics and sensor readings to guide responses.

## 2) High-level Architecture
- Stack: PHP 8 (XAMPP) + MySQL/MariaDB, vanilla JS and CSS, Leaflet for maps.
- Structure:
  - Pages (PHP): Render UI, include session/auth, fetch from DB.
  - APIs (PHP under `api/`): JSON endpoints used by the UI.
  - Includes:
    - `includes/bootstrap.php`: Start session/auth, DB connection, helpers.
    - `includes/api_bootstrap.php`: JSON header, auth + DB, `json_response/json_error` helpers.
  - Database: tables for users, reports, announcements, notifications, sensor_data, plus archives and password_resets. See `docs/sql/*.sql` and `docs/SENSOR_DATABASE_SETUP.md`.
  - Device integration: ESP32 hits `/api/save_sensor_data.php`; UI fetches `/api/get_sensor_data.php` and `/api/get_sensor_history.php`.
- Flow (example – Report):
  1) User submits report form (multipart). 2) `api/reports_create.php` validates + inserts. 3) New report appears on public feed; optional notification is stored.

## 3) Key Pages (what to say for each)
- `index.php` (Public landing)
  - Shows latest announcements and a paginated grid of reports (9/page), with smooth transitions.
  - Uses helper functions for formatting and category/status labels.
- `dashboard.php` (Metrics)
  - City metrics cards (temp/humidity/air/water), sparkline mini-charts, and a modal for detailed graphs.
  - Grids resilient to CSS load timing, asset versioning to avoid stale caches.
- `announcements.php` (Admin)
  - Compose and publish announcements (with optional image), view paginated list, and delete.
- `admin.php` (Admin reports)
  - Table with filters/sorts; update report status inline (unresolved/in_progress/solved).
- `archives.php`
  - Access archived reports/announcements (paged), view item modals.
- Other: `create-report.php`, `profile.php`, `login.php`, `register.php`, `forgot-password.php`, `reset-password.php`.

## 4) Database Schema (essentials)
- `users`: id, email, password hash, role, mobile, timestamps.
- `reports`: id, user_id (nullable), title, category, description, location, image_path (nullable), status, created_at/updated_at, latitude/longitude (optional columns added later).
- `notifications`: id, user_id, title, meta, type, is_read, created/updated.
- `announcements`: id, title, body, image_path, created/updated.
- `sensor_data`: id, barangay, device_ip, temp/humidity, water_percent, flood_level, air_quality, gas_analog, gas_voltage, status, source, reading_timestamp, created_at.
- `archives_*`: store archived items (reports/announcements).
- SQL files: see `docs/sql/*.sql`; sensor setup in `docs/SENSOR_DATABASE_SETUP.md`.

Indexing recommendations (defense talking point):
- Add indexes on frequently filtered/ordered columns: `reports(created_at)`, `reports(latitude)`, `reports(longitude)`, `notifications(user_id, is_read)`, `sensor_data(barangay, reading_timestamp)`.

## 5) API Catalog (how to describe each quickly)
Prefix: `/api/`
- `reports_create.php` (POST) — Create a report (optional photo, optional lat/lng). Returns created object.
- `reports_list.php` (GET) — List reports with filters: `status`, `category`, `mine`.
- `reports_update_status.php` (POST, admin) — Update status + notify owner.
- `get_reports.php` (GET) — Map feed; supports bounding box `minLat/maxLat/minLng/maxLng` or `south/north/west/east`.
- `announcements_list.php` (GET) — Announcements (DB-first; falls back to session).
- `notifications_list.php` (GET, auth) — Notifications for user; returns `unreadCount`.
- `notifications_delete.php` (POST, auth) — Delete one.
- `notifications_mark_read.php` (POST, auth) — Mark all as read.
- `save_sensor_data.php` (POST) — ESP32 pushes readings; CORS enabled; stores + trims history.
- `get_sensor_data.php` (GET) — One-shot data for a barangay; real device for Malanday, dummy for others; may persist intermittently.
- `get_sensor_history.php` (GET) — Historical data; supports `latest`, `limit`, `barangay`, `from`, `to`.
- `geocode_proxy.php` (GET) — Server-side Nominatim proxy with caching.
- `weather_today.php` (GET) — Open‑Meteo snapshot for Marikina (optional feature).

Each endpoint now starts with a PHPDoc header describing purpose, params, and response.

## 6) Frontend: JS and CSS responsibilities
- JS (`assets/js/script.js`):
  - Announcements modal + simple carousel inside modal.
  - Notifications: list and mark-read flows; small UI state handling.
  - Reports UI helpers (layout tweaks, animations); smooth pager transitions.
  - Admin inline actions to update report status.
  - Auth card toggles and small validations.
- CSS (`assets/css/style.css`):
  - Design tokens, utilities, layout grids, components (cards, pagers, modals), and page-specific styles.
  - Animations for report/announcement lists and pager transitions.

## 7) Data Flows to Demo (scripted)
1) Report submission
   - Go to Create Report, fill fields, optionally add photo, submit.
   - Check new card on `index.php` (page 1 if recent), open modal, see summary and status.
   - As admin (`admin.php`), change status to `in_progress` then `solved`; notification appears for the user.
2) Announcements
   - In `announcements.php`, publish a new announcement with image.
   - See it on `index.php` announcements and admin list; delete to show moderation.
3) Sensors
   - Call `save_sensor_data.php` with a JSON sample (Postman or small script) for a barangay.
   - Verify record saved via `get_sensor_history.php?latest=true` or UI metrics on dashboard.

## 8) Security and Safety
- Sessions and auth helpers in `config/auth.php`.
- Admin-only actions guarded in endpoints (`require_admin` logic on pages, `is_admin` in APIs).
- Query safety: prepared statements for inserts/updates; numeric casts on GET; hand-validated input.
- File uploads: MIME-checked and size-limited; stored under `uploads/`.
- CORS:
  - Open CORS only where needed (`save_sensor_data.php`) so ESP32 can POST.
- Common improvements to mention (if asked):
  - CSRF tokens on forms; rate limiting on APIs; stricter file validations; centralized request validation.

## 9) Performance
- Server-side pagination (reports/announcements) to keep pages fast.
- Asset versioning (filemtime) to avoid stale assets.
- Gzip for map feed; caching in geocoding proxy.
- Suggestion: DB indexes (see Schema), CDN caching for images.

## 10) Debugging Playbook (live)
- White screen / PHP error: check `php_error_log` (XAMPP) and last modified file.
- DB issues:
  - Verify connection in `config/db.php` and service running.
  - If `reports` missing `latitude/longitude`, endpoints fall back (designed). Run migrations in `docs/sql/`.
- Uploads failing: ensure `uploads/reports` and `uploads/announcements` writable.
- JSON/API errors:
  - Check network tab; confirm method and payload match endpoint. Read the JSON error message.
- Sensors:
  - If `get_sensor_data.php` returns unavailable, the device may be offline; test with dummy data via `save_sensor_data.php`.
- UI hiccups:
  - If pager buttons stop responding after DOM swap, ensure listeners reattach (we updated JS for this).
  - Hard refresh to defeat cached JS if versioning wasn’t applied on a page.

Quick MySQL checks (optional):
```sql
-- Latest reports
SELECT id, title, status, created_at FROM reports ORDER BY created_at DESC LIMIT 5;
-- Latest sensor sample per barangay
SELECT barangay, MAX(reading_timestamp) FROM sensor_data GROUP BY barangay;
```

## 11) Likely Defense Questions (with concise answers)
- Why PHP + vanilla JS?
  - Lightweight, low dependency footprint; easy to deploy on XAMPP; fits campus infra; fast to iterate.
- How do you prevent SQL injection?
  - Prepared statements (bind_param); numeric casts; whitelists for enums.
- File upload safety?
  - Verify MIME via `finfo`, size limit (5MB), whitelist extensions, store outside web root when possible.
- Why open CORS on sensor endpoint?
  - Only for device ingestion; other endpoints are same-origin. We can limit by IP or add token if required.
- What if DB columns are missing (migrations incomplete)?
  - Endpoints feature-detect (e.g., lat/lng) and gracefully fall back.
- How would you scale?
  - Add DB indexes; paginate everywhere; move images to object storage/CDN; cache API responses; split API and web servers.
- How to add CSRF protection?
  - Include token in session and form; verify on POST; rotate per session.
- Why server-side pagination instead of infinite scroll?
  - Predictable performance and accessibility; works without JS; small payloads.

## 12) Runbook (Windows/XAMPP)
- Start Apache + MySQL in XAMPP.
- Import schema:
  - Open phpMyAdmin → create the database configured in `config/db.php` (default is `user_db`) → run scripts in `docs/sql/*.sql` in order.
- Configure DB connection in `config/db.php` to match your local credentials.
- Visit: `http://localhost/ncp3106-projectGoMarikina/`.
- Default roles: register a user; set admin manually in DB if needed (`UPDATE users SET role='admin' WHERE id=1;`).

## 13) Appendix — Endpoint Contracts (quick reference)
- reports_create (POST): title, category, description, location, [location_lat/lng], [photo]
- reports_list (GET): status, category, mine
- reports_update_status (POST admin): report_id, status
- announcements_list (GET): limit
- notifications_list (GET auth): limit
- notifications_delete (POST auth): notification_id
- notifications_mark_read (POST auth)
- save_sensor_data (POST json): barangay, temperature, humidity, waterPercent, floodLevel, airQuality, gasAnalog, gasVoltage, status, source, timestamp
- get_sensor_data (GET): barangay, [ip, debug]
- get_sensor_history (GET): barangay, limit, latest, from, to
- get_reports (GET): minLat/maxLat/minLng/maxLng or south/north/west/east
- geocode_proxy (GET): action=search|reverse + q|lat/lon

---
Tip: When presenting, lean on the 3 flows (Report → Admin status/Notification, Announcement publish, Sensor ingest/view). Show how each crosses UI ↔ API ↔ DB cleanly.
