<?php
// Do not output raw PHP errors as HTML — return JSON instead. Log errors instead.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Convert PHP errors to exceptions so we can return JSON
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $payload = ['success' => false, 'message' => 'Server exception', 'error' => $e->getMessage()];
    echo json_encode($payload);
    exit;
});

header('Content-Type: application/json; charset=utf-8');

// Simple endpoint to return donor list as JSON
// Reuses db.php for DB connection
$dbFile = __DIR__ . '/db.php';
if (!file_exists($dbFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'db.php not found at expected path.', 'path' => $dbFile]);
    exit;
}

require_once $dbFile;

// Ensure $mysqli exists and is a mysqli instance
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection is not available.']);
    exit;
}

$sql = "SELECT * FROM `blood_donor` ORDER BY id DESC";
$result = $mysqli->query($sql);
if ($result === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query failed', 'error' => $mysqli->error]);
    exit;
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    $donor = [];
    $donor['id'] = isset($r['id']) ? $r['id'] : (isset($r['ID']) ? $r['ID'] : null);
    // Name support
    if (!empty($r['firstName'])) $donor['firstName'] = $r['firstName'];
    elseif (!empty($r['first_name'])) $donor['firstName'] = $r['first_name'];
    elseif (!empty($r['fname'])) $donor['firstName'] = $r['fname'];
    else $donor['firstName'] = isset($r['name']) ? $r['name'] : '';

    if (!empty($r['lastName'])) $donor['lastName'] = $r['lastName'];
    elseif (!empty($r['last_name'])) $donor['lastName'] = $r['last_name'];
    elseif (!empty($r['lname'])) $donor['lastName'] = $r['lname'];
    else $donor['lastName'] = '';

    // Blood group
    if (!empty($r['blood_group'])) $donor['blood_group'] = $r['blood_group'];
    elseif (!empty($r['bloodgroup'])) $donor['blood_group'] = $r['bloodgroup'];
    elseif (!empty($r['blood'])) $donor['blood_group'] = $r['blood'];
    else $donor['blood_group'] = isset($r['bg']) ? $r['bg'] : '';

    // Contact info - check many possible column names used in various schemas
    $contactCandidates = ['contact','phone','mobile','phone_no','phone_no','phoneNumber','phonenumber','contact_no','contactnumber','telephone','tel','mobile_no','mobile_number','contact_number','cell','contactnumber'];
    $foundContact = '';
    foreach ($contactCandidates as $c) {
        if (!empty($r[$c])) { $foundContact = $r[$c]; break; }
    }
    $donor['contact'] = $foundContact;

    $waCandidates = ['whatsapp','whatsapp_no','whatsapp_number','wa','whatsappNumber','whatsapp_no','wapp','watsap'];
    $foundWa = '';
    foreach ($waCandidates as $w) {
        if (!empty($r[$w])) { $foundWa = $r[$w]; break; }
    }
    // fallback to contact if whatsapp not separately provided
    $donor['whatsapp'] = $foundWa ?: $donor['contact'];

    // Other fields
    $donor['city'] = !empty($r['city']) ? $r['city'] : (!empty($r['town']) ? $r['town'] : '');
    $donor['email'] = !empty($r['email']) ? $r['email'] : (!empty($r['gmail']) ? $r['gmail'] : '');
    $donor['age'] = !empty($r['age']) ? $r['age'] : (isset($r['dob']) ? $r['dob'] : '');
    $donor['nic'] = !empty($r['nic']) ? $r['nic'] : (isset($r['nic_no']) ? $r['nic_no'] : '');
    $donor['street'] = !empty($r['street']) ? $r['street'] : (isset($r['address']) ? $r['address'] : '');

    // include raw DB row for debugging (helpful during local development)
    $donor['raw'] = $r;
    $rows[] = $donor;
}

echo json_encode($rows);
exit;

?>
