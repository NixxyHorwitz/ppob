<?php
// backoffice/topup_history.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Topup History';
$active_menu = 'topups';

// ══ ACTIONS ══════════════════════════════════════════════════
$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

// ── Update status manual ─────────────────────────────────────
if ($action === 'set_status' && !empty($_POST['id'])) {
  $id     = (int)$_POST['id'];
  $status = in_array($_POST['status'] ?? '', ['pending', 'success', 'failed'])
    ? $_POST['status'] : null;

  if (!$status) {
    $toast_e = 'Status tidak valid.';
  } else {
    $pdo->prepare("UPDATE topup_history SET status = ? WHERE id = ?")
      ->execute([$status, $id]);
    $toast = 'Status topup berhasil diubah menjadi ' . strtoupper($status) . '.';
  }
}

// ── Hapus satu ────────────────────────────────────────────────
if ($action === 'delete' && !empty($_POST['id'])) {
  $pdo->prepare("DELETE FROM topup_history WHERE id = ?")
    ->execute([(int)$_POST['id']]);
  $toast = 'Record topup berhasil dihapus.';
}

// ── Hapus bulk ────────────────────────────────────────────────
if ($action === 'delete_bulk' && !empty($_POST['ids'])) {
  $ids = array_filter(array_map('intval', explode(',', $_POST['ids'])));
  if ($ids) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM topup_history WHERE id IN ($ph)")->execute($ids);
    $toast = count($ids) . ' record berhasil dihapus.';
  }
}

