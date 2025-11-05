# Backend Integration Guide

This project currently ships with front-end stubs and session-based demo data so the UI can be explored without a server. Use this document as the hand-off for the backend team so they can wire real services into the existing screens quickly.

## Quick summary

| Area | Front-end file(s) | What the backend needs to provide |
|------|-------------------|------------------------------------|
| Authentication | `includes/login_card.php`, `login.php`, `logout.php`, `config/auth.php` | Session/token-based login, logout, and current-user lookup |
| Report creation | `create-report.php`, `assets/js/script.js` (`initializeCreateReport`) | Endpoint to accept a new report with optional photo upload |
| Report feed (dashboard/profile) | `index.php`, `dashboard.php`, `profile.php` | Endpoint(s) to list reports for the city and for a single user |
| Admin report management | `admin.php` | Secure endpoints to update status, delete reports |
| Announcements | `admin.php`, `announcements.php`, `index.php` | CRUD endpoints for announcements, optional image upload |
| Profile data | `profile.php` | Endpoint to fetch/update logged-in user details |

All interactive components already expose unique IDs / `data-*` attributes to make DOM targeting simple.

## Authentication contract

### Implemented endpoints (local PHP)

These endpoints are available in this repo for local usage under XAMPP:

| Purpose | Method & URL | Payload / Params | Response |
|---------|--------------|------------------|----------|
| Submit new report | `POST /api/reports_create.php` | `multipart/form-data` with fields `photo` (optional), `category`, `title`, `description`, `location` | `200 OK` JSON `{ success, message, report }`; `422` on validation errors |
| List reports | `GET /api/reports_list.php?status=unresolved&category=public_safety&mine=true` | Query params optional; `mine=true` requires session login | `200 OK` JSON `{ success, data: [...] }` |

Notes:
- The create endpoint also appends the created report into `$_SESSION['reports']` so existing pages render it immediately while the DB-backed listing is being adopted.
- Uploads are stored under `uploads/reports/` with randomized filenames; max size 5MB; accepts JPG/PNG/WEBP.

### Suggested endpoints (for production backends)

| Purpose | Method & URL | Expected request | Response (happy path) |
|---------|--------------|------------------|------------------------|
| Login | `POST /api/auth/login` | JSON `{ "email": "user@example.com", "password": "secret" }` | `200 OK` with JSON `{ "user": { "id": "uuid", "name": "Miguel", "email": "user@example.com", "role": "user" }, "token": "jwt-or-session-id" }` |
| Logout | `POST /api/auth/logout` | Header-only (bearer token / session cookie) | `204 No Content` |
| Current user | `GET /api/auth/me` | Header-only | `200 OK` with the same `user` object |

### Front-end hook points

* Login form posts to `login.php`. The backend team can either:
  * Replace `login.php` with a controller that forwards the POST body to the real API and stores the returned session/token, or
  * Convert the form to submit directly to `/api/auth/login` (add JS fetch in `assets/js/script.js`).
* The sidebar renders the user's name/email via `current_user()` helper. Swap the helper implementation to read from the backend-auth source.

## Reports domain

### Data model (suggested)

```json
{
  "id": "uuid",
  "title": "Flooding on Bulelak Street",
  "category": "public_safety",
  "description": "Standing water after heavy rain",
  "location": "Bulelak Street, Marikina",
  "status": "unresolved", // unresolved | in_progress | solved
  "reporter": {
    "id": "uuid",
    "name": "Miguel De Guzman",
    "email": "miguelivan@gmail.com"
  },
  "submitted_at": "2025-10-05T03:21:00Z",
  "photo_url": "https://.../flooding.jpg"
}
```

### Suggested endpoints

| Purpose | Method & URL | Payload / Params | Response |
|---------|--------------|------------------|----------|
| Submit new report | `POST /api/reports` | `multipart/form-data` containing `photo`, `category`, `title`, `description`, `location` | `201 Created` with created report |
| List all reports (public feed) | `GET /api/reports?status=unresolved&category=public_safety` (filters optional) | Query params | `200 OK` with `[{...}]` |
| List current user's reports | `GET /api/users/{userId}/reports` or `GET /api/reports?mine=true` | Auth required | `200 OK` |
| Update report status (admin) | `PATCH /api/reports/{reportId}` | JSON `{ "status": "solved" }` | `200 OK` |
| Delete report (admin) | `DELETE /api/reports/{reportId}` | Auth required | `204 No Content` |

