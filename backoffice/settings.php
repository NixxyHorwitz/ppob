<?php
// backoffice/settings.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Settings';
$active_menu = 'settings';

// ══ LOAD DATA ════════════════════════════════════════════════
// Website settings (selalu row id=1, upsert jika kosong)
$ws = $pdo->query("SELECT * FROM website_settings WHERE id = 1 LIMIT 1")->fetch();
if (!$ws) {
  $pdo->exec("INSERT INTO website_settings (id, site_name) VALUES (1, 'AdminPPOB')");
  $ws = $pdo->query("SELECT * FROM website_settings WHERE id = 1 LIMIT 1")->fetch();
}

// Data admin yang sedang login
$me = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$me->execute([$admin_id]);
$me = $me->fetch();

// ══ HANDLE SAVE ══════════════════════════════════════════════
$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

// ── Save General (website_settings) ─────────────────────────
if ($action === 'save_general') {
  $site_name        = trim($_POST['site_name']        ?? '');
  $site_tagline     = trim($_POST['site_tagline']     ?? '');
  $site_description = trim($_POST['site_description'] ?? '');
  $site_keywords    = trim($_POST['site_keywords']    ?? '');
  $site_author      = trim($_POST['site_author']      ?? '');
  $phone            = trim($_POST['phone']            ?? '');

  if (!$site_name) {
    $toast_e = 'Nama situs wajib diisi.';
  } else {
    $pdo->prepare("UPDATE website_settings SET
            site_name=?, site_tagline=?, site_description=?,
            site_keywords=?, site_author=?, phone=?
            WHERE id=1
        ")->execute([
      $site_name,
      $site_tagline,
      $site_description,
      $site_keywords,
      $site_author,
      $phone
    ]);
    $toast = 'Pengaturan umum berhasil disimpan.';
    // Reload
    $ws = $pdo->query("SELECT * FROM website_settings WHERE id = 1")->fetch();
  }
}

// ── Save Logo / Favicon ──────────────────────────────────────
if ($action === 'save_logo') {
  $upload_dir = __DIR__ . '/../uploads/settings/';
  if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

  $updated = [];

  foreach (['site_logo' => 'logo', 'site_favicon' => 'favicon', 'seo_og_image' => 'og_image'] as $col => $field) {
    if (!empty($_FILES[$field]['tmp_name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
      $ext  = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
      $ok   = in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'ico', 'webp']);
      $size = $_FILES[$field]['size'];
      if (!$ok) {
        $toast_e = "Format file $field tidak didukung.";
        break;
      }
      if ($size > 2097152) {
        $toast_e = "File $field terlalu besar (maks 2MB).";
        break;
      }
      $fname = 'setting_' . time() . '_' . $field . '.' . $ext;
      move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $fname);
      $updated[$col] = 'uploads/settings/' . $fname;
    }
  }

  if (!$toast_e && !empty($updated)) {
    $sets = implode(',', array_map(fn($k) => "$k=?", array_keys($updated)));
    $pdo->prepare("UPDATE website_settings SET $sets WHERE id=1")
      ->execute(array_values($updated));
    $toast = 'Logo/favicon berhasil diupdate.';
    $ws = $pdo->query("SELECT * FROM website_settings WHERE id = 1")->fetch();
  } elseif (!$toast_e) {
    $toast_e = 'Tidak ada file yang dipilih.';
  }
}

// ── Save Integrasi API ───────────────────────────────────────
if ($action === 'save_api') {
  $api_username = trim($_POST['api_username'] ?? '');
  $api_key_val  = trim($_POST['api_key']      ?? '');
  $api_url      = trim($_POST['api_url']      ?? '');
  $qris_code    = trim($_POST['qris_code']    ?? '');

  $pdo->prepare("UPDATE website_settings SET api_username=?, api_key=?, api_url=?, qris_code=? WHERE id=1")
    ->execute([$api_username, $api_key_val, $api_url, $qris_code]);
  $toast = 'Pengaturan API & QRIS berhasil disimpan.';
  $ws = $pdo->query("SELECT * FROM website_settings WHERE id = 1")->fetch();
}

