<?php

/**
 * pages/services.php
 * Halaman Semua Layanan
 */

$pageTitle = 'Semua Layanan';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../core/transaction.php';
require_once __DIR__ . '/../core/api_handler.php';

/* ── POST handler — proses transaksi langsung di sini ──────── */
$txMessage = '';
$txStatus  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin    = $_POST['pin_transaksi'] ?? '';
    $sku    = $_POST['sku']           ?? '';
    $target = $_POST['target']        ?? '';
    $refId  = $_POST['ref_id']        ?? '';

    // ── Debug: log semua POST yang masuk ─────────────────────
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    file_put_contents(
        $logDir . '/transaction_debug.log',
        '[' . date('Y-m-d H:i:s') . '] [SERVICES_POST] ' . json_encode([
            'sku'           => $sku,
            'target'        => $target,
            'ref_id'        => $refId,
            'has_beli'      => isset($_POST['beli']),
            'bayar_tagihan' => $_POST['bayar_tagihan'] ?? '',
            'user_id'       => $_SESSION['user_id'],
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
    // ─────────────────────────────────────────────────────────

    if (isset($_POST['bayar_tagihan']) && $_POST['bayar_tagihan'] === '1') {
        $txMessage = bayarTagihanPasca($_SESSION['user_id'], $sku, $target, $refId, $pin);
        $txStatus  = str_contains(strtolower($txMessage), 'berhasil') ? 'ok' : 'err';
    } elseif (isset($_POST['beli'])) {
        $txMessage = prosesTransaksi($_SESSION['user_id'], $sku, $target, $pin);
        $txStatus  = str_contains(strtolower($txMessage), 'diproses') ? 'ok' : 'err';
    }
}

/* ── 1. Dashboard menus (static bar atas) ──────────────────── */
$dashMenus = $pdo->query(
    "SELECT * FROM dashboard_menus WHERE is_active=1 ORDER BY sort_order ASC"
)->fetchAll(PDO::FETCH_ASSOC);

/* ── 2. Service menus — ambil semua sekaligus ──────────────── */
$smRows = $pdo->query(
    "SELECT * FROM service_menus WHERE is_active=1 ORDER BY sort_order ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$smCats  = [];
$smItems = [];
foreach ($smRows as $r) {
    if ($r['row_type'] === 'category') {
        $smCats[$r['id']] = $r;
    } else {
        $smItems[$r['category_id']][] = $r;
    }
}

function menuHref(array $m): string
{
    $href = $m['href'] ?? '#';
    if (!empty($m['query_cat']) && $href !== '#') {
        $sep  = str_contains($href, '?') ? '&' : '?';
        $href .= $sep . 'cat=' . urlencode($m['query_cat']);
    }
    return htmlspecialchars($href);
}
?>

<style>
    .sv-topbar {
        position: sticky;
        top: 0;
        z-index: 50;
        background: var(--cp);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 14px;
        height: 54px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .12);
    }

    .sv-topbar-back {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 17px;
        flex-shrink: 0;
    }

    .sv-topbar-title {
        flex: 1;
        font-size: 15px;
        font-weight: 800;
        color: #fff;
    }

    .sv-topbar-srch {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 17px;
        cursor: pointer;
    }

    .sv-static-wrap {
        background: var(--cp);
        padding: 0 14px 16px;
    }

    .sv-static-inner {
        background: rgba(255, 255, 255, .12);
        border-radius: 14px;
        padding: 10px 12px;
    }

    .sv-static-lbl {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: rgba(255, 255, 255, .7);
        margin-bottom: 10px;
    }

    .sv-static-list {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        scrollbar-width: none;
        padding-bottom: 2px;
    }

    .sv-static-list::-webkit-scrollbar {
        display: none;
    }

    .sv-static-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        min-width: 56px;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        text-decoration: none;
    }

    .sv-static-ico {
        width: 44px;
        height: 44px;
        border-radius: 13px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .24);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        color: #fff;
        flex-shrink: 0;
        transition: background .15s, transform .15s;
    }

    .sv-static-item:active .sv-static-ico {
        background: rgba(255, 255, 255, .3);
        transform: scale(.94);
    }

    .sv-static-lbl2 {
        font-size: 9.5px;
        font-weight: 700;
        color: rgba(255, 255, 255, .88);
        text-align: center;
        line-height: 1.2;
        max-width: 56px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .sv-search-wrap {
        padding: 12px 14px 4px;
    }

    .sv-search {
        display: flex;
        align-items: center;
        gap: 9px;
        background: var(--cc);
        border-radius: 12px;
        padding: 9px 13px;
        border: 1.5px solid #e8edf2;
        transition: border-color .15s;
    }

    .sv-search:focus-within {
        border-color: var(--cp);
    }

    .sv-search i {
        color: var(--cm);
        font-size: 15px;
        flex-shrink: 0;
    }

    .sv-search input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 13px;
        font-family: var(--f);
        background: transparent;
        color: var(--ct);
    }

    .sv-search input::placeholder {
        color: var(--cm);
    }

    .sv-section {
        padding: 16px 14px 0;
    }

    .sv-sec-hd {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .sv-sec-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--ct);
        letter-spacing: -.1px;
    }

    .sv-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }

    .sv-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        padding: 10px 4px;
        border-radius: 12px;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: background .12s, transform .12s;
        position: relative;
        text-decoration: none;
    }

    .sv-item:active {
        background: rgba(0, 0, 0, .04);
        transform: scale(.95);
    }

    .sv-ico {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }

    .sv-ico img {
        width: 28px;
        height: 28px;
        object-fit: contain;
    }

    .sv-name {
        font-size: 10.5px;
        font-weight: 700;
        color: var(--ct);
        text-align: center;
        line-height: 1.25;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .sv-badge {
        position: absolute;
        top: 6px;
        right: 6px;
        font-size: 8px;
        font-weight: 800;
        padding: 1px 5px;
        border-radius: 99px;
        color: #fff;
    }

    .sv-div {
        height: 8px;
    }

    .sv-empty {
        padding: 50px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .sv-empty i {
        font-size: 32px;
        color: var(--cp);
        opacity: .3;
    }

    .sv-empty p {
        font-size: 12px;
        color: var(--cm);
    }

    .sv-sheet-bg {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        backdrop-filter: blur(3px);
        z-index: 200;
        display: none;
    }

    .sv-sheet-bg.show {
        display: block;
    }

    .sv-sheet {
        position: fixed;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%) translateY(100%);
        width: 100%;
        max-width: 480px;
        background: var(--cc);
        border-radius: 24px 24px 0 0;
        z-index: 201;
        box-shadow: 0 -8px 40px rgba(0, 0, 0, .18);
        transition: transform .3s cubic-bezier(.4, 0, .2, 1);
        max-height: 92dvh;
        overflow-y: auto;
        scrollbar-width: none;
    }

    .sv-sheet::-webkit-scrollbar {
        display: none;
    }

    .sv-sheet.show {
        transform: translateX(-50%) translateY(0);
    }

    .sv-sheet-pull {
        width: 36px;
        height: 4px;
        background: rgba(0, 0, 0, .1);
        border-radius: 99px;
        margin: 12px auto 0;
    }

    .sv-sheet-head {
        padding: 14px 18px 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        position: sticky;
        top: 0;
        background: var(--cc);
        z-index: 5;
    }

    .sv-sheet-ico {
        width: 44px;
        height: 44px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 21px;
        flex-shrink: 0;
    }

    .sv-sheet-title {
        font-size: 15px;
        font-weight: 800;
        color: var(--ct);
    }

    .sv-sheet-sub {
        font-size: 11px;
        color: var(--cm);
        margin-top: 2px;
    }

    .sv-sheet-close {
        margin-left: auto;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(0, 0, 0, .06);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        color: var(--cm);
        flex-shrink: 0;
    }

    .sv-sheet-body {
        padding: 16px 18px calc(84px + env(safe-area-inset-bottom));
    }

    .sv-field {
        margin-bottom: 14px;
    }

    .sv-field label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: var(--cm);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 6px;
    }

    .sv-inp {
        width: 100%;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 11px;
        padding: 11px 13px;
        font-size: 14px;
        font-weight: 600;
        font-family: var(--f);
        color: var(--ct);
        outline: none;
        transition: border-color .15s;
    }

    .sv-inp:focus {
        border-color: var(--cp);
        background: #fff;
    }

    .sv-inp::placeholder {
        font-weight: 500;
        color: #94a3b8;
    }

    .sv-op-lbl {
        font-size: 11px;
        font-weight: 700;
        color: var(--cp);
        min-height: 18px;
        margin-top: 4px;
    }

    .sv-prod-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        max-height: 220px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #e2e8f0 transparent;
    }

    .sv-prod-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 10px;
        cursor: pointer;
        background: #fff;
        text-align: center;
        transition: all .15s;
    }

    .sv-prod-card:hover {
        border-color: var(--cp);
    }

    .sv-prod-card.on {
        border-color: var(--cp);
        background: var(--cpl);
    }

    .sv-prod-name {
        font-size: 12px;
        font-weight: 700;
        color: var(--ct);
        display: block;
        margin-bottom: 4px;
    }

    .sv-prod-price {
        font-size: 13px;
        font-weight: 800;
        color: var(--cpd);
        display: block;
    }

    .sv-inquiry {
        background: var(--cpl);
        border: 1.5px dashed var(--cp);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 14px;
    }

    .sv-iq-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        font-size: 12px;
    }

    .sv-iq-row:not(:last-child) {
        border-bottom: 1px solid rgba(0, 0, 0, .05);
    }

    .sv-iq-k {
        color: var(--cm);
    }

    .sv-iq-v {
        font-weight: 800;
        color: var(--ct);
    }

    .sv-iq-total .sv-iq-k,
    .sv-iq-total .sv-iq-v {
        font-size: 14px;
        color: var(--cpd);
        font-weight: 900;
    }

    .sv-pin-wrap {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin: 8px 0 16px;
    }

    .sv-pin-dot {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        transition: border-color .15s;
    }

    .sv-pin-dot.filled {
        border-color: var(--cp);
        background: var(--cpl);
    }

    .sv-pin-dot.filled::after {
        content: '●';
        color: var(--cp);
        font-size: 12px;
    }

    .sv-numpad {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 8px;
    }

    .sv-numpad-key {
        height: 50px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        font-size: 18px;
        font-weight: 700;
        color: var(--ct);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background .12s, transform .1s;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
    }

    .sv-numpad-key:active {
        background: var(--cpl);
        transform: scale(.95);
    }

    .sv-numpad-key.del {
        color: var(--cm);
    }

    .sv-numpad-key.empty {
        background: transparent;
        border-color: transparent;
        pointer-events: none;
    }

    .sv-steps {
        display: flex;
        gap: 5px;
        justify-content: center;
        margin-bottom: 14px;
    }

    .sv-step {
        height: 3px;
        border-radius: 99px;
        flex: 1;
        max-width: 60px;
        background: #e2e8f0;
        transition: background .2s;
    }

    .sv-step.on {
        background: var(--cp);
    }

    .sv-cta {
        width: 100%;
        padding: 14px;
        background: var(--cp);
        color: #fff;
        border: none;
        border-radius: 13px;
        font-size: 14px;
        font-weight: 800;
        font-family: var(--f);
        cursor: pointer;
        transition: background .15s, transform .1s, opacity .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .sv-cta:active {
        transform: scale(.98);
    }

    .sv-cta:disabled {
        opacity: .5;
        pointer-events: none;
    }

    .sv-cta-sec {
        width: 100%;
        padding: 11px;
        margin-top: 8px;
        background: transparent;
        color: var(--cm);
        border: none;
        border-radius: 13px;
        font-size: 13px;
        font-weight: 700;
        font-family: var(--f);
        cursor: pointer;
    }

    .sv-bottom {
        height: 16px;
    }

    @keyframes sv-spin {
        to {
            transform: rotate(360deg);
        }
    }

    .sv-spinner {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: 3px solid rgba(0, 0, 0, .08);
        border-top-color: var(--cp);
        animation: sv-spin .7s linear infinite;
        display: inline-block;
    }

    /* ── Toast ── */
    .sv-toast {
        position: fixed;
        top: -80px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        max-width: 340px;
        width: calc(100% - 28px);
        padding: 12px 16px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 8px 28px rgba(0, 0, 0, .18);
        transition: top .35s cubic-bezier(.4, 0, .2, 1), opacity .35s;
        opacity: 0;
        pointer-events: none;
    }

    .sv-toast.show {
        top: 14px;
        opacity: 1;
        pointer-events: auto;
    }

    .sv-toast.ok {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #15803d;
    }

    .sv-toast.err {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
    }

    .sv-toast-close {
        margin-left: auto;
        cursor: pointer;
        opacity: .7;
        flex-shrink: 0;
    }
</style>

<!-- TOAST -->
<div class="sv-toast" id="svToast">
    <span id="svToastIcon"></span>
    <span id="svToastMsg"></span>
    <span class="sv-toast-close" onclick="closeToast()">
        <svg width="12" height="12" viewBox="0 0 14 14" fill="none">
            <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
    </span>
</div>

<?php if ($txMessage): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast(<?= json_encode($txMessage) ?>, <?= json_encode($txStatus) ?>);
        });
    </script>
