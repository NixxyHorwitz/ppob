<?php
// backoffice/analytics.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Analytics';
$active_menu = 'analytics';

// ══ DATE RANGE ═══════════════════════════════════════════════════════
$period    = $_GET['period']    ?? 'month';
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to']   ?? '');

if ($date_from && $date_to) {
  $period = 'custom';
  $df = $date_from;
  $dt = $date_to;
} else {
  $dt = date('Y-m-d');
  $df = match ($period) {
    '7d'    => date('Y-m-d', strtotime('-6 days')),
    '30d'   => date('Y-m-d', strtotime('-29 days')),
    '90d'   => date('Y-m-d', strtotime('-89 days')),
    'month' => date('Y-m-01'),
    'year'  => date('Y-01-01'),
    default => date('Y-m-01'),
  };
}

$days_diff = max(1, (int)ceil((strtotime($dt) - strtotime($df)) / 86400) + 1);

// ══ AJAX: CEK STATUS TRANSAKSI ════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cek_status_id'])) {
  // Stub — integrasikan dengan API vendor di sini
  $tid = (int)$_POST['cek_status_id'];
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'message' => 'Fitur cek status belum dikonfigurasi ke API vendor.']);
  exit;
}

// ══ SUMMARY (dari report_data.php) ═══════════════════════════════════
$sum_st = $pdo->prepare("
    SELECT
        COUNT(id)                                                           AS total_trx,
        SUM(CASE WHEN status='success' THEN amount   ELSE 0 END)           AS total_omset,
        SUM(CASE WHEN status='success' THEN amount*0.1 ELSE 0 END)         AS total_profit,
        SUM(status='success')                                               AS trx_sukses,
        SUM(status='failed')                                                AS trx_gagal,
        SUM(status='pending')                                               AS trx_pending,
        COUNT(DISTINCT user_id)                                             AS unique_users
    FROM transactions
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$sum_st->execute([$df, $dt]);
$sum_raw = $sum_st->fetch();
$sum = array_map(fn($v) => is_numeric($v) ? (float)$v : $v, $sum_raw ?: []);
$sum += ['total_trx' => 0, 'trx_sukses' => 0, 'trx_gagal' => 0, 'trx_pending' => 0, 'total_omset' => 0, 'total_profit' => 0, 'unique_users' => 0];

// KPI periode sebelumnya
$prev_dt = date('Y-m-d', strtotime($df) - 86400);
$prev_df = date('Y-m-d', strtotime($prev_dt) - ($days_diff - 1) * 86400);
$sum_prev = $pdo->prepare("
    SELECT COUNT(id) AS total_trx,
           SUM(CASE WHEN status='success' THEN amount   ELSE 0 END) AS total_omset,
           SUM(CASE WHEN status='success' THEN amount*0.1 ELSE 0 END) AS total_profit
    FROM transactions WHERE DATE(created_at) BETWEEN ? AND ?
");
$sum_prev->execute([$prev_df, $prev_dt]);
$sp_raw = $sum_prev->fetch();
$sp = array_map(fn($v) => is_numeric($v) ? (float)$v : $v, $sp_raw ?: []);
$sp += ['total_trx' => 0, 'total_omset' => 0, 'total_profit' => 0];

// ══ LAPORAN HARIAN (tabel dari report_data.php) ═══════════════════════
$daily_st = $pdo->prepare("
    SELECT
        DATE(created_at)                                                    AS tgl,
        COUNT(*)                                                            AS total_trx,
        SUM(amount)                                                         AS omset,
        SUM(CASE WHEN status='success' THEN amount*0.1 ELSE 0 END)         AS net_profit,
        SUM(CASE WHEN status='failed'  THEN 1 ELSE 0 END)                  AS total_failed,
        SUM(status='success')                                               AS sukses
    FROM transactions
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) DESC
");
$daily_st->execute([$df, $dt]);
$reports = $daily_st->fetchAll();

// ══ TOPUP SUMMARY ═════════════════════════════════════════════════════
$tkpi = $pdo->prepare("
    SELECT
        COUNT(*)                                                            AS total_topup,
        SUM(status='success')                                               AS topup_sukses,
        COALESCE(SUM(CASE WHEN status='success' THEN amount END),0)        AS topup_masuk,
        COALESCE(SUM(CASE WHEN status='success' THEN amount-amount_original END),0) AS fee_collected
    FROM topup_history WHERE DATE(created_at) BETWEEN ? AND ?
");
$tkpi->execute([$df, $dt]);
$TK_raw = $tkpi->fetch();
$TK = array_map(fn($v) => is_numeric($v) ? (float)$v : $v, $TK_raw ?: []);
$TK += ['total_topup' => 0, 'topup_sukses' => 0, 'topup_masuk' => 0, 'fee_collected' => 0];

// ══ CHART DATA ════════════════════════════════════════════════════════
// Buat map berdasarkan tanggal agar bisa fill zero untuk hari tanpa data
$chart_daily = $pdo->prepare("
    SELECT
        DATE(created_at) AS tgl,
        COUNT(*)                                                            AS total,
        SUM(status='success')                                               AS sukses,
        SUM(status='failed')                                                AS gagal,
        COALESCE(SUM(CASE WHEN status='success' THEN amount END),0)        AS omset,
        COALESCE(SUM(CASE WHEN status='success' THEN amount*0.1 END),0)    AS profit
    FROM transactions WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY tgl ORDER BY tgl ASC
");
$chart_daily->execute([$df, $dt]);
$daily_map = [];
foreach ($chart_daily->fetchAll() as $r) $daily_map[$r['tgl']] = $r;

$chart_topup = $pdo->prepare("
    SELECT DATE(created_at) AS tgl,
           SUM(status='success')                                            AS sukses,
           COALESCE(SUM(CASE WHEN status='success' THEN amount END),0)     AS masuk
    FROM topup_history WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY tgl ORDER BY tgl ASC
");
$chart_topup->execute([$df, $dt]);
$topup_map = [];
foreach ($chart_topup->fetchAll() as $r) $topup_map[$r['tgl']] = $r;

// Fill semua tanggal dalam range
$c_labels = $c_total = $c_sukses = $c_gagal = $c_omset = $c_profit = $c_tu_sukses = $c_tu_masuk = [];
for ($ts = strtotime($df); $ts <= strtotime($dt); $ts += 86400) {
  $d = date('Y-m-d', $ts);
  $c_labels[]    = date('d M', $ts);
  $r  = $daily_map[$d] ?? null;
  $tr = $topup_map[$d] ?? null;
  $c_total[]     = $r  ? (int)$r['total']  : 0;
  $c_sukses[]    = $r  ? (int)$r['sukses'] : 0;
  $c_gagal[]     = $r  ? (int)$r['gagal']  : 0;
  $c_omset[]     = $r  ? (int)$r['omset']  : 0;
  $c_profit[]    = $r  ? (int)$r['profit'] : 0;
  $c_tu_sukses[] = $tr ? (int)$tr['sukses'] : 0;
  $c_tu_masuk[]  = $tr ? (int)$tr['masuk'] : 0;
}

// ══ TOP PRODUK ════════════════════════════════════════════════════════
$top_prods = $pdo->prepare("
    SELECT t.sku_code,
           COALESCE(p.product_name, t.sku_code)   AS nama,
           COALESCE(p.category,'—')               AS kategori,
           COUNT(*)                                AS total,
           SUM(t.status='success')                AS sukses,
           COALESCE(SUM(CASE WHEN t.status='success' THEN t.amount END),0) AS omset
    FROM transactions t
    LEFT JOIN products p ON p.sku_code = t.sku_code
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY t.sku_code ORDER BY total DESC LIMIT 10
");
$top_prods->execute([$df, $dt]);
$top_products = $top_prods->fetchAll();

// ══ TOP USER ══════════════════════════════════════════════════════════
$top_users = $pdo->prepare("
    SELECT u.id, u.username, u.fullname, u.role,
           COUNT(t.id)                                                      AS total_trx,
           COALESCE(SUM(CASE WHEN t.status='success' THEN t.amount END),0) AS total_belanja
    FROM transactions t JOIN users u ON u.id = t.user_id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY t.user_id ORDER BY total_trx DESC LIMIT 8
");
$top_users->execute([$df, $dt]);
$top_u = $top_users->fetchAll();

// ══ MONITORING: 50 TRANSAKSI TERBARU ══════════════════════════════════
$mon_trx = $pdo->query("
    SELECT t.*, u.username
    FROM transactions t
    LEFT JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC LIMIT 50
")->fetchAll();

// ══ HELPERS ═══════════════════════════════════════════════════════════
function an_rp(mixed $n): string
{
  return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
function an_short(mixed $n): string
{
  $n = (float)$n;
  if ($n >= 1_000_000_000) return 'Rp ' . number_format($n / 1_000_000_000, 1, ',', '.') . 'M';
  if ($n >= 1_000_000)     return 'Rp ' . number_format($n / 1_000_000, 1, ',', '.') . 'jt';
  if ($n >= 1_000)         return 'Rp ' . number_format($n / 1_000, 1, ',', '.') . 'rb';
  return an_rp($n);
}
function an_pct(mixed $now, mixed $prev): string
{
  $now  = (float)($now  ?? 0);
  $prev = (float)($prev ?? 0);
  if ($prev == 0) return $now > 0 ? '+100%' : '0%';
  $p = ($now - $prev) / $prev * 100;
  return ($p >= 0 ? '+' : '') . number_format($p, 1) . '%';
}
function an_up(mixed $now, mixed $prev): bool
{
  return (float)($now ?? 0) >= (float)($prev ?? 0);
}

$trx_rate = $sum['total_trx'] > 0 ? round($sum['trx_sukses'] / $sum['total_trx'] * 100, 1) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<div class="toast-wrap"></div>

<!-- ══ PAGE HEADER ══ -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h1>Analytics</h1>
    <nav>
      <ol class="breadcrumb bc">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Analytics</li>
      </ol>
    </nav>
  </div>

  <!-- Periode + custom date -->
  <form method="GET" class="d-flex flex-wrap align-items-center gap-2">
    <?php $periods = ['7d' => '7 Hari', '30d' => '30 Hari', '90d' => '90 Hari', 'month' => 'Bulan Ini', 'year' => 'Tahun Ini'];
    foreach ($periods as $k => $lbl):
      $on = $period === $k;
    ?>
      <a href="?period=<?= $k ?>" style="font-size:12px;font-weight:600;padding:6px 13px;border-radius:7px;border:1px solid;text-decoration:none;white-space:nowrap;<?= $on ? 'background:var(--as);border-color:var(--ba);color:var(--accent)' : 'background:var(--hover);border-color:var(--border);color:var(--sub)' ?>">
        <?= $lbl ?>
      </a>
    <?php endforeach; ?>
    <div style="width:1px;height:22px;background:var(--border)"></div>
    <input type="date" name="date_from" class="fs" value="<?= htmlspecialchars($date_from ?: $df) ?>" style="font-size:12px;padding:5px 9px" />
    <span style="font-size:12px;color:var(--mut)">–</span>
    <input type="date" name="date_to" class="fs" value="<?= htmlspecialchars($date_to ?: $dt) ?>" style="font-size:12px;padding:5px 9px" />
    <button type="submit" class="btn btn-primary btn-sm" style="border-radius:7px;font-size:12px;padding:6px 14px">Terapkan</button>
    <button type="button" onclick="window.print()" class="btn btn-sm" style="border-radius:7px;font-size:12px;padding:6px 13px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">
      <i class="ph ph-printer me-1"></i>Cetak
    </button>
  </form>
</div>

<!-- Periode label -->
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
  <i class="ph ph-calendar-blank" style="color:var(--mut);font-size:14px"></i>
  <span style="font-size:12px;color:var(--mut)">
    <?= date('d M Y', strtotime($df)) ?> — <?= date('d M Y', strtotime($dt)) ?>
    <span style="color:var(--sub);margin-left:4px">(<?= $days_diff ?> hari)</span>
  </span>
</div>

<!-- ══ SECTION: KPI UTAMA ══ -->
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--mut);margin-bottom:12px">
  <i class="ph ph-chart-bar me-1"></i>Ringkasan Transaksi
</div>
<div class="row g-3 mb-3">
  <?php
  $kpis = [
    [
      'Total Transaksi',
      number_format($sum['total_trx']),
      'ph-swap',
      'blue',
      an_pct($sum['total_trx'], $sp['total_trx']),
      an_up($sum['total_trx'], $sp['total_trx'])
    ],
    [
      'Total Omset',
      an_short($sum['total_omset']),
      'ph-currency-dollar',
      'green',
      an_pct($sum['total_omset'], $sp['total_omset']),
      an_up($sum['total_omset'], $sp['total_omset'])
    ],
    [
      'Est. Net Profit',
      an_short($sum['total_profit']),
      'ph-trend-up',
      'purple',
      an_pct($sum['total_profit'], $sp['total_profit']),
      an_up($sum['total_profit'], $sp['total_profit'])
    ],
    [
      'Success Rate',
      $trx_rate . '%',
      'ph-check-circle',
      'orange',
      number_format($sum['trx_sukses']) . ' sukses · ' . number_format($sum['trx_gagal']) . ' gagal',
      true
    ],
  ];
  foreach ($kpis as [$lbl, $val, $ico, $cls, $sub, $up]):
  ?>
    <div class="col-6 col-lg-3">
      <div class="sc <?= $cls ?>">
        <div class="si <?= $cls ?>"><i class="ph <?= $ico ?>"></i></div>
        <div class="sv"><?= $val ?></div>
        <div class="sl"><?= $lbl ?></div>
        <div style="margin-top:8px;font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px;color:<?= $up ? 'var(--ok)' : 'var(--err)' ?>">
          <i class="ph <?= $up ? 'ph-trend-up' : 'ph-trend-down' ?>"></i>
          <span><?= $sub ?><?= ($lbl !== 'Success Rate') ? ' <span style="color:var(--mut);font-weight:400">vs sblm</span>' : '' ?></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- KPI ROW 2: Topup + User -->
<div class="row g-3 mb-4">
  <?php
  $kpi2 = [
    ['ph-arrow-circle-down', '#a855f7', 'Total Topup Masuk', an_short($TK['topup_masuk']),   number_format($TK['topup_sukses']) . ' topup sukses'],
    ['ph-coins',            '#f59e0b', 'Fee Topup',         an_short($TK['fee_collected']),  number_format($TK['total_topup']) . ' topup masuk'],
    ['ph-users-three',      '#3b82f6', 'User Aktif Trx',    number_format($sum['unique_users']), 'unik dalam periode ini'],
    ['ph-clock',            '#ef4444', 'Trx Pending',       number_format($sum['trx_pending']),  'menunggu konfirmasi'],
  ];
  foreach ($kpi2 as [$ico, $clr, $lbl, $val, $sub2]):
  ?>
    <div class="col-6 col-lg-3">
      <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:16px 18px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
          <div style="width:36px;height:36px;border-radius:9px;background:<?= $clr ?>1a;color:<?= $clr ?>;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">
            <i class="ph <?= $ico ?>"></i>
          </div>
          <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--mut)"><?= $lbl ?></span>
        </div>
        <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;color:<?= $clr ?>"><?= $val ?></div>
        <div style="font-size:11px;color:var(--mut);margin-top:3px"><?= $sub2 ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ══ SECTION: CHARTS ══ -->
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--mut);margin-bottom:12px">
  <i class="ph ph-chart-line-up me-1"></i>Grafik Harian
</div>
<div class="row g-3 mb-4">

  <!-- Chart: Volume -->
  <div class="col-lg-8">
    <div class="card-c" style="padding:20px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
        <div>
          <div style="font-size:14px;font-weight:700">Volume Transaksi</div>
          <div style="font-size:11px;color:var(--mut)">Jumlah harian dalam periode</div>
        </div>
        <div style="display:flex;gap:12px;font-size:11px;font-weight:600;flex-wrap:wrap">
          <span style="color:#3b82f6;display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:50%;background:#3b82f6;display:inline-block"></span>Total</span>
          <span style="color:#10b981;display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block"></span>Sukses</span>
          <span style="color:#ef4444;display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block"></span>Gagal</span>
        </div>
      </div>
      <canvas id="chartVolume" height="100"></canvas>
    </div>
  </div>

  <!-- Chart: Donut status -->
  <div class="col-lg-4">
    <div class="card-c" style="padding:20px;height:100%">
      <div style="font-size:14px;font-weight:700;margin-bottom:2px">Distribusi Status</div>
      <div style="font-size:11px;color:var(--mut);margin-bottom:14px">Proporsi periode ini</div>
      <div style="display:flex;justify-content:center">
        <canvas id="chartDonut" style="max-height:160px;max-width:160px"></canvas>
      </div>
      <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
        <?php
        foreach ([['Sukses', (int)$sum['trx_sukses'], '#10b981'], ['Pending', (int)$sum['trx_pending'], '#f59e0b'], ['Gagal', (int)$sum['trx_gagal'], '#ef4444']] as [$sl, $sv, $sc]):
          $sp2 = $sum['total_trx'] > 0 ? round($sv / $sum['total_trx'] * 100, 1) : 0;
        ?>
          <div style="display:flex;align-items:center;gap:8px;font-size:12px">
            <div style="width:8px;height:8px;border-radius:50%;background:<?= $sc ?>;flex-shrink:0"></div>
            <span style="flex:1;color:var(--sub)"><?= $sl ?></span>
            <span style="font-family:'JetBrains Mono',monospace;font-weight:700"><?= number_format($sv) ?></span>
            <span style="color:var(--mut);width:38px;text-align:right"><?= $sp2 ?>%</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Chart: Omset & Profit -->
  <div class="col-lg-8">
    <div class="card-c" style="padding:20px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
        <div>
          <div style="font-size:14px;font-weight:700">Omset & Net Profit</div>
          <div style="font-size:11px;color:var(--mut)">Harian (Rp)</div>
        </div>
        <div style="display:flex;gap:12px;font-size:11px;font-weight:600">
          <span style="color:#3b82f6;display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#3b82f6;display:inline-block"></span>Omset</span>
          <span style="color:#10b981;display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block"></span>Profit</span>
        </div>
      </div>
      <canvas id="chartOmset" height="100"></canvas>
    </div>
  </div>

  <!-- Chart: Topup harian -->
  <div class="col-lg-4">
    <div class="card-c" style="padding:20px;height:100%">
      <div style="font-size:14px;font-weight:700;margin-bottom:2px">Topup Harian</div>
      <div style="font-size:11px;color:var(--mut);margin-bottom:14px">Jumlah topup sukses per hari</div>
      <canvas id="chartTopup" height="160"></canvas>
    </div>
  </div>
</div>

<!-- ══ SECTION: TOP PRODUK & USER ══ -->
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--mut);margin-bottom:12px">
  <i class="ph ph-star me-1"></i>Top Produk & User
</div>
<div class="row g-3 mb-4">

  <!-- Top Produk -->
  <div class="col-lg-7">
    <div class="card-c">
      <div style="padding:16px 20px 0;display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-size:14px;font-weight:700">Top 10 Produk</div>
          <div style="font-size:11px;color:var(--mut);margin-top:2px">Berdasarkan volume transaksi</div>
        </div>
        <span class="bd bd-acc"><?= count($top_products) ?> produk</span>
      </div>
      <div style="overflow-x:auto;margin-top:8px">
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:24px">#</th>
              <th>Produk</th>
              <th>Kategori</th>
              <th style="text-align:right">Trx</th>
              <th style="text-align:right">Rate</th>
              <th style="text-align:right">Omset</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($top_products)): ?>
              <tr>
                <td colspan="6" style="text-align:center;padding:28px;color:var(--mut)">Tidak ada data produk</td>
              </tr>
            <?php else: ?>
              <?php
              $mx = max(1, (int)($top_products[0]['total'] ?? 1));
              foreach ($top_products as $i => $p):
                $bw   = round($p['total'] / $mx * 100);
                $rate = $p['total'] > 0 ? round($p['sukses'] / $p['total'] * 100) : 0;
                $rc   = $rate >= 80 ? 'var(--ok)' : ($rate >= 50 ? 'var(--warn)' : 'var(--err)');
              ?>
                <tr>
                  <td style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--mut)"><?= $i + 1 ?></td>
                  <td>
                    <div style="font-size:13px;font-weight:600;max-width:190px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($p['nama']) ?></div>
                    <div style="height:3px;background:var(--hover);border-radius:99px;margin-top:4px;max-width:160px">
                      <div style="height:100%;width:<?= $bw ?>%;background:var(--accent);border-radius:99px"></div>
                    </div>
                  </td>
                  <td><span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;background:var(--hover);color:var(--sub)"><?= htmlspecialchars($p['kategori']) ?></span></td>
                  <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700"><?= number_format($p['total']) ?></td>
                  <td style="text-align:right;font-size:11px;font-weight:700;color:<?= $rc ?>"><?= $rate ?>%</td>
                  <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-size:11px;white-space:nowrap"><?= an_short($p['omset']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Top User -->
  <div class="col-lg-5">
    <div class="card-c">
      <div style="padding:16px 20px 0;display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-size:14px;font-weight:700">Top User</div>
          <div style="font-size:11px;color:var(--mut);margin-top:2px">Paling aktif bertransaksi</div>
        </div>
        <span class="bd bd-acc"><?= count($top_u) ?> user</span>
      </div>
      <div style="padding:8px 0">
        <?php if (empty($top_u)): ?>
          <div style="text-align:center;padding:28px;color:var(--mut);font-size:12px">Tidak ada data</div>
        <?php else: ?>
          <?php
          $mu = max(1, (int)($top_u[0]['total_trx'] ?? 1));
          foreach ($top_u as $i => $u):
            $ub  = round($u['total_trx'] / $mu * 100);
            $uc  = match ($u['role']) {
              'admin' => '#ef4444',
              'reseller' => '#a855f7',
              default => '#3b82f6'
            };
          ?>
            <div style="display:flex;align-items:center;gap:10px;padding:9px 20px;<?= $i < count($top_u) - 1 ? 'border-bottom:1px solid rgba(255,255,255,.03)' : '' ?>">
              <div style="font-size:11px;font-weight:700;color:var(--mut);width:16px;text-align:right;flex-shrink:0"><?= $i + 1 ?></div>
              <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['fullname'] ?: $u['username']) ?>&background=<?= ltrim($uc, '#') ?>&color=fff&size=64&bold=true"
                style="width:30px;height:30px;border-radius:50%;flex-shrink:0" alt="" />
              <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:6px">
                  <span style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($u['fullname'] ?: $u['username']) ?></span>
                  <span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:99px;background:<?= $uc ?>22;color:<?= $uc ?>;flex-shrink:0"><?= $u['role'] ?></span>
                </div>
                <div style="height:3px;background:var(--hover);border-radius:99px;margin-top:3px">
                  <div style="height:100%;width:<?= $ub ?>%;background:<?= $uc ?>;border-radius:99px"></div>
                </div>
              </div>
              <div style="text-align:right;flex-shrink:0">
                <div style="font-size:13px;font-weight:700;font-family:'JetBrains Mono',monospace"><?= number_format($u['total_trx']) ?></div>
                <div style="font-size:10px;color:var(--mut)"><?= an_short($u['total_belanja']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══ SECTION: LAPORAN HARIAN (dari report_data.php) ══ -->
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--mut);margin-bottom:12px">
  <i class="ph ph-file-text me-1"></i>Laporan Penjualan Harian
</div>
<div class="card-c mb-4">
  <div style="padding:16px 20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <div>
      <div style="font-size:14px;font-weight:700">Rincian Per Hari</div>
      <div style="font-size:11px;color:var(--mut)">Diurutkan terbaru</div>
    </div>
    <!-- Summary box kecil -->
    <div style="display:flex;gap:16px;flex-wrap:wrap">
      <?php
      $sbox = [
        ['Omset Sukses',  an_rp($sum['total_omset']),  '#3b82f6'],
        ['Net Profit',    an_rp($sum['total_profit']),  '#10b981'],
        ['Vol. Transaksi', number_format($sum['total_trx']) . ' Trx', 'var(--sub)'],
      ];
      foreach ($sbox as [$sl, $sv, $sc]):
      ?>
        <div style="text-align:right">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--mut)"><?= $sl ?></div>
          <div style="font-size:15px;font-weight:700;font-family:'JetBrains Mono',monospace;color:<?= $sc ?>"><?= $sv ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div style="overflow-x:auto;margin-top:10px">
    <table class="tbl">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th style="text-align:right">Total Trx</th>
          <th style="text-align:right">Sukses</th>
          <th style="text-align:right">Gagal</th>
          <th style="text-align:right">Omset Bruto</th>
          <th style="text-align:right">Net Profit</th>
          <th style="text-align:right">Rate</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reports)): ?>
          <tr>
            <td colspan="7" style="text-align:center;padding:32px;color:var(--mut)">
              <i class="ph ph-file-text" style="font-size:28px;display:block;margin-bottom:8px;opacity:.3"></i>
              Tidak ada data pada periode ini
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($reports as $r):
            $dr = $r['total_trx'] > 0 ? round($r['sukses'] / $r['total_trx'] * 100, 1) : 0;
            $drc = $dr >= 80 ? 'var(--ok)' : ($dr >= 50 ? 'var(--warn)' : 'var(--err)');
          ?>
            <tr>
              <td style="font-weight:600"><?= date('D, d M Y', strtotime($r['tgl'])) ?></td>
              <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-weight:700"><?= number_format($r['total_trx']) ?></td>
              <td style="text-align:right">
                <span class="bd bd-ok"><?= number_format($r['sukses']) ?></span>
              </td>
              <td style="text-align:right">
                <?php if ($r['total_failed'] > 0): ?>
                  <span class="bd bd-err"><?= number_format($r['total_failed']) ?></span>
                <?php else: ?>
                  <span style="color:var(--mut);font-size:12px">—</span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-size:12px"><?= an_rp($r['omset']) ?></td>
              <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700;color:var(--ok)"><?= an_rp($r['net_profit']) ?></td>
              <td style="text-align:right;font-size:12px;font-weight:700;color:<?= $drc ?>"><?= $dr ?>%</td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══ SECTION: MONITORING TRANSAKSI TERBARU ══ -->
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--mut);margin-bottom:12px">
  <i class="ph ph-activity me-1"></i>Monitoring Transaksi Terbaru
