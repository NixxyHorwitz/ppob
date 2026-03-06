<?php
/**
 * backoffice/includes/session.php
 * Include file ini PERTAMA KALI di setiap halaman backoffice,
 * SEBELUM output HTML apapun.
 *
 * Contoh pemakaian di dashboard.php:
 *   <?php require_once __DIR__ . '/includes/session.php'; ?>
 */

// Pastikan tidak ada output sebelum ini (tidak ada spasi/baris kosong)
if (headers_sent($file, $line)) {
    die("FATAL: Headers already sent in $file on line $line — pastikan tidak ada output sebelum session.php di-include.");
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);

    session_name('ADMINPPOB_SESS'); // konsisten di semua halaman termasuk login.php

    session_start();
}