<?php
require_once __DIR__ . '/../config/database.php';

// 1. Total Saldo User
$totalSaldo = $pdo->query("SELECT SUM(saldo) FROM users WHERE role != 'admin'")->fetchColumn();

// 2. Statistik User
$userStats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN role = 'reseller' THEN 1 ELSE 0 END) as total_reseller,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as total_user
FROM users")->fetch();

// 3. Statistik Produk
$prodStats = $pdo->query("SELECT 
    SUM(CASE WHEN type = 'prabayar' THEN 1 ELSE 0 END) as pra,
    SUM(CASE WHEN type = 'pascabayar' THEN 1 ELSE 0 END) as pasca
FROM products")->fetch();

// 4. Kesimpulan Profit & Transaksi (Hari ini, Minggu ini, Bulan ini)
$statsQuery = "SELECT 
    COUNT(*) as total_trx,
    SUM(amount) as total_omset,
    SUM(CASE WHEN status = 'success' THEN (amount * 0.1) ELSE 0 END) as profit 
    FROM transactions WHERE status = 'success' AND ";

$profitToday = $pdo->query($statsQuery . "DATE(created_at) = CURDATE()")->fetch();
$profitWeek  = $pdo->query($statsQuery . "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)")->fetch();
$profitMonth = $pdo->query($statsQuery . "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch();

// 5. Data untuk Grafik (7 Hari Terakhir)
$chartData = $pdo->query("SELECT DATE(created_at) as tgl, COUNT(*) as jml 
    FROM transactions 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
    
// 7. Log Topup Terbaru (Menggunakan tabel topup_history)
$recentDeposits = $pdo->query("SELECT u.username, t.amount, t.status, t.created_at 
    FROM topup_history t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 8. Produk Terlaris
$topProducts = $pdo->query("SELECT p.product_name, t.sku_code, COUNT(t.id) as terjual 
    FROM transactions t 
    LEFT JOIN products p ON t.sku_code = p.sku_code 
    WHERE t.status = 'success' 
    GROUP BY t.sku_code 
    ORDER BY terjual DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>