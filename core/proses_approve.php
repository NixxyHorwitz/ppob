<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/functions.php';

$ext_id = $_GET['id'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($ext_id) || empty($action)) {
    header("Location: ../modules/admin/konfirmasi.php");
    exit();
}

$stmt = $pdo->prepare("SELECT user_id, amount FROM topup_history WHERE external_id = ? AND status = 'pending'");
$stmt->execute([$ext_id]);
$trx = $stmt->fetch();

if ($trx) {
    try {
        $pdo->beginTransaction();
        
        $userId = $trx['user_id'];
        $amount = $trx['amount'];

        if ($action === 'approve') {
            $stmtUpdate = $pdo->prepare("UPDATE topup_history SET status = 'success' WHERE external_id = ?");
            $stmtUpdate->execute([$ext_id]);

            $stmtSaldo = $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?");
            $stmtSaldo->execute([$amount, $userId]);
            
            notifyUser($pdo, $userId, "Topup Manual Berhasil", "Topup ID: [$ext_id] sebesar Rp " . number_format($amount) . " telah disetujui.");
            notifyAdmins($pdo, "Topup Manual Disetujui", "Topup ID: [$ext_id] untuk User ID: $userId telah disetujui.");
            
            $status = "success";
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE topup_history SET status = 'failed' WHERE external_id = ?");
            $stmtUpdate->execute([$ext_id]);
            
           notifyUser($pdo, $userId, "Topup Ditolak", "Maaf, topup manual ID: [$ext_id] sebesar Rp " . number_format($amount) . " ditolak.");
            
            $status = "rejected";
        }

        $pdo->commit();
        header("Location: ../modules/admin/konfirmasi.php?status=" . $status);
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: ../modules/admin/konfirmasi.php?status=error");
        exit();
    }
} else {
    header("Location: ../modules/admin/konfirmasi.php?status=notfound");
    exit();
}