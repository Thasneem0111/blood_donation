<?php require_once __DIR__ . '/admin_auth.php'; ?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin — Settings</title>
<link rel="stylesheet" href="/BloodDonation/styles.css"></head>
<body style="padding:1rem;">
  <a href="dashboard.php">← Back</a>
  <h2>Settings</h2>
  <form id="settings" style="max-width:720px;background:#fff;padding:1rem;border-radius:8px;">
    <label>Site Title<input name="site_title" value="BloodDonate" /></label>
    <label>Contact Email<input name="contact_email" value="admin@example.com" /></label>
    <div style="margin-top:.6rem;"><button class="submit-btn">Save</button></div>
  </form>
  <script>
  document.getElementById('settings').addEventListener('submit', (e)=>{ e.preventDefault(); alert('Settings saved (client-side)'); });
  </script>
</body>
</html>
