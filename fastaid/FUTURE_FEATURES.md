# Future Features

## SMS Alerts
- Integrate Twilio or Vonage API to send SMS when a volunteer is matched or arrives.
- Add `phone_verified` flag to users table; require OTP verification.
- Configurable per-user: opt in/out of SMS notifications.

## Real-Time Updates
- Replace 5-second polling with Server-Sent Events (SSE) or WebSocket.
- PHP SSE endpoint: `api.php?action=stream&request_id=r101` that holds connection open.
- Fallback to polling on hosts that don't support long connections.

## Multi-Language Support
- Add `lang` column to users table (default `en`).
- Create `includes/lang/en.php`, `es.php`, `fr.php`, `bn.php` with string arrays.
- Load translations based on session language; allow switching from navbar.
- Translate all UI strings, form labels, toast messages, and email templates.

## Push Notifications
- Service Worker + Web Push API for browser notifications.
- Notify volunteers of new cases even when app is in background.

## Volunteer Skill Tags
- Allow volunteers to tag their specialties (CPR, trauma, pediatrics).
- Match requests to volunteers with relevant skills first.

## Case History & Reports
- Admin can export CSV of all requests, ratings, debriefs.
- Patient can download their own case history as PDF.

## Two-Factor Authentication
- TOTP-based 2FA for admin accounts.
- Optional 2FA for volunteers handling sensitive patient data.

## Mobile App (PWA)
- Add `manifest.json` and service worker to make the site installable.
- Offline cache for static assets; queue requests when offline.
