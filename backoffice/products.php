<?php
// ── Konfigurasi halaman ──────────────────
$page_title  = 'Products';
$active_menu = 'products-list';
$base_path   = '';

// ── Sample data produk ───────────────────
$categories = ['Semua', 'Sepatu', 'Kaos', 'Celana', 'Tas', 'Aksesoris'];
$products = [
  [
    'id'       => 'PRD-001',
    'name'     => 'Sepatu Lari Pro X',
    'category' => 'Sepatu',
    'price'    => 520000,
    'stock'    => 142,
    'sold'     => 842,
    'status'   => 'Aktif',
    'rating'   => 4.8,
    'reviews'  => 234,
    'sku'      => 'SHP-LRX-001',
    'img_bg'   => '#1e3a5f',
    'img_color'=> '#3b82f6',
  ],
  [
    'id'       => 'PRD-002',
    'name'     => 'Kaos Olahraga Slim Fit',
    'category' => 'Kaos',
    'price'    => 185000,
    'stock'    => 310,
    'sold'     => 621,
    'status'   => 'Aktif',
    'rating'   => 4.6,
    'reviews'  => 187,
    'sku'      => 'KOS-SLM-002',
    'img_bg'   => '#1a3a2a',
    'img_color'=> '#10b981',
  ],
  [
    'id'       => 'PRD-003',
    'name'     => 'Celana Training V2',
    'category' => 'Celana',
    'price'    => 320000,
    'stock'    => 8,
    'sold'     => 504,
    'status'   => 'Aktif',
    'rating'   => 4.7,
    'reviews'  => 143,
    'sku'      => 'CLN-TRV-003',
    'img_bg'   => '#3a1a3a',
    'img_color'=> '#a855f7',
  ],
  [
    'id'       => 'PRD-004',
    'name'     => 'Tas Gym Premium',
    'category' => 'Tas',
    'price'    => 780000,
    'stock'    => 55,
    'sold'     => 310,
    'status'   => 'Aktif',
    'rating'   => 4.9,
    'reviews'  => 98,
    'sku'      => 'TAS-GYM-004',
    'img_bg'   => '#3a2a1a',
    'img_color'=> '#f59e0b',
  ],
  [
    'id'       => 'PRD-005',
    'name'     => 'Sarung Tangan Gym',
    'category' => 'Aksesoris',
    'price'    => 95000,
    'stock'    => 0,
    'sold'     => 188,
    'status'   => 'Habis',
    'rating'   => 4.4,
    'reviews'  => 76,
    'sku'      => 'AKS-STG-005',
    'img_bg'   => '#1a2a3a',
    'img_color'=> '#06b6d4',
  ],
  [
    'id'       => 'PRD-006',
    'name'     => 'Headband Sport',
    'category' => 'Aksesoris',
    'price'    => 55000,
    'stock'    => 220,
    'sold'     => 155,
    'status'   => 'Aktif',
    'rating'   => 4.3,
    'reviews'  => 52,
    'sku'      => 'AKS-HDB-006',
    'img_bg'   => '#3a1a1a',
    'img_color'=> '#ef4444',
  ],
  [
    'id'       => 'PRD-007',
    'name'     => 'Sepatu Futsal Strike',
    'category' => 'Sepatu',
    'price'    => 445000,
    'stock'    => 3,
    'sold'     => 134,
    'status'   => 'Draft',
    'rating'   => 4.5,
    'reviews'  => 41,
    'sku'      => 'SHP-FTS-007',
    'img_bg'   => '#1e2a1a',
    'img_color'=> '#10b981',
  ],
  [
    'id'       => 'PRD-008',
    'name'     => 'Jaket Windbreaker Pro',
    'category' => 'Kaos',
    'price'    => 650000,
    'stock'    => 67,
    'sold'     => 89,
    'status'   => 'Aktif',
    'rating'   => 4.7,
    'reviews'  => 33,
    'sku'      => 'KOS-WBP-008',
    'img_bg'   => '#1a1e3a',
    'img_color'=> '#3b82f6',
  ],
];

require_once 'includes/header.php';
?>

