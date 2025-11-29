<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// Start output buffering to avoid accidental HTML/warnings before JSON
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

function send_json($payload){
	$buf = ob_get_clean();
	if(!isset($_REQUEST['debug'])){
		// discard any buffered non-JSON output in normal operation
		$buf = '';
	}
	if($buf) $payload['_debug'] = $buf;
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($payload);
	exit;
}

$email = $_SESSION['email'] ?? null;
if(!$email){ send_json(['success'=>false,'message'=>'Not authenticated']); }

// Try multiple possible admin table names
$possible = ['admins','admin_users','admin'];
$foundTable = null;
foreach($possible as $t){
	$check = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($t) . "'");
	if($check && $check->num_rows > 0){ $foundTable = $t; break; }
}
if(!$foundTable){ send_json(['success'=>false,'message'=>'Admin table not found']); }

// Prefer session email lookup, fallback to id=1 if not available
$params = [];
if($email){
	$sql = "SELECT id, full_name, username, gmail, contact AS contact_number, contact_number, gmail AS gmail_addr, email FROM `".$mysqli->real_escape_string($foundTable)."` WHERE email = ? LIMIT 1";
	$stmt = $mysqli->prepare($sql);
	if($stmt){
		$stmt->bind_param('s',$email);
		$stmt->execute();
		$res = $stmt->get_result();
		if($res && $res->num_rows > 0){
			$r = $res->fetch_assoc();
			// normalize keys
			$out = [];
			$out['full_name'] = $r['full_name'] ?? ($r['fullname'] ?? ($r['name'] ?? ''));
			$out['username'] = $r['username'] ?? ($r['user'] ?? '');
			$out['gmail'] = $r['gmail'] ?? ($r['gmail_addr'] ?? ($r['gmail_address'] ?? ''));
			$out['contact_number'] = $r['contact_number'] ?? ($r['contact'] ?? '');
			send_json(['success'=>true,'settings'=>$out]);
		}
		// else try fallback below
	}
	// if prepare failed, continue to fallback
}

// fallback to id = 1 record
$stmt = $mysqli->prepare("SELECT id, full_name, username, gmail, contact AS contact_number, contact_number, gmail AS gmail_addr FROM `".$mysqli->real_escape_string($foundTable)."` WHERE id = 1 LIMIT 1");
$stmt->execute(); $res = $stmt->get_result();
if(!$res || $res->num_rows === 0){ send_json(['success'=>false,'message'=>'Admin not found']); }
$r = $res->fetch_assoc();
$out = [];
$out['full_name'] = $r['full_name'] ?? ($r['fullname'] ?? ($r['name'] ?? ''));
$out['username'] = $r['username'] ?? ($r['user'] ?? '');
$out['gmail'] = $r['gmail'] ?? ($r['gmail_addr'] ?? ($r['gmail_address'] ?? ''));
$out['contact_number'] = $r['contact_number'] ?? ($r['contact'] ?? '');
send_json(['success'=>true,'settings'=>$out]);
?>
