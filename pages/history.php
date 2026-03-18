<?php

/**
 * pages/history.php
 * Riwayat Transaksi — dipindah dari modules/user/riwayat
 * Include universal header/footer dari root/includes/
 */

$pageTitle = 'Riwayat Transaksi';

require_once __DIR__ . '/../includes/header.php';

/* ── Query transaksi user ──────────────────────────────────────────── */
$stmt = $pdo->prepare("
    SELECT t.*, p.product_name, p.category
    FROM transactions t
    LEFT JOIN products p
           ON LOWER(TRIM(t.sku_code)) = LOWER(TRIM(p.sku_code))
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$userId]);
$history = $stmt->fetchAll();

/* ── Query topup history ───────────────────────────────────────────── */
$stmtTopup = $pdo->prepare("
    SELECT * FROM topup_history
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmtTopup->execute([$userId]);
$topupHistory = $stmtTopup->fetchAll();

// Topup expired jika pending > 24 jam
function isTopupExpired(string $createdAt): bool
{
    return (time() - strtotime($createdAt)) > 86400;
}

/* ── Group by bulan ────────────────────────────────────────────────── */
$grouped = [];
foreach ($history as $row) {
    $key = date('Y-m', strtotime($row['created_at']));
    $grouped[$key][] = $row;
}

/* ── Helper: icon & label kategori ────────────────────────────────── */
function txIcon(string $cat, string $sku): array
{
    $cat = strtolower(trim($cat));
    $sku = strtolower($sku);

    if (str_contains($cat, 'pulsa') || str_contains($sku, 'pulsa'))   return ['ph ph-device-mobile-speaker', '#0ea5e9'];
    if (str_contains($cat, 'data')  || str_contains($sku, 'data'))    return ['ph ph-wifi-high', '#6366f1'];
    if (str_contains($cat, 'listrik') || str_contains($sku, 'pln'))    return ['ph ph-lightning', '#f59e0b'];
    if (str_contains($cat, 'air')   || str_contains($sku, 'pdam'))    return ['ph ph-drop', '#38bdf8'];
    if (str_contains($cat, 'game')  || str_contains($sku, 'game'))    return ['ph ph-game-controller', '#a855f7'];
    if (str_contains($cat, 'tv')    || str_contains($sku, 'tv'))      return ['ph ph-television', '#ec4899'];
    if (str_contains($cat, 'bpjs')  || str_contains($sku, 'bpjs'))    return ['ph ph-first-aid-kit', '#10b981'];
    if (str_contains($cat, 'transfer') || str_contains($sku, 'transfer')) return ['ph ph-arrows-left-right', '#f97316'];
    if (str_contains($cat, 'topup') || str_contains($sku, 'topup'))   return ['ph ph-arrow-circle-up', '#22c55e'];
    return ['ph ph-receipt', '#94a3b8'];
}

function txColor(string $status): array
{
    $s = strtolower(trim($status));
    if ($s === 'success' || $s === 'sukses') return ['#dcfce7', '#16a34a', 'Sukses'];
    if ($s === 'failed'  || $s === 'gagal')  return ['#fee2e2', '#dc2626', 'Gagal'];
    return ['#fef9c3', '#ca8a04', 'Pending'];
}

function amountSign(string $status): string
{
    $s = strtolower(trim($status));
    if ($s === 'success' || $s === 'sukses') return '-';
    return '';
}

function monthLabel(string $ym): string
{
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    [$y, $m] = explode('-', $ym);
    $now  = date('Y-m');
    $prev = date('Y-m', strtotime('last month'));
    if ($ym === $now)  return 'Bulan Ini';
    if ($ym === $prev) return 'Bulan Lalu';
    return $months[(int)$m - 1] . ' ' . $y;
}
?>

<style>
    /* ══════════════════════════════════════════════════════════════
   RIWAYAT TRANSAKSI — Activity-style, warna dari CSS vars DB
══════════════════════════════════════════════════════════════ */

    /* ── Top bar ── */
    .hist-topbar {
        position: sticky;
        top: 0;
        z-index: 50;
        background: var(--cp);
        padding: 0 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        height: 54px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .12);
    }

    .hist-topbar-back {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        flex-shrink: 0;
        transition: background .15s;
        cursor: pointer;
    }

    .hist-topbar-back:active {
        background: rgba(255, 255, 255, .3);
    }

    .hist-topbar-title {
        flex: 1;
        font-size: 15px;
        font-weight: 800;
        color: #fff;
        letter-spacing: -.2px;
    }

    .hist-topbar-dl {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        flex-shrink: 0;
        cursor: pointer;
        transition: background .15s;
    }

    .hist-topbar-dl:active {
        background: rgba(255, 255, 255, .3);
    }

    /* ── Search bar ── */
    .hist-search-wrap {
        background: var(--cp);
        padding: 0 14px 14px;
    }

    .hist-search {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
        border-radius: 12px;
        padding: 9px 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
    }

    .hist-search i {
        color: var(--cp);
        font-size: 16px;
        flex-shrink: 0;
    }

    .hist-search input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 13px;
        font-family: var(--f);
        color: var(--ct);
        background: transparent;
    }

    .hist-search input::placeholder {
        color: var(--cm);
    }

    .hist-filter-btn {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: var(--cpl);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--cpd);
        font-size: 14px;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .15s;
    }

    .hist-filter-btn.on,
    .hist-filter-btn:active {
        background: var(--cp);
        color: #fff;
    }

    /* ── Chips filter ── */
    .hist-chips {
        display: flex;
        gap: 6px;
        padding: 0 14px 12px;
        overflow-x: auto;
        scrollbar-width: none;
        background: var(--cbg);
        padding-top: 12px;
    }

    .hist-chips::-webkit-scrollbar {
        display: none;
    }

    .hchip {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 99px;
        border: 1.5px solid rgba(0, 0, 0, .09);
        background: var(--cc);
        font-size: 11px;
        font-weight: 700;
        color: var(--cm);
        white-space: nowrap;
        cursor: pointer;
        transition: all .15s;
        flex-shrink: 0;
    }

    .hchip.on {
        background: var(--cp);
        border-color: var(--cp);
        color: #fff;
    }

    .hchip i {
        font-size: 12px;
    }

    /* ── Month group ── */
    .hist-month {
        padding: 14px 0 0;
    }

    .hist-month-hd {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        padding: 0 14px;
    }

    .hist-month-lbl {
        font-size: 12px;
        font-weight: 800;
        color: var(--ct);
        letter-spacing: -.1px;
    }

    .hist-month-stmt {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 700;
        color: var(--cp);
    }

    .hist-month-stmt i {
        font-size: 13px;
    }

    /* ── List group card ── */
    .hist-group {
        background: var(--cc);
        border-top: 1px solid rgba(0, 0, 0, .05);
        border-bottom: 1px solid rgba(0, 0, 0, .05);
        overflow: hidden;
    }

    /* ── Transaction row ── */
    .hist-row {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        padding: 13px 14px;
        cursor: pointer;
        transition: background .12s;
        border-bottom: 1px solid rgba(0, 0, 0, .04);
        position: relative;
    }

    .hist-row:last-child {
        border-bottom: none;
    }

    .hist-row:active {
        background: rgba(0, 0, 0, .025);
    }

    /* Dot — kategori warna */
    .hist-dot {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .hist-body {
        flex: 1;
        min-width: 0;
    }

    .hist-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--ct);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 2px;
    }

    .hist-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .hist-date {
        font-size: 11px;
        color: var(--cm);
        flex-shrink: 0;
    }

    .hist-target {
        font-size: 11px;
        color: var(--cm);
        display: flex;
        align-items: center;
        gap: 3px;
    }

    .hist-target i {
        font-size: 10px;
    }

    .hist-sku {
        font-size: 10px;
        font-family: ui-monospace, monospace;
        color: var(--cm);
        background: rgba(0, 0, 0, .04);
        padding: 1px 5px;
        border-radius: 4px;
    }

    /* Detail baris kedua */
    .hist-detail {
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
    }

    .hist-sn {
        font-size: 10px;
        font-family: ui-monospace, monospace;
        color: var(--cp);
        background: var(--cpl);
        padding: 2px 7px;
        border-radius: 5px;
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .hist-badge {
        font-size: 9.5px;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 99px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        flex-shrink: 0;
    }

    .hist-badge i {
        font-size: 8px;
    }

    /* Right side: amount */
    .hist-right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 5px;
        flex-shrink: 0;
    }

    .hist-amount {
        font-size: 13px;
        font-weight: 800;
        letter-spacing: -.3px;
        white-space: nowrap;
    }

    .hist-amount.cr {
        color: #16a34a;
    }

    /* credit / masuk */
    .hist-amount.db {
        color: var(--ct);
    }

    /* debit / keluar */
    .hist-amount.fail {
        color: #dc2626;
        text-decoration: line-through;
    }

    /* Chevron */
    .hist-chev {
        font-size: 13px;
        color: rgba(0, 0, 0, .15);
        margin-top: 2px;
    }

    /* ── Empty state ── */
    .hist-empty {
        padding: 60px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .hist-empty-ic {
        width: 64px;
        height: 64px;
        border-radius: 20px;
        background: var(--cpl);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: var(--cp);
        margin-bottom: 4px;
    }

    .hist-empty h6 {
        font-size: 14px;
        font-weight: 800;
        color: var(--ct);
    }

    .hist-empty p {
        font-size: 12px;
        color: var(--cm);
        line-height: 1.6;
    }

    /* ── Detail sheet (bottom modal) ── */
    .hist-sheet-bg {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        z-index: 200;
        display: none;
        backdrop-filter: blur(2px);
    }

    .hist-sheet {
        position: fixed;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        max-width: 480px;
        background: var(--cc);
        border-radius: 22px 22px 0 0;
        padding: 0 0 calc(env(safe-area-inset-bottom) + 20px);
        z-index: 201;
        box-shadow: 0 -8px 40px rgba(0, 0, 0, .18);
        transition: transform .28s cubic-bezier(.4, 0, .2, 1);
        transform: translateX(-50%) translateY(100%);
    }

    .hist-sheet.show {
        transform: translateX(-50%) translateY(0);
    }

    .hist-sheet-bg.show {
        display: block;
    }

    .hist-sheet-pull {
        width: 36px;
        height: 4px;
        background: rgba(0, 0, 0, .1);
        border-radius: 99px;
        margin: 12px auto 18px;
    }

    .hist-sheet-head {
        padding: 0 18px 14px;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .hist-sheet-icon {
        width: 46px;
        height: 46px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }

    .hist-sheet-nm {
        font-size: 14px;
        font-weight: 800;
        color: var(--ct);
    }

    .hist-sheet-sub {
        font-size: 11px;
        color: var(--cm);
        margin-top: 2px;
    }

    .hist-sheet-body {
        padding: 14px 18px calc(84px + env(safe-area-inset-bottom));
    }

    .hist-kv {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 9px 0;
        border-bottom: 1px solid rgba(0, 0, 0, .04);
        gap: 10px;
    }

    .hist-kv:last-child {
        border-bottom: none;
    }

    .hist-kv-k {
        font-size: 12px;
        color: var(--cm);
        flex-shrink: 0;
    }

    .hist-kv-v {
        font-size: 12px;
        font-weight: 700;
        color: var(--ct);
        text-align: right;
        word-break: break-all;
    }

    .hist-kv-v.mono {
        font-family: ui-monospace, monospace;
        font-size: 11px;
        color: var(--cp);
    }

    /* No history chip count */
    .hist-count {
        font-size: 11px;
        font-weight: 700;
        color: var(--cm);
        padding: 2px 8px;
        background: rgba(0, 0, 0, .04);
        border-radius: 99px;
    }

    /* ── Filter pills bar ── */
    .hist-filter-bar {
        display: none;
        padding: 0 14px 12px;
        background: var(--cbg);
        gap: 6px;
        flex-wrap: wrap;
    }

    .hist-filter-bar.show {
        display: flex;
    }

    /* Spacing at bottom for nav */
    .hist-bottom-space {
        height: 16px;
    }

    /* ── Filter modal ── */
    .hist-fmodal-bg {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        backdrop-filter: blur(2px);
        z-index: 200;
        display: none;
    }

    .hist-fmodal-bg.show {
        display: block;
    }

    .hist-fmodal {
        position: fixed;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%) translateY(100%);
        width: 100%;
        max-width: 480px;
        background: var(--cc);
        border-radius: 22px 22px 0 0;
        z-index: 201;
        padding: 0 0 calc(84px + env(safe-area-inset-bottom));
        transition: transform .3s cubic-bezier(.4, 0, .2, 1);
        box-shadow: 0 -8px 32px rgba(0, 0, 0, .15);
    }

    .hist-fmodal.show {
        transform: translateX(-50%) translateY(0);
    }

    .hist-fmodal-pull {
        width: 36px;
        height: 4px;
        background: rgba(0, 0, 0, .1);
        border-radius: 99px;
        margin: 12px auto 0;
    }

    .hist-fmodal-head {
        padding: 14px 18px 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
    }

    .hist-fmodal-title {
        font-size: 14px;
        font-weight: 800;
        color: var(--ct);
    }

    .hist-fmodal-reset {
        font-size: 12px;
        font-weight: 700;
        color: var(--cp);
        cursor: pointer;
    }

    .hist-fmodal-body {
        padding: 16px 18px 0;
    }

    .hist-fmodal-lbl {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--cm);
        margin-bottom: 10px;
    }

    .hist-fmodal-opts {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
    }

    .hist-fopt {
        padding: 7px 14px;
        border-radius: 99px;
        border: 1.5px solid #e2e8f0;
        background: var(--cc);
        font-size: 12px;
        font-weight: 700;
        color: var(--cm);
        cursor: pointer;
        transition: all .15s;
    }

    .hist-fopt.on {
        background: var(--cp);
        border-color: var(--cp);
        color: #fff;
    }

    .hist-fapply {
        width: 100%;
        padding: 13px;
        border: none;
        border-radius: 13px;
        background: var(--cp);
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        font-family: var(--f);
        cursor: pointer;
        margin-top: 4px;
    }
