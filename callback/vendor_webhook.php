<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/functions.php'; 

$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

if (!$data) die("No data");

$refId = $data['data']['ref_id'];
$status = $data['data']['status']; 
$sn = $data['data']['sn'];
$message = $data['data']['message'];

$stmt = $pdo->prepare("SELECT user_id, sku_code, amount FROM transactions WHERE ref_id = ?");
$stmt->execute([$refId]);
$trx = $stmt->fetch();

if (!$trx) die("Transaction not found");

$userId = $trx['user_id'];
$sku = $trx['sku_code'];

if ($status == 'Sukses') {
    $stmt = $pdo->prepare("UPDATE transactions SET status = 'success', sn = ? WHERE ref_id = ?");
    $stmt->execute([$sn, $refId]);

    notifyUser($pdo, $userId, "Transaksi Berhasil!", "Pesanan $sku telah sukses. SN: $sn");
    notifyAdmins($pdo, "Transaksi Sukses", "Pesanan $sku oleh User ID $userId berhasil dikirim.");

} else if ($status == 'Gagal') {
    if ($trx) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?");
        $stmt->execute([$trx['amount'], $userId]);

        $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed', sn = ? WHERE ref_id = ?");
        $stmt->execute([$message, $refId]);
        $pdo->commit();

        notifyUser($pdo, $userId, "Transaksi Gagal", "Pesanan $sku gagal. Saldo Rp " . number_format($trx['amount']) . " telah dikembalikan.");
        notifyAdmins($pdo, "Transaksi Gagal", "Pesanan $sku oleh User ID $userId Gagal. Saldo otomatis direfund.");
    }
}