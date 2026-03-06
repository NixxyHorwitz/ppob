<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/api_handler.php';

$payload = [
    'cmd'      => 'pasca',
    'username' => API_USERNAME,
    'sign'     => md5(API_USERNAME . API_KEY . "pricelist")
];

$result = hitVendor('price-list', $payload);

if (isset($result['data']) && is_array($result['data'])) {
    $count = 0;
    foreach ($result['data'] as $product) {
        if (!is_array($product)) continue;

        $pName = strtoupper($product['product_name'] ?? '');
        $pBrand = strtoupper($product['brand'] ?? '');
        $pCat = strtoupper($product['category'] ?? '');

        $category = 'LAINNYA';
        if (strpos($pName, 'PLN') !== false || strpos($pBrand, 'PLN') !== false) {
            $category = 'PLN';
        } elseif (strpos($pBrand, 'INTERNET') !== false || strpos($pCat, 'INTERNET') !== false || strpos($pName, 'BIZNET') !== false || strpos($pName, 'INDIHOME') !== false) {
            $category = 'INTERNET';
        } elseif (strpos($pBrand, 'PDAM') !== false || strpos($pName, 'PDAM') !== false) {
            $category = 'PDAM';
        } elseif (strpos($pBrand, 'TV') !== false || strpos($pName, 'VISION') !== false || strpos($pName, 'MNC') !== false) {
            $category = 'TV';
        } elseif (strpos($pBrand, 'HP') !== false || strpos($pName, 'POSTPAID') !== false || strpos($pName, 'HALO') !== false) {
            $category = 'HP';
        } elseif (strpos($pBrand, 'BPJS') !== false) {
            $category = 'BPJS';
        } elseif (strpos($pBrand, 'E-MONEY') !== false || strpos($pBrand, 'E-WALET') !== false || strpos($pName, 'OVO') !== false || strpos($pName, 'GOPAY') !== false) {
            $category = 'E-MONEY';
        }

        $brand = str_replace([' PASCABAYAR', ' PASCA'], '', $pBrand);
        
        if ($brand == 'INTERNET' || $brand == 'TV' || $brand == 'HP' || $brand == 'E-MONEY' || empty($brand)) {
            $brand = explode(' ', $pName)[0];
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO products (sku_code, product_name, category, brand, price_vendor, price_sell, status, type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pascabayar') 
                ON DUPLICATE KEY UPDATE 
                product_name = VALUES(product_name), 
                category = VALUES(category),
                brand = VALUES(brand), 
                price_vendor = VALUES(price_vendor), 
                price_sell = VALUES(price_sell), 
                status = VALUES(status),
                type = 'pascabayar'");
            
            $b_status = $product['buyer_product_status'] ?? false;
            $s_status = $product['seller_product_status'] ?? false;
            $status = ($b_status && $s_status) ? 'active' : 'non-active';

            $stmt->execute([
                $product['buyer_sku_code'],
                $product['product_name'],
                $category,
                $brand,
                $product['admin'] ?? 0,
                $product['admin'] ?? 0,
                $status
            ]);
            $count++;
        } catch (\PDOException $e) {
            echo "Error DB: " . $e->getMessage() . "<br>";
        }
    }
    echo "Sinkronisasi Selesai! $count produk diproses.";
} else {
    echo "Gagal: Data API tidak ditemukan.";
}