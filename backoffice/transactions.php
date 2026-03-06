<?php
// backoffice/transaksi.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Transaksi';
$active_menu = 'transaksi';

// ══ ACTIONS ══════════════════════════════════════════════════════════════
$toast = $toast_e = '';

// Update status
if ($_POST['action'] ?? '' === 'set_status' && !empty($_POST['id'])) {
  $new_status = $_POST['status'] ?? '';
  if (in_array($new_status, ['pending', 'success', 'failed'])) {
    $pdo->prepare("UPDATE transactions SET status=? WHERE id=?")
      ->execute([$new_status, (int)$_POST['id']]);
    $toast = "Status transaksi #" . (int)$_POST['id'] . " diubah ke " . ucfirst($new_status) . ".";
  }
}

// Hapus satu
if ($_POST['action'] ?? '' === 'delete' && !empty($_POST['id'])) {
  $pdo->prepare("DELETE FROM transactions WHERE id=?")->execute([(int)$_POST['id']]);
  $toast = "Transaksi berhasil dihapus.";
}

// Hapus bulk (halaman ini)
if ($_POST['action'] ?? '' === 'delete_bulk' && !empty($_POST['ids'])) {
  $ids = array_map('intval', (array)$_POST['ids']);
  if ($ids) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM transactions WHERE id IN ($ph)")->execute($ids);
    $toast = count($ids) . " transaksi berhasil dihapus.";
  }
}

// ══ FILTERS ══════════════════════════════════════════════════════════════
$q          = trim($_GET['q']          ?? '');
$f_status   = $_GET['status']          ?? '';
$f_type     = $_GET['type']            ?? '';
$f_user     = (int)($_GET['user_id']   ?? 0);
$f_date_from = trim($_GET['date_from']  ?? '');
$f_date_to  = trim($_GET['date_to']    ?? '');
$per_page   = 25;
$page       = max(1, (int)($_GET['page'] ?? 1));

// ══ QUERY ════════════════════════════════════════════════════════════════
$where  = ['1=1'];
$params = [];

if ($q) {
  $where[]  = "(t.ref_id LIKE ? OR t.target LIKE ? OR t.sku_code LIKE ? OR t.sn LIKE ? OR u.username LIKE ?)";
  $lq = "%$q%";
  array_push($params, $lq, $lq, $lq, $lq, $lq);
}
if ($f_status) {
  $where[] = "t.status = ?";
  $params[] = $f_status;
}
if ($f_type) {
  $where[] = "t.type = ?";
  $params[] = $f_type;
}
if ($f_user) {
  $where[] = "t.user_id = ?";
  $params[] = $f_user;
}
if ($f_date_from) {
  $where[] = "DATE(t.created_at) >= ?";
  $params[] = $f_date_from;
}
if ($f_date_to) {
  $where[] = "DATE(t.created_at) <= ?";
  $params[] = $f_date_to;
}

$where_sql = implode(' AND ', $where);

// Total rows
$cnt_st = $pdo->prepare("SELECT COUNT(*) FROM transactions t LEFT JOIN users u ON u.id=t.user_id WHERE $where_sql");
$cnt_st->execute($params);
$total_rows  = (int)$cnt_st->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// Rows
$st = $pdo->prepare("
    SELECT t.*, u.username, u.fullname, u.role AS urole,
           p.product_name, p.category
    FROM transactions t
    LEFT JOIN users u ON u.id = t.user_id
    LEFT JOIN products p ON p.sku_code = t.sku_code
    WHERE $where_sql
    ORDER BY t.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$st->execute($params);
$rows = $st->fetchAll();

// ══ STAT CARDS ═══════════════════════════════════════════════════════════
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='success') AS sukses,
        SUM(status='pending') AS pending,
        SUM(status='failed')  AS gagal,
        SUM(CASE WHEN status='success' THEN amount ELSE 0 END) AS omzet
    FROM transactions
")->fetch();

// Users dropdown for filter
$users_list = $pdo->query("SELECT id, username, fullname FROM users ORDER BY username")->fetchAll();

// ══ HELPERS ══════════════════════════════════════════════════════════════
function trx_qstr(array $exclude = []): string
{
  $p = $_GET;
  foreach ($exclude as $k) unset($p[$k]);
  return $p ? '?' . http_build_query($p) : '';
}

function trx_status_badge(string $s): string
{
  return match ($s) {
    'success' => '<span class="bd bd-ok"><i class="ph ph-check-circle"></i>Success</span>',
    'failed'  => '<span class="bd bd-err"><i class="ph ph-x-circle"></i>Failed</span>',
    default   => '<span class="bd bd-warn"><i class="ph ph-clock"></i>Pending</span>',
  };
}

