<?php require_once __DIR__ . '/admin_auth.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Seeker</title>
  <link rel="stylesheet" href="/BloodDonation/styles.css">
  <style>.form{max-width:720px;margin:1rem;padding:1rem;background:#fff;border-radius:8px}</style>
</head>
<body>
  <div class="admin-wrap">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
      <div style="padding:1rem;">
        <a href="seekers.php">←</a>
        <h2 id="title">Create Seeker</h2>
        <form id="seeker-form" class="form" autocomplete="off">
          <input type="hidden" name="id" id="id" />
          <div class="field-row">
            <label>First Name
              <input name="first_name" id="first_name" required placeholder="Given name" />
            </label>
            <label>Last Name
              <input name="last_name" id="last_name" placeholder="Family name" />
            </label>
          </div>
          <div class="field-row">
            <label>Street
            <input name="street" id="street" placeholder="Street or address" />
          </label>
            <label>Age<input name="age" id="age" type="number" min="0" max="120" /></label>
          </div>
          <div class="field-row">
            <label>Contact
              <input name="contact_number" id="contact_number" type="tel" placeholder="e.g. +94 7XXXXXXXX" />
            </label>
            <label>WhatsApp
              <input name="whatsapp_number" id="whatsapp_number" type="tel" placeholder="WhatsApp number (optional)" />
            </label>
          </div>
          <div class="field-row">
            <label>Email<input name="email" id="email" type="email" required /></label>
            <label>City<input name="city" id="city" /></label>
          </div>
          
          <label>Password (leave blank to keep current)
            <input name="password" id="password" type="password" placeholder="••••••" />
            <div class="help">Leave blank to keep existing password.</div>
          </label>
          <div class="actions" style="margin-top:.8rem;"><button class="submit-btn" id="save">Save Seeker</button></div>
        </form>
      </div>

        <script>
        async function load(id){
          if(!id) return;
          const res = await fetch('/BloodDonation/admin/admin_seeker_api.php?id='+encodeURIComponent(id));
          const b = await res.json();
          if(b.seeker){
            document.getElementById('title').textContent='Edit Seeker';
            const s = b.seeker;
            document.getElementById('id').value = s.id || '';
            document.getElementById('first_name').value = s.first_name || '';
            document.getElementById('last_name').value = s.last_name || '';
            document.getElementById('bloodgroup').value = s.bloodgroup || '';
            document.getElementById('age').value = s.age || '';
            document.getElementById('contact_number').value = s.contact_number || '';
            document.getElementById('whatsapp_number').value = s.whatsapp_number || s.whatsapp || '';
            document.getElementById('email').value = s.email || '';
            document.getElementById('city').value = s.city || '';
            document.getElementById('street').value = s.street || '';
          }
        }
        document.addEventListener('DOMContentLoaded', ()=>{ const p=new URLSearchParams(location.search); const id=p.get('id'); if(id) load(id); });
        document.getElementById('seeker-form').addEventListener('submit', async (e)=>{
          e.preventDefault();
          const fd = new FormData(e.target);
          const res = await fetch('/BloodDonation/admin/admin_seeker_api.php',{method:'POST',body:fd});
          const b = await res.json();
          if(b.success){ alert('Saved'); location.href='seekers.php'; } else alert(b.message||b.error||'Failed');
        });
        </script>
    </main>
  </div>
</body>
</html>
