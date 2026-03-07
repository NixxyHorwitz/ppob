<?php
// backoffice/products.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Manage Produk';
$active_menu = 'products';

// ══ HELPERS ══════════════════════════════════════════════════
function fmt_rp(float $n): string
{
  return 'Rp ' . number_format($n, 0, ',', '.');
}
function margin_pct(float $vendor, float $sell): string
{
  if ($vendor <= 0) return '0';
  return number_format((($sell - $vendor) / $vendor) * 100, 1);
}

// ══ ACTIONS ══════════════════════════════════════════════════
$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

// ── Tambah ───────────────────────────────────────────────────
if ($action === 'add') {
  $sku_code     = trim($_POST['sku_code']      ?? '');
  $product_name = trim($_POST['product_name']  ?? '');
  $category     = trim($_POST['category']      ?? '');
  $type         = in_array($_POST['type'] ?? '', ['prabayar', 'pascabayar']) ? $_POST['type'] : 'prabayar';
  $brand        = trim($_POST['brand']         ?? '');
  $price_vendor = (float)str_replace(['.', ','], ['', '.'], $_POST['price_vendor'] ?? 0);
  $price_sell   = (float)str_replace(['.', ','], ['', '.'], $_POST['price_sell']   ?? 0);
  $status       = in_array($_POST['status'] ?? '', ['active', 'non-active']) ? $_POST['status'] : 'active';

  if (!$sku_code)     $toast_e = 'SKU Code wajib diisi.';
  elseif (!$product_name) $toast_e = 'Nama produk wajib diisi.';
  elseif (!$category) $toast_e = 'Kategori wajib diisi.';
  elseif ($price_sell <= 0) $toast_e = 'Harga jual harus lebih dari 0.';
  else {
    $chk = $pdo->prepare("SELECT id FROM products WHERE sku_code = ?");
    $chk->execute([$sku_code]);
    if ($chk->fetch()) {
      $toast_e = "SKU Code '$sku_code' sudah digunakan.";
    } else {
      $pdo->prepare("INSERT INTO products (sku_code, product_name, category, type, brand, price_vendor, price_sell, status) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$sku_code, $product_name, $category, $type, $brand, $price_vendor, $price_sell, $status]);
      $toast = "Produk '$product_name' berhasil ditambahkan.";
    }
  }
}

// ── Edit ─────────────────────────────────────────────────────
if ($action === 'edit' && !empty($_POST['id'])) {
  $id           = (int)$_POST['id'];
  $product_name = trim($_POST['product_name']  ?? '');
  $category     = trim($_POST['category']      ?? '');
  $type         = in_array($_POST['type'] ?? '', ['prabayar', 'pascabayar']) ? $_POST['type'] : 'prabayar';
  $brand        = trim($_POST['brand']         ?? '');
  $price_vendor = (float)str_replace(['.', ','], ['', '.'], $_POST['price_vendor'] ?? 0);
  $price_sell   = (float)str_replace(['.', ','], ['', '.'], $_POST['price_sell']   ?? 0);
  $status       = in_array($_POST['status'] ?? '', ['active', 'non-active']) ? $_POST['status'] : 'active';

  if (!$product_name) $toast_e = 'Nama produk wajib diisi.';
  elseif ($price_sell <= 0) $toast_e = 'Harga jual harus lebih dari 0.';
  else {
    $pdo->prepare("UPDATE products SET product_name=?, category=?, type=?, brand=?, price_vendor=?, price_sell=?, status=? WHERE id=?")
      ->execute([$product_name, $category, $type, $brand, $price_vendor, $price_sell, $status, $id]);
    $toast = "Produk berhasil diupdate.";
  }
}

// ── Toggle status ─────────────────────────────────────────────
if ($action === 'toggle' && !empty($_POST['id'])) {
  $id  = (int)$_POST['id'];
  $cur = $pdo->prepare("SELECT status FROM products WHERE id=?");
  $cur->execute([$id]);
  $now = $cur->fetchColumn();
  $new = $now === 'active' ? 'non-active' : 'active';
  $pdo->prepare("UPDATE products SET status=? WHERE id=?")->execute([$new, $id]);
  $toast = 'Status produk berhasil diubah.';
}

// ── Hapus ─────────────────────────────────────────────────────
if ($action === 'delete' && !empty($_POST['id'])) {
  $id  = (int)$_POST['id'];
  // Cek apakah ada transaksi dengan sku ini
  $used = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE sku_code = (SELECT sku_code AS psku FROM products WHERE id=?)");
  $used->execute([$id]);
  if ((int)$used->fetchColumn() > 0) {
    $toast_e = 'Produk tidak bisa dihapus karena sudah memiliki riwayat transaksi.';
  } else {
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    $toast = 'Produk berhasil dihapus.';
  }
}

// ── Bulk status ───────────────────────────────────────────────
if ($action === 'bulk_toggle' && !empty($_POST['ids'])) {
  $ids    = array_map('intval', (array)$_POST['ids']);
  $status = $_POST['bulk_status'] ?? 'active';
  $status = in_array($status, ['active', 'non-active']) ? $status : 'active';
  if ($ids) {
    $in = implode(',', $ids);
    $pdo->exec("UPDATE products SET status='$status' WHERE id IN ($in)");
    $toast = count($ids) . ' produk berhasil diubah ke ' . ($status === 'active' ? 'Aktif' : 'Nonaktif') . '.';
  }
}

// ══ FILTER & FETCH ════════════════════════════════════════════
$q        = trim($_GET['q']        ?? '');
$f_cat    = trim($_GET['category'] ?? '');
$f_brand  = trim($_GET['brand']    ?? '');
$f_type   = trim($_GET['type']     ?? '');
$f_status = $_GET['status']        ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

$where = [];
$params = [];
if ($q) {
  $where[] = "(p.sku_code LIKE ? OR p.product_name LIKE ? OR p.brand LIKE ?)";
  $s = "%$q%";
  array_push($params, $s, $s, $s);
}
if ($f_cat) {
  $where[] = "p.category = ?";
  $params[] = $f_cat;
}
if ($f_brand) {
  $where[] = "p.brand = ?";
  $params[] = $f_brand;
}
if ($f_type) {
  $where[] = "p.type = ?";
  $params[] = $f_type;
}
if ($f_status !== '') {
  $where[] = "p.status = ?";
  $params[] = $f_status;
}
$wsql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total count
$cnt   = $pdo->prepare("SELECT COUNT(*) FROM products p $wsql");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, ceil($total / $per_page));
$offset = ($page - 1) * $per_page;

