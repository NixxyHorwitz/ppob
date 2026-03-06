<?php
/**
 * backoffice/includes/guard.php
 */

require_once __DIR__ . '/session.php';

// Logout
if (!empty($_POST['_logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Belum login atau bukan admin
if (empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Timeout 2 jam
if ((time() - ($_SESSION['admin_login_at'] ?? 0)) > 7200) {
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['admin_login_at'] = time();

// Variabel siap pakai
$admin_id   = (int)$_SESSION['admin_id'];
$admin_name = $_SESSION['admin_fullname'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'];