</style>

<!-- TOP BAR -->
<div class="hist-topbar">
    <a href="<?= dirname($_SERVER['PHP_SELF']) ?>/dashboard.php" class="hist-topbar-back">
        <i class="ph ph-caret-left"></i>
    </a>
    <div class="hist-topbar-title">Aktivitas</div>
    <button class="hist-topbar-dl" id="btnDownload" title="Download">
        <i class="ph ph-download-simple"></i>
    </button>
</div>

<!-- SEARCH BAR (in primary color header) -->
<div class="hist-search-wrap">
    <div class="hist-search">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Cari pembayaran...">
        <button class="hist-filter-btn" id="btnFilter" title="Filter">
            <i class="ph ph-sliders-horizontal"></i>
        </button>
    </div>
</div>

<!-- FILTER CHIPS -->
<div class="hist-chips" id="statusChips">
    <div class="hchip on" data-tab="all" data-status="all"><i class="ph ph-list"></i>Semua</div>
    <div class="hchip" data-tab="all" data-status="success"><i class="ph ph-check-circle"></i>Sukses</div>
    <div class="hchip" data-tab="all" data-status="pending"><i class="ph ph-clock"></i>Pending</div>
    <div class="hchip" data-tab="all" data-status="failed"><i class="ph ph-x-circle"></i>Gagal</div>
    <div class="hchip" data-tab="topup" data-status="all"><i class="ph ph-arrow-circle-up"></i>Top Up</div>
