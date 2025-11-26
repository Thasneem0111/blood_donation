<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$out = ['success'=>true, 'notifications'=>[], 'donors'=>[], 'seekers'=>[]];

// recent notifications (if table exists)
if ($mysqli->query("SELECT 1 FROM notifications LIMIT 1") !== false) {
    $res = $mysqli->query("SELECT id, title, message, created_at FROM notifications ORDER BY id DESC LIMIT 3");
    if ($res) while($r = $res->fetch_assoc()) $out['notifications'][] = $r;
}

// donors
$res = $mysqli->query("SELECT id, first_name, last_name, email, city, contact FROM blood_donor ORDER BY id DESC LIMIT 3");
if ($res) while($r = $res->fetch_assoc()) $out['donors'][] = $r;

// seekers
$res = $mysqli->query("SELECT id, first_name, last_name, email, city, contact_number AS contact FROM bloodseeker ORDER BY id DESC LIMIT 3");
if ($res) while($r = $res->fetch_assoc()) $out['seekers'][] = $r;

echo json_encode($out);
exit;
?>
