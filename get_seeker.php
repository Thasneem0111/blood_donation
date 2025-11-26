<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

// allow session-based retrieval when user is logged in
if (session_status() === PHP_SESSION_NONE) session_start();

// GET /get_seeker.php?email=you@example.com
// If a session exists and the user is a seeker, use session email automatically
$email = '';
if (!empty($_SESSION['email']) && !empty($_SESSION['userType']) && $_SESSION['userType'] === 'seeker') {
    $email = trim($_SESSION['email']);
} else {
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
}
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email parameter required or not logged in']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id, first_name, last_name, age, nic, contact_number, whatsapp_number, email, street, city FROM bloodseeker WHERE email = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare failed']);
    exit;
}
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Seeker not found']);
    exit;
}
$row = $res->fetch_assoc();
echo json_encode(['success' => true, 'seeker' => $row]);
exit;
?>
