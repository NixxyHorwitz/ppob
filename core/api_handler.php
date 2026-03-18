<?php

/**
 * core/api_handler.php
 * Koneksi ke Digiflazz API
 * Kredensial dari tabel website_settings
 */

if (!isset($pdo)) {
    require_once dirname(__DIR__) . '/config/database.php';
}

if (!defined('API_USERNAME')) {
    $_apiRow = $pdo->query("SELECT api_username, api_key, api_url FROM website_settings LIMIT 1")
        ->fetch(PDO::FETCH_ASSOC);
    define('API_USERNAME', $_apiRow['api_username'] ?? '');
    define('API_KEY',      $_apiRow['api_key']      ?? '');
    // Simpan full URL transaction endpoint — sama persis seperti yang dipakai di test file
    define('API_URL', rtrim($_apiRow['api_url'] ?? 'https://api.digiflazz.com/v1', '/') . '/transaction');
    unset($_apiRow);
}

if (!function_exists('hitVendor')) {
    function hitVendor(array $data): ?array
    {
        $ch = curl_init(API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log('[hitVendor] cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[hitVendor] Invalid JSON: ' . $result);
            return null;
        }

        return $decoded;
    }
}
