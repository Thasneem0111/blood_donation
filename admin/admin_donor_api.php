<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// Basic admin CRUD for donors
function column_exists($mysqli, $table, $col){
    $t = $mysqli->real_escape_string($table);
    $c = $mysqli->real_escape_string($col);
    $res = $mysqli->query("SHOW COLUMNS FROM `".$t."` LIKE '".$c."'");
    return ($res && $res->num_rows>0);
}

function ensure_columns($mysqli, $table, $cols){
    foreach($cols as $name => $def){
        if(!column_exists($mysqli,$table,$name)){
            $t = $mysqli->real_escape_string($table);
            $c = $mysqli->real_escape_string($name);
            $sql = "ALTER TABLE `".$t."` ADD COLUMN `".$c."` $def";
            $mysqli->query($sql);
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $mysqli->prepare("SELECT * FROM blood_donor WHERE id = ? LIMIT 1");
        $stmt->bind_param('i',$id); $stmt->execute(); $res = $stmt->get_result(); $row = $res->fetch_assoc();
        echo json_encode(['success'=>true,'donor'=>$row]); exit;
    }
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    // select common columns; some installs may also have alternative column names
    // prefer the column names you used: contact_number, whatsapp_number, bloodgroup
    $res = $mysqli->query("SELECT id, first_name, last_name, email, city, IFNULL(contact_number, '') AS contact_number, IFNULL(whatsapp_number, '') AS whatsapp_number, IFNULL(bloodgroup, '') AS bloodgroup FROM blood_donor ORDER BY id DESC LIMIT " . max(1,$limit));
    $list = [];
    if ($res) while($r=$res->fetch_assoc()) $list[]=$r;
    echo json_encode(['success'=>true,'donors'=>$list]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure optional columns exist so admin UI can save additional fields (only non-conflicting ones)
    ensure_columns($mysqli,'blood_donor',[
        'nic'=>'VARCHAR(64) DEFAULT ""',
        'age'=>'INT(3) DEFAULT NULL',
        'street'=>'VARCHAR(255) DEFAULT ""'
    ]);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $first = $_POST['first_name'] ?? '';
    $last = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $city = $_POST['city'] ?? '';
    // use the column names you created: contact_number, whatsapp_number, bloodgroup
    $contact = $_POST['contact_number'] ?? '';
    $bloodgroup = $_POST['bloodgroup'] ?? '';
    $age = isset($_POST['age']) ? $_POST['age'] : '';
    $nic = $_POST['nic'] ?? '';
    $whatsapp_number = $_POST['whatsapp_number'] ?? '';
    $street = $_POST['street'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($id>0) {
        $sets=[];$params=[];$types='';
        // map to actual DB column names used in your schema
        $cands = ['first_name'=>$first,'last_name'=>$last,'email'=>$email,'city'=>$city,'contact_number'=>$contact,'bloodgroup'=>$bloodgroup,'age'=>$age,'nic'=>$nic,'whatsapp_number'=>$whatsapp_number,'street'=>$street];
        foreach ($cands as $col=>$val) {
            if ($val!=='' && column_exists($mysqli,'blood_donor',$col)) { $sets[] = "$col = ?"; $params[] = $val; $types.='s'; }
        }
        if ($password!=='') { $sets[]='password=?'; $params[]=password_hash($password,PASSWORD_DEFAULT); $types.='s'; }
        if (count($sets)===0) { echo json_encode(['success'=>false,'message'=>'No fields']); exit; }
        $types.='i'; $params[]=$id;
        $sql = "UPDATE blood_donor SET ".implode(',',$sets)." WHERE id = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'Updated']); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
        exit;
    }
    if (!$email) { echo json_encode(['success'=>false,'message'=>'Email required']); exit; }
    $pwHash = $password ? password_hash($password,PASSWORD_DEFAULT) : '';
    // build insert dynamically but only for columns that exist
    $insertCols = [];$placeholders=[];$insertParams=[];$types='';
    $candIns = ['first_name'=>$first,'last_name'=>$last,'email'=>$email,'city'=>$city,'contact_number'=>$contact,'bloodgroup'=>$bloodgroup,'age'=>$age,'nic'=>$nic,'whatsapp_number'=>$whatsapp_number,'street'=>$street,'password'=>$pwHash];
    foreach ($candIns as $col=>$val) {
        if ($col==='password') {
            if ($val!=='') { if (column_exists($mysqli,'blood_donor','password')) { $insertCols[]='password'; $placeholders[]='?'; $insertParams[]=$val; $types.='s'; } }
        } else {
            if ($val!=='' && column_exists($mysqli,'blood_donor',$col)) { $insertCols[]=$col; $placeholders[]='?'; $insertParams[]=$val; $types.='s'; }
        }
    }
    if (count($insertCols)===0) { echo json_encode(['success'=>false,'message'=>'No insertable fields or table schema incompatible']); exit; }
    $sql = "INSERT INTO blood_donor (".implode(',',$insertCols).") VALUES (".implode(',',$placeholders).")";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>$mysqli->error,'sql'=>$sql]); exit; }
    $stmt->bind_param($types, ...$insertParams);
    if ($stmt->execute()) echo json_encode(['success'=>true,'id'=>$stmt->insert_id]); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $d);
    $id = isset($d['id']) ? (int)$d['id'] : 0;
    if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    $stmt = $mysqli->prepare("DELETE FROM blood_donor WHERE id = ? LIMIT 1");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}

http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);

?>
