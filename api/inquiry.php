<?php

/**
 * api/inquiry.php
 * AJAX POST — cek tagihan pascabayar
 * Expects: sku, target (POST)
 * Returns: JSON dari cekTagihanPasca()
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['rc' => '99', 'message' => 'Unauthorized']);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/transaction.php';

header('Content-Type: application/json');

$sku    = trim($_POST['sku']    ?? '');
$target = trim($_POST['target'] ?? '');

if (!$sku || !$target) {
    echo json_encode(['rc' => '98', 'message' => 'Parameter tidak lengkap']);
    exit;
}

$result = cekTagihanPasca($_SESSION['user_id'], $sku, $target);
echo json_encode($result);
