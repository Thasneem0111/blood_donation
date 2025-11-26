<?php
header('Content-Type: application/json; charset=utf-8');
// Simple DB test helper — run locally to inspect connection/table state
require_once __DIR__ . '/db.php';

$result = ['connected' => false, 'db' => null, 'table_exists' => false, 'error' => null];
try {
    if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
        $result['error'] = 'No mysqli connection object';
        echo json_encode($result);
        exit;
    }
    $result['connected'] = true;
    $result['db'] = $mysqli->real_escape_string(isset($DB_NAME) ? $DB_NAME : (isset($DB_NAME) ? $DB_NAME : 'unknown'));

    // Check table exists
    $q = "SHOW TABLES LIKE 'blood_donor'";
    $res = $mysqli->query($q);
    if ($res && $res->num_rows > 0) {
        $result['table_exists'] = true;
        // Count rows
        $r2 = $mysqli->query("SELECT COUNT(*) AS c FROM `blood_donor`");
        if ($r2 && ($row = $r2->fetch_assoc())) $result['count'] = intval($row['c']);
    } else {
        $result['table_exists'] = false;
    }
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result);

?>