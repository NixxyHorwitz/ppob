<?php
require_once __DIR__ . '/../config/database.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Query Laporan Gabungan: Transaksi & Profit
$query = "SELECT 
            DATE(created_at) as tgl, 
            COUNT(*) as total_trx,
            SUM(amount) as omset,
            SUM(CASE WHEN status = 'success' THEN (amount * 0.1) ELSE 0 END) as net_profit,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed
          FROM transactions 
          WHERE DATE(created_at) BETWEEN ? AND ?
          GROUP BY DATE(created_at) 
          ORDER BY DATE(created_at) DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$start_date, $end_date]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary untuk box atas
$summary = $pdo->prepare("SELECT 
            COUNT(id) as total_trx,
            SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) as total_omset,
            SUM(CASE WHEN status = 'success' THEN (amount * 0.1) ELSE 0 END) as total_profit
          FROM transactions WHERE DATE(created_at) BETWEEN ? AND ?");
$summary->execute([$start_date, $end_date]);
$sum = $summary->fetch(PDO::FETCH_ASSOC);
?>