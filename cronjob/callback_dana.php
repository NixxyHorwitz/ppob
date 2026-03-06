<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

/* ===============================
   KONFIGURASI
================================= */
$secret_key_app = 'qris';
$incoming_key   = $_SERVER['HTTP_X_SECRET_KEY'] ?? '';

if ($incoming_key !== $secret_key_app) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Akses Ditolak']);
    exit;
}

/* ===============================
   AMBIL PAYLOAD
================================= */
$input = file_get_contents('php://input');

file_put_contents(
    __DIR__ . '/dana_callback_log.txt',
    date('Y-m-d H:i:s')." | RAW: ".$input."\n",
    FILE_APPEND
);

$data = json_decode($input, true);
if (!is_array($data)) {
    parse_str($input, $data);
}

/* ===============================
   VALIDASI DATA DANA
================================= */
if (empty($data['amount']) || empty($data['message'])) {
    echo json_encode(['success'=>false,'message'=>'Payload tidak valid']);
    exit;
}

$amount  = (int) preg_replace('/[^0-9]/','',$data['amount']);
$message = strtolower($data['message']);

/* hanya proses dana masuk */
if (strpos($message,'berhasil menerima') === false) {
    echo json_encode(['success'=>false,'message'=>'Bukan dana masuk']);
    exit;
}

/* ===============================
   CARI TOPUP PENDING
================================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM topup_history
    WHERE status = 'pending'
      AND payment_method = 'QRIS'
      AND amount = ?
    ORDER BY id ASC
    LIMIT 1
");

$stmt->execute([$amount]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) {

    file_put_contents(
        __DIR__.'/dana_callback_log.txt',
        date('Y-m-d H:i:s')." | NOMINAL TIDAK MATCH: {$amount}\n",
        FILE_APPEND
    );

    echo json_encode(['success'=>false,'message'=>'Tidak ada topup pending']);
    exit;
}

/* ===============================
   PROSES TRANSAKSI (ANTI DOUBLE)
================================= */
try {

    $pdo->beginTransaction();

    /* LOCK ROW */
    $lock = $pdo->prepare("
        SELECT status FROM topup_history
        WHERE id = ?
        FOR UPDATE
    ");
    $lock->execute([$trx['id']]);
    $statusNow = $lock->fetchColumn();

    if ($statusNow !== 'pending') {
        throw new Exception('Transaksi sudah diproses');
    }

    /* UPDATE SALDO USER */
    $updateSaldo = $pdo->prepare("
        UPDATE users
        SET saldo = saldo + ?
        WHERE id = ?
    ");

    $updateSaldo->execute([
        $trx['amount_original'], // saldo sesuai nominal asli
        $trx['user_id']
    ]);

    if ($updateSaldo->rowCount() == 0) {
        throw new Exception('User tidak ditemukan');
    }

    /* UPDATE STATUS TOPUP */
    $updateTopup = $pdo->prepare("
        UPDATE topup_history
        SET status = 'success'
        WHERE id = ?
    ");

    $updateTopup->execute([$trx['id']]);

    $pdo->commit();

} catch (Exception $e) {

    $pdo->rollBack();

    file_put_contents(
        __DIR__.'/dana_callback_log.txt',
        date('Y-m-d H:i:s')." | ERROR: ".$e->getMessage()."\n",
        FILE_APPEND
    );

    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}

echo json_encode([
    'success'=>true,
    'message'=>'Deposit berhasil diproses'
]);