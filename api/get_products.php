<?php

/**
 * api/get_products.php
 * AJAX — return JSON list produk berdasarkan ?cat= dan ?type=
 * Dipanggil oleh services.php bottom sheet via fetch()
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$cat  = trim($_GET['cat']  ?? '');
$type = trim($_GET['type'] ?? 'prabayar');

// type hanya boleh salah satu dari dua ini
if (!in_array($type, ['prabayar', 'pascabayar'])) $type = 'prabayar';

if ($cat === '') {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT sku_code, product_name, price_sell, brand, category
        FROM products
        WHERE status  = 'active'
          AND category = ?
          AND type     = ?
        ORDER BY price_sell ASC
        LIMIT 60
    ");
    $stmt->execute([$cat, $type]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}
