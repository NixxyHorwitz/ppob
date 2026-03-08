<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/qrcode/qrlib.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

$userId = $_SESSION['user_id'];

/* ======================================================
   ✅ PROSES TOPUP (HARUS DI ATAS SEBELUM HTML)
======================================================*/
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $method = $_POST['payment_method'] ?? 'QRIS';
    $amount_original = (int)$_POST['amount_original'];
    $kode_unik = (int)($_POST['kode_unik'] ?? 0);

    if ($amount_original < 10000) {
        $_SESSION['error_msg'] = "Minimal topup Rp10.000";
        header("Location: topup.php");
        exit;
    }

    /* ================= QRIS ================= */
    if ($method === 'QRIS') {

        $ref = "TOPUP-" . time() . "-" . $userId;

        $qris = $pdo->query("SELECT qris_code FROM website_settings LIMIT 1")
            ->fetch(PDO::FETCH_ASSOC);

        if (!$qris) {
            die("QRIS belum disetting admin");
        }

        $unique = rand(100, 999);
        $total = $amount_original + $unique;

        $qr_string = generateDynamicQRIS(
            trim($qris['qris_code']),
            $total
        );

        $dir = __DIR__ . '/../../uploads/qris/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $file = "qris_$ref.png";
        QRcode::png($qr_string, $dir . $file, QR_ECLEVEL_H, 6, 2);

        $stmt = $pdo->prepare("
            INSERT INTO topup_history
            (user_id,external_id,amount_original,amount,qr_string,status,payment_method)
            VALUES (?,?,?,?,?,'pending','QRIS')
        ");

        $stmt->execute([
            $userId,
            $ref,
            $amount_original,
            $total,
            '/uploads/qris/' . $file
        ]);

        header("Location: invoice?ext_id=" . $ref);
        exit;
    }

    /* ================= BANK MANUAL ================= */ else {

        $stmt = $pdo->prepare("
            SELECT bank_name,account_name,account_number
            FROM payment_method
            WHERE bank_name=? AND is_active=1 LIMIT 1
        ");
        $stmt->execute([$method]);
        $bank = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bank) {
            die("Metode tidak valid");
        }

        $note =
            "BANK " . strtoupper($bank['bank_name']) . "\n" .
            "A/N : " . $bank['account_name'] . "\n" .
            "No Rek : " . $bank['account_number'];

        $total = $amount_original + $kode_unik;
        $ref = "BANK-" . time() . "-" . $userId;

        $stmt = $pdo->prepare("
            INSERT INTO topup_history
            (user_id,external_id,amount_original,amount,note,status,payment_method,created_at)
            VALUES (?,?,?,?,?,'pending',?,NOW())
        ");

        $stmt->execute([
            $userId,
            $ref,
            $amount_original,
            $total,
            $note,
            "BANK - " . strtoupper($method)
        ]);

        header("Location: invoice?ext_id=" . $ref);
        exit;
    }
}

/* ======================================================
   ✅ AMBIL BANK AKTIF
======================================================*/
$stmt = $pdo->query("
SELECT bank_name
FROM payment_method
WHERE method_type='MANUAL'
AND is_active=1
ORDER BY bank_name ASC
");
$banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>


<div class="main-content">
    <div class="container py-4">

        <div class="card card-topup p-4">

            <h5 class="text-center fw-bold mb-4">
                💳 Topup Saldo
            </h5>

            <form method="POST" id="topupForm">

                <input type="hidden" name="payment_method" id="payment_method" value="QRIS">
                <input type="hidden" name="kode_unik" id="kode_unik">

                <label class="fw-semibold">Nominal Topup</label>
                <div class="input-group mb-3">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="amount_original"
                        id="amountInput"
                        class="form-control"
                        placeholder="Minimal 10.000"
                        required>
                </div>

                <label class="fw-semibold">Metode Pembayaran</label>

                <div class="border rounded p-2 mb-3">

                    <div class="method-item"
                        onclick="selectMethod('QRIS','auto')">
                        ✅ QRIS (Otomatis)
                    </div>

                    <?php foreach ($banks as $b): ?>
                        <div class="method-item"
                            onclick="selectMethod('<?= strtoupper($b['bank_name']) ?>','manual')">
                            🏦 Transfer <?= strtoupper($b['bank_name']) ?>
                        </div>
                    <?php endforeach; ?>

                </div>

                <div id="manualBox" class="total-box d-none">
                    Total Transfer:
                    <h5 id="totalText" class="fw-bold mb-0">Rp 0</h5>
                    <small>Termasuk kode unik otomatis</small>
                </div>

                <button class="btn btn-primary w-100 mt-4" id="payBtn">
                    Lanjutkan Pembayaran
                </button>

            </form>
        </div>
    </div>
</div>

<script>
    const amountInput = document.getElementById('amountInput');
    const totalText = document.getElementById('totalText');
    const kodeInput = document.getElementById('kode_unik');
    const manualBox = document.getElementById('manualBox');
    const payBtn = document.getElementById('payBtn');

    let mode = 'auto';
    let kode = null;

    function selectMethod(method, m) {
        document.getElementById('payment_method').value = method;
        mode = m;

        if (m === 'manual') {
            manualBox.classList.remove('d-none');
            generateTotal();
        } else {
            manualBox.classList.add('d-none');
        }
    }

    amountInput.addEventListener('input', generateTotal);

    function generateTotal() {
        if (mode !== 'manual') return;

        let amount = parseInt(amountInput.value);
        if (!amount || amount < 10000) return;

        if (!kode) {
            kode = Math.floor(Math.random() * 900) + 100;
        }

        let total = amount + kode;

        totalText.innerText = 'Rp ' + total.toLocaleString('id-ID');
        kodeInput.value = kode;
    }

    /* anti double klik */
    document.getElementById('topupForm').addEventListener('submit', () => {
        payBtn.disabled = true;
        payBtn.innerText = 'Memproses...';
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>