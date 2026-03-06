<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../core/dashboard_data.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

?>
<style>
    .main-content {
        min-height: 100vh;
        margin-left: 260px; /* Lebar sidebar desktop */
        transition: all 0.3s ease;
    }

    /* Fix untuk HP */
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0 !important;
            padding: 10px !important;
        }
        .card h5 { font-size: 1.1rem; }
        .card small { font-size: 0.75rem; }
    }

    .card { border-radius: 15px !important; }
    .progress { border-radius: 10px; }
</style>

<div class="main-content">
    <div class="container-fluid">
         <div class="hero-mini text-center mb-5">
           <i class="fas fa-shopping-cart fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
            <h4 class="fw-bold mt-1">Admin Overview</h4>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="badge bg-dark border border-secondary px-3 py-2 rounded-pill">
                <i class="fas fa-calendar-alt me-2 text-info"></i><?= date('d M Y') ?>
            </span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card bg-dark border-0 p-3 shadow-sm h-100">
                    <small class="text-light d-block mb-1">Total Saldo User</small>
                    <h5 class="text-info fw-bold mb-0">Rp <?= number_format($totalSaldo, 0, ',', '.') ?></h5>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-dark border-0 p-3 shadow-sm h-100">
                    <small class="text-light d-block mb-1">Total Pengguna</small>
                    <h5 class="text-white fw-bold mb-0"><?= $userStats['total'] ?></h5>
                    <small class="text-light" style="font-size: 10px;"><?= $userStats['total_user'] ?> Aktif</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-dark border-0 p-3 shadow-sm h-100">
                    <small class="text-light d-block mb-1">Produk Aktif</small>
                    <h6 class="text-white fw-bold mb-0"><?= $prodStats['pra'] ?> Pra / <?= $prodStats['pasca'] ?> Pasca</h6>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-primary border-0 p-3 shadow-sm h-100 text-white">
                    <small class="opacity-75 d-block mb-1">Profit Bulan Ini</small>
                    <h5 class="fw-bold mb-0">Rp <?= number_format($profitMonth['profit'], 0, ',', '.') ?></h5>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card bg-dark border-0 p-4 shadow-sm h-100">
                    <h6 class="text-white mb-4"><i class="fas fa-chart-area me-2 text-info"></i>Aktivitas Transaksi (7 Hari)</h6>
                    <div style="position: relative; height: 300px;">
                        <canvas id="trxChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card bg-dark border-0 p-4 shadow-sm h-100">
                    <h6 class="text-white mb-3"><i class="fas fa-wallet me-2 text-success"></i>Kesimpulan Profit</h6>
                    <div class="list-group list-group-flush bg-transparent">
                        <div class="list-group-item bg-transparent text-white border-secondary px-0 py-3">
                            <small class="text-light d-block">Hari Ini</small>
                            <span class="fw-bold">Rp <?= number_format($profitToday['profit'], 0, ',', '.') ?></span>
                        </div>
                        <div class="list-group-item bg-transparent text-white border-secondary px-0 py-3">
                            <small class="text-light d-block">Minggu Ini</small>
                            <span class="fw-bold">Rp <?= number_format($profitWeek['profit'], 0, ',', '.') ?></span>
                        </div>
                        <div class="list-group-item bg-transparent text-white border-0 px-0 py-3">
                            <small class="text-light d-block">Bulan Ini</small>
                            <span class="fw-bold text-success fs-5">Rp <?= number_format($profitMonth['profit'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card bg-dark border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-secondary py-3 text-white fw-bold">
                        <i class="fas fa-signal text-info me-2"></i>Status Hari Ini
                    </div>
                    <div class="card-body">
                        <?php 
                        $total = array_sum($statusStats ?: [0]);
                        $p_sukses = $total > 0 ? ($statusStats['sukses'] / $total) * 100 : 0;
                        $p_pending = $total > 0 ? ($statusStats['pending'] / $total) * 100 : 0;
                        $p_gagal = $total > 0 ? ($statusStats['gagal'] / $total) * 100 : 0;
                        ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-light">Sukses (<?= $statusStats['sukses'] ?>)</span>
                                <span class="text-success"><?= round($p_sukses) ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px; background: #252930;">
                                <div class="progress-bar bg-success" style="width: <?= $p_sukses ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-light">Pending (<?= $statusStats['pending'] ?>)</span>
                                <span class="text-warning"><?= round($p_pending) ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px; background: #252930;">
                                <div class="progress-bar bg-warning" style="width: <?= $p_pending ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-light">Gagal (<?= $statusStats['gagal'] ?>)</span>
                                <span class="text-danger"><?= round($p_gagal) ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px; background: #252930;">
                                <div class="progress-bar bg-danger" style="width: <?= $p_gagal ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card bg-dark border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-secondary py-3 text-white fw-bold">
                        <i class="fas fa-box text-primary me-2"></i>Produk Terlaris
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <tbody>
                                <?php foreach($topProducts as $tp): ?>
                                <tr class="border-bottom border-secondary">
                                    <td class="py-3 px-3">
                                        <div class="small fw-bold text-truncate" style="max-width: 140px;"><?= htmlspecialchars($tp['product_name'] ?? $tp['sku_code']) ?></div>
                                    </td>
                                    <td class="py-3 text-end px-3">
                                        <span class="badge bg-primary rounded-pill"><?= $tp['terjual'] ?>x</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card bg-dark border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-secondary py-3 text-white fw-bold">
                        <i class="fas fa-university text-success me-2"></i>Deposit Terbaru
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if(empty($recentDeposits)): ?>
                                <li class="list-group-item bg-transparent text-light text-center py-5 small">Belum ada deposit</li>
                            <?php endif; ?>
                            <?php foreach($recentDeposits as $rd): ?>
                            <li class="list-group-item bg-transparent border-secondary py-3 px-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-white small fw-bold"><?= htmlspecialchars($rd['username']) ?></div>
                                    <small class="text-light" style="font-size: 10px;"><?= date('H:i', strtotime($rd['created_at'])) ?></small>
                                </div>
                                <div class="text-end">
                                    <div class="text-success small fw-bold">+<?= number_format($rd['amount'], 0, ',', '.') ?></div>
                                    <span class="badge bg-<?= $rd['status'] == 'success' ? 'success' : 'warning' ?>" style="font-size: 8px;"><?= strtoupper($rd['status']) ?></span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('trxChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($chartData)) ?>,
        datasets: [{
            label: 'Jumlah Transaksi',
            data: <?= json_encode(array_values($chartData)) ?>,
            borderColor: '#0dcaf0',
            backgroundColor: 'rgba(13, 202, 240, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#333' }, ticks: { color: '#aaa' } },
            x: { grid: { display: false }, ticks: { color: '#aaa' } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>