<?php endif; ?>

<!-- TOP BAR -->
<div class="sv-topbar">
    <a href="<?= base_url('dashboard.php') ?>" class="sv-topbar-back">
        <i class="fas fa-chevron-left"></i>
    </a>
    <div class="sv-topbar-title">Semua Layanan</div>
    <button class="sv-topbar-srch" onclick="document.getElementById('svSearch').focus()">
        <i class="fas fa-search"></i>
    </button>
</div>

<!-- STATIC BAR — semua dashboard_menus -->
<?php if (!empty($dashMenus)): ?>
    <div class="sv-static-wrap">
        <div class="sv-static-inner">
            <div class="sv-static-lbl">Menu Utama</div>
            <div class="sv-static-list">
                <?php foreach ($dashMenus as $dm): ?>
                    <a class="sv-static-item" href="<?= htmlspecialchars($dm['href'] ?? '#') ?>">
                        <div class="sv-static-ico" style="background:<?= htmlspecialchars($dm['icon_bg_color'] ?? 'rgba(255,255,255,.18)') ?>">
                            <?php if ($dm['icon_type'] === 'image_url'): ?>
                                <img src="<?= htmlspecialchars($dm['icon_value']) ?>" style="width:26px;height:26px;object-fit:contain" alt="">
                            <?php elseif ($dm['icon_type'] === 'emoji'): ?>
                                <span style="font-size:20px"><?= htmlspecialchars($dm['icon_value']) ?></span>
                            <?php else: ?>
                                <i class="<?= htmlspecialchars($dm['icon_value']) ?>" style="color:<?= htmlspecialchars($dm['icon_color'] ?? '#fff') ?>"></i>
                            <?php endif; ?>
                        </div>
                        <span class="sv-static-lbl2"><?= htmlspecialchars($dm['name'] ?? '') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- SEARCH -->