</div>

<!-- LIST TRANSAKSI -->
<div id="histList">
    <?php if (empty($history)): ?>
        <div class="hist-empty">
            <div class="hist-empty-ic"><i class="ph ph-receipt"></i></div>
            <h6>Belum Ada Transaksi</h6>
            <p>Transaksi yang kamu lakukan<br>akan muncul di sini.</p>
        </div>
    <?php else: ?>
        <?php foreach ($grouped as $ym => $rows): ?>
            <div class="hist-month" data-month="<?= htmlspecialchars($ym) ?>">
                <div class="hist-month-hd">
                    <span class="hist-month-lbl"><?= monthLabel($ym) ?></span>
                    <span class="hist-count"><?= count($rows) ?> transaksi</span>
                </div>
                <div class="hist-group">
                    <?php foreach ($rows as $tx):
                        [$ic, $icClr]    = txIcon($tx['category'] ?? '', $tx['sku_code'] ?? '');
                        [$bgBadge, $fgBadge, $statusLabel] = txColor($tx['status'] ?? '');
                        $isSuccess  = in_array(strtolower($tx['status']), ['success', 'sukses']);
                        $isFailed   = in_array(strtolower($tx['status']), ['failed', 'gagal']);
                        $prodName   = htmlspecialchars($tx['product_name'] ?? $tx['sku_code'] ?? '-');
                        $target     = htmlspecialchars($tx['target'] ?? '-');
                        $sku        = htmlspecialchars($tx['sku_code'] ?? '-');
                        $sn         = htmlspecialchars($tx['sn'] ?? '');
                        $amount     = number_format((float)($tx['amount'] ?? 0), 0, ',', '.');
                        $created    = strtotime($tx['created_at']);
                        $dateFmt    = date('d M Y', $created);
                        $timeFmt    = date('H:i', $created);
                        $statusRaw  = strtolower($tx['status'] ?? 'pending');
                    ?>
                        <div class="hist-row"
                            data-status="<?= $statusRaw === 'sukses' ? 'success' : ($statusRaw === 'gagal' ? 'failed' : $statusRaw) ?>"
                            data-search="<?= strtolower($prodName . $target . $sku . $sn) ?>"
                            onclick="showSheet(this)"
                            data-prodname="<?= $prodName ?>"
                            data-target="<?= $target ?>"
                            data-sku="<?= $sku ?>"
                            data-sn="<?= $sn ?>"
                            data-amount="<?= $amount ?>"
                            data-date="<?= $dateFmt ?>"
                            data-time="<?= $timeFmt ?>"
                            data-status-label="<?= htmlspecialchars($statusLabel) ?>"
                            data-ic="<?= $ic ?>"
                            data-ic-clr="<?= $icClr ?>"
                            data-bg-badge="<?= $bgBadge ?>"
                            data-fg-badge="<?= $fgBadge ?>"
                            data-is-success="<?= $isSuccess ? '1' : '0' ?>"
                            data-is-failed="<?= $isFailed ? '1' : '0' ?>">
                            <!-- Icon kategori -->
                            <div class="hist-dot" style="background:<?= $icClr ?>18;color:<?= $icClr ?>">
                                <i class="<?= $ic ?>"></i>
                            </div>

                            <!-- Body -->
                            <div class="hist-body">
                                <div class="hist-name"><?= $prodName ?></div>
                                <div class="hist-meta">
                                    <span class="hist-date"><?= $dateFmt ?> • <?= $timeFmt ?> WIB</span>
                                    <?php if ($target && $target !== '-'): ?>
                                        <span class="hist-target"><i class="ph ph-device-mobile"></i><?= $target ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="hist-detail">
                                    <!-- Status badge -->
                                    <span class="hist-badge"
                                        style="background:<?= $bgBadge ?>;color:<?= $fgBadge ?>">
                                        <?php if ($isSuccess): ?><i class="ph ph-check"></i>
                                        <?php elseif ($isFailed): ?><i class="ph ph-x"></i>
                                        <?php else: ?><i class="ph ph-clock"></i><?php endif; ?>
                                        <?= $statusLabel ?>
                                    </span>
                                    <!-- SKU -->
                                    <span class="hist-sku"><?= $sku ?></span>
                                    <!-- SN jika ada -->
                                    <?php if ($sn): ?>
                                        <span class="hist-sn"><?= mb_strimwidth($sn, 0, 22, '…') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Amount + chevron -->
                            <div class="hist-right">
                                <span class="hist-amount <?= $isFailed ? 'fail' : ($isSuccess ? 'db' : 'db') ?>">
                                    <?= $isFailed ? '' : '-' ?>Rp <?= $amount ?>
                                </span>
                                <i class="ph ph-caret-right hist-chev"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- ── TOPUP HISTORY (di dalam histList) ─────────────────────── -->
    <div id="topupSection" style="display:none">
        <?php if (!empty($topupHistory)): ?>
            <div class="hist-month" style="padding-top:16px">
                <div class="hist-month-hd">
                    <span class="hist-month-lbl">Riwayat Top Up Saldo</span>
                    <span class="hist-count"><?= count($topupHistory) ?> transaksi</span>
                </div>
                <div class="hist-group">
                    <?php foreach ($topupHistory as $tu):
                        $tuStatus   = strtolower($tu['status'] ?? 'pending');
                        $tuSuccess  = $tuStatus === 'success';
                        $tuFailed   = $tuStatus === 'failed';
                        $tuPending  = $tuStatus === 'pending';
                        $tuExpired  = $tuPending && isTopupExpired($tu['created_at']);
                        [$bgBadge, $fgBadge, $statusLabel] = txColor($tu['status'] ?? 'pending');
                        if ($tuExpired) {
                            $bgBadge = '#f1f5f9';
                            $fgBadge = '#94a3b8';
                            $statusLabel = 'Kedaluwarsa';
                        }
                        $amount     = number_format((float)($tu['amount_original'] ?? 0), 0, ',', '.');
                        $created    = strtotime($tu['created_at']);
                        $dateFmt    = date('d M Y', $created);
                        $timeFmt    = date('H:i', $created);
                        $invoiceUrl = base_url('pages/invoice.php?ext_id=' . urlencode($tu['external_id']));
                        $canPay     = $tuPending && !$tuExpired;
                    ?>
                        <div class="hist-row"
                            data-topup="1"
                            data-status="<?= $tuStatus ?>"
                            data-search="top up saldo qris <?= strtolower($tu['external_id']) ?>"
                            onclick="showTopupSheet(this)"
                            data-extid="<?= htmlspecialchars($tu['external_id']) ?>"
                            data-amount="<?= $amount ?>"
                            data-amount-total="<?= number_format((float)($tu['amount'] ?? 0), 0, ',', '.') ?>"
                            data-date="<?= $dateFmt ?>"
                            data-time="<?= $timeFmt ?>"
                            data-method="<?= htmlspecialchars($tu['payment_method'] ?? 'QRIS') ?>"
                            data-status-label="<?= htmlspecialchars($statusLabel) ?>"
                            data-bg-badge="<?= $bgBadge ?>"
                            data-fg-badge="<?= $fgBadge ?>"
                            data-can-pay="<?= $canPay ? '1' : '0' ?>"
                            data-invoice-url="<?= htmlspecialchars($invoiceUrl) ?>">
                            <!-- Icon -->
                            <div class="hist-dot" style="background:#dcfce7;color:#16a34a">
                                <i class="ph ph-arrow-circle-up"></i>
                            </div>
                            <!-- Body -->
                            <div class="hist-body">
                                <div class="hist-name">Top Up Saldo</div>
                                <div class="hist-meta">
                                    <span class="hist-date"><?= $dateFmt ?> • <?= $timeFmt ?> WIB</span>
                                    <span class="hist-target"><i class="ph ph-credit-card"></i><?= htmlspecialchars($tu['payment_method'] ?? 'QRIS') ?></span>
                                </div>
                                <div class="hist-detail">
                                    <span class="hist-badge" style="background:<?= $bgBadge ?>;color:<?= $fgBadge ?>">
                                        <?php if ($tuSuccess): ?><i class="ph ph-check"></i>
                                        <?php elseif ($tuFailed || $tuExpired): ?><i class="ph ph-x"></i>
                                        <?php else: ?><i class="ph ph-clock"></i><?php endif; ?>
                                        <?= $statusLabel ?>
                                    </span>
                                    <span class="hist-sku"><?= htmlspecialchars($tu['external_id']) ?></span>
                                    <?php if ($canPay): ?>
                                        <a href="<?= $invoiceUrl ?>" onclick="event.stopPropagation()"
                                            style="font-size:10px;font-weight:800;padding:2px 8px;border-radius:5px;
                              background:var(--cp);color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:3px">
                                            <i class="ph ph-qr-code"></i> Bayar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Amount -->
                            <div class="hist-right">
                                <span class="hist-amount <?= $tuSuccess ? 'cr' : 'db' ?>">
                                    <?= $tuSuccess ? '+' : '' ?>Rp <?= $amount ?>
                                </span>
                                <i class="ph ph-caret-right hist-chev"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="hist-empty">
                <div class="hist-empty-ic"><i class="ph ph-arrow-circle-up"></i></div>
                <h6>Belum Ada Top Up</h6>
                <p>Riwayat top up saldo<br>akan muncul di sini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- No result placeholder -->