function trx_type_badge(string $t): string
{
  return $t === 'prabayar'
    ? '<span class="bd bd-acc">Prabayar</span>'
    : '<span class="bd bd-pur">Pascabayar</span>';
}

function trx_rp(int|float|null $n): string
{
  return 'Rp ' . number_format((float)$n, 0, ',', '.');
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
    <h1>Transaksi</h1>
    <nav>
      <ol class="breadcrumb bc">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Transaksi</li>
      </ol>
    </nav>
  </div>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="sc blue">
      <div class="si blue"><i class="ph ph-swap"></i></div>
      <div class="sv"><?= number_format($stats['total']) ?></div>
      <div class="sl">Total Transaksi</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="sc green">
      <div class="si green"><i class="ph ph-check-circle"></i></div>
      <div class="sv"><?= number_format($stats['sukses']) ?></div>
      <div class="sl">Sukses · <?= trx_rp($stats['omzet']) ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="sc orange">
      <div class="si orange"><i class="ph ph-clock"></i></div>
      <div class="sv"><?= number_format($stats['pending']) ?></div>
      <div class="sl">Pending</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="sc purple">
      <div class="si purple"><i class="ph ph-x-circle"></i></div>
      <div class="sv"><?= number_format($stats['gagal']) ?></div>
      <div class="sl">Gagal</div>
    </div>
  </div>
</div>

<!-- ══ MAIN CARD ══ -->
<div class="card-c">

  <!-- Filter bar -->
  <div class="ch" style="padding-bottom:16px;flex-wrap:wrap;gap:10px">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center w-100">
      <!-- Search -->
      <div style="position:relative;flex:1;min-width:200px;max-width:300px">
        <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mut);font-size:16px;pointer-events:none"></i>
        <input type="text" name="q" class="fi w-100" placeholder="Ref ID, target, SKU, SN…" value="<?= htmlspecialchars($q) ?>" style="padding-left:36px" />
      </div>

      <!-- Status -->
      <select name="status" class="fs">
        <option value="">Semua Status</option>
        <?php foreach (['success' => 'Success', 'pending' => 'Pending', 'failed' => 'Failed'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= $f_status === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Type -->
      <select name="type" class="fs">
        <option value="">Semua Tipe</option>
        <option value="prabayar" <?= $f_type === 'prabayar' ? 'selected' : '' ?>>Prabayar</option>
        <option value="pascabayar" <?= $f_type === 'pascabayar' ? 'selected' : '' ?>>Pascabayar</option>
      </select>

      <!-- User -->
      <select name="user_id" class="fs">
        <option value="">Semua User</option>
        <?php foreach ($users_list as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $f_user === $u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['username']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Date range -->
      <input type="date" name="date_from" class="fs" value="<?= htmlspecialchars($f_date_from) ?>" title="Dari tanggal" />
      <input type="date" name="date_to" class="fs" value="<?= htmlspecialchars($f_date_to) ?>" title="Sampai tanggal" />

      <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;padding:7px 16px">
        <i class="ph ph-funnel me-1"></i>Filter
      </button>
      <?php if ($q || $f_status || $f_type || $f_user || $f_date_from || $f_date_to): ?>
        <a href="transaksi.php" class="btn btn-sm" style="border-radius:8px;padding:7px 14px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">
          <i class="ph ph-x me-1"></i>Reset
        </a>
      <?php endif; ?>

      <!-- Bulk delete -->
      <div class="ms-auto d-flex gap-2">
        <form method="POST" id="formBulk">
          <input type="hidden" name="action" value="delete_bulk" />
          <button type="button" class="btn btn-sm" onclick="bulkDelete()"
            style="border-radius:8px;padding:7px 14px;background:var(--es);border:1px solid rgba(239,68,68,.25);color:var(--err)">
            <i class="ph ph-trash me-1"></i>Hapus Terpilih
          </button>
        </form>
      </div>
    </form>
  </div>

  <!-- Info bar -->
  <div style="padding:0 20px 12px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <div style="font-size:12px;color:var(--mut)">
      Menampilkan <strong style="color:var(--sub)"><?= number_format(count($rows)) ?></strong>
      dari <strong style="color:var(--sub)"><?= number_format($total_rows) ?></strong> transaksi
      <?php if ($q || $f_status || $f_type || $f_user): ?>
        <span style="color:var(--accent)">· filter aktif</span>
      <?php endif; ?>
    </div>
    <label style="font-size:12px;color:var(--mut);display:flex;align-items:center;gap:6px;cursor:pointer">
      <input type="checkbox" id="checkAll" onchange="toggleAll(this)" style="accent-color:var(--accent)" />
      Pilih semua
    </label>
  </div>

  <!-- ══ TABLE: DESKTOP ══ -->
  <div class="d-none d-md-block">
    <div style="overflow-x:auto">
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:36px"><input type="checkbox" id="checkAllTh" onchange="toggleAll(this)" style="accent-color:var(--accent)" /></th>
            <th>ID / Ref</th>
            <th>User</th>
            <th>Target</th>
            <th>Produk</th>
            <th>Tipe</th>
            <th>Amount</th>
            <th>Status</th>
            <th>SN</th>
            <th>Waktu</th>
            <th style="width:90px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="11" style="text-align:center;padding:40px;color:var(--mut)">
                <i class="ph ph-swap" style="font-size:32px;display:block;margin-bottom:8px;opacity:.3"></i>
                Tidak ada transaksi ditemukan
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><input type="checkbox" class="row-check" value="<?= $r['id'] ?>" style="accent-color:var(--accent)" /></td>

                <!-- ID / Ref -->
                <td>
                  <div style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600">#<?= $r['id'] ?></div>
                  <?php if ($r['ref_id']): ?>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--mut);margin-top:2px"><?= htmlspecialchars($r['ref_id']) ?></div>
                  <?php endif; ?>
                </td>

                <!-- User -->
                <td>
                  <?php if ($r['username']): ?>
                    <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($r['fullname'] ?: $r['username']) ?></div>
                    <div style="font-size:11px;color:var(--mut)">@<?= htmlspecialchars($r['username']) ?></div>
                  <?php else: ?>
                    <span style="color:var(--mut);font-size:12px">—</span>
                  <?php endif; ?>
                </td>

                <!-- Target -->
                <td>
                  <span style="font-family:'JetBrains Mono',monospace;font-size:12px;background:var(--hover);padding:3px 8px;border-radius:6px;border:1px solid var(--border)">
                    <?= htmlspecialchars($r['target'] ?: '—') ?>
                  </span>
                </td>

                <!-- Produk -->
                <td>
                  <div style="font-size:12px;font-weight:600;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= htmlspecialchars($r['product_name'] ?: $r['sku_code'] ?: '—') ?>
                  </div>
                  <?php if ($r['category']): ?>
                    <div style="font-size:10px;color:var(--mut)"><?= htmlspecialchars($r['category']) ?></div>
                  <?php endif; ?>
                </td>

                <!-- Tipe -->
                <td><?= trx_type_badge($r['type']) ?></td>

                <!-- Amount -->
                <td>
                  <div style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;white-space:nowrap">
                    <?= trx_rp($r['amount']) ?>
                  </div>
                </td>

                <!-- Status -->
                <td><?= trx_status_badge($r['status']) ?></td>

                <!-- SN -->
                <td>
                  <?php if ($r['sn']): ?>
                    <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--sub);max-width:120px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                      title="<?= htmlspecialchars($r['sn']) ?>">
                      <?= htmlspecialchars($r['sn']) ?>
                    </span>
                  <?php else: ?>
                    <span style="color:var(--mut);font-size:12px">—</span>
                  <?php endif; ?>
                </td>

                <!-- Waktu -->
                <td style="white-space:nowrap">
                  <div style="font-size:12px"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                  <div style="font-size:11px;color:var(--mut)"><?= date('H:i:s', strtotime($r['created_at'])) ?></div>
                </td>

                <!-- Aksi -->
                <td>
                  <div class="d-flex gap-1 align-items-center">
                    <!-- Detail -->
                    <button type="button" class="ab" title="Detail"
                      onclick="showDetail(<?= htmlspecialchars(json_encode($r)) ?>)">
                      <i class="ph ph-eye"></i>
                    </button>

                    <!-- Status dropdown -->
                    <div class="dropdown">
                      <button class="ab" title="Ubah Status" data-bs-toggle="dropdown">
                        <i class="ph ph-pencil-simple"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end" style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:6px;min-width:140px">
                        <?php foreach (['pending' => ['ph-clock', 'var(--warn)'], 'success' => ['ph-check-circle', 'var(--ok)'], 'failed' => ['ph-x-circle', 'var(--err)']] as $sv => [$si, $sc]): ?>
                          <li>
                            <form method="POST" class="d-block">
                              <input type="hidden" name="action" value="set_status" />
                              <input type="hidden" name="id" value="<?= $r['id'] ?>" />
                              <input type="hidden" name="status" value="<?= $sv ?>" />
                              <button type="submit" class="dropdown-item" style="border-radius:7px;font-size:13px;color:<?= $sv === $r['status'] ? 'var(--accent)' : 'var(--sub)' ?>;padding:7px 10px;background:<?= $sv === $r['status'] ? 'var(--as)' : 'transparent' ?>">
                                <i class="ph <?= $si ?>" style="color:<?= $sc ?>;margin-right:6px"></i>
                                <?= ucfirst($sv) ?>
                                <?php if ($sv === $r['status']): ?><i class="ph ph-check ms-auto" style="color:var(--accent)"></i><?php endif; ?>
                              </button>
                            </form>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>

                    <!-- Hapus -->
                    <form method="POST" onsubmit="return confirm('Hapus transaksi #<?= $r['id'] ?>?')">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="id" value="<?= $r['id'] ?>" />
                      <button type="submit" class="ab red" title="Hapus"><i class="ph ph-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ CARDS: MOBILE ══ -->
  <div class="d-md-none" style="padding:0 16px 16px;display:flex;flex-direction:column;gap:10px">
    <?php if (empty($rows)): ?>
      <div style="text-align:center;padding:40px;color:var(--mut)">
        <i class="ph ph-swap" style="font-size:32px;display:block;margin-bottom:8px;opacity:.3"></i>
        Tidak ada transaksi
      </div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <div style="background:var(--hover);border:1px solid var(--border);border-radius:10px;padding:14px;border-left:3px solid <?= $r['status'] === 'success' ? 'var(--ok)' : ($r['status'] === 'failed' ? 'var(--err)' : 'var(--warn)') ?>">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
            <div>
              <div style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700">#<?= $r['id'] ?></div>
              <?php if ($r['ref_id']): ?>
                <div style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--mut)"><?= htmlspecialchars($r['ref_id']) ?></div>
              <?php endif; ?>
            </div>
            <div class="d-flex gap-2 align-items-center">
              <?= trx_status_badge($r['status']) ?>
              <?= trx_type_badge($r['type']) ?>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:12px">
            <div><span style="color:var(--mut)">User</span><br><strong><?= htmlspecialchars($r['username'] ?: '—') ?></strong></div>
            <div><span style="color:var(--mut)">Target</span><br><strong style="font-family:'JetBrains Mono',monospace"><?= htmlspecialchars($r['target'] ?: '—') ?></strong></div>
            <div><span style="color:var(--mut)">Produk</span><br><strong><?= htmlspecialchars($r['product_name'] ?: $r['sku_code'] ?: '—') ?></strong></div>
            <div><span style="color:var(--mut)">Amount</span><br><strong style="font-family:'JetBrains Mono',monospace"><?= trx_rp($r['amount']) ?></strong></div>
          </div>
          <?php if ($r['sn']): ?>
            <div style="margin-top:8px;font-size:11px;color:var(--mut)">SN: <span style="font-family:'JetBrains Mono',monospace;color:var(--sub)"><?= htmlspecialchars($r['sn']) ?></span></div>
          <?php endif; ?>
          <div style="margin-top:8px;font-size:11px;color:var(--mut)"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></div>
          <div style="margin-top:10px;display:flex;gap:6px">
            <button class="ab" onclick="showDetail(<?= htmlspecialchars(json_encode($r)) ?>)" title="Detail" style="flex:1;width:auto;padding:0 10px"><i class="ph ph-eye me-1"></i>Detail</button>
            <form method="POST" onsubmit="return confirm('Hapus?')" class="d-inline">
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="id" value="<?= $r['id'] ?>" />
              <button type="submit" class="ab red" title="Hapus"><i class="ph ph-trash"></i></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ══ PAGINATION ══ -->
  <?php if ($total_pages > 1): ?>
    <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
      <div style="font-size:12px;color:var(--mut)">
        Halaman <?= $page ?> / <?= $total_pages ?>
      </div>
      <div class="d-flex gap-1 flex-wrap">
        <!-- Prev -->
        <?php $qbase = trx_qstr(['page']); ?>
        <a href="<?= $qbase ?>&page=<?= $page - 1 ?>" class="pg <?= $page <= 1 ? 'dis' : '' ?>"><i class="ph ph-caret-left"></i></a>

        <?php
        // Smart pagination with ellipsis
        $p_range = 2;
        $pages_shown = [];
        for ($i = 1; $i <= $total_pages; $i++) {
          if ($i === 1 || $i === $total_pages || abs($i - $page) <= $p_range) $pages_shown[] = $i;
        }
        $prev = 0;
        foreach ($pages_shown as $pn):
          if ($prev && $pn - $prev > 1): ?>
            <span class="pg dis">…</span>
          <?php endif; ?>
          <a href="<?= $qbase ?>&page=<?= $pn ?>" class="pg <?= $pn === $page ? 'active' : '' ?>"><?= $pn ?></a>
        <?php $prev = $pn;
        endforeach; ?>

        <!-- Next -->
        <a href="<?= $qbase ?>&page=<?= $page + 1 ?>" class="pg <?= $page >= $total_pages ? 'dis' : '' ?>"><i class="ph ph-caret-right"></i></a>
      </div>
    </div>
  <?php endif; ?>