<!-- ══ PAGE HEADER ══ -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h1>Products</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Products</li>
      </ol>
    </nav>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-sm" style="background:var(--bg-card);border:1px solid var(--border);color:var(--text-subtle);border-radius:8px">
      <i class="ph ph-download-simple me-1"></i> Export
    </button>
    <button class="btn btn-sm" style="background:var(--bg-card);border:1px solid var(--border);color:var(--text-subtle);border-radius:8px">
      <i class="ph ph-upload-simple me-1"></i> Import CSV
    </button>
    <button class="btn btn-sm btn-primary" style="border-radius:8px" data-bs-toggle="modal" data-bs-target="#modalProduct">
      <i class="ph ph-plus me-1"></i> Tambah Produk
    </button>
  </div>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="stat-card blue">
      <div class="stat-icon blue"><i class="ph-fill ph-package"></i></div>
      <div class="stat-value"><?= count($products) ?></div>
      <div class="stat-label">Total Produk</div>
      <div class="stat-trend up"><i class="ph ph-trend-up"></i> +3 produk baru bulan ini</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="stat-card green">
      <div class="stat-icon green"><i class="ph-fill ph-check-circle"></i></div>
      <div class="stat-value"><?= count(array_filter($products, fn($p)=>$p['status']==='Aktif')) ?></div>
      <div class="stat-label">Produk Aktif</div>
      <div class="stat-trend up"><i class="ph ph-trend-up"></i> <?= round(count(array_filter($products, fn($p)=>$p['status']==='Aktif'))/count($products)*100) ?>% dari total</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="stat-card orange">
      <div class="stat-icon orange"><i class="ph-fill ph-warning-circle"></i></div>
      <div class="stat-value"><?= count(array_filter($products, fn($p)=>$p['stock']<10)) ?></div>
      <div class="stat-label">Stok Kritis</div>
      <div class="stat-trend down"><i class="ph ph-warning"></i> Perlu restock segera</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="stat-card purple">
      <div class="stat-icon purple"><i class="ph-fill ph-shopping-cart"></i></div>
      <div class="stat-value"><?= number_format(array_sum(array_column($products,'sold'))) ?></div>
      <div class="stat-label">Total Terjual</div>
      <div class="stat-trend up"><i class="ph ph-trend-up"></i> +8.4% bulan ini</div>
    </div>
  </div>
</div>