<div id="noResult" class="hist-empty" style="display:none">
    <div class="hist-empty-ic"><i class="ph ph-magnifying-glass"></i></div>
    <h6>Tidak Ditemukan</h6>
    <p>Coba kata kunci atau filter lain.</p>
</div>

<div class="hist-bottom-space"></div>

<!-- ── FILTER MODAL ─────────────────────────────────────────────── -->
<div class="hist-fmodal-bg" id="fmodalBg" onclick="closeFModal()"></div>
<div class="hist-fmodal" id="fmodal">
    <div class="hist-fmodal-pull"></div>
    <div class="hist-fmodal-head">
        <span class="hist-fmodal-title">Filter Riwayat</span>
        <span class="hist-fmodal-reset" onclick="resetFilter()">Reset</span>
    </div>
    <div class="hist-fmodal-body">
        <div class="hist-fmodal-lbl">Jenis Transaksi</div>
        <div class="hist-fmodal-opts" id="foptTab">
            <div class="hist-fopt on" data-val="all">Semua</div>
            <div class="hist-fopt" data-val="topup">Top Up Saldo</div>
            <div class="hist-fopt" data-val="ppob">Transaksi PPOB</div>
        </div>
        <div class="hist-fmodal-lbl">Status</div>
        <div class="hist-fmodal-opts" id="foptStatus">
            <div class="hist-fopt on" data-val="all">Semua</div>
            <div class="hist-fopt" data-val="success">Sukses</div>
            <div class="hist-fopt" data-val="pending">Pending</div>
            <div class="hist-fopt" data-val="failed">Gagal</div>
        </div>
        <button class="hist-fapply" onclick="applyFModal()">Terapkan Filter</button>
    </div>
