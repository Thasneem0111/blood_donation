<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

// start session to sign-in user immediately after register
if (session_status() === PHP_SESSION_NONE) session_start();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Helper to get POST value
function getv($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

$userType = strtolower(getv('userType') ?: '');
if (!in_array($userType, ['donor','seeker'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user type']);
    exit;
}

// Common fields
$first = getv('firstName') ?: getv('donorFirstName') ?: getv('seekerFirstName');
$last = getv('lastName') ?: getv('donorLastName') ?: getv('seekerLastName');
$age = getv('age') ?: getv('donorAge') ?: getv('seekerAge');
$nic = getv('nic') ?: getv('donorNic') ?: getv('seekerNic');
$contact = getv('contact') ?: getv('donorContact') ?: getv('seekerContact');
$whatsapp = getv('whatsapp') ?: getv('whatsappNumber') ?: '';
$email = getv('email') ?: getv('donorEmail') ?: getv('seekerEmail');
$street = getv('street') ?: '';
$city = getv('city') ?: '';
$password = getv('password') ?: getv('donorPassword') ?: getv('seekerPassword');
$notes = getv('seekerNotes') ?: getv('notes') ?: '';
$bloodgroup = getv('bloodGroup') ?: getv('donorBloodGroup') ?: '';

// Basic validation
$errors = [];
if (!$first) $errors[] = 'First name is required';
if (!$last) $errors[] = 'Last name is required';
if (!$contact) $errors[] = 'Contact number is required';
if (!$email) $errors[] = 'Email is required';
if (!$password) $errors[] = 'Password is required';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
    exit;
}

// Determine target table and redirect
if ($userType === 'donor') {
    $table = 'blood_donor';
    $redirect = 'components/donor/donor.html';
} else {
    $table = 'bloodseeker';
    $redirect = 'components/bloodseeker/bloodseeker.html';
}

// Check if email already exists in either table (prevent duplicate registration across types)
// simpler: run two checks
$stmt = $mysqli->prepare("SELECT id FROM blood_donor WHERE email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute(); $stmt->store_result();
    if ($stmt->num_rows > 0) { echo json_encode(['success' => false, 'message' => 'Email already registered']); exit; }
    $stmt->close();
}
$stmt2 = $mysqli->prepare("SELECT id FROM bloodseeker WHERE email = ? LIMIT 1");
if ($stmt2) {
    $stmt2->bind_param('s', $email);
    $stmt2->execute(); $stmt2->store_result();
    if ($stmt2->num_rows > 0) { echo json_encode(['success' => false, 'message' => 'Email already registered']); exit; }
    $stmt2->close();
}

// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

if ($userType === 'donor') {
    $ins = $mysqli->prepare("INSERT INTO blood_donor (first_name, last_name, age, nic, contact_number, whatsapp_number, email, street, city, password, bloodgroup) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    // types: first(s), last(s), age(i), nic(s), contact(s), whatsapp(s), email(s), street(s), city(s), password(s), bloodgroup(s)
    $ins->bind_param('ssissssssss', $first, $last, $age, $nic, $contact, $whatsapp, $email, $street, $city, $hash, $bloodgroup);
} else {
    // Note: `bloodseeker` table in the project does not include a `notes` column based on schema; insert without notes
    $ins = $mysqli->prepare("INSERT INTO bloodseeker (first_name, last_name, age, nic, contact_number, whatsapp_number, email, street, city, password) VALUES (?,?,?,?,?,?,?,?,?,?)");
    // types: first(s), last(s), age(i), nic(s), contact(s), whatsapp(s), email(s), street(s), city(s), password(s)
    $ins->bind_param('ssisssssss', $first, $last, $age, $nic, $contact, $whatsapp, $email, $street, $city, $hash);
}

if (!$ins) {
    echo json_encode(['success' => false, 'message' => 'Database error (prepare insert)']);
    exit;
}

if ($ins->execute()) {
    // set session so user is treated as logged in immediately
    $_SESSION['email'] = $email;
    $_SESSION['userType'] = $userType; // 'donor' or 'seeker'
    echo json_encode(['success' => true, 'message' => 'Registration successful', 'redirect' => $redirect]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
    exit;
}

?>