// Produk dengan stat penjualan dari transactions (soft join via sku_code)
$stmt = $pdo->prepare("
    SELECT p.*,
           COALESCE(t.trx_count, 0)   AS trx_count,
           COALESCE(t.trx_success, 0) AS trx_success,
           COALESCE(t.revenue, 0)     AS revenue
    FROM products p
    LEFT JOIN (
        SELECT sku_code,
               COUNT(*)                         AS trx_count,
               SUM(status = 'success')          AS trx_success,
               SUM(CASE WHEN status='success' THEN amount ELSE 0 END) AS revenue
        FROM transactions
        GROUP BY sku_code
    ) t ON t.sku_code = p.sku_code
    $wsql
    ORDER BY p.id DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Stats ringkasan
$stats = $pdo->query("
    SELECT COUNT(*) total,
           SUM(status='active') aktif,
           COUNT(DISTINCT category) cat_count,
           COUNT(DISTINCT brand) brand_count
    FROM products
")->fetch();

// Revenue total dari transaksi sukses semua produk
$total_rev = (float)$pdo->query("
    SELECT COALESCE(SUM(t.amount), 0)
    FROM transactions t
    INNER JOIN products p ON p.sku_code = t.sku_code
    WHERE t.status = 'success'
")->fetchColumn();

// Dropdown options (dari data aktual)
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category")->fetchAll(\PDO::FETCH_COLUMN);
$brands     = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL ORDER BY brand")->fetchAll(\PDO::FETCH_COLUMN);

// Edit data
$edit_data = null;
if (!empty($_GET['edit'])) {
  $eu = $pdo->prepare("SELECT * FROM products WHERE id = ?");
  $eu->execute([(int)$_GET['edit']]);
  $edit_data = $eu->fetch();
}

// Warna per kategori
$cat_colors = [
  'Pulsa'            => ['bg' => 'var(--as)',  'cl' => 'var(--accent)', 'ic' => 'ph-device-mobile'],
  'Data'             => ['bg' => 'var(--oks)', 'cl' => 'var(--ok)',     'ic' => 'ph-wifi-high'],
  'E-Money'          => ['bg' => 'var(--ws)',  'cl' => 'var(--warn)',   'ic' => 'ph-wallet'],
  'Games'            => ['bg' => 'var(--ps)',  'cl' => 'var(--pur)',    'ic' => 'ph-game-controller'],
  'Aktivasi Perdana' => ['bg' => 'var(--es)',  'cl' => 'var(--err)',    'ic' => 'ph-sim-card'],
  'Aktivasi Voucher' => ['bg' => 'rgba(6,182,212,.12)', 'cl' => '#06b6d4', 'ic' => 'ph-ticket'],
  'Masa Aktif'       => ['bg' => 'rgba(245,158,11,.12)', 'cl' => '#f59e0b', 'ic' => 'ph-clock-countdown'],
];

$qs = http_build_query(array_filter(['q' => $q, 'category' => $f_cat, 'brand' => $f_brand, 'type' => $f_type, 'status' => $f_status, 'page' => $page]));

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
    <h1>Manage Produk</h1>
    <nav>
      <ol class="breadcrumb bc">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Produk</li>
      </ol>
    </nav>
  </div>
  <button class="btn btn-primary" style="border-radius:8px"
    data-bs-toggle="modal" data-bs-target="#mAdd">
    <i class="ph ph-plus me-1"></i> Tambah Produk
  </button>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="sc blue">
      <div class="si blue"><i class="ph-fill ph-storefront"></i></div>
      <div class="sv"><?= number_format($stats['total']) ?></div>
      <div class="sl">Total Produk</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="sc green">
      <div class="si green"><i class="ph-fill ph-check-circle"></i></div>
      <div class="sv"><?= number_format($stats['aktif']) ?></div>
      <div class="sl">Produk Aktif</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="sc orange">
      <div class="si orange"><i class="ph-fill ph-tag"></i></div>
      <div class="sv"><?= $stats['cat_count'] ?> <span style="font-size:14px;font-weight:500">kategori</span></div>
      <div class="sl"><?= $stats['brand_count'] ?> brand tersedia</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="sc purple">
      <div class="si purple"><i class="ph-fill ph-currency-dollar"></i></div>
      <div class="sv" style="font-size:18px"><?= $total_rev >= 1000000 ? 'Rp ' . number_format($total_rev / 1000000, 1, ',', '.') . 'jt' : fmt_rp($total_rev) ?></div>
      <div class="sl">Revenue dari Produk</div>
    </div>
  </div>
</div>

<!-- ══ TABEL CARD ══ -->
<div class="card-c">
  <div class="ch">
    <div>
      <p class="ct">Daftar Produk</p>
      <p class="cs">
        <?= $total ?> produk
        <?= $q ? "· cari: <strong style='color:var(--accent)'>" . htmlspecialchars($q) . "</strong>" : '' ?>
        <?= $f_cat ? "· <span class='bd bd-acc' style='font-size:10px'>" . htmlspecialchars($f_cat) . "</span>" : '' ?>
        <?= $f_brand ? "· <span class='bd bd-warn' style='font-size:10px'>" . htmlspecialchars($f_brand) . "</span>" : '' ?>
      </p>
    </div>
    <!-- Bulk action -->
    <div class="d-flex gap-2 align-items-center" id="bulkBar" style="display:none!important">
      <span id="bulkCount" style="font-size:12px;color:var(--mut)">0 dipilih</span>
      <form method="POST" id="bulkForm">
        <input type="hidden" name="action" value="bulk_toggle" />
        <input type="hidden" name="bulk_status" id="bulkStatus" value="active" />
        <div id="bulkIds"></div>
        <button type="button" class="btn btn-sm"
          style="border-radius:7px;background:var(--oks);border:1px solid rgba(16,185,129,.2);color:var(--ok);font-size:12px"
          onclick="doBulk('active')">
          <i class="ph ph-check me-1"></i>Aktifkan
        </button>
        <button type="button" class="btn btn-sm ms-1"
          style="border-radius:7px;background:var(--es);border:1px solid rgba(239,68,68,.2);color:var(--err);font-size:12px"
          onclick="doBulk('non-active')">
          <i class="ph ph-x me-1"></i>Nonaktifkan
        </button>
      </form>
    </div>
  </div>

  <!-- Filter -->
  <div class="cb pb-0">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
      <div style="position:relative;flex:1;min-width:200px">
        <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mut);font-size:16px;pointer-events:none"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="fi"
          placeholder="Cari SKU, nama, brand…" style="width:100%" />
      </div>
      <select name="category" class="fs">
        <option value="">Semua Kategori</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $f_cat === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="brand" class="fs">
        <option value="">Semua Brand</option>
        <?php foreach ($brands as $b): ?>
          <option value="<?= htmlspecialchars($b) ?>" <?= $f_brand === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="type" class="fs">
        <option value="">Semua Tipe</option>
        <option value="prabayar" <?= $f_type === 'prabayar'  ? 'selected' : '' ?>>Prabayar</option>
        <option value="pascabayar" <?= $f_type === 'pascabayar' ? 'selected' : '' ?>>Pascabayar</option>
      </select>
      <select name="status" class="fs">
        <option value="">Semua Status</option>
        <option value="active" <?= $f_status === 'active'     ? 'selected' : '' ?>>Aktif</option>
        <option value="non-active" <?= $f_status === 'non-active' ? 'selected' : '' ?>>Nonaktif</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm" style="border-radius:7px;padding:8px 16px">
        <i class="ph ph-funnel me-1"></i>Filter
      </button>
      <?php if ($q || $f_cat || $f_brand || $f_type || $f_status !== ''): ?>
        <a href="products.php" class="btn btn-sm"
          style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub);padding:8px 14px">
          <i class="ph ph-x me-1"></i>Reset
        </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Table -->
  <div class="cb">
    <?php if (empty($products)): ?>
      <div class="text-center py-5" style="color:var(--mut)">
        <i class="ph ph-storefront" style="font-size:48px;display:block;margin-bottom:10px;opacity:.4"></i>
        <div style="font-size:14px;font-weight:600;margin-bottom:4px">Tidak ada produk ditemukan</div>
        <div style="font-size:12px">Coba ubah filter atau <button onclick="document.getElementById('mAdd').querySelector('[data-bs-toggle]') && bootstrap.Modal.getOrCreateInstance(document.getElementById('mAdd')).show()" style="background:none;border:none;color:var(--accent);cursor:pointer;padding:0">tambah produk baru</button></div>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="tbl" id="prodTable">
          <thead>
            <tr>
              <th style="width:36px">
                <input type="checkbox" id="chkAll" style="accent-color:var(--accent);cursor:pointer" />
              </th>
              <th>Produk</th>
              <th>Kategori</th>
              <th>Brand</th>
              <th>Harga Vendor</th>
              <th>Harga Jual</th>
              <th>Margin</th>
              <th>Transaksi</th>
              <th>Status</th>
              <th style="text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p):
              $cc = $cat_colors[$p['category']] ?? ['bg' => 'var(--hover)', 'cl' => 'var(--sub)', 'ic' => 'ph-package'];
              $margin = (float)$p['price_sell'] - (float)$p['price_vendor'];
              $margin_p = margin_pct((float)$p['price_vendor'], (float)$p['price_sell']);
              $is_active = $p['status'] === 'active';
            ?>
              <tr style="<?= !$is_active ? 'opacity:.55' : '' ?>">

                <!-- Checkbox -->
                <td>
                  <input type="checkbox" class="rowchk" value="<?= $p['id'] ?>"
                    style="accent-color:var(--accent);cursor:pointer" />
                </td>

                <!-- Produk -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;border-radius:9px;background:<?= $cc['bg'] ?>;
                            display:flex;align-items:center;justify-content:center;
                            font-size:17px;color:<?= $cc['cl'] ?>;flex-shrink:0">
                      <i class="ph <?= $cc['ic'] ?>"></i>
                    </div>
                    <div>
                      <div style="font-weight:600;font-size:13px;line-height:1.3">
                        <?= htmlspecialchars($p['product_name']) ?>
                      </div>
                      <div style="font-size:11px;color:var(--mut);font-family:'JetBrains Mono',monospace">
                        <?= htmlspecialchars($p['sku_code']) ?>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Kategori -->
                <td>
                  <span style="font-size:12px;padding:3px 9px;border-radius:99px;
                           background:<?= $cc['bg'] ?>;color:<?= $cc['cl'] ?>;font-weight:600">
                    <?= htmlspecialchars($p['category'] ?? '—') ?>
                  </span>
                </td>

                <!-- Brand -->
                <td style="font-size:12px;color:var(--sub);font-weight:500">
                  <?= htmlspecialchars($p['brand'] ?? '—') ?>
                </td>

                <!-- Harga Vendor -->
                <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--mut)">
                  <?= fmt_rp((float)$p['price_vendor']) ?>
                </td>

                <!-- Harga Jual -->
                <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;color:var(--ok)">
                  <?= fmt_rp((float)$p['price_sell']) ?>
                </td>

                <!-- Margin -->
                <td>
                  <div style="font-size:12px;font-weight:600;color:<?= $margin >= 0 ? 'var(--ok)' : 'var(--err)' ?>">
                    +<?= fmt_rp($margin) ?>
                  </div>
                  <div style="font-size:10px;color:var(--mut)"><?= $margin_p ?>%</div>
                </td>

                <!-- Transaksi (dari transactions join) -->
                <td>
                  <?php if ($p['trx_count'] > 0): ?>
                    <div style="font-size:13px;font-weight:600;color:var(--text)">
                      <?= number_format($p['trx_success']) ?>
                      <span style="font-size:10px;color:var(--mut);font-weight:400">sukses</span>
                    </div>
                    <div style="font-size:10px;color:var(--mut)">
                      <?= number_format($p['trx_count']) ?> total trx
                    </div>
                  <?php else: ?>
                    <span style="color:var(--mut);font-size:12px">—</span>
                  <?php endif; ?>
                </td>

                <!-- Status -->
                <td>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle" />
                    <input type="hidden" name="id" value="<?= $p['id'] ?>" />
                    <button type="submit" class="bd <?= $is_active ? 'bd-ok' : 'bd-err' ?>"
                      style="border:none;cursor:pointer">
                      <i class="ph <?= $is_active ? 'ph-check-circle' : 'ph-x-circle' ?>"></i>
                      <?= $is_active ? 'Aktif' : 'Nonaktif' ?>
                    </button>
                  </form>
                </td>

                <!-- Aksi -->
                <td>
                  <div class="d-flex gap-1 justify-content-center">
                    <a href="?edit=<?= $p['id'] ?><?= $qs ? '&' . $qs : '' ?>"
                      class="ab" title="Edit">
                      <i class="ph ph-pencil-simple"></i>
                    </a>
                    <form method="POST" style="display:inline"
                      onsubmit="return confirm('Hapus produk \'<?= addslashes(htmlspecialchars($p['product_name'])) ?>\'?<?= $p['trx_count'] > 0 ? '\n\nPeringatan: produk ini memiliki riwayat transaksi.' : '' ?>')">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="id" value="<?= $p['id'] ?>" />
                      <button type="submit" class="ab red" title="Hapus"
                        <?= $p['trx_count'] > 0 ? 'style="cursor:not-allowed;opacity:.4" disabled title="Tidak bisa dihapus — ada riwayat transaksi"' : '' ?>>
                        <i class="ph ph-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>

              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
        <div class="d-flex align-items-center justify-content-between mt-4 flex-wrap gap-2">
          <div style="font-size:12px;color:var(--mut)">
            Menampilkan <?= ($offset + 1) ?>–<?= min($offset + $per_page, $total) ?> dari <?= $total ?> produk
          </div>
          <div class="d-flex gap-1">
            <?php
            $base = 'products.php?' . http_build_query(array_filter(['q' => $q, 'category' => $f_cat, 'brand' => $f_brand, 'type' => $f_type, 'status' => $f_status]));
            for ($i = 1; $i <= $pages; $i++):
              $active_pg = $i === $page;
            ?>
              <a href="<?= $base ?>&page=<?= $i ?>"
                style="width:32px;height:32px;border-radius:7px;display:flex;align-items:center;justify-content:center;
                      font-size:12px;font-weight:600;text-decoration:none;
                      background:<?= $active_pg ? 'var(--accent)' : 'var(--hover)' ?>;
                      color:<?= $active_pg ? '#fff' : 'var(--sub)' ?>;
                      border:1px solid <?= $active_pg ? 'var(--accent)' : 'var(--border)' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php endif; // end if products 
    ?>
  </div>
