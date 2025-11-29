<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// Capture any unexpected output (warnings/notices) so we can return clean JSON
if (session_status() === PHP_SESSION_NONE) {
    // start session only if needed later
}
ob_start();

// Helper to send JSON and include buffer when debug requested
function send_json($payload, $status = 200) {
    http_response_code($status);
    $buf = ob_get_clean();
    if (!empty($buf) && (isset($_REQUEST['debug']) && $_REQUEST['debug'])) {
        $payload['_debug'] = $buf;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

// Simple notifications API - will create table if not exists (basic)
$create = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT DEFAULT NULL,
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
// ensure donor_id column exists
$res = $mysqli->query("SHOW COLUMNS FROM notifications LIKE 'donor_id'");
if (!$res || $res->num_rows === 0) {
    @ $mysqli->query("ALTER TABLE notifications ADD COLUMN donor_id INT DEFAULT NULL");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $donorId = isset($_GET['donor_id']) ? (int)$_GET['donor_id'] : 0;
    // include full_name, email and contact_number when available, optionally filter by donor_id
    if ($donorId) {
        $stmt = $mysqli->prepare("SELECT id, donor_id, IFNULL(full_name,'') AS full_name, IFNULL(email,'') AS email, IFNULL(contact_number,'') AS contact_number, title, message, created_at FROM notifications WHERE donor_id = ? ORDER BY id DESC LIMIT ?");
        $limitParam = max(1,$limit);
        $stmt->bind_param('ii', $donorId, $limitParam);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $mysqli->query("SELECT id, donor_id, IFNULL(full_name,'') AS full_name, IFNULL(email,'') AS email, IFNULL(contact_number,'') AS contact_number, title, message, created_at FROM notifications ORDER BY id DESC LIMIT " . max(1,$limit));
    }
    $list = [];
    if ($res) while($r=$res->fetch_assoc()) $list[]=$r;
    send_json(['success'=>true,'notifications'=>$list]);
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
    // accept optional donor_id to target a specific donor
    $donor_id = isset($_POST['donor_id']) ? (int)$_POST['donor_id'] : null;
    if ($donor_id) {
        $stmt = $mysqli->prepare("INSERT INTO notifications (donor_id,full_name,email,contact_number,title,message) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('isssss',$donor_id,$name,$email,$mobile,$title,$message);
    } else {
        $stmt = $mysqli->prepare("INSERT INTO notifications (full_name,email,contact_number,title,message) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss',$name,$email,$mobile,$title,$message);
    }
    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        $response = ['success'=>true,'id'=>$insertId];
        // if donor_id provided, try to find donor email and send notification email
        if (!empty($donor_id)) {
            $donorEmail = '';
            $donorName = '';
            $tables = ['blood_donor','blooddonor','donors','donor','blood_donors'];
            foreach ($tables as $t) {
                $tbl = $mysqli->real_escape_string($t);
                $sql = "SELECT * FROM `{$tbl}` WHERE id = ? LIMIT 1";
                if ($stmt2 = $mysqli->prepare($sql)) {
                    $stmt2->bind_param('i', $donor_id);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    if ($res2 && $res2->num_rows) {
                        $row = $res2->fetch_assoc();
                        // try common email/name columns
                        $donorEmail = $row['email'] ?? $row['Email'] ?? $row['gmail'] ?? '';
                        $donorName = $row['first_name'] ?? $row['firstName'] ?? $row['name'] ?? '';
                        break;
                    }
                }
            }
            if ($donorEmail) {
                // prepare email
                $to = $donorEmail;
                $subject = $title ?: 'New contact request';
                $bodyLines = [];
                $bodyLines[] = ($name ? "From: {$name}" : 'From: Seeker');
                if ($email) $bodyLines[] = "Email: {$email}";
                if ($mobile) $bodyLines[] = "Contact: {$mobile}";
                $bodyLines[] = '';
                $bodyLines[] = $message;
                $bodyLines[] = '';
                $bodyLines[] = 'You can also sign in to your donor account to view all notifications:';
                $bodyLines[] = (isset($_SERVER['HTTP_HOST']) ? ('http://' . $_SERVER['HTTP_HOST'] . '/BloodDonation/components/donor/notifications.html') : '/BloodDonation/components/donor/notifications.html');
                $body = implode("\n", $bodyLines);
                $headers = 'From: no-reply@blooddonate.local' . "\r\n" . 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=utf-8';
                if ($email) $headers .= "\r\n" . 'Reply-To: ' . $email;
                // attempt to send mail (may fail on local dev)
                $mailOk = false;
                try { $mailOk = mail($to, $subject, $body, $headers); } catch(Exception $e) { $mailOk = false; }
                $response['email_sent'] = $mailOk ? true : false;
                $response['donor_email'] = $donorEmail;
                // If mail failed, save the email to a local log for local development/testing
                if (!$mailOk) {
                    $storageDir = __DIR__ . '/../storage';
                    if (!is_dir($storageDir)) @mkdir($storageDir, 0755, true);
                    $logFile = $storageDir . '/email_log.txt';
                    $logEntry = "---\n" . date('Y-m-d H:i:s') . "\nTO: " . $to . "\nSUBJECT: " . $subject . "\nHEADERS: " . $headers . "\n\n" . $body . "\n---\n\n";
                    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
                    $response['email_saved_log'] = true;
                    $response['email_log_path'] = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $logFile));
                }
            } else {
                $response['email_sent'] = false;
                $response['donor_email'] = null;
            }
        }
        send_json($response);
    } else {
        send_json(['success'=>false,'error'=>$stmt->error], 500);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $d);
    $id = isset($d['id']) ? (int)$d['id'] : 0;
    if (!$id) { send_json(['success'=>false,'message'=>'id required'], 400); }
    $stmt = $mysqli->prepare("DELETE FROM notifications WHERE id = ? LIMIT 1");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) send_json(['success'=>true]); else send_json(['success'=>false,'error'=>$stmt->error], 500);
    exit;
}

send_json(['success'=>false,'message'=>'Method not allowed'], 405);

?>
