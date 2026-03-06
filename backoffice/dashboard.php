<?php
// backoffice/dashboard.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Dashboard';
$active_menu = 'dashboard';

require_once __DIR__ . '/includes/header.php';

// ══ HELPERS ══════════════════════════════════════════════════
function fmt_rp(float $n): string {
    if ($n >= 1_000_000_000) return 'Rp ' . number_format($n / 1_000_000_000, 1, ',', '.') . 'M';
    if ($n >= 1_000_000)     return 'Rp ' . number_format($n / 1_000_000, 1, ',', '.') . 'jt';
    if ($n >= 1_000)         return 'Rp ' . number_format($n / 1_000, 1, ',', '.') . 'rb';
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function fmt_rp_full(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function time_ago(string $ts): string {
    $d = time() - strtotime($ts);
    if ($d < 60)     return 'Baru saja';
    if ($d < 3600)   return floor($d/60) . ' mnt lalu';
    if ($d < 86400)  return floor($d/3600) . ' jam lalu';
    if ($d < 604800) return floor($d/86400) . ' hari lalu';
    return date('d M Y', strtotime($ts));
}

// ══ STAT CARDS ═══════════════════════════════════════════════
$rev_all   = $pdo->query("SELECT COALESCE(SUM(amount),0) v, COUNT(*) c FROM transactions WHERE status='success'")->fetch();
$total_revenue = (float)$rev_all['v'];
$total_orders  = (int)$rev_all['c'];

$rev_this = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status='success' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$rev_last = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status='success' AND MONTH(created_at)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(created_at)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))")->fetchColumn();
$rev_trend = $rev_last > 0 ? round((($rev_this - $rev_last) / $rev_last) * 100, 1) : 0;

$ord_this  = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$ord_last  = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE MONTH(created_at)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(created_at)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))")->fetchColumn();
$ord_trend = $ord_last > 0 ? round((($ord_this - $ord_last) / $ord_last) * 100, 1) : 0;

$user_stats  = $pdo->query("SELECT COUNT(*) total, SUM(is_active=1) aktif FROM users")->fetch();
$user_new    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY")->fetchColumn();
$total_saldo = (float)$pdo->query("SELECT COALESCE(SUM(saldo),0) FROM users")->fetchColumn();
$topup_in    = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM topup_history WHERE status='success'")->fetchColumn();

// ══ STATUS COUNTS ═════════════════════════════════════════════
$status_rows = $pdo->query("SELECT status, COUNT(*) c FROM transactions GROUP BY status")->fetchAll(\PDO::FETCH_KEY_PAIR);
$s_success = (int)($status_rows['success'] ?? 0);
$s_pending = (int)($status_rows['pending'] ?? 0);
$s_failed  = (int)($status_rows['failed']  ?? 0);
$s_total   = max(1, $s_success + $s_pending + $s_failed);

$total_prod = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn();

// ══ CHART — Revenue 12 bulan ══════════════════════════════════
$monthly_raw = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') ym,
           DATE_FORMAT(created_at,'%b') lbl,
           COALESCE(SUM(amount),0) total
    FROM transactions
    WHERE status='success'
      AND created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 11 MONTH),'%Y-%m-01')
    GROUP BY ym, lbl ORDER BY ym ASC
")->fetchAll();

$monthly_map = [];
for ($i = 11; $i >= 0; $i--) {
    $ym  = date('Y-m', strtotime("-$i months"));
    $lbl = date('M',   strtotime("-$i months"));
    $monthly_map[$ym] = ['lbl' => $lbl, 'total' => 0];
}
foreach ($monthly_raw as $r) {
    if (isset($monthly_map[$r['ym']])) $monthly_map[$r['ym']]['total'] = (float)$r['total'];
}
$chart_labels  = json_encode(array_values(array_map(fn($r) => $r['lbl'],   $monthly_map)));
$chart_revenue = json_encode(array_values(array_map(fn($r) => round($r['total'] / 1000, 1), $monthly_map)));