// ══ FETCH STATS ══════════════════════════════════════════════
$stats = $pdo->query("
    SELECT
        COUNT(*)                                AS total,
        SUM(status = 'pending')                 AS pending_count,
        SUM(status = 'success')                 AS success_count,
        SUM(status = 'failed')                  AS failed_count,
        COALESCE(SUM(CASE WHEN status='success' THEN amount_original END), 0) AS total_success_amount,
        SUM(payment_method = 'QRIS')            AS qris_count,
        SUM(payment_method = 'MANUAL')          AS manual_count
    FROM topup_history
")->fetch();

// ══ FILTER ═══════════════════════════════════════════════════
$q        = trim($_GET['q']       ?? '');
$f_status = trim($_GET['status']  ?? '');
$f_method = trim($_GET['method']  ?? '');
$f_uid    = trim($_GET['uid']     ?? '');
$f_date_s = trim($_GET['date_s']  ?? '');
$f_date_e = trim($_GET['date_e']  ?? '');
$page     = max(1, (int)($_GET['p'] ?? 1));
$per_page = 20;

$where = [];
$params = [];

if ($q) {
  $where[] = "(t.external_id LIKE ? OR t.note LIKE ? OR u.username LIKE ? OR u.fullname LIKE ?)";
  $s = "%$q%";
  array_push($params, $s, $s, $s, $s);
}
if ($f_status) {
  $where[] = "t.status = ?";
  $params[] = $f_status;
}
if ($f_method) {
  $where[] = "t.payment_method = ?";
  $params[] = $f_method;
}
if ($f_uid) {
  $where[] = "t.user_id = ?";
  $params[] = (int)$f_uid;
}
if ($f_date_s) {
  $where[] = "DATE(t.created_at) >= ?";
  $params[] = $f_date_s;
}
if ($f_date_e) {
  $where[] = "DATE(t.created_at) <= ?";
  $params[] = $f_date_e;
}

$wsql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$count_st = $pdo->prepare("SELECT COUNT(*) FROM topup_history t LEFT JOIN users u ON t.user_id = u.id $wsql");
$count_st->execute($params);
$total_rows  = (int)$count_st->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$offset      = ($page - 1) * $per_page;

// Data
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.fullname, u.email, u.role AS user_role
    FROM topup_history t
    LEFT JOIN users u ON t.user_id = u.id
    $wsql
    ORDER BY t.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$page_ids = array_column($rows, 'id');

// Users untuk filter dropdown
$all_users = $pdo->query("SELECT id, username, fullname FROM users WHERE is_active=1 ORDER BY username")->fetchAll();

$qs = http_build_query(array_filter([
  'q' => $q,
  'status' => $f_status,
  'method' => $f_method,
  'uid' => $f_uid,
  'date_s' => $f_date_s,
  'date_e' => $f_date_e,
]));

// ── Helpers ──────────────────────────────────────────────────
function status_badge(string $s): string
{
  return match ($s) {
    'success' => '<span class="bd bd-ok"><i class="ph ph-check-circle"></i> Success</span>',
    'failed'  => '<span class="bd bd-err"><i class="ph ph-x-circle"></i> Failed</span>',
    default   => '<span class="bd bd-war"><i class="ph ph-clock"></i> Pending</span>',
  };
}
function method_badge(string $m): string
{
  return $m === 'QRIS'
    ? '<span class="bd bd-pur"><i class="ph ph-qr-code"></i> QRIS</span>'
    : '<span class="bd bd-acc"><i class="ph ph-bank"></i> Manual</span>';
}
function rp(int $n): string
{
  return 'Rp ' . number_format($n, 0, ',', '.');
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ══ TOAST ══ -->
<div class="toast-wrap">
  <?php if ($toast):   ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>
  <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<!-- ══ PAGE HEADER ══ -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h1>Topup History</h1>
    <nav>
      <ol class="breadcrumb bc">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Topup History</li>
      </ol>
    </nav>
  </div>
  <?php if (!empty($page_ids)): ?>
    <form method="POST" onsubmit="return confirm('Hapus semua record di halaman ini?')">
      <input type="hidden" name="action" value="delete_bulk" />
      <input type="hidden" name="ids" value="<?= implode(',', $page_ids) ?>" />
      <button type="submit" class="btn btn-sm"
        style="border-radius:8px;background:var(--es);border:1px solid rgba(239,68,68,.2);color:var(--err)">
        <i class="ph ph-trash me-1"></i>Hapus Halaman Ini
      </button>
    </form>
  <?php endif; ?>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="sc blue">
      <div class="si blue"><i class="ph-fill ph-arrows-clockwise"></i></div>
      <div class="sv"><?= number_format($stats['total']) ?></div>
      <div class="sl">Total Transaksi</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="sc green">
      <div class="si green"><i class="ph-fill ph-check-circle"></i></div>
      <div class="sv"><?= number_format($stats['success_count']) ?></div>
      <div class="sl">Success</div>
      <div style="font-size:11px;color:var(--mut);margin-top:4px;font-family:'JetBrains Mono',monospace">
        <?= rp((int)$stats['total_success_amount']) ?>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="sc orange">
      <div class="si orange"><i class="ph-fill ph-clock"></i></div>
      <div class="sv"><?= number_format($stats['pending_count']) ?></div>
      <div class="sl">Pending</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="sc purple">
      <div class="si purple"><i class="ph-fill ph-x-circle"></i></div>
      <div class="sv"><?= number_format($stats['failed_count']) ?></div>
      <div class="sl">Failed</div>
    </div>
  </div>
</div>

<!-- ══ METHOD BREAKDOWN ══ -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card-c" style="padding:16px 20px">
      <div class="d-flex align-items-center gap-3">
        <div class="si purple" style="margin:0;flex-shrink:0"><i class="ph-fill ph-qr-code"></i></div>
        <div>
          <div style="font-size:11px;color:var(--mut);font-weight:600;text-transform:uppercase;letter-spacing:.5px">QRIS</div>
          <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace"><?= number_format($stats['qris_count']) ?></div>
        </div>
        <div style="margin-left:auto">
          <?php
          $pct_qris = $stats['total'] ? round($stats['qris_count'] / $stats['total'] * 100) : 0;
          ?>
          <div style="font-size:13px;color:var(--mut)"><?= $pct_qris ?>%</div>
          <div class="progress-custom" style="width:80px;margin-top:4px">
            <div class="progress-bar-custom" style="width:<?= $pct_qris ?>%;background:var(--purple)"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card-c" style="padding:16px 20px">
      <div class="d-flex align-items-center gap-3">
        <div class="si blue" style="margin:0;flex-shrink:0"><i class="ph-fill ph-bank"></i></div>
        <div>
          <div style="font-size:11px;color:var(--mut);font-weight:600;text-transform:uppercase;letter-spacing:.5px">Transfer Manual</div>
          <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace"><?= number_format($stats['manual_count']) ?></div>
        </div>
        <div style="margin-left:auto">
          <?php $pct_manual = $stats['total'] ? round($stats['manual_count'] / $stats['total'] * 100) : 0; ?>
          <div style="font-size:13px;color:var(--mut)"><?= $pct_manual ?>%</div>
          <div class="progress-custom" style="width:80px;margin-top:4px">
            <div class="progress-bar-custom" style="width:<?= $pct_manual ?>%;background:var(--accent)"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══ TABLE CARD ══ -->
<div class="card-c">
  <div class="ch">
    <div>
      <p class="ct">Riwayat Topup</p>
      <p class="cs">
        <?= number_format($total_rows) ?> transaksi
        <?= $q ? "· <strong style='color:var(--accent)'>" . htmlspecialchars($q) . "</strong>" : '' ?>
      </p>
    </div>
    <!-- Export CSV -->
    <a href="topup_history.php?export=csv&<?= $qs ?>" class="btn btn-sm"
      style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub);font-size:12px;text-decoration:none">
      <i class="ph ph-file-csv me-1"></i>Export CSV
    </a>
  </div>

  <!-- Filter bar -->
  <div class="cb pb-0">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
      <!-- Search -->
      <div style="position:relative;flex:1;min-width:200px">
        <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mut);font-size:16px;pointer-events:none"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="fi"
          placeholder="External ID, catatan, username…" style="width:100%;padding-left:34px" />
      </div>

      <!-- User -->
      <select name="uid" class="fs">
        <option value="">Semua User</option>
        <?php foreach ($all_users as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $f_uid == $u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['username'] . ($u['fullname'] ? ' — ' . $u['fullname'] : '')) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Status -->
      <select name="status" class="fs">
        <option value="">Semua Status</option>
        <option value="pending" <?= $f_status === 'pending'  ? 'selected' : '' ?>>Pending</option>
        <option value="success" <?= $f_status === 'success'  ? 'selected' : '' ?>>Success</option>
        <option value="failed" <?= $f_status === 'failed'   ? 'selected' : '' ?>>Failed</option>
      </select>

      <!-- Method -->
      <select name="method" class="fs">
        <option value="">Semua Metode</option>
        <option value="QRIS" <?= $f_method === 'QRIS'   ? 'selected' : '' ?>>QRIS</option>
        <option value="MANUAL" <?= $f_method === 'MANUAL' ? 'selected' : '' ?>>Manual</option>
      </select>

      <!-- Date range -->
      <input type="date" name="date_s" value="<?= htmlspecialchars($f_date_s) ?>" class="fi"
        style="width:auto" title="Dari tanggal" />
      <input type="date" name="date_e" value="<?= htmlspecialchars($f_date_e) ?>" class="fi"
        style="width:auto" title="Sampai tanggal" />

      <button type="submit" class="btn btn-primary btn-sm" style="border-radius:7px;padding:8px 16px">
        <i class="ph ph-funnel me-1"></i>Filter
      </button>
      <?php if ($q || $f_status || $f_method || $f_uid || $f_date_s || $f_date_e): ?>
        <a href="topup_history.php" class="btn btn-sm"
          style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub);padding:8px 14px">
          <i class="ph ph-x me-1"></i>Reset
        </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Table -->
  <div class="cb">
    <?php if (empty($rows)): ?>
      <div class="text-center py-5" style="color:var(--mut)">
        <i class="ph ph-receipt" style="font-size:48px;display:block;margin-bottom:10px;opacity:.4"></i>
        <div style="font-size:14px;font-weight:600;margin-bottom:4px">Tidak ada riwayat topup</div>
        <div style="font-size:12px">Coba ubah filter pencarian</div>
      </div>
    <?php else: ?>

      <!-- Desktop Table -->
      <div class="table-responsive d-none d-xl-block">
        <table class="tbl">
          <thead>
            <tr>
              <th>ID</th>
              <th>External ID</th>
              <th>User</th>
              <th>Metode</th>
              <th style="text-align:right">Nominal</th>
              <th style="text-align:right">Total Dibayar</th>
              <th>Status</th>
              <th>Waktu</th>
              <th style="text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <!-- ID -->
                <td><span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--mut)">#<?= $r['id'] ?></span></td>

                <!-- External ID -->
                <td>
                  <div style="display:flex;align-items:center;gap:5px">
                    <span style="font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--sub);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:inline-block"
                      title="<?= htmlspecialchars($r['external_id']) ?>">
                      <?= htmlspecialchars($r['external_id']) ?>
                    </span>
                    <button type="button" class="ab" style="width:22px;height:22px;font-size:12px;flex-shrink:0"
                      title="Copy" onclick="copyText('<?= htmlspecialchars($r['external_id']) ?>')">
                      <i class="ph ph-copy"></i>
                    </button>
                  </div>
                  <?php if ($r['note']): ?>
                    <div style="font-size:11px;color:var(--mut);margin-top:2px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                      title="<?= htmlspecialchars($r['note']) ?>">
                      <?= htmlspecialchars($r['note']) ?>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- User — klik untuk detail -->
                <td>
                  <?php if ($r['user_id']): ?>
                    <button type="button" onclick="openUserDetail(<?= (int)$r['user_id'] ?>)"
                      style="background:none;border:none;padding:0;cursor:pointer;text-align:left;display:block">
                      <div style="font-weight:600;font-size:13px;color:var(--text);display:flex;align-items:center;gap:5px">
                        <?= htmlspecialchars($r['username'] ?? 'User #' . $r['user_id']) ?>
                        <i class="ph ph-arrow-square-out" style="font-size:11px;color:var(--accent);opacity:.7"></i>
                      </div>
                      <div style="font-size:11px;color:var(--mut)"><?= htmlspecialchars($r['fullname'] ?: $r['email'] ?? '') ?></div>
                    </button>
                  <?php else: ?>
                    <span style="color:var(--mut);font-size:12px">—</span>
                  <?php endif; ?>
                </td>

                <!-- Metode -->
                <td><?= method_badge($r['payment_method'] ?? 'QRIS') ?></td>

                <!-- Nominal asli -->
                <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-size:13px">
                  <?= rp($r['amount_original']) ?>
                </td>

                <!-- Total dibayar (amount = nominal + fee) -->
                <td style="text-align:right">
                  <div style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;color:var(--text)">
                    <?= rp($r['amount']) ?>
                  </div>
                  <?php $fee = $r['amount'] - $r['amount_original']; ?>
                  <?php if ($fee > 0): ?>
                    <div style="font-size:10px;color:var(--mut)">+<?= rp($fee) ?> fee</div>
                  <?php endif; ?>
                </td>

                <!-- Status -->
                <td>
                  <?= status_badge($r['status']) ?>
                </td>

                <!-- Waktu -->
                <td style="font-size:11.5px;color:var(--mut);white-space:nowrap">
                  <div><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                  <div style="font-family:'JetBrains Mono',monospace"><?= date('H:i:s', strtotime($r['created_at'])) ?></div>
                </td>

                <!-- Aksi -->
                <td>
                  <div class="d-flex gap-1 justify-content-center">
                    <!-- Detail -->
                    <button type="button" class="ab" title="Lihat Detail"
                      onclick="showDetail(<?= htmlspecialchars(json_encode([
                                            'id'          => $r['id'],
                                            'external_id' => $r['external_id'],
                                            'user'        => $r['username'] ?? 'User #' . $r['user_id'],
                                            'fullname'    => $r['fullname'] ?? '',
                                            'email'       => $r['email'] ?? '',
                                            'method'      => $r['payment_method'],
                                            'amount_ori'  => rp($r['amount_original']),
                                            'amount'      => rp($r['amount']),
                                            'fee'         => rp($r['amount'] - $r['amount_original']),
                                            'note'        => $r['note'],
                                            'status'      => $r['status'],
                                            'qr_string'   => $r['qr_string'] ?? '',
                                            'time'        => date('d M Y, H:i:s', strtotime($r['created_at'])),
                                          ]), ENT_QUOTES) ?>)">
                      <i class="ph ph-eye"></i>
                    </button>

                    <!-- Ubah status (dropdown) -->
                    <div class="dropdown">
                      <button class="ab" title="Ubah Status" data-bs-toggle="dropdown">
                        <i class="ph ph-pencil-simple"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end dm">
                        <?php foreach (['pending', 'success', 'failed'] as $s): ?>
                          <?php if ($s !== $r['status']): ?>
                            <li>
                              <form method="POST">
                                <input type="hidden" name="action" value="set_status" />
                                <input type="hidden" name="id" value="<?= $r['id'] ?>" />
                                <input type="hidden" name="status" value="<?= $s ?>" />
                                <?php if ($qs): ?><input type="hidden" name="_qs" value="<?= htmlspecialchars($qs) ?>"><?php endif; ?>
                                <button type="submit" class="dropdown-item di">
                                  <?= match ($s) {
                                    'success' => '<i class="ph ph-check-circle" style="color:var(--ok)"></i> Set Success',
                                    'failed'  => '<i class="ph ph-x-circle"    style="color:var(--err)"></i> Set Failed',
                                    default   => '<i class="ph ph-clock"        style="color:var(--war)"></i> Set Pending',
                                  } ?>
                                </button>
                              </form>
                            </li>
                          <?php endif; ?>
                        <?php endforeach; ?>
                        <li>
                          <hr class="dropdown-divider" style="border-color:var(--border)" />
                        </li>
                        <li>
                          <form method="POST" onsubmit="return confirm('Hapus record ini?')">
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="id" value="<?= $r['id'] ?>" />
                            <button type="submit" class="dropdown-item di" style="color:var(--err)">
                              <i class="ph ph-trash"></i> Hapus
                            </button>
                          </form>
                        </li>
                      </ul>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile/Tablet Cards -->
      <div class="d-xl-none">
        <?php foreach ($rows as $r): ?>
          <?php $fee = $r['amount'] - $r['amount_original']; ?>
          <div class="topup-card" data-status="<?= $r['status'] ?>">

            <!-- Header -->
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
              <div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--mut);margin-bottom:2px">
                  #<?= $r['id'] ?> · <?= htmlspecialchars($r['external_id']) ?>
                </div>
                <div style="font-weight:700;font-size:15px;font-family:'JetBrains Mono',monospace">
                  <?= rp($r['amount_original']) ?>
                </div>
                <?php if ($fee > 0): ?>
                  <div style="font-size:11px;color:var(--mut)">Total: <?= rp($r['amount']) ?> (+<?= rp($fee) ?> fee)</div>
                <?php endif; ?>
              </div>
              <div class="d-flex flex-column align-items-end gap-1">
                <?= status_badge($r['status']) ?>
                <?= method_badge($r['payment_method'] ?? 'QRIS') ?>
              </div>
            </div>

            <!-- User & time -->
            <div class="d-flex align-items-center gap-2 mb-3" style="font-size:12px;color:var(--mut)">
              <i class="ph ph-user"></i>
              <?php if ($r['user_id']): ?>
                <button type="button" onclick="openUserDetail(<?= (int)$r['user_id'] ?>)"
                  style="background:none;border:none;padding:0;cursor:pointer;font-size:12px;color:var(--accent);font-weight:600;display:flex;align-items:center;gap:4px">
                  <?= htmlspecialchars($r['username'] ?? 'User #' . $r['user_id']) ?>
                  <i class="ph ph-arrow-square-out" style="font-size:10px"></i>
                </button>
              <?php else: ?>
                <span><?= htmlspecialchars($r['username'] ?? '—') ?></span>
              <?php endif; ?>
              <span style="margin-left:auto"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></span>
            </div>

            <?php if ($r['note']): ?>
              <div style="font-size:12px;color:var(--sub);background:var(--hover);padding:8px 10px;border-radius:var(--rs);margin-bottom:12px">
                <?= htmlspecialchars($r['note']) ?>
              </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm flex-fill"
                style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub);font-size:12px"
                onclick="showDetail(<?= htmlspecialchars(json_encode([
                                      'id'          => $r['id'],
                                      'external_id' => $r['external_id'],
                                      'user'        => $r['username'] ?? 'User #' . $r['user_id'],
                                      'fullname'    => $r['fullname'] ?? '',
                                      'email'       => $r['email'] ?? '',
                                      'method'      => $r['payment_method'],
                                      'amount_ori'  => rp($r['amount_original']),
                                      'amount'      => rp($r['amount']),
                                      'fee'         => rp($fee),
                                      'note'        => $r['note'],
                                      'status'      => $r['status'],
                                      'qr_string'   => $r['qr_string'] ?? '',
                                      'time'        => date('d M Y, H:i:s', strtotime($r['created_at'])),
                                    ]), ENT_QUOTES) ?>)">
                <i class="ph ph-eye me-1"></i>Detail
              </button>

              <div class="dropdown flex-fill">
                <button class="btn btn-sm w-100"
                  style="border-radius:7px;background:var(--as);border:1px solid var(--border-active);color:var(--accent);font-size:12px"
                  data-bs-toggle="dropdown">
                  <i class="ph ph-pencil-simple me-1"></i>Status <i class="ph ph-caret-down ms-1"></i>
                </button>
                <ul class="dropdown-menu dm">
                  <?php foreach (['pending', 'success', 'failed'] as $s): ?>
                    <li>
                      <form method="POST">
                        <input type="hidden" name="action" value="set_status" />
                        <input type="hidden" name="id" value="<?= $r['id'] ?>" />
                        <input type="hidden" name="status" value="<?= $s ?>" />
                        <button type="submit" class="dropdown-item di <?= $s === $r['status'] ? 'active' : '' ?>">
                          <?= match ($s) {
                            'success' => '<i class="ph ph-check-circle" style="color:var(--ok)"></i> Success',
                            'failed'  => '<i class="ph ph-x-circle"    style="color:var(--err)"></i> Failed',
                            default   => '<i class="ph ph-clock"        style="color:var(--war)"></i> Pending',
                          } ?>
                          <?= $s === $r['status'] ? '✓' : '' ?>
                        </button>
                      </form>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <form method="POST" onsubmit="return confirm('Hapus record ini?')">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?= $r['id'] ?>" />
                <button type="submit" class="btn btn-sm"
                  style="border-radius:7px;background:var(--es);border:1px solid rgba(239,68,68,.2);color:var(--err);font-size:12px;padding:8px 10px">
                  <i class="ph ph-trash"></i>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="d-flex align-items-center justify-content-between mt-4 flex-wrap gap-2">
          <div style="font-size:12px;color:var(--mut)">
            Halaman <?= $page ?> dari <?= $total_pages ?> · <?= number_format($total_rows) ?> total
          </div>
          <div class="d-flex gap-1 flex-wrap">
            <?php
            $base  = 'topup_history.php?' . ($qs ? $qs . '&' : '');
            $start = max(1, $page - 2);
            $end   = min($total_pages, $page + 2);
            ?>
            <?php if ($page > 1): ?>
              <a href="<?= $base ?>p=<?= $page - 1 ?>" class="pag-btn"><i class="ph ph-caret-left"></i></a>
            <?php endif; ?>
            <?php if ($start > 1): ?>
              <a href="<?= $base ?>p=1" class="pag-btn">1</a>
              <?php if ($start > 2): ?><span class="pag-btn" style="pointer-events:none">…</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $start; $i <= $end; $i++): ?>
              <a href="<?= $base ?>p=<?= $i ?>"
                class="pag-btn <?= $i === $page ? 'pag-active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($end < $total_pages): ?>
              <?php if ($end < $total_pages - 1): ?><span class="pag-btn" style="pointer-events:none">…</span><?php endif; ?>
              <a href="<?= $base ?>p=<?= $total_pages ?>" class="pag-btn"><?= $total_pages ?></a>
            <?php endif; ?>
            <?php if ($page < $total_pages): ?>
              <a href="<?= $base ?>p=<?= $page + 1 ?>" class="pag-btn"><i class="ph ph-caret-right"></i></a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</div>

