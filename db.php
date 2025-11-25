<?php
// Database configuration - update if your environment differs
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'blooddonation';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Use UTF-8
$mysqli->set_charset('utf8mb4');
?>