// ══ CHART — Bar 7 hari ═══════════════════════════════════════
$daily_raw = $pdo->query("
    SELECT DATE(created_at) d,
           DATE_FORMAT(created_at,'%a') lbl,
           SUM(status='success') sukses,
           SUM(status='failed')  gagal
    FROM transactions
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY d, lbl ORDER BY d ASC
")->fetchAll();
$daily_map = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $daily_map[$d] = ['lbl' => date('D', strtotime("-$i days")), 'sukses' => 0, 'gagal' => 0];
}
foreach ($daily_raw as $r) {
    if (isset($daily_map[$r['d']])) {
        $daily_map[$r['d']]['sukses'] = (int)$r['sukses'];
        $daily_map[$r['d']]['gagal']  = (int)$r['gagal'];
    }
}
$bar_labels = json_encode(array_values(array_map(fn($r) => $r['lbl'],   $daily_map)));
$bar_sukses = json_encode(array_values(array_map(fn($r) => $r['sukses'], $daily_map)));
$bar_gagal  = json_encode(array_values(array_map(fn($r) => $r['gagal'],  $daily_map)));

// ══ TOP PRODUCTS ══════════════════════════════════════════════
$top_products = $pdo->query("
    SELECT t.sku_code,
           COALESCE(p.product_name, t.sku_code) product_name,
           COALESCE(p.category,'—') category,
           COUNT(*) sold,
           COALESCE(SUM(t.amount),0) revenue
    FROM transactions t
    LEFT JOIN products p ON p.sku_code = t.sku_code
    WHERE t.status = 'success'
    GROUP BY t.sku_code, product_name, category
    ORDER BY sold DESC LIMIT 5
")->fetchAll();
$max_sold = max(1, (int)($top_products[0]['sold'] ?? 1));

// ══ TRANSAKSI TERBARU ═════════════════════════════════════════
$recent_trx = $pdo->query("
    SELECT t.*, u.username, u.fullname,
           COALESCE(p.product_name, t.sku_code) product_name,
           COALESCE(p.category,'—') category
    FROM transactions t
    LEFT JOIN users u ON u.id = t.user_id
    LEFT JOIN products p ON p.sku_code = t.sku_code
    ORDER BY t.created_at DESC LIMIT 10
")->fetchAll();

// ══ TOPUP TERBARU ════════════════════════════════════════════
$recent_topup = $pdo->query("
    SELECT th.*, u.username, u.fullname
    FROM topup_history th
    LEFT JOIN users u ON u.id = th.user_id
    ORDER BY th.created_at DESC LIMIT 5
")->fetchAll();

// ══ AKTIVITAS ════════════════════════════════════════════════
$activities = $pdo->query("
    SELECT n.title, n.message, n.created_at, n.is_read, u.username
    FROM notifications n
    LEFT JOIN users u ON u.id = n.user_id
    WHERE n.title NOT LIKE '[SISTEM]%'
    ORDER BY n.created_at DESC LIMIT 8
")->fetchAll();

// ══ TOP KATEGORI ══════════════════════════════════════════════
$top_cats = $pdo->query("
    SELECT COALESCE(p.category,'Lainnya') cat, COUNT(*) c, COALESCE(SUM(t.amount),0) rev
    FROM transactions t
    LEFT JOIN products p ON p.sku_code = t.sku_code
    WHERE t.status = 'success'
    GROUP BY cat ORDER BY c DESC LIMIT 5
")->fetchAll();
$cat_total = max(1, array_sum(array_column($top_cats, 'c')));
?>

<!-- PAGE HEADER -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h1>Dashboard</h1>
    <nav><ol class="breadcrumb bc"><li class="breadcrumb-item active">Home</li></ol></nav>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <span style="font-size:12px;color:var(--mut)">Update: <?= date('d M Y H:i') ?></span>
    <a href="dashboard.php" class="btn btn-sm" style="border-radius:8px;background:var(--hover);border:1px solid var(--border);color:var(--sub);padding:7px 14px;font-size:12px">
      <i class="ph ph-arrows-clockwise me-1"></i>Refresh
    </a>
  </div>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="row g-3 mb-4">

  <div class="col-xl-3 col-sm-6">
    <div class="sc blue">
      <div class="si blue"><i class="ph-fill ph-currency-dollar"></i></div>
      <div class="sv"><?= fmt_rp($total_revenue) ?></div>
      <div class="sl">Total Revenue</div>
      <div class="mt-2" style="font-size:11px;color:<?= $rev_trend >= 0 ? 'var(--ok)' : 'var(--err)' ?>">
        <i class="ph ph-trend-<?= $rev_trend >= 0 ? 'up' : 'down' ?>"></i>
        <?= $rev_trend >= 0 ? '+' : '' ?><?= $rev_trend ?>% bulan ini
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="sc green">
      <div class="si green"><i class="ph-fill ph-receipt"></i></div>
      <div class="sv"><?= number_format($total_orders) ?></div>
      <div class="sl">Transaksi Sukses</div>
      <div class="mt-2" style="font-size:11px;color:<?= $ord_trend >= 0 ? 'var(--ok)' : 'var(--err)' ?>">
        <i class="ph ph-trend-<?= $ord_trend >= 0 ? 'up' : 'down' ?>"></i>
        <?= $ord_trend >= 0 ? '+' : '' ?><?= $ord_trend ?>% vs bulan lalu
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="sc orange">
      <div class="si orange"><i class="ph-fill ph-users-three"></i></div>
      <div class="sv"><?= number_format($user_stats['total']) ?></div>
      <div class="sl">Total User</div>
      <div class="mt-2" style="font-size:11px;color:var(--mut)">
        <i class="ph ph-user-check" style="color:var(--ok)"></i> <?= $user_stats['aktif'] ?> aktif ·
        <i class="ph ph-user-plus" style="color:var(--accent)"></i> +<?= $user_new ?> (30hr)
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="sc purple">
      <div class="si purple"><i class="ph-fill ph-wallet"></i></div>
      <div class="sv"><?= fmt_rp($total_saldo) ?></div>
      <div class="sl">Saldo Beredar</div>
      <div class="mt-2" style="font-size:11px;color:var(--mut)">
        <i class="ph ph-arrow-circle-up" style="color:var(--ok)"></i> Topup masuk: <?= fmt_rp($topup_in) ?>
      </div>
    </div>
  </div>

</div>

<!-- ══ SECONDARY STATS ══ -->
<div class="row g-3 mb-4">
  <?php
  $mini = [
    ['Transaksi Sukses',  $s_success,  'var(--ok)',   'var(--oks)', 'ph-fill ph-check-circle'],
    ['Transaksi Pending', $s_pending,  'var(--warn)', 'var(--ws)',  'ph-fill ph-clock'],
    ['Transaksi Gagal',   $s_failed,   'var(--err)',  'var(--es)',  'ph-fill ph-x-circle'],
    ['Produk Aktif',      $total_prod, 'var(--accent)','var(--as)', 'ph-fill ph-storefront'],
  ];
  foreach ($mini as [$lbl, $val, $col, $bg, $ico]): ?>
  <div class="col-xl-3 col-sm-6">
    <div class="card-c p-3 d-flex align-items-center gap-3">
      <div style="width:42px;height:42px;border-radius:10px;background:<?=$bg?>;display:flex;align-items:center;justify-content:center;font-size:21px;color:<?=$col?>;flex-shrink:0">
        <i class="<?=$ico?>"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace;line-height:1"><?= number_format($val) ?></div>
        <div style="font-size:11px;color:var(--mut);margin-top:3px"><?= $lbl ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══ CHARTS ROW 1 ══ -->
<div class="row g-3 mb-4">
  <div class="col-xl-8">
    <div class="card-c h-100">
      <div class="ch"><div><p class="ct">Revenue Overview</p><p class="cs">Pendapatan 12 bulan terakhir (dalam ribuan Rp)</p></div></div>
      <div class="cb"><div id="chartRevenue" style="min-height:280px"></div></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-c h-100">
      <div class="ch"><div><p class="ct">Status Transaksi</p><p class="cs">Distribusi semua transaksi</p></div></div>
      <div class="cb">
        <div id="chartDonut" style="min-height:200px"></div>
        <div class="mt-2">
          <?php foreach ([['Sukses',$s_success,'var(--ok)'],['Pending',$s_pending,'var(--warn)'],['Gagal',$s_failed,'var(--err)']] as [$lbl,$val,$col]): ?>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
              <div style="width:8px;height:8px;border-radius:50%;background:<?=$col?>"></div>
              <span style="font-size:12px;color:var(--sub)"><?=$lbl?></span>
            </div>
            <span style="font-size:12px;font-weight:600"><?=number_format($val)?> <span style="color:var(--mut);font-weight:400">(<?=round($val/$s_total*100)?>%)</span></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══ CHARTS ROW 2 ══ -->
<div class="row g-3 mb-4">
  <div class="col-xl-6">
    <div class="card-c h-100">
      <div class="ch"><div><p class="ct">Transaksi 7 Hari Terakhir</p><p class="cs">Sukses vs Gagal per hari</p></div></div>
      <div class="cb"><div id="chartBar" style="min-height:260px"></div></div>
    </div>
  </div>
  <div class="col-xl-6">
    <div class="row g-3 h-100">
      <!-- Top Products -->
      <div class="col-12">
        <div class="card-c">
          <div class="ch">
            <div><p class="ct">Top Produk Terlaris</p><p class="cs">Berdasarkan transaksi sukses</p></div>
            <a href="transaksi.php" style="font-size:12px;color:var(--accent);text-decoration:none">Semua <i class="ph ph-arrow-right"></i></a>
          </div>
          <div class="cb">
            <?php if (empty($top_products)): ?>
              <div style="text-align:center;color:var(--mut);font-size:13px;padding:20px 0"><i class="ph ph-receipt" style="font-size:28px;display:block;margin-bottom:6px"></i>Belum ada transaksi sukses</div>
            <?php else:
              $cat_colors = ['Pulsa'=>'var(--accent)','Data'=>'var(--ok)','E-Money'=>'var(--warn)','Games'=>'var(--pur)'];
              foreach ($top_products as $p):
              $pct = round($p['sold'] / $max_sold * 100);
              $col = $cat_colors[$p['category']] ?? 'var(--a2)';
            ?>
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div>
                    <span style="font-size:13px;font-weight:500"><?= htmlspecialchars($p['product_name']) ?></span>
                    <span class="bd bd-acc ms-1" style="font-size:10px"><?= htmlspecialchars($p['category']) ?></span>
                  </div>
                  <span style="font-size:11px;color:var(--mut);font-family:'JetBrains Mono',monospace"><?= number_format($p['sold']) ?> trx</span>
                </div>
                <div style="height:5px;background:var(--hover);border-radius:99px">
                  <div style="height:100%;width:<?=$pct?>%;background:<?=$col?>;border-radius:99px"></div>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <!-- Aktivitas -->
      <div class="col-12">
        <div class="card-c">
          <div class="ch">
            <div><p class="ct">Aktivitas Terbaru</p></div>
            <a href="notifikasi.php" style="font-size:12px;color:var(--accent);text-decoration:none">Semua <i class="ph ph-arrow-right"></i></a>
          </div>
          <div class="cb" style="max-height:200px;overflow-y:auto">
            <?php if (empty($activities)): ?>
              <div style="text-align:center;color:var(--mut);font-size:13px;padding:16px 0">Tidak ada aktivitas</div>
            <?php else: foreach ($activities as $a):
              $is_fail = stripos($a['title'],'gagal')!==false || stripos($a['title'],'tolak')!==false;
              $is_topup = stripos($a['title'],'topup')!==false;
              $col = $is_fail ? 'var(--err)' : ($is_topup ? 'var(--ok)' : 'var(--accent)');
            ?>
              <div style="display:flex;gap:10px;margin-bottom:10px">
                <div style="width:7px;height:7px;border-radius:50%;background:<?=$col?>;flex-shrink:0;margin-top:4px"></div>
                <div>
                  <div style="font-size:12px;font-weight:500"><?= htmlspecialchars($a['title']) ?></div>
                  <div style="font-size:11px;color:var(--mut)">@<?= htmlspecialchars($a['username']??'—') ?> · <?= time_ago($a['created_at']) ?></div>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══ DATA TABLES ══ -->
<div class="row g-3 mb-4">

  <!-- Transaksi Terbaru -->
  <div class="col-xl-8">
    <div class="card-c">
      <div class="ch">
        <div><p class="ct">Transaksi Terbaru</p><p class="cs">10 transaksi terakhir</p></div>
        <a href="transaksi.php" class="btn btn-sm btn-primary" style="border-radius:7px;font-size:12px"><i class="ph ph-list me-1"></i>Lihat Semua</a>
      </div>
      <div class="cb" style="padding-top:12px">
        <div class="table-responsive">
          <table class="tbl">
            <thead><tr><th>Ref ID</th><th>User</th><th>Produk</th><th>Amount</th><th>Status</th><th>Waktu</th></tr></thead>
            <tbody>
            <?php if (empty($recent_trx)): ?>
              <tr><td colspan="6" class="text-center py-5" style="color:var(--mut)">
                <i class="ph ph-receipt" style="font-size:36px;display:block;margin-bottom:8px"></i>Belum ada transaksi
              </td></tr>
            <?php else: foreach ($recent_trx as $t):
              $sc = ['success'=>'bd-ok','pending'=>'bd-warn','failed'=>'bd-err'][$t['status']] ?? 'bd-acc';
              $sl = ['success'=>'Sukses','pending'=>'Pending','failed'=>'Gagal'][$t['status']] ?? $t['status'];
              $si = ['bd-ok'=>'ph-check-circle','bd-warn'=>'ph-clock','bd-err'=>'ph-x-circle'][$sc];
            ?>
              <tr>
                <td><span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--accent)"><?= htmlspecialchars(substr($t['ref_id']??'—',0,20)) ?></span></td>
                <td>
                  <div style="font-size:13px;font-weight:500"><?= htmlspecialchars($t['fullname'] ?: ($t['username']??'—')) ?></div>
                  <div style="font-size:11px;color:var(--mut)">@<?= htmlspecialchars($t['username']??'—') ?></div>
                </td>
                <td>
                  <div style="font-size:13px"><?= htmlspecialchars($t['product_name']) ?></div>
                  <div style="font-size:11px;color:var(--mut)"><?= htmlspecialchars($t['target']??'') ?></div>
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--ok)"><?= fmt_rp_full((float)$t['amount']) ?></td>
                <td><span class="bd <?=$sc?>"><i class="ph <?=$si?>"></i><?=$sl?></span></td>
                <td style="font-size:11px;color:var(--mut)"><?= time_ago($t['created_at']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Topup + Kategori -->
  <div class="col-xl-4">
    <div class="row g-3">

      <div class="col-12">
        <div class="card-c">
          <div class="ch">
            <div><p class="ct">Topup Terbaru</p></div>
            <a href="topup.php" style="font-size:12px;color:var(--accent);text-decoration:none">Lihat <i class="ph ph-arrow-right"></i></a>
          </div>
          <div class="cb" style="padding-top:12px">
            <?php if (empty($recent_topup)): ?>
              <div style="text-align:center;color:var(--mut);font-size:13px;padding:16px 0">Belum ada topup</div>
            <?php else: foreach ($recent_topup as $tp):
              $tc = ['success'=>'bd-ok','pending'=>'bd-warn','failed'=>'bd-err'][$tp['status']] ?? 'bd-acc';
              $tl = ['success'=>'Sukses','pending'=>'Pending','failed'=>'Gagal'][$tp['status']] ?? $tp['status'];
            ?>
              <div class="d-flex align-items-center gap-2 mb-3">
                <img src="https://ui-avatars.com/api/?name=<?=urlencode($tp['fullname']??$tp['username']??'?')?>&background=1a2540&color=3b82f6&size=32"
                     style="width:30px;height:30px;border-radius:7px;flex-shrink:0"/>
                <div style="flex:1;min-width:0">
                  <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($tp['fullname']??$tp['username']??'—') ?></div>
                  <div style="font-size:11px;color:var(--mut)"><?= $tp['payment_method'] ?> · <?= time_ago($tp['created_at']) ?></div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                  <div style="font-size:12px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--ok)"><?= fmt_rp((float)$tp['amount']) ?></div>
                  <span class="bd <?=$tc?>" style="font-size:10px"><?=$tl?></span>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card-c">
          <div class="ch"><div><p class="ct">Kategori Terlaris</p><p class="cs">Berdasarkan volume transaksi</p></div></div>
          <div class="cb" style="padding-top:12px">
            <?php
            $cat_col_map = ['Pulsa'=>'var(--accent)','Data'=>'var(--ok)','E-Money'=>'var(--warn)','Games'=>'var(--pur)'];
            if (empty($top_cats)): ?>
              <div style="text-align:center;color:var(--mut);font-size:13px;padding:16px 0">Belum ada data</div>
            <?php else: foreach ($top_cats as $cat):
              $pct = round($cat['c'] / $cat_total * 100);
              $col = $cat_col_map[$cat['cat']] ?? 'var(--a2)';
            ?>
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span style="font-size:12px;color:var(--sub)"><?= htmlspecialchars($cat['cat']) ?></span>
                  <span style="font-size:12px;font-weight:600;font-family:'JetBrains Mono',monospace"><?=number_format($cat['c'])?> trx · <?=$pct?>%</span>
                </div>
                <div style="height:4px;background:var(--hover);border-radius:99px">
                  <div style="height:100%;width:<?=$pct?>%;background:<?=$col?>;border-radius:99px"></div>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

<?php
$extra_scripts = <<<SCRIPT
<script>
// Revenue Area
new ApexCharts(document.querySelector('#chartRevenue'), {
  chart:{ type:'area', height:280, toolbar:{show:false}, background:'transparent', fontFamily:"'Plus Jakarta Sans',sans-serif" },
  theme:{ mode:'dark' },
  series:[{ name:'Revenue (rb)', data:{$chart_revenue} }],
  xaxis:{ categories:{$chart_labels}, axisBorder:{show:false}, axisTicks:{show:false}, labels:{style:{colors:'#4b5e7a',fontSize:'11px'}} },
  yaxis:{ labels:{ formatter: v => 'Rp '+v+'rb', style:{colors:'#4b5e7a',fontSize:'11px'} } },
  colors:['#3b82f6'],
  stroke:{ curve:'smooth', width:2.5 },
  fill:{ type:'gradient', gradient:{ shadeIntensity:1, opacityFrom:.35, opacityTo:.02, stops:[0,90,100] } },
  grid:{ borderColor:'rgba(255,255,255,.04)', strokeDashArray:4 },
  markers:{ size:0 }, dataLabels:{ enabled:false },
  tooltip:{ theme:'dark', y:{ formatter: v => 'Rp '+(v*1000).toLocaleString('id-ID') } },
}).render();

// Donut
new ApexCharts(document.querySelector('#chartDonut'), {
  chart:{ type:'donut', height:200, background:'transparent', fontFamily:"'Plus Jakarta Sans',sans-serif" },
  theme:{ mode:'dark' },
  series:[{$s_success},{$s_pending},{$s_failed}],
  labels:['Sukses','Pending','Gagal'],
  colors:['#10b981','#f59e0b','#ef4444'],
  plotOptions:{ pie:{ donut:{ size:'70%', labels:{ show:true,
    total:{ show:true, label:'Total', color:'#7a90b0', fontSize:'12px', formatter:()=>'{$s_total}' },
    value:{ color:'#e2e8f0', fontSize:'22px', fontWeight:700 }
  }}}},
  legend:{ show:false }, dataLabels:{ enabled:false },
  tooltip:{ theme:'dark' },
}).render();

// Bar
new ApexCharts(document.querySelector('#chartBar'), {
  chart:{ type:'bar', height:260, toolbar:{show:false}, background:'transparent', fontFamily:"'Plus Jakarta Sans',sans-serif" },
  theme:{ mode:'dark' },
  series:[
    { name:'Sukses', data:{$bar_sukses} },
    { name:'Gagal',  data:{$bar_gagal} },
  ],
  colors:['#10b981','#ef4444'],
  xaxis:{ categories:{$bar_labels}, axisBorder:{show:false}, axisTicks:{show:false}, labels:{style:{colors:'#4b5e7a',fontSize:'12px'}} },
  yaxis:{ labels:{style:{colors:'#4b5e7a',fontSize:'11px'}} },
  plotOptions:{ bar:{ borderRadius:6, columnWidth:'55%', borderRadiusApplication:'end' } },
  grid:{ borderColor:'rgba(255,255,255,.04)', strokeDashArray:4 },
  legend:{ position:'top', horizontalAlign:'right', labels:{colors:'#7a90b0'}, markers:{size:6} },
  dataLabels:{ enabled:false },
  tooltip:{ theme:'dark' },
}).render();
</script>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>