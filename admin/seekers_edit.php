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
  <div style="padding:1rem;">
    <a href="seekers.php">← Back</a>
    <h2 id="title">Create Seeker</h2>
    <form id="seeker-form" class="form">
      <input type="hidden" name="id" id="id" />
      <label>First Name<input name="first_name" id="first_name" /></label>
      <label>Last Name<input name="last_name" id="last_name" /></label>
      <label>Email<input name="email" id="email" type="email" /></label>
      <label>City<input name="city" id="city" /></label>
      <label>Contact<input name="contact_number" id="contact_number" /></label>
      <label>Password<input name="password" id="password" type="password" /></label>
      <div style="margin-top:.6rem;"><button class="submit-btn" id="save">Save</button></div>
    </form>
  </div>
  <script>
  async function load(id){ if(!id) return; const res=await fetch('/BloodDonation/admin/admin_seeker_api.php?id='+encodeURIComponent(id)); const b=await res.json(); if(b.seeker){ document.getElementById('title').textContent='Edit Seeker'; document.getElementById('id').value=b.seeker.id; document.getElementById('first_name').value=b.seeker.first_name||''; document.getElementById('last_name').value=b.seeker.last_name||''; document.getElementById('email').value=b.seeker.email||''; document.getElementById('city').value=b.seeker.city||''; document.getElementById('contact_number').value=b.seeker.contact_number||''; } }
  document.addEventListener('DOMContentLoaded', ()=>{ const params=new URLSearchParams(location.search); const id=params.get('id'); if(id){ load(id); } });
  document.getElementById('seeker-form').addEventListener('submit', async (e)=>{ e.preventDefault(); const fd=new FormData(e.target); const res=await fetch('/BloodDonation/admin/admin_seeker_api.php',{method:'POST',body:fd}); const b=await res.json(); if(b.success){ alert('Saved'); location.href='seekers.php'; } else alert(b.message||b.error||'Failed'); });
  </script>
</body>
</html>
