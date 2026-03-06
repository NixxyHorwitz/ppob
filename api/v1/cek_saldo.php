<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

error_reporting(0);
ini_set('display_errors',0);

function response($status,$message,$data=null){
    echo json_encode([
        "status"=>$status,
        "message"=>$message,
        "data"=>$data
    ],JSON_UNESCAPED_UNICODE);
    exit;
}

try{

/* ===============================
   VALIDASI METHOD
================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(false,"Invalid request method");
}

/* ===============================
   INPUT
================================= */
$api_key = trim($_POST['api_key'] ?? '');
$pin     = trim($_POST['pin'] ?? '');

if (!$api_key || !$pin) {
    response(false,"API Key dan PIN wajib diisi");
}

/* ===============================
   CEK USER
================================= */
$stmt = $pdo->prepare("
    SELECT fullname,email,saldo
    FROM users
    WHERE api_key = ?
    AND pin = ?
    AND is_active='1'
    LIMIT 1
");

$stmt->execute([$api_key,$pin]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    response(false,"API Key atau PIN salah");
}

/* ===============================
   SUCCESS RESPONSE
================================= */
response(true,"Saldo berhasil diambil",[
    "fullname"=>$user['fullname'],
    "email"=>$user['email'],
    "saldo"=>(int)$user['saldo']
]);

}catch(Throwable $e){
    response(false,"Server error");
}