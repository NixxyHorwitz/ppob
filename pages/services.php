<?php

/**
 * pages/services.php
 * Halaman Semua Layanan
 * - Top bar: semua item dari dashboard_menus (static)
 * - Bawahnya: kategori + item dari service_menus (dynamic)
 * - Form pembelian muncul sebagai bottom sheet modal
 */

$pageTitle = 'Semua Layanan';
require_once __DIR__ . '/../includes/header.php';

/* ── 1. Dashboard menus (static bar atas) ──────────────────── */
$dashMenus = $pdo->query(
    "SELECT * FROM dashboard_menus WHERE is_active=1 ORDER BY sort_order ASC"
)->fetchAll(PDO::FETCH_ASSOC);

/* ── 2. Service menus — ambil semua sekaligus ──────────────── */
$smRows = $pdo->query(
    "SELECT * FROM service_menus WHERE is_active=1 ORDER BY sort_order ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Pisahkan kategori & items, build tree
$smCats  = [];
$smItems = [];
foreach ($smRows as $r) {
    if ($r['row_type'] === 'category') {
        $smCats[$r['id']] = $r;
    } else {
        $smItems[$r['category_id']][] = $r;
    }
}

/* ── Helper: render icon ────────────────────────────────────── */
function renderIcon(array $m, string $size = '20px'): string
{
    $type  = $m['icon_type']  ?? 'ph';
    $value = $m['icon_value'] ?? 'ph-circle';
    $color = $m['icon_color'] ?? '#0ea5e9';
    if ($type === 'img') {
        return "<img src=\"" . htmlspecialchars($value) . "\" style=\"width:{$size};height:{$size};object-fit:contain\" alt=\"\">";
    }
    return "<i class=\"" . htmlspecialchars($value) . "\" style=\"color:{$color};font-size:{$size}\"></i>";
}

/* ── Helper: build href dengan query_cat ────────────────────── */
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
<script src="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1"></script>
<style>
    /* ══════════════════════════════════════════════════════════════
   SERVICES PAGE
══════════════════════════════════════════════════════════════ */

    /* ── Top bar ── */
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

    /* ── Static scroll bar (dashboard_menus) ── */
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

    /* ── Search bar ── */
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

    /* ── Section kategori ── */
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

    .sv-sec-more {
        font-size: 11px;
        font-weight: 700;
        color: var(--cpd);
        display: flex;
        align-items: center;
        gap: 3px;
        cursor: pointer;
        display: none;
        /* tampil jika overflow */
    }

    /* ── Grid item ── */
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

    /* ── Divider antar section ── */
    .sv-div {
        height: 8px;
    }

    /* ── Empty ── */
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

    /* ── Bottom sheet modal ── */
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
        padding: 16px 18px;
    }

    /* Form fields */
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

    .sv-sel {
        width: 100%;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 11px;
        padding: 11px 13px;
        font-size: 13px;
        font-weight: 600;
        font-family: var(--f);
        color: var(--ct);
        outline: none;
        appearance: none;
        cursor: pointer;
        transition: border-color .15s;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
    }

    .sv-sel:focus {
        border-color: var(--cp);
        background-color: #fff;
    }

    /* Operator detect label */
    .sv-op-lbl {
        font-size: 11px;
        font-weight: 700;
        color: var(--cp);
        min-height: 18px;
        margin-top: 4px;
    }

    /* Product grid in sheet */
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

    /* Inquiry box */
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

    /* PIN field */
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
        color: var(--ct);
        font-weight: 900;
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

    /* Numpad */
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
        font-size: 20px;
    }

    .sv-numpad-key.empty {
        background: transparent;
        border-color: transparent;
        pointer-events: none;
    }

    /* Step indicator */
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

    /* CTA button */
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

    .sv-cta.green {
        background: #16a34a;
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

    /* Bottom spacing */
    .sv-bottom {
        height: 16px;
    }
</style>

<!-- TOP BAR -->
<div class="sv-topbar">
    <a href="<?= base_url('dashboard.php') ?>" class="sv-topbar-back">
        <i class="ph ph-caret-left"></i>
    </a>
    <div class="sv-topbar-title">Semua Layanan</div>
    <button class="sv-topbar-srch" onclick="document.getElementById('svSearch').focus()">
        <i class="ph ph-magnifying-glass"></i>
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
                        <div class="sv-static-ico">
                            <?php if (($dm['icon_type'] ?? 'ph') === 'img'): ?>
                                <img src="<?= htmlspecialchars($dm['icon_value']) ?>" style="width:22px;height:22px;object-fit:contain" alt="">
                            <?php else: ?>
                                <i class="<?= htmlspecialchars($dm['icon_value'] ?? 'ph ph-circle') ?>"></i>
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
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" id="svSearch" placeholder="Cari layanan...">
    </div>
</div>

<!-- DYNAMIC SERVICE SECTIONS -->
<div id="svList">
    <?php if (empty($smCats)): ?>
        <div class="sv-empty">
            <i class="ph ph-squares-four"></i>
            <p>Belum ada layanan.<br>Tambah melalui menu Admin.</p>
        </div>
    <?php else: ?>
        <?php foreach ($smCats as $catId => $cat): ?>
            <?php
            $items = $smItems[$catId] ?? [];
            if (empty($items)) continue;
            ?>
            <div class="sv-section" data-cat="<?= htmlspecialchars($cat['cat_slug']) ?>">
                <div class="sv-sec-hd">
                    <span class="sv-sec-title"><?= htmlspecialchars($cat['cat_name']) ?></span>
                </div>
                <div class="sv-grid">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $href = menuHref($item);
                        // Jika href menuju prabayar/pascabayar → pakai sheet modal
                        $useModal = (
                            str_contains($item['href'] ?? '', 'prabayar') ||
                            str_contains($item['href'] ?? '', 'pascabayar')
                        );
                        ?>
                        <<?= $useModal ? 'div' : 'a' ?>
                            class="sv-item"
                            <?php if ($useModal): ?>
                            onclick="openSheet(<?= htmlspecialchars(json_encode([
                                                    'name'      => $item['name'],
                                                    'icon_type' => $item['icon_type'],
                                                    'icon_value' => $item['icon_value'],
                                                    'icon_bg'   => $item['icon_bg'],
                                                    'icon_color' => $item['icon_color'],
                                                    'href'      => $item['href'],
                                                    'query_cat' => $item['query_cat'],
                                                    'type'      => str_contains($item['href'], 'pascabayar') ? 'pasca' : 'prabayar',
                                                ])) ?>)"
                            data-search="<?= strtolower(htmlspecialchars($item['name'])) ?>"
                            <?php else: ?>
                            href="<?= $href ?>"
                            data-search="<?= strtolower(htmlspecialchars($item['name'])) ?>"
                            <?php endif; ?>>
                            <div class="sv-ico" style="background:<?= htmlspecialchars($item['icon_bg']) ?>">
                                <?php if ($item['icon_type'] === 'img'): ?>
                                    <img src="<?= htmlspecialchars($item['icon_value']) ?>" alt="">
                                <?php else: ?>
                                    <i class="<?= htmlspecialchars($item['icon_value']) ?>" style="color:<?= htmlspecialchars($item['icon_color']) ?>;font-size:22px"></i>
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

    <!-- No result -->
    <div id="svNoResult" class="sv-empty" style="display:none">
        <i class="ph ph-magnifying-glass"></i>
        <p>Layanan tidak ditemukan.</p>
    </div>
