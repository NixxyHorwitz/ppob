<?php
// ── Konfigurasi halaman ──────────────────
$page_title  = 'Dashboard';
$active_menu = 'dashboard';
$base_path   = '';  // kosong jika di root; contoh: '../' jika di subfolder

// Script chart akan di-inject ke footer
ob_start();
?>
/* Chart script akan diisi di bawah */
<?php
$extra_js = ob_get_clean();

require_once 'includes/header.php';
?>

<!-- ══════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════ -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h1>Dashboard</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Dashboard</li>
      </ol>
    </nav>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-sm" style="background:var(--bg-card);border:1px solid var(--border);color:var(--text-subtle);border-radius:8px">
      <i class="ph ph-calendar-blank me-1"></i> Jan 2025 – Des 2025
    </button>
    <button class="btn btn-sm btn-primary" style="border-radius:8px">
      <i class="ph ph-download-simple me-1"></i> Export
    </button>
  </div>
</div>

<!-- ══ STAT CARDS ══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

  <div class="col-xl-3 col-sm-6">
    <div class="stat-card blue">
      <div class="stat-icon blue"><i class="ph-fill ph-currency-dollar"></i></div>
      <div class="stat-value">Rp 84,2jt</div>
      <div class="stat-label">Total Revenue</div>
      <div class="stat-trend up">
        <i class="ph ph-trend-up"></i> +12.4% dari bulan lalu
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="stat-card green">
      <div class="stat-icon green"><i class="ph-fill ph-shopping-bag"></i></div>
      <div class="stat-value">1.284</div>
      <div class="stat-label">Total Orders</div>
      <div class="stat-trend up">
        <i class="ph ph-trend-up"></i> +8.1% dari bulan lalu
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="stat-card orange">
      <div class="stat-icon orange"><i class="ph-fill ph-users-three"></i></div>
      <div class="stat-value">5.641</div>
      <div class="stat-label">Total Users</div>
      <div class="stat-trend down">
        <i class="ph ph-trend-down"></i> -2.3% dari bulan lalu
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="stat-card purple">
      <div class="stat-icon purple"><i class="ph-fill ph-chart-pie"></i></div>
      <div class="stat-value">68,4%</div>
      <div class="stat-label">Conversion Rate</div>
      <div class="stat-trend up">
        <i class="ph ph-trend-up"></i> +4.7% dari bulan lalu
      </div>
    </div>
  </div>

</div><!-- /stat cards -->

<!-- ══ CHART ROW 1 ══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

  <!-- Revenue Chart (Area) -->
  <div class="col-xl-8">
    <div class="card-custom h-100">
      <div class="card-header-custom">
        <div>
          <p class="card-title">Revenue Overview</p>
          <p class="card-subtitle">Pendapatan 12 bulan terakhir</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-sm chart-period active-period" data-period="monthly"
            style="font-size:11px;padding:4px 10px;border-radius:6px;background:var(--accent-soft);color:var(--accent);border:1px solid var(--border-active)">
            Bulanan
          </button>
          <button class="btn btn-sm chart-period"
            style="font-size:11px;padding:4px 10px;border-radius:6px;background:var(--bg-hover);color:var(--text-muted);border:1px solid var(--border)">
            Mingguan
          </button>
        </div>
      </div>
      <div class="card-body-custom">
        <div id="chartRevenue" class="chart-container"></div>
      </div>
    </div>
  </div>

  <!-- Donut Chart -->
  <div class="col-xl-4">
    <div class="card-custom h-100">
      <div class="card-header-custom">
        <div>
          <p class="card-title">Sales by Category</p>
          <p class="card-subtitle">Distribusi penjualan</p>
        </div>
      </div>
      <div class="card-body-custom">
        <div id="chartDonut" class="chart-container"></div>
        <!-- Legend -->
        <div class="mt-3" id="donutLegend"></div>
      </div>
    </div>
  </div>