</div><!-- /card-c -->

<!-- ══ MODAL DETAIL ══ -->
<div class="modal fade" id="modalDetail" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content mc">
      <div class="modal-header mh" style="padding:16px 20px">
        <h5 class="modal-title" style="font-size:15px;font-weight:700">
          <i class="ph ph-swap me-2" style="color:var(--accent)"></i>Detail Transaksi
        </h5>
        <button type="button" class="btn-close" style="filter:invert(1);opacity:.7" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailBody" style="padding:20px;background:transparent"></div>
    </div>
  </div>
</div>

<style>
  .dropdown-item:hover {
    background: var(--hover) !important;
  }

  .dropdown-menu {
    box-shadow: 0 8px 32px rgba(0, 0, 0, .4);
  }
</style>

<?php
$page_scripts = <<<'SCRIPT'
<script>
// ── Bulk delete ───────────────────────────────────────────────
function bulkDelete() {
  const checked = [...document.querySelectorAll('.row-check:checked')];
  if (!checked.length) { alert('Pilih transaksi terlebih dahulu.'); return; }
  if (!confirm(`Hapus ${checked.length} transaksi yang dipilih?`)) return;
  const form = document.getElementById('formBulk');
  checked.forEach(cb => {
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = cb.value;
    form.appendChild(inp);
  });
  form.submit();
}