<!-- ══ FILTER + VIEW TOGGLE ══ -->
<div class="card-custom mb-4">
  <div class="card-body-custom">
    <div class="d-flex flex-wrap align-items-center gap-3">

      <!-- Search -->
      <div style="position:relative;flex:1;min-width:200px">
        <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:16px"></i>
        <input type="text" class="form-control settings-input" id="productSearch"
               placeholder="Cari nama produk, SKU…"
               style="padding-left:34px"/>
      </div>

      <!-- Category filter -->
      <div class="d-flex gap-2 flex-wrap">
        <?php foreach ($categories as $i => $cat): ?>
          <button class="cat-filter-btn <?= $i===0?'active':'' ?>" data-cat="<?= $cat ?>">
            <?= $cat ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Status filter -->
      <select class="form-select settings-input" id="statusFilter" style="width:auto">
        <option value="">Semua Status</option>
        <option>Aktif</option>
        <option>Habis</option>
        <option>Draft</option>
      </select>

      <!-- Sort -->
      <select class="form-select settings-input" id="sortSelect" style="width:auto">
        <option value="sold">Terlaris</option>
        <option value="price_asc">Harga Termurah</option>
        <option value="price_desc">Harga Termahal</option>
        <option value="stock">Stok Terbanyak</option>
        <option value="rating">Rating Tertinggi</option>
      </select>

      <!-- View toggle -->
      <div class="d-flex gap-1" style="background:var(--bg-hover);border:1px solid var(--border);border-radius:8px;padding:3px">
        <button class="view-btn active" id="viewGrid" title="Grid view">
          <i class="ph ph-squares-four"></i>
        </button>
        <button class="view-btn" id="viewList" title="List view">
          <i class="ph ph-list"></i>
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ══ PRODUCT GRID VIEW ══ -->
<div id="gridView">
  <div class="row g-3" id="productGrid">
    <?php foreach ($products as $p):
      $is_low   = $p['stock'] > 0 && $p['stock'] < 10;
      $is_empty = $p['stock'] === 0;
      $status_class = match($p['status']) {
        'Aktif' => 'badge-success',
        'Habis' => 'badge-danger',
        'Draft' => 'badge-warning',
        default => 'badge-accent',
      };
    ?>
      <div class="col-xl-3 col-lg-4 col-sm-6 product-card-wrap"
           data-cat="<?= $p['category'] ?>"
           data-status="<?= $p['status'] ?>"
           data-name="<?= strtolower($p['name']) ?>"
           data-sku="<?= strtolower($p['sku']) ?>"
           data-sold="<?= $p['sold'] ?>"
           data-price="<?= $p['price'] ?>"
           data-stock="<?= $p['stock'] ?>"
           data-rating="<?= $p['rating'] ?>">
        <div class="product-card card-custom">

          <!-- Image area -->
          <div style="height:160px;background:<?= $p['img_bg'] ?>;border-radius:var(--radius) var(--radius) 0 0;
                      display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden">
            <!-- Decorative circles -->
            <div style="position:absolute;width:120px;height:120px;border-radius:50%;background:<?= $p['img_color'] ?>;opacity:.1;top:-30px;right:-30px"></div>
            <div style="position:absolute;width:80px;height:80px;border-radius:50%;background:<?= $p['img_color'] ?>;opacity:.08;bottom:-20px;left:-20px"></div>
            <!-- Product icon -->
            <div style="width:72px;height:72px;border-radius:18px;background:<?= $p['img_color'] ?>22;border:1px solid <?= $p['img_color'] ?>44;
                        display:flex;align-items:center;justify-content:center;font-size:32px;color:<?= $p['img_color'] ?>">
              <i class="ph <?= match($p['category']) {
                'Sepatu'     => 'ph-sneaker',
                'Kaos'       => 'ph-t-shirt',
                'Celana'     => 'ph-pants',
                'Tas'        => 'ph-backpack',
                'Aksesoris'  => 'ph-star',
                default      => 'ph-package',
              } ?>"></i>
            </div>
            <!-- Status badge -->
            <div style="position:absolute;top:10px;left:10px">
              <span class="badge-custom <?= $status_class ?>"><?= $p['status'] ?></span>
            </div>
            <!-- Stock warning -->
            <?php if ($is_empty): ?>
              <div style="position:absolute;top:10px;right:10px">
                <span class="badge-custom badge-danger"><i class="ph ph-warning"></i> Habis</span>
              </div>
            <?php elseif ($is_low): ?>
              <div style="position:absolute;top:10px;right:10px">
                <span class="badge-custom badge-warning"><i class="ph ph-warning"></i> Kritis</span>
              </div>
            <?php endif; ?>
            <!-- Action buttons hover -->
            <div class="product-actions">
              <button class="topbar-btn" style="width:32px;height:32px;font-size:15px" title="Preview"
                      data-bs-toggle="modal" data-bs-target="#modalPreview"
                      onclick="fillPreview(<?= htmlspecialchars(json_encode($p)) ?>)">
                <i class="ph ph-eye"></i>
              </button>
              <button class="topbar-btn" style="width:32px;height:32px;font-size:15px" title="Edit">
                <i class="ph ph-pencil-simple"></i>
              </button>
              <button class="topbar-btn" style="width:32px;height:32px;font-size:15px;color:var(--danger)" title="Hapus">
                <i class="ph ph-trash"></i>
              </button>
            </div>
          </div>

          <!-- Card body -->
          <div class="card-body-custom" style="padding:14px 16px">
            <div style="font-size:10px;color:var(--text-muted);font-family:'JetBrains Mono',monospace;margin-bottom:4px"><?= $p['sku'] ?></div>
            <div style="font-size:14px;font-weight:600;margin-bottom:6px;line-height:1.3"><?= $p['name'] ?></div>
            <div class="d-flex align-items-center gap-1 mb-3">
              <?php for($s=1;$s<=5;$s++): ?>
                <i class="ph-fill ph-star" style="font-size:12px;color:<?= $s<=$p['rating']?'var(--warning)':'var(--bg-hover)' ?>"></i>
              <?php endfor; ?>
              <span style="font-size:11px;color:var(--text-muted);margin-left:2px">(<?= $p['reviews'] ?>)</span>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3">
              <div style="font-size:16px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--accent)">
                Rp <?= number_format($p['price'],0,',','.') ?>
              </div>
              <div style="font-size:12px;color:var(--text-muted)">
                Stok: <strong style="color:<?= $is_empty?'var(--danger)':($is_low?'var(--warning)':'var(--text-primary)') ?>"><?= number_format($p['stock']) ?></strong>
              </div>
            </div>

            <!-- Progress sold -->
            <div>
              <div class="d-flex justify-content-between mb-1">
                <span style="font-size:11px;color:var(--text-muted)">Terjual</span>
                <span style="font-size:11px;font-weight:600;color:var(--text-primary)"><?= number_format($p['sold']) ?></span>
              </div>
              <?php $sold_pct = min(100, round($p['sold']/($p['sold']+$p['stock'])*100)); ?>
              <div class="progress-custom">
                <div class="progress-bar-custom" style="width:<?= $sold_pct ?>%;background:<?= $p['img_color'] ?>"></div>
              </div>
            </div>
          </div>

          <!-- Card footer -->
          <div style="padding:10px 16px;border-top:1px solid var(--border);display:flex;gap:6px">
            <button class="btn btn-sm flex-fill" style="border-radius:7px;background:var(--accent-soft);border:1px solid var(--border-active);color:var(--accent);font-size:12px">
              <i class="ph ph-pencil-simple me-1"></i> Edit
            </button>
            <button class="topbar-btn" style="width:32px;height:32px;font-size:15px" title="Duplikat">
              <i class="ph ph-copy"></i>
            </button>
          </div>

        </div>
      </div>
    <?php endforeach; ?>

    <!-- Empty state -->
    <div class="col-12 d-none" id="emptyState">
      <div class="card-custom text-center py-5">
        <i class="ph ph-magnifying-glass" style="font-size:48px;color:var(--text-muted);margin-bottom:12px;display:block"></i>
        <div style="font-size:16px;font-weight:600">Produk tidak ditemukan</div>
        <div style="font-size:13px;color:var(--text-muted);margin-top:4px">Coba ubah filter atau kata kunci pencarian</div>
      </div>
    </div>
  </div>
