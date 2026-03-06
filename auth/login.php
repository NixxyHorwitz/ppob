<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$error = "";

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        /*
        ==========================================
        LOGIN BERHASIL → JANGAN LANGSUNG MASUK
        ==========================================
        */

        // simpan data sementara untuk OTP
        $_SESSION['temp_user_id'] = $user['id'];
        $_SESSION['temp_email']   = $user['email'];
        $_SESSION['temp_username']= $user['username'];
        $_SESSION['role']         = $user['role'];

        /*
        ==========================================
        PANGGIL FILE OTP (AUTO KIRIM EMAIL)
        ==========================================
        */
        require_once __DIR__ . '/send_otp_mail.php';
        exit;

    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UsahaPPOB</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .auth-card { max-width: 400px; margin: 50px auto; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-primary { border-radius: 10px; padding: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card auth-card">
        <div class="card-body p-4">
            <h3 class="fw-bold text-primary mb-1">Selamat Datang</h3>
            <p class="text-muted mb-4">Silakan login ke akun Anda.</p>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                <div class="alert alert-success small">Registrasi berhasil! Silakan login.</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger small"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100 mb-3">Masuk</button>
                <p class="text-center small">Belum punya akun? <a href="register">Daftar</a></p>
            </form>
        </div>
    </div>
</div>
</body>
</html>