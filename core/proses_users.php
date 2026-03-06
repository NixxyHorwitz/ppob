<?php
require_once __DIR__ . '/../includes/auth.php';
if ($_SESSION['role'] !== 'admin') { exit; }
require_once __DIR__ . '/../config/database.php';

// Hapus User
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: manage_users");
}

// Tambah / Edit User
if (isset($_POST['save_user'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $saldo = $_POST['saldo'];
    $role = $_POST['role'];

    if (empty($id)) {
        // Create
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password, saldo, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $phone, $password, $saldo, $role]);
    } else {
        // Update
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, phone=?, password=?, saldo=?, role=? WHERE id=?");
            $stmt->execute([$username, $email, $phone, $password, $saldo, $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, phone=?, saldo=?, role=? WHERE id=?");
            $stmt->execute([$username, $email, $phone, $saldo, $role, $id]);
        }
    }
    header("Location: ../modules/admin/manage_users");
}