</div>

<!-- ── DETAIL SHEET ─────────────────────────────────────────────── -->
<div class="hist-sheet-bg" id="sheetBg" onclick="closeSheet()"></div>
<div class="hist-sheet" id="sheet">
    <div class="hist-sheet-pull"></div>
    <div class="hist-sheet-head">
        <div class="hist-sheet-icon" id="shIcon"></div>
        <div>
            <div class="hist-sheet-nm" id="shName">—</div>
            <div class="hist-sheet-sub" id="shSub">—</div>
        </div>
    </div>
    <div class="hist-sheet-body" id="shBody"></div>
</div>

<script>
    /* ── Filter & Search ───────────────────────────────────────────── */
    let _status = 'all';
    let _search = '';
    let _tab = 'all'; // 'all' | 'topup' | 'ppob'
    let _status = 'all';

    // Chip status bar (atas) — hanya untuk filter status cepat
    document.querySelectorAll('.hchip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.hchip').forEach(x => x.classList.remove('on'));
            chip.classList.add('on');
            _status = chip.dataset.status;
            _tab = chip.dataset.tab || 'all';
            // sync fmodal opts
            syncFModalOpts();
            applyFilter();
        });
    });

    document.getElementById('searchInput').addEventListener('input', function() {
        _search = this.value.toLowerCase();
        applyFilter();
    });

    // ── Filter modal ──────────────────────────────────────────────
    document.getElementById('btnFilter').addEventListener('click', () => {
        syncFModalOpts();
        document.getElementById('fmodalBg').classList.add('show');
        requestAnimationFrame(() => document.getElementById('fmodal').classList.add('show'));
    });

    function closeFModal() {
        document.getElementById('fmodal').classList.remove('show');
        setTimeout(() => document.getElementById('fmodalBg').classList.remove('show'), 300);
    }

    // Single-select per group
    document.querySelectorAll('#foptTab .hist-fopt').forEach(o => {
        o.addEventListener('click', () => {
            document.querySelectorAll('#foptTab .hist-fopt').forEach(x => x.classList.remove('on'));
            o.classList.add('on');
        });
    });
    document.querySelectorAll('#foptStatus .hist-fopt').forEach(o => {
        o.addEventListener('click', () => {
            document.querySelectorAll('#foptStatus .hist-fopt').forEach(x => x.classList.remove('on'));
            o.classList.add('on');
        });
    });

    function applyFModal() {
        _tab = document.querySelector('#foptTab .hist-fopt.on')?.dataset.val || 'all';
        _status = document.querySelector('#foptStatus .hist-fopt.on')?.dataset.val || 'all';
        // sync chips
        document.querySelectorAll('.hchip').forEach(x => x.classList.remove('on'));
        const matchChip = document.querySelector(`.hchip[data-status="${_status}"][data-tab="all"]`);
        if (matchChip) matchChip.classList.add('on');
        else document.querySelector('.hchip[data-status="all"]')?.classList.add('on');
        closeFModal();
        applyFilter();
    }

    function resetFilter() {
        _tab = 'all';
        _status = 'all';
        document.querySelectorAll('#foptTab .hist-fopt').forEach(x => x.classList.remove('on'));
        document.querySelectorAll('#foptStatus .hist-fopt').forEach(x => x.classList.remove('on'));
        document.querySelector('#foptTab .hist-fopt[data-val="all"]')?.classList.add('on');
        document.querySelector('#foptStatus .hist-fopt[data-val="all"]')?.classList.add('on');
    }

    function syncFModalOpts() {
        document.querySelectorAll('#foptTab .hist-fopt').forEach(o => o.classList.toggle('on', o.dataset.val === _tab));
        document.querySelectorAll('#foptStatus .hist-fopt').forEach(o => o.classList.toggle('on', o.dataset.val === _status));
    }

    // ── Main filter ───────────────────────────────────────────────
    function applyFilter() {
        const topupSec = document.getElementById('topupSection');

        // Topup only
        if (_tab === 'topup') {
            document.querySelectorAll('.hist-month').forEach(m => m.style.display = 'none');
            if (topupSec) topupSec.style.display = '';
            document.getElementById('noResult').style.display = 'none';
            return;
        }

        // PPOB / all — sembunyikan topupSection
        if (topupSec) topupSec.style.display = 'none';

        let anyVisible = false;
        document.querySelectorAll('.hist-month').forEach(month => {
            let monthHasVisible = false;
            month.querySelectorAll('.hist-row').forEach(row => {
                const matchStatus = _status === 'all' || row.dataset.status === _status;
                const matchSearch = !_search || row.dataset.search.includes(_search) ||
                    row.querySelector('.hist-name').textContent.toLowerCase().includes(_search);
                const show = matchStatus && matchSearch;
                row.style.display = show ? '' : 'none';
                if (show) {
                    monthHasVisible = true;
                    anyVisible = true;
                }
            });
            month.style.display = monthHasVisible ? '' : 'none';
        });
        document.getElementById('noResult').style.display = anyVisible ? 'none' : '';
    }

    /* ── Detail Sheet ──────────────────────────────────────────────── */
    function showSheet(row) {
        const d = row.dataset;
        const isFailed = d.isFailed === '1';
        const isSuccess = d.isSuccess === '1';

        // Icon
        const icon = document.getElementById('shIcon');
        icon.innerHTML = `<i class="${d.ic}"></i>`;
        icon.style.background = d.icClr + '20';
        icon.style.color = d.icClr;

        // Header
        document.getElementById('shName').textContent = d.prodname;
        document.getElementById('shSub').innerHTML =
            `<span style="background:${d.bgBadge};color:${d.fgBadge};padding:2px 8px;border-radius:99px;font-size:10.5px;font-weight:800">${d.statusLabel}</span>`;

        // Body KV
        const body = document.getElementById('shBody');
        const amtClr = isFailed ? '#dc2626' : 'var(--ct)';
        const amtPrefix = isFailed ? '' : '-';
        const kv = (k, v, mono = false, color = '') =>
            `<div class="hist-kv">
            <span class="hist-kv-k">${k}</span>
            <span class="hist-kv-v ${mono ? 'mono' : ''}" ${color ? `style="color:${color}"` : ''}>${v}</span>
        </div>`;

        body.innerHTML =
            kv('Nominal', `<span style="font-weight:900;font-size:15px;color:${amtClr}">${amtPrefix}Rp ${d.amount}</span>`) +
            kv('Produk', d.prodname) +
            kv('Kode SKU', d.sku, true) +
            (d.target && d.target !== '-' ? kv('No. Tujuan', d.target) : '') +
            kv('Tanggal', `${d.date} ${d.time} WIB`) +
            (d.sn ? kv('SN / Keterangan', d.sn, true) : '') +
            kv('Status', `<span style="background:${d.bgBadge};color:${d.fgBadge};padding:2px 8px;border-radius:99px;font-size:10.5px;font-weight:800">${d.statusLabel}</span>`);

        // Show
        document.getElementById('sheetBg').classList.add('show');
        requestAnimationFrame(() => document.getElementById('sheet').classList.add('show'));
    }

    function closeSheet() {
        document.getElementById('sheet').classList.remove('show');
        setTimeout(() => document.getElementById('sheetBg').classList.remove('show'), 280);
    }

    // Swipe down to close
    (function() {
        const sh = document.getElementById('sheet');
        let startY = 0,
            startT = 0;
        sh.addEventListener('touchstart', e => {
            startY = e.touches[0].clientY;
            startT = Date.now();
        }, {
            passive: true
        });
        sh.addEventListener('touchend', e => {
            const dy = e.changedTouches[0].clientY - startY;
            const dt = Date.now() - startT;
            if (dy > 60 || (dy > 30 && dt < 200)) closeSheet();
        }, {
            passive: true
        });
    })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>