# Testing Instructions — BloodDonation (local)

This file documents manual test steps to verify the key flows you asked for: registration/login, auto-profile load, donor-search, cross-page anchors, and the admin interface.

Prerequisites

- XAMPP installed and Apache + MySQL running.
- Project served from `http://localhost/BloodDonation/`.

Quick power user checks (PowerShell)

- Check DB & table availability (uses our local helper):

```powershell
Invoke-RestMethod 'http://localhost/BloodDonation/test_db.php' -Method Get | ConvertTo-Json -Depth 5
```

- Check donors endpoint JSON:

```powershell
Invoke-RestMethod 'http://localhost/BloodDonation/get_donors.php' -Method Get | ConvertTo-Json -Depth 5
```

- Check seeker session endpoint (when logged in as seeker):

```powershell
Invoke-RestMethod 'http://localhost/BloodDonation/get_seeker.php' -Method Get | ConvertTo-Json -Depth 5
```

Manual browser test steps

1. Start server

- Open XAMPP Control Panel and start `Apache` and `MySQL`.
- Open `http://localhost/BloodDonation/` in your browser.

2. Register a seeker (web UI)

- Open the registration form (site signup).
- Complete required fields and choose `Seeker` as user-type.
- Submit and watch the Network tab for `register.php` POST.
  - Expected JSON: `{"success":true,...}` and a `redirect` property.
- After redirect, localStorage should contain `userEmail` and `showProfileOnNextLoad` (used for auto-open). You can check in DevTools → Application → Local Storage.

3. Auto-open profile modal (session-first)

- After registration + redirect, the profile modal should auto-open.
- If it doesn't auto-open, click the profile icon (top-right) to open manually.
- Profile modal behavior:
  - First attempt: `get_seeker.php` is called without `?email=`; server uses the PHP session to return the seeker.
  - Fallback: if no session, the modal allows entering email and will call `get_seeker.php?email=you@example.com`.
- Edit a field (e.g., city) and click `Save`.
  - Network: `update_seeker.php` POST → Expected JSON `{"success":true}`.
- Verify change persisted by reloading and re-opening the profile or checking DB via phpMyAdmin.

4. Donor search page

- Open `http://localhost/BloodDonation/components/bloodseeker/blooddonor.html`.
- Network: confirm there is a GET request to `/get_donors.php` and it returns an array of donors.
- Each donor card should show:
  - Name, blood group, city
  - Clickable Contact link (`tel:`) — tapping it should prompt the OS to call or attempt to call if supported.
  - Clickable WhatsApp link (`https://wa.me/<digits>`) — opens WhatsApp Web or app.
- If contact/whatsapp values are blank, the UI will show `—` placeholder.

5. Cross-page anchors

- From donor component pages, the navbar Home/About/Contact links were changed to anchor-style links (or absolute links to the bloodseeker component) to ensure they return to the correct anchors on the seeker page.
- Test by clicking `Home`, `About Us`, `Contact Us` from a donor page. Each should land on the appropriate anchor in `components/bloodseeker/bloodseeker.html`.

6. Admin: login, dashboard, CRUD

- Visit `http://localhost/BloodDonation/admin/login.php`.
  - If no admin exists, the first successful sign-in will create the admin user with the password you supplied.
- After login, you'll be redirected to `/admin/dashboard.php`.
  - Confirm counts load via `admin_get_counts.php` (Network tab).
  - Confirm recent items load via `admin_get_recent.php`.
- Seekers page:
  - Open `admin/seekers.php`. It should fetch `/admin/admin_seeker_api.php` for the list.
  - Create: click `Create Seeker` → fill form → Save → expected POST to `/admin/admin_seeker_api.php` and success JSON.
  - Edit: click `Edit` → change fields → Save → expected POST update.
  - Delete: click `Delete` → confirm → expected DELETE call to `/admin/admin_seeker_api.php`.
- Donors page: same as Seekers but calls `/admin/admin_donor_api.php`.
- Notifications: create via the `Create` button (prompts), verify creation via `admin_notifications_api.php`.

7. Expected Error Handling / Debugging

- If a fetch fails with `JSON.parse` errors, open the failing request's Response in DevTools; if it's HTML, it's likely a PHP error page.
  - Check Apache/PHP error logs or enable `test_db.php` to inspect DB connectivity.
- If `test_db.php` returns `connected:false` or `table_exists:false`, ensure your DB name/credentials in `db.php` are correct and MySQL is running.
- If admin pages redirect to login unexpectedly, ensure `$_SESSION['is_admin']` is set by `admin/login.php` (successful login) and cookies are enabled.

Endpoints reference (examples)

- `POST /register.php` → returns JSON { success, redirect }
- `POST /login.php` → returns JSON { success, redirect }
- `GET /get_seeker.php` (session-based) or `GET /get_seeker.php?email=...` → { success, seeker }
- `POST /update_seeker.php` → { success }
- `GET /get_donors.php` → JSON array of donor objects
- Admin APIs (require admin session):
  - `GET /admin/admin_get_counts.php` → { total_users, total_donors, ... }
  - `GET /admin/admin_get_recent.php` → { notifications, donors, seekers }
  - CRUD: `/admin/admin_seeker_api.php`, `/admin/admin_donor_api.php`, `/admin/admin_notifications_api.php`

Troubleshooting checklist

- If responses are HTML (not JSON): check Apache/PHP error log; look for syntax errors in the modified PHP files.
- If sessions don't persist: check PHP session save path and ensure `session_start()` calls exist at the top of `login.php`, `register.php`, and `get_seeker.php`.
- If email duplication causes unexpected behavior: verify `register.php` checks both `blood_donor` and `bloodseeker` tables before inserting.

If you want, I can:

- Run through these tests now and fix any issues found.
- Add simple CSRF tokens to admin POST/DELETE flows.
- Add a small test harness (node script or PowerShell script) to automate the basic endpoint checks.

---

Created on: 2025-11-26
