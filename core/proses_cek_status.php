<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/api_handler.php';
require_once __DIR__ . '/functions.php';

$stmt = $pdo->query("SELECT api_username, api_key, api_url FROM website_settings LIMIT 1");
$api = $stmt->fetch(PDO::FETCH_ASSOC);

define('API_USERNAME', $api['api_username']);
define('API_KEY', $api['api_key']);
define('API_URL', $api['api_url']);


header('Content-Type: application/json');

$trxId = $_POST['trx_id'] ?? '';

if (!$trxId) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan']);
    exit;
}

$stmt = $pdo->prepare("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$trxId]);
$trx = $stmt->fetch();

if (!$trx) {
    echo json_encode(['status' => 'error', 'message' => 'Data DB tidak ada']);
    exit;
}

$userId = $trx['user_id'];
$username = $trx['username'];
$sku = $trx['sku_code'];
$oldStatus = $trx['status'];

$refId = $trx['ref_id'];
$sign = md5(API_USERNAME . API_KEY . $refId);

$res = hitVendor('/transaction', [
     'username' => API_USERNAME,
     'ref_id'   => $refId,
     'sign'     => $sign,
     'commands' => 'status'
]);

if (isset($res['data'])) {
    $vendorStatus = strtolower($res['data']['status']); 
    $newPrice = (!empty($res['data']['price'])) ? $res['data']['price'] : $trx['amount'];

    $newStatus = 'pending';
    if ($vendorStatus === 'sukses' || $vendorStatus === 'success') {
        $newStatus = 'success';
        notifyUser($pdo, $userId, "Transaksi Berhasil", "Pesanan $sku ID: [$refId] telah sukses.");
        notifyAdmins($pdo, "Transaksi Sukses", "Pesanan $sku ID: [$refId] milik $username sukses.");
    } elseif ($vendorStatus === 'gagal' || $vendorStatus === 'failed') {
        $newStatus = 'failed';
        notifyUser($pdo, $userId, "Transaksi Gagal", "Pesanan $sku ID: [$refId] gagal. Saldo dikembalikan.");
        notifyAdmins($pdo, "Transaksi Gagal", "Pesanan $sku ID: [$refId] milik $username GAGAL. Saldo direfund.");
    }

    $sn = (!empty($res['data']['sn'])) ? $res['data']['sn'] : $trx['sn'];

   $update = $pdo->prepare("UPDATE transactions SET status = ?, sn = ?, amount = ? WHERE id = ?");
    $update->execute([$newStatus, $sn, $newPrice, $trxId]);

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'new_status' => $newStatus,
        'sn' => $sn,
        'amount' => $newPrice
    ]);
} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Gagal respon dari vendor']);
}