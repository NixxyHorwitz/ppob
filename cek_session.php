<?php
$timeout = 86400; // 1 hari
ini_set('session.gc_maxlifetime', $timeout);
session_set_cookie_params($timeout);
session_start();

echo "<h3>Hasil Pengecekan Session:</h3>";
echo "Durasi diatur ke: " . ini_get('session.gc_maxlifetime') . " detik";
echo "<br>";
echo "Atau sekitar: " . (ini_get('session.gc_maxlifetime') / 3600) . " jam";

echo "<hr>";
echo "ID Session Anda: " . session_id();
?>