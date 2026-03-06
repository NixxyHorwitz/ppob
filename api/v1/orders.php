<?php
require '../../config/database.php';

/* ===================================
   FORCE JSON RESPONSE
=================================== */
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors',0);

/* ===================================
   JSON RESPONSE FUNCTION
=================================== */
function response($status,$message,$data=null){
    echo json_encode([
        "status"=>$status,
        "message"=>$message,
        "data"=>$data
    ],JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===================================
   AMBIL INPUT
=================================== */

$api_key = trim($_POST['api_key'] ?? '');
$pin     = trim($_POST['pin'] ?? '');
$sku     = trim($_POST['sku_code'] ?? '');
$target  = trim($_POST['target'] ?? '');
$ref_id  = trim($_POST['ref_id'] ?? '');

if(!$api_key || !$pin || !$sku || !$target || !$ref_id){
    response(false,"Parameter tidak lengkap");
}

/* ===============================
   CEK USER (AMBIL USER ID)
================================= */
$stmt = $pdo->prepare("
    SELECT id, fullname, email, saldo
    FROM users
    WHERE api_key = ?
    AND pin = ?
    AND is_active = '1'
    LIMIT 1
");
$stmt->execute([$api_key, $pin]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    response(false,"API Key atau PIN salah");
}

$user_id = (int)$user['id'];

/* ===================================
   CEK PRODUK
=================================== */
$product=$pdo->prepare("
    SELECT sku_code, price_sell, type
    FROM products
    WHERE sku_code=? AND status='active'
    LIMIT 1
");
$product->execute([$sku]);

$p=$product->fetch(PDO::FETCH_ASSOC);

if(!$p){
    response(false,"Produk tidak ditemukan");
}

$harga=(int)$p['price_sell'];


/* ===================================
   TRANSAKSI DATABASE (SAFE PPOB MODE)
=================================== */
try{

    $pdo->beginTransaction();

    /* -----------------------------
       LOCK USER (ANTI DOUBLE ORDER)
    ----------------------------- */
    $lockUser = $pdo->prepare("
        SELECT saldo FROM users
        WHERE id=? FOR UPDATE
    ");
    $lockUser->execute([$user_id]);
    $lockedUser = $lockUser->fetch(PDO::FETCH_ASSOC);

    if(!$lockedUser){
        throw new Exception("User tidak ditemukan");
    }

    /* -----------------------------
       CEK DUPLIKASI REF_ID (LOCK)
    ----------------------------- */
    $cek=$pdo->prepare("
        SELECT id FROM transactions
        WHERE ref_id=? AND user_id=? LIMIT 1
    ");
    $cek->execute([$ref_id, $user_id]);

    if($cek->fetch()){
        throw new Exception("Ref ID sudah digunakan");
    }

    /* -----------------------------
       CEK SALDO REALTIME
    ----------------------------- */
    if($lockedUser['saldo'] < $harga){
        throw new Exception("Saldo tidak cukup");
    }

    /* -----------------------------
       POTONG SALDO
    ----------------------------- */
    $update=$pdo->prepare("
        UPDATE users
        SET saldo = saldo - ?
        WHERE id=?
    ");
    $update->execute([$harga,$user_id]);

    /* -----------------------------
       INSERT TRANSAKSI
    ----------------------------- */
    $insert=$pdo->prepare("
        INSERT INTO transactions
        (user_id,ref_id,target,sku_code,amount,type,status,created_at)
        VALUES (?,?,?,?,?,?,?,NOW())
    ");

    $insert->execute([
        $user_id,
        $ref_id,
        $target,
        $sku,
        $harga,
        $p['type'],
        'pending'
    ]);

    $trx_id=$pdo->lastInsertId();

    $pdo->commit();

}catch(Exception $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    response(false,$e->getMessage());
}

/* ===================================
   RESPONSE SUCCESS
=================================== */
response(true,"Order berhasil dibuat",[
    "trx_id"=>$trx_id,
    "ref_id"=>$ref_id,
    "target"=>$target,
    "sku_code"=>$sku,
    "price"=>$harga,
    "status"=>"pending"
]);