</div>

<!-- ══ CHART ROW 2 ══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

  <!-- Bar Chart -->
  <div class="col-xl-6">
    <div class="card-custom h-100">
      <div class="card-header-custom">
        <div>
          <p class="card-title">Orders per Minggu</p>
          <p class="card-subtitle">7 minggu terakhir</p>
        </div>
      </div>
      <div class="card-body-custom">
        <div id="chartBar" class="chart-container"></div>
      </div>
    </div>
  </div>

  <!-- Activity + Progress -->
  <div class="col-xl-6">
    <div class="row g-3 h-100">

      <!-- Top Products Progress -->
      <div class="col-12">
        <div class="card-custom">
          <div class="card-header-custom">
            <div>
              <p class="card-title">Top Products</p>
              <p class="card-subtitle">Berdasarkan penjualan</p>
            </div>
            <a href="#" style="font-size:12px;color:var(--accent);text-decoration:none">
              Lihat semua <i class="ph ph-arrow-right"></i>
            </a>
          </div>
          <div class="card-body-custom">
            <?php
            $top_products = [
              ['name' => 'Sepatu Lari Pro X',  'sold' => 842, 'pct' => 84, 'color' => 'var(--accent)'],
              ['name' => 'Kaos Olahraga Slim',  'sold' => 621, 'pct' => 62, 'color' => 'var(--success)'],
              ['name' => 'Celana Training V2',  'sold' => 504, 'pct' => 50, 'color' => 'var(--warning)'],
              ['name' => 'Tas Gym Premium',     'sold' => 310, 'pct' => 31, 'color' => 'var(--purple)'],
              ['name' => 'Sarung Tangan Gym',   'sold' => 188, 'pct' => 19, 'color' => 'var(--accent2)'],
            ];
            foreach ($top_products as $p): ?>
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span style="font-size:13px;font-weight:500"><?= $p['name'] ?></span>
                  <span style="font-size:12px;color:var(--text-muted);font-family:'JetBrains Mono',monospace"><?= number_format($p['sold']) ?></span>
                </div>
                <div class="progress-custom">
                  <div class="progress-bar-custom" style="width:<?= $p['pct'] ?>%;background:<?= $p['color'] ?>"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Activity Feed -->
      <div class="col-12">
        <div class="card-custom">
          <div class="card-header-custom">
            <div>
              <p class="card-title">Aktivitas Terbaru</p>
            </div>
            <a href="#" style="font-size:12px;color:var(--accent);text-decoration:none">
              Semua <i class="ph ph-arrow-right"></i>
            </a>
          </div>
          <div class="card-body-custom">
            <?php
            $activities = [
              ['color' => 'var(--success)', 'text' => 'Order #4821 berhasil dibayar', 'time' => '2 menit lalu'],
              ['color' => 'var(--accent)',  'text' => 'User baru mendaftar: rina@email.com', 'time' => '14 menit lalu'],
              ['color' => 'var(--warning)', 'text' => 'Stok "Kaos Slim" hampir habis', 'time' => '1 jam lalu'],
              ['color' => 'var(--danger)',  'text' => 'Order #4815 dibatalkan oleh user', 'time' => '2 jam lalu'],
              ['color' => 'var(--purple)',  'text' => 'Report bulanan telah di-generate', 'time' => '3 jam lalu'],
            ];
            foreach ($activities as $a): ?>
              <div class="activity-item">
                <div class="activity-dot mt-1" style="background:<?= $a['color'] ?>"></div>
                <div>
                  <div style="font-size:13px"><?= $a['text'] ?></div>
                  <div class="activity-time"><?= $a['time'] ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </div>

</div><!-- /chart row 2 -->