</div>
<div class="card-c mb-4">
  <div style="padding:16px 20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div>
      <div style="font-size:14px;font-weight:700">50 Transaksi Terakhir</div>
      <div style="font-size:11px;color:var(--mut)">Real-time dari seluruh periode</div>
    </div>
    <!-- Search inline -->
    <div style="position:relative">
      <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mut);font-size:15px;pointer-events:none"></i>
      <input type="text" id="monSearch" placeholder="Cari ref, target, SKU, user…"
        style="background:var(--hover);border:1px solid var(--border);color:var(--text);border-radius:8px;padding:7px 12px 7px 32px;font-size:12px;width:240px;outline:none;font-family:'Plus Jakarta Sans',sans-serif"
        oninput="monFilter(this.value)" />
    </div>
  </div>

  <!-- Desktop table -->
  <div class="d-none d-md-block" style="overflow-x:auto;margin-top:10px">
    <table class="tbl" id="monTable">
      <thead>
        <tr>
          <th>Waktu</th>
          <th>TRX ID / Ref</th>
          <th>User</th>
          <th>SKU / Produk</th>
          <th>Target</th>
          <th style="text-align:right">Harga</th>
          <th>Status</th>
          <th style="max-width:120px">SN</th>
          <th style="width:80px;text-align:center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($mon_trx)): ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:32px;color:var(--mut)">Belum ada transaksi</td>
          </tr>
        <?php else: ?>
          <?php foreach ($mon_trx as $h): ?>
            <tr id="monrow-<?= $h['id'] ?>">
              <td style="white-space:nowrap;font-size:12px">
                <div><?= date('d M Y', strtotime($h['created_at'])) ?></div>
                <div style="color:var(--mut)"><?= date('H:i:s', strtotime($h['created_at'])) ?></div>
              </td>
              <td>
                <div style="font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:700">#<?= $h['id'] ?></div>
                <?php if ($h['ref_id']): ?>
                  <div style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--mut)"><?= htmlspecialchars($h['ref_id']) ?></div>
                <?php endif; ?>
              </td>
              <td style="font-size:12px"><?= htmlspecialchars($h['username'] ?? '—') ?></td>
              <td style="font-size:12px;max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($h['sku_code'] ?? '—') ?></td>
              <td>
                <span style="font-family:'JetBrains Mono',monospace;font-size:11px;background:var(--hover);padding:2px 7px;border-radius:5px;border:1px solid var(--border)">
                  <?= htmlspecialchars($h['target'] ?? '—') ?>
                </span>
              </td>
              <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700;white-space:nowrap">
                <?= an_rp($h['amount']) ?>
              </td>
              <td>
                <?php if ($h['status'] === 'success'): ?>
                  <span class="bd bd-ok"><i class="ph ph-check-circle"></i>Sukses</span>
                <?php elseif ($h['status'] === 'failed'): ?>
                  <span class="bd bd-err"><i class="ph ph-x-circle"></i>Gagal</span>
                <?php else: ?>
                  <span class="bd bd-warn"><i class="ph ph-clock"></i>Pending</span>
                <?php endif; ?>
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--sub);max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                title="<?= htmlspecialchars($h['sn'] ?? '') ?>">
                <?= htmlspecialchars($h['sn'] ?: '—') ?>
              </td>
              <td style="text-align:center">
                <?php if ($h['status'] === 'pending'): ?>
                  <button class="ab" title="Cek Status" onclick="cekStatus(<?= $h['id'] ?>, this)">
                    <i class="ph ph-arrows-clockwise"></i>
                  </button>
                <?php else: ?>
                  <i class="ph ph-check-circle" style="color:var(--ok);font-size:16px"></i>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Mobile cards -->
  <div class="d-md-none" style="padding:12px 16px;display:flex;flex-direction:column;gap:8px" id="monCards">
    <?php foreach ($mon_trx as $h): ?>
      <div class="mon-card" style="background:var(--hover);border:1px solid var(--border);border-radius:9px;padding:12px;border-left:3px solid <?= $h['status'] === 'success' ? 'var(--ok)' : ($h['status'] === 'failed' ? 'var(--err)' : 'var(--warn)') ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
          <div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700">#<?= $h['id'] ?> <?= $h['ref_id'] ? '· ' . $h['ref_id'] : '' ?></div>
            <div style="font-size:11px;color:var(--mut)"><?= date('d M Y H:i', strtotime($h['created_at'])) ?></div>
          </div>
          <?php if ($h['status'] === 'success'): ?><span class="bd bd-ok"><i class="ph ph-check-circle"></i>Sukses</span>
          <?php elseif ($h['status'] === 'failed'): ?><span class="bd bd-err"><i class="ph ph-x-circle"></i>Gagal</span>
          <?php else:                            ?><span class="bd bd-warn"><i class="ph ph-clock"></i>Pending</span>
          <?php endif; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;font-size:11px">
          <div><span style="color:var(--mut)">SKU:</span> <strong><?= htmlspecialchars($h['sku_code'] ?? '—') ?></strong></div>
          <div><span style="color:var(--mut)">Target:</span> <strong style="font-family:'JetBrains Mono',monospace"><?= htmlspecialchars($h['target'] ?? '—') ?></strong></div>
          <div><span style="color:var(--mut)">Harga:</span> <strong style="font-family:'JetBrains Mono',monospace"><?= an_rp($h['amount']) ?></strong></div>
          <div><span style="color:var(--mut)">User:</span> <strong><?= htmlspecialchars($h['username'] ?? '—') ?></strong></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php
