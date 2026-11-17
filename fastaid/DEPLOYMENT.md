# Complete Deployment Guide — FastAid on Free Shared PHP Hosting

This guide walks you through deploying FastAid to any free cPanel or standard PHP/MySQL hosting provider (e.g., InfinityFree, 000webhost, AwardSpace).

---

## Step 1: Upload Files
1. Log into your hosting Control Panel / File Manager.
2. Navigate to your root directory (`htdocs/` or `public_html/`).
3. Upload the contents of the `fastaid/` folder:
   - Make sure `public/` files are placed directly where the web server serves web requests (or configure document root to `public/`).
   - Move the `includes/`, `sql/`, and `logs/` folders one level **above** the public web root for maximum security.

---

## Step 2: Database Setup
1. In cPanel/vPanel, go to **MySQL Databases**.
2. Create a new database named `fastaid` (or your host prefix, e.g., `epiz_12345_fastaid`).
3. Create a MySQL user and assign a strong password. Add the user to the database with **ALL PRIVILEGES**.
4. Open **phpMyAdmin**.
5. Select your newly created database.
6. Go to the **Import** tab:
   - First, upload and import `sql/schema.sql`.
   - Next, upload and import `sql/seed.sql` to populate default users, roles, and test records.

---

## Step 3: Update Configuration
1. Open `includes/config.php` in the File Manager editor.
2. Update the credentials with your hosting details:
   ```php
   define('DB_HOST', 'sqlxxx.yourhost.com'); // often localhost or custom host provided by cPanel
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```
3. Save changes.

---

## Step 4: Folder Permissions & Security
1. Ensure the `logs/` directory exists and has write permissions (`chmod 755` or `775`).
2. Verify that `includes/.htaccess` is present containing `Deny from all` so private configuration files cannot be downloaded via the browser.

---

## Step 5: Test & Verification
1. Visit `https://your-domain.com/index.html` in your browser.
2. Check `api.php` by visiting `https://your-domain.com/api.php?action=admin_stats` (should return JSON error `Admin required`).
3. Log in with test accounts:
   - **Admin:** `admin@fastaid.local` / `password`
   - **Volunteer:** `james@test.com` / `password`
   - **Patient:** `sarah@test.com` / `password`
4. Follow the test checklist in `TEST_CHECKLIST.md` to verify the full flow.
