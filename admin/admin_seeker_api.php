<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// Basic admin CRUD for seekers. No auth in this example.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // list or single
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $mysqli->prepare("SELECT * FROM bloodseeker WHERE id = ? LIMIT 1");
        $stmt->bind_param('i',$id); $stmt->execute(); $res = $stmt->get_result(); $row = $res->fetch_assoc();
        echo json_encode(['success'=>true,'seeker'=>$row]); exit;
    }
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $res = $mysqli->query("SELECT id, first_name, last_name, email, city, contact_number FROM bloodseeker ORDER BY id DESC LIMIT " . max(1,$limit));
    $list = [];
    if ($res) while($r=$res->fetch_assoc()) $list[]=$r;
    echo json_encode(['success'=>true,'seekers'=>$list]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // create or update by id
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $first = $_POST['first_name'] ?? '';
    $last = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $city = $_POST['city'] ?? '';
    $contact = $_POST['contact_number'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($id>0) {
        // update
        $sets = [];$params=[];$types='';
        foreach (['first_name'=>$first,'last_name'=>$last,'email'=>$email,'city'=>$city,'contact_number'=>$contact] as $col=>$val) {
            if ($val!=='') { $sets[] = "$col = ?"; $params[]=$val; $types.='s'; }
        }
        if ($password!=='') { $sets[]='password=?'; $params[]=password_hash($password,PASSWORD_DEFAULT); $types.='s'; }
        if (count($sets)===0) { echo json_encode(['success'=>false,'message'=>'No fields']); exit; }
        $types.='i'; $params[]=$id;
        $sql = "UPDATE bloodseeker SET ".implode(',',$sets)." WHERE id = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'Updated']); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
        exit;
    }
    // create
    if (!$email) { echo json_encode(['success'=>false,'message'=>'Email required']); exit; }
    $pwHash = $password ? password_hash($password,PASSWORD_DEFAULT) : '';
    $stmt = $mysqli->prepare("INSERT INTO bloodseeker (first_name,last_name,email,city,contact_number,password) VALUES (?,?,?,?,?,?)");
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>$mysqli->error]); exit; }
    $stmt->bind_param('ssssss',$first,$last,$email,$city,$contact,$pwHash);
    if ($stmt->execute()) echo json_encode(['success'=>true,'id'=>$stmt->insert_id]); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $d);
    $id = isset($d['id']) ? (int)$d['id'] : 0;
    if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); exit; }
    $stmt = $mysqli->prepare("DELETE FROM bloodseeker WHERE id = ? LIMIT 1");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}

http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);

?>
