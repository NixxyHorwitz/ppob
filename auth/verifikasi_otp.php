<?php
$timeout = 86400;
ini_set('session.gc_maxlifetime', $timeout);
session_set_cookie_params($timeout);
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: ../index");
    exit();
}
$error = '';

if (isset($_POST['verifikasi'])) {
    $otp_input = $_POST['otp'];
    $userId = $_SESSION['temp_user_id'];

    $stmt = $pdo->prepare("SELECT id, otp_code, otp_expiry FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user) {
        $now = date("Y-m-d H:i:s");
        
        if ($user['otp_code'] === $otp_input && $now <= $user['otp_expiry']) {
            $_SESSION['user_id'] = $user['id'];
            $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE id = ?")->execute([$userId]);
            
            unset($_SESSION['temp_user_id']);
            header("Location: ../dashboard");
            exit();
        } else {
            $error = "Kode OTP salah atau sudah kedaluwarsa.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Verifikasi OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0f172a; color: white; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card-otp { background: #1e293b; border: none; border-radius: 15px; padding: 30px; width: 100%; max-width: 400px; }
        .form-control { background: #0f172a; border: 1px solid #334155; color: white; text-align: center; letter-spacing: 5px; font-weight: bold; }
        .form-control:focus { background: #0f172a; color: white; border-color: #0ea5e9; box-shadow: none; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="card-otp shadow-lg text-center">
        <h4 class="fw-bold text-info">Verifikasi Login</h4>
        <p class="small text-secondary">Masukkan 6 digit kode yang dikirim ke email Anda</p>
        
        <?php if(isset($_GET['resend']) && $_GET['resend'] == 'success'): ?>
            <div class="alert alert-success small py-2 mb-3">
                Kode baru telah dikirim ke email anda!
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger small py-2"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <input type="text" name="otp" class="form-control form-control-lg" maxlength="6" placeholder="000000" required autofocus autocomplete="off">
            </div>
            <button type="submit" name="verifikasi" class="btn btn-info w-100 fw-bold py-2">Verifikasi</button>

            <div class="mt-3">
                <p class="small text-secondary mb-1">Tidak menerima kode?</p>
                <a href="send_otp_mail" class="text-info small text-decoration-none fw-bold" id="btnResend">Kirim Ulang Kode</a>
            </div>
        </form>
        
        <div class="mt-4">
            <a href="auth/logout" class="text-decoration-none small text-muted">Batal dan Keluar</a>
        </div>
    </div>
</div>

</body>
</html>