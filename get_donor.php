<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

// allow session-based retrieval when user is logged in
if (session_status() === PHP_SESSION_NONE) session_start();

// GET /get_donor.php?email=you@example.com
// If a session exists and the user is a donor, use session email automatically
$email = '';
if (!empty($_SESSION['email']) && !empty($_SESSION['userType']) && $_SESSION['userType'] === 'donor') {
    $email = trim($_SESSION['email']);
} else {
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
}
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email parameter required or not logged in']);
    exit;
}

// Try common donor table names
$tables = ['blood_donor','blooddonor','donors','donor','blood_donors'];
$row = null;
foreach ($tables as $t) {
    // use a safe prepared statement
    $sql = "SELECT * FROM `" . $mysqli->real_escape_string($t) . "` WHERE email = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) continue;
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows) { $row = $res->fetch_assoc(); break; }
}

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Donor not found']);
    exit;
}

// Normalize common fields to the same keys the UI expects
$donor = [];
$donor['id'] = isset($row['id']) ? $row['id'] : (isset($row['ID']) ? $row['ID'] : null);
$donor['first_name'] = isset($row['first_name']) ? $row['first_name'] : (isset($row['firstName']) ? $row['firstName'] : (isset($row['fname']) ? $row['fname'] : (isset($row['name']) ? $row['name'] : '')));
$donor['last_name'] = isset($row['last_name']) ? $row['last_name'] : (isset($row['lastName']) ? $row['lastName'] : (isset($row['lname']) ? $row['lname'] : ''));
$donor['blood_group'] = isset($row['blood_group']) ? $row['blood_group'] : (isset($row['bloodgroup']) ? $row['bloodgroup'] : (isset($row['blood']) ? $row['blood'] : ''));

$contactCandidates = ['contact_number','contact','phone','mobile','phone_no','phoneNumber','contact_no','contactnumber','telephone','tel','mobile_no','mobile_number','cell'];
$foundContact = '';
foreach ($contactCandidates as $c) { if (!empty($row[$c])) { $foundContact = $row[$c]; break; } }
$donor['contact_number'] = $foundContact;

$waCandidates = ['whatsapp_number','whatsapp','whatsapp_no','wa','watsap'];
$foundWa = '';
foreach ($waCandidates as $w) { if (!empty($row[$w])) { $foundWa = $row[$w]; break; } }
if (!$foundWa) $foundWa = $foundContact;
$donor['whatsapp_number'] = $foundWa;

$donor['age'] = isset($row['age']) ? $row['age'] : (isset($row['dob']) ? $row['dob'] : '');
$donor['nic'] = isset($row['nic']) ? $row['nic'] : (isset($row['nic_no']) ? $row['nic_no'] : '');
$donor['email'] = isset($row['email']) ? $row['email'] : (isset($row['gmail']) ? $row['gmail'] : '');
$donor['street'] = isset($row['street']) ? $row['street'] : (isset($row['address']) ? $row['address'] : '');
$donor['city'] = isset($row['city']) ? $row['city'] : (isset($row['town']) ? $row['town'] : '');

// include raw row for debugging if needed
$donor['raw'] = $row;

echo json_encode(['success' => true, 'donor' => $donor]);
exit;
?>
