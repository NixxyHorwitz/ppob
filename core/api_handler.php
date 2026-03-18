<?php

/**
 * core/api_handler.php
 * Define API constants + fungsi hitVendor()
 * Guard: aman di-include berkali-kali & tidak konflik dengan transaction.php
 */

if (!isset($pdo)) {
    require_once dirname(__DIR__) . '/config/database.php';
}

// Ambil dari DB hanya jika belum ada konstantanya
if (!defined('API_USERNAME')) {
    $_apiRow = $pdo->query("SELECT api_username, api_key, api_url FROM website_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    define('API_USERNAME', $_apiRow['api_username'] ?? '');
    define('API_KEY',      $_apiRow['api_key']      ?? '');
    define('API_URL',      rtrim($_apiRow['api_url'] ?? 'https://api.digiflazz.com/v1/', '/') . '/');
    unset($_apiRow);
}

if (!function_exists('hitVendor')) {
    function hitVendor(string $endpoint, array $data): ?array
    {
        $url     = API_URL . ltrim($endpoint, '/');
        $payload = json_encode($data);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log('[hitVendor] cURL error: ' . curl_error($ch) . ' | URL: ' . $url);
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[hitVendor] Invalid JSON: ' . $result . ' | URL: ' . $url);
            return null;
        }

        return $decoded;
    }
}
