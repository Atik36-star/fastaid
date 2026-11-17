# FastAid Testing Checklist

Complete manual and API test flow to verify end-to-end functionality.

## 1. Setup & Migration
- [ ] MySQL database created (`fastaid`).
- [ ] Schema imported from `sql/schema.sql`.
- [ ] Seed data imported from `sql/seed.sql`.
- [ ] Verify `includes/config.php` credentials connect to DB without errors.

## 2. Authentication Flow
- [ ] **Register (Patient):** Register a new user with role `patient`. Verify user record created with `password_hash`.
- [ ] **Register (Volunteer):** Register with certification info. Verify `volunteers` table entry with `pending` status.
- [ ] **Login:** Check bcrypt verification works for test users (e.g. `sarah@test.com` / `password`).
- [ ] **Session & CSRF:** Verify `$_SESSION['csrf']` generated and returned in headers/meta.
- [ ] **Auth Guard:** Try accessing `patient-dashboard.html` directly when logged out; verify redirect to `login.html`.

## 3. Emergency Request & Matching Flow
- [ ] **Create Request:** From `request.html`, select emergency type and send request.
- [ ] **Haversine Proximity:** Verify SQL finds approved volunteers within 10 km (ignoring those in DND).
- [ ] **Notifications:** Check `notifications` table gets populated for matching volunteers.

## 4. Volunteer Workflow
- [ ] **View Incoming:** Log in as `james@test.com`, check `volunteer-dashboard.html` for pending requests.
- [ ] **Accept Case:** Click "Accept Request". Verify race-condition protection works (transaction locks row).
- [ ] **Tracking & Status:** Open `focus.html`, click "Mark Arrived", then "Complete Case". Verify status progression order `requested` -> `accepted` -> `on_the_way` -> `arrived` -> `completed`.
- [ ] **Debrief:** Fill out 3-question debrief modal after completing case. Verify written to `debriefs` table.

## 5. Patient Rating Flow
- [ ] Open `tracking.html` after case is marked completed.
- [ ] Submit 1-5 star rating and comment. Verify saved to `ratings` table.

## 6. Admin Panel
- [ ] Log in as `admin@fastaid.local` / `password`.
- [ ] Access `admin.html`. Verify non-admin gets redirected.
- [ ] View pending volunteers, click "Verify" -> updates status to `approved`.
- [ ] View live system statistics (active cases, registered volunteers, etc.).

## 7. Security Checks
- [ ] Verify direct access to `/includes/config.php` returns 403 Forbidden (`.htaccess`).
- [ ] Verify POST requests without valid CSRF return `{ "success": false, "error": "CSRF invalid" }`.
- [ ] Ensure all input is sanitized/escaped before database storage or JSON output.
