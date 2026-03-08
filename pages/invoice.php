<?php

/**
 * pages/invoice.php
 * Invoice / Detail Top Up
 */

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

$ext_id = $_GET['ext_id'] ?? '';
$stmt   = $pdo->prepare("SELECT * FROM topup_history WHERE external_id=? AND user_id=? LIMIT 1");
$stmt->execute([$ext_id, $_SESSION['user_id']]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) die("Transaksi tidak ditemukan.");

$isQRIS = ($trx['payment_method'] === 'QRIS');

$statusColor = '#f59e0b';
$statusBg    = '#fffbeb';
$statusLabel = 'Menunggu Pembayaran';
$statusIcon  = 'ph-clock';

if ($trx['status'] === 'success') {
    $statusColor = '#16a34a';
    $statusBg = '#dcfce7';
    $statusLabel = 'Pembayaran Berhasil';
    $statusIcon = 'ph-check-circle';
} elseif ($trx['status'] === 'failed') {
    $statusColor = '#dc2626';
    $statusBg = '#fee2e2';
    $statusLabel = 'Pembayaran Gagal';
    $statusIcon = 'ph-x-circle';
}

/* Parse bank note */
$bankName = $bankNorek = $bankNama = '-';
if (!$isQRIS && !empty($trx['note'])) {
    foreach (explode("\n", $trx['note']) as $line) {
        if (stripos($line, 'BANK') !== false)   $bankName  = trim(str_replace('BANK', '', $line));
        if (stripos($line, 'A/N') !== false)    $bankNama  = trim(str_replace(['A/N :', 'A/N:'], '', $line));
        if (stripos($line, 'No Rek') !== false) $bankNorek = trim(str_replace(['No Rek :', 'No Rek:'], '', $line));
    }
}

$pageTitle = 'Invoice Deposit';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* ══════════════════════════════════════════════════════════
   INVOICE PAGE