<!-- ══ MODAL: DETAIL ══ -->
<div class="modal fade" id="mDetail" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content mc">
      <div class="modal-header mh">
        <h5 class="modal-title"><i class="ph ph-receipt me-2" style="color:var(--accent)"></i>Detail Topup</h5>
        <button type="button" class="btn-close" style="filter:invert(1)" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailBody"></div>
      <div class="modal-footer mf">
        <button type="button" class="btn btn-sm"
          style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)"
          data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<style>
  .topup-card {
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 14px;
    margin-bottom: 10px;
    transition: border-color .2s;
  }

  .topup-card[data-status="success"] {
    border-color: rgba(16, 185, 129, .2);
  }

  .topup-card[data-status="failed"] {
    border-color: rgba(239, 68, 68, .15);
  }

  .topup-card[data-status="pending"] {
    border-color: rgba(245, 158, 11, .2);
  }

  .pag-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    background: var(--hover);
    border: 1px solid var(--border);
    color: var(--sub);
    text-decoration: none;
    transition: all .15s;
  }

  .pag-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
  }

  .pag-active {
    background: var(--accent) !important;
    border-color: var(--accent) !important;
    color: #fff !important;
  }

  .bd-war {
    background: rgba(245, 158, 11, .12);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, .25);
  }
