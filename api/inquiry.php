<?php

/**
 * api/inquiry.php
 * AJAX POST — cek tagihan pascabayar via Digiflazz
 * Dipanggil oleh services.php bottom sheet via fetch()
 *
 * POST params: sku, target
 * Returns: JSON dari Digiflazz (rc, customer_name, customer_no, selling_price, ref_id, ...)
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['rc' => '99', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/transaction.php';

header('Content-Type: application/json; charset=utf-8');

$sku    = trim($_POST['sku']    ?? '');
$target = trim($_POST['target'] ?? '');

if (!$sku || !$target) {
    echo json_encode(['rc' => '98', 'message' => 'Parameter tidak lengkap']);
    exit;
}

try {
    $result = cekTagihanPasca($_SESSION['user_id'], $sku, $target);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['rc' => '99', 'message' => 'Server error: ' . $e->getMessage()]);
}
