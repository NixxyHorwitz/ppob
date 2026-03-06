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
