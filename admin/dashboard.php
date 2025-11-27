<?php require_once __DIR__ . '/admin_auth.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard — BloodDonate</title>
  <link rel="stylesheet" href="/BloodDonation/styles.css">
  <style>
    /* admin specific (same as HTML version) */
    .admin-wrap { display:flex; min-height:100vh; }
    .admin-sidebar { width:260px; background:#1a0b0d; color:#fff; padding:1rem; }
    .admin-sidebar .nav-item { display:flex; align-items:center; gap:.75rem; padding:.6rem .5rem; border-radius:8px; color:#fff; margin-bottom:.25rem; cursor:pointer; }
    .admin-sidebar .nav-item:hover, .admin-sidebar .nav-item.active { background:#2a0d14; }
    .admin-main { flex:1; padding:1.25rem; background:#fafafa; }
    .overview { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; }
    .search { flex:1; }
    .profile-icon { width:44px;height:44px;border-radius:50%;background:#fff;display:inline-flex;align-items:center;justify-content:center;border:1px solid #eee; }
    .metrics { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; margin-bottom:1rem; }
    .metric { background:#fff;padding:1rem;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06); }
    .lists { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; }
    .list-card { background:#fff;padding:1rem;border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
    .list-card h4 { margin-bottom:.6rem; }
    .small-list li { padding:.45rem 0; border-bottom:1px dashed #eee; }
    @media(max-width:900px){ .admin-sidebar{display:none} .lists{grid-template-columns:1fr} }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
      <div class="overview">
        <div class="search">
          <input id="admin-search" placeholder="Search users, email or city" style="width:100%;padding:.6rem;border-radius:8px;border:1px solid #ddd;" />
        </div>
        <div style="display:flex;gap:.6rem;align-items:center;">
          <div class="profile-icon" title="Admin">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#c40024" aria-hidden="true"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          </div>
        </div>
      </div>

      <div class="metrics">
        <div class="metric"><strong>Total Users</strong><div id="total-users" style="font-size:1.6rem;margin-top:.6rem;">—</div></div>
        <div class="metric"><strong>Blood Donors</strong><div id="total-donors" style="font-size:1.6rem;margin-top:.6rem;">—</div></div>
        <div class="metric"><strong>Blood Seekers</strong><div id="total-seekers" style="font-size:1.6rem;margin-top:.6rem;">—</div></div>
        <div class="metric"><strong>Notifications</strong><div id="total-notifs" style="font-size:1.6rem;margin-top:.6rem;">—</div></div>
      </div>

      <div class="lists">
        <div class="list-card">
          <h4>Recent Notifications</h4>
          <ol id="recent-notifs" class="small-list numbered"></ol>
        </div>
        <div class="list-card">
          <h4>Recent Donors</h4>
          <ol id="recent-donors" class="small-list numbered"></ol>
        </div>
        <div class="list-card">
          <h4>Recent Seekers</h4>
          <ol id="recent-seekers" class="small-list numbered"></ol>
        </div>
      </div>
    </main>
  </div>

  <script>
  async function loadCounts(){
    try{
      const res = await fetch('/BloodDonation/admin/admin_get_counts.php');
      const body = await res.json();
      if(body.success){
        document.getElementById('total-users').textContent = body.total_users;
        document.getElementById('total-donors').textContent = body.total_donors;
        document.getElementById('total-seekers').textContent = body.total_seekers;
        document.getElementById('total-notifs').textContent = body.total_notifications;
      }
    }catch(e){ console.error(e); }
  }

  async function loadRecent(){
    try{
      const res = await fetch('/BloodDonation/admin/admin_get_recent.php');
      const body = await res.json().catch(()=>null) || {};
      const notUl = document.getElementById('recent-notifs'); notUl.innerHTML='';
      const dUl = document.getElementById('recent-donors'); dUl.innerHTML='';
      const sUl = document.getElementById('recent-seekers'); sUl.innerHTML='';

      function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[c]); }
      function formatPhoneLinks(raw){
        if(!raw) return '';
        const digits = String(raw).replace(/\D/g,'');
        const tel = 'tel:' + (String(raw).trim().replace(/[^+\d]/g,''));
        const wa = digits ? 'https://wa.me/' + digits : null;
        const telLink = '<a href="'+tel+'">'+escapeHtml(raw)+'</a>';
        const waLink = wa ? ' <a target="_blank" rel="noopener" href="'+wa+'" title="WhatsApp">(WA)</a>' : '';
        return telLink + waLink;
      }

      const notifs = body.notifications || [];
      if(notifs.length === 0){
        const li = document.createElement('li'); li.textContent = 'No recent notifications'; notUl.appendChild(li);
      } else {
        notifs.forEach(n=>{
          const li=document.createElement('li');
          const who = n.full_name || n.title || '';
          const contact = n.contact_number || '';
          li.innerHTML = (who ? '<strong>' + escapeHtml(who) + '</strong> ' : '') + (contact ? '<span class="muted"> ' + formatPhoneLinks(contact) + '</span>' : '');
          notUl.appendChild(li);
        });
      }

      const donors = body.donors || [];
      if(donors.length === 0){ const li=document.createElement('li'); li.textContent = 'No recent donors'; dUl.appendChild(li); }
      else donors.forEach(d=>{
        const li=document.createElement('li');
        const name = (d.first_name||'') + (d.last_name ? ' ' + d.last_name : '');
        const contact = d.contact_number || d.whatsapp_number || '';
        const bgHtml = d.bloodgroup ? ' <span class="badge">' + escapeHtml(d.bloodgroup) + '</span>' : '';
        li.innerHTML = (name ? '<strong>' + escapeHtml(name) + '</strong> ' : '') + (contact ? '<span class="muted"> ' + formatPhoneLinks(contact) + '</span>' : '') + bgHtml;
        dUl.appendChild(li);
      });

      const seekers = body.seekers || [];
      if(seekers.length === 0){ const li=document.createElement('li'); li.textContent = 'No recent seekers'; sUl.appendChild(li); }
      else seekers.forEach(s=>{
        const li=document.createElement('li');
        const name = (s.first_name||'') + (s.last_name ? ' ' + s.last_name : '');
        const contact = s.contact_number || s.whatsapp_number || '';
        li.innerHTML = (name ? '<strong>' + escapeHtml(name) + '</strong> ' : '') + (contact ? '<span class="muted"> ' + formatPhoneLinks(contact) + '</span>' : '');
        sUl.appendChild(li);
      });

    }catch(e){ console.error(e); const notUl = document.getElementById('recent-notifs'); notUl.innerHTML='<li>Unable to load recents</li>'; }
  }

  document.addEventListener('DOMContentLoaded', ()=>{ loadCounts(); loadRecent(); });
  </script>
</body>
</html>