</div><!-- /gridView -->

<!-- ══ PRODUCT LIST VIEW ══ -->
<div id="listView" class="d-none">
  <div class="card-custom">
    <div class="card-body-custom">
      <div class="table-responsive">
        <table id="productTable" class="table-dark-custom w-100">
          <thead>
            <tr>
              <th><input type="checkbox" class="form-check-input" id="checkAll" style="background:var(--bg-hover);border-color:var(--border)"/></th>
              <th>Produk</th>
              <th>Kategori</th>
              <th>Harga</th>
              <th>Stok</th>
              <th>Terjual</th>
              <th>Rating</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p):
              $is_low   = $p['stock'] > 0 && $p['stock'] < 10;
              $is_empty = $p['stock'] === 0;
              $status_class = match($p['status']) {
                'Aktif' => 'badge-success',
                'Habis' => 'badge-danger',
                'Draft' => 'badge-warning',
                default => 'badge-accent',
              };
            ?>
              <tr>
                <td><input type="checkbox" class="form-check-input row-check" style="background:var(--bg-hover);border-color:var(--border)"/></td>
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <div style="width:42px;height:42px;border-radius:10px;background:<?= $p['img_bg'] ?>;
                                display:flex;align-items:center;justify-content:center;font-size:20px;color:<?= $p['img_color'] ?>;flex-shrink:0">
                      <i class="ph <?= match($p['category']) {
                        'Sepatu'    => 'ph-sneaker',
                        'Kaos'      => 'ph-t-shirt',
                        'Celana'    => 'ph-pants',
                        'Tas'       => 'ph-backpack',
                        'Aksesoris' => 'ph-star',
                        default     => 'ph-package',
                      } ?>"></i>
                    </div>
                    <div>
                      <div style="font-size:13.5px;font-weight:600"><?= $p['name'] ?></div>
                      <div style="font-size:11px;color:var(--text-muted);font-family:'JetBrains Mono',monospace"><?= $p['sku'] ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="badge-custom badge-accent"><?= $p['category'] ?></span></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--accent)">
                  Rp <?= number_format($p['price'],0,',','.') ?>
                </td>
                <td>
                  <span style="font-weight:600;color:<?= $is_empty?'var(--danger)':($is_low?'var(--warning)':'var(--text-primary)') ?>">
                    <?= number_format($p['stock']) ?>
                    <?php if ($is_empty): ?><i class="ph ph-warning-circle" style="color:var(--danger)"></i>
                    <?php elseif ($is_low): ?><i class="ph ph-warning" style="color:var(--warning)"></i>
                    <?php endif; ?>
                  </span>
                </td>
                <td style="font-family:'JetBrains Mono',monospace"><?= number_format($p['sold']) ?></td>
                <td>
                  <div class="d-flex align-items-center gap-1">
                    <i class="ph-fill ph-star" style="font-size:13px;color:var(--warning)"></i>
                    <span style="font-size:13px;font-weight:600"><?= $p['rating'] ?></span>
                    <span style="font-size:11px;color:var(--text-muted)">(<?= $p['reviews'] ?>)</span>
                  </div>
                </td>
                <td><span class="badge-custom <?= $status_class ?>"><?= $p['status'] ?></span></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="topbar-btn" style="width:28px;height:28px;font-size:15px" title="Preview"
                            data-bs-toggle="modal" data-bs-target="#modalPreview"
                            onclick="fillPreview(<?= htmlspecialchars(json_encode($p)) ?>)">
                      <i class="ph ph-eye"></i>
                    </button>
                    <button class="topbar-btn" style="width:28px;height:28px;font-size:15px" title="Edit">
                      <i class="ph ph-pencil-simple"></i>
                    </button>
                    <button class="topbar-btn" style="width:28px;height:28px;font-size:15px" title="Duplikat">
                      <i class="ph ph-copy"></i>
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
</div><!-- /listView -->

