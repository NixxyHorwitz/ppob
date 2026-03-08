<?php
session_start();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit();
}

$ext_id = $_GET['ext_id'] ?? '';

$stmt = $pdo->prepare("
SELECT * FROM topup_history
WHERE external_id=? AND user_id=? LIMIT 1
");
$stmt->execute([$ext_id, $_SESSION['user_id']]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) {
    die("Transaksi tidak ditemukan.");
}

/* ===============================
   STATUS LABEL
================================= */
$statusColor = '#d9534f';
$statusLabel = 'Menunggu Pembayaran';

if ($trx['status'] == 'success') {
    $statusColor = '#28a745';
    $statusLabel = 'Pembayaran Berhasil';
} elseif ($trx['status'] == 'failed') {
    $statusColor = '#dc3545';
    $statusLabel = 'Pembayaran Gagal';
}

/* ===============================
   CEK MODE PEMBAYARAN
================================= */
$isQRIS = ($trx['payment_method'] == 'QRIS');

/* ===============================
   PARSE NOTE (UNTUK BANK)
================================= */
$bank = '-';
$norek = '-';
$nama = '-';

if (!$isQRIS && !empty($trx['note'])) {

    $lines = explode("\n", $trx['note']);

    foreach ($lines as $line) {

        if (stripos($line, 'BANK') !== false)
            $bank = trim(str_replace('BANK', '', $line));

        if (stripos($line, 'A/N') !== false)
            $nama = trim(str_replace(['A/N :', 'A/N:'], '', $line));

        if (stripos($line, 'No Rek') !== false)
            $norek = trim(str_replace(['No Rek :', 'No Rek:'], '', $line));
    }
}
?>


<div class="main-content">

    <div class="invoice-card">

        <div class="invoice-header">
            <h5 class="m-0">Rincian Deposit #<?= $trx['id']; ?></h5>
            <small><?= date('d M Y H:i', strtotime($trx['created_at'])) ?> WIB</small>
        </div>

        <div class="invoice-body">

            <table class="detail-table w-100 mb-3">
                <tr>
                    <td>Tanggal</td>
                    <td><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td>
                </tr>

                <tr>
                    <td>Metode</td>
                    <td><?= $isQRIS ? 'QRIS Otomatis' : 'Transfer ' . $bank ?></td>
                </tr>

                <tr>
                    <td>Status</td>
                    <td style="color:<?= $statusColor ?>;font-weight:bold">
                        <?= $statusLabel ?>
                    </td>
                </tr>
            </table>

            <?php if ($trx['status'] != 'success'): ?>

                <div class="pay-box">

                    <?php if ($isQRIS): ?>

                        <!-- ================= QRIS ================= -->
                        <h6 class="mb-3">Scan QRIS</h6>

                        <img src="<?= $trx['qr_string']; ?>" alt="QRIS">

                        <p class="small text-muted mt-2">
                            Scan menggunakan aplikasi e-wallet / m-banking
                        </p>

                    <?php else: ?>

                        <!-- ================= BANK ================= -->
                        <h6 class="mb-3">Transfer Bank</h6>

                        <div class="text-start small">
                            <b>Bank :</b> <?= htmlspecialchars($bank) ?><br>
                            <b>A/N :</b> <?= htmlspecialchars($nama) ?><br>

                            <b>No Rek :</b>
                            <span id="norek"><?= htmlspecialchars($norek) ?></span>

                            <i class="far fa-copy"
                                style="cursor:pointer"
                                onclick="copyText('<?= htmlspecialchars($norek) ?>')"></i>
                        </div>

                    <?php endif; ?>

                    <div class="total">
                        <span>Total Bayar</span>
                        <span style="color:#008080">
                            Rp <?= number_format($trx['amount'], 0, ',', '.'); ?>
                        </span>
                    </div>

                </div>

                <div class="btn-group">
                    <a href="cancel.php?id=<?= $trx['id']; ?>" class="btn-action btn-cancel">
                        Batalkan
                    </a>

                    <a href="javascript:location.reload();" class="btn-action btn-refresh">
                        Refresh
                    </a>
                </div>

            <?php else: ?>

                <!-- SUCCESS -->
                <div class="pay-box">

                    <div class="success-icon">✅</div>

                    <h5 class="text-success mt-2">Pembayaran Berhasil</h5>

                    <p class="text-muted small">
                        Saldo telah masuk ke akun Anda.
                    </p>

                    <div class="total">
                        <span>Total Deposit</span>
                        <span style="color:#28a745">
                            Rp <?= number_format($trx['amount_original'], 0, ',', '.'); ?>
                        </span>
                    </div>

                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function copyText(text) {
        navigator.clipboard.writeText(text);
        Swal.fire({
            icon: 'success',
            title: 'Disalin',
            timer: 1200,
            showConfirmButton: false
        });
    }
</script>

<?php if ($trx['status'] != 'success'): ?>
    <script>
        /* AUTO CEK STATUS */
        let checker = setInterval(() => {
            fetch('../../core/cek_status_kira.php?ext_id=<?= $ext_id ?>')
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') {
                        clearInterval(checker);
                        Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Berhasil',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    }
                });
        }, 5000);
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>