<?php
require_once __DIR__ . '/../config/database.php';

// Ambil data JSON dari Kira
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (isset($data['status']) && $result['status'] === true && $data['data']['status'] === 'success') {
    $ext_id = $data['data']['reference_id'];
    $amount = $data['data']['amount_original'];

    $stmt = $pdo->prepare("SELECT id, user_id FROM topup_history WHERE external_id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$ext_id]);
    $topup = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($topup) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE topup_history SET status = 'success' WHERE external_id = ?")->execute([$ext_id]);
            $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?")->execute([$amount, $topup['user_id']]);
            $pdo->commit();
            
            echo json_encode(['status' => 'ok', 'msg' => 'Callback processed']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
        }
    } else {
        echo json_encode(['status' => 'ok', 'msg' => 'Already processed or not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'msg' => 'Invalid data']);
}