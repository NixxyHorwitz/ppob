<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors',0);
/*
|--------------------------------------------------------------------------
| FUNCTION RESPONSE JSON
|--------------------------------------------------------------------------
*/
function json_response($data){
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

try {

    /* ===============================
       VALIDASI METHOD
    =============================== */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response([
            "status" => false,
            "message" => "Method tidak diizinkan"
        ]);
    }

    /* ===============================
       AMBIL INPUT
    =============================== */
   $api_key = trim($_POST['api_key'] ?? '');
   $pin     = trim($_POST['pin'] ?? '');

    if (empty($api_key) || empty($pin)) {
        json_response([
            "status" => false,
            "message" => "API Key dan PIN wajib diisi"
        ]);
    }

    /* ===============================
       VALIDASI CLIENT API
       (sesuaikan nama tabel user kamu)
    =============================== */
    $stmt = $pdo->prepare("
        SELECT id, fullname, email
        FROM users
        WHERE api_key = ?
        AND pin = ?
        AND is_active='1'
        LIMIT 1
    ");

    $stmt->execute([$api_key, $pin]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        json_response([
            "status" => false,
            "message" => "API Key atau PIN salah"
        ]);
    }

    /* ===============================
       AMBIL DATA PRODUK
    =============================== */
    $stmt = $pdo->prepare("
        SELECT
            sku_code,
            product_name,
            category,
            type,
            brand,
            price_sell,
            status
        FROM products
        WHERE status = 'active'
        ORDER BY category ASC, product_name ASC
    ");

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        "status" => true,
        "message" => "Berhasil mengambil data produk",
        "client" => [
            "fullname" => $user['fullname'],
            "email" => $user['email']
        ],
        "total_products" => count($products),
        "data" => $products
    ]);

} catch (Exception $e) {

    json_response([
        "status" => false,
        "message" => "Server error",
        "error" => $e->getMessage()
    ]);
}