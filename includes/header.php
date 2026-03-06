<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UsahaPPOB</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- NProgress -->
    <link rel="stylesheet" href="https://unpkg.com/nprogress@0.2.0/nprogress.css">
    <script src="https://unpkg.com/nprogress@0.2.0/nprogress.js"></script>

    <style>
        :root {
            --primary: #01d298;
            --primary-dark: #00b07e;
            --primary-darker: #009068;
            --primary-light: #e6fff9;
            --primary-soft: #f0fdf8;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: 'Nunito', sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
        }

        /* NProgress bar */
        #nprogress .bar {
            background: #01d298 !important;
            height: 3px !important;
        }

        #nprogress .peg {
            box-shadow: 0 0 10px #01d298, 0 0 5px #01d298 !important;
        }

        /* ===================== NAVBAR ===================== */
        .main-navbar {
            background: linear-gradient(135deg, #01d298 0%, #009068 100%);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 990;
            box-shadow: 0 2px 20px rgba(1,210,152,0.3);
        }

        .navbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 24px;
        }

        /* Brand */
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-brand-icon {
            width: 38px; height: 38px;
            background: rgba(255,255,255,0.25);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(5px);
        }

        .nav-brand-icon i { color: white; font-size: 18px; }

        .nav-brand-name {
            font-size: 20px;
            font-weight: 900;
            color: white;
            letter-spacing: -0.8px;
        }

        /* Nav Links */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-link-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.2s;
            font-family: 'Nunito', sans-serif;
        }

        .nav-link-item:hover {
            background: rgba(255,255,255,0.18);
            color: white;
        }

        .nav-link-item i { font-size: 14px; }

        /* CTA Button */
        .nav-cta-btn {
            background: white;
            color: var(--primary) !important;
            font-weight: 800 !important;
            border-radius: 50px !important;
            padding: 9px 22px !important;
            box-shadow: 0 4px 14px rgba(0,0,0,0.12);
            transition: transform 0.15s, box-shadow 0.15s !important;
        }

        .nav-cta-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.15) !important;
            color: var(--primary-dark) !important;
        }

        /* Auth buttons */
        .btn-nav-outline {
            background: transparent;
            color: white !important;
            border: 2px solid rgba(255,255,255,0.55);
            border-radius: 50px !important;
            padding: 7px 20px !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            transition: all 0.2s;
            font-family: 'Nunito', sans-serif;
        }

        .btn-nav-outline:hover {
            background: rgba(255,255,255,0.18);
            border-color: white;
            color: white !important;
        }

        .btn-nav-solid {
            background: white;
            color: var(--primary) !important;
            border: 2px solid white;
            border-radius: 50px !important;
            padding: 7px 20px !important;
            font-weight: 800 !important;
            font-size: 13px !important;
            font-family: 'Nunito', sans-serif;
            transition: all 0.2s;
        }

        .btn-nav-solid:hover {
            background: rgba(255,255,255,0.88);
            color: var(--primary-dark) !important;
        }

        /* User pill */
        .nav-user-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.18);
            border-radius: 50px;
            padding: 6px 14px 6px 6px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .nav-user-pill:hover { background: rgba(255,255,255,0.28); }

        .nav-user-avatar {
            width: 28px; height: 28px;
            background: rgba(255,255,255,0.35);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        .nav-user-avatar i { color: white; font-size: 13px; }

        .nav-user-name {
            font-size: 13px;
            font-weight: 700;
            color: white;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Logout btn */
        .btn-nav-logout {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.12);
            color: white !important;
            border-radius: 50px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.2s;
            font-family: 'Nunito', sans-serif;
        }

        .btn-nav-logout:hover { background: rgba(255,255,255,0.22); }
        .btn-nav-logout i { font-size: 13px; }

        /* Mobile hamburger (only on mobile) */
        .nav-hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 4px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 10px;
            width: 40px; height: 40px;
            align-items: center; justify-content: center;
        }

        .nav-hamburger span {
            display: block;
            width: 20px; height: 2px;
            background: white;
            border-radius: 2px;
            transition: all 0.25s;
        }

        /* Collapsible nav (mobile) */
        .nav-collapse {
            display: none;
            background: rgba(0,0,0,0.15);
            backdrop-filter: blur(5px);
            padding: 12px 20px 20px;
        }

        .nav-collapse.show { display: block; }

        .nav-collapse .nav-link-item {
            display: block;
            padding: 10px 16px;
            margin-bottom: 4px;
        }

        .nav-collapse .nav-cta-btn,
        .nav-collapse .btn-nav-solid,
        .nav-collapse .btn-nav-outline,
        .nav-collapse .btn-nav-logout,
        .nav-collapse .nav-user-pill {
            display: block;
            width: 100%;
            text-align: center;
            margin-bottom: 8px;
        }

        @media (max-width: 991px) {
            .nav-links { display: none; }
            .nav-hamburger { display: flex; }
        }
    </style>
