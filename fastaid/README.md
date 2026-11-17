# FastAid — Emergency Medical Assistance

## Quick Start (Local)
1. Import `sql/schema.sql` into MySQL (database `fastaid`).
2. Import `sql/seed.sql` (test data included).
3. Copy `includes/config.php` and point DB_USER/DB_PASS to your local MySQL.
4. Point web root to `public/`.
5. Open `http://localhost/fastaid/public/index.html`.

## Test Logins
- Admin: `admin@fastaid.local` / `password`
- Volunteer verified: `james@test.com` / `password`
- Patient: `sarah@test.com` / `password`

## Tech
- PHP 8, PDO MySQL, vanilla JS frontend
- All API endpoints at `public/api.php?action=NAME`
- Mock mode disabled (`USE_MOCK = false` in `assets/js/api.js`)