// ── Save Profile Admin ───────────────────────────────────────
if ($action === 'save_profile') {
  $fullname = trim($_POST['fullname'] ?? '');
  $email    = trim($_POST['email']    ?? '');
  $phone_p  = trim($_POST['phone']    ?? '');
  $nik      = trim($_POST['nik']      ?? '');
  $address  = trim($_POST['address']  ?? '');

  if (!$fullname) {
    $toast_e = 'Nama lengkap wajib diisi.';
  } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $toast_e = 'Format email tidak valid.';
  } else {
    // Cek duplikat email
    if ($email) {
      $chk = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
      $chk->execute([$email, $admin_id]);
      if ($chk->fetch()) {
        $toast_e = 'Email sudah dipakai user lain.';
      }
    }
    if (!$toast_e) {
      $pdo->prepare("UPDATE users SET fullname=?, email=?, phone=?, nik=?, address=? WHERE id=?")
        ->execute([$fullname, $email, $phone_p, $nik, $address, $admin_id]);
      $toast = 'Profil berhasil diupdate.';
      // Update session name
      $_SESSION['admin_fullname'] = $fullname;
      $admin_name = $fullname;
      $me = $pdo->prepare("SELECT * FROM users WHERE id=?");
      $me->execute([$admin_id]);
      $me = $me->fetch();
    }
  }
}

// ── Save Password Admin ──────────────────────────────────────
if ($action === 'save_password') {
  $curr     = $_POST['curr_password'] ?? '';
  $new_pass = $_POST['new_password']  ?? '';
  $conf     = $_POST['conf_password'] ?? '';

  if (!$curr || !$new_pass || !$conf) {
    $toast_e = 'Semua field password wajib diisi.';
  } elseif (!password_verify($curr, $me['password'])) {
    $toast_e = 'Password saat ini salah.';
  } elseif (strlen($new_pass) < 8) {
    $toast_e = 'Password baru minimal 8 karakter.';
  } elseif ($new_pass !== $conf) {
    $toast_e = 'Konfirmasi password tidak cocok.';
  } else {
    $pdo->prepare("UPDATE users SET password=? WHERE id=?")
      ->execute([password_hash($new_pass, PASSWORD_BCRYPT), $admin_id]);
    $toast = 'Password berhasil diubah.';
  }
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
    <h1>Settings</h1>
    <nav>
      <ol class="breadcrumb bc">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Settings</li>
      </ol>
    </nav>
  </div>
  <div style="font-size:12px;color:var(--mut)">
    <i class="ph ph-clock"></i> Terakhir update: <?= $ws['updated_at'] ? date('d M Y H:i', strtotime($ws['updated_at'])) : '—' ?>
  </div>
</div>

<style>
  .settings-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--sub);
    margin-bottom: 6px;
  }

  .settings-input {
    background: var(--hover) !important;
    border: 1px solid var(--border) !important;
    color: var(--text) !important;
    border-radius: var(--rs) !important;
    font-size: 13.5px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    transition: border-color .2s;
  }

  .settings-input:focus {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px var(--ag) !important;
  }

  .settings-input::placeholder {
    color: var(--mut);
  }

  .settings-input option {
    background: var(--card);
  }

  .settings-input-addon {
    background: var(--card) !important;
    border: 1px solid var(--border) !important;
    color: var(--sub) !important;
    border-radius: var(--rs) !important;
  }

  .settings-switch {
    background-color: var(--mut) !important;
    border-color: var(--mut) !important;
  }

  .settings-switch:checked {
    background-color: var(--accent) !important;
    border-color: var(--accent) !important;
  }

  .tab-nav-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    text-align: left;
    width: 100%;
    padding: 10px 12px;
    border-radius: var(--rs);
    border: none;
    background: transparent;
    color: var(--sub);
    transition: all .2s;
    cursor: pointer;
  }

  .tab-nav-btn:hover {
    background: var(--hover);
    color: var(--text);
  }

  .tab-nav-btn.active {
    background: var(--as);
    color: var(--accent);
  }

  .upload-area {
    border: 2px dashed var(--border);
    border-radius: var(--r);
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s;
  }

  .upload-area:hover {
    border-color: var(--accent);
  }

  .theme-opt {
    border: 2px solid var(--border);
    border-radius: var(--r);
    padding: 14px;
    cursor: pointer;
    transition: all .2s;
  }

  .theme-opt.active,
  .theme-opt:hover {
    border-color: var(--accent);
  }

  .mono {
    font-family: 'JetBrains Mono', monospace;
  }

  textarea.settings-input {
    resize: vertical;
  }

  .settings-pane {
    display: none;
  }

  .settings-pane.active {
    display: block;
  }