</div>

<!-- ══ MODAL: TAMBAH ══ -->
<div class="modal fade" id="mAdd" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content mc">
      <div class="modal-header mh">
        <h5 class="modal-title">
          <i class="ph ph-plus-circle me-2" style="color:var(--accent)"></i>Tambah Produk
        </h5>
        <button type="button" class="btn-close" style="filter:invert(1)" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="add" />
        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="ml">SKU Code *</label>
              <input type="text" name="sku_code" class="fi w-100" required
                placeholder="ax10, dana20, ff140…"
                style="font-family:'JetBrains Mono',monospace" />
              <div style="font-size:10px;color:var(--mut);margin-top:3px">Harus unik, tidak bisa diubah setelah disimpan</div>
            </div>

            <div class="col-md-6">
              <label class="ml">Nama Produk *</label>
              <input type="text" name="product_name" class="fi w-100" required
                placeholder="Telkomsel 10.000, DANA 20.000…" />
            </div>

            <div class="col-md-4">
              <label class="ml">Kategori *</label>
              <input type="text" name="category" class="fi w-100" required
                list="catList" placeholder="Pulsa, Data, E-Money…" />
              <datalist id="catList">
                <?php foreach ($categories as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>">
                  <?php endforeach; ?>
              </datalist>
            </div>

            <div class="col-md-4">
              <label class="ml">Brand</label>
              <input type="text" name="brand" class="fi w-100"
                list="brandList" placeholder="TELKOMSEL, DANA…" />
              <datalist id="brandList">
                <?php foreach ($brands as $b): ?>
                  <option value="<?= htmlspecialchars($b) ?>">
                  <?php endforeach; ?>
              </datalist>
            </div>

            <div class="col-md-4">
              <label class="ml">Tipe</label>
              <select name="type" class="fs w-100">
                <option value="prabayar">Prabayar</option>
                <option value="pascabayar">Pascabayar</option>
              </select>
            </div>

            <div class="col-12">
              <hr style="border-color:var(--border);margin:4px 0" />
            </div>

            <div class="col-md-4">
              <label class="ml">Harga Vendor (Modal)</label>
              <div style="position:relative">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--mut)">Rp</span>
                <input type="number" name="price_vendor" class="fi w-100" min="0" step="1"
                  placeholder="0" style="padding-left:32px" />
              </div>
            </div>

            <div class="col-md-4">
              <label class="ml">Harga Jual *</label>
              <div style="position:relative">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--mut)">Rp</span>
                <input type="number" name="price_sell" class="fi w-100" min="1" step="1" required
                  placeholder="0" style="padding-left:32px" id="addSell" oninput="calcMargin('add')" />
              </div>
            </div>

            <div class="col-md-4">
              <label class="ml">Estimasi Margin</label>
              <div class="fi d-flex align-items-center gap-2" id="addMarginBox"
                style="color:var(--mut);font-size:13px;font-family:'JetBrains Mono',monospace">
                —
              </div>
            </div>

            <div class="col-md-6">
              <label class="ml">Status</label>
              <select name="status" class="fs w-100">
                <option value="active">Aktif</option>
                <option value="non-active">Nonaktif</option>
              </select>
            </div>

          </div>
        </div>
        <div class="modal-footer mf">
          <button type="button" class="btn btn-sm"
            style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)"
            data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px">
            <i class="ph ph-plus me-1"></i>Tambahkan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ══ MODAL: EDIT ══ -->
