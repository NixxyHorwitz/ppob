<?php

/**
 * dashboard.php
 * Cukup set $pageTitle → require header → konten → require footer.
 * Semua auth, session, DB, CSS global, navbar, footer sudah di-handle
 * oleh includes/header.php dan includes/footer.php
 */

$pageTitle = 'Dashboard';
require_once 'includes/header.php';

// ── Data khusus halaman ini ───────────────────────────────────
$menusMain  = $pdo->query("SELECT * FROM dashboard_menus WHERE is_active=1 AND show_on_main=1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
$menusOther = $pdo->query("SELECT * FROM dashboard_menus WHERE is_active=1 AND show_on_main=0 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
$banners    = $pdo->query("SELECT * FROM promo_banners WHERE is_active=1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
$quickActs  = $pdo->query("SELECT * FROM quick_actions WHERE is_active=1 ORDER BY sort_order LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

$sN = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 3");
$sN->execute([$userId]);
$feedNotifs = $sN->fetchAll(PDO::FETCH_ASSOC);

try {
    $sTrx = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
    $sTrx->execute([$userId]);
    $trxCount = (int)$sTrx->fetchColumn();
} catch (Exception $e) {
    $trxCount = 0;
}

try {
    $runningTexts = $pdo->query("SELECT * FROM running_text WHERE is_active=1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $runningTexts = [];
}

try {
    $heroBanners = $pdo->query("SELECT * FROM hero_banner WHERE is_active=1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $heroBanners = [];
}

// ── Menu JSON untuk JS renderer ───────────────────────────────
$menuMainClean  = array_values(array_filter($menusMain, fn($m) => $m['name'] !== 'View All'));
$menuOtherClean = array_values($menusOther);
$hasOther       = count($menuOtherClean) > 0;

$jsMain  = json_encode(array_map(fn($m) => [
    'href'  => $m['href'],
    'bg'   => $m['icon_bg_color'],
    'type'  => $m['icon_type'],
    'icon' => $m['icon_value'],
    'color' => $m['icon_color'],
    'name' => $m['name'],
], $menuMainClean));

$jsOther = json_encode(array_map(fn($m) => [
    'href'  => $m['href'],
    'bg'   => $m['icon_bg_color'],
    'type'  => $m['icon_type'],
    'icon' => $m['icon_value'],
    'color' => $m['icon_color'],
    'name' => $m['name'],
], $menuOtherClean));

// ── Helpers ───────────────────────────────────────────────────
function notifMeta(string $t): array
{
    $t = mb_strtolower($t);
    if (str_contains($t, 'berhasil') || str_contains($t, 'sukses')) return ['fas fa-circle-check', '#22c55e', '#f0fdf4'];
    if (str_contains($t, 'gagal'))     return ['fas fa-circle-xmark', '#ef4444', '#fff1f2'];
    if (str_contains($t, 'ditolak'))   return ['fas fa-ban',          '#ef4444', '#fff1f2'];
    if (str_contains($t, 'topup'))     return ['fas fa-wallet',       '#01d298', '#e6fff9'];
    if (str_contains($t, 'transaksi')) return ['fas fa-receipt',      '#8b5cf6', '#fdf4ff'];
    if (str_contains($t, 'promo'))     return ['fas fa-tag',          '#f97316', '#fff7ed'];
    return ['fas fa-bell', '#01d298', '#e6fff9'];
}
function timeAgo(string $ts): string
{
    $d = time() - strtotime($ts);
    if ($d < 60)      return 'Baru';
    if ($d < 3600)    return (int)($d / 60) . 'm';
    if ($d < 86400)   return (int)($d / 3600) . 'j';
    if ($d < 2592000) return (int)($d / 86400) . 'h';
    return date('d/m', strtotime($ts));
}
?>

<style>
    /* ══ HERO ═══════════════════════════════════════════════════ */
    .hero {
        <?php if (!empty($hero['hero_bg_image'])): ?>background: url('<?= htmlspecialchars($hero['hero_bg_image']) ?>') center/cover no-repeat;
        <?php else: ?>background: linear-gradient(<?= (int)($hero['hero_gradient_angle'] ?? 145) ?>deg,
                var(--cpdd) 0%, var(--cpd) 40%, var(--cp) 80%, #02f0b0 100%);
        <?php endif; ?>padding: 28px 18px 54px;
        position: relative;
        overflow: hidden;
    }

    .hero-ov {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, <?= number_format((float)($hero['hero_overlay_opacity'] ?? 0), 1) ?>);
        pointer-events: none
    }

    .hero::before,
    .hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none
    }

    .hero::before {
        width: 240px;
        height: 240px;
        background: rgba(255, 255, 255, .07);
        top: -80px;
        right: -60px
    }

    .hero::after {
        width: 130px;
        height: 130px;
        background: rgba(255, 255, 255, .05);
        bottom: 20px;
        left: -40px
    }

    .hero-deco {
        position: absolute;
        <?= ($hero['hero_decoration_pos'] ?? 'right') === 'left' ? 'left:10px' : 'right:10px' ?>;
        bottom: 0;
        width: <?= (int)($hero['hero_decoration_width'] ?? 120) ?>px;
        pointer-events: none;
        z-index: 1;
    }

    .hero-in {
        position: relative;
        z-index: 2
    }

    .hbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 8px
    }

    .b-ico {
        width: 32px;
        height: 32px;
        background: rgba(255, 255, 255, .20);
        border: 1px solid rgba(255, 255, 255, .30);
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center
    }

    .b-ico i,
    .b-ico img {
        color: #fff;
        font-size: 14px
    }

    .b-name {
        color: #fff;
        font-size: 16px;
        font-weight: 900;
        letter-spacing: -.2px
    }

    .hbtns {
        display: flex;
        gap: 6px
    }

    .hbtn {
        width: 34px;
        height: 34px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .26);
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        position: relative;
        transition: background .2s
    }

    .hbtn:active {
        background: rgba(255, 255, 255, .30)
    }

    .nbadge {
        position: absolute;
        top: -3px;
        right: -3px;
        background: #ff3b5c;
        color: #fff;
        font-size: 8px;
        font-weight: 900;
        min-width: 15px;
        height: 15px;
        border-radius: 99px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1.5px solid rgba(0, 150, 110, .5)
    }

    .bal-wrap {
        margin-bottom: 16px
    }

    .bal-greet {
        color: rgba(255, 255, 255, .78);
        font-size: 12px;
        font-weight: 500;
        margin-bottom: 1px
    }

    .bal-greet strong {
        color: #fff;
        font-weight: 800
    }

    .bal-lbl {
        color: rgba(255, 255, 255, .68);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        margin-bottom: 5px
    }

    .bal-row {
        display: flex;
        align-items: center;
        gap: 8px
    }

    .bal-num {
        color: #fff;
        font-size: 27px;
        font-weight: 900;
        letter-spacing: -1px;
        line-height: 1
    }

    .bal-dots {
        color: rgba(255, 255, 255, .82);
        font-size: 20px;
        letter-spacing: 5px;
        line-height: 1
    }

    .bal-tog {
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .26);
        border-radius: 8px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px;
        cursor: pointer;
        transition: background .2s
    }

    .bal-tog:active {
        background: rgba(255, 255, 255, .30)
    }

    .qa-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px
    }

    .qa-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px
    }

    .qa-ico {
        width: 44px;
        height: 44px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .24);
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        transition: transform .15s, background .15s
    }

    .qa-item:active .qa-ico {
        transform: scale(.88);
        background: rgba(255, 255, 255, .30)
    }

    .qa-lbl {
        color: rgba(255, 255, 255, .86);
        font-size: 9.5px;
        font-weight: 700;
        text-align: center
    }

    /* ══ MENU CARD ══════════════════════════════════════════════ */
    .fw {
        padding: 0 14px;
        position: relative;
        z-index: 10
    }

    .fw-overlap {
        margin-top: -40px;
    }

    /* pakai kalau TIDAK ada hero_banner */
    .fw-normal {
        margin-top: 10px;
    }

    /* pakai kalau ada hero_banner */
    .mcard {
        background: var(--cc);
        border-radius: 20px;
        padding: 14px 10px 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, .11)
    }

    .mgrid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2px
    }

    .mi {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        padding: 8px 2px;
        border-radius: 11px;
        cursor: pointer;
        transition: background .15s, transform .15s;
        -webkit-tap-highlight-color: transparent
    }

    .mi:active {
        background: var(--cbg);
        transform: scale(.93)
    }

    .mi-ico {
        width: 44px;
        height: 44px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center
    }

    .mi-ico img {
        width: 26px;
        height: 26px;
        object-fit: contain
    }

    .mi-ico i {
        font-size: 19px
    }

    .mi-lbl {
        font-size: 9.5px;
        font-weight: 700;
        color: var(--ct);
        text-align: center;
        line-height: 1.2
    }

    .mdiv {
        grid-column: 1/-1;
        height: 1px;
        background: #f1f5f9;
        margin: 4px 0
    }

    /* ══ RUNNING TEXT ═══════════════════════════════════════════ */
    .rticker {
        overflow: hidden;
        border-top: 1px solid;
        border-bottom: 1px solid;
        height: 34px;
        display: flex;
        align-items: center;
        margin-top: 10px
    }

    .rticker-in {
        display: flex;
        align-items: center;
        white-space: nowrap;
        animation: ticker linear infinite;
        will-change: transform
    }

    .rticker-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 28px;
        font-size: 11.5px;
        font-weight: 700
    }

    .rticker-item i {
        font-size: 12px;
        flex-shrink: 0
    }

    @keyframes ticker {
        0% {
            transform: translateX(0)
        }

        100% {
            transform: translateX(-50%)
        }
    }

    /* ══ SEARCH ═════════════════════════════════════════════════ */
    .swrap {
        padding: 10px 14px 0
    }

    .sbar {
        background: var(--cc);
        border-radius: 12px;
        padding: 10px 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1.5px solid #e8edf2;
        cursor: pointer;
        transition: border-color .2s
    }

    .sbar:active {
        border-color: var(--cp)
    }

    .sbar i {
        color: #94a3b8;
        font-size: 13px
    }

    .sbar span {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 600;
        flex: 1
    }

    .sbar .sk {
        background: #f1f5f9;
        padding: 1px 7px;
        border-radius: 5px;
        font-size: 9.5px;
        font-weight: 700;
        color: #94a3b8
    }

    /* ══ BANNERS ════════════════════════════════════════════════ */
    .bscroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        margin: 0 -14px;
        padding: 0 14px 4px
    }

    .bscroll::-webkit-scrollbar {
        display: none
    }

    .btrack {
        display: flex;
        gap: 10px;
        width: max-content
    }

    .bcard {
        width: calc(min(100vw, 480px)*.88);
        max-width: 390px;
        border-radius: 16px;
        overflow: hidden;
        flex-shrink: 0;
        position: relative;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .12);
        transition: transform .2s
    }

    .bcard:active {
        transform: scale(.97)
    }

    .bimg {
        width: 100%;
        height: 128px;
        object-fit: cover;
        display: block
    }

    .bcap {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 26px 13px 11px;
        background: linear-gradient(transparent, rgba(0, 0, 0, .55))
    }

    .bc-tag {
        display: inline-block;
        background: rgba(255, 255, 255, .22);
        color: #fff;
        font-size: 8px;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 20px;
        margin-bottom: 3px
    }

    .bc-title {
        color: #fff;
        font-size: 12.5px;
        font-weight: 900;
        line-height: 1.3
    }

    .bc-sub {
        color: rgba(255, 255, 255, .80);
        font-size: 9.5px;
        font-weight: 600;
        margin-top: 2px
    }

    .bgrad {
        height: 128px;
        padding: 13px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        position: relative;
        overflow: hidden
    }

    .bgrad::before {
        content: '';
        position: absolute;
        width: 110px;
        height: 110px;
        background: rgba(255, 255, 255, .10);
        border-radius: 50%;
        top: -35px;
        right: -25px
    }

    .bgrad::after {
        content: '';
        position: absolute;
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, .07);
        border-radius: 50%;
        bottom: -18px;
        left: 8px
    }

    .bg-tx {
        flex: 1;
        z-index: 1
    }

    .bg-tag {
        display: inline-block;
        background: rgba(255, 255, 255, .22);
        color: #fff;
        font-size: 8px;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 20px;
        margin-bottom: 5px
    }

    .bg-tit {
        color: #fff;
        font-size: 13.5px;
        font-weight: 900;
        line-height: 1.3
    }

    .bg-sub {
        color: rgba(255, 255, 255, .80);
        font-size: 9.5px;
        font-weight: 600;
        margin-top: 3px
    }

    .bg-emo {
        font-size: 36px;
        z-index: 1;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, .15));
        align-self: center
    }

    /* ══ STATS ══════════════════════════════════════════════════ */
    .stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 9px
    }

    .sc {
        background: var(--cc);
        border-radius: 13px;
        padding: 12px 13px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, .05);
        border: 1px solid rgba(0, 0, 0, .04)
    }

    .sc-ico {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0
    }

    .sc-lbl {
        font-size: 9.5px;
        color: var(--cm);
        font-weight: 600;
        margin-bottom: 2px
    }

    .sc-val {
        font-size: 13.5px;
        font-weight: 900;
        color: var(--ct);
        letter-spacing: -.3px
    }

    /* ══ NOTIF FEED ═════════════════════════════════════════════ */
    .ncard {
        background: var(--cc);
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, .05);
        box-shadow: 0 1px 6px rgba(0, 0, 0, .05)
    }

    .nr {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 13px;
        border-bottom: 1px solid #f4f6f9;
        transition: background .15s
    }

    .nr:last-of-type {
        border-bottom: none
    }

    .nr:active {
        background: #f8fafc
    }

    .n-ico {
        width: 36px;
        height: 36px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0
    }

    .n-body {
        flex: 1;
        min-width: 0
    }

    .n-tit {
        font-size: 12px;
        font-weight: 700;
        color: var(--ct);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 2px
    }

    .n-msg {
        font-size: 10.5px;
        color: var(--cm);
        font-weight: 500;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4
    }

    .n-time {
        font-size: 9.5px;
        color: #94a3b8;
        font-weight: 700;
        flex-shrink: 0
    }

    .nfoot {
        display: flex;
        justify-content: center;
        padding: 8px;
        border-top: 1px solid #f4f6f9;
        font-size: 11px;
        font-weight: 700;
        color: var(--cpd);
        gap: 4px
    }

    /* ══ ALERT STRIP ════════════════════════════════════════════ */
    .astrip {
        margin: 12px 14px 0;
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 9px;
        cursor: pointer
    }

    .astrip:active {
        background: #fef9e7
    }

    .a-ico {
        color: #f59e0b;
        font-size: 16px;
        flex-shrink: 0
    }

    .a-body {
        flex: 1
    }

    .a-t {
        font-size: 11.5px;
        font-weight: 800;
        color: #92400e
    }

    .a-s {
        font-size: 9.5px;
        font-weight: 500;
        color: #b45309;
        margin-top: 1px
    }

    .a-chev {
        color: #f59e0b;
        font-size: 10px
    }

    /* ══ HERO BANNER ═════════════════════════════════════════════ */
    /* Banner langsung full-width, tanpa padding kiri kanan, tanpa card wrapper */
    .hb-wrap {
        margin-top: 0;
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .hb {
        border-radius: 0;
        box-shadow: none;
    }

    /* override rounded/shadow dari sebelumnya */
    .hb+.hb {
        border-top: 1px solid rgba(255, 255, 255, .1);
    }

    .hb {
        border-radius: 18px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 6px 24px rgba(0, 0, 0, .15);
        -webkit-tap-highlight-color: transparent;
        transition: transform .2s;
    }

    .hb:active {
        transform: scale(.98);
    }

    /* Full image type */
    .hb-img-only {
        display: block;
        width: 100%;
        object-fit: cover;
    }

    /* Layout type: left | center | right */
    .hb-layout {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .hb-side {
        display: flex;
        align-items: flex-end;
        justify-content: center;
        flex-shrink: 0;
        z-index: 2;
    }

    .hb-side img {
        object-fit: contain;
        display: block;
    }

    .hb-center {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 14px 6px 16px;
        z-index: 2;
    }

    .hb-title {
        font-size: 15px;
        font-weight: 900;
        line-height: 1.2;
        letter-spacing: -.3px;
        margin-bottom: 2px;
        text-shadow: 0 1px 4px rgba(0, 0, 0, .2);
    }

    .hb-sub {
        font-size: 10px;
        font-weight: 700;
        margin-bottom: 10px;
        opacity: .88;
    }

    .hb-btn {
        display: inline-block;
        padding: 7px 22px;
        border-radius: 99px;
        font-size: 11.5px;
        font-weight: 900;
        letter-spacing: .3px;
        text-decoration: none;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .20);
        cursor: pointer;
        border: none;
        font-family: var(--f);
        transition: transform .15s;
    }

    .hb-btn:active {
        transform: scale(.93);
    }

    /* Center image type */
    .hb-center-img {
        width: 100%;
        object-fit: contain;
        display: block;
    }

    /* ── Animations ─────────────────────── */
    @keyframes hb-float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-7px);
        }
    }

    @keyframes hb-bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-10px);
        }

        60% {
            transform: translateY(-5px);
        }
    }

    @keyframes hb-slide-left {
        from {
            transform: translateX(-30px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes hb-slide-right {
        from {
            transform: translateX(30px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes hb-pulse {

        0%,
        100% {
            transform: scale(1);
            box-shadow: 0 3px 12px rgba(0, 0, 0, .20);
        }

        50% {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 0, 0, .28);
        }
    }

    @keyframes hb-zoom-in {
        from {
            transform: scale(.85);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .anim-float {
        animation: hb-float 3s ease-in-out infinite;
    }

    .anim-bounce {
        animation: hb-bounce 2s ease-in-out infinite;
    }

    .anim-slide-left {
        animation: hb-slide-left .6s ease forwards;
    }

    .anim-slide-right {
        animation: hb-slide-right .6s ease forwards;
    }

    .anim-pulse {
        animation: hb-pulse 2s ease-in-out infinite;
    }

    .anim-zoom-in {
        animation: hb-zoom-in .5s ease forwards;
    }
</style>

<!-- ═══ HERO ═══ -->
<div class="hero">
    <div class="hero-ov"></div>
    <?php if (!empty($hero['hero_decoration_image'])): ?>
        <div class="hero-deco"><img src="<?= htmlspecialchars($hero['hero_decoration_image']) ?>" alt=""></div>
    <?php endif; ?>

    <div class="hero-in">
        <div class="hbar">
            <div class="brand">
                <div class="b-ico">
                    <?php if (!empty($cfg['brand_logo_url'])): ?>
                        <img src="<?= htmlspecialchars($cfg['brand_logo_url']) ?>" style="width:18px;height:18px;object-fit:contain" alt="">
                    <?php else: ?>
                        <i class="fas fa-bolt"></i>
                    <?php endif; ?>
                </div>
                <span class="b-name"><?= htmlspecialchars($brandName) ?></span>
            </div>
            <div class="hbtns">
                <a href="modules/user/inbox" class="hbtn">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadCount > 0): ?><span class="nbadge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span><?php endif; ?>
                </a>
                <a href="modules/user/profil" class="hbtn"><i class="fas fa-user-circle"></i></a>
            </div>
        </div>

        <div class="bal-wrap">
            <div class="bal-greet">
                <?php $h = (int)date('H');
                echo $h < 12 ? 'Selamat pagi ☀️, ' : ($h < 17 ? 'Selamat siang ☀️, ' : ($h < 20 ? 'Selamat sore 🌤️, ' : 'Selamat malam 🌙, '));
                ?><strong><?= htmlspecialchars(explode(' ', $user['fullname'] ?? $user['username'] ?? 'Pengguna')[0]) ?></strong>
            </div>
            <div class="bal-lbl">Saldo <?= htmlspecialchars($brandName) ?></div>
            <div class="bal-row">
                <div id="bal-d"><span class="bal-dots">••••••</span></div>
                <button class="bal-tog" onclick="toggleBal()"><i class="fas fa-eye" id="bal-i"></i></button>
            </div>
        </div>

        <div class="qa-grid">
            <?php foreach ($quickActs as $qa): ?>
                <a href="<?= htmlspecialchars($qa['href']) ?>" class="qa-item">
                    <div class="qa-ico"><i class="<?= htmlspecialchars($qa['icon_class']) ?>"></i></div>
                    <span class="qa-lbl"><?= htmlspecialchars($qa['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ═══ HERO BANNER ═══ -->
<?php if (!empty($heroBanners)): ?>
    <div class="hb-wrap">
        <?php foreach ($heroBanners as $hb):
            $bgStyle = !empty($hb['bg_image'])
                ? "background:url('{$hb['bg_image']}') center/cover no-repeat"
                : "background:linear-gradient({$hb['bg_gradient_angle']}deg,{$hb['bg_color_start']},{$hb['bg_color_end']})";
            $h = (int)($hb['height'] ?? 160);

            // animation class helper
            $animClass = fn($a) => match (trim((string)$a)) {
                'float'       => 'anim-float',
                'bounce'      => 'anim-bounce',
                'slide-left'  => 'anim-slide-left',
                'slide-right' => 'anim-slide-right',
                'pulse'       => 'anim-pulse',
                'zoom-in'     => 'anim-zoom-in',
                default       => '',
            };
        ?>

            <?php if ($hb['type'] === 'image_only'): ?>
                <!-- Type: full image -->
                <a href="<?= htmlspecialchars($hb['btn_href'] ?? '#') ?>" class="hb">
                    <img src="<?= htmlspecialchars($hb['bg_image']) ?>" class="hb-img-only"
                        style="height:<?= $h ?>px" alt="">
                </a>

            <?php elseif ($hb['type'] === 'image_center'): ?>
                <!-- Type: center image only -->
                <a href="<?= htmlspecialchars($hb['btn_href'] ?? '#') ?>" class="hb" style="<?= $bgStyle ?>">
                    <img src="<?= htmlspecialchars($hb['center_image'] ?? '') ?>"
                        class="hb-center-img <?= $animClass($hb['center_image_anim'] ?? '') ?>"
                        style="height:<?= $h ?>px;width:<?= (int)($hb['center_image_width'] ?? 160) ?>px;margin:0 auto"
                        alt="">
                </a>

            <?php else: ?>
                <!-- Type: layout (left | center | right) -->
                <div class="hb hb-layout" style="<?= $bgStyle ?>;height:<?= $h ?>px">

                    <!-- Left image -->
                    <?php if (!empty($hb['img_left'])): ?>
                        <div class="hb-side" style="width:<?= (int)($hb['img_left_width'] ?? 90) ?>px;height:<?= $h ?>px">
                            <img src="<?= htmlspecialchars($hb['img_left']) ?>"
                                class="<?= $animClass($hb['img_left_anim'] ?? '') ?>"
                                style="width:100%;max-height:<?= $h ?>px;object-fit:contain" alt="">
                        </div>
                    <?php endif; ?>

                    <!-- Center -->
                    <div class="hb-center">
                        <?php if (($hb['center_type'] ?? 'text') === 'image' && !empty($hb['center_image'])): ?>
                            <img src="<?= htmlspecialchars($hb['center_image']) ?>"
                                class="<?= $animClass($hb['center_image_anim'] ?? '') ?>"
                                style="width:<?= (int)($hb['center_image_width'] ?? 160) ?>px;max-height:<?= $h - 20 ?>px;object-fit:contain"
                                alt="">
                        <?php else: ?>
                            <?php if (!empty($hb['title'])): ?>
                                <div class="hb-title" style="color:<?= htmlspecialchars($hb['title_color'] ?? '#fff') ?>">
                                    <?= htmlspecialchars($hb['title']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($hb['subtitle'])): ?>
                                <div class="hb-sub" style="color:<?= htmlspecialchars($hb['subtitle_color'] ?? 'rgba(255,255,255,0.85)') ?>">
                                    <?= htmlspecialchars($hb['subtitle']) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($hb['btn_text'])): ?>
                            <a href="<?= htmlspecialchars($hb['btn_href'] ?? '#') ?>"
                                class="hb-btn <?= $animClass($hb['btn_anim'] ?? '') ?>"
                                style="background:<?= htmlspecialchars($hb['btn_color'] ?? '#FFD700') ?>;color:<?= htmlspecialchars($hb['btn_text_color'] ?? '#000') ?>">
                                <?= htmlspecialchars($hb['btn_text']) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Right image -->
                    <?php if (!empty($hb['img_right'])): ?>
                        <div class="hb-side" style="width:<?= (int)($hb['img_right_width'] ?? 90) ?>px;height:<?= $h ?>px">
                            <img src="<?= htmlspecialchars($hb['img_right']) ?>"
                                class="<?= $animClass($hb['img_right_anim'] ?? '') ?>"
                                style="width:100%;max-height:<?= $h ?>px;object-fit:contain" alt="">
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ═══ MENU CARD ═══ -->
<div class="fw <?= !empty($heroBanners) ? 'fw-normal' : 'fw-overlap' ?>">
    <div class="mcard">
        <div class="mgrid" id="mgrid"></div>
    </div>
</div>

<!-- ═══ RUNNING TEXT ═══ -->
<?php if (!empty($runningTexts)):
    $rt = $runningTexts[0];
    $items = '';
    foreach ($runningTexts as $r) {
        $items .= '<span class="rticker-item"><i class="' . htmlspecialchars($r['icon_class'])
            . '" style="color:' . htmlspecialchars($r['icon_color']) . '"></i>'
            . htmlspecialchars($r['content']) . '</span>'
            . '<span class="rticker-item" style="color:#cbd5e1">•</span>';
    }
    $speed = max(10, (int)($rt['speed'] ?? 35));
?>
    <div class="rticker" style="background:<?= htmlspecialchars($rt['bg_color'] ?? '#fff') ?>;border-color:<?= htmlspecialchars($rt['border_color'] ?? '#e2e8f0') ?>">
        <div class="rticker-in" style="animation-duration:<?= $speed ?>s;color:<?= htmlspecialchars($rt['text_color'] ?? '#0f172a') ?>">
            <?= $items . $items ?>
        </div>
    </div>
<?php endif; ?>

<!-- ═══ SEARCH ═══ -->
<div class="swrap">
    <div class="sbar">
        <i class="fas fa-magnifying-glass"></i>
        <span>Cari layanan atau transaksi…</span>
        <span class="sk">⌘K</span>
    </div>
</div>

<!-- ═══ PROMO BANNERS ═══ -->
<?php if (!empty($banners)): ?>
    <div class="sec">
        <div class="sechd">
            <div class="sectit"><i class="fas fa-fire" style="color:#f97316"></i> Promo</div>
            <a href="#" class="secmore">Semua <i class="fas fa-chevron-right" style="font-size:8px"></i></a>
        </div>
        <div class="bscroll">
            <div class="btrack">
                <?php foreach ($banners as $b): ?>
                    <a href="<?= htmlspecialchars($b['href']) ?>" class="bcard">
                        <?php if (!empty($b['image_url'])): ?>
                            <img src="<?= htmlspecialchars($b['image_url']) ?>" class="bimg" alt="">
                            <div class="bcap">
                                <?php if (!empty($b['tag_label'])): ?><span class="bc-tag"><?= htmlspecialchars($b['tag_label']) ?></span><?php endif; ?>
                                <div class="bc-title"><?= htmlspecialchars($b['title']) ?></div>
                                <?php if (!empty($b['subtitle'])): ?><div class="bc-sub"><?= htmlspecialchars($b['subtitle']) ?></div><?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="bgrad" style="background:linear-gradient(135deg,<?= htmlspecialchars($b['bg_color_start']) ?>,<?= htmlspecialchars($b['bg_color_end']) ?>)">
                                <div class="bg-tx">
                                    <?php if (!empty($b['tag_label'])): ?><span class="bg-tag"><?= htmlspecialchars($b['tag_label']) ?></span><?php endif; ?>
                                    <div class="bg-tit"><?= nl2br(htmlspecialchars($b['title'])) ?></div>
                                    <?php if (!empty($b['subtitle'])): ?><div class="bg-sub"><?= htmlspecialchars($b['subtitle']) ?></div><?php endif; ?>
                                </div>
                                <?php if (!empty($b['emoji_icon'])): ?><div class="bg-emo"><?= htmlspecialchars($b['emoji_icon']) ?></div><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ═══ STATS ═══ -->
<div class="sec">
    <div class="stats">
        <div class="sc">
            <div class="sc-ico" style="background:var(--cpl)"><i class="fas fa-arrow-trend-up" style="color:var(--cpd)"></i></div>
            <div>
                <div class="sc-lbl">Trx Bulan Ini</div>
                <div class="sc-val"><?= $trxCount ?> Trx</div>
            </div>
        </div>
        <div class="sc">
            <div class="sc-ico" style="background:#fff7ed"><i class="fas fa-wallet" style="color:#f97316"></i></div>
            <div>
                <div class="sc-lbl">Saldo Aktif</div>
                <div class="sc-val">Rp <?= number_format($user['saldo'] ?? 0, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ NOTIFICATIONS ═══ -->
<div class="sec">
    <div class="sechd">
        <div class="sectit"><i class="fas fa-bell" style="color:var(--cp)"></i> Notifikasi</div>
        <a href="modules/user/inbox" class="secmore">Semua <i class="fas fa-chevron-right" style="font-size:8px"></i></a>
    </div>
    <div class="ncard">
        <?php if (empty($feedNotifs)): ?>
            <div class="nr" style="justify-content:center;color:var(--cm);font-size:11.5px;font-weight:600;border-bottom:none">
                <i class="far fa-bell-slash" style="margin-right:6px"></i>Belum ada notifikasi
            </div>
            <?php else: foreach ($feedNotifs as $n):
                [$ico, $col, $bg] = notifMeta($n['title'] ?? '');
            ?>
                <a href="modules/user/inbox" class="nr">
                    <div class="n-ico" style="background:<?= $bg ?>"><i class="<?= $ico ?>" style="color:<?= $col ?>"></i></div>
                    <div class="n-body">
                        <div class="n-tit"><?= htmlspecialchars($n['title'] ?? 'Notifikasi') ?></div>
                        <div class="n-msg"><?= htmlspecialchars($n['message'] ?? '') ?></div>
                    </div>
                    <span class="n-time"><?= timeAgo($n['created_at']) ?></span>
                </a>
        <?php endforeach;
        endif; ?>
        <a href="modules/user/inbox" class="nfoot">Semua notifikasi <i class="fas fa-chevron-right" style="font-size:9px"></i></a>
    </div>
</div>

<!-- ═══ SECURITY ALERT ═══ -->
<div class="astrip">
    <i class="fas fa-shield-halved a-ico"></i>
    <div class="a-body">
        <div class="a-t">Aktifkan verifikasi 2 langkah</div>
        <div class="a-s">Tingkatkan keamanan akun kamu</div>
    </div>
    <i class="fas fa-chevron-right a-chev"></i>
</div>
<div class="g20"></div>

<?php require_once 'includes/footer.php'; ?>

<script>
    // ── Balance toggle ────────────────────────────────────────
    const saldo = 'Rp <?= number_format($user['saldo'] ?? 0, 0, ',', '.') ?>';
    let balHidden = true;

    function toggleBal() {
        const d = document.getElementById('bal-d');
        const i = document.getElementById('bal-i');
        if (balHidden) {
            d.innerHTML = '<span class="bal-num">' + saldo + '</span>';
            i.className = 'fas fa-eye-slash';
        } else {
            d.innerHTML = '<span class="bal-dots">••••••</span>';
            i.className = 'fas fa-eye';
        }
        balHidden = !balHidden;
    }

    // ── Menu grid — JS rendered ───────────────────────────────
    const menuMain = <?= $jsMain ?>;
    const menuOther = <?= $jsOther ?>;
    const hasOther = <?= $hasOther ? 'true' : 'false' ?>;

    function buildIcon(m) {
        const w = document.createElement('div');
        w.className = 'mi-ico';
        w.style.background = m.bg;
        if (m.type === 'fontawesome') {
            const i = document.createElement('i');
            i.className = m.icon;
            i.style.color = m.color;
            w.appendChild(i);
        } else if (m.type === 'image_url') {
            const img = document.createElement('img');
            img.src = m.icon;
            img.alt = '';
            w.appendChild(img);
        } else {
            w.textContent = m.icon;
        }
        return w;
    }

    function buildItem(m, fn) {
        const a = document.createElement('a');
        a.className = 'mi';
        a.href = fn ? 'javascript:void(0)' : m.href;
        if (fn) a.addEventListener('click', fn);
        a.appendChild(buildIcon(m));
        const lbl = document.createElement('span');
        lbl.className = 'mi-lbl';
        lbl.textContent = m.name;
        a.appendChild(lbl);
        return a;
    }

    function buildDivider() {
        const d = document.createElement('div');
        d.className = 'mdiv';
        return d;
    }

    function renderMenu(open) {
        const g = document.getElementById('mgrid');
        g.innerHTML = '';
        menuMain.forEach(m => g.appendChild(buildItem(m, null)));
        if (!open && hasOther) {
            g.appendChild(buildItem({
                    href: '#',
                    bg: '#f1f5f9',
                    type: 'fontawesome',
                    icon: 'fas fa-th',
                    color: '#64748b',
                    name: 'Lainnya'
                },
                () => renderMenu(true)
            ));
        }
        if (open) {
            g.appendChild(buildDivider());
            menuOther.forEach(m => g.appendChild(buildItem(m, null)));
            g.appendChild(buildItem({
                    href: '#',
                    bg: '#fff1f2',
                    type: 'fontawesome',
                    icon: 'fas fa-chevron-up',
                    color: '#ef4444',
                    name: 'Tutup'
                },
                () => renderMenu(false)
            ));
        }
    }
    renderMenu(false);
</script>