$j_labels      = json_encode($c_labels);
$j_total       = json_encode($c_total);
$j_sukses      = json_encode($c_sukses);
$j_gagal       = json_encode($c_gagal);
$j_omset       = json_encode($c_omset);
$j_profit      = json_encode($c_profit);
$j_tu_sukses   = json_encode($c_tu_sukses);
$j_donut       = json_encode([(int)$sum['trx_sukses'], (int)$sum['trx_pending'], (int)$sum['trx_gagal']]);

$page_scripts = <<<SCRIPT
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Chart defaults ────────────────────────────────────────────
Chart.defaults.color       = '#4b5e7a';
Chart.defaults.borderColor = 'rgba(255,255,255,.05)';
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.plugins.legend.display = false;

const labels     = {$j_labels};
const dTotal     = {$j_total};
const dSukses    = {$j_sukses};
const dGagal     = {$j_gagal};
const dOmset     = {$j_omset};
const dProfit    = {$j_profit};
const dTuSukses  = {$j_tu_sukses};
const dDonut     = {$j_donut};

const tooltipDef = {
  backgroundColor: '#131d30',
  borderColor: 'rgba(255,255,255,.08)',
  borderWidth: 1,
  padding: 10,
  titleFont: { weight:'700' },
  bodyFont: { size: 12 }
};

