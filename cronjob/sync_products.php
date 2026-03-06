<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/api_handler.php';

$payload = [
    'cmd' => 'prepaid',
    'username' => API_USERNAME,
    'sign' => md5(API_USERNAME . API_KEY . "pricelist")
];

$result = hitVendor('price-list', $payload);

if (isset($result['data'])) {
    foreach ($result['data'] as $product) {
        
        // HITUNG HARGA JUAL +5%
        $price_vendor = (float)$product['price'];
        $price_sell   = ceil($price_vendor + 100); 


        $stmt = $pdo->prepare("INSERT INTO products (sku_code, product_name, category, brand, price_vendor, price_sell, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            product_name = VALUES(product_name), 
            category = VALUES(category),
            brand = VALUES(brand), 
            price_vendor = VALUES(price_vendor), 
            price_sell = VALUES(price_sell), 
            status = VALUES(status)");
        
        $status = ($product['buyer_product_status'] && $product['seller_product_status']) ? 'active' : 'non-active';
        
        $stmt->execute([
            $product['buyer_sku_code'],
            $product['product_name'],
            $product['category'],
            $product['brand'], // Menambahkan data brand dari API Digiflazz
            $product['price'],
            $price_sell,
            $status
        ]);
    }
    echo "Sinkronisasi selesai!";
} else {
    echo "Gagal sinkronisasi: " . ($result['message'] ?? 'Unknown Error');
}