<!-- ══ MODAL: Tambah / Edit Produk ══ -->
<div class="modal fade" id="modalProduct" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border)">
      <div class="modal-header" style="border-color:var(--border)">
        <h5 class="modal-title"><i class="ph ph-plus-circle me-2" style="color:var(--accent)"></i>Tambah Produk Baru</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">

          <!-- Upload gambar -->
          <div class="col-12">
            <label class="form-label settings-label">Foto Produk</label>
            <div class="upload-area" id="productImageUpload">
              <i class="ph ph-image" style="font-size:36px;color:var(--text-muted);margin-bottom:8px;display:block"></i>
              <div style="font-size:13px;color:var(--text-subtle);margin-bottom:4px">Drag & drop foto atau klik untuk upload</div>
              <div style="font-size:11px;color:var(--text-muted)">PNG, JPG — Maks 5MB · Rekomendasi 800×800px</div>
              <button class="btn btn-sm btn-primary mt-3" style="border-radius:7px" onclick="event.preventDefault()">
                <i class="ph ph-upload-simple me-1"></i> Pilih Foto
              </button>
            </div>
          </div>

          <div class="col-md-8">
            <label class="form-label settings-label">Nama Produk <span style="color:var(--danger)">*</span></label>
            <input type="text" class="form-control settings-input" placeholder="Contoh: Sepatu Lari Pro X"/>
          </div>
          <div class="col-md-4">
            <label class="form-label settings-label">SKU</label>
            <input type="text" class="form-control settings-input" placeholder="Auto-generate" id="skuInput"/>
          </div>

          <div class="col-md-6">
            <label class="form-label settings-label">Kategori <span style="color:var(--danger)">*</span></label>
            <select class="form-select settings-input">
              <option value="">-- Pilih Kategori --</option>
              <?php foreach (array_slice($categories, 1) as $cat): ?>
                <option><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label settings-label">Brand</label>
            <input type="text" class="form-control settings-input" placeholder="Nama brand / merek"/>
          </div>

          <div class="col-md-4">
            <label class="form-label settings-label">Harga Jual <span style="color:var(--danger)">*</span></label>
            <div class="input-group">
              <span class="input-group-text settings-input-addon" style="font-size:12px">Rp</span>
              <input type="number" class="form-control settings-input" placeholder="0" id="priceInput"/>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label settings-label">Harga Coret</label>
            <div class="input-group">
              <span class="input-group-text settings-input-addon" style="font-size:12px">Rp</span>
              <input type="number" class="form-control settings-input" placeholder="0"/>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label settings-label">Margin</label>
            <div class="input-group">
              <input type="text" class="form-control settings-input" id="marginOutput" placeholder="–" readonly/>
              <span class="input-group-text settings-input-addon"><i class="ph ph-percent"></i></span>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label settings-label">Stok Awal <span style="color:var(--danger)">*</span></label>
            <input type="number" class="form-control settings-input" placeholder="0" min="0"/>
          </div>
          <div class="col-md-4">
            <label class="form-label settings-label">Alert Stok Minimum</label>
            <input type="number" class="form-control settings-input" placeholder="10" min="0"/>
          </div>
          <div class="col-md-4">
            <label class="form-label settings-label">Berat (gram)</label>
            <input type="number" class="form-control settings-input" placeholder="0"/>
          </div>

          <div class="col-12">
            <label class="form-label settings-label">Deskripsi Produk</label>
            <textarea class="form-control settings-input" rows="4" placeholder="Tulis deskripsi lengkap produk…"></textarea>
          </div>

          <!-- Varian / Tags -->
          <div class="col-md-6">
            <label class="form-label settings-label">Ukuran Tersedia</label>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach (['XS','S','M','L','XL','XXL'] as $sz): ?>
                <label style="cursor:pointer">
                  <input type="checkbox" class="d-none size-check" value="<?= $sz ?>"/>
                  <span class="size-badge"><?= $sz ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label settings-label">Status Produk</label>
            <select class="form-select settings-input">
              <option>Aktif</option>
              <option>Draft</option>
              <option>Nonaktif</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label settings-label">Tags</label>
            <input type="text" class="form-control settings-input" placeholder="Pisahkan dengan koma: olahraga, sport, gym"/>
          </div>

        </div>
      </div>
      <div class="modal-footer" style="border-color:var(--border)">
        <button class="btn btn-sm" style="border-radius:7px;background:var(--bg-hover);border:1px solid var(--border);color:var(--text-subtle)" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-sm" style="border-radius:7px;background:var(--bg-hover);border:1px solid var(--border);color:var(--text-subtle)">
          <i class="ph ph-floppy-disk me-1"></i> Simpan Draft
        </button>
        <button class="btn btn-sm btn-primary" style="border-radius:7px">
          <i class="ph ph-check me-1"></i> Publish Produk
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══ MODAL: Preview Produk ══ -->
<div class="modal fade" id="modalPreview" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
    <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border)">
      <div class="modal-header" style="border-color:var(--border)">
        <h5 class="modal-title">Detail Produk</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <!-- Image header -->
        <div id="previewImgBg" style="height:180px;display:flex;align-items:center;justify-content:center;font-size:64px">
        </div>
        <div class="p-4">
          <div style="font-size:10px;color:var(--text-muted);font-family:'JetBrains Mono',monospace" id="previewSku"></div>
          <div style="font-size:18px;font-weight:700;margin:4px 0 8px" id="previewName"></div>
          <div class="d-flex align-items-center gap-2 mb-3">
            <div id="previewStars" class="d-flex gap-1"></div>
            <span style="font-size:12px;color:var(--text-muted)" id="previewReviews"></span>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <div style="font-size:11px;color:var(--text-muted)">Harga</div>
              <div style="font-size:20px;font-weight:700;color:var(--accent);font-family:'JetBrains Mono',monospace" id="previewPrice"></div>
            </div>
            <div class="col-6">
              <div style="font-size:11px;color:var(--text-muted)">Stok</div>
              <div style="font-size:20px;font-weight:700;" id="previewStock"></div>
            </div>
            <div class="col-6">
              <div style="font-size:11px;color:var(--text-muted)">Terjual</div>
              <div style="font-size:16px;font-weight:600" id="previewSold"></div>
            </div>
            <div class="col-6">
              <div style="font-size:11px;color:var(--text-muted)">Kategori</div>
              <div id="previewCat"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="border-color:var(--border)">
        <button class="btn btn-sm" style="border-radius:7px;background:var(--bg-hover);border:1px solid var(--border);color:var(--text-subtle)" data-bs-dismiss="modal">Tutup</button>
        <button class="btn btn-sm btn-primary" style="border-radius:7px">
          <i class="ph ph-pencil-simple me-1"></i> Edit Produk
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$extra_scripts = <<<'SCRIPT'
<script>