</style>

<?php
// ══ EXPORT CSV ═══════════════════════════════════════════════
// Must be checked before any output — put after header.php because header.php
// doesn't output body in export mode if we redirect before require.
// Alternatively handle export at the top of the file (before require header).
// Here we output inline after page via JS redirect trick.

$page_scripts = <<<'SCRIPT'
<script>
// ── Detail modal ─────────────────────────────────────────────
function showDetail(d) {
  const statusMap = {
    success: '<span class="bd bd-ok"><i class="ph ph-check-circle"></i> Success</span>',
    failed:  '<span class="bd bd-err"><i class="ph ph-x-circle"></i> Failed</span>',
    pending: '<span class="bd bd-war"><i class="ph ph-clock"></i> Pending</span>',
  };
  const methodMap = {
    QRIS:   '<span class="bd bd-pur"><i class="ph ph-qr-code"></i> QRIS</span>',
    MANUAL: '<span class="bd bd-acc"><i class="ph ph-bank"></i> Manual</span>',
  };

  let qrHtml = '';
  if (d.qr_string && d.method === 'QRIS') {
    qrHtml = `
      <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:6px">QR Image</div>
        <img src="${d.qr_string}" alt="QR" style="max-width:180px;border-radius:8px;border:1px solid var(--border)"
             onerror="this.parentElement.innerHTML='<span style=\\'font-size:11px;color:var(--mut)\\'>QR tidak tersedia</span>'"/>
      </div>`;
  }

  document.getElementById('detailBody').innerHTML = `
    <div style="display:flex;flex-direction:column;gap:14px">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <span style="font-size:11px;color:var(--mut);font-family:'JetBrains Mono',monospace">#${d.id}</span>
        <div class="d-flex gap-2">
          ${statusMap[d.status] ?? ''}
          ${methodMap[d.method] ?? ''}
        </div>
      </div>

      <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:4px">External ID</div>
        <div style="font-family:'JetBrains Mono',monospace;font-size:12.5px;background:var(--hover);padding:8px 10px;border-radius:var(--rs);display:flex;align-items:center;justify-content:space-between;gap:8px">
          <span style="word-break:break-all">${d.external_id}</span>
          <button type="button" class="ab" style="width:24px;height:24px;font-size:13px;flex-shrink:0" onclick="copyText('${d.external_id}')">
            <i class="ph ph-copy"></i>
          </button>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:4px">User</div>
          <div style="font-weight:600;font-size:13px">${d.user}</div>
          <div style="font-size:11px;color:var(--mut)">${d.fullname || d.email || ''}</div>
        </div>
        <div>
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:4px">Waktu</div>
          <div style="font-size:12.5px;font-family:'JetBrains Mono',monospace;color:var(--sub)">${d.time}</div>
        </div>
      </div>

      <div style="background:var(--hover);border:1px solid var(--border);border-radius:var(--rs);padding:14px">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;text-align:center">
          <div>
            <div style="font-size:10px;color:var(--mut);font-weight:600;text-transform:uppercase">Nominal</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;margin-top:4px">${d.amount_ori}</div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--mut);font-weight:600;text-transform:uppercase">Fee</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;margin-top:4px;color:var(--war)">${d.fee}</div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--mut);font-weight:600;text-transform:uppercase">Total</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;margin-top:4px;color:var(--ok)">${d.amount}</div>
          </div>
        </div>
      </div>

      ${d.note ? `
      <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:4px">Catatan</div>
        <div style="font-size:13px;color:var(--sub);background:var(--hover);padding:10px 12px;border-radius:var(--rs)">${d.note}</div>
      </div>` : ''}

      ${qrHtml}
    </div>
  `;
  new bootstrap.Modal(document.getElementById('mDetail')).show();
}

