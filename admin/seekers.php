<?php require_once __DIR__ . '/admin_auth.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Seekers</title>
  <link rel="stylesheet" href="/BloodDonation/styles.css">
  <style>
    .container{padding:1rem}
    table{width:100%;border-collapse:collapse}
    th,td{padding:.6rem;border-bottom:1px solid #eee}
    .actions button{margin-right:.4rem}
  </style>
</head>
<body>
  <div style="display:flex;gap:1rem;align-items:center;padding:1rem;">
    <a href="dashboard.php">← Back</a>
    <h2>Seekers</h2>
    <div style="margin-left:auto;"><button id="create-seeker" class="submit-btn">Create Seeker</button></div>
  </div>
  <div class="container">
    <table id="seekers-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>City</th><th>Contact</th><th>Actions</th></tr></thead><tbody></tbody></table>
  </div>

  <script>
  async function loadSeekers(){
    const res = await fetch('/BloodDonation/admin/admin_seeker_api.php?limit=100');
    const body = await res.json();
    const tbody = document.querySelector('#seekers-table tbody'); tbody.innerHTML='';
    (body.seekers||[]).forEach(s=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${s.id}</td><td>${s.first_name} ${s.last_name||''}</td><td>${s.email||''}</td><td>${s.city||''}</td><td>${s.contact_number||s.contact||''}</td><td class="actions"><button data-id="${s.id}" class="edit">Edit</button><button data-id="${s.id}" class="del">Delete</button></td>`;
      tbody.appendChild(tr);
    });
  }
  document.addEventListener('click', async (e)=>{
    if(e.target.matches('.del')){
      if(!confirm('Delete seeker?')) return; const id = e.target.dataset.id;
      await fetch('/BloodDonation/admin/admin_seeker_api.php', { method:'DELETE', body: 'id='+encodeURIComponent(id), headers:{'Content-Type':'application/x-www-form-urlencoded'} });
      loadSeekers();
    }
    if(e.target.matches('.edit')){
      const id=e.target.dataset.id; location.href='seekers_edit.php?id='+id;
    }
    if(e.target.id==='create-seeker'){ location.href='seekers_edit.php'; }
  });
  loadSeekers();
  </script>
</body>
</html>