const scalesLine = {
  x: { grid:{display:false}, ticks:{color:'#4b5e7a', maxTicksLimit:12, font:{size:11}} },
  y: { grid:{color:'rgba(255,255,255,.04)'}, ticks:{color:'#4b5e7a', font:{size:11}} }
};

// ── Chart: Volume ─────────────────────────────────────────────
new Chart(document.getElementById('chartVolume'), {
  type: 'line',
  data: { labels, datasets: [
    { label:'Total',  data:dTotal,  borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.08)', fill:true, tension:.4, pointRadius:2, pointHoverRadius:5, borderWidth:2 },
    { label:'Sukses', data:dSukses, borderColor:'#10b981', backgroundColor:'transparent', fill:false, tension:.4, pointRadius:2, pointHoverRadius:5, borderWidth:2 },
    { label:'Gagal',  data:dGagal,  borderColor:'#ef4444', backgroundColor:'transparent', fill:false, tension:.4, pointRadius:2, pointHoverRadius:5, borderWidth:1.5, borderDash:[4,3] },
  ]},
  options: { responsive:true, interaction:{mode:'index',intersect:false}, plugins:{legend:{display:false},tooltip:tooltipDef}, scales:scalesLine }
});

// ── Chart: Donut ──────────────────────────────────────────────
new Chart(document.getElementById('chartDonut'), {
  type: 'doughnut',
  data: { labels:['Sukses','Pending','Gagal'], datasets:[{ data:dDonut, backgroundColor:['#10b981','#f59e0b','#ef4444'], borderWidth:0, hoverOffset:6 }] },
  options: { responsive:true, cutout:'72%', plugins:{legend:{display:false},tooltip:{...tooltipDef}} }
});

