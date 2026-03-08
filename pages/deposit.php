<?php

/**
 * pages/deposit.php
 * Halaman Top Up Saldo
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/qrcode/qrlib.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

$userId = $_SESSION['user_id'];

/* ── POST handler ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method          = $_POST['payment_method'] ?? 'QRIS';
    $amount_original = (int)$_POST['amount_original'];
    $kode_unik       = (int)($_POST['kode_unik'] ?? 0);

    if ($amount_original < 10000) {
        $_SESSION['error_msg'] = "Minimal topup Rp10.000";
        header("Location: deposit.php");
        exit;
    }

    if ($method === 'QRIS') {
        $ref  = "TOPUP-" . time() . "-" . $userId;
        $qris = $pdo->query("SELECT qris_code FROM website_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$qris) die("QRIS belum disetting admin");

        $unique = rand(100, 999);
        $total  = $amount_original + $unique;
        $qr_string = generateDynamicQRIS(trim($qris['qris_code']), $total);

        $dir  = __DIR__ . '/../uploads/qris/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = "qris_$ref.png";
        QRcode::png($qr_string, $dir . $file, QR_ECLEVEL_H, 6, 2);

        $stmt = $pdo->prepare("INSERT INTO topup_history (user_id,external_id,amount_original,amount,qr_string,status,payment_method) VALUES (?,?,?,?,?,'pending','QRIS')");
        $stmt->execute([$userId, $ref, $amount_original, $total, '/uploads/qris/' . $file]);
        header("Location: invoice.php?ext_id=" . $ref);
        exit;
    } else {
        $stmt = $pdo->prepare("SELECT bank_name,account_name,account_number FROM payment_method WHERE bank_name=? AND is_active=1 LIMIT 1");
        $stmt->execute([$method]);
        $bank = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$bank) die("Metode tidak valid");

        $note  = "BANK " . strtoupper($bank['bank_name']) . "\nA/N : " . $bank['account_name'] . "\nNo Rek : " . $bank['account_number'];
        $total = $amount_original + $kode_unik;
        $ref   = "BANK-" . time() . "-" . $userId;

        $stmt = $pdo->prepare("INSERT INTO topup_history (user_id,external_id,amount_original,amount,note,status,payment_method,created_at) VALUES (?,?,?,?,?,'pending',?,NOW())");
        $stmt->execute([$userId, $ref, $amount_original, $total, $note, "BANK - " . strtoupper($method)]);
        header("Location: invoice.php?ext_id=" . $ref);
        exit;
    }
}

/* ── Ambil bank aktif ─────────────────────────────────────── */
$banks = $pdo->query("SELECT bank_name FROM payment_method WHERE method_type='MANUAL' AND is_active=1 ORDER BY bank_name ASC")->fetchAll(PDO::FETCH_ASSOC);

/* ── Ambil saldo user ─────────────────────────────────────── */
$userRow = $pdo->prepare("SELECT saldo FROM users WHERE id=?");
$userRow->execute([$userId]);
$balance = $userRow->fetchColumn() ?: 0;

$pageTitle = 'Top Up Saldo';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* ══════════════════════════════════════════════════════════
   DEPOSIT PAGE — Mobile-first, DANA-inspired UX
