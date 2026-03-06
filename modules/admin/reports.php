<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../core/report_data.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

?>

<style>
    body { background-color: #f8f9fa; color: #333; }
    .main-content { margin-left: 260px; }
    .card-report { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .table thead th { background-color: #f1f3f5; color: #495057; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border: none; }
    @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
</style>

<div class="main-content">
    <div class="container-fluid">
         <div class="hero-mini text-center mb-5">
           <i class="fas fa-file-invoice-dollar fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
            <h4 class="fw-bold mt-1">Report </h4>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4 mx-3">
          <h4 class="fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Laporan Penjualan</h4>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fas fa-print me-1"></i> Cetak Laporan
            </button>
        </div>

        <div class="card-report p-3 mb-4">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill">Filter</button>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-4 text-center">
            <div class="col-md-4">
                <div class="card-report p-3">
                    <small class="text-muted d-block">Total Omset (Sukses)</small>
                    <span class="fw-bold fs-5 text-dark">Rp <?= number_format($sum['total_omset'], 0, ',', '.') ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-report p-3 border-start border-success border-4">
                    <small class="text-muted d-block text-success">Total Net Profit</small>
                    <span class="fw-bold fs-5 text-success">Rp <?= number_format($sum['total_profit'], 0, ',', '.') ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-report p-3">
                    <small class="text-muted d-block">Volume Transaksi</small>
                    <span class="fw-bold fs-5 text-dark"><?= number_format($sum['total_trx'], 0, ',', '.') ?> Trx</span>
                </div>
            </div>
        </div>

        <div class="card-report overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Total Trx</th>
                            <th>Gagal</th>
                            <th>Omset Bruto</th>
                            <th class="text-end pe-4">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reports as $r): ?>
                        <tr class="align-middle">
                            <td class="ps-4 fw-bold"><?= date('d M Y', strtotime($r['tgl'])) ?></td>
                            <td><?= $r['total_trx'] ?></td>
                            <td class="text-danger small"><?= $r['total_failed'] ?></td>
                            <td class="fw-semibold">Rp <?= number_format($r['omset'], 0, ',', '.') ?></td>
                            <td class="text-end pe-4 text-success fw-bold">
                                Rp <?= number_format($r['net_profit'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($reports)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada data pada periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>