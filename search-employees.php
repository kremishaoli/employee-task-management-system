<?php
session_start();
include 'db.php';
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])){
    http_response_code(403); exit('Forbidden');
}
$q = trim($_GET['q'] ?? '');
$data = [];
if($q !== ''){
    $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE role='employee' AND fullname LIKE ? LIMIT 10");
    $like = "%$q%";
    $stmt->bind_param('s',$like);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row=$res->fetch_assoc()) $data[]=$row;
    $stmt->close();
}
echo json_encode(['success'=>true,'data'=>$data]);