<!-- ══ DATA TABLE ══════════════════════════════════════════════ -->
<div class="card-custom mb-4">
  <div class="card-header-custom">
    <div>
      <p class="card-title">Transaksi Terbaru</p>
      <p class="card-subtitle">Daftar order 30 hari terakhir</p>
    </div>
    <button class="btn btn-sm btn-primary" style="border-radius:7px;font-size:12px">
      <i class="ph ph-plus me-1"></i> Tambah Order
    </button>
  </div>
  <div class="card-body-custom">
    <div class="table-responsive">
      <table id="transactionTable" class="table-dark-custom w-100">
        <thead>
          <tr>
            <th>#Order</th>
            <th>Pelanggan</th>
            <th>Produk</th>
            <th>Total</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $statuses = [
            'Selesai'  => 'badge-success',
            'Proses'   => 'badge-accent',
            'Dikirim'  => 'badge-warning',
            'Batal'    => 'badge-danger',
          ];
          $orders = [
            ['#4831','Andi Wijaya','Sepatu Lari Pro X','Rp 520.000','Selesai','12 Jan 2025'],
            ['#4830','Sari Dewi','Kaos Olahraga Slim','Rp 185.000','Dikirim','12 Jan 2025'],
            ['#4829','Budi Hartono','Celana Training V2','Rp 320.000','Proses','11 Jan 2025'],
            ['#4828','Rina Suyati','Tas Gym Premium','Rp 780.000','Selesai','11 Jan 2025'],
            ['#4827','Dodi Pratama','Sarung Tangan Gym','Rp 95.000','Batal','10 Jan 2025'],
            ['#4826','Maya Lestari','Sepatu Lari Pro X','Rp 520.000','Selesai','10 Jan 2025'],
            ['#4825','Hendra Kusuma','Kaos Olahraga Slim','Rp 185.000','Dikirim','09 Jan 2025'],
            ['#4824','Fitri Amalia','Celana Training V2','Rp 320.000','Selesai','09 Jan 2025'],
            ['#4823','Yusuf Hakim','Tas Gym Premium','Rp 780.000','Proses','08 Jan 2025'],
            ['#4822','Nurul Hidayah','Sarung Tangan Gym','Rp 95.000','Selesai','08 Jan 2025'],
            ['#4821','Rizki Maulana','Sepatu Lari Pro X','Rp 520.000','Selesai','07 Jan 2025'],
            ['#4820','Dewi Anggraini','Kaos Olahraga Slim','Rp 185.000','Batal','07 Jan 2025'],
          ];
          foreach ($orders as $o):
            $badge = $statuses[$o[4]];
          ?>
            <tr>
              <td><span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--accent)"><?= $o[0] ?></span></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="https://ui-avatars.com/api/?name=<?= urlencode($o[1]) ?>&background=1a2540&color=7a90b0&size=32"
                       style="width:28px;height:28px;border-radius:50%;" alt=""/>
                  <span><?= $o[1] ?></span>
                </div>
              </td>
              <td style="color:var(--text-subtle)"><?= $o[2] ?></td>
              <td style="font-family:'JetBrains Mono',monospace"><?= $o[3] ?></td>
              <td><span class="badge-custom <?= $badge ?>"><?= $o[4] ?></span></td>
              <td style="color:var(--text-muted)"><?= $o[5] ?></td>
              <td>
                <div class="d-flex gap-1">
                  <button class="topbar-btn" style="width:28px;height:28px;font-size:15px" title="Detail">
                    <i class="ph ph-eye"></i>
                  </button>
                  <button class="topbar-btn" style="width:28px;height:28px;font-size:15px" title="Edit">
                    <i class="ph ph-pencil-simple"></i>
                  </button>
                  <button class="topbar-btn" style="width:28px;height:28px;font-size:15px;color:var(--danger)" title="Hapus">
                    <i class="ph ph-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
