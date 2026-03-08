<?php

/**
 * api/get_products.php
 * AJAX — return JSON list produk berdasarkan ?cat= dan ?type=
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

header('Content-Type: application/json');

$cat  = trim($_GET['cat']  ?? '');
$type = trim($_GET['type'] ?? 'prabayar'); // prabayar | pascabayar

if ($cat === '') {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT sku_code, product_name, price_sell, brand, category
    FROM products
    WHERE status = 'active'
      AND category = ?
      AND type = ?
    ORDER BY price_sell ASC
    LIMIT 50
");
$stmt->execute([$cat, $type]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
