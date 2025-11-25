<?php
header('Content-Type: application/json; charset=utf-8');
// Simple endpoint to return donor list as JSON
// Reuses db.php for DB connection
require_once __DIR__ . '/db.php';

try {
    $sql = "SELECT * FROM `blooddonor` ORDER BY id DESC";
    if (!$result = $mysqli->query($sql)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Query failed.']);
        exit;
    }

    $rows = [];
    while ($r = $result->fetch_assoc()) {
        // Normalize column names to the front-end expected keys if possible
        $donor = [];
        // id
        $donor['id'] = isset($r['id']) ? $r['id'] : (isset($r['ID']) ? $r['ID'] : null);
        // name fields - support multiple naming conventions
        if (isset($r['firstName'])) $donor['firstName'] = $r['firstName'];
        elseif (isset($r['first_name'])) $donor['firstName'] = $r['first_name'];
        elseif (isset($r['fname'])) $donor['firstName'] = $r['fname'];
        else $donor['firstName'] = isset($r['name']) ? $r['name'] : '';

        if (isset($r['lastName'])) $donor['lastName'] = $r['lastName'];
        elseif (isset($r['last_name'])) $donor['lastName'] = $r['last_name'];
        elseif (isset($r['lname'])) $donor['lastName'] = $r['lname'];
        else $donor['lastName'] = '';

        // blood group
        if (isset($r['blood_group'])) $donor['blood_group'] = $r['blood_group'];
        elseif (isset($r['bloodgroup'])) $donor['blood_group'] = $r['bloodgroup'];
        elseif (isset($r['blood'])) $donor['blood_group'] = $r['blood'];
        else $donor['blood_group'] = isset($r['bg']) ? $r['bg'] : '';

        // contact / whatsapp / phone
        $donor['contact'] = isset($r['contact']) ? $r['contact'] : (isset($r['phone']) ? $r['phone'] : (isset($r['mobile']) ? $r['mobile'] : ''));
        $donor['whatsapp'] = isset($r['whatsapp']) ? $r['whatsapp'] : $donor['contact'];

        // city, email, age, nic, street
        $donor['city'] = isset($r['city']) ? $r['city'] : (isset($r['town']) ? $r['town'] : '');
        $donor['email'] = isset($r['email']) ? $r['email'] : (isset($r['gmail']) ? $r['gmail'] : '');
        $donor['age'] = isset($r['age']) ? $r['age'] : (isset($r['dob']) ? $r['dob'] : '');
        $donor['nic'] = isset($r['nic']) ? $r['nic'] : (isset($r['nic_no']) ? $r['nic_no'] : '');
        $donor['street'] = isset($r['street']) ? $r['street'] : (isset($r['address']) ? $r['address'] : '');

        // include any other fields (non-sensitive) — add raw row for debugging if needed
        $rows[] = $donor;
    }

    echo json_encode($rows);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit;
}

?>