══════════════════════════════════════════════════════════ */
    .inv-topbar {
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

    .inv-topbar-back {
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
        text-decoration: none;
    }

    .inv-topbar-title {
        flex: 1;
        font-size: 15px;
        font-weight: 800;
        color: #fff;
    }

    /* Header status */
    .inv-hero {
        background: var(--cp);
        padding: 0 14px 28px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    .inv-status-ico {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        margin-bottom: 2px;
    }

    .inv-status-lbl {
        font-size: 16px;
        font-weight: 900;
        color: #fff;
    }

    .inv-status-sub {
        font-size: 12px;
        color: rgba(255, 255, 255, .75);
        font-weight: 600;
    }

    .inv-amount-big {
        font-size: 28px;
        font-weight: 900;
        color: #fff;
        letter-spacing: -1px;
        margin-top: 4px;
    }

    /* Card */
    .inv-card {
        margin: -14px 14px 0;
        background: var(--cc);
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, .10);
        overflow: hidden;
        position: relative;
        z-index: 2;
    }

    .inv-card-title {
        padding: 14px 16px 8px;
        font-size: 11px;
        font-weight: 800;
        color: var(--cm);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .inv-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
        border-bottom: 1px solid rgba(0, 0, 0, .04);
        gap: 10px;
    }

    .inv-row:last-child {
        border-bottom: none;
    }

    .inv-row-k {
        font-size: 12px;
        color: var(--cm);
        flex-shrink: 0;
    }

    .inv-row-v {
        font-size: 12px;
        font-weight: 700;
        color: var(--ct);
        text-align: right;
        word-break: break-all;
    }

    .inv-row-v.mono {
        font-family: ui-monospace, monospace;
        font-size: 11px;
        color: var(--cp);
    }

    .inv-row-v.big {
        font-size: 15px;
        font-weight: 900;
        color: var(--cpd);
    }

    /* QRIS box */
    .inv-qris-wrap {
        margin: 14px;
        background: #f8fafc;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        border: 1.5px dashed #e2e8f0;
    }

    .inv-qris-wrap img {
        width: 180px;
        height: 180px;
        object-fit: contain;
        margin: 0 auto;
        display: block;
    }

    .inv-qris-note {
        font-size: 11px;
        color: var(--cm);
        font-weight: 600;
        margin-top: 10px;
    }

    /* Bank box */
    .inv-bank-wrap {
        margin: 14px;
        background: #f0f9ff;
        border-radius: 16px;
        padding: 16px;
        border: 1.5px dashed #bae6fd;
    }

    .inv-bank-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
    }

    .inv-bank-row:last-child {
        border-bottom: none;
    }

    .inv-bank-k {
        font-size: 12px;
        color: #0369a1;
    }

    .inv-bank-v {
        font-size: 13px;
        font-weight: 800;
        color: #0c4a6e;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .inv-copy-btn {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        background: #e0f2fe;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 13px;
        color: #0284c7;
        transition: background .15s;
    }

    .inv-copy-btn:active {
        background: #bae6fd;
    }

    /* Timer */
    .inv-timer {
        margin: 0 14px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .inv-timer-ico {
        font-size: 18px;
        color: #f59e0b;
        flex-shrink: 0;
    }

    .inv-timer-info {
        flex: 1;
    }

    .inv-timer-lbl {
        font-size: 11px;
        color: #92400e;
        font-weight: 600;
    }

    .inv-timer-val {
        font-size: 16px;
        font-weight: 900;
        color: #b45309;
        font-family: ui-monospace, monospace;
    }

    /* Total transfer highlight */
    .inv-total-box {
        margin: 14px;
        background: var(--cpl);
        border: 1.5px solid var(--cp);
        border-radius: 14px;
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .inv-total-k {
        font-size: 13px;
        font-weight: 700;
        color: var(--cpd);
    }

    .inv-total-v {
        font-size: 18px;
        font-weight: 900;
        color: var(--cpd);
        letter-spacing: -.5px;
    }

    /* Buttons */
    .inv-actions {
        display: flex;
        gap: 8px;
        padding: 14px;
    }

    .inv-btn {
        flex: 1;
        padding: 13px;
        border-radius: 13px;
        font-size: 13px;
        font-weight: 800;
        font-family: var(--f);
        cursor: pointer;
        transition: all .15s;
        text-align: center;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: none;
    }

    .inv-btn.cancel {
        background: #fee2e2;
        color: #dc2626;
    }

    .inv-btn.refresh {
        background: var(--cp);
        color: #fff;
    }

    .inv-btn.home {
        background: var(--cpl);
        color: var(--cpd);
    }

    /* Success state */
    .inv-success-box {
        margin: 14px;
        padding: 20px;
        background: #dcfce7;
        border-radius: 16px;
        text-align: center;
    }

    .inv-success-ico {
        font-size: 40px;
        color: #16a34a;
    }

    .inv-success-title {
        font-size: 15px;
        font-weight: 800;
        color: #15803d;
        margin-top: 8px;
    }

    .inv-success-sub {
        font-size: 12px;
        color: #166534;
        margin-top: 4px;
    }

    .inv-bottom {
        height: 16px;
    }
</style>

<!-- TOP BAR -->
<div class="inv-topbar">
    <a href="<?= base_url('pages/deposit.php') ?>" class="inv-topbar-back">
        <i class="ph ph-caret-left"></i>
    </a>
    <div class="inv-topbar-title">Detail Deposit</div>
</div>

<!-- HERO STATUS -->
<div class="inv-hero">
    <div class="inv-status-ico" style="background:<?= $statusBg ?>">
        <i class="ph <?= $statusIcon ?>" style="color:<?= $statusColor ?>"></i>
    </div>
    <div class="inv-status-lbl"><?= $statusLabel ?></div>
    <div class="inv-status-sub"><?= date('d M Y • H:i', strtotime($trx['created_at'])) ?> WIB</div>
    <div class="inv-amount-big">Rp <?= number_format($trx['amount_original'], 0, ',', '.') ?></div>
</div>

<!-- DETAIL CARD -->
<div class="inv-card">
    <div class="inv-card-title">Detail Transaksi</div>
    <div class="inv-row">
        <span class="inv-row-k">ID Transaksi</span>
        <span class="inv-row-v mono"><?= htmlspecialchars($trx['external_id']) ?></span>
    </div>
    <div class="inv-row">
        <span class="inv-row-k">Metode</span>
        <span class="inv-row-v"><?= $isQRIS ? 'QRIS Otomatis' : 'Transfer ' . htmlspecialchars($bankName) ?></span>
    </div>
    <div class="inv-row">
        <span class="inv-row-k">Status</span>
        <span class="inv-row-v">
            <span style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:800">
                <?= $statusLabel ?>
            </span>
        </span>
    </div>
    <div class="inv-row">
        <span class="inv-row-k">Nominal</span>
        <span class="inv-row-v">Rp <?= number_format($trx['amount_original'], 0, ',', '.') ?></span>
    </div>
    <?php if ($trx['amount'] !== $trx['amount_original']): ?>
        <div class="inv-row">
            <span class="inv-row-k">Kode Unik</span>
            <span class="inv-row-v">+Rp <?= number_format($trx['amount'] - $trx['amount_original'], 0, ',', '.') ?></span>
        </div>
    <?php endif; ?>
</div>

<?php if ($trx['status'] !== 'success'): ?>

    <!-- TIMER (15 menit) -->
    <div class="inv-timer" style="margin-top:14px">
        <i class="ph ph-clock-countdown inv-timer-ico"></i>
        <div class="inv-timer-info">
            <div class="inv-timer-lbl">Selesaikan pembayaran dalam</div>
            <div class="inv-timer-val" id="invTimer">15:00</div>
        </div>
    </div>

    <?php if ($isQRIS): ?>
        <!-- QRIS -->
        <div class="inv-qris-wrap">
            <img src="<?= htmlspecialchars($trx['qr_string']) ?>" alt="QRIS">
            <div class="inv-qris-note">Scan menggunakan aplikasi e-wallet atau m-banking</div>
        </div>
    <?php else: ?>
        <!-- BANK TRANSFER -->
        <div class="inv-bank-wrap">
            <div class="inv-bank-row">
                <span class="inv-bank-k">Bank</span>
                <span class="inv-bank-v"><?= htmlspecialchars($bankName) ?></span>
            </div>
            <div class="inv-bank-row">
                <span class="inv-bank-k">Atas Nama</span>
                <span class="inv-bank-v"><?= htmlspecialchars($bankNama) ?></span>
            </div>
            <div class="inv-bank-row">
                <span class="inv-bank-k">No. Rekening</span>
                <span class="inv-bank-v">
                    <?= htmlspecialchars($bankNorek) ?>
                    <div class="inv-copy-btn" onclick="copyText('<?= htmlspecialchars($bankNorek) ?>')">
                        <i class="ph ph-copy"></i>
                    </div>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- TOTAL TRANSFER -->
    <div class="inv-total-box">
        <span class="inv-total-k">Total <?= $isQRIS ? 'Bayar' : 'Transfer' ?></span>
        <span class="inv-total-v">Rp <?= number_format($trx['amount'], 0, ',', '.') ?></span>
    </div>

    <!-- ACTIONS -->
    <div class="inv-actions">
        <a href="cancel.php?id=<?= $trx['id'] ?>" class="inv-btn cancel">
            <i class="ph ph-x"></i> Batalkan
        </a>
        <button class="inv-btn refresh" onclick="location.reload()">
            <i class="ph ph-arrows-clockwise"></i> Refresh
        </button>
    </div>

<?php else: ?>

    <!-- SUCCESS -->
    <div class="inv-success-box" style="margin-top:14px">
        <i class="ph ph-check-circle inv-success-ico"></i>
        <div class="inv-success-title">Saldo Berhasil Masuk!</div>
        <div class="inv-success-sub">Rp <?= number_format($trx['amount_original'], 0, ',', '.') ?> telah ditambahkan ke saldo kamu</div>
    </div>
    <div class="inv-actions">
        <a href="<?= base_url('dashboard.php') ?>" class="inv-btn home">
            <i class="ph ph-house"></i> Ke Dashboard
        </a>
        <a href="deposit.php" class="inv-btn refresh">
            <i class="ph ph-plus"></i> Top Up Lagi
        </a>
    </div>

<?php endif; ?>

<div class="inv-bottom"></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function copyText(text) {
        navigator.clipboard.writeText(text);
        Swal.fire({
            icon: 'success',
            title: 'Disalin!',
            timer: 1200,
            showConfirmButton: false
        });
    }

    <?php if ($trx['status'] !== 'success'): ?>
            /* Countdown 15 menit dari created_at */
            (function() {
                const created = new Date('<?= $trx['created_at'] ?>').getTime();
                const expiry = created + 15 * 60 * 1000;
                const el = document.getElementById('invTimer');

                function tick() {
                    const left = Math.max(0, expiry - Date.now());
                    const m = Math.floor(left / 60000);
                    const s = Math.floor((left % 60000) / 1000);
                    el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    if (left <= 0) el.textContent = 'Kedaluwarsa';
                }
                tick();
                setInterval(tick, 1000);
            })();

        /* Auto cek status setiap 5 detik */
        let checker = setInterval(() => {
            fetch('<?= base_url('core/cek_status_kira.php') ?>?ext_id=<?= $ext_id ?>')
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') {
                        clearInterval(checker);
                        Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Berhasil!',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    }
                });
        }, 5000);
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>