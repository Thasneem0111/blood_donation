<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// buffer output to avoid accidental HTML before JSON
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
function send_json($payload){
	$buf = ob_get_clean();
	if(!isset($_REQUEST['debug'])) $buf = '';
	if($buf) $payload['_debug'] = $buf;
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($payload);
	exit;
}

$email = $_SESSION['email'] ?? null;
if(!$email){ send_json(['success'=>false,'message'=>'Not authenticated']); }

$full = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$gmail = trim($_POST['gmail'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$password = trim($_POST['password'] ?? '');

// Expect an `admins` table. Try update by email. If table doesn't exist, return helpful message.
// detect admin table (support admins, admin_users, admin)
$possible = ['admins','admin_users','admin'];
$foundTable = null;
foreach($possible as $t){
	$check = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($t) . "'");
	if($check && $check->num_rows > 0){ $foundTable = $t; break; }
}
if(!$foundTable){ send_json(['success'=>false,'message'=>'Admin table not found. Create an `admins` or `admin_users` table or ask for migration SQL.']); }

// find admin id by session email if possible, else fallback to id=1
$adminId = null;
if($email){
	// detect which column stores the admin email/identifier in this table
	$lookupCols = ['email','gmail','gmail_addr','gmail_address','username'];
	$foundLookupCol = null;
	foreach($lookupCols as $col){
		$c = $mysqli->query("SHOW COLUMNS FROM `".$mysqli->real_escape_string($foundTable)."` LIKE '".$mysqli->real_escape_string($col)."'");
		if($c && $c->num_rows>0){ $foundLookupCol = $col; break; }
	}
	if($foundLookupCol){
		$sql = "SELECT id FROM `".$mysqli->real_escape_string($foundTable)."` WHERE `".$mysqli->real_escape_string($foundLookupCol)."` = ? LIMIT 1";
		$stmt = $mysqli->prepare($sql);
		if($stmt){
			$stmt->bind_param('s',$email);
			$stmt->execute();
			$res = $stmt->get_result();
			if($res && $res->num_rows > 0){ $row = $res->fetch_assoc(); $adminId = (int)$row['id']; }
		}
	}
}
if(!$adminId){
	// try id = 1
	$stmt = $mysqli->prepare("SELECT id FROM `".$mysqli->real_escape_string($foundTable)."` WHERE id = 1 LIMIT 1");
	$stmt->execute(); $res = $stmt->get_result();
	if($res && $res->num_rows>0){ $row = $res->fetch_assoc(); $adminId = (int)$row['id']; }
}
if(!$adminId){ send_json(['success'=>false,'message'=>'Admin record not found for current session or id=1']); }

// detect which columns exist and map incoming fields to actual columns
$existingCols = [];
$colRes = $mysqli->query("SHOW COLUMNS FROM `".$mysqli->real_escape_string($foundTable)."`");
if($colRes){
	while($c = $colRes->fetch_assoc()){
		$existingCols[] = $c['Field'];
	}
}

$fields = [];
$types = '';
$values = [];

function pick_col($candidates, $existingCols){
	foreach($candidates as $cand) if(in_array($cand, $existingCols)) return $cand;
	return null;
}

$col_full = pick_col(['full_name','fullname','name'], $existingCols);
$col_user = pick_col(['username','user'], $existingCols);
$col_gmail = pick_col(['gmail','email','gmail_addr','gmail_address'], $existingCols);
$col_contact = pick_col(['contact_number','contact','phone'], $existingCols);

if($full !== '' && $col_full){ $fields[] = "`".$col_full."` = ?"; $types .= 's'; $values[] = $full; }
if($username !== '' && $col_user){ $fields[] = "`".$col_user."` = ?"; $types .= 's'; $values[] = $username; }
if($gmail !== '' && $col_gmail){ $fields[] = "`".$col_gmail."` = ?"; $types .= 's'; $values[] = $gmail; }
if($contact !== '' && $col_contact){ $fields[] = "`".$col_contact."` = ?"; $types .= 's'; $values[] = $contact; }
// detect password column name
$passwordCol = null;
$colRes = $mysqli->query("SHOW COLUMNS FROM `".$mysqli->real_escape_string($foundTable)."` LIKE 'password_hash'");
if($colRes && $colRes->num_rows>0) $passwordCol = 'password_hash';
else {
	$colRes = $mysqli->query("SHOW COLUMNS FROM `".$mysqli->real_escape_string($foundTable)."` LIKE 'password'");
	if($colRes && $colRes->num_rows>0) $passwordCol = 'password';
}
if($password !== ''){
	if($passwordCol) {
		$fields[] = $passwordCol . ' = ?';
		$types .= 's';
		$values[] = password_hash($password, PASSWORD_DEFAULT);
	} else {
		// no known password column, skip updating password but still accept other fields
	}
}

if(empty($fields)){ send_json(['success'=>false,'message'=>'No changes provided']); }

$sql = "UPDATE `".$mysqli->real_escape_string($foundTable)."` SET " . implode(', ', $fields) . " WHERE id = ? LIMIT 1";
$types .= 'i'; $values[] = $adminId;
$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$values);
if($stmt->execute()) send_json(['success'=>true]); else send_json(['success'=>false,'message'=>$stmt->error]);

?>