<div class="modal fade" id="mEdit" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content mc">
      <div class="modal-header mh">
        <h5 class="modal-title">
          <i class="ph ph-pencil-simple me-2" style="color:var(--accent)"></i>Edit Produk
        </h5>
        <button type="button" class="btn-close" style="filter:invert(1)"
          onclick="location.href='products.php<?= $qs ? '?' . $qs : '' ?>'"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="edit" />
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>" />
        <div class="modal-body">
          <div class="row g-3">

            <!-- SKU readonly -->
            <div class="col-md-6">
              <label class="ml">SKU Code</label>
              <div class="fi d-flex align-items-center gap-2"
                style="font-family:'JetBrains Mono',monospace;opacity:.6;cursor:default">
                <i class="ph ph-lock-key" style="color:var(--mut)"></i>
                <?= htmlspecialchars($edit_data['sku_code'] ?? '') ?>
              </div>
              <div style="font-size:10px;color:var(--mut);margin-top:3px">SKU tidak bisa diubah</div>
            </div>

            <div class="col-md-6">
              <label class="ml">Nama Produk *</label>
              <input type="text" name="product_name" class="fi w-100" required
                value="<?= htmlspecialchars($edit_data['product_name'] ?? '') ?>" />
            </div>

            <div class="col-md-4">
              <label class="ml">Kategori *</label>
              <input type="text" name="category" class="fi w-100" required
                list="catListE"
                value="<?= htmlspecialchars($edit_data['category'] ?? '') ?>" />
              <datalist id="catListE">
                <?php foreach ($categories as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>">
                  <?php endforeach; ?>
              </datalist>
            </div>

            <div class="col-md-4">
              <label class="ml">Brand</label>
              <input type="text" name="brand" class="fi w-100"
                list="brandListE"
                value="<?= htmlspecialchars($edit_data['brand'] ?? '') ?>" />
              <datalist id="brandListE">
                <?php foreach ($brands as $b): ?>
                  <option value="<?= htmlspecialchars($b) ?>">
                  <?php endforeach; ?>
              </datalist>
            </div>

            <div class="col-md-4">
              <label class="ml">Tipe</label>
              <select name="type" class="fs w-100">
                <option value="prabayar" <?= ($edit_data['type'] ?? '') === 'prabayar'   ? 'selected' : '' ?>>Prabayar</option>
                <option value="pascabayar" <?= ($edit_data['type'] ?? '') === 'pascabayar' ? 'selected' : '' ?>>Pascabayar</option>
              </select>
            </div>

            <div class="col-12">
              <hr style="border-color:var(--border);margin:4px 0" />
            </div>

            <div class="col-md-4">
              <label class="ml">Harga Vendor (Modal)</label>
              <div style="position:relative">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--mut)">Rp</span>
                <input type="number" name="price_vendor" id="editVendor" class="fi w-100" min="0" step="1"
                  value="<?= (int)($edit_data['price_vendor'] ?? 0) ?>"
                  style="padding-left:32px" oninput="calcMargin('edit')" />
              </div>
            </div>

            <div class="col-md-4">
              <label class="ml">Harga Jual *</label>
              <div style="position:relative">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--mut)">Rp</span>
                <input type="number" name="price_sell" id="editSell" class="fi w-100" min="1" step="1" required
                  value="<?= (int)($edit_data['price_sell'] ?? 0) ?>"
                  style="padding-left:32px" oninput="calcMargin('edit')" />
              </div>
            </div>

            <div class="col-md-4">
              <label class="ml">Margin Saat Ini</label>
              <div class="fi d-flex align-items-center gap-2" id="editMarginBox"
                style="font-size:13px;font-family:'JetBrains Mono',monospace;
                          color:<?= ((float)($edit_data['price_sell'] ?? 0) - (float)($edit_data['price_vendor'] ?? 0)) >= 0 ? 'var(--ok)' : 'var(--err)' ?>">
                <?php
                $m = (float)($edit_data['price_sell'] ?? 0) - (float)($edit_data['price_vendor'] ?? 0);
                $mp = margin_pct((float)($edit_data['price_vendor'] ?? 0), (float)($edit_data['price_sell'] ?? 0));
                echo '+' . fmt_rp($m) . ' (' . $mp . '%)';
                ?>
              </div>
            </div>

            <div class="col-md-6">
              <label class="ml">Status</label>
              <select name="status" class="fs w-100">
                <option value="active" <?= ($edit_data['status'] ?? '') === 'active'     ? 'selected' : '' ?>>Aktif</option>
                <option value="non-active" <?= ($edit_data['status'] ?? '') === 'non-active' ? 'selected' : '' ?>>Nonaktif</option>
              </select>
            </div>

            <!-- Info transaksi terkait -->
            <?php
            if ($edit_data) {
              $trx_info = $pdo->prepare("SELECT COUNT(*) total, SUM(status='success') sukses FROM transactions WHERE sku_code=?");
              $trx_info->execute([$edit_data['sku_code']]);
              $ti = $trx_info->fetch();
            }
            ?>
            <?php if (!empty($ti) && $ti['total'] > 0): ?>
              <div class="col-12">
                <div class="d-flex align-items-center gap-3 p-3"
                  style="background:var(--as);border:1px solid rgba(59,130,246,.2);border-radius:var(--rs)">
                  <i class="ph ph-info" style="color:var(--accent);font-size:18px;flex-shrink:0"></i>
                  <div style="font-size:12px;color:var(--sub)">
                    Produk ini memiliki <strong><?= number_format($ti['total']) ?> transaksi</strong>
                    (<?= number_format($ti['sukses']) ?> sukses).
                    Produk <strong>tidak bisa dihapus</strong> selama ada riwayat transaksi.
                  </div>
                </div>
              </div>
            <?php endif; ?>

          </div>
        </div>
        <div class="modal-footer mf">
          <a href="products.php<?= $qs ? '?' . $qs : '' ?>" class="btn btn-sm"
            style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">
            Batal
          </a>
          <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px">
            <i class="ph ph-floppy-disk me-1"></i>Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .ml {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--sub);
    display: block;
    margin-bottom: 6px;
  }

  .bd-pur {
    background: rgba(168, 85, 247, .12);
    color: #a855f7;
  }

  .fs.w-100 {
    width: 100% !important;
  }