function toggleAll(src) {
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = src.checked);
  ['checkAll','checkAllTh'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.checked = src.checked;
  });
}

// ── Detail modal ──────────────────────────────────────────────
function showDetail(r) {
  const rp = n => 'Rp ' + (parseInt(n)||0).toLocaleString('id-ID');
  const statusColor = {success:'#10b981',pending:'#f59e0b',failed:'#ef4444'};
  const statusIcon  = {success:'ph-check-circle',pending:'ph-clock',failed:'ph-x-circle'};

  const row = (label, val, mono=false) =>
    `<div style="display:flex;justify-content:space-between;align-items:flex-start;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05)">
      <span style="font-size:12px;color:#4b5e7a;font-weight:600;text-transform:uppercase;letter-spacing:.4px;flex-shrink:0;margin-right:12px">${label}</span>
      <span style="font-size:13px;${mono?"font-family:'JetBrains Mono',monospace;":""}text-align:right">${val||'<span style="color:#4b5e7a">—</span>'}</span>
    </div>`;

  const html = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <div>
        <div style="font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:700">#${r.id}</div>
        ${r.ref_id ? `<div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:#4b5e7a;margin-top:2px">${r.ref_id}</div>` : ''}
      </div>
      <div style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:${statusColor[r.status]}">
        <i class="ph ${statusIcon[r.status]}" style="font-size:18px"></i>
        ${r.status.charAt(0).toUpperCase()+r.status.slice(1)}
      </div>
    </div>
    <div>
      ${row('User',      r.fullname ? `${r.fullname} <span style="color:#4b5e7a">(@${r.username})</span>` : r.username)}
      ${row('Target',    r.target,   true)}
      ${row('SKU',       r.sku_code, true)}
      ${row('Produk',    r.product_name)}
      ${row('Kategori',  r.category)}
      ${row('Tipe',      r.type === 'prabayar' ? '<span style="background:rgba(59,130,246,.15);color:#3b82f6;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700">Prabayar</span>' : '<span style="background:rgba(168,85,247,.12);color:#a855f7;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700">Pascabayar</span>')}
      ${row('Amount',    `<strong style="font-family:'JetBrains Mono',monospace">${rp(r.amount)}</strong>`)}
      ${row('SN',        r.sn, true)}
      ${row('Waktu',     r.created_at)}
    </div>
  `;

  document.getElementById('detailBody').innerHTML = html;
  new bootstrap.Modal(document.getElementById('modalDetail')).show();
}

// ── Toast auto-dismiss ────────────────────────────────────────
document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => t.style.opacity = '0', 3500);
  setTimeout(() => t.remove(), 4000);
});
</script>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>