══════════════════════════════════════════════════════════ */

    /* Top bar */
    .dp-topbar {
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

    .dp-topbar-back {
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

    .dp-topbar-title {
        flex: 1;
        font-size: 15px;
        font-weight: 800;
        color: #fff;
    }

    /* Balance chip */
    .dp-balance {
        background: var(--cp);
        padding: 0 14px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
    }

    .dp-bal-lbl {
        font-size: 11px;
        font-weight: 700;
        color: rgba(255, 255, 255, .7);
        letter-spacing: .5px;
    }

    .dp-bal-val {
        font-size: 22px;
        font-weight: 900;
        color: #fff;
        letter-spacing: -.5px;
    }

    /* Amount display card */
    .dp-amount-card {
        margin: 0 14px;
        background: var(--cc);
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, .10);
        padding: 20px 20px 16px;
        margin-top: -10px;
        position: relative;
        z-index: 2;
    }

    .dp-amount-lbl {
        font-size: 10px;
        font-weight: 800;
        color: var(--cp);
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: 6px;
    }

    .dp-amount-display {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 10px 0;
        border-bottom: 2px solid var(--cp);
        cursor: pointer;
        min-height: 52px;
    }

    .dp-amount-prefix {
        font-size: 18px;
        font-weight: 800;
        color: var(--cm);
    }

    .dp-amount-val {
        font-size: 32px;
        font-weight: 900;
        color: var(--ct);
        letter-spacing: -1px;
        flex: 1;
        min-height: 40px;
        line-height: 1;
    }

    .dp-amount-val.placeholder {
        color: #cbd5e1;
    }

    .dp-amount-cursor {
        width: 2px;
        height: 32px;
        background: var(--cp);
        border-radius: 2px;
        animation: blink .9s step-end infinite;
        display: none;
    }

    .dp-amount-display.focused .dp-amount-cursor {
        display: block;
    }

    @keyframes blink {
        50% {
            opacity: 0;
        }
    }

    /* Admin fee note */
    .dp-fee-note {
        margin-top: 8px;
        font-size: 11px;
        font-weight: 600;
        color: var(--cm);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .dp-fee-note i {
        font-size: 11px;
        color: var(--cp);
    }

    /* Quick amounts */
    .dp-quick {
        margin: 16px 14px 0;
    }

    .dp-quick-lbl {
        font-size: 11px;
        font-weight: 800;
        color: var(--cm);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .dp-quick-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
    }

    .dp-quick-btn {
        padding: 9px 4px;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        background: var(--cc);
        font-size: 12px;
        font-weight: 700;
        color: var(--ct);
        text-align: center;
        cursor: pointer;
        transition: all .15s;
        -webkit-tap-highlight-color: transparent;
    }

    .dp-quick-btn:active,
    .dp-quick-btn.on {
        border-color: var(--cp);
        background: var(--cpl);
        color: var(--cpd);
    }

    /* Method selector */
    .dp-method {
        margin: 16px 14px 0;
    }

    .dp-method-lbl {
        font-size: 11px;
        font-weight: 800;
        color: var(--cm);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .dp-method-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .dp-method-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 14px;
        border: 2px solid #e2e8f0;
        background: var(--cc);
        cursor: pointer;
        transition: all .15s;
        -webkit-tap-highlight-color: transparent;
    }

    .dp-method-item.on {
        border-color: var(--cp);
        background: var(--cpl);
    }

    .dp-method-ico {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .dp-method-info {
        flex: 1;
    }

    .dp-method-name {
        font-size: 13px;
        font-weight: 800;
        color: var(--ct);
    }

    .dp-method-sub {
        font-size: 11px;
        color: var(--cm);
        margin-top: 1px;
    }

    .dp-method-radio {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all .15s;
    }

    .dp-method-item.on .dp-method-radio {
        border-color: var(--cp);
        background: var(--cp);
    }

    .dp-method-item.on .dp-method-radio::after {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #fff;
    }

    /* Kode unik info */
    .dp-kode-box {
        margin: 12px 14px 0;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 12px;
        font-weight: 700;
        color: #92400e;
        display: none;
        align-items: center;
        gap: 8px;
    }

    .dp-kode-box.show {
        display: flex;
    }

    .dp-kode-box i {
        color: #f59e0b;
        font-size: 14px;
    }

    /* CTA */
    .dp-cta-wrap {
        padding: 20px 14px 8px;
    }

    .dp-cta {
        width: 100%;
        padding: 15px;
        background: var(--cp);
        color: #fff;
        border: none;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 800;
        font-family: var(--f);
        cursor: pointer;
        transition: all .15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .dp-cta:disabled {
        opacity: .4;
        pointer-events: none;
    }

    .dp-cta:active {
        transform: scale(.98);
    }

    /* ── KEYPAD ── */
    .dp-keypad-wrap {
        position: fixed;
        bottom: 0;
        left: 50%;
        z-index: 100;
        transform: translateX(-50%) translateY(100%);
        width: 100%;
        max-width: 600px;
        background: var(--cc);
        border-radius: 24px 24px 0 0;
        box-shadow: 0 -8px 40px rgba(0, 0, 0, .15);
        transition: transform .28s cubic-bezier(.4, 0, .2, 1);
        padding-bottom: env(safe-area-inset-bottom);
    }

    .dp-keypad-wrap.show {
        transform: translateX(-50%) translateY(0);
    }

    .dp-keypad-pull {
        width: 36px;
        height: 4px;
        background: rgba(0, 0, 0, .1);
        border-radius: 99px;
        margin: 10px auto 4px;
    }

    .dp-keypad-done {
        text-align: right;
        padding: 4px 18px 8px;
        font-size: 14px;
        font-weight: 800;
        color: var(--cp);
        cursor: pointer;
    }

    .dp-keypad {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2px;
        padding: 0 8px 8px;
    }

    .dp-key {
        height: 58px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 700;
        color: var(--ct);
        cursor: pointer;
        transition: background .1s, transform .1s;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
        background: transparent;
    }

    .dp-key:active {
        background: rgba(0, 0, 0, .06);
        transform: scale(.94);
    }

    .dp-key.del {
        font-size: 20px;
        color: var(--cm);
    }

    .dp-key.empty {
        pointer-events: none;
    }

    .dp-keypad-bg {
        position: fixed;
        inset: 0;
        z-index: 99;
        background: rgba(0, 0, 0, .3);
        display: none;
    }

    .dp-keypad-bg.show {
        display: block;
    }

    .dp-bottom {
        height: 16px;
    }

    /* Error message */
    .dp-error {
        margin: 10px 14px 0;
        padding: 10px 14px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        color: #dc2626;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<!-- TOP BAR -->
<div class="dp-topbar">
    <a href="<?= base_url('dashboard.php') ?>" class="dp-topbar-back">
        <i class="ph ph-caret-left"></i>
    </a>
    <div class="dp-topbar-title">Top Up Saldo</div>
</div>

<!-- BALANCE -->
<div class="dp-balance">
    <span class="dp-bal-lbl">Saldo Kamu</span>
    <span class="dp-bal-val">Rp <?= number_format($balance, 0, ',', '.') ?></span>
</div>

<?php if (!empty($_SESSION['error_msg'])): ?>
    <div class="dp-error">
        <i class="ph ph-warning"></i>
        <?= htmlspecialchars($_SESSION['error_msg']) ?>
    </div>
<?php unset($_SESSION['error_msg']);
endif; ?>

<!-- AMOUNT CARD -->
<div class="dp-amount-card">
    <div class="dp-amount-lbl">Nominal Top Up</div>
    <div class="dp-amount-display" id="dpAmountDisplay" onclick="openKeypad()">
        <span class="dp-amount-prefix">Rp</span>
        <span class="dp-amount-val placeholder" id="dpAmountVal">0</span>
        <span class="dp-amount-cursor" id="dpCursor"></span>
    </div>
    <div class="dp-fee-note">
        <i class="ph ph-info"></i>
        <span id="dpFeeNote">Minimal top up Rp10.000</span>
    </div>
</div>

<!-- QUICK AMOUNTS -->
<div class="dp-quick">
    <div class="dp-quick-lbl">Nominal Cepat</div>
    <div class="dp-quick-grid">
        <?php foreach ([10000, 20000, 50000, 100000, 200000, 500000] as $q): ?>
            <div class="dp-quick-btn" onclick="setQuick(<?= $q ?>)">
                Rp <?= number_format($q, 0, ',', '.') ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- METHOD -->
<div class="dp-method">
    <div class="dp-method-lbl">Metode Pembayaran</div>
    <div class="dp-method-list">
        <!-- QRIS -->
        <div class="dp-method-item on" onclick="selectMethod('QRIS','auto',this)">
            <div class="dp-method-ico" style="background:#f0fdf4">
                <i class="ph ph-qr-code" style="color:#16a34a"></i>
            </div>
            <div class="dp-method-info">
                <div class="dp-method-name">QRIS</div>
                <div class="dp-method-sub">Scan & bayar otomatis</div>
            </div>
            <div class="dp-method-radio"></div>
        </div>
        <!-- BANK MANUAL -->
        <?php foreach ($banks as $b): ?>
            <div class="dp-method-item" onclick="selectMethod('<?= strtoupper($b['bank_name']) ?>','manual',this)">
                <div class="dp-method-ico" style="background:#eff6ff">
                    <i class="ph ph-bank" style="color:#3b82f6"></i>
                </div>
                <div class="dp-method-info">
                    <div class="dp-method-name">Transfer <?= strtoupper($b['bank_name']) ?></div>
                    <div class="dp-method-sub">Transfer manual ke rekening</div>
                </div>
                <div class="dp-method-radio"></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- KODE UNIK INFO -->
<div class="dp-kode-box" id="dpKodeBox">
    <i class="ph ph-info"></i>
    <span id="dpKodeText">Nominal transfer akan ditambah kode unik otomatis</span>
</div>

<!-- CTA -->
<div class="dp-cta-wrap">
    <button class="dp-cta" id="dpCta" disabled onclick="submitDeposit()">
        <i class="ph ph-arrow-circle-up"></i>
        <span id="dpCtaLabel">Lanjutkan</span>
    </button>
</div>

<div class="dp-bottom"></div>

<!-- KEYPAD -->
<div class="dp-keypad-bg" id="dpKpBg" onclick="closeKeypad()"></div>
<div class="dp-keypad-wrap" id="dpKeypad">
    <div class="dp-keypad-pull"></div>
    <div class="dp-keypad-done" onclick="closeKeypad()">Selesai</div>
    <div class="dp-keypad">
        <?php
        $keys = [1, 2, 3, 4, 5, 6, 7, 8, 9, '000', '0', 'del'];
        foreach ($keys as $k):
        ?>
            <div class="dp-key <?= $k === 'del' ? 'del' : ($k === '' ? 'empty' : '') ?>"
                onclick="keyPress('<?= $k ?>')">
                <?php if ($k === 'del'): ?>
                    <svg width="22" height="22" viewBox="0 0 256 256" fill="currentColor">
                        <path d="M216,48H88a16,16,0,0,0-13.16,6.92l-61.6,84.85a8,8,0,0,0,0,9.35l61.6,84.85A16,16,0,0,0,88,240H216a16,16,0,0,0,16-16V64A16,16,0,0,0,216,48Zm0,176H88L30.43,140,88,64H216ZM178.34,146.34,161,128l17.37-17.37a8,8,0,0,0-11.32-11.32L149.66,116.7l-17.37-17.37a8,8,0,0,0-11.32,11.32L138.35,128l-17.38,17.34a8,8,0,0,0,11.32,11.32l17.37-17.37,17.37,17.37a8,8,0,0,0,11.31-11.32Z" />
                    </svg>
                <?php else: ?>
                    <?= $k ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Hidden form -->
<form method="POST" id="dpForm" style="display:none">
    <input type="hidden" name="payment_method" id="fMethod" value="QRIS">
    <input type="hidden" name="amount_original" id="fAmount">
    <input type="hidden" name="kode_unik" id="fKode" value="">
</form>

<script>
    let _amount = 0;
    let _method = 'QRIS';
    let _mode = 'auto';
    let _kode = null;

    const MAX = 10000000; // 10jt

    /* ── Amount display ── */
    function updateDisplay() {
        const el = document.getElementById('dpAmountVal');
        const cta = document.getElementById('dpCta');
        const fee = document.getElementById('dpFeeNote');
        const lbl = document.getElementById('dpCtaLabel');

        if (_amount === 0) {
            el.textContent = '0';
            el.classList.add('placeholder');
        } else {
            el.textContent = _amount.toLocaleString('id-ID');
            el.classList.remove('placeholder');
        }

        const ok = _amount >= 10000;
        cta.disabled = !ok;

        if (_amount > 0 && _amount < 10000) {
            fee.textContent = 'Minimal top up Rp10.000';
            fee.style.color = '#dc2626';
        } else if (_amount >= 10000) {
            fee.textContent = 'Nominal valid ✓';
            fee.style.color = '#16a34a';
        } else {
            fee.textContent = 'Minimal top up Rp10.000';
            fee.style.color = '';
        }

        if (ok) {
            const total = _mode === 'manual' && _kode ?
                _amount + _kode :
                _amount;
            lbl.textContent = 'Bayar Rp ' + total.toLocaleString('id-ID');
        } else {
            lbl.textContent = 'Lanjutkan';
        }

        // Uncheck quick btns
        document.querySelectorAll('.dp-quick-btn').forEach(b => {
            b.classList.toggle('on', parseInt(b.dataset.val) === _amount);
        });
    }

    /* ── Keypad ── */
    function openKeypad() {
        document.getElementById('dpKpBg').classList.add('show');
        document.getElementById('dpKeypad').classList.add('show');
        document.getElementById('dpAmountDisplay').classList.add('focused');
    }

    function closeKeypad() {
        document.getElementById('dpKpBg').classList.remove('show');
        document.getElementById('dpKeypad').classList.remove('show');
        document.getElementById('dpAmountDisplay').classList.remove('focused');
    }

    function keyPress(k) {
        if (k === 'del') {
            _amount = Math.floor(_amount / 10);
        } else {
            const append = parseInt(k);
            const next = _amount * (k === '000' ? 1000 : 10) + (k === '000' ? 0 : append);
            if (k === '000') {
                _amount = _amount * 1000;
            } else {
                _amount = _amount * 10 + append;
            }
            if (_amount > MAX) _amount = MAX;
        }
        _kode = null; // reset kode unik saat nominal berubah
        updateKodeBox();
        updateDisplay();
    }

    function setQuick(val) {
        _amount = val;
        _kode = null;
        updateKodeBox();
        updateDisplay();
        closeKeypad();
        // Set data-val pada tombol quick
    }

    // Set data-val on quick btns
    document.querySelectorAll('.dp-quick-btn').forEach((b, i) => {
        const vals = [10000, 20000, 50000, 100000, 200000, 500000];
        b.dataset.val = vals[i];
    });

    /* ── Method ── */
    function selectMethod(method, mode, el) {
        _method = method;
        _mode = mode;
        document.querySelectorAll('.dp-method-item').forEach(x => x.classList.remove('on'));
        el.classList.add('on');
        document.getElementById('fMethod').value = method;

        if (mode === 'manual') {
            if (!_kode) _kode = Math.floor(Math.random() * 900) + 100;
        } else {
            _kode = null;
        }
        updateKodeBox();
        updateDisplay();
    }

    function updateKodeBox() {
        const box = document.getElementById('dpKodeBox');
        const text = document.getElementById('dpKodeText');
        if (_mode === 'manual' && _amount >= 10000) {
            if (!_kode) _kode = Math.floor(Math.random() * 900) + 100;
            const total = _amount + _kode;
            text.textContent = `Transfer tepat Rp ${total.toLocaleString('id-ID')} (termasuk kode unik +${_kode})`;
            box.classList.add('show');
        } else {
            box.classList.remove('show');
        }
    }

    /* ── Submit ── */
    function submitDeposit() {
        if (_amount < 10000) return;
        document.getElementById('fAmount').value = _amount;
        document.getElementById('fKode').value = _kode || '';
        document.getElementById('dpCta').disabled = true;
        document.getElementById('dpCtaLabel').textContent = 'Memproses...';
        document.getElementById('dpForm').submit();
    }

    updateDisplay();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>