</style>

<?php
$open_edit = $edit_data ? 'new bootstrap.Modal(document.getElementById("mEdit")).show();' : '';

$page_scripts = <<<SCRIPT
<script>
// ── Open edit modal ───────────────────────────────────────────
{$open_edit}

// ── Margin calculator ─────────────────────────────────────────
function calcMargin(prefix) {
  const vendor = parseFloat(document.getElementById(prefix === 'edit' ? 'editVendor' : 'addVendor')?.value || 0);
  const sell   = parseFloat(document.getElementById(prefix + 'Sell')?.value || 0);
  const box    = document.getElementById(prefix + 'MarginBox');
  if (!box) return;
  if (!sell) { box.textContent = '—'; box.style.color = 'var(--mut)'; return; }
  const margin = sell - vendor;
  const pct    = vendor > 0 ? ((margin / vendor) * 100).toFixed(1) : 0;
  box.textContent = (margin >= 0 ? '+' : '') + 'Rp ' + margin.toLocaleString('id-ID') + ' (' + pct + '%)';
  box.style.color = margin >= 0 ? 'var(--ok)' : 'var(--err)';
}
// Init edit margin on load
calcMargin('edit');

// ── Bulk select ───────────────────────────────────────────────
const chkAll  = document.getElementById('chkAll');
const bulkBar = document.getElementById('bulkBar');
const bulkCnt = document.getElementById('bulkCount');

