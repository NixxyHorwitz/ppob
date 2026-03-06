<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/kiraqris.php';

ob_start();
header('Content-Type: application/json');

$ext_id = trim($_GET['ext_id'] ?? '');
$api_key = trim(KIRA_API_KEY ?? '');
$secret_key = trim(KIRA_SECRET_KEY ?? '');

if (!$ext_id || !$api_key || !$secret_key) {
    exit(json_encode(['status' => 'error', 'msg' => 'Kredensial tidak lengkap']));
}

$payload = json_encode([
    'action'       => 'check_status',
    'reference_id' => $ext_id
]);

$ch = curl_init('https://kiraqris.com/api/v1');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-KEY: ' . $api_key,   
        'X-SECRET-KEY: ' . $secret_key
    ],
]);

$response = curl_exec($ch);
$result   = json_decode($response, true);
curl_close($ch);

if (isset($result['status']) && $result['status'] === true && isset($result['data']['status'])) {
    
    $status_api = strtolower($result['data']['status']);

    if ($status_api === 'success') {
        $stmt = $pdo->prepare("SELECT user_id, amount FROM topup_history WHERE external_id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$ext_id]);
        $topup = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($topup) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE topup_history SET status = 'success' WHERE external_id = ?")->execute([$ext_id]);
                $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?")->execute([$topup['amount'], $topup['user_id']]);
                $pdo->commit();
                exit(json_encode(['status' => 'success']));
            } catch (Exception $e) {
                $pdo->rollBack();
                exit(json_encode(['status' => 'error', 'msg' => 'Database error']));
            }
        } else {
            exit(json_encode(['status' => 'success']));
        }
    } 
    
    // Pemetaan status gagal yang lebih luas (lowercase untuk keamanan)
    $fail_statuses = ['failed', 'expired', 'canceled', 'gagal', 'kadaluwarsa', 'expired_transaction'];
    if (in_array($status_api, $fail_statuses)) {
        $pdo->prepare("UPDATE topup_history SET status = 'failed' WHERE external_id = ? AND status = 'pending'")->execute([$ext_id]);
        exit(json_encode(['status' => 'failed']));
    }
}

exit(json_encode(['status' => 'pending']));