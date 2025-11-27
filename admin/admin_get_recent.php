<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$out = ['success'=>true, 'notifications'=>[], 'donors'=>[], 'seekers'=>[]];

// recent notifications (if table exists)
if ($mysqli->query("SELECT 1 FROM notifications LIMIT 1") !== false) {
    // select only columns that are safe to query; created_at may not exist in older schemas
    $res = $mysqli->query("SELECT id, IFNULL(full_name,'') AS full_name, IFNULL(contact_number,'') AS contact_number FROM notifications ORDER BY id DESC LIMIT 3");
    if ($res) {
        while($r = $res->fetch_assoc()){
            // attach created_at if present in the row (fetch_assoc won't include missing cols)
            $r['created_at'] = isset($r['created_at']) ? $r['created_at'] : '';
            $out['notifications'][] = $r;
        }
    }
}

// donors
// Try to fetch recent donors; table/column names may vary across installs
$donorTables = ['blood_donor','blooddonor','donors'];
$donors = [];
foreach ($donorTables as $t) {
    // select all columns from candidate donor table (don't use `id, *` which is invalid SQL)
    $q = "SELECT * FROM `".$t."` ORDER BY id DESC LIMIT 3";
    $res = $mysqli->query($q);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            // map to expected keys
            $d = [];
            $d['id'] = isset($r['id']) ? $r['id'] : (isset($r['ID']) ? $r['ID'] : null);
            if (!empty($r['first_name'])) $d['first_name'] = $r['first_name'];
            elseif (!empty($r['firstName'])) $d['first_name'] = $r['firstName'];
            elseif (!empty($r['fname'])) $d['first_name'] = $r['fname'];
            else $d['first_name'] = isset($r['name']) ? $r['name'] : '';

            if (!empty($r['last_name'])) $d['last_name'] = $r['last_name'];
            elseif (!empty($r['lastName'])) $d['last_name'] = $r['lastName'];
            elseif (!empty($r['lname'])) $d['last_name'] = $r['lname'];
            else $d['last_name'] = '';

            // only include fields we need for dashboard: name, contact_number, bloodgroup
            $d['email'] = !empty($r['email']) ? $r['email'] : (!empty($r['gmail']) ? $r['gmail'] : '');
            $d['city'] = !empty($r['city']) ? $r['city'] : (!empty($r['town']) ? $r['town'] : '');

            // contact_number candidates
            $contact_number = '';
            foreach (['contact_number','contact','phone','mobile','phone_no','telephone'] as $c) { if (!empty($r[$c])) { $contact_number = $r[$c]; break; } }
            $d['contact_number'] = $contact_number;

            // whatsapp_number candidates
            $whatsapp_number = '';
            foreach (['whatsapp_number','whatsapp','mobile','phone'] as $c) { if (!empty($r[$c])) { $whatsapp_number = $r[$c]; break; } }
            $d['whatsapp_number'] = $whatsapp_number;


            // bloodgroup candidates
            $bloodgroup = '';
            foreach (['bloodgroup','blood_group','blood_type','blood','bg'] as $c) { if (!empty($r[$c])) { $bloodgroup = $r[$c]; break; } }
            $d['bloodgroup'] = $bloodgroup;
            // created_at fallback (optional)
            $d['created_at'] = isset($r['created_at']) ? $r['created_at'] : (isset($r['createdAt']) ? $r['createdAt'] : '');

            // keep only the keys dashboard needs
            $donors[] = [
                'first_name' => $d['first_name'],
                'last_name' => $d['last_name'],
                'contact_number' => $d['contact_number'],
                'whatsapp_number' => $d['whatsapp_number'],
                'bloodgroup' => $d['bloodgroup'],
                'created_at' => $d['created_at']
            ];
        }
        break; // found a table that worked
    }
}
// ensure only last 3
$out['donors'] = array_slice($donors, 0, 3);

// seekers (include contact_number, whatsapp_number, bloodgroup)
// include created_at when available
$res = $mysqli->query("SELECT id, IFNULL(first_name,'') AS first_name, IFNULL(last_name,'') AS last_name, IFNULL(contact_number,'') AS contact_number, IFNULL(whatsapp_number,'') AS whatsapp_number FROM bloodseeker ORDER BY id DESC LIMIT 3");
if ($res) while($r = $res->fetch_assoc()) {
    // attach created_at if present
    $created = isset($r['created_at']) ? $r['created_at'] : '';
    // Only include fields needed by dashboard
    $out['seekers'][] = [
        'first_name' => $r['first_name'],
        'last_name' => $r['last_name'],
        'contact_number' => $r['contact_number'],
        'whatsapp_number' => $r['whatsapp_number'],
        'created_at' => $created
    ];
}

echo json_encode($out);
exit;
?>
