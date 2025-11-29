<?php
require_once __DIR__ . '/admin_auth.php';
// server-side fetch of admin details so the form is pre-populated even if JS fails
require_once __DIR__ . '/../db.php';

$full_name = $username = $gmail = $contact_number = '';
$session_email = $_SESSION['email'] ?? null;
if($session_email){
  // try find admin table and fetch row
  $possible = ['admins','admin_users','admin'];
  $foundTable = null;
  foreach($possible as $t){
    $check = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($t) . "'");
    if($check && $check->num_rows > 0){ $foundTable = $t; break; }
  }
  if($foundTable){
    // detect email-like column
    $lookupCols = ['email','gmail','gmail_addr','gmail_address','username'];
    $foundLookup = null;
    foreach($lookupCols as $col){
      $c = $mysqli->query("SHOW COLUMNS FROM `".$mysqli->real_escape_string($foundTable)."` LIKE '".$mysqli->real_escape_string($col)."'");
      if($c && $c->num_rows>0){ $foundLookup = $col; break; }
    }
    if($foundLookup){
      $sql = "SELECT * FROM `".$mysqli->real_escape_string($foundTable)."` WHERE `".$mysqli->real_escape_string($foundLookup)."` = ? LIMIT 1";
      $stmt = $mysqli->prepare($sql);
      if($stmt){
        $stmt->bind_param('s',$session_email);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res && $res->num_rows>0){
          $r = $res->fetch_assoc();
          $full_name = $r['full_name'] ?? $r['fullname'] ?? $r['name'] ?? '';
          $username = $r['username'] ?? $r['user'] ?? '';
          $gmail = $r['gmail'] ?? $r['email'] ?? $r['gmail_addr'] ?? '';
          $contact_number = $r['contact_number'] ?? $r['contact'] ?? $r['phone'] ?? '';
        }
      }
    }
    // fallback: try id=1
    if(empty($full_name)){
      $stmt = $mysqli->prepare("SELECT * FROM `".$mysqli->real_escape_string($foundTable)."` WHERE id = 1 LIMIT 1");
      if($stmt){ $stmt->execute(); $res = $stmt->get_result(); if($res && $res->num_rows>0){ $r = $res->fetch_assoc(); $full_name = $r['full_name'] ?? $r['fullname'] ?? $r['name'] ?? ''; $username = $r['username'] ?? $r['user'] ?? ''; $gmail = $r['gmail'] ?? $r['email'] ?? ''; $contact_number = $r['contact_number'] ?? $r['contact'] ?? ''; } }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Settings</title>
  <link rel="stylesheet" href="/BloodDonation/styles.css">
  <!-- Bootstrap for alert styling -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <div id="settings-alert-placeholder"></div>
        <a href="dashboard.php">← Back</a>
        <h2>Settings</h2>
        <form id="settings-form" class="form">
          <div class="field-row">
            <label>Admin Full Name
              <input name="full_name" id="full_name" placeholder="e.g. Jane Doe" required value="<?php echo htmlspecialchars($full_name); ?>" />
            </label>
            <label>Username
              <input name="username" id="username" placeholder="admin" required value="<?php echo htmlspecialchars($username); ?>" />
            </label>
          </div>

          <div class="field-row">
            <label>Gmail
              <input name="gmail" id="gmail" type="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($gmail); ?>" />
            </label>
            <label>Contact Number
              <input name="contact_number" id="contact_number" type="tel" placeholder="e.g. +94 7XXXXXXXX" value="<?php echo htmlspecialchars($contact_number); ?>" />
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
      try{
        const res = await fetch('/BloodDonation/admin/admin_settings_save.php',{method:'POST',body:fd});
        const txt = await res.text();
        let body = null;
        try{ body = JSON.parse(txt); } catch(err){
          // show raw response for easier debugging
          alert('Save failed: server returned non-JSON response. See console for details.');
          console.error('Save response (raw):', txt);
          return;
        }
        if(body && body.success){
          // show bootstrap alert
          const ph = document.getElementById('settings-alert-placeholder');
          ph.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">Settings saved successfully.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
          // auto-dismiss after 3 seconds
          setTimeout(()=>{ const a = ph.querySelector('.alert'); if(a){ a.classList.remove('show'); a.classList.add('hide'); ph.innerHTML=''; } }, 3000);
        } else {
          const msg = body && body.message ? body.message : 'Save failed';
          const ph = document.getElementById('settings-alert-placeholder');
          ph.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
        }
      }catch(err){
        console.error(err); alert('Network or server error while saving settings');
      }
    });
  </script>
  <!-- Bootstrap JS for dismissible alerts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

