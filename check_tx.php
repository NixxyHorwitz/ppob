<?php

/**
 * check_tx.php — taruh di root, akses ?key=debug123
 * Cek apakah transaction.php yang baru sudah ter-deploy
 */
if (($_GET['key'] ?? '') !== 'debug123') die('403');

$txFile = dirname(__FILE__) . '/core/transaction.php';
$ahFile = dirname(__FILE__) . '/core/api_handler.php';

echo '<pre style="background:#111;color:#0f0;padding:20px;font-size:13px">';

// Cek api_handler.php
echo "=== core/api_handler.php ===\n";
if (file_exists($ahFile)) {
    $ah = file_get_contents($ahFile);
    echo "✅ File ada\n";
    echo "Punya hitVendor(array \$data)  : " . (str_contains($ah, 'function hitVendor(array $data)') ? '✅ YA (versi baru)' : '❌ TIDAK') . "\n";
    echo "Punya hitVendor(\$endpoint)    : " . (str_contains($ah, 'string $endpoint') ? '⚠️ YA (versi lama!)' : '✅ tidak ada') . "\n";
    echo "API_URL dengan /transaction   : " . (str_contains($ah, '/transaction') ? '✅ YA' : '❌ TIDAK') . "\n";
} else {
    echo "❌ FILE TIDAK ADA!\n";
}

echo "\n=== core/transaction.php ===\n";
if (file_exists($txFile)) {
    $tx = file_get_contents($txFile);
    echo "✅ File ada\n";
    echo "Punya txLog()                 : " . (str_contains($tx, 'function txLog') ? '✅ YA (versi baru)' : '❌ TIDAK (versi lama!)') . "\n";
    echo "hitVendor tanpa endpoint      : " . (str_contains($tx, 'hitVendor($payload)') ? '✅ YA (versi baru)' : '❌ TIDAK (versi lama!)') . "\n";
    echo "hitVendor dengan endpoint     : " . (str_contains($tx, "hitVendor('transaction'") ? '⚠️ YA (versi lama!)' : '✅ tidak ada') . "\n";
    echo "Punya require api_handler     : " . (str_contains($tx, 'api_handler') ? '✅ YA' : '❌ TIDAK') . "\n";
    echo "\n--- 20 baris pertama ---\n";
    echo htmlspecialchars(implode('', array_slice(file($txFile), 0, 20)));
} else {
    echo "❌ FILE TIDAK ADA!\n";
}

echo "\n=== services.php include order ===\n";
$svFile = dirname(__FILE__) . '/pages/services.php';
if (file_exists($svFile)) {
    $sv = file_get_contents($svFile);
    echo "Include transaction.php : " . (str_contains($sv, 'transaction.php') ? '✅' : '❌') . "\n";
    echo "Include api_handler.php : " . (str_contains($sv, 'api_handler.php') ? '✅' : '❌') . "\n";
    // Tunjukkan urutan include
    preg_match_all('/require_once[^;]+;/', $sv, $m);
    echo "\nUrutan require_once:\n";
    foreach (array_slice($m[0], 0, 6) as $r) echo "  " . htmlspecialchars($r) . "\n";
}
echo '</pre>';
