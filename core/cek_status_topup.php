<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$ext_id = $_GET['ext_id'] ?? '';
$apiKey = 'ISI_API_KEY_DI_SINI'; // Pastikan ini benar dari dashboard Kira

$payload = json_encode([
    "action" => "check_status",
    "reference_id" => $ext_id
]);

$ch = curl_init('https://kiraqris.com/api/v1/check');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
curl_close($ch);

// Ini untuk mengecek kenapa status masih pending. Buka file ini setelah klik tombol.
file_put_contents('debug_respon_kira.txt', $response);

$result = json_decode($response, true);

// Sesuai gambar 3: status harus true DAN data status harus success
if (isset($result['status']) && $result['status'] === true && $result['data']['status'] === 'success') {
    
    $amount = $result['data']['amount_original'];
    $db_ref = $result['data']['reference_id'];

    $stmt = $pdo->prepare("SELECT user_id FROM topup_history WHERE external_id = ? AND status = 'pending'");
    $stmt->execute([$db_ref]);
    $topup = $stmt->fetch();

    if ($topup) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE topup_history SET status = 'success' WHERE external_id = ?")->execute([$db_ref]);
            $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?")->execute([$amount, $topup['user_id']]);
            $pdo->commit();
            exit(json_encode(['status' => 'success']));
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

exit(json_encode(['status' => 'pending']));