### Front-end hook points

* `create-report.php` renders `<form id="createReportForm" ...>`.
   * `assets/js/script.js` performs client-side validation and now posts via `fetch('api/reports_create.php', { method: 'POST', body: formData })`. The form uses `enctype="multipart/form-data"` and consistent field names.
  * Uploaded photo preview uses the native `<input type="file" name="photo">`, so the backend just needs to accept the `photo` field.
* Dashboard/Profile templates expect an array named `$reports`. Replace the session mock data initialization inside `config/auth.php` with API fetches (or render-time includes).
* Admin table has a `<select class="admin-select">`. Each form already posts with hidden `report_id` and desired `status`, so these map 1:1 to the `PATCH` endpoint.

## Announcements domain

### Data model (suggested)

```json
{
  "id": "uuid",
  "title": "Scheduled Road Repairs",
  "body": "Road works on Bayan-Bayanan Ave start Monday...",
   "created_at": "2025-10-03T10:00:00Z",
   "author_id": "uuid",
   "image_url": "https://cdn.example.com/announcements/road-repairs.jpg"
}
```

### Suggested endpoints

| Purpose | Method & URL | Payload | Response |
|---------|--------------|---------|----------|
| Create | `POST /api/announcements` | `multipart/form-data` (`title`, `body`, optional `image`) | `201 Created` |
| List | `GET /api/announcements` | - | `200 OK` with array |
| Delete (optional) | `DELETE /api/announcements/{id}` | - | `204 No Content` |

### Front-end hook points

* Admin announcement form posts via `<form class="admin-form">` with fields `announcement_title` and `announcement_body`. A backend handler can read these names directly.
* Optional featured image gets uploaded through the `announcement_image` field. Store the file and return the public URL as `image_url` so the UI can render it in both admin and public views.
* Public surfaces (`index.php`, `dashboard.php`) print announcements from `$_SESSION['announcements']`. Replace the session array with real data, including `image_url` when present.

## Profile data

* `profile.php` currently uses hard-coded details (`Miguel De Guzman`). Swap these for values fetched from `/api/auth/me` or `/api/users/{id}`.
* The edit-password / edit-mobile buttons only show alerts. Replace the success branches with `fetch` calls to endpoints like `PATCH /api/users/{id}`.

## File upload handling

* Photos are collected through `#photoInput` and the cropping tool revokes/creates a `File` object. The final `FormData` will include the field `photo` of type `image/jpeg`.
* The backend should return the stored URL; once available, add a JS handler to display server-side validation feedback or updated preview.

## Implementation checklist for backend engineers

1. **Auth**
   - [ ] Implement login/logout/current-user endpoints.
   - [ ] Provide a way to persist the authenticated user (session cookie or JWT stored in `localStorage`).
   - [ ] Update PHP helpers in `config/auth.php` to consume the real auth source instead of session mocks.
2. **Reports**
   - [ ] Create report CRUD endpoints (see table above).
   - [ ] Wire `create-report.php` submit handler to call the `POST /api/reports` endpoint.
   - [ ] Populate dashboards (`$_SESSION['reports']`) from `GET /api/reports` calls.
3. **Admin tooling**
   - [ ] Gate admin routes via role-based auth.
   - [ ] Connect status updates and deletions to the respective `PATCH`/`DELETE` endpoints.
4. **Announcements**
   - [ ] Persist announcements and expose list/create endpoints.
   - [ ] Replace session-backed arrays in templates with API responses.
5. **Profile**
   - [ ] Surface real user data and save mutations through dedicated endpoints.
6. **Configuration**
   - [ ] Centralize the API base URL (e.g., define `window.APP_CONFIG = { apiBase: 'https://api.example.com' };` and have scripts read from it).

## Notes for future polish

* Consider replacing inline PHP session mocks with a lightweight API client class, so backend logic lives in one place.
* For SPAs, progressively move form handling from PHP redirects to JS `fetch` calls that update the DOM without reloads.
* Add error-state UI (e.g., inline validation messages) once backend validation requirements are known.

This guide should give the backend team enough context to expose real endpoints while reusing the existing structure. Feel free to extend it with database schema diagrams or sequence charts as the project evolves.
