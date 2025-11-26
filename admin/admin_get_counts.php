<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$out = [
    'success' => true,
    'total_users' => 0,
    'total_donors' => 0,
    'total_seekers' => 0,
    'total_notifications' => 0
];

// donors
$res = $mysqli->query("SELECT COUNT(*) AS c FROM blood_donor");
if ($res) { $row = $res->fetch_assoc(); $out['total_donors'] = (int)$row['c']; }

// seekers
$res = $mysqli->query("SELECT COUNT(*) AS c FROM bloodseeker");
if ($res) { $row = $res->fetch_assoc(); $out['total_seekers'] = (int)$row['c']; }

$out['total_users'] = $out['total_seekers'] + $out['total_donors'];

// notifications (attempt, may not exist)
if ($mysqli->query("SELECT 1 FROM notifications LIMIT 1") !== false) {
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM notifications");
    if ($res) { $row = $res->fetch_assoc(); $out['total_notifications'] = (int)$row['c']; }
}

echo json_encode($out);
exit;

?>
