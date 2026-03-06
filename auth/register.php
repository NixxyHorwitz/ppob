<?php
session_start();
require_once __DIR__ . '/../config/database.php';

/* =====================================
   GENERATE API KEY
===================================== */
function generateApiKey($length = 48){
    return bin2hex(random_bytes($length / 2));
}

/* =====================================
   GENERATE PIN RANDOM
===================================== */
function generatePin(){
    return str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);
}

$error = '';
$success = '';

/* =====================================
   REGISTER PROCESS
===================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';

    if(!$username || !$fullname || !$email || !$password_raw){
        $error = "Semua field wajib diisi.";
    } else {

        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        // cek duplicate
        $check = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
        $check->execute([$username,$email]);

        if($check->fetch()){
            $error = "Username atau Email sudah digunakan.";
        } else {

            // generate api key unik
            do{
                $api_key = generateApiKey();
                $cekKey = $pdo->prepare("SELECT id FROM users WHERE api_key=?");
                $cekKey->execute([$api_key]);
            } while($cekKey->fetch());

            $pin = generatePin();

            $stmt = $pdo->prepare("
                INSERT INTO users
                (username, fullname, email, password, saldo, api_key, pin, role, is_active, created_at)
                VALUES (?,?,?,?,?,?,?,'user',1,NOW())
            ");

            if($stmt->execute([
                $username,
                $fullname,
                $email,
                $password,
                0,
                $api_key,
                $pin
            ])){

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';

                $success = "Registrasi berhasil! API Key & PIN sudah dibuat otomatis.";
            } else {
                $error = "Terjadi kesalahan sistem.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar Akun</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background: linear-gradient(135deg,#0ea5e9,#2563eb);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:system-ui;
}

.register-card{
    width:420px;
    border-radius:20px;
    backdrop-filter:blur(12px);
    background:rgba(255,255,255,.95);
    box-shadow:0 20px 50px rgba(0,0,0,.2);
    padding:35px;
    animation:fadeIn .5s ease;
}

@keyframes fadeIn{
    from{opacity:0;transform:translateY(20px);}
    to{opacity:1;transform:translateY(0);}
}

.logo-circle{
    width:70px;
    height:70px;
    border-radius:20px;
    background:#e0f2fe;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:auto;
    font-size:28px;
    color:#0284c7;
}

.form-control{
    border-radius:12px;
    padding:12px;
}

.btn-register{
    background:#0ea5e9;
    border:none;
    font-weight:600;
    padding:12px;
    border-radius:12px;
    transition:.3s;
}

.btn-register:hover{
    background:#0284c7;
}

.small-info{
    font-size:13px;
    color:#64748b;
}

</style>
</head>

<body>

<div class="register-card">

    <div class="text-center mb-3">
        <div class="logo-circle">💳</div>
        <h4 class="fw-bold mt-3">Buat Akun Baru</h4>
        <div class="small-info">API Key & PIN akan dibuat otomatis</div>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <script>
            setTimeout(()=>{ window.location='../index.php'; },2000);
        </script>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label class="fw-semibold">Nama Lengkap</label>
            <input type="text" name="fullname" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="fw-semibold">Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-4">
            <label class="fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-register w-100">
            🚀 Daftar Sekarang
        </button>

    </form>

    <div class="text-center mt-4 small-info">
        Sudah punya akun? <a href="../index.php">Login disini</a>
    </div>

</div>

</body>
</html>