// ── View toggle ───────────────────────────────────────────────
document.getElementById('viewGrid').addEventListener('click', function() {
  document.getElementById('gridView').classList.remove('d-none');
  document.getElementById('listView').classList.add('d-none');
  this.classList.add('active');
  document.getElementById('viewList').classList.remove('active');
});
document.getElementById('viewList').addEventListener('click', function() {
  document.getElementById('listView').classList.remove('d-none');
  document.getElementById('gridView').classList.add('d-none');
  this.classList.add('active');
  document.getElementById('viewGrid').classList.remove('active');
  // init DataTable jika belum
  if (!$.fn.DataTable.isDataTable('#productTable')) {
    $('#productTable').DataTable();
  }
});

// ── Category filter ───────────────────────────────────────────
document.querySelectorAll('.cat-filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.cat-filter-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    filterProducts();
  });
});

document.getElementById('statusFilter').addEventListener('change', filterProducts);
document.getElementById('productSearch').addEventListener('input',  filterProducts);
document.getElementById('sortSelect').addEventListener('change',    sortProducts);

function filterProducts() {
  const cat    = document.querySelector('.cat-filter-btn.active')?.dataset.cat || 'Semua';
  const status = document.getElementById('statusFilter').value;
  const search = document.getElementById('productSearch').value.toLowerCase();
  const cards  = document.querySelectorAll('.product-card-wrap');
  let visible  = 0;

  cards.forEach(card => {
    const matchCat    = cat === 'Semua' || card.dataset.cat === cat;
    const matchStatus = !status || card.dataset.status === status;
    const matchSearch = !search || card.dataset.name.includes(search) || card.dataset.sku.includes(search);
    const show = matchCat && matchStatus && matchSearch;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  document.getElementById('emptyState').classList.toggle('d-none', visible > 0);
}

function sortProducts() {
  const sortVal = document.getElementById('sortSelect').value;
  const grid    = document.getElementById('productGrid');
  const cards   = [...grid.querySelectorAll('.product-card-wrap')];

  cards.sort((a, b) => {
    switch (sortVal) {
      case 'sold':       return +b.dataset.sold  - +a.dataset.sold;
      case 'price_asc':  return +a.dataset.price - +b.dataset.price;
      case 'price_desc': return +b.dataset.price - +a.dataset.price;
      case 'stock':      return +b.dataset.stock - +a.dataset.stock;
      case 'rating':     return +b.dataset.rating- +a.dataset.rating;
    }
  });
  cards.forEach(c => grid.appendChild(c));
}

// ── Check all (list view) ─────────────────────────────────────
document.getElementById('checkAll')?.addEventListener('change', function() {
  document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
});

// ── Modal preview ─────────────────────────────────────────────
function fillPreview(p) {
  const iconMap = {
    'Sepatu':'ph-sneaker','Kaos':'ph-t-shirt','Celana':'ph-pants',
    'Tas':'ph-backpack','Aksesoris':'ph-star','default':'ph-package'
  };
  const icon = iconMap[p.category] || iconMap['default'];

  document.getElementById('previewImgBg').style.background   = p.img_bg;
  document.getElementById('previewImgBg').style.color         = p.img_color;
  document.getElementById('previewImgBg').innerHTML =
    `<div style="width:90px;height:90px;border-radius:22px;background:${p.img_color}22;border:1px solid ${p.img_color}44;
                 display:flex;align-items:center;justify-content:center;font-size:42px">
       <i class="ph ${icon}"></i>
     </div>`;

  document.getElementById('previewSku').textContent    = p.sku;
  document.getElementById('previewName').textContent   = p.name;
  document.getElementById('previewPrice').textContent  = 'Rp ' + Number(p.price).toLocaleString('id-ID');
  document.getElementById('previewStock').textContent  = Number(p.stock).toLocaleString('id-ID');
  document.getElementById('previewStock').style.color  = p.stock === 0 ? 'var(--danger)' : p.stock < 10 ? 'var(--warning)' : 'var(--text-primary)';
  document.getElementById('previewSold').textContent   = Number(p.sold).toLocaleString('id-ID') + ' terjual';
  document.getElementById('previewReviews').textContent= '(' + p.reviews + ' ulasan)';
  document.getElementById('previewCat').innerHTML      = `<span class="badge-custom badge-accent">${p.category}</span>`;

  // stars
  let stars = '';
  for (let i = 1; i <= 5; i++) {
    stars += `<i class="ph-fill ph-star" style="font-size:14px;color:${i<=p.rating?'var(--warning)':'var(--bg-hover)'}"></i>`;
  }
  document.getElementById('previewStars').innerHTML = stars;
}

// ── Size badge toggle ─────────────────────────────────────────
document.querySelectorAll('.size-check').forEach(cb => {
  cb.addEventListener('change', function() {
    this.nextElementSibling.classList.toggle('selected', this.checked);
  });
});

// ── SKU generator ─────────────────────────────────────────────
document.getElementById('skuInput').placeholder = 'PRD-' + Math.floor(Math.random()*9000+1000);
</script>
SCRIPT;

require_once 'includes/footer.php';
?>
