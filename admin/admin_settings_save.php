<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/admin_auth.php';

$email = $_SESSION['email'] ?? null;
if(!$email){ echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }

$full = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$gmail = trim($_POST['gmail'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$password = trim($_POST['password'] ?? '');

// Expect an `admins` table. Try update by email. If table doesn't exist, return helpful message.
$check = $mysqli->query("SHOW TABLES LIKE 'admins'");
if(!$check || $check->num_rows === 0){ echo json_encode(['success'=>false,'message'=>'Admin table not found. Create an `admins` table or let me know and I can add migration SQL.']); exit; }

$stmt = $mysqli->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
$stmt->bind_param('s',$email); $stmt->execute(); $res = $stmt->get_result();
if(!$res || $res->num_rows === 0){ echo json_encode(['success'=>false,'message'=>'Admin record not found for current session email']); exit; }
$row = $res->fetch_assoc(); $adminId = (int)$row['id'];

$fields = [];
$types = '';
$values = [];
if($full !== ''){ $fields[] = 'full_name = ?'; $types .= 's'; $values[] = $full; }
if($username !== ''){ $fields[] = 'username = ?'; $types .= 's'; $values[] = $username; }
if($gmail !== ''){ $fields[] = 'gmail = ?'; $types .= 's'; $values[] = $gmail; }
if($contact !== ''){ $fields[] = 'contact_number = ?'; $types .= 's'; $values[] = $contact; }
if($password !== ''){ $fields[] = 'password_hash = ?'; $types .= 's'; $values[] = password_hash($password, PASSWORD_DEFAULT); }

if(empty($fields)){ echo json_encode(['success'=>false,'message'=>'No changes provided']); exit; }

$sql = "UPDATE admins SET " . implode(', ', $fields) . " WHERE id = ? LIMIT 1";
$types .= 'i'; $values[] = $adminId;
$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$values);
if($stmt->execute()) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'message'=>$stmt->error]);

?>