// Script charts – di-pass ke footer melalui $extra_scripts
$extra_scripts = <<<'SCRIPT'
<script>
// ── ApexCharts — Revenue Area ─────────────────────────────────
const revenueOptions = {
  ...window.apexDefaults,
  chart: {
    ...window.apexDefaults.chart,
    type: 'area',
    height: 280,
    sparkline: { enabled: false },
  },
  series: [{
    name: 'Revenue',
    data: [18, 32, 27, 45, 38, 52, 48, 61, 57, 72, 68, 84],
  }],
  xaxis: {
    categories: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
    axisBorder: { show: false },
    axisTicks:  { show: false },
    labels:     { style: { colors: '#4b5e7a', fontSize: '12px' } },
  },
  yaxis: {
    labels: {
      formatter: v => 'Rp ' + v + 'jt',
      style: { colors: '#4b5e7a', fontSize: '11px' },
    },
  },
  stroke: { curve: 'smooth', width: 2.5 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.35,
      opacityTo:   0.02,
      stops: [0, 90, 100],
    },
  },
  markers: { size: 0 },
  dataLabels: { enabled: false },
};
new ApexCharts(document.querySelector('#chartRevenue'), revenueOptions).render();

// ── ApexCharts — Donut ────────────────────────────────────────
const donutLabels  = ['Sepatu', 'Kaos', 'Celana', 'Tas', 'Aksesoris'];
const donutSeries  = [34, 22, 18, 15, 11];
const donutColors  = ['#3b82f6','#10b981','#f59e0b','#a855f7','#06b6d4'];

const donutOptions = {
  ...window.apexDefaults,
  chart: { ...window.apexDefaults.chart, type: 'donut', height: 220 },
  series: donutSeries,
  labels: donutLabels,
  colors: donutColors,
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Total',
            color: '#7a90b0',
            fontSize: '12px',
            formatter: () => '100%',
          },
          value: {
            color: '#e2e8f0',
            fontSize: '20px',
            fontWeight: 700,
            formatter: v => v + '%',
          },
        },
      },
    },
  },
  legend: { show: false },
  dataLabels: { enabled: false },
};
new ApexCharts(document.querySelector('#chartDonut'), donutOptions).render();

// Legend manual donut
const legendEl = document.getElementById('donutLegend');
donutLabels.forEach((label, i) => {
  legendEl.innerHTML += `
    <div class="d-flex align-items-center justify-content-between mb-1">
      <div class="d-flex align-items-center gap-2">
        <div style="width:9px;height:9px;border-radius:50%;background:${donutColors[i]}"></div>
        <span style="font-size:12px;color:var(--text-subtle)">${label}</span>
      </div>
      <span style="font-size:12px;font-weight:600;color:var(--text-primary)">${donutSeries[i]}%</span>
    </div>`;
});

// ── ApexCharts — Bar ──────────────────────────────────────────
const barOptions = {
  ...window.apexDefaults,
  chart: { ...window.apexDefaults.chart, type: 'bar', height: 260 },
  series: [
    { name: 'Online', data: [44, 55, 41, 67, 22, 43, 61] },
    { name: 'Offline', data: [13, 23, 20, 8, 13, 27, 18] },
  ],
  xaxis: {
    categories: ['Mg 1','Mg 2','Mg 3','Mg 4','Mg 5','Mg 6','Mg 7'],
    axisBorder: { show: false },
    axisTicks:  { show: false },
    labels:     { style: { colors: '#4b5e7a', fontSize: '12px' } },
  },
  yaxis: {
    labels: { style: { colors: '#4b5e7a', fontSize: '11px' } },
  },
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '55%',
      borderRadiusApplication: 'end',
    },
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    labels: { colors: '#7a90b0' },
    markers: { size: 6, shape: 'circle' },
  },
  dataLabels: { enabled: false },
};
new ApexCharts(document.querySelector('#chartBar'), barOptions).render();

// ── DataTable ─────────────────────────────────────────────────
$('#transactionTable').DataTable();
</script>
SCRIPT;

require_once 'includes/footer.php';
?>