</div>

<div class="sv-bottom"></div>

<!-- ══════════════════════════════════════════════════════════════
     BOTTOM SHEET MODAL PEMBELIAN
══════════════════════════════════════════════════════════════ -->
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
            <i class="ph ph-x"></i>
        </button>
    </div>
    <div class="sv-sheet-body" id="svSheetBody">
        <!-- Isi dirender oleh JS -->
    </div>
</div>

<!-- Hidden form for POST submit -->
<form method="POST" id="svHiddenForm" style="display:none">
    <input type="hidden" name="sku" id="fSku">
    <input type="hidden" name="target" id="fTarget">
    <input type="hidden" name="pin_transaksi" id="fPin">
    <input type="hidden" name="ref_id" id="fRefId">
    <input type="hidden" name="beli" value="1">
    <input type="hidden" name="cek_tagihan" id="fCekTagihan" value="">
    <input type="hidden" name="bayar_tagihan" id="fBayarTagihan" value="">
</form>

<script>
    /* ══════════════════════════════════════════════════════════════
   SERVICES PAGE JS
══════════════════════════════════════════════════════════════ */

    // ── Search filter ─────────────────────────────────────────────
    document.getElementById('svSearch').addEventListener('input', function() {
        const kw = this.value.toLowerCase();
        let any = false;
        document.querySelectorAll('.sv-item').forEach(el => {
            const match = !kw || (el.dataset.search || '').includes(kw);
            el.style.display = match ? '' : 'none';
            if (match) any = true;
        });
        // Hide section if all items hidden
        document.querySelectorAll('.sv-section').forEach(sec => {
            const vis = [...sec.querySelectorAll('.sv-item')].some(el => el.style.display !== 'none');
            sec.style.display = vis ? '' : 'none';
        });
        document.getElementById('svNoResult').style.display = any ? 'none' : '';
    });

    // ── State ─────────────────────────────────────────────────────
    let _sheetMeta = null; // {name, href, query_cat, type, icon_*}
    let _step = 1; // 1=input form | 2=confirm/inquiry | 3=pin
    let _selectedSku = '';
    let _pinVal = '';
    let _inquiryData = null;

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

    // ── Sheet open/close ──────────────────────────────────────────
    function openSheet(meta) {
        _sheetMeta = meta;
        _step = 1;
        _selectedSku = '';
        _pinVal = '';
        _inquiryData = null;
        renderSheet();
        document.getElementById('svBg').classList.add('show');
        requestAnimationFrame(() => document.getElementById('svSheet').classList.add('show'));
    }

    function closeSheet() {
        document.getElementById('svSheet').classList.remove('show');
        setTimeout(() => document.getElementById('svBg').classList.remove('show'), 300);
    }
    // Swipe down
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

    // ── Render sheet content by step ─────────────────────────────
    function renderSheet() {
        const m = _sheetMeta;
        if (!m) return;

        // Head icon
        const ico = document.getElementById('svSheetIco');
        ico.style.background = (m.icon_bg || '#e0f2fe');
        if (m.icon_type === 'img') {
            ico.innerHTML = `<img src="${esc(m.icon_value)}" style="width:26px;height:26px;object-fit:contain" alt="">`;
        } else {
            ico.innerHTML = `<i class="${esc(m.icon_value)}" style="color:${esc(m.icon_color)};font-size:22px"></i>`;
        }
        document.getElementById('svSheetTitle').textContent = m.name || 'Layanan';

        // Step indicator
        const stepHtml = `<div class="sv-steps">
        <div class="sv-step ${_step >= 1 ? 'on' : ''}"></div>
        <div class="sv-step ${_step >= 2 ? 'on' : ''}"></div>
        <div class="sv-step ${_step >= 3 ? 'on' : ''}"></div>
    </div>`;
        const sub = ['Form Input', 'Konfirmasi', 'Masukkan PIN'];
        document.getElementById('svSheetSub').textContent = sub[_step - 1] || '';

        const body = document.getElementById('svSheetBody');

        if (_step === 1) {
            body.innerHTML = stepHtml + renderStep1(m);
            bindStep1();
        } else if (_step === 2) {
            body.innerHTML = stepHtml + (_inquiryData ? renderInquiry() : renderConfirm(m));
            bindStep2();
        } else if (_step === 3) {
            body.innerHTML = stepHtml + renderPin();
            bindPin();
        }
    }

    // ── Step 1: Form input ────────────────────────────────────────
    function renderStep1(m) {
        // Load produk via AJAX (inline PHP menyediakan data)
        return `
    <div class="sv-field">
        <label>Nomor / ID Pelanggan</label>
        <input type="text" class="sv-inp" id="svTarget" placeholder="Masukkan nomor..." inputmode="numeric">
        <div class="sv-op-lbl" id="svOpLbl"></div>
    </div>
    <div class="sv-field">
        <label>Pilih Produk</label>
        <div class="sv-prod-grid" id="svProdGrid">
            <div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--cm);font-size:12px">
                <i class="ph ph-circle-notch" style="animation:spin .8s linear infinite;font-size:20px;display:block;margin-bottom:6px"></i>
                Memuat produk...
            </div>
        </div>
        <input type="hidden" id="svSelSku">
    </div>
    <button class="sv-cta" id="svCta1" onclick="goStep2()" disabled>
        ${m.type === 'pasca' ? '<i class="ph ph-magnifying-glass"></i> Cek Tagihan' : '<i class="ph ph-arrow-right"></i> Lanjutkan'}
    </button>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>`;
    }

    function bindStep1() {
        const m = _sheetMeta;
        // Load products
        loadProducts(m.query_cat, m.type === 'pasca');

        // Operator detect
        const ti = document.getElementById('svTarget');
        if (ti) {
            ti.addEventListener('input', function() {
                const pfx = this.value.substring(0, 4);
                const op = OPERATOR_MAP[pfx] || '';
                const lbl = document.getElementById('svOpLbl');
                if (lbl) lbl.textContent = op;
                checkCta1();
                // Filter produk by operator
                filterProductsByOp(op);
            });
        }
    }

    function checkCta1() {
        const target = document.getElementById('svTarget')?.value;
        const sku = document.getElementById('svSelSku')?.value;
        const cta = document.getElementById('svCta1');
        const m = _sheetMeta;
        if (!cta) return;
        // pasca: cukup target; prabayar: butuh sku juga
        cta.disabled = !(target && (m.type === 'pasca' || sku));
    }

    function filterProductsByOp(op) {
        document.querySelectorAll('.sv-prod-card').forEach(c => {
            if (!op) {
                c.style.display = '';
                return;
            }
            const info = (c.dataset.info || '').toLowerCase();
            c.style.display = info.includes(op.toLowerCase()) ? '' : 'none';
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
            // Recheck CTA after load
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

    // ── Step 2: Confirm / Inquiry ─────────────────────────────────
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
    <button class="sv-cta" onclick="goStep3()"><i class="ph ph-lock-simple"></i> Masukkan PIN</button>
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
    <button class="sv-cta" onclick="goStep3()"><i class="ph ph-lock-simple"></i> Masukkan PIN</button>
    <button class="sv-cta-sec" onclick="backStep()">Kembali</button>`;
    }

    function bindStep2() {
        /* nothing extra needed */
    }

    async function goStep2() {
        const m = _sheetMeta;
        const target = document.getElementById('svTarget')?.value?.trim() || '';
        if (!target) return;

        if (m.type === 'pasca') {
            // AJAX cek tagihan
            const body = document.getElementById('svSheetBody');
            body.innerHTML += `<div id="svLoadingOv" style="position:absolute;inset:0;background:rgba(255,255,255,.8);display:flex;align-items:center;justify-content:center;border-radius:0 0 24px 24px;z-index:10"><i class="ph ph-circle-notch" style="font-size:28px;color:var(--cp);animation:spin .8s linear infinite"></i></div>`;

            const fd = new FormData();
            fd.append('sku', _selectedSku || document.getElementById('svSelSku')?.value || '');
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

    // ── Step 3: PIN ───────────────────────────────────────────────
    function renderPin() {
        _pinVal = '';
        return `
    <p style="text-align:center;font-size:13px;color:var(--cm);margin-bottom:12px">Masukkan PIN 6 digit transaksi kamu</p>
    <div class="sv-pin-wrap" id="svPinDots">
        ${[...Array(6)].map((_,i) => `<div class="sv-pin-dot" id="dot${i}"></div>`).join('')}
    </div>
    <div class="sv-numpad" id="svNumpad">
        ${[1,2,3,4,5,6,7,8,9,'','0','del'].map(k =>
            k === 'del' ?
                `<div class="sv-numpad-key del" onclick="pinKey('del')"><i class="ph ph-backspace"></i></div>` :
            k === '' ?
                `<div class="sv-numpad-key empty"></div>` :
                `<div class="sv-numpad-key" onclick="pinKey('${k}')">${k}</div>`
        ).join('')}
    </div>`;
    }

    function bindPin() {
        /* handled by onclick */
    }

    function pinKey(k) {
        if (k === 'del') {
            _pinVal = _pinVal.slice(0, -1);
        } else if (_pinVal.length < 6) {
            _pinVal += k;
        }
        // Update dots
        for (let i = 0; i < 6; i++) {
            const d = document.getElementById('dot' + i);
            if (d) d.classList.toggle('filled', i < _pinVal.length);
        }
        if (_pinVal.length === 6) submitTransaction();
    }

    function submitTransaction() {
        const m = _sheetMeta;
        const target = document.getElementById('svTarget')?.value?.trim() || '';
        const sku = _selectedSku || '';

        document.getElementById('fTarget').value = target;
        document.getElementById('fSku').value = sku;
        document.getElementById('fPin').value = _pinVal;

        if (m.type === 'pasca' && _inquiryData) {
            document.getElementById('fRefId').value = _inquiryData.ref_id || '';
            document.getElementById('fBayarTagihan').value = '1';
            document.getElementById('fCekTagihan').value = '';
        } else {
            document.getElementById('fCekTagihan').value = '';
            document.getElementById('fBayarTagihan').value = '';
        }

        document.getElementById('svHiddenForm').action = m.href + (m.query_cat ? '?cat=' + encodeURIComponent(m.query_cat) : '');
        document.getElementById('svHiddenForm').submit();
    }

    // ── Helpers ───────────────────────────────────────────────────
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

    // Toast
    function showToast(msg, type) {
        const el = document.createElement('div');
        el.style.cssText = `position:fixed;top:14px;left:50%;transform:translateX(-50%);
        background:${type==='err'?'#7f1d1d':'#064e3b'};color:${type==='err'?'#fca5a5':'#6ee7b7'};
        padding:8px 18px;border-radius:10px;font-size:12px;font-weight:700;z-index:9999;
        box-shadow:0 6px 20px rgba(0,0,0,.3);max-width:340px;text-align:center`;
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transition = 'opacity .4s';
        }, 2600);
        setTimeout(() => el.remove(), 3000);
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>