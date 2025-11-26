<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

// support session based identification
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Expect originalEmail to identify row
$orig = isset($_POST['originalEmail']) ? trim($_POST['originalEmail']) : '';
if (!$orig) {
    // try session
    if (!empty($_SESSION['email']) && !empty($_SESSION['userType']) && $_SESSION['userType'] === 'seeker') {
        $orig = trim($_SESSION['email']);
    }
}
if (!$orig) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'originalEmail is required or user not logged in']);
    exit;
}

// Allowed fields mapping from POST to DB columns
$map = [
    'seekerFirstName' => 'first_name',
    'seekerLastName' => 'last_name',
    'seekerAge' => 'age',
    'seekerNic' => 'nic',
    'seekerContact' => 'contact_number',
    'whatsapp' => 'whatsapp_number',
    'seekerEmail' => 'email',
    'email' => 'email',
    'street' => 'street',
    'city' => 'city',
    'password' => 'password'
];

$sets = [];
$types = '';
$values = [];
foreach ($map as $postKey => $col) {
    if (isset($_POST[$postKey]) && $_POST[$postKey] !== '') {
        $val = trim($_POST[$postKey]);
        if ($postKey === 'password') {
            $val = password_hash($val, PASSWORD_DEFAULT);
        }
        $sets[] = "{$col} = ?";
        $types .= 's';
        $values[] = $val;
    }
}

if (count($sets) === 0) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

// If updating email, ensure it doesn't collide with another record
$newEmail = null;
foreach (['seekerEmail','email'] as $k) { if (isset($_POST[$k]) && $_POST[$k] !== '') { $newEmail = trim($_POST[$k]); break; } }
if ($newEmail && strcasecmp($newEmail, $orig) !== 0) {
    // ensure new email is not used by any seeker or donor
    $chk = $mysqli->prepare("SELECT id FROM bloodseeker WHERE email = ? LIMIT 1");
    if ($chk) { $chk->bind_param('s', $newEmail); $chk->execute(); $chk->store_result(); if ($chk->num_rows > 0) { echo json_encode(['success' => false, 'message' => 'Email already used by another seeker']); exit; } }
    $chk2 = $mysqli->prepare("SELECT id FROM blood_donor WHERE email = ? LIMIT 1");
    if ($chk2) { $chk2->bind_param('s', $newEmail); $chk2->execute(); $chk2->store_result(); if ($chk2->num_rows > 0) { echo json_encode(['success' => false, 'message' => 'Email already used by another account']); exit; } }
}

$sql = "UPDATE bloodseeker SET " . implode(', ', $sets) . " WHERE email = ? LIMIT 1";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed', 'error' => $mysqli->error]);
    exit;
}

// bind params
$types .= 's';
$values[] = $orig;
$stmt->bind_param($types, ...$values);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Seeker updated']);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed', 'error' => $stmt->error]);
    exit;
}

?>
