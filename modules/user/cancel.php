<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit();
}

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    $stmt = $pdo->prepare("UPDATE topup_history SET status = 'failed' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$id, $_SESSION['user_id']]);
}

header("Location: topup?status=canceled");
exit();