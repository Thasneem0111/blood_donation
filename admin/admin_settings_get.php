<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/admin_auth.php';

$email = $_SESSION['email'] ?? null;
if(!$email){ echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }

$check = $mysqli->query("SHOW TABLES LIKE 'admins'");
if(!$check || $check->num_rows === 0){ echo json_encode(['success'=>false,'message'=>'Admin table not found']); exit; }

$stmt = $mysqli->prepare("SELECT full_name, username, gmail, contact_number FROM admins WHERE email = ? LIMIT 1");
$stmt->bind_param('s',$email); $stmt->execute(); $res = $stmt->get_result();
if(!$res || $res->num_rows === 0){ echo json_encode(['success'=>false,'message'=>'Admin not found']); exit; }
$r = $res->fetch_assoc();
echo json_encode(['success'=>true,'settings'=>$r]);
exit;
?>
