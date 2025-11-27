<?php require_once __DIR__ . '/admin_auth.php'; ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Donor</title><link rel="stylesheet" href="/BloodDonation/styles.css"><style>.form{max-width:720px;margin:1rem;padding:1rem;background:#fff;border-radius:8px}</style></head><body>
  <div class="admin-wrap">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
      <div style="padding:1rem;">
        <a href="donors.php">← Back</a>
        <h2 id="title">Create Donor</h2>
      </div>

      <div style="padding:1rem;">
        <form id="donor-form" class="form" autocomplete="off">
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
            <label>Blood Group
              <select name="bloodgroup" id="bloodgroup">
                <option value="">Select</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
              </select>
            </label>
            <label>Age<input name="age" id="age" type="number" min="0" max="120" /></label>
          </div>
          <div class="field-row">
            <label>NIC / ID
              <input name="nic" id="nic" placeholder="National ID or passport" />
            </label>
            <label>Contact
              <input name="contact_number" id="contact_number" type="tel" placeholder="e.g. +94 7XXXXXXXX" />
            </label>
          </div>
          <div class="field-row">
            <label>WhatsApp
              <input name="whatsapp_number" id="whatsapp_number" type="tel" placeholder="WhatsApp number (optional)" />
            </label>
            <label>Email
              <input name="email" id="email" type="email" required placeholder="you@example.com" />
            </label>
          </div>
          <div class="field-row">
            <label>Street<input name="street" id="street" /></label>
            <label>City<input name="city" id="city" /></label>
          </div>
          <label>Password (leave blank to keep current)
            <input name="password" id="password" type="password" placeholder="••••••" />
            <div class="help">Leave blank to keep existing password.</div>
          </label>
          <div class="actions" style="margin-top:.8rem;"><button class="submit-btn" id="save">Save Donor</button></div>
        </form>
      </div>

      <script>
      async function load(id){
        if(!id) return;
        const res = await fetch('/BloodDonation/admin/admin_donor_api.php?id='+encodeURIComponent(id));
        const b = await res.json();
        if(b.donor){
          document.getElementById('title').textContent='Edit Donor';
          const d = b.donor;
          document.getElementById('id').value = d.id || '';
          document.getElementById('first_name').value = d.first_name || '';
          document.getElementById('last_name').value = d.last_name || '';
          document.getElementById('bloodgroup').value = d.bloodgroup || '';
          document.getElementById('age').value = d.age || '';
          document.getElementById('nic').value = d.nic || '';
          document.getElementById('contact_number').value = d.contact_number || d.contact || '';
          document.getElementById('whatsapp_number').value = d.whatsapp_number || d.whatsapp || '';
          document.getElementById('email').value = d.email || '';
          document.getElementById('street').value = d.street || '';
          document.getElementById('city').value = d.city || '';
        }
      }
      document.addEventListener('DOMContentLoaded', ()=>{ const p=new URLSearchParams(location.search); const id=p.get('id'); if(id) load(id); });
      document.getElementById('donor-form').addEventListener('submit', async (e)=>{
        e.preventDefault();
        const fd = new FormData(e.target);
        const res = await fetch('/BloodDonation/admin/admin_donor_api.php',{method:'POST',body:fd});
        const b = await res.json();
        if(b.success){ alert('Saved'); location.href='donors.php'; } else alert(b.message||b.error||'Failed');
      });
      </script>
    </main>
  </div>
</body></html>
