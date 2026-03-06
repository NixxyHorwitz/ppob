<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT t.*, p.product_name 
    FROM transactions t 
    LEFT JOIN products p ON LOWER(TRIM(t.sku_code)) = LOWER(TRIM(p.sku_code)) 
    WHERE t.user_id = ? 
    ORDER BY t.created_at DESC
");
$stmt->execute([$userId]);
$history = $stmt->fetchAll();
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

        #searchHistory {
            max-width: 100% !important;
            width: 100%;
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
    <div class="hero-mini text-center">
         <i class="fas fa-history fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
        <h4 class="fw-bold mt-1">Riwayat Transaksi PPOB</h4>
    </div>

    <div class="container-fluid mt-5">
        <div class="d-flex justify-content-end align-items-center mb-4">
            <a href="../prabayar/index.php" class="btn btn-outline-info btn-sm">
                <i class="fas fa-plus me-1"></i> Transaksi Baru
            </a>
        </div>

        <div class="card bg-dark border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
           <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold text-info mb-0">
                        Riwayat Transaksi
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

        <div class="table-responsive">
                <table class="table table-dark table-hover mb-0" style="background: #1a1d21;">
                    <thead class="text-uppercase small" style="background: #252930;">
                        <tr>
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3">Nama Produk</th>
                            <th class="py-3">Kode Produk</th>
                            <th class="py-3">No.Tujuan</th>
                            <th class="py-3">Harga</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 px-4">SN / KET</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($history) > 0): ?>
                            <?php foreach($history as $h): ?>
                            <tr class="align-middle">
                                <td class="px-4">
                                    <div class="small text-white"><?= date('d M Y', strtotime($h['created_at'])); ?></div>
                                    <div class="text-muted" style="font-size: 11px;"><?= date('H:i', strtotime($h['created_at'])); ?> WIB</div>
                                </td>
                               <td class="text-white fw-bold">
                                    <?= htmlspecialchars($h['product_name'] ?? $h['sku_code']); ?>
                                </td>
                                <td class="fw-bold text-white text-center"><?= htmlspecialchars($h['sku_code']); ?></td>
                                <td class="text-info"><?= htmlspecialchars($h['target']); ?></td>
                               <td class="text-white">Rp <?= number_format($h['amount'] ?? 0, 0, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    $status = strtolower($h['status']);
                                    if($status == 'success' || $status == 'sukses'): ?>
                                        <span class="badge rounded-pill bg-success px-3">Sukses</span>
                                    <?php elseif($status == 'failed' || $status == 'gagal'): ?>
                                        <span class="badge rounded-pill bg-danger px-3">Gagal</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-warning text-dark px-3">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4">
                                    <code class="text-light" style="font-size: 12px;"><?= $h['sn'] ?: '-'; ?></code>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Belum ada transaksi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</div>

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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


