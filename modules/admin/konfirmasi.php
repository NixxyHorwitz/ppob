<?php
session_start();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit();
}

// 1. Ambil Data Antrean (Hanya Pending)
$stmtPending = $pdo->prepare("SELECT th.*, u.username FROM topup_history th JOIN users u ON th.user_id = u.id WHERE th.status = 'pending' ORDER BY th.created_at DESC");
$stmtPending->execute();
$pendingList = $stmtPending->fetchAll();

// 2. Ambil Riwayat Keseluruhan (Semua Status)
$stmtAll = $pdo->prepare("SELECT th.*, u.username FROM topup_history th JOIN users u ON th.user_id = u.id ORDER BY th.created_at DESC LIMIT 50");
$stmtAll->execute();
$allHistories = $stmtAll->fetchAll();
?>

<style>
    .main-content { min-height: 85vh; display: flex; flex-direction: column; margin-left: 260px; transition: all 0.3s ease; }
    footer { margin-top: auto; margin-left: 260px; }
    @media (max-width: 768px) {
        .main-content, footer { margin-left: 0 !important; }
        .table thead th { font-size: 11px; padding: 10px 5px !important; }
        .table tbody td { font-size: 12px; padding: 10px 5px !important; }
    }
</style>

<div class="main-content">
    <div class="container-fluid flex-grow-1">
        <div class="hero-mini text-center mb-4">
             <i class="fas fa-tasks fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
            <h4 class="fw-bold mt-1">Monitoring & Riwayat Topup</h4>
        </div>

        <div class="card bg-dark border-0 shadow-sm mx-3 my-5" style="border-radius: 15px; overflow: hidden; border-left: 4px solid #ffc107 !important;">
            <div class="card-body pb-2">
                <h6 class="fw-bold text-warning mb-0"><i class="fas fa-clock me-2"></i>Antrean Persetujuan</h6>
            </div>
            <div class="table-responsive rounded-4 mx-3">
                <table class="table table-dark table-hover mb-0">
                    <thead style="background: #252930;">
                        <tr>
                            <th>User</th>
                            <th>ID Transaksi</th>
                            <th>Jumlah</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendingList)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada antrean pending.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($pendingList as $row): ?>
                        <tr>
                            <td><span class="text-info"><?= $row['username'] ?></span></td>
                            <td><small><?= $row['external_id'] ?></small></td>
                            <td class="fw-bold text-warning">Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-success" onclick="prosesTopup('<?= $row['external_id'] ?>', 'approve')"><i class="fas fa-check"></i></button>
                                    <button class="btn btn-sm btn-danger" onclick="prosesTopup('<?= $row['external_id'] ?>', 'reject')"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card bg-dark border-0 shadow-sm mx-3" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold text-info mb-0"><i class="fas fa-history me-2"></i>Semua Riwayat Topup Saldo</h6>
                    <input type="text" id="searchHistory" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Cari username/ID..." style="max-width: 220px;">
                </div>
            </div>
          
            <div class="table-responsive rounded-4 mx-3">
                <table class="table table-dark table-hover mb-0">
                    <thead style="background: #252930;">
                        <tr class="text-center">
                            <th>No.</th>
                            <th>User</th>
                            <th>ID Transaksi</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php $no = 1; ?>
                        <?php foreach ($allHistories as $row): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td style="text-align: left !important;"><?= $row['username'] ?></td>
                            <td><small><?= $row['external_id'] ?></small></td>
                            <td>Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                            <td><?= $row['payment_method'] ?></td>
                            <td class="text-center">
                                <?php if ($row['status'] == 'success'): ?>
                                    <span class="badge bg-success">Sukses</span>
                                <?php elseif ($row['status'] == 'failed'): ?>
                                    <span class="badge bg-danger">Gagal</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                          <td class="text-center">
    <?php if ($row['status'] == 'pending'): ?>
        <?php if (in_array($row['payment_method'], ['QRIS', 'BRIVA', 'BCA', 'MANDIRI'])): ?>
            <button class="btn btn-sm btn-outline-info" onclick="manualCekStatus('<?= $row['external_id'] ?>')">
                <i class="fas fa-sync-alt"></i> Check
            </button>
        <?php else: ?>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3">
                <i class="fas fa-hourglass-half me-1"></i> Queue
            </span>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($row['status'] == 'success'): ?>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3">
                <i class="fas fa-check-circle me-1"></i> Done
            </span>
        <?php else: ?>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3">
                <i class="fas fa-times-circle me-1"></i> Failed
            </span>
        <?php endif; ?>
    <?php endif; ?>
</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function prosesTopup(id, action) {
    const title = action === 'approve' ? 'Approve Saldo?' : 'Batalkan Topup?';
    Swal.fire({
        title: title,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'approve' ? '#198754' : '#dc3545',
        confirmButtonText: 'Ya, Lanjutkan!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `../../core/proses_approve.php?id=${id}&action=${action}`;
        }
    });
}

function manualCekStatus(id) {
    fetch('/demo/core/cek_status_kira?ext_id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Saldo berhasil masuk ke akun Anda.',
                    icon: 'success',
                    confirmButtonColor: '#008080'
                }).then(() => {
                    location.reload();
                });
            } else if (data.status === 'failed') {
                Swal.fire({
                    title: 'Transaksi Gagal',
                    text: 'Pembayaran telah kedaluwarsa atau dibatalkan.',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Transaksi Pending',
                    text: 'Status masih menunggu pembayaran.',
                    icon: 'info',
                    confirmButtonColor: '#f0ad4e'
                });
            }
        })
        .catch(err => {
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan koneksi ke server.',
                icon: 'error'
            });
        });
}


document.getElementById('searchHistory').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    const rows = document.querySelectorAll('table:last-child tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>