</style>

<!-- ══ LAYOUT ══ -->
<div class="row g-4">

  <!-- ── LEFT NAV ── -->
  <div class="col-xl-3 col-lg-4">
    <div class="card-c" style="position:sticky;top:calc(var(--hh) + 20px)">
      <div class="cb p-2">
        <?php
        $tabs = [
          ['id' => 'general',      'icon' => 'ph-gear-six',        'label' => 'General',     'sub' => 'Info & branding situs'],
          ['id' => 'api',          'icon' => 'ph-plugs-connected',  'label' => 'Integrasi',   'sub' => 'API Digiflazz & QRIS'],
          ['id' => 'profile',      'icon' => 'ph-user-circle',     'label' => 'Profil Admin', 'sub' => 'Data akun Anda'],
          ['id' => 'security',     'icon' => 'ph-shield-check',    'label' => 'Security',    'sub' => 'Ubah password'],
        ];
        foreach ($tabs as $i => $tab): ?>
          <button class="tab-nav-btn <?= $i === 0 ? 'active' : '' ?>"
            data-target="pane-<?= $tab['id'] ?>"
            type="button">
            <i class="ph <?= $tab['icon'] ?>" style="font-size:18px;flex-shrink:0"></i>
            <div>
              <div style="font-size:13px;font-weight:600;line-height:1.2"><?= $tab['label'] ?></div>
              <div style="font-size:11px;color:var(--mut)"><?= $tab['sub'] ?></div>
            </div>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ── RIGHT CONTENT ── -->
  <div class="col-xl-9 col-lg-8">
    <div class="settings-tab-content">

      <!-- ══════════════════════════════════
           PANE: GENERAL
      ══════════════════════════════════ -->
      <div class="settings-pane active" id="pane-general" role="tabpanel">

        <!-- Info Situs -->
        <div class="card-c mb-4">
          <div class="ch">
            <div>
              <p class="ct">Informasi Situs</p>
              <p class="cs">Data utama website PPOB Anda</p>
            </div>
          </div>
          <form method="POST" class="cb">
            <input type="hidden" name="action" value="save_general" />
            <div class="row g-3">
              <div class="col-md-6">
                <label class="settings-label">Nama Situs *</label>
                <input type="text" name="site_name" class="form-control settings-input"
                  value="<?= htmlspecialchars($ws['site_name'] ?? '') ?>" required placeholder="Nama aplikasi" />
              </div>
              <div class="col-md-6">
                <label class="settings-label">Tagline</label>
                <input type="text" name="site_tagline" class="form-control settings-input"
                  value="<?= htmlspecialchars($ws['site_tagline'] ?? '') ?>" placeholder="Slogan singkat" />
              </div>
              <div class="col-md-6">
                <label class="settings-label">Nama Author / Pemilik</label>
                <input type="text" name="site_author" class="form-control settings-input"
                  value="<?= htmlspecialchars($ws['site_author'] ?? '') ?>" placeholder="PT Nama Perusahaan" />
              </div>
              <div class="col-md-6">
                <label class="settings-label">No. Telepon / WA</label>
                <div class="input-group">
                  <span class="input-group-text settings-input-addon"><i class="ph ph-phone"></i></span>
                  <input type="text" name="phone" class="form-control settings-input"
                    value="<?= htmlspecialchars($ws['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx" />
                </div>
              </div>
              <div class="col-12">
                <label class="settings-label">Deskripsi Situs</label>
                <textarea name="site_description" class="form-control settings-input" rows="3"
                  placeholder="Deskripsi singkat untuk SEO…"><?= htmlspecialchars($ws['site_description'] ?? '') ?></textarea>
              </div>
              <div class="col-12">
                <label class="settings-label">Keywords SEO</label>
                <input type="text" name="site_keywords" class="form-control settings-input"
                  value="<?= htmlspecialchars($ws['site_keywords'] ?? '') ?>"
                  placeholder="ppob murah, pulsa murah, token listrik (pisahkan dengan koma)" />
                <div style="font-size:11px;color:var(--mut);margin-top:4px">Pisahkan tiap keyword dengan koma</div>
              </div>
              <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" style="border-radius:8px">
                  <i class="ph ph-floppy-disk me-1"></i> Simpan Info Situs
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Logo & Favicon -->
        <div class="card-c mb-4">
          <div class="ch">
            <div>
              <p class="ct">Logo, Favicon & OG Image</p>
              <p class="cs">Branding visual situs</p>
            </div>
          </div>
          <form method="POST" enctype="multipart/form-data" class="cb">
            <input type="hidden" name="action" value="save_logo" />
            <div class="row g-4">

              <!-- Logo -->
              <div class="col-md-4">
                <label class="settings-label">Logo Utama</label>
                <label class="upload-area d-block" for="inputLogo">
                  <?php if (!empty($ws['site_logo']) && file_exists(__DIR__ . '/../' . $ws['site_logo'])): ?>
                    <img src="<?= htmlspecialchars('../' . $ws['site_logo']) ?>"
                      style="max-height:70px;max-width:100%;object-fit:contain;margin-bottom:8px" />
                  <?php else: ?>
                    <div style="width:56px;height:56px;background:linear-gradient(135deg,var(--accent),var(--a2));border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:24px;color:#fff">
                      <i class="ph ph-image"></i>
                    </div>
                  <?php endif; ?>
                  <div style="font-size:12px;color:var(--sub)">Klik untuk upload</div>
                  <div style="font-size:11px;color:var(--mut)">PNG, SVG — Maks 2MB</div>
                </label>
                <input type="file" id="inputLogo" name="logo" accept=".png,.jpg,.jpeg,.svg,.webp" class="d-none"
                  onchange="previewImg(this,'prevLogo')" />
                <img id="prevLogo" style="display:none;max-height:50px;margin-top:6px" />
              </div>

              <!-- Favicon -->
              <div class="col-md-4">
                <label class="settings-label">Favicon</label>
                <label class="upload-area d-block" for="inputFavicon">
                  <?php if (!empty($ws['site_favicon']) && file_exists(__DIR__ . '/../' . $ws['site_favicon'])): ?>
                    <img src="<?= htmlspecialchars('../' . $ws['site_favicon']) ?>"
                      style="max-height:40px;max-width:100%;object-fit:contain;margin-bottom:8px" />
                  <?php else: ?>
                    <div style="width:40px;height:40px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:18px;color:#fff">
                      <i class="ph ph-lightning"></i>
                    </div>
                  <?php endif; ?>
                  <div style="font-size:12px;color:var(--sub)">Klik untuk upload</div>
                  <div style="font-size:11px;color:var(--mut)">ICO, PNG 32×32</div>
                </label>
                <input type="file" id="inputFavicon" name="favicon" accept=".png,.ico,.jpg" class="d-none"
                  onchange="previewImg(this,'prevFavicon')" />
                <img id="prevFavicon" style="display:none;max-height:40px;margin-top:6px" />
              </div>

              <!-- OG Image -->
              <div class="col-md-4">
                <label class="settings-label">OG Image (SEO)</label>
                <label class="upload-area d-block" for="inputOg">
                  <?php if (!empty($ws['seo_og_image']) && file_exists(__DIR__ . '/../' . $ws['seo_og_image'])): ?>
                    <img src="<?= htmlspecialchars('../' . $ws['seo_og_image']) ?>"
                      style="max-height:50px;max-width:100%;object-fit:contain;margin-bottom:8px" />
                  <?php else: ?>
                    <div style="font-size:28px;color:var(--mut);margin-bottom:4px"><i class="ph ph-share-network"></i></div>
                  <?php endif; ?>
                  <div style="font-size:12px;color:var(--sub)">Klik untuk upload</div>
                  <div style="font-size:11px;color:var(--mut)">1200×630px, Maks 2MB</div>
                </label>
                <input type="file" id="inputOg" name="og_image" accept=".png,.jpg,.jpeg" class="d-none"
                  onchange="previewImg(this,'prevOg')" />
                <img id="prevOg" style="display:none;max-height:40px;margin-top:6px" />
              </div>

            </div>
            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-primary" style="border-radius:8px">
                <i class="ph ph-upload-simple me-1"></i> Upload & Simpan
              </button>
            </div>
          </form>
        </div>

      </div><!-- /pane-general -->

      <!-- ══════════════════════════════════
           PANE: INTEGRASI / API
      ══════════════════════════════════ -->
      <div class="settings-pane" id="pane-api" role="tabpanel">

        <!-- Digiflazz API -->
        <div class="card-c mb-4">
          <div class="ch">
            <div>
              <p class="ct">API Digiflazz</p>
              <p class="cs">Konfigurasi koneksi ke provider PPOB</p>
            </div>
            <span class="bd <?= !empty($ws['api_key']) ? 'bd-ok' : 'bd-err' ?>">
              <i class="ph <?= !empty($ws['api_key']) ? 'ph-check-circle' : 'ph-x-circle' ?>"></i>
              <?= !empty($ws['api_key']) ? 'Terkonfigurasi' : 'Belum diset' ?>
            </span>
          </div>
          <form method="POST" class="cb">
            <input type="hidden" name="action" value="save_api" />
            <div class="row g-3">
              <div class="col-md-6">
                <label class="settings-label">Username API</label>
                <input type="text" name="api_username" class="form-control settings-input mono"
                  value="<?= htmlspecialchars($ws['api_username'] ?? '') ?>"
                  placeholder="Username Digiflazz" />
              </div>
              <div class="col-md-6">
                <label class="settings-label">API URL</label>
                <input type="url" name="api_url" class="form-control settings-input mono"
                  value="<?= htmlspecialchars($ws['api_url'] ?? '') ?>"
                  placeholder="https://api.digiflazz.com/v1/" />
              </div>
              <div class="col-12">
                <label class="settings-label">API Key (Production)</label>
                <div class="input-group">
                  <input type="password" name="api_key" id="inpApiKey" class="form-control settings-input mono"
                    value="<?= htmlspecialchars($ws['api_key'] ?? '') ?>"
                    placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                  <button type="button" class="input-group-text settings-input-addon" id="btnShowApiKey" style="cursor:pointer">
                    <i class="ph ph-eye"></i>
                  </button>
                </div>
                <div style="font-size:11px;color:var(--mut);margin-top:4px">
                  <i class="ph ph-lock-key"></i> Nilai disimpan terenkripsi di database. Kosongkan jika tidak ingin mengubah.
                </div>
              </div>

              <!-- QRIS Code -->
              <div class="col-12">
                <label class="settings-label">QRIS Code (String)</label>
                <textarea name="qris_code" class="form-control settings-input mono" rows="4"
                  placeholder="String QRIS EMV Co dari payment provider…"><?= htmlspecialchars($ws['qris_code'] ?? '') ?></textarea>
                <div style="font-size:11px;color:var(--mut);margin-top:4px">
                  Format EMV QRIS. Digunakan untuk generate QR code pembayaran.
                </div>
              </div>

              <!-- Preview QRIS jika ada -->
              <?php if (!empty($ws['qris_code'])): ?>
                <div class="col-12">
                  <label class="settings-label">Preview QRIS</label>
                  <div class="p-3" style="background:var(--hover);border:1px solid var(--border);border-radius:var(--rs);display:inline-block">
                    <canvas id="qrisCanvas" style="display:block"></canvas>
                    <div style="font-size:11px;color:var(--mut);text-align:center;margin-top:6px">QRIS aktif saat ini</div>
                  </div>
                </div>
              <?php endif; ?>

              <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" style="border-radius:8px">
                  <i class="ph ph-floppy-disk me-1"></i> Simpan Integrasi
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Payment Methods info -->
        <div class="card-c">
          <div class="ch">
            <div>
              <p class="ct">Metode Pembayaran Aktif</p>
              <p class="cs">Data dari tabel payment_method</p>
            </div>
            <a href="settings.php?tab=api" style="font-size:12px;color:var(--accent);text-decoration:none">Refresh <i class="ph ph-arrows-clockwise"></i></a>
          </div>
          <div class="cb" style="padding-top:12px">
            <?php
            $pay_methods = $pdo->query("SELECT * FROM payment_method ORDER BY id ASC")->fetchAll();
            if (empty($pay_methods)): ?>
              <div style="text-align:center;color:var(--mut);font-size:13px;padding:16px">Belum ada metode pembayaran</div>
              <?php else: foreach ($pay_methods as $pm): ?>
                <div class="d-flex align-items-center justify-content-between py-3" style="border-bottom:1px solid var(--border)">
                  <div class="d-flex align-items-center gap-3">
                    <div style="width:40px;height:40px;background:var(--as);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--accent)">
                      <i class="ph <?= $pm['method_type'] === 'QRIS' ? 'ph-qr-code' : 'ph-bank' ?>"></i>
                    </div>
                    <div>
                      <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($pm['bank_name'] ?? $pm['method_type']) ?></div>
                      <div style="font-size:11px;color:var(--mut)">
                        <?= $pm['method_type'] ?> ·
                        <?php if ($pm['account_number']): ?>
                          <span class="mono"><?= htmlspecialchars($pm['account_number']) ?></span> —
                          <?= htmlspecialchars($pm['account_name'] ?? '') ?>
                        <?php else: echo 'QRIS';
                        endif; ?>
                      </div>
                    </div>
                  </div>
                  <span class="bd <?= $pm['is_active'] ? 'bd-ok' : 'bd-err' ?>">
                    <i class="ph <?= $pm['is_active'] ? 'ph-check-circle' : 'ph-x-circle' ?>"></i>
                    <?= $pm['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                  </span>
                </div>
            <?php endforeach;
            endif; ?>
          </div>
        </div>

      </div><!-- /pane-api -->

      <!-- ══════════════════════════════════
           PANE: PROFIL ADMIN
      ══════════════════════════════════ -->
      <div class="settings-pane" id="pane-profile" role="tabpanel">

        <!-- Avatar -->
        <div class="card-c mb-4">
          <div class="ch">
            <div>
              <p class="ct">Foto Profil</p>
            </div>
          </div>
          <div class="cb">
            <div class="d-flex align-items-center gap-4 flex-wrap">
              <img src="https://ui-avatars.com/api/?name=<?= urlencode($me['fullname'] ?: $me['username']) ?>&background=3b82f6&color=fff&size=128"
                style="width:80px;height:80px;border-radius:50%;border:3px solid var(--accent)" />
              <div>
                <div style="font-size:14px;font-weight:700"><?= htmlspecialchars($me['fullname'] ?: $me['username']) ?></div>
                <div style="font-size:12px;color:var(--mut)">@<?= htmlspecialchars($me['username']) ?> · <?= ucfirst($me['role']) ?></div>
                <div style="font-size:11px;color:var(--mut);margin-top:4px">
                  <i class="ph ph-calendar"></i> Bergabung <?= date('d M Y', strtotime($me['created_at'])) ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Info Personal -->
        <div class="card-c mb-4">
          <div class="ch">
            <div>
              <p class="ct">Informasi Personal</p>
              <p class="cs">Data akun admin yang sedang login</p>
            </div>
          </div>
          <form method="POST" class="cb">
            <input type="hidden" name="action" value="save_profile" />
            <div class="row g-3">
              <div class="col-md-6">
                <label class="settings-label">Username</label>
                <input type="text" class="form-control settings-input" value="<?= htmlspecialchars($me['username']) ?>"
                  disabled style="opacity:.5" title="Username tidak bisa diubah" />
              </div>
              <div class="col-md-6">
                <label class="settings-label">Role</label>
                <input type="text" class="form-control settings-input" value="<?= ucfirst($me['role']) ?>"
                  disabled style="opacity:.5" />
              </div>
              <div class="col-12">
                <label class="settings-label">Nama Lengkap *</label>
                <input type="text" name="fullname" class="form-control settings-input"
                  value="<?= htmlspecialchars($me['fullname'] ?? '') ?>" required />
              </div>
              <div class="col-md-6">
                <label class="settings-label">Email</label>
                <input type="email" name="email" class="form-control settings-input"
                  value="<?= htmlspecialchars($me['email'] ?? '') ?>" placeholder="email@domain.com" />
              </div>
              <div class="col-md-6">
                <label class="settings-label">No. Telepon</label>
                <input type="text" name="phone" class="form-control settings-input"
                  value="<?= htmlspecialchars($me['phone'] ?? '') ?>" placeholder="08xx" />
              </div>
              <div class="col-md-6">
                <label class="settings-label">NIK</label>
                <input type="text" name="nik" class="form-control settings-input mono"
                  value="<?= htmlspecialchars($me['nik'] ?? '') ?>" maxlength="20" placeholder="16 digit NIK" />
              </div>
              <div class="col-md-6">
                <label class="settings-label">PIN Saat Ini</label>
                <input class="form-control settings-input mono" value="<?= htmlspecialchars($me['pin'] ?? '') ?>"
                  disabled style="opacity:.5;letter-spacing:4px" title="Ubah PIN dari halaman user" />
              </div>
              <div class="col-12">
                <label class="settings-label">Alamat</label>
                <textarea name="address" class="form-control settings-input" rows="2"
                  placeholder="Alamat lengkap…"><?= htmlspecialchars($me['address'] ?? '') ?></textarea>
              </div>
              <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" style="border-radius:8px">
                  <i class="ph ph-floppy-disk me-1"></i> Simpan Profil
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- API Key user -->
        <?php if (!empty($me['api_key'])): ?>
          <div class="card-c">
            <div class="ch">
              <div>
                <p class="ct">API Key Akun</p>
                <p class="cs">Gunakan untuk akses API publik</p>
              </div>
            </div>
            <div class="cb">
              <div class="input-group">
                <input type="password" id="myApiKey" class="form-control settings-input mono"
                  value="<?= htmlspecialchars($me['api_key']) ?>" readonly />
                <button type="button" class="input-group-text settings-input-addon" id="btnShowMyKey" style="cursor:pointer">
                  <i class="ph ph-eye"></i>
                </button>
                <button type="button" class="input-group-text settings-input-addon" onclick="copyText('<?= htmlspecialchars($me['api_key']) ?>')" style="cursor:pointer" title="Copy">
                  <i class="ph ph-copy"></i>
                </button>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div><!-- /pane-profile -->

      <!-- ══════════════════════════════════
           PANE: SECURITY
      ══════════════════════════════════ -->
      <div class="settings-pane" id="pane-security" role="tabpanel">

        <div class="card-c mb-4">
          <div class="ch">
            <div>
              <p class="ct">Ubah Password</p>
              <p class="cs">Minimal 8 karakter, kombinasi huruf & angka</p>
            </div>
          </div>
          <form method="POST" class="cb">
            <input type="hidden" name="action" value="save_password" />
            <div class="row g-3">
              <div class="col-12">
                <label class="settings-label">Password Saat Ini</label>
                <div class="input-group">
                  <input type="password" name="curr_password" id="inpCurr" class="form-control settings-input" placeholder="Password lama" required />
                  <button type="button" class="input-group-text settings-input-addon toggle-pass" data-target="inpCurr" style="cursor:pointer"><i class="ph ph-eye"></i></button>
                </div>
              </div>
              <div class="col-md-6">
                <label class="settings-label">Password Baru</label>
                <div class="input-group">
                  <input type="password" name="new_password" id="inpNew" class="form-control settings-input"
                    placeholder="Min. 8 karakter" required oninput="checkStrength(this.value)" />
                  <button type="button" class="input-group-text settings-input-addon toggle-pass" data-target="inpNew" style="cursor:pointer"><i class="ph ph-eye"></i></button>
                </div>
                <!-- Strength bar -->
                <div class="d-flex gap-1 mt-2">
                  <div class="flex-fill" id="sb1" style="height:3px;border-radius:99px;background:var(--hover)"></div>
                  <div class="flex-fill" id="sb2" style="height:3px;border-radius:99px;background:var(--hover)"></div>
                  <div class="flex-fill" id="sb3" style="height:3px;border-radius:99px;background:var(--hover)"></div>
                  <div class="flex-fill" id="sb4" style="height:3px;border-radius:99px;background:var(--hover)"></div>
                </div>
                <div id="strengthLabel" style="font-size:11px;color:var(--mut);margin-top:3px">Masukkan password baru</div>
              </div>
              <div class="col-md-6">
                <label class="settings-label">Konfirmasi Password Baru</label>
                <div class="input-group">
                  <input type="password" name="conf_password" id="inpConf" class="form-control settings-input" placeholder="Ulangi password baru" required />
                  <button type="button" class="input-group-text settings-input-addon toggle-pass" data-target="inpConf" style="cursor:pointer"><i class="ph ph-eye"></i></button>
                </div>
              </div>
              <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" style="border-radius:8px">
                  <i class="ph ph-lock-key me-1"></i> Update Password
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Info session -->
        <div class="card-c">
          <div class="ch">
            <div>
              <p class="ct">Sesi Login Saat Ini</p>
            </div>
            <form method="POST">
              <input type="hidden" name="_logout" value="1" />
              <button type="submit" class="btn btn-sm" style="border-radius:7px;background:var(--es);border:1px solid rgba(239,68,68,.2);color:var(--err);font-size:12px">
                <i class="ph ph-sign-out me-1"></i> Logout Sekarang
              </button>
            </form>
          </div>
          <div class="cb">
            <div class="d-flex align-items-center gap-3 p-3" style="background:var(--hover);border:1px solid var(--border);border-radius:var(--rs)">
              <div style="width:40px;height:40px;background:var(--as);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--accent)">
                <i class="ph ph-desktop"></i>
              </div>
              <div>
                <div style="font-size:13px;font-weight:600">Sesi aktif ini</div>
                <div style="font-size:11px;color:var(--mut)">
                  Login sejak: <?= date('d M Y H:i', $_SESSION['admin_login_at'] ?? time()) ?> ·
                  <span class="bd bd-ok" style="font-size:10px"><i class="ph ph-check-circle"></i> Online</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /pane-security -->

    </div>

  </div><!-- /settings-tab-content -->
</div><!-- /col right -->
</div><!-- /row -->

<?php
$qris_js = !empty($ws['qris_code'])
  ? 'if(typeof QRCode!=="undefined"){new QRCode(document.getElementById("qrisCanvas"),{text:' . json_encode($ws['qris_code']) . ',width:120,height:120,colorDark:"#000",colorLight:"#fff",correctLevel:QRCode.CorrectLevel.M});}'
  : '';

$page_scripts = <<<SCRIPT
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// ── Tab nav — pure JS (no Bootstrap Pills needed) ────────────
document.querySelectorAll('.tab-nav-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const target = this.dataset.target;
    // Hide all panes
    document.querySelectorAll('.settings-pane').forEach(p => p.style.display = 'none');
    // Show target pane
    const pane = document.getElementById(target);
    if (pane) pane.style.display = 'block';
    // Update active button
    document.querySelectorAll('.tab-nav-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
  });
});
// Init: hide all except active
document.querySelectorAll('.settings-pane').forEach(p => {
  p.style.display = p.classList.contains('active') ? 'block' : 'none';
});

