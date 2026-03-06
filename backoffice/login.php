<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('ADMINPPOB_SESS'); // harus sama dengan session.php
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Sudah login → langsung masuk
if (!empty($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin') {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, fullname, password, role, is_active FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Username atau password salah.';
            } elseif (!$user['is_active']) {
                $error = 'Akun dinonaktifkan.';
            } elseif ($user['role'] !== 'admin') {
                $error = 'Akun ini tidak memiliki akses admin.';
            } else {
                session_regenerate_id(true);
                $_SESSION['admin_id']       = (int)$user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_fullname'] = $user['fullname'];
                $_SESSION['admin_role']     = $user['role'];
                $_SESSION['admin_login_at'] = time();

                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan sistem, coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — Admin PPOB</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:#0b1120;min-height:100vh;display:flex;align-items:center;justify-content:center;}
    .box{width:100%;max-width:380px;padding:24px;}
    .brand{text-align:center;margin-bottom:32px;}
    .brand-icon{width:48px;height:48px;background:#3b82f6;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin-bottom:12px;}
    .brand h1{font-size:20px;font-weight:700;color:#e2e8f0;}
    .brand p{font-size:13px;color:#4b5e7a;margin-top:4px;}
    .card{background:#111827;border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:28px;}
    .alert{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;border-radius:8px;padding:10px 14px;font-size:13px;display:flex;align-items:center;gap:8px;margin-bottom:20px;}
    .group{margin-bottom:16px;}
    label{display:block;font-size:13px;font-weight:600;color:#94a3b8;margin-bottom:6px;}
    .wrap{position:relative;}
    .ico{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:16px;color:#334155;pointer-events:none;}
    input{width:100%;background:#0d1526;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:11px 12px 11px 38px;font-size:14px;font-family:'Plus Jakarta Sans',sans-serif;color:#e2e8f0;outline:none;transition:border-color .2s;}
    input::placeholder{color:#2d3f57;}
    input:focus{border-color:#3b82f6;background:#0f1a2e;}
    input.err{border-color:#ef4444;}
    .eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#334155;font-size:16px;cursor:pointer;padding:2px;}
    .eye:hover{color:#64748b;}
    button[type=submit]{width:100%;background:#3b82f6;border:none;border-radius:8px;padding:12px;color:#fff;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;margin-top:8px;transition:background .2s;}
    button[type=submit]:hover{background:#2563eb;}
  </style>
</head>
<body>
<div class="box">
  <div class="brand">
    <div class="brand-icon"><i class="ph ph-lightning"></i></div>
    <h1>Admin Panel</h1>
    <p>Masuk untuk melanjutkan</p>
  </div>
  <div class="card">
    <?php if ($error): ?>
      <div class="alert"><i class="ph ph-warning-circle"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <div class="group">
        <label for="username">Username</label>
        <div class="wrap">
          <i class="ph ph-user ico"></i>
          <input type="text" id="username" name="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="Masukkan username" autocomplete="username" autofocus required
                 class="<?= $error ? 'err' : '' ?>"/>
        </div>
      </div>
      <div class="group">
        <label for="password">Password</label>
        <div class="wrap">
          <i class="ph ph-lock ico"></i>
          <input type="password" id="password" name="password"
                 placeholder="Masukkan password" autocomplete="current-password" required
                 class="<?= $error ? 'err' : '' ?>"/>
          <button type="button" class="eye" id="eyeBtn" tabindex="-1">
            <i class="ph ph-eye" id="eyeIco"></i>
          </button>
        </div>
      </div>
      <button type="submit">Masuk</button>
    </form>
  </div>
</div>
<script>
document.getElementById('eyeBtn').addEventListener('click', function() {
    const inp = document.getElementById('password');
    const ico = document.getElementById('eyeIco');
    inp.type      = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'text' ? 'ph ph-eye-slash' : 'ph ph-eye';
});
</script>
</body>
</html>