</head>
<body>

<!-- ===================== NAVBAR ===================== -->
<nav class="main-navbar">
    <div class="navbar-inner container-fluid px-3 px-lg-4">
        <!-- Brand -->
        <a href="/" class="nav-brand">
            <div class="nav-brand-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <span class="nav-brand-name">UsahaPPOB</span>
        </a>

        <!-- Desktop Nav Links -->
        <div class="nav-links">
            <?php if (!$user): ?>
                <a href="#product" class="nav-link-item">
                    <i class="fas fa-th-large"></i> Produk
                </a>
                <a href="#promo" class="nav-link-item">
                    <i class="fas fa-percentage"></i> Promo
                </a>
                <a href="#" class="nav-link-item nav-cta-btn">
                    <i class="fas fa-mobile-alt"></i> Download App
                </a>
                <a href="#" class="btn-nav-outline" data-bs-toggle="modal" data-bs-target="#loginModal">
                    Masuk
                </a>
                <a href="#" class="btn-nav-solid" data-bs-toggle="modal" data-bs-target="#registerModal">
                    Daftar
                </a>
            <?php else: ?>
                <a href="#" class="nav-link-item nav-cta-btn">
                    <i class="fas fa-mobile-alt"></i> Download App
                </a>
                <a href="/dashboard.php" class="nav-user-pill">
                    <div class="nav-user-avatar"><i class="fas fa-user"></i></div>
                    <span class="nav-user-name"><?= htmlspecialchars($user['username']) ?></span>
                </a>
                <a href="/logout.php" class="btn-nav-logout">
                    <i class="fas fa-power-off"></i> Keluar
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Hamburger -->
        <button class="nav-hamburger" onclick="toggleMobileNav(this)" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <!-- Mobile Collapse Menu -->
    <div class="nav-collapse" id="mobileNav">
        <?php if (!$user): ?>
            <a href="#product" class="nav-link-item"><i class="fas fa-th-large me-2"></i>Produk</a>
            <a href="#promo"   class="nav-link-item"><i class="fas fa-percentage me-2"></i>Promo</a>
            <a href="#"        class="nav-cta-btn nav-link-item"><i class="fas fa-mobile-alt me-2"></i>Download App</a>
            <a href="#" class="btn-nav-outline text-center py-2 d-block rounded-pill mb-2"
               data-bs-toggle="modal" data-bs-target="#loginModal">Masuk</a>
            <a href="#" class="btn-nav-solid text-center py-2 d-block rounded-pill"
               data-bs-toggle="modal" data-bs-target="#registerModal">Daftar</a>
        <?php else: ?>
            <a href="/dashboard.php" class="nav-link-item"><i class="fas fa-home me-2"></i>Dashboard</a>
            <a href="/logout.php"    class="btn-nav-logout mt-2"><i class="fas fa-power-off me-2"></i>Keluar</a>
        <?php endif; ?>
    </div>
</nav>

<script>
    // NProgress
    NProgress.start();
    window.addEventListener('load', function () { NProgress.done(); });

    // Mobile nav toggle
    function toggleMobileNav(btn) {
        const nav = document.getElementById('mobileNav');
        nav.classList.toggle('show');
        // Animate hamburger
        const spans = btn.querySelectorAll('span');
        if (nav.classList.contains('show')) {
            spans[0].style.transform = 'translateY(7px) rotate(45deg)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'translateY(-7px) rotate(-45deg)';
        } else {
            spans[0].style.transform = '';
            spans[1].style.opacity = '';
            spans[2].style.transform = '';
        }
    }
</script>