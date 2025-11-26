<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

// start session so we can persist logged-in user
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Check blood_donor first
$stmt = $mysqli->prepare("SELECT id FROM blood_donor WHERE email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
        if ($stmt->num_rows > 0) {
        // set session to mark user logged in as donor
        $_SESSION['email'] = $email;
        $_SESSION['userType'] = 'donor';
        echo json_encode(['success' => true, 'message' => 'Found donor account', 'redirect' => 'components/donor/donor.html']);
        exit;
    }
    $stmt->close();
}

// Then check bloodseeker
$stmt2 = $mysqli->prepare("SELECT id FROM bloodseeker WHERE email = ? LIMIT 1");
if ($stmt2) {
    $stmt2->bind_param('s', $email);
    $stmt2->execute();
    $stmt2->store_result();
    if ($stmt2->num_rows > 0) {
        // set session to mark user logged in as seeker
        $_SESSION['email'] = $email;
        $_SESSION['userType'] = 'seeker';
        echo json_encode(['success' => true, 'message' => 'Found seeker account', 'redirect' => 'components/bloodseeker/bloodseeker.html']);
        exit;
    }
    $stmt2->close();
}

echo json_encode(['success' => false, 'message' => 'No account found with that email']);
exit;

?>