<div class="sv-search-wrap">
    <div class="sv-search">
        <i class="fas fa-search"></i>
        <input type="text" id="svSearch" placeholder="Cari layanan...">
    </div>
</div>

<!-- DYNAMIC SERVICE SECTIONS -->
<div id="svList">
    <?php if (empty($smCats)): ?>
        <div class="sv-empty">
            <i class="fas fa-th-large"></i>
            <p>Belum ada layanan.<br>Tambah melalui menu Admin.</p>
        </div>
    <?php else: ?>
        <?php foreach ($smCats as $catId => $cat): ?>
            <?php $items = $smItems[$catId] ?? [];
            if (empty($items)) continue; ?>
            <div class="sv-section" data-cat="<?= htmlspecialchars($cat['cat_slug']) ?>">
                <div class="sv-sec-hd">
                    <span class="sv-sec-title"><?= htmlspecialchars($cat['cat_name']) ?></span>
                </div>
                <div class="sv-grid">
                    <?php foreach ($items as $item):
                        $href     = menuHref($item);
                        $useModal = str_contains($item['href'] ?? '', 'prabayar') || str_contains($item['href'] ?? '', 'pascabayar');
                        $modalData = $useModal ? htmlspecialchars(json_encode([
                            'name'       => $item['name'],
                            'icon_type'  => $item['icon_type'],
                            'icon_value' => $item['icon_value'],
                            'icon_bg'    => $item['icon_bg'],
                            'icon_color' => $item['icon_color'],
                            'href'       => $item['href'],
                            'query_cat'  => $item['query_cat'],
                            'type'       => str_contains($item['href'], 'pascabayar') ? 'pasca' : 'prabayar',
                        ])) : '';
                    ?>
                        <<?= $useModal ? 'div' : 'a' ?>
                            class="sv-item"
                            <?= $useModal ? "onclick=\"openSheet({$modalData})\"" : "href=\"{$href}\"" ?>
                            data-search="<?= strtolower(htmlspecialchars($item['name'])) ?>">
                            <div class="sv-ico" style="background:<?= htmlspecialchars($item['icon_bg']) ?>">
                                <?php if ($item['icon_type'] === 'img'): ?>
                                    <img src="<?= htmlspecialchars($item['icon_value']) ?>" alt="">
                                <?php else:
                                    $prefix = $item['icon_type'] === 'fa' ? '' : 'ph ';
                                ?>
                                    <i class="<?= $prefix . htmlspecialchars($item['icon_value']) ?>"
                                        style="color:<?= htmlspecialchars($item['icon_color']) ?>;font-size:22px"></i>
                                <?php endif; ?>
                            </div>
                            <span class="sv-name"><?= htmlspecialchars($item['name']) ?></span>
                            <?php if (!empty($item['badge'])): ?>
                                <span class="sv-badge" style="background:<?= htmlspecialchars($item['badge_color']) ?>"><?= htmlspecialchars($item['badge']) ?></span>
                            <?php endif; ?>
                        </<?= $useModal ? 'div' : 'a' ?>>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="sv-div"></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div id="svNoResult" class="sv-empty" style="display:none">
        <i class="fas fa-search"></i>
        <p>Layanan tidak ditemukan.</p>
    </div>
