<?php

/**
 * includes/header.php
 * ─────────────────────────────────────────────
 * Include di PALING ATAS setiap halaman.
 *
 * Yang ditangani file ini:
 *  - Session start & auth check (redirect ke index jika belum login)
 *  - Load config/database.php
 *  - Load $cfg (site_settings) & $hero (hero_settings)
 *  - Load $user & $unreadCount
 *  - Output <!DOCTYPE html> ... <head> ... CSS global ... </head> <body>
 *
 * Variabel yang tersedia untuk halaman setelah include:
 *  $pdo, $userId, $isAdmin, $user, $unreadCount, $cfg, $hero,modules/user/inbox $brandName
 *
 * Cara pakai di setiap halaman:
 *  <?php
 *    $pageTitle = 'Nama Halaman'; // opsional, default = $brandName
 *    require_once 'includes/header.php';
 *  ?>
 *  ... konten halaman ...
 *  <?php require_once 'includes/footer.php'; ?>
 */

// ── Session ────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $timeout = 86400;
    ini_set('session.gc_maxlifetime', $timeout);
    session_set_cookie_params($timeout);
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 1) . 'index');
    exit();
}

// ── Database ───────────────────────────────────────────────
if (!isset($pdo)) {
    // Naik ke root dari includes/
    require_once dirname(__DIR__) . '/config/database.php';
}

$userId  = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// ── Settings loader helper ─────────────────────────────────
if (!function_exists('loadSettings')) {
    function loadSettings(PDO $pdo, string $table): array
    {
        try {
            $s = $pdo->query("SELECT setting_key, setting_value FROM $table");
            $r = [];
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $r[$row['setting_key']] = $row['setting_value'];
            }
            return $r;
        } catch (Exception $e) {
            return [];
        }
    }
}

// ── Load global settings ───────────────────────────────────
$cfg  = loadSettings($pdo, 'site_settings');
$hero = loadSettings($pdo, 'hero_settings');
$brandName = $cfg['brand_name'] ?? 'UsahaPPOB';

// ── User data ──────────────────────────────────────────────
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// ── Unread notification count ──────────────────────────────
try {
    if ($isAdmin) {
        $sC = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE is_read=0 AND (user_id=? OR (SELECT role FROM users WHERE id=notifications.user_id)='user')");
    } else {
        $sC = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE is_read=0 AND user_id=?");
    }
    $sC->execute([$userId]);
    $unreadCount = (int)$sC->fetchColumn();
} catch (Exception $e) {
    $unreadCount = 0;
}

// ── Page title ─────────────────────────────────────────────
// Halaman bisa set $pageTitle sebelum include ini
if (!isset($pageTitle)) $pageTitle = $brandName;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <meta name="theme-color" content="<?= htmlspecialchars($cfg['color_primary'] ?? '#01d298') ?>">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars($brandName) ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Icons -->
    <script src="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        /* ═══════════════════════════════════════════════════
   CSS VARIABLES — diambil dari site_settings di DB
   Ubah warna cukup dari tabel site_settings
═══════════════════════════════════════════════════ */
        :root {
            --cp: <?= htmlspecialchars($cfg['color_primary']        ?? $cfg['primary_color']      ?? '#01d298') ?>;
            --cpd: <?= htmlspecialchars($cfg['color_primary_dark']   ?? $cfg['primary_dark_color'] ?? '#00b07e') ?>;
            --cpdd: <?= htmlspecialchars($cfg['color_primary_darker'] ?? '#009068') ?>;
            --cpl: <?= htmlspecialchars($cfg['color_primary_light']  ?? '#e6fff9') ?>;
            --ca: <?= htmlspecialchars($cfg['color_accent']         ?? '#f97316') ?>;
            --ct: <?= htmlspecialchars($cfg['color_text_main']      ?? '#0f172a') ?>;
            --cm: <?= htmlspecialchars($cfg['color_text_muted']     ?? '#64748b') ?>;
            --cbg: <?= htmlspecialchars($cfg['color_bg']             ?? '#f0f4f8') ?>;
            --cc: <?= htmlspecialchars($cfg['color_card']           ?? '#ffffff') ?>;
            --f: 'Plus Jakarta Sans', sans-serif;
            --r: 16px;
            --rsm: 10px;
            --sh: 0 2px 12px rgba(0, 0, 0, .06);
            --shm: 0 6px 28px rgba(0, 0, 0, .10);
        }

        /* ── RESET & BASE ───────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        body {
            font-family: var(--f);
            background: var(--cbg);
            color: var(--ct);
            padding-bottom: 84px;
            /* ruang untuk bottom nav, di-override di 992px oleh footer.php */
            max-width: 600px;
            /* layout mobile-centered */
            margin: 0 auto;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
            -webkit-tap-highlight-color: transparent;
        }

        img {
            display: block;
            max-width: 100%;
        }

        button {
            font-family: var(--f);
            cursor: pointer;
            border: none;
            background: none;
        }

        /* ── UTILITY ────────────────────────────────── */
        .g8 {
            height: 8px;
        }

        .g12 {
            height: 12px;
        }

        .g16 {
            height: 16px;
        }

        .g20 {
            height: 20px;
        }

        /* ── SECTION ────────────────────────────────── */
        .sec {
            padding: 12px 14px 0;
        }

        .sechd {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 9px;
        }

        .sectit {
            font-size: 13px;
            font-weight: 800;
            color: var(--ct);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .sectit i {
            font-size: 11px;
        }

        .secmore {
            font-size: 11px;
            font-weight: 700;
            color: var(--cpd);
        }

        /* ── CARD BASE ──────────────────────────────── */
        .card-base {
            background: var(--cc);
            border-radius: var(--r);
            border: 1px solid rgba(0, 0, 0, .045);
            box-shadow: var(--sh);
        }

        /* ── MAINTENANCE NOTICE ─────────────────────── */
        .mnt {
            margin: 10px 14px 0;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 11px;
            padding: 9px 12px;
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 11px;
            font-weight: 700;
            color: #be123c;
        }

        .mnt i {
            color: #f43f5e;
            font-size: 13px;
        }

        /* ── PAGE-LEVEL STYLES halaman bisa override ── */
        <?php if (isset($extraCSS)) echo $extraCSS; ?>
    </style>
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>

<body>
    <?php if (!empty($cfg['maintenance_notice'])): ?>
        <div class="mnt"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($cfg['maintenance_notice']) ?></div>
    <?php endif; ?>