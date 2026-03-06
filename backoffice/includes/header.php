<?php

/**
 * backoffice/includes/header.php
 * Cek session + handle logout + output HTML sidebar/topbar
 *
 * PENTING: Setiap halaman yang meng-include file ini harus
 * sudah meng-include session.php terlebih dahulu di baris pertama.
 */

// Pastikan session sudah berjalan (fallback jika ada halaman yang lupa include session.php)
if (session_status() === PHP_SESSION_NONE) {
  require_once __DIR__ . '/session.php';
}

// Cek login — belum login langsung tendang ke login.php
if (empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
  header('Location: login.php');
  exit;
}

// Handle logout dari tombol mana pun (sidebar / topbar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_logout'])) {
  session_unset();
  session_destroy();
  header('Location: login.php');
  exit;
}

// Shortcut variabel unt uk dipavisikai di view
$admin_id       = (int)$_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'] ?? '';
$admin_name     = $_SESSION['admin_fullname']  ?? $admin_username;
$admin_role     = $_SESSION['admin_role']      ?? 'admin';

$page_title  = $page_title  ?? 'Dashboard';
$active_menu = $active_menu ?? '';

$_menus = [
  ['section' => 'Main', 'items' => [
    ['key' => 'dashboard',  'label' => 'Dashboard',   'icon' => 'ph-squares-four',         'url' => 'dashboard.php'],
    ['key' => 'analytics',  'label' => 'Analytics',   'icon' => 'ph-chart-line-up',        'url' => 'analytics.php'],
  ]],
  ['section' => 'CMS', 'items' => [
    ['key' => 'promo_banners',  'label' => 'Banners',   'icon' => 'ph-slideshow',         'url' => 'promo_banners'],
    ['key' => 'hero_banners',  'label' => 'Hero Banner',   'icon' => 'ph-textbox',         'url' => 'hero_banners'],
    ['key' => 'frontend',  'label' => 'Frontend',   'icon' => 'ph-app-window',        'url' => 'frontend'],
    ['key' => 'media',  'label' => 'Media', 'icon' => 'ph-folder-open',        'url' => 'media'],
    ['key' => 'menus',  'label' => 'Menus', 'icon' => 'ph-list',        'url' => 'menu'],
  ]],
  ['section' => 'Management', 'items' => [
    ['key' => 'users',      'label' => 'Users',       'icon' => 'ph-users-three',          'url' => 'users.php'],
    ['key' => 'transaksi',  'label' => 'Transaksi',   'icon' => 'ph-swap',                 'url' => 'transactions'],
    ['key' => 'bank',      'label' => 'Bank',       'icon' => 'ph-bank',      'url' => 'bank'],
    ['key' => 'topups',      'label' => 'Topup',       'icon' => 'ph-currency-dollar',      'url' => 'topups'],
    ['key' => 'products',     'label' => 'Produk PPOB', 'icon' => 'ph-storefront',           'url' => 'products'],
    ['key' => 'notifications', 'label' => 'Notifications',  'icon' => 'ph-bell',                 'url' => 'notifications'],
  ]],
  ['section' => 'System', 'items' => [
    ['key' => 'settings',   'label' => 'Settings',    'icon' => 'ph-gear-six',             'url' => 'settings.php'],
    ['key' => 'logs',       'label' => 'Logs',        'icon' => 'ph-list-magnifying-glass', 'url' => 'logs.php'],
  ]],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?> — Admin PPOB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg: #090d18;
      --surface: #0f1623;
      --card: #131d30;
      --hover: #192035;
      --border: rgba(255, 255, 255, .06);
      --ba: rgba(59, 130, 246, .4);
      --accent: #3b82f6;
      --as: rgba(59, 130, 246, .15);
      --ag: rgba(59, 130, 246, .3);
      --a2: #06b6d4;
      --ok: #10b981;
      --oks: rgba(16, 185, 129, .12);
      --warn: #f59e0b;
      --ws: rgba(245, 158, 11, .12);
      --err: #ef4444;
      --es: rgba(239, 68, 68, .12);
      --pur: #a855f7;
      --ps: rgba(168, 85, 247, .12);
      --text: #e2e8f0;
      --mut: #4b5e7a;
      --sub: #7a90b0;
      --sw: 258px;
      --hh: 62px;
      --r: 12px;
      --rs: 8px;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      font-size: 14px;
    }

    ::-webkit-scrollbar {
      width: 5px
    }

    ::-webkit-scrollbar-thumb {
      background: var(--border);
      border-radius: 99px
    }

    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: var(--sw);
      height: 100vh;
      background: var(--surface);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      z-index: 1040;
      transition: transform .3s;
    }

    .sb-brand {
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 0 20px;
      height: var(--hh);
      border-bottom: 1px solid var(--border);
      text-decoration: none;
      flex-shrink: 0;
    }

    .sb-logo {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, var(--accent), var(--a2));
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 17px;
      color: #fff;
      box-shadow: 0 4px 18px var(--ag);
    }

    .sb-name {
      font-size: 16px;
      font-weight: 700;
      color: var(--text);
    }

    .sb-name span {
      color: var(--accent);
    }

    .sb-scroll {
      flex: 1;
      overflow-y: auto;
      padding: 16px 12px;
    }

    .sb-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      color: var(--mut);
      padding: 8px 10px 4px;
      margin-top: 4px;
    }

    .sb-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border-radius: var(--rs);
      color: var(--sub);
      text-decoration: none;
      font-size: 13.5px;
      font-weight: 500;
      transition: all .18s;
      margin-bottom: 2px;
    }

    .sb-link i {
      font-size: 18px;
      flex-shrink: 0;
    }

    .sb-link:hover {
      background: var(--hover);
      color: var(--text);
    }

    .sb-link.active {
      background: var(--as);
      color: var(--accent);
      border: 1px solid var(--ba);
    }

    .sb-user {
      padding: 14px 16px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }

    .sb-ava {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      border: 2px solid var(--accent);
      object-fit: cover;
    }

    .sb-uname {
      font-size: 13px;
      font-weight: 600;
      color: var(--text);
      line-height: 1.2;
    }

    .sb-urole {
      font-size: 11px;
      color: var(--mut);
    }

    .sb-logout {
      margin-left: auto;
      color: var(--mut);
      font-size: 18px;
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      border-radius: 6px;
      transition: color .2s;
    }

    .sb-logout:hover {
      color: var(--err);
    }

    .topbar {
      position: fixed;
      top: 0;
      left: var(--sw);
      right: 0;
      height: var(--hh);
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      padding: 0 24px;
      gap: 12px;
      z-index: 1030;
    }

    .tb-title {
      font-size: 16px;
      font-weight: 700;
      margin: 0;
      color: var(--text);
    }

    .tb-btn {
      width: 36px;
      height: 36px;
      border-radius: 9px;
      background: var(--card);
      border: 1px solid var(--border);
      color: var(--sub);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      cursor: pointer;
      text-decoration: none;
      transition: all .2s;
    }

    .tb-btn:hover {
      background: var(--hover);
      color: var(--text);
      border-color: var(--ba);
    }

    .tb-sep {
      width: 1px;
      height: 28px;
      background: var(--border);
      margin: 0 4px;
    }

    .main-content {
      margin-left: var(--sw);
      padding-top: var(--hh);
      min-height: 100vh;
    }

    .content-wrap {
      padding: 28px;
    }

    .bd {
      font-size: 11px;
      font-weight: 600;
      padding: 3px 9px;
      border-radius: 99px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .bd-ok {
      background: var(--oks);
      color: var(--ok);
    }

    .bd-err {
      background: var(--es);
      color: var(--err);
    }

    .bd-acc {
      background: var(--as);
      color: var(--accent);
    }

    .bd-warn {
      background: var(--ws);
      color: var(--warn);
    }

    .bd-pur {
      background: var(--ps);
      color: var(--pur);
    }

    .card-c {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--r);
    }

    .ch {
      padding: 18px 20px 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .ct {
      font-size: 14px;
      font-weight: 600;
      color: var(--text);
      margin: 0;
    }

    .cs {
      font-size: 12px;
      color: var(--mut);
      margin-top: 2px;
    }

    .cb {
      padding: 20px;
    }

    .sc {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--r);
      padding: 20px;
      position: relative;
      overflow: hidden;
      transition: transform .2s, border-color .2s;
    }

    .sc:hover {
      transform: translateY(-2px);
      border-color: rgba(255, 255, 255, .12);
    }

    .sc::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 80px;
      height: 80px;
      border-radius: 0 var(--r) 0 80px;
      opacity: .07;
    }

    .sc.blue::before {
      background: var(--accent);
    }

    .sc.green::before {
      background: var(--ok);
    }

    .sc.orange::before {
      background: var(--warn);
    }

    .sc.purple::before {
      background: var(--pur);
    }

    .si {
      width: 44px;
      height: 44px;
      border-radius: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-bottom: 14px;
    }

    .si.blue {
      background: var(--as);
      color: var(--accent);
    }

    .si.green {
      background: var(--oks);
      color: var(--ok);
    }

    .si.orange {
      background: var(--ws);
      color: var(--warn);
    }

    .si.purple {
      background: var(--ps);
      color: var(--pur);
    }

    .sv {
      font-size: 26px;
      font-weight: 700;
      font-family: 'JetBrains Mono', monospace;
      line-height: 1;
      margin-bottom: 4px;
    }

    .sl {
      font-size: 12px;
      color: var(--mut);
      font-weight: 500;
    }

    .tbl {
      color: var(--text);
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    .tbl thead th {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .8px;
      color: var(--mut);
      padding: 10px 14px;
      border-bottom: 1px solid var(--border);
    }

    .tbl tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background .15s;
    }

    .tbl tbody tr:last-child {
      border-bottom: none;
    }

    .tbl tbody tr:hover {
      background: var(--hover);
    }

    .tbl tbody td {
      padding: 12px 14px;
      font-size: 13.5px;
      vertical-align: middle;
    }

    .fc {
      width: 100%;
      background: var(--hover) !important;
      border: 1px solid var(--border) !important;
      color: var(--text) !important;
      border-radius: var(--rs) !important;
      padding: 10px 13px;
      font-size: 13.5px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      transition: border-color .2s;
      outline: none;
    }

    .fc:focus {
      border-color: var(--accent) !important;
      box-shadow: 0 0 0 3px var(--ag) !important;
    }

    .fc::placeholder {
      color: var(--mut);
    }

    .fc option {
      background: var(--card);
    }

    .fl {
      display: block;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .5px;
      color: var(--sub);
      margin-bottom: 6px;
    }

    .fi {
      background: var(--hover);
      border: 1px solid var(--border);
      color: var(--text);
      border-radius: var(--rs);
      padding: 8px 14px 8px 36px;
      font-size: 13px;
      outline: none;
      transition: all .2s;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .fi:focus {
      border-color: var(--accent);
    }

    .fi::placeholder {
      color: var(--mut);
    }

    .fs {
      background: var(--hover);
      border: 1px solid var(--border);
      color: var(--text);
      border-radius: var(--rs);
      padding: 8px 12px;
      font-size: 13px;
      outline: none;
      font-family: 'Plus Jakarta Sans', sans-serif;
      cursor: pointer;
    }

    .fs option {
      background: var(--card);
    }

    .ab {
      width: 28px;
      height: 28px;
      border-radius: 7px;
      background: var(--card);
      border: 1px solid var(--border);
      color: var(--sub);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      cursor: pointer;
      text-decoration: none;
      transition: all .2s;
    }

    .ab:hover {
      background: var(--hover);
      color: var(--text);
      border-color: var(--ba);
    }

    .ab.red {
      color: var(--err);
    }

    .ab.red:hover {
      background: var(--es);
      border-color: rgba(239, 68, 68, .3);
    }

    .ab.green {
      color: var(--ok);
    }

    .ab.green:hover {
      background: var(--oks);
      border-color: rgba(16, 185, 129, .3);
    }

    .pg {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 32px;
      height: 32px;
      padding: 0 8px;
      border-radius: 7px;
      background: var(--hover);
      border: 1px solid var(--border);
      color: var(--sub);
      font-size: 13px;
      text-decoration: none;
      transition: all .15s;
    }

    .pg:hover {
      background: var(--card);
      color: var(--text);
      border-color: var(--ba);
    }

    .pg.active {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
    }

    .pg.dis {
      opacity: .4;
      pointer-events: none;
    }

    .toast-wrap {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .toast-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 18px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 500;
      min-width: 240px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, .4);
      animation: toastIn .3s ease both;
    }

    .toast-ok {
      background: var(--ok);
      color: #fff;
    }

    .toast-err {
      background: var(--err);
      color: #fff;
    }

    @keyframes toastIn {
      from {
        opacity: 0;
        transform: translateX(20px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .mc {
      background: var(--card) !important;
      border: 1px solid var(--border) !important;
      color: var(--text);
    }

    .mh,
    .mf {
      border-color: var(--border) !important;
    }

    .page-header {
      margin-bottom: 24px;
    }

    .page-header h1 {
      font-size: 22px;
      font-weight: 700;
      margin: 0 0 4px;
    }

    .bc {
      margin: 0;
      font-size: 12px;
    }

    .bc .breadcrumb-item a {
      color: var(--mut);
      text-decoration: none;
    }

    .bc .breadcrumb-item.active {
      color: var(--sub);
    }

    .bc .breadcrumb-item+.breadcrumb-item::before {
      color: var(--mut);
    }

    .sb-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .6);
      z-index: 1039;
    }

    .sb-overlay.show {
      display: block;
    }

    .sb-toggle {
      display: none;
      width: 36px;
      height: 36px;
      border-radius: 9px;
      background: var(--card);
      border: 1px solid var(--border);
      color: var(--sub);
      align-items: center;
      justify-content: center;
      font-size: 20px;
      cursor: pointer;
    }

    @media(max-width:991.98px) {
      .sidebar {
        transform: translateX(calc(-1 * var(--sw)));
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
      }

      .topbar {
        left: 0;
      }

      .sb-toggle {
        display: flex;
      }
    }

    @media(max-width:575.98px) {
      .content-wrap {
        padding: 16px;
      }
    }
  </style>
</head>

<body>

  <div class="sb-overlay" id="sbOverlay"></div>

  <aside class="sidebar" id="sidebar">
    <a href="dashboard.php" class="sb-brand">
      <div class="sb-logo"><i class="ph ph-lightning"></i></div>
      <span class="sb-name">Admin<span>PPOB</span></span>
    </a>
    <div class="sb-scroll">
      <?php foreach ($_menus as $_s): ?>
        <div class="sb-label"><?= $_s['section'] ?></div>
        <?php foreach ($_s['items'] as $_item): ?>
          <a href="<?= $_item['url'] ?>" class="sb-link <?= $active_menu === $_item['key'] ? 'active' : '' ?>">
            <i class="ph <?= $_item['icon'] ?>"></i> <?= $_item['label'] ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
    <div class="sb-user">
      <img src="https://ui-avatars.com/api/?name=<?= urlencode($admin_name) ?>&background=3b82f6&color=fff&size=72" class="sb-ava" alt="" />
      <div>
        <div class="sb-uname"><?= htmlspecialchars($admin_name) ?></div>
        <div class="sb-urole"><?= ucfirst($admin_role) ?></div>
      </div>
      <form method="POST" style="margin-left:auto">
        <input type="hidden" name="_logout" value="1" />
        <button type="submit" class="sb-logout" title="Logout"><i class="ph ph-sign-out"></i></button>
      </form>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <button class="sb-toggle" id="sbToggle"><i class="ph ph-list"></i></button>
      <h6 class="tb-title"><?= htmlspecialchars($page_title) ?></h6>
      <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
        <span style="font-size:12px;color:var(--mut)"><?= htmlspecialchars($admin_name) ?></span>
        <span class="bd bd-acc"><?= $admin_role ?></span>
        <div class="tb-sep"></div>
        <form method="POST">
          <input type="hidden" name="_logout" value="1" />
          <button type="submit" class="tb-btn" title="Logout"><i class="ph ph-sign-out"></i></button>
        </form>
      </div>
    </header>
    <div class="content-wrap">