// ── Chart: Omset & Profit ─────────────────────────────────────
new Chart(document.getElementById('chartOmset'), {
  type: 'bar',
  data: { labels, datasets: [
    { label:'Omset', data:dOmset, backgroundColor:'rgba(59,130,246,.5)', borderRadius:4, order:2 },
    { label:'Profit', data:dProfit, type:'line', borderColor:'#10b981', backgroundColor:'transparent', fill:false, tension:.4, pointRadius:2, borderWidth:2, order:1 },
  ]},
  options: {
    responsive:true, interaction:{mode:'index',intersect:false},
    plugins:{legend:{display:false},tooltip:tooltipDef},
    scales:{
      x:{grid:{display:false},ticks:{color:'#4b5e7a',maxTicksLimit:12,font:{size:11}}},
      y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#4b5e7a',font:{size:11},callback:v=>v>=1e6?(v/1e6).toFixed(1)+'jt':v>=1e3?(v/1e3).toFixed(0)+'rb':v}}
    }
  }
});

// ── Chart: Topup ──────────────────────────────────────────────
new Chart(document.getElementById('chartTopup'), {
  type: 'bar',
  data: { labels, datasets:[
    { label:'Sukses', data:dTuSukses, backgroundColor:'rgba(168,85,247,.6)', borderRadius:3 }
  ]},
  options: {
    responsive:true,
    plugins:{legend:{display:false},tooltip:tooltipDef},
    scales:{
      x:{grid:{display:false},ticks:{color:'#4b5e7a',maxTicksLimit:10,font:{size:10}}},
      y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#4b5e7a',font:{size:10},precision:0}}
    }
  }
});

