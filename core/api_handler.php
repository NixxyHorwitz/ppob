<?php

/**
 * core/api_handler.php
 * Fungsi hitVendor() + define API constants dari website_settings
 * Aman dipanggil berkali-kali (guard defined)
 */
if (!defined('API_USERNAME')) {
    if (!isset($pdo)) {
        require_once dirname(__DIR__) . '/config/database.php';
    }

    $stmt = $pdo->query("SELECT api_username, api_key, api_url FROM website_settings LIMIT 1");
    $api  = $stmt->fetch(PDO::FETCH_ASSOC);

    define('API_USERNAME', $api['api_username'] ?? '');
    define('API_KEY',      $api['api_key']      ?? '');
    define('API_URL',      rtrim($api['api_url'] ?? 'https://api.digiflazz.com/v1/', '/') . '/');
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
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            error_log("[hitVendor] cURL error: $err | URL: $url");
            return null;
        }
        curl_close($ch);

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[hitVendor] Invalid JSON: $result | URL: $url");
            return null;
        }

        return $decoded;
    }
}