// ── Toggle password ───────────────────────────────────────────
document.querySelectorAll('.toggle-pass').forEach(btn => {
  btn.addEventListener('click', () => {
    const inp = document.getElementById(btn.dataset.target);
    const ico = btn.querySelector('i');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'text' ? 'ph ph-eye-slash' : 'ph ph-eye';
  });
});

// API Key toggle
document.getElementById('btnShowApiKey')?.addEventListener('click', function() {
  const inp = document.getElementById('inpApiKey');
  const ico = this.querySelector('i');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'text' ? 'ph ph-eye-slash' : 'ph ph-eye';
});

document.getElementById('btnShowMyKey')?.addEventListener('click', function() {
  const inp = document.getElementById('myApiKey');
  const ico = this.querySelector('i');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'text' ? 'ph ph-eye-slash' : 'ph ph-eye';
});

// ── Copy text ─────────────────────────────────────────────────
function copyText(txt) {
  navigator.clipboard.writeText(txt).then(() => {
    const wrap = document.querySelector('.toast-wrap');
    const t = document.createElement('div');
    t.className = 'toast-item toast-ok';
    t.innerHTML = '<i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i>Disalin ke clipboard';
    wrap.appendChild(t);
    setTimeout(() => t.remove(), 3000);
  });
}

// ── Password strength ─────────────────────────────────────────
function checkStrength(val) {
  const bars  = ['sb1','sb2','sb3','sb4'].map(id => document.getElementById(id));
  const label = document.getElementById('strengthLabel');
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const lvls = [
    {c:'var(--err)',  t:'Sangat Lemah'},
    {c:'var(--warn)', t:'Lemah'},
    {c:'var(--warn)', t:'Cukup'},
    {c:'var(--ok)',   t:'Kuat'},
    {c:'var(--ok)',   t:'Sangat Kuat'},
  ];
  const lvl = lvls[score] || lvls[0];
  bars.forEach((b, i) => b.style.background = i < score ? lvl.c : 'var(--hover)');
  label.textContent = val ? lvl.t : 'Masukkan password baru';
  label.style.color = lvl.c;
}

// ── Image preview ─────────────────────────────────────────────
function previewImg(input, previewId) {
  const prev = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      prev.src = e.target.result;
      prev.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// ── QRIS Preview ──────────────────────────────────────────────
{$qris_js}
</script>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>