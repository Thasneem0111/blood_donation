<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// Simple notifications API - will create table if not exists (basic)
$create = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    contact_number VARCHAR(64) DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$mysqli->query($create);

// ensure legacy columns if the table existed in older shape
$res = $mysqli->query("SHOW COLUMNS FROM notifications LIKE 'full_name'");
if (!$res || $res->num_rows === 0) {
    // attempt to add columns (ignore errors)
    @$mysqli->query("ALTER TABLE notifications ADD COLUMN full_name VARCHAR(255) DEFAULT NULL");
    @ $mysqli->query("ALTER TABLE notifications ADD COLUMN email VARCHAR(255) DEFAULT NULL");
    @ $mysqli->query("ALTER TABLE notifications ADD COLUMN contact_number VARCHAR(64) DEFAULT NULL");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    // include full_name, email and contact_number when available
    $res = $mysqli->query("SELECT id, IFNULL(full_name,'') AS full_name, IFNULL(email,'') AS email, IFNULL(contact_number,'') AS contact_number, title, message, created_at FROM notifications ORDER BY id DESC LIMIT " . max(1,$limit));
    $list = [];
    if ($res) while($r=$res->fetch_assoc()) $list[]=$r;
    echo json_encode(['success'=>true,'notifications'=>$list]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // accept contact form fields: name, mobile, email — also accept title & message
    $name = trim($_POST['name'] ?? ($_POST['full_name'] ?? ''));
    $mobile = trim($_POST['mobile'] ?? ($_POST['contact_number'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $title = trim($_POST['title'] ?? 'Contact Form Submission');
    $message = trim($_POST['message'] ?? '');
    if (!$message) {
        $message = sprintf('Contact form: %s — %s — %s', $name ?: 'n/a', $email ?: 'n/a', $mobile ?: 'n/a');
    }
    $stmt = $mysqli->prepare("INSERT INTO notifications (full_name,email,contact_number,title,message) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss',$name,$email,$mobile,$title,$message);
    if ($stmt->execute()) echo json_encode(['success'=>true,'id'=>$stmt->insert_id]); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $d);
    $id = isset($d['id']) ? (int)$d['id'] : 0;
    if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    $stmt = $mysqli->prepare("DELETE FROM notifications WHERE id = ? LIMIT 1");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}

http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);

?>
