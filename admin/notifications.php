<?php require_once __DIR__ . '/admin_auth.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Notifications</title>
  <link rel="stylesheet" href="/BloodDonation/styles.css">
  <style>.container{padding:1rem}.notif{padding:.6rem;border-bottom:1px solid #eee}</style>
</head>
<body>
  <div class="admin-wrap">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
      <div style="padding:1rem;display:flex;align-items:center;gap:1rem;"><a href="dashboard.php">← Back</a><h2>Notifications</h2><div style="margin-left:auto;"></div></div>
      <div class="container"><div id="notifs"></div></div>
      <script>
      async function load(){
        const res = await fetch('/BloodDonation/admin/admin_notifications_api.php');
        const b = await res.json();
        const el = document.getElementById('notifs'); el.innerHTML='';
        (b.notifications||[]).forEach(n=>{
          const d = document.createElement('div'); d.className='notif';
          const who = n.full_name || n.title || '';
          const em = n.email ? `<div><strong>Email:</strong> ${escapeHtml(n.email)}</div>` : '';
          const phone = n.contact_number ? `<div><strong>Contact:</strong> ${escapeHtml(n.contact_number)}</div>` : '';
          const msg = n.message ? `<div>${escapeHtml(n.message)}</div>` : '';
          const created = n.created_at ? `<div style="color:#666;font-size:.85rem">${escapeHtml(n.created_at)}</div>` : '';
          d.innerHTML = `<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">` +
                        `<div style="flex:1;">` +
                        `<strong>${escapeHtml(who)}</strong> ${em} ${phone} ${msg} ${created}` +
                        `</div>` +
                        `<div style="margin-left:12px;flex:0 0 auto;display:flex;flex-direction:column;gap:.5rem;align-items:flex-end;">` +
                        `<button class="del-notif" data-id="${n.id}" style="background:#ef4444;border:0;color:#fff;padding:.45rem .6rem;border-radius:6px;cursor:pointer">Delete</button>` +
                        `</div></div>`;
          el.appendChild(d);
        });
      }
      document.addEventListener('click', async (e)=>{
        const btn = e.target.closest('.del-notif');
        if(!btn) return;
        if(!confirm('Delete this notification?')) return;
        const id = btn.dataset.id;
        try{
          await fetch('/BloodDonation/admin/admin_notifications_api.php', {method:'DELETE', body: 'id=' + encodeURIComponent(id), headers: {'Content-Type':'application/x-www-form-urlencoded'}});
          load();
        }catch(err){ console.error(err); alert('Failed to delete'); }
      });
      document.getElementById('create-notif')?.addEventListener('click', async ()=>{
        const title = prompt('Title'); if(!title) return; const message = prompt('Message')||'';
        await fetch('/BloodDonation/admin/admin_notifications_api.php',{method:'POST',body:new URLSearchParams({title,message})});
        load();
      });
      function escapeHtml(s){ if(!s) return ''; return s.replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[c]); }
      load();
      </script>
    </main>
  </div>
</body>
</html>