// ── Monitoring: filter ────────────────────────────────────────
function monFilter(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#monTable tbody tr').forEach(tr => {
    tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
  });
  document.querySelectorAll('#monCards .mon-card').forEach(c => {
    c.style.display = c.innerText.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── Monitoring: cek status ────────────────────────────────────
function cekStatus(id, btn) {
  btn.disabled = true;
  btn.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.2);border-top-color:#fff;border-radius:50%;animation:anSpin .6s linear infinite"></span>';

  const fd = new FormData();
  fd.append('cek_status_id', id);

  fetch('analytics.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        location.reload();
      } else {
        alert(data.message || 'Tidak ada respons dari vendor.');
        btn.disabled = false;
        btn.innerHTML = '<i class="ph ph-arrows-clockwise"></i>';
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="ph ph-arrows-clockwise"></i>';
    });
}

// ── Toast auto-dismiss ────────────────────────────────────────
document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => t.style.opacity = '0', 3500);
  setTimeout(() => t.remove(), 4000);
});
</script>
<style>
@keyframes anSpin { to { transform: rotate(360deg); } }
@media print {
  .sidebar, .topbar, .sb-overlay, form, button, .ab { display:none !important; }
  .main-content { margin-left:0 !important; padding-top:0 !important; }
  .card-c, .sc { break-inside:avoid; border:1px solid #ddd !important; }
}
</style>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>