// ── Copy helper ──────────────────────────────────────────────
function copyText(txt) {
  navigator.clipboard.writeText(txt).then(() => {
    const wrap = document.querySelector('.toast-wrap');
    if (!wrap) return;
    const t = document.createElement('div');
    t.className = 'toast-item toast-ok';
    t.innerHTML = '<i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i>Disalin!';
    wrap.appendChild(t);
    setTimeout(() => t.style.opacity = '0', 2500);
    setTimeout(() => t.remove(), 3000);
  });
}

// ── Auto dismiss toast ───────────────────────────────────────
document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => t.style.opacity = '0', 3500);
  setTimeout(() => t.remove(), 4000);
});

// ── User detail embed ─────────────────────────────────────────
let _udLoading = false;

function openUserDetail(userId) {
  if (_udLoading) return;
  _udLoading = true;

  // Pastikan container embed sudah ada di DOM
  let container = document.getElementById('udContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'udContainer';
    document.body.appendChild(container);
  }

  // Tampilkan loading overlay
  const wrap = document.createElement('div');
  wrap.id = 'udLoadWrap';
  wrap.style.cssText = 'position:fixed;inset:0;z-index:1060;display:flex;align-items:center;justify-content:center;background:rgba(9,13,24,.75)';
  wrap.innerHTML = `<div style="background:#131d30;border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:32px 40px;text-align:center">
    <div style="width:32px;height:32px;border:3px solid rgba(59,130,246,.2);border-top-color:#3b82f6;border-radius:50%;animation:udSpin .7s linear infinite;margin:0 auto 12px"></div>
    <div style="font-size:13px;color:#7a90b0">Memuat data user…</div>
  </div>`;
  document.body.appendChild(wrap);

  fetch(`user_detail.php?user_id=${userId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.text())
    .then(html => {
      wrap.remove();
      _udLoading = false;

      const trimmed = html.trim();

      // Inject langsung via innerHTML — paling aman, tidak perlu parsing DOM
      container.innerHTML = trimmed;

      // Cari modal element setelah inject
      const modalEl = container.querySelector('.modal');
      if (!modalEl) {
        // Tampilkan isi response mentah di console untuk debug
        console.error('[user_detail] Modal not found. Raw response (first 800 chars):', trimmed.substring(0, 800));
        container.innerHTML = '';
        alert('Gagal: modal tidak ditemukan dalam respons server.\nCek console (F12) untuk detail.');
        return;
      }

      // Pastikan id benar agar tidak konflik
      modalEl.id = 'modalUserDetail';

      const modal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
      modal.show();

      modalEl.addEventListener('hidden.bs.modal', () => {
        modal.dispose();
        container.innerHTML = '';
      }, { once: true });
    })
    .catch(err => {
      wrap.remove();
      _udLoading = false;
      console.error('[user_detail] fetch error:', err);
      alert('Gagal menghubungi server: ' + err.message);
    });
}
</script>
<style>
@keyframes udSpin { to { transform: rotate(360deg); } }
</style>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>
<?php
// ══ CSV EXPORT (letakkan di bagian PALING ATAS file, sebelum require header) ══
// NOTE: Pindahkan blok ini ke bagian atas file (sebelum session/output apapun)
// untuk production. Di sini sebagai referensi implementasinya.
/*
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // rebuild query dengan filter yang sama tapi tanpa LIMIT
    $export_stmt = $pdo->prepare("
        SELECT t.id, t.external_id, u.username, u.fullname,
               t.payment_method, t.amount_original, t.amount,
               (t.amount - t.amount_original) AS fee,
               t.status, t.note, t.created_at
        FROM topup_history t
        LEFT JOIN users u ON t.user_id = u.id
        $wsql
        ORDER BY t.created_at DESC
    ");
    $export_stmt->execute($params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=topup_history_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','External ID','Username','Nama','Metode','Nominal','Total','Fee','Status','Catatan','Waktu']);
    while ($row = $export_stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
*/
?>