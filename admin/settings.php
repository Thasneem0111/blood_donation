<?php require_once __DIR__ . '/admin_auth.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Settings</title>
  <link rel="stylesheet" href="/BloodDonation/styles.css">
  <style>
    .settings-wrap{max-width:900px;margin:1rem auto;padding:1rem}
    .form .help{font-size:.88rem;color:#6b7280;margin-top:.25rem}
    .field-inline{display:flex;gap:1rem}
    .field-inline label{flex:1}
  </style>
</head>
<body>
  <div class="admin-wrap">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
      <div class="settings-wrap">
        <a href="dashboard.php">← Back</a>
        <h2>Settings</h2>
        <form id="settings-form" class="form">
          <div class="field-row">
            <label>Admin Full Name
              <input name="full_name" id="full_name" placeholder="e.g. Jane Doe" required />
            </label>
            <label>Username
              <input name="username" id="username" placeholder="admin" required />
            </label>
          </div>

          <div class="field-row">
            <label>Gmail
              <input name="gmail" id="gmail" type="email" placeholder="you@example.com" required />
            </label>
            <label>Contact Number
              <input name="contact_number" id="contact_number" type="tel" placeholder="e.g. +94 7XXXXXXXX" />
            </label>
          </div>

          <label>Password (leave blank to keep current)
            <input name="password" id="password" type="password" placeholder="••••••" />
            <div class="help">Leave blank to keep existing password. Password will be stored securely.</div>
          </label>

          <div class="actions" style="margin-top:.8rem;"><button class="submit-btn" type="submit">Save Settings</button></div>
        </form>
      </div>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', async ()=>{
      try{
        const res = await fetch('/BloodDonation/admin/admin_get_counts.php'); // harmless call to ensure auth
      }catch(e){}
      // try to prefill from server (if available)
      try{
        const r = await fetch('/BloodDonation/admin/admin_settings_get.php');
        const body = await r.json().catch(()=>null);
        if(body && body.success && body.settings){
          const s = body.settings;
          document.getElementById('full_name').value = s.full_name || '';
          document.getElementById('username').value = s.username || '';
          document.getElementById('gmail').value = s.gmail || '';
          document.getElementById('contact_number').value = s.contact_number || '';
        }
      }catch(e){ /* ignore */ }
    });

    document.getElementById('settings-form').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd = new FormData(e.target);
      const res = await fetch('/BloodDonation/admin/admin_settings_save.php',{method:'POST',body:fd});
      const body = await res.json();
      if(body.success){ alert('Settings saved'); } else alert(body.message||'Save failed');
    });
  </script>
</body>
</html>
<?php require_once __DIR__ . '/admin_auth.php'; ?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin — Settings</title>
<link rel="stylesheet" href="/BloodDonation/styles.css"></head>
<body>
  <div class="admin-wrap">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
      <div style="padding:1rem;">
        <a href="dashboard.php">← Back</a>
        <h2>Settings</h2>
        <form id="settings" style="max-width:720px;background:#fff;padding:1rem;border-radius:8px;">
          <label>Site Title<input name="site_title" value="BloodDonate" /></label>
          <label>Contact Email<input name="contact_email" value="admin@example.com" /></label>
          <div style="margin-top:.6rem;"><button class="submit-btn">Save</button></div>
        </form>
      </div>
      <script>
      document.getElementById('settings').addEventListener('submit', (e)=>{ e.preventDefault(); alert('Settings saved (client-side)'); });
      </script>
    </main>
  </div>
</body>
</html>
