<?php
define('BASE_URL', 'https://ppob.bersamakita.my.id');

$host = "localhost";
$user = "bersama4_ppob";
$pass = "bersama4_ppob";
$db   = "bersama4_ppob";

try {
    $pdo = new \PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $scheme = 'https';
        }
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $root   = rtrim($scheme . '://' . $host . '/', '/') . '/';
        return $root . ltrim($path, '/');
    }
}
