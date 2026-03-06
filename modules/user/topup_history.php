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

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM topup_history WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$histories = $stmt->fetchAll();
?>

<style>
    .main-content {
        min-height: 85vh; 
        display: flex;
        flex-direction: column;
        margin-left: 260px; 
        transition: all 0.3s ease;
    }

    footer {
        margin-top: auto;
        margin-left: 260px;
    }

    @media (max-width: 768px) {
        .main-content, footer {
            margin-left: 0 !important; 
        }
        
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }

     

        .table thead th {
            font-size: 11px;
            padding: 10px 5px !important;
        }
        
        .table tbody td {
            font-size: 12px;
            padding: 10px 5px !important;
        }

        .table-responsive {
            border: 0;
            margin-bottom: 0;
        }
    }
</style>



<div class="main-content">
    <div class="container-fluid flex-grow-1">
            <div class="hero-mini text-center mb-5">
                 <i class="fas fa-wallet fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
                <h4 class="fw-bold mt-1">Riwayat Topup Saldo</h4>
            </div>
            <br>
            
         <div class="card bg-dark border-0 shadow-sm mx-3" style="border-radius: 15px; overflow: hidden;">
           <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold text-info mb-0">
                        Riwayat Topup
                    </h6>
            
                    <input 
                        type="text"
                        id="searchHistory"
                        class="form-control form-control-sm bg-dark text-light border-secondary"
                        placeholder="Cari transaksi..."
                        style="max-width: 220px;"
                    >
                </div>
            </div>
            
         <div class="table-responsive rounded-4 mx-3">
                <table class="table table-dark table-hover mb-0">
                    <thead style="background: #252930;">
                        <tr>
                            <th>Tanggal</th>
                            <th>ID Transaksi</th>
                             <th>Metode</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($histories as $row): ?>
                        <tr>
                             <td>
                                <?= date_timezone_set(date_create($row['created_at']), timezone_open('Asia/Jakarta'))->format('d M Y H:i'); ?> WIB
                            </td>
                            <td><?= $row['external_id'] ?></td>
                             <td><?= $row['payment_method'] ?></td>
                            <td>Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ($row['status'] == 'success'): ?>
                                    <span style="color: green;">Sukses</span>
                                <?php elseif ($row['status'] == 'failed'): ?>
                                    <span style="color: red;">Gagal</span>
                                <?php else: ?>
                                    <span style="color: orange;">Pending</span>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('searchHistory').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    const rows = document.querySelectorAll('table tbody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(keyword) ? '' : 'none';
    });
});
</script>
<script>
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

</script>