</div>

<div class="sv-bottom"></div>

<!-- BOTTOM SHEET -->
<div class="sv-sheet-bg" id="svBg" onclick="closeSheet()"></div>
<div class="sv-sheet" id="svSheet">
    <div class="sv-sheet-pull"></div>
    <div class="sv-sheet-head">
        <div class="sv-sheet-ico" id="svSheetIco"></div>
        <div>
            <div class="sv-sheet-title" id="svSheetTitle">Layanan</div>
            <div class="sv-sheet-sub" id="svSheetSub"></div>
        </div>
        <button class="sv-sheet-close" onclick="closeSheet()">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
        </button>
    </div>
    <div class="sv-sheet-body" id="svSheetBody"></div>
</div>

<!-- Hidden form POST -->
<form method="POST" id="svHiddenForm" style="display:none">
    <input type="hidden" name="sku" id="fSku">
    <input type="hidden" name="target" id="fTarget">
    <input type="hidden" name="pin_transaksi" id="fPin">
    <input type="hidden" name="ref_id" id="fRefId">
    <input type="hidden" name="cat" id="fCat">
    <input type="hidden" name="beli" value="1">
    <input type="hidden" name="cek_tagihan" id="fCekTagihan" value="">
    <input type="hidden" name="bayar_tagihan" id="fBayarTagihan" value="">
