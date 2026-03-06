<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$stmt = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 50");
$history = $stmt->fetchAll();
?>
<?php
$chartDates = [];
$chartCount = [];
$chartAmount = [];

$stmtChart = $pdo->query("
    SELECT 
        DATE(created_at) as tanggal,
        COUNT(*) as total_trx,
        SUM(amount) as total_amount
    FROM transactions
    WHERE status = 'success'
    GROUP BY DATE(created_at)
    ORDER BY tanggal ASC
");

while ($row = $stmtChart->fetch(\PDO::FETCH_ASSOC)) {
    $chartDates[]  = date('d M', strtotime($row['tanggal']));
    $chartCount[]  = (int) $row['total_trx'];
    $chartAmount[] = (int) ($row['total_amount'] ?? 0);
}
?>


<style>
    .main-content {
        min-height: 85vh; 
        display: flex;
        flex-direction: column;
        margin-left: 260px; /* Default Desktop */
        transition: all 0.3s ease;
    }
    
    footer {
        margin-top: auto;
        margin-left: 260px;
    }

    /* Perbaikan Mobile (HP) */
    @media (max-width: 768px) {
        .main-content, footer {
            margin-left: 0 !important; 
        }
        
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }

        .card {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .table-responsive {
            border: 0;
        }

        canvas {
            max-height: 200px;
        }
    }
</style>
<div class="main-content" style="min-height: 85vh;">
    <div class="container-fluid flex-grow-1">
       <div class="hero-mini text-center">
           <i class="fas fa-shopping-cart fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
            <h4 class="fw-bold mt-1">Monitoring Transaksi</h4>
        </div>

         <div class="card bg-dark border-0 shadow-sm mt-5 mx-3 mb-4" style="border-radius:12px;">
            <div class="card-body">
                <h6 class="fw-bold text-info mb-3">
                    Ringkasan Transaksi
                </h6>
        
                <div class="row g-4">
                    <div class="col-md-6">
                        <p class="small text-secondary mb-2">Jumlah Transaksi</p>
                        <canvas id="chartCount" height="140"></canvas>
                    </div>
        
                    <div class="col-md-6">
                        <p class="small text-secondary mb-2">Total Pengeluaran (Rp)</p>
                        <canvas id="chartAmount" height="140"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-dark border-0 shadow-sm mt-5 mx-3" style="border-radius: 12px;">
            <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold text-info mb-0">
                        Semua Aktivitas Transaksi PPOB
                    </h6>
        
                    <input 
                        type="text" 
                        id="searchInput"
                        class="form-control form-control-sm bg-dark text-light border-secondary w-auto"
                        placeholder="Cari transaksi..."
                        style="max-width: 220px;"
                    >
                </div>
            </div>

            <div class="table-responsive rounded-4">
                <table class="table table-dark table-hover mb-0">
                    <thead style="background: #252930;">
                        <tr>
                            <th class="py-3 px-4 text-info">Waktu</th>
                            <th class="py-3 px-4 text-info">TRX ID</th>
                            <th class="py-3 text-info">Kode Produk</th>
                            <th class="py-3 text-info">Target</th>
                            <th class="py-3 text-info">Harga</th>
                            <th class="py-3 text-info">Status</th>
                            <th class="py-3 text-info">SN</th>
                            <th class="py-3 text-info text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $h): ?>
                        <tr id="row-<?= $h['id']; ?>">
                           <td class="px-4"><?= date('d F Y, H:i:s', strtotime($h['created_at'])); ?></td>
                            <td class="px-4"><?= $h['ref_id']; ?></td>
                            <td><?= $h['sku_code']; ?></td>
                            <td><?= $h['target']; ?></td>
                           <td><?= number_format($h['amount'] ?? 0, 0, ',', '.'); ?></td>
                            <td class="status-col">
                                <?php if($h['status'] == 'success'): ?>
                                    <span class="badge bg-success">Sukses</span>
                                <?php elseif($h['status'] == 'failed'): ?>
                                    <span class="badge bg-danger">Gagal</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="sn-col small"><?= $h['sn'] ?: '-'; ?></td>
                            <td class="text-center">
                                <?php if($h['status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-outline-info btn-cek" data-id="<?= $h['id']; ?>">
                                        <i class="fas fa-sync-alt"></i> Cek Status
                                    </button>
                                <?php else: ?>
                                    <i class="fas fa-check-circle text-success"></i>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labels = <?= json_encode($chartDates); ?>;

new Chart(document.getElementById('chartCount'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            data: <?= json_encode($chartCount); ?>,
            borderColor: '#0dcaf0',
            backgroundColor: 'rgba(13,202,240,0.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 3
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#adb5bd' }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { color: '#adb5bd', precision: 0 } }
        }
    }
});

new Chart(document.getElementById('chartAmount'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            data: <?= json_encode($chartAmount); ?>,
            backgroundColor: 'rgba(25,135,84,0.6)'
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#adb5bd' }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { color: '#adb5bd' } }
        }
    }
});
</script>


<script>
document.querySelectorAll('.btn-cek').forEach(button => {
    button.addEventListener('click', function() {
        const trxId = this.dataset.id;
        const btn = this;
        const row = document.getElementById(`row-${trxId}`);
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
console.log("Mengirim ID:", trxId);
        fetch('../../core/proses_cek_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `trx_id=${trxId}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload(); 
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Cek Status';
            }
        });
    });
});
</script>

<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    const rows = document.querySelectorAll('table tbody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(keyword) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>