function updateBulkBar() {
  const checked = document.querySelectorAll('.rowchk:checked');
  if (checked.length > 0) {
    bulkBar.style.display = 'flex';
    bulkCnt.textContent   = checked.length + ' dipilih';
  } else {
    bulkBar.style.display = 'none';
  }
}

chkAll?.addEventListener('change', function() {
  document.querySelectorAll('.rowchk').forEach(c => c.checked = this.checked);
  updateBulkBar();
});
document.querySelectorAll('.rowchk').forEach(c => {
  c.addEventListener('change', function() {
    chkAll.checked = [...document.querySelectorAll('.rowchk')].every(x => x.checked);
    updateBulkBar();
  });
});

function doBulk(status) {
  const ids = [...document.querySelectorAll('.rowchk:checked')].map(c => c.value);
  if (!ids.length) return;
  const form    = document.getElementById('bulkForm');
  const idsDiv  = document.getElementById('bulkIds');
  document.getElementById('bulkStatus').value = status;
  idsDiv.innerHTML = ids.map(id => '<input type="hidden" name="ids[]" value="'+id+'"/>').join('');
  form.submit();
}

// ── Auto dismiss toast ────────────────────────────────────────
document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => t.style.opacity = '0', 3500);
  setTimeout(() => t.remove(), 4000);
});
</script>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>