</form>

<script>
    const SVG = {
        arrowRight: `<svg width="18" height="18" viewBox="0 0 256 256" fill="currentColor"><path d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z"/></svg>`,
        search: `<svg width="18" height="18" viewBox="0 0 256 256" fill="currentColor"><path d="M229.66,218.34l-50.07-50.07a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.31ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"/></svg>`,
        lock: `<svg width="18" height="18" viewBox="0 0 256 256" fill="currentColor"><path d="M208,80H168V56a40,40,0,0,0-80,0V80H48A16,16,0,0,0,32,96V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V96A16,16,0,0,0,208,80ZM104,56a24,24,0,0,1,48,0V80H104ZM208,208H48V96H208V208Zm-80-48a16,16,0,1,0-16-16A16,16,0,0,0,128,160Z"/></svg>`,
        backspace: `<svg width="22" height="22" viewBox="0 0 256 256" fill="currentColor"><path d="M216,48H88a16,16,0,0,0-13.16,6.92l-61.6,84.85a8,8,0,0,0,0,9.35l61.6,84.85A16,16,0,0,0,88,240H216a16,16,0,0,0,16-16V64A16,16,0,0,0,216,48Zm0,176H88L30.43,140,88,64H216ZM178.34,146.34,161,128l17.37-17.37a8,8,0,0,0-11.32-11.32L149.66,116.7l-17.37-17.37a8,8,0,0,0-11.32,11.32L138.35,128l-17.38,17.34a8,8,0,0,0,11.32,11.32l17.37-17.37,17.37,17.37a8,8,0,0,0,11.31-11.32Z"/></svg>`,
    };

    document.getElementById('svSearch').addEventListener('input', function() {
        const kw = this.value.toLowerCase();
        let any = false;
        document.querySelectorAll('.sv-item').forEach(el => {
            const match = !kw || (el.dataset.search || '').includes(kw);
            el.style.display = match ? '' : 'none';
            if (match) any = true;
        });
        document.querySelectorAll('.sv-section').forEach(sec => {
            const vis = [...sec.querySelectorAll('.sv-item')].some(el => el.style.display !== 'none');
            sec.style.display = vis ? '' : 'none';
        });
        document.getElementById('svNoResult').style.display = any ? 'none' : '';
    });

    let _sheetMeta = null,
        _step = 1,
        _selectedSku = '',
        _pinVal = '',
        _inquiryData = null,
        _target = '';

    const OPERATOR_MAP = {
        '0811': 'Telkomsel',
        '0812': 'Telkomsel',
        '0813': 'Telkomsel',
        '0821': 'Telkomsel',
        '0822': 'Telkomsel',
        '0852': 'Telkomsel',
        '0853': 'Telkomsel',
        '0817': 'XL',
        '0818': 'XL',
        '0819': 'XL',
        '0859': 'XL',
        '0877': 'XL',
        '0878': 'XL',
        '0814': 'Indosat',
        '0815': 'Indosat',
        '0816': 'Indosat',
        '0855': 'Indosat',
        '0856': 'Indosat',
        '0857': 'Indosat',
        '0858': 'Indosat',
        '0895': 'Three',
        '0896': 'Three',
        '0897': 'Three',
        '0898': 'Three',
        '0899': 'Three',
        '0831': 'Axis',
        '0832': 'Axis',
        '0833': 'Axis',
        '0838': 'Axis',
        '0881': 'Smartfren',
        '0882': 'Smartfren',
        '0883': 'Smartfren',
        '0884': 'Smartfren',
        '0885': 'Smartfren',
    };

    function openSheet(meta) {
        _sheetMeta = meta;
        _step = 1;
        _selectedSku = '';
        _pinVal = '';
        _inquiryData = null;
        _target = '';
        renderSheet();
        document.getElementById('svBg').classList.add('show');
        requestAnimationFrame(() => document.getElementById('svSheet').classList.add('show'));
    }

    function closeSheet() {
        document.getElementById('svSheet').classList.remove('show');
        setTimeout(() => document.getElementById('svBg').classList.remove('show'), 300);
    }
    (function() {
        const sh = document.getElementById('svSheet');
        let sy = 0,
            st = 0;
        sh.addEventListener('touchstart', e => {
            sy = e.touches[0].clientY;
            st = Date.now();
        }, {
            passive: true
        });
        sh.addEventListener('touchend', e => {
            if (e.changedTouches[0].clientY - sy > 60 || (e.changedTouches[0].clientY - sy > 30 && Date.now() - st < 200)) closeSheet();
        }, {
            passive: true
        });
    })();

    function renderSheet() {
        const m = _sheetMeta;
        if (!m) return;
        const ico = document.getElementById('svSheetIco');
        ico.style.background = m.icon_bg || '#e0f2fe';
        if (m.icon_type === 'img') {
            ico.innerHTML = `<img src="${esc(m.icon_value)}" style="width:26px;height:26px;object-fit:contain" alt="">`;
        } else {
            ico.innerHTML = `<i class="${m.icon_type === 'fa' ? '' : 'ph '}${esc(m.icon_value)}" style="color:${esc(m.icon_color)};font-size:22px"></i>`;
        }
        document.getElementById('svSheetTitle').textContent = m.name || 'Layanan';
        const stepHtml = `<div class="sv-steps">
        <div class="sv-step ${_step>=1?'on':''}"></div>
        <div class="sv-step ${_step>=2?'on':''}"></div>
        <div class="sv-step ${_step>=3?'on':''}"></div>
    </div>`;
        document.getElementById('svSheetSub').textContent = ['Form Input', 'Konfirmasi', 'Masukkan PIN'][_step - 1] || '';
        const body = document.getElementById('svSheetBody');
        if (_step === 1) {
            body.innerHTML = stepHtml + renderStep1(m);
            bindStep1();
        } else if (_step === 2) {
            body.innerHTML = stepHtml + (_inquiryData ? renderInquiry() : renderConfirm(m));
        } else if (_step === 3) {
            body.innerHTML = stepHtml + renderPin();
        }
    }

    function renderStep1(m) {
        const ctaIcon = m.type === 'pasca' ? SVG.search : SVG.arrowRight;
        const ctaLabel = m.type === 'pasca' ? 'Cek Tagihan' : 'Lanjutkan';
        return `
    <div class="sv-field">
        <label>Nomor / ID Pelanggan</label>
        <input type="tel" class="sv-inp" id="svTarget" placeholder="Contoh: 085819478911" autocomplete="tel">
        <div class="sv-op-lbl" id="svOpLbl"></div>
        <div style="font-size:10.5px;color:var(--cm);margin-top:4px;line-height:1.6">
            Format yang didukung: <code style="background:rgba(0,0,0,.06);padding:1px 5px;border-radius:4px">085xxx</code>
            &nbsp;<code style="background:rgba(0,0,0,.06);padding:1px 5px;border-radius:4px">6285xxx</code>
            &nbsp;<code style="background:rgba(0,0,0,.06);padding:1px 5px;border-radius:4px">+6285xxx</code>
            &nbsp;<code style="background:rgba(0,0,0,.06);padding:1px 5px;border-radius:4px">85xxx</code>
        </div>
    </div>
    <div class="sv-field">
        <label>Pilih Produk</label>
        <div class="sv-prod-grid" id="svProdGrid">
            <div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--cm);font-size:12px">
                <span class="sv-spinner" style="margin:0 auto 8px;display:block"></span>Memuat produk...
            </div>
        </div>
        <input type="hidden" id="svSelSku">
    </div>
    <button class="sv-cta" id="svCta1" onclick="goStep2()" disabled>${ctaIcon} ${ctaLabel}</button>`;
    }

    /* ── Normalisasi nomor HP — handle semua format ──────────────────
     * Input yang didukung:
     *   85819478911       → 085819478911  (tanpa 0)
     *   085819478911      → 085819478911  (sudah benar)
     *   6285819478911     → 085819478911  (format internasional tanpa +)
     *   +6285819478911    → 085819478911  (format internasional dengan +)
     *   0858-1947-8911    → 085819478911  (dengan strip/dash)
     *   0858 1947 8911    → 085819478911  (dengan spasi)
     */
    function normalizePhone(raw) {
        // 1. Strip semua kecuali digit dan +
        let num = raw.replace(/[^0-9+]/g, '');
        // 2. Handle +62 → 0
        if (num.startsWith('+62')) num = '0' + num.slice(3);
        // 3. Handle 62 → 0 (hanya jika panjang > 11 digit, hindari angka biasa yg mulai 62xx)
        else if (num.startsWith('62') && num.length >= 12) num = '0' + num.slice(2);
        // 4. Handle 8xxx tanpa awalan → tambah 0
        else if (num.startsWith('8') && !num.startsWith('0')) num = '0' + num;
        return num;
    }

    function bindStep1() {
        const m = _sheetMeta;
        loadProducts(m.query_cat, m.type === 'pasca');
        const ti = document.getElementById('svTarget');
        if (ti) ti.addEventListener('input', function() {
            const normalized = normalizePhone(this.value);
            _target = normalized;
            const op = OPERATOR_MAP[normalized.substring(0, 4)] || '';
            const lbl = document.getElementById('svOpLbl');
            // Tampilkan operator atau hint format
            if (lbl) {
                if (op) {
                    lbl.innerHTML = '<span style="color:var(--cp)">✓ ' + op + '</span>';
                } else if (this.value.trim() && normalized.length < 9) {
                    lbl.innerHTML = '<span style="color:#f59e0b">⚠ Format: 085xxxxxxx atau 6285xxxxxxx</span>';
                } else {
                    lbl.innerHTML = '';
                }
            }
            checkCta1();
            filterProductsByOp(op);
        });
    }

    function checkCta1() {
        const target = _target || document.getElementById('svTarget')?.value || '';
        const sku = document.getElementById('svSelSku')?.value;
        const cta = document.getElementById('svCta1');
        if (!cta) return;
        const validTarget = target.length >= 9; // min 9 digit
        cta.disabled = !(validTarget && (_sheetMeta.type === 'pasca' || sku));
    }

    function filterProductsByOp(op) {
        const cat = (_sheetMeta?.query_cat || '').toLowerCase();
        if (cat !== 'pulsa' && cat !== 'data') return;
        document.querySelectorAll('.sv-prod-card').forEach(c => {
            c.style.display = (!op || (c.dataset.info || '').toLowerCase().includes(op.toLowerCase())) ? '' : 'none';
        });
    }

    async function loadProducts(cat, isPasca) {
        const grid = document.getElementById('svProdGrid');
        if (!grid) return;
        const type = isPasca ? 'pascabayar' : 'prabayar';
        const url = `<?= base_url('api/get_products.php') ?>?cat=${encodeURIComponent(cat||'')}&type=${type}`;
        try {
            const res = await fetch(url);
            const data = await res.json();
            if (!data.length) {
                grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:16px;color:var(--cm);font-size:12px">Produk tidak tersedia.</div>`;
                return;
            }
            grid.innerHTML = data.map(p => `
        <div class="sv-prod-card" data-sku="${esc(p.sku_code)}" data-info="${(p.brand||'').toLowerCase()+' '+(p.product_name||'').toLowerCase()}" onclick="selectProd(this)">
            <span class="sv-prod-name">${esc(p.product_name)}</span>
            <span class="sv-prod-price">Rp ${numFmt(p.price_sell)}</span>
        </div>`).join('');
            checkCta1();
        } catch (e) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:16px;color:#dc2626;font-size:12px">Gagal memuat produk.</div>`;
        }
    }

    function selectProd(el) {
        document.querySelectorAll('.sv-prod-card').forEach(c => c.classList.remove('on'));
        el.classList.add('on');
        _selectedSku = el.dataset.sku;
        document.getElementById('svSelSku').value = _selectedSku;
        checkCta1();
    }

    function renderConfirm(m) {
        const target = document.getElementById('svTarget')?.value || '';
        const card = document.querySelector('.sv-prod-card.on');
        const prodName = card?.querySelector('.sv-prod-name')?.textContent || '';
        const price = card?.querySelector('.sv-prod-price')?.textContent || '';
        return `
    <div class="sv-inquiry">
        <div class="sv-iq-row"><span class="sv-iq-k">Produk</span><span class="sv-iq-v">${esc(prodName)}</span></div>
        <div class="sv-iq-row"><span class="sv-iq-k">No. Tujuan</span><span class="sv-iq-v">${esc(target)}</span></div>
        <div class="sv-iq-row sv-iq-total"><span class="sv-iq-k">Harga</span><span class="sv-iq-v">${esc(price)}</span></div>
    </div>
    <button class="sv-cta" onclick="goStep3()">${SVG.lock} Masukkan PIN</button>
    <button class="sv-cta-sec" onclick="backStep()">Kembali</button>`;
    }

    function renderInquiry() {
        const d = _inquiryData;
        return `
    <div class="sv-inquiry">
        <div class="sv-iq-row"><span class="sv-iq-k">Nama</span><span class="sv-iq-v">${esc(d.customer_name||'-')}</span></div>
        <div class="sv-iq-row"><span class="sv-iq-k">ID Pelanggan</span><span class="sv-iq-v">${esc(d.customer_no||'-')}</span></div>
        <div class="sv-iq-row sv-iq-total"><span class="sv-iq-k">Total Tagihan</span><span class="sv-iq-v">Rp ${numFmt(d.selling_price||0)}</span></div>
    </div>
    <button class="sv-cta" onclick="goStep3()">${SVG.lock} Masukkan PIN</button>
    <button class="sv-cta-sec" onclick="backStep()">Kembali</button>`;
    }

    async function goStep2() {
        const m = _sheetMeta;
        const target = normalizePhone(document.getElementById('svTarget')?.value?.trim() || _target);
        _target = target; // simpan normalized ke state
        if (!target) return;
        if (m.type === 'pasca') {
            const body = document.getElementById('svSheetBody');
            const ov = document.createElement('div');
            ov.id = 'svLoadingOv';
            ov.style.cssText = 'position:absolute;inset:0;background:rgba(255,255,255,.8);display:flex;align-items:center;justify-content:center;z-index:10';
            ov.innerHTML = '<span class="sv-spinner"></span>';
            body.style.position = 'relative';
            body.appendChild(ov);
            const fd = new FormData();
            fd.append('sku', _selectedSku || '');
            fd.append('target', target);
            try {
                const res = await fetch('<?= base_url('api/inquiry.php') ?>', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.rc === '00') {
                    _inquiryData = data;
                    _step = 2;
                    renderSheet();
                } else {
                    showToast(data.message || 'Cek tagihan gagal', 'err');
                    document.getElementById('svLoadingOv')?.remove();
                }
            } catch (e) {
                showToast('Koneksi gagal', 'err');
                document.getElementById('svLoadingOv')?.remove();
            }
        } else {
            _step = 2;
            renderSheet();
        }
    }

    function backStep() {
        _step = Math.max(1, _step - 1);
        _inquiryData = null;
        renderSheet();
    }

    function renderPin() {
        _pinVal = '';
        const keys = [1, 2, 3, 4, 5, 6, 7, 8, 9, '', '0', 'del'];
        return `
    <p style="text-align:center;font-size:13px;color:var(--cm);margin-bottom:12px">Masukkan PIN 6 digit transaksi kamu</p>
    <div class="sv-pin-wrap" id="svPinDots">
        ${[...Array(6)].map((_,i) => `<div class="sv-pin-dot" id="dot${i}"></div>`).join('')}
    </div>
    <div class="sv-numpad">
        ${keys.map(k =>
            k==='del' ? `<div class="sv-numpad-key del" onclick="pinKey('del')">${SVG.backspace}</div>`
          : k===''    ? `<div class="sv-numpad-key empty"></div>`
          :             `<div class="sv-numpad-key" onclick="pinKey('${k}')">${k}</div>`
        ).join('')}
    </div>`;
    }

    function pinKey(k) {
        if (k === 'del') _pinVal = _pinVal.slice(0, -1);
        else if (_pinVal.length < 6) _pinVal += k;
        for (let i = 0; i < 6; i++) {
            const d = document.getElementById('dot' + i);
            if (d) d.classList.toggle('filled', i < _pinVal.length);
        }
        if (_pinVal.length === 6) submitTransaction();
    }

    function submitTransaction() {
        const m = _sheetMeta;
        // Pakai _target dari state — #svTarget sudah tidak ada di DOM saat step 3 (PIN)
        const target = _target || normalizePhone(document.getElementById('svTarget')?.value?.trim() || '');
        document.getElementById('fTarget').value = target;
        document.getElementById('fSku').value = _selectedSku || '';
        document.getElementById('fPin').value = _pinVal;
        document.getElementById('fCat').value = m.query_cat || '';
        if (m.type === 'pasca' && _inquiryData) {
            document.getElementById('fRefId').value = _inquiryData.ref_id || '';
            document.getElementById('fBayarTagihan').value = '1';
            document.getElementById('fCekTagihan').value = '';
        } else {
            document.getElementById('fBayarTagihan').value = '';
            document.getElementById('fCekTagihan').value = '';
        }
        document.getElementById('svHiddenForm').action = window.location.pathname;
        document.getElementById('svHiddenForm').submit();
    }

    function goStep3() {
        _step = 3;
        renderSheet();
    }

    function esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function numFmt(n) {
        return Number(n).toLocaleString('id-ID');
    }

    const SVG_CHECK = `<svg width="16" height="16" viewBox="0 0 256 256" fill="currentColor"><path d="M229.66,77.66l-128,128a8,8,0,0,1-11.32,0l-56-56a8,8,0,0,1,11.32-11.32L96,188.69,218.34,66.34a8,8,0,0,1,11.32,11.32Z"/></svg>`;
    const SVG_WARN = `<svg width="16" height="16" viewBox="0 0 256 256" fill="currentColor"><path d="M236.8,188.09,149.35,36.22a24.76,24.76,0,0,0-42.7,0L19.2,188.09a23.51,23.51,0,0,0,0,23.72A24.35,24.35,0,0,0,40.55,224h174.9a24.35,24.35,0,0,0,21.33-12.19A23.51,23.51,0,0,0,236.8,188.09ZM120,104a8,8,0,0,1,16,0v40a8,8,0,0,1-16,0Zm8,88a12,12,0,1,1,12-12A12,12,0,0,1,128,192Z"/></svg>`;

    let _toastTimer = null;

    function showToast(msg, type) {
        const toast = document.getElementById('svToast');
        const icon = document.getElementById('svToastIcon');
        const msgEl = document.getElementById('svToastMsg');
        if (!toast) return;
        toast.className = 'sv-toast ' + (type === 'ok' ? 'ok' : 'err');
        icon.innerHTML = type === 'ok' ? SVG_CHECK : SVG_WARN;
        msgEl.textContent = msg;
        requestAnimationFrame(() => toast.classList.add('show'));
        clearTimeout(_toastTimer);
        _toastTimer = setTimeout(() => closeToast(), 3500);
    }

    function closeToast() {
        const toast = document.getElementById('svToast');
        if (toast) toast.classList.remove('show');
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>