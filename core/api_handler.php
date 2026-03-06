<?php
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("SELECT api_username, api_key, api_url FROM website_settings LIMIT 1");
$api = $stmt->fetch(PDO::FETCH_ASSOC);

define('API_USERNAME', $api['api_username']);
define('API_KEY', $api['api_key']);
define('API_URL', $api['api_url']);

function hitVendor($endpoint, $data) {
    $url = API_URL . $endpoint;
    $payload = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . API_KEY
    ]);

    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        error_log("[" . date('Y-m-d H:i:s') . "] cURL Error: $error_msg | URL: $url\n", 3, __DIR__ . '/../logs/error_api.log');
        curl_close($ch);
        return ['status' => 'error', 'message' => 'Koneksi ke server gagal.'];
    }

    curl_close($ch);
    $decoded = json_decode($result, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[" . date('Y-m-d H:i:s') . "] Invalid JSON: $result | URL: $url\n", 3, __DIR__ . '/../logs/error_api.log');
        return ['status' => 'error', 'message' => 'Respon vendor tidak valid.'];
    }

    return $decoded;
}