<?php
// backoffice/dashboard_menus.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Dashboard Menu Builder';
$active_menu = 'menus';

$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

if ($action === 'add') {
  $name          = trim($_POST['name']          ?? '');
  $href          = trim($_POST['href']          ?? '');
  $icon_type     = in_array($_POST['icon_type'] ?? '', ['fontawesome', 'image_url', 'emoji']) ? $_POST['icon_type'] : 'image_url';
  $icon_value    = trim($_POST['icon_value']    ?? '');
  $icon_bg_color = trim($_POST['icon_bg_color'] ?? '#f0fdf4');
  $icon_color    = trim($_POST['icon_color']    ?? '#01d298');
  $show_on_main  = isset($_POST['show_on_main']) ? 1 : 0;
  $is_active     = isset($_POST['is_active'])    ? 1 : 0;
  $max = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM dashboard_menus")->fetchColumn();
  if (!$name) {
    $toast_e = 'Nama menu wajib diisi.';
  } elseif (!$href) {
    $toast_e = 'Link/URL wajib diisi.';
  } elseif (!$icon_value) {
    $toast_e = 'Icon wajib diisi.';
  } else {
    $pdo->prepare("INSERT INTO dashboard_menus (name,href,icon_type,icon_value,icon_bg_color,icon_color,sort_order,is_active,show_on_main) VALUES (?,?,?,?,?,?,?,?,?)")
      ->execute([$name, $href, $icon_type, $icon_value, $icon_bg_color, $icon_color, $max + 1, $is_active, $show_on_main]);
    $toast = "Menu \"$name\" berhasil ditambahkan.";
  }
}

if ($action === 'edit' && !empty($_POST['id'])) {
  $id            = (int)$_POST['id'];
  $name          = trim($_POST['name']          ?? '');
  $href          = trim($_POST['href']          ?? '');
  $icon_type     = in_array($_POST['icon_type'] ?? '', ['fontawesome', 'image_url', 'emoji']) ? $_POST['icon_type'] : 'image_url';
  $icon_value    = trim($_POST['icon_value']    ?? '');
  $icon_bg_color = trim($_POST['icon_bg_color'] ?? '#f0fdf4');
  $icon_color    = trim($_POST['icon_color']    ?? '#01d298');
  $show_on_main  = isset($_POST['show_on_main']) ? 1 : 0;
  $is_active     = isset($_POST['is_active'])    ? 1 : 0;
  if (!$name || !$href || !$icon_value) {
    $toast_e = 'Nama, link, dan icon wajib diisi.';
  } else {
    $pdo->prepare("UPDATE dashboard_menus SET name=?,href=?,icon_type=?,icon_value=?,icon_bg_color=?,icon_color=?,is_active=?,show_on_main=? WHERE id=?")
      ->execute([$name, $href, $icon_type, $icon_value, $icon_bg_color, $icon_color, $is_active, $show_on_main, $id]);
    $toast = "Menu \"$name\" berhasil diperbarui.";
  }
}

if ($action === 'toggle' && !empty($_POST['id']))
  $pdo->prepare("UPDATE dashboard_menus SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]);

if ($action === 'toggle_main' && !empty($_POST['id']))
  $pdo->prepare("UPDATE dashboard_menus SET show_on_main = NOT show_on_main WHERE id=?")->execute([(int)$_POST['id']]);

if ($action === 'reorder' && !empty($_POST['order'])) {
  $ids = json_decode($_POST['order'], true);
  if (is_array($ids)) {
    $st = $pdo->prepare("UPDATE dashboard_menus SET sort_order=? WHERE id=?");
    foreach ($ids as $i => $id) $st->execute([$i + 1, (int)$id]);
    echo json_encode(['ok' => true]);
    exit;
  }
}

if ($action === 'delete' && !empty($_POST['id'])) {
  $r = $pdo->prepare("SELECT name FROM dashboard_menus WHERE id=?");
  $r->execute([(int)$_POST['id']]);
  $del_name = $r->fetchColumn() ?: 'menu';
  $pdo->prepare("DELETE FROM dashboard_menus WHERE id=?")->execute([(int)$_POST['id']]);
  $toast = "Menu \"$del_name\" berhasil dihapus.";
}

$menus       = $pdo->query("SELECT * FROM dashboard_menus ORDER BY sort_order ASC, id ASC")->fetchAll();
$main_menus  = array_filter($menus, fn($m) =>  $m['show_on_main']);
$other_menus = array_filter($menus, fn($m) => !$m['show_on_main']);
$total   = count($menus);
$aktif   = count(array_filter($menus, fn($m) => $m['is_active']));
$on_main = count($main_menus);

$edit_data = null;
if (!empty($_GET['edit'])) {
  $es = $pdo->prepare("SELECT * FROM dashboard_menus WHERE id=?");
  $es->execute([(int)$_GET['edit']]);
  $edit_data = $es->fetch();
}

// ── Icon renderer ─────────────────────────────────────────────
function menus_render_icon(array $m, int $size = 40): string
{
  $bg  = htmlspecialchars($m['icon_bg_color'] ?? '#f0fdf4');
  $clr = htmlspecialchars($m['icon_color']    ?? '#01d298');
  $val = htmlspecialchars($m['icon_value']    ?? '');
  $inner = match ($m['icon_type']) {
    'image_url'   => "<img src=\"$val\" alt=\"\" style=\"width:" . ($size * .6) . "px;height:" . ($size * .6) . "px;object-fit:contain\"/>",
    'fontawesome' => "<i class=\"$val\" style=\"color:$clr;font-size:" . ($size * .45) . "px\"></i>",
    'emoji'       => "<span style=\"font-size:" . ($size * .5) . "px;line-height:1\">{$m['icon_value']}</span>",
    default       => '',
  };
  return "<div style=\"width:{$size}px;height:{$size}px;border-radius:50%;background:$bg;display:flex;align-items:center;justify-content:center;flex-shrink:0\">$inner</div>";
}

// ── Menu card (all inline styles, no global class conflicts) ──
function menus_card(array $m): string
{
  $id   = (int)$m['id'];
  $name = htmlspecialchars($m['name']);
  $href = htmlspecialchars($m['href']);
  $sort = (int)$m['sort_order'];
  $itype_lbl = match ($m['icon_type']) {
    'image_url' => 'IMG',
    'fontawesome' => 'FA',
    'emoji' => '😀',
    default => '?'
  };
  $op = $m['is_active'] ? '1' : '.45';

  ob_start(); ?>
  <div data-id="<?= $id ?>" style="display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:10px 12px;transition:border-color .2s,box-shadow .2s;opacity:<?= $op ?>">

    <div class="menus-drag-handle" title="Drag untuk reorder"
      style="color:var(--mut);font-size:20px;cursor:grab;flex-shrink:0;padding:4px 2px;touch-action:none;user-select:none">
      <i class="ph ph-dots-six-vertical"></i>
    </div>

    <div style="position:relative;flex-shrink:0">
      <?= menus_render_icon($m, 44) ?>
      <div style="position:absolute;bottom:-3px;right:-3px;font-size:8px;font-weight:700;background:var(--surface);border:1px solid var(--border);border-radius:3px;padding:1px 3px;color:var(--mut);line-height:1.4"><?= $itype_lbl ?></div>
    </div>

    <div style="flex:1;min-width:0">
      <div style="font-size:13.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $name ?></div>
      <div style="font-size:11px;color:var(--mut);font-family:'JetBrains Mono',monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px"><?= $href ?></div>
      <div style="display:flex;gap:4px;margin-top:5px;flex-wrap:wrap">
        <?php if ($m['is_active']): ?>
          <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(16,185,129,.12);color:#10b981;display:inline-flex;align-items:center;gap:3px"><i class="ph ph-circle-fill" style="font-size:5px"></i>Aktif</span>
        <?php else: ?>
          <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(255,255,255,.06);color:#4b5e7a;display:inline-flex;align-items:center;gap:3px"><i class="ph ph-circle" style="font-size:5px"></i>Nonaktif</span>
        <?php endif; ?>
        <?php if ($m['show_on_main']): ?>
          <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(59,130,246,.15);color:#3b82f6">Utama</span>
        <?php else: ?>
          <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(255,255,255,.05);color:#4b5e7a">Lainnya</span>
        <?php endif; ?>
        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(255,255,255,.05);color:#4b5e7a;font-family:'JetBrains Mono',monospace">#<?= $sort ?></span>
      </div>
    </div>

    <div style="display:flex;gap:4px;flex-shrink:0">
      <!-- Toggle aktif -->
      <form method="POST" class="d-inline">
        <input type="hidden" name="action" value="toggle" />
        <input type="hidden" name="id" value="<?= $id ?>" />
        <button type="submit" title="<?= $m['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"
          style="width:28px;height:28px;border-radius:7px;border:none;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;<?= $m['is_active'] ? 'background:rgba(16,185,129,.12);color:#10b981' : 'background:rgba(255,255,255,.06);color:#4b5e7a' ?>">
          <i class="ph <?= $m['is_active'] ? 'ph-eye' : 'ph-eye-slash' ?>"></i>
        </button>
      </form>
      <!-- Toggle main -->
      <form method="POST" class="d-inline">
        <input type="hidden" name="action" value="toggle_main" />
        <input type="hidden" name="id" value="<?= $id ?>" />
        <button type="submit" title="<?= $m['show_on_main'] ? 'Pindah ke Lainnya' : 'Pindah ke Utama' ?>"
          style="width:28px;height:28px;border-radius:7px;border:none;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;<?= $m['show_on_main'] ? 'background:rgba(59,130,246,.15);color:#3b82f6' : 'background:rgba(255,255,255,.06);color:#4b5e7a' ?>">
          <i class="ph <?= $m['show_on_main'] ? 'ph-star' : 'ph-tray' ?>"></i>
        </button>
      </form>
      <!-- Edit -->
      <a href="?edit=<?= $id ?>" title="Edit"
        style="width:28px;height:28px;border-radius:7px;background:rgba(255,255,255,.06);color:var(--sub);display:flex;align-items:center;justify-content:center;font-size:14px;text-decoration:none">
        <i class="ph ph-pencil-simple"></i>
      </a>
      <!-- Hapus /chsssssssschh-->
      <form method="POST" class="d-inline" onsubmit="return confirm('Hapus menu <?= addslashes($name) ?>?')">
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="id" value="<?= $id ?>" />
        <button type="submit" title="Hapus"
          style="width:28px;height:28px;border-radius:7px;background:rgba(239,68,68,.12);color:#ef4444;border:none;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer">
          <i class="ph ph-trash"></i>
        </button>
      </form>
    </div>
  </div>
<?php return ob_get_clean();
}

// ── Form body for modals ──────────────────────────────────────
function menus_form_fields(string $mid, array $fd): void
{
  $sel    = $fd['icon_type'] ?? 'image_url';
  $bg_val = htmlspecialchars($fd['icon_bg_color'] ?? '#f0fdf4');
  $fg_val = htmlspecialchars($fd['icon_color']    ?? '#01d298');
  $act_chk   = (!isset($fd['is_active'])    || $fd['is_active'])    ? 'checked' : '';
  $main_chk  = (!isset($fd['show_on_main']) || $fd['show_on_main']) ? 'checked' : '';
  $act_bg    = ($act_chk  ? '#3b82f6' : 'rgba(255,255,255,.12)');
  $main_bg   = ($main_chk ? '#3b82f6' : 'rgba(255,255,255,.12)');
  $act_left  = ($act_chk  ? '19px' : '3px');
  $main_left = ($main_chk ? '19px' : '3px');
  $label_style = "font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);display:block;margin-bottom:6px";
?>
  <div class="row g-3">
    <div class="col-md-7">

      <div class="mb-3">
        <label style="<?= $label_style ?>">Nama Menu *</label>
        <input type="text" name="name" class="fi w-100" maxlength="100" style="padding-left:13px" placeholder="Pulsa & Data, BPJS…"
          value="<?= htmlspecialchars($fd['name'] ?? '') ?>" />
      </div>

      <div class="mb-3">
        <label style="<?= $label_style ?>">Link / URL *</label>
        <div style="position:relative">
          <i class="ph ph-link" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mut);font-size:16px;pointer-events:none"></i>
          <input type="text" name="href" class="fi w-100" maxlength="255"
            placeholder="/modules/prabayar/index atau #" style="padding-left:34px"
            value="<?= htmlspecialchars($fd['href'] ?? '') ?>" />
        </div>
      </div>

      <div class="mb-3">
        <label style="<?= $label_style ?>">Tipe Icon *</label>
        <div class="d-flex gap-2">
          <?php foreach (['image_url' => ['ph-image', 'URL Gambar'], 'fontawesome' => ['ph-flag', 'FontAwesome'], 'emoji' => ['ph-smiley', 'Emoji']] as $v => [$ic, $lb]): ?>
            <label id="<?= $mid ?>_itlbl_<?= $v ?>"
              style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:7px 10px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid <?= $sel === $v ? '#3b82f6' : 'var(--border)' ?>;background:<?= $sel === $v ? 'rgba(59,130,246,.15)' : 'var(--hover)' ?>;color:<?= $sel === $v ? '#3b82f6' : 'var(--sub)' ?>"
              for="<?= $mid ?>_it_<?= $v ?>">
              <input type="radio" name="icon_type" value="<?= $v ?>" id="<?= $mid ?>_it_<?= $v ?>"
                <?= $sel === $v ? 'checked' : '' ?> class="d-none"
                onchange="menusIconTypeChange('<?= $mid ?>')" />
              <i class="ph <?= $ic ?>"></i> <?= $lb ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="mb-3">
        <label style="<?= $label_style ?>" id="<?= $mid ?>_iconLbl">Nilai Icon *</label>
        <input type="text" name="icon_value" class="fi w-100" maxlength="500" style="padding-left:13px"
          id="<?= $mid ?>_iconVal" placeholder="https://img.icons8.com/…"
          value="<?= htmlspecialchars($fd['icon_value'] ?? '') ?>"
          oninput="menusUpdatePreview('<?= $mid ?>')" />
        <div id="<?= $mid ?>_iconHint" style="font-size:11px;color:var(--mut);margin-top:4px">URL lengkap gambar icon (JPG, PNG, SVG, WebP)</div>
      </div>

      <div class="row g-2">
        <?php foreach (['bg' => ['icon_bg_color', $bg_val, 'Warna Background', ['#fff5f5', '#fffbeb', '#f0fdf4', '#eff6ff', '#fdf4ff', '#fff7ed', '#f8fafc', '#fefce8', '#fff1f2']], 'fg' => ['icon_color', $fg_val, 'Warna Icon', ['#ef4444', '#f59e0b', '#01d298', '#3b82f6', '#a855f7', '#f97316', '#64748b', '#eab308', '#10b981']]] as $which => [$fname, $fval, $flbl, $palette]): ?>
          <div class="col-6">
            <label style="<?= $label_style ?>"><?= $flbl ?></label>
            <div style="display:flex;gap:8px;align-items:center">
              <input type="color" name="<?= $fname ?>" id="<?= $mid ?>_<?= $which ?>Color"
                style="width:36px;height:36px;border-radius:8px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer;flex-shrink:0"
                value="<?= $fval ?>"
                oninput="document.getElementById('<?= $mid ?>_<?= $which ?>Hex').value=this.value;menusUpdatePreview('<?= $mid ?>')" />
              <input type="text" class="fi" id="<?= $mid ?>_<?= $which ?>Hex" style="padding-left:13px"
                style="width:90px;font-family:'JetBrains Mono',monospace;font-size:12px"
                value="<?= $fval ?>" maxlength="7"
                oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){document.getElementById('<?= $mid ?>_<?= $which ?>Color').value=this.value;menusUpdatePreview('<?= $mid ?>')}" />
            </div>
            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:8px">
              <?php foreach ($palette as $c): ?>
                <button type="button" onclick="menusSetPalette('<?= $mid ?>','<?= $which ?>','<?= $c ?>')"
                  style="width:20px;height:20px;border-radius:5px;border:1.5px solid rgba(255,255,255,.12);background:<?= $c ?>;cursor:pointer;padding:0"
                  onmouseover="this.style.transform='scale(1.3)'" onmouseout="this.style.transform=''"></button>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </div><!-- /left -->

    <div class="col-md-5">
      <label style="<?= $label_style ?>">Preview</label>
      <div style="background:var(--hover);border:1px solid var(--border);border-radius:12px;padding:20px;text-align:center">
        <div style="display:inline-flex;flex-direction:column;align-items:center;gap:8px">
          <div id="<?= $mid ?>_prevIcon"
            style="width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:<?= $bg_val ?>">
            <?php
            if (!empty($fd['icon_value'])) {
              if ($fd['icon_type'] === 'image_url')
                echo "<img src=\"" . htmlspecialchars($fd['icon_value']) . "\" style=\"width:30px;height:30px;object-fit:contain\"/>";
              elseif ($fd['icon_type'] === 'fontawesome')
                echo "<i class=\"" . htmlspecialchars($fd['icon_value']) . "\" style=\"color:" . htmlspecialchars($fd['icon_color']) . ";font-size:22px\"></i>";
              elseif ($fd['icon_type'] === 'emoji')
                echo "<span style='font-size:26px'>" . htmlspecialchars($fd['icon_value']) . "</span>";
            } else {
              echo "<i class='ph ph-image-square' style='color:#94a3b8;font-size:22px'></i>";
            }
            ?>
          </div>
          <div id="<?= $mid ?>_prevName"
            style="font-size:11px;font-weight:600;color:var(--sub);max-width:64px;text-align:center;word-break:break-word;line-height:1.3">
            <?= htmlspecialchars($fd['name'] ?? 'Nama Menu') ?>
          </div>
        </div>
        <div style="font-size:10px;color:var(--mut);margin-top:8px">Tampilan di app</div>
      </div>

      <div style="background:var(--hover);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-top:14px">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid var(--border)">
          <div>
            <div style="font-size:13px;font-weight:600">Aktifkan Menu</div>
            <div style="font-size:11px;color:var(--mut)">Tampilkan ke pengguna</div>
          </div>
          <label style="position:relative;display:inline-block;width:38px;height:22px;flex-shrink:0;cursor:pointer">
            <input type="checkbox" name="is_active" <?= $act_chk ?>
              style="position:absolute;opacity:0;width:0;height:0"
              onchange="menusSw(this)" />
            <span style="position:absolute;inset:0;border-radius:99px;background:<?= $act_bg ?>;transition:background .2s">
              <span style="position:absolute;top:3px;left:<?= $act_left ?>;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .2s;display:block"></span>
            </span>
          </label>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px">
          <div>
            <div style="font-size:13px;font-weight:600">Menu Utama</div>
            <div style="font-size:11px;color:var(--mut)">Tampil di section utama</div>
          </div>
          <label style="position:relative;display:inline-block;width:38px;height:22px;flex-shrink:0;cursor:pointer">
            <input type="checkbox" name="show_on_main" <?= $main_chk ?>
              style="position:absolute;opacity:0;width:0;height:0"
              onchange="menusSw(this)" />
            <span style="position:absolute;inset:0;border-radius:99px;background:<?= $main_bg ?>;transition:background .2s">
              <span style="position:absolute;top:3px;left:<?= $main_left ?>;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .2s;display:block"></span>
            </span>
          </label>
        </div>
      </div>

      <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:10px 12px;font-size:11.5px;color:var(--sub);display:flex;align-items:flex-start;gap:6px;line-height:1.5;margin-top:14px">
        <i class="ph ph-lightbulb" style="color:#f59e0b;flex-shrink:0;margin-top:1px"></i>
        <span><strong>Tips:</strong> Gunakan icons8.com/fluency untuk icon yang konsisten.</span>
      </div>
    </div><!-- /right -->
  </div>
<?php
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- TOAST -->
<div class="toast-wrap">
  <?php if ($toast):   ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>
  <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<!-- PAGE HEADER -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h1>Menu Builder</h1>
    <nav>
      <ol class="breadcrumb bc">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Dashboard Menu</li>
      </ol>
    </nav>
  </div>
  <div class="d-flex align-items-center gap-2">
    <span style="font-size:12px;color:var(--mut)"><i class="ph ph-arrows-out-cardinal me-1"></i>Drag kartu untuk ubah urutan</span>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddMenu" style="border-radius:8px">
      <i class="ph ph-plus me-1"></i>Tambah Menu
    </button>
  </div>
</div>

<!-- SUMMARY BAR — 100% inline style -->
<div style="display:flex;align-items:center;background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px">
  <?php $sbitems = [[$total, 'Total Menu', ''], [$aktif, 'Aktif', 'color:#10b981'], [$on_main, 'Menu Utama', 'color:#3b82f6'], [$total - $aktif, 'Nonaktif', 'color:var(--mut)']];
  foreach ($sbitems as $i => [$v, $l, $c]): ?>
    <?php if ($i): ?><div style="width:1px;background:var(--border);align-self:stretch"></div><?php endif; ?>
    <div style="flex:1;padding:14px 20px;text-align:center">
      <div style="font-size:24px;font-weight:700;font-family:'JetBrains Mono',monospace;line-height:1;<?= $c ?>"><?= $v ?></div>
      <div style="font-size:11px;color:var(--mut);font-weight:600;margin-top:3px"><?= $l ?></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- PREVIEW STRIP — 100% inline style -->
<div style="background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px">
  <div style="font-size:12px;font-weight:700;color:var(--sub);padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center">
    <i class="ph ph-device-mobile me-1"></i>Preview Tampilan App
    <span style="font-size:10px;color:var(--mut);font-weight:400;margin-left:8px">Menu Utama · hanya yang aktif</span>
  </div>
  <div style="display:flex;gap:4px;padding:14px 16px;overflow-x:auto;scrollbar-width:none">
    <?php $has = false;
    foreach ($main_menus as $m): if (!$m['is_active']) continue;
      $has = true; ?>
      <div style="display:flex;flex-direction:column;align-items:center;gap:6px;min-width:60px">
        <?= menus_render_icon($m, 48) ?>
        <div style="font-size:9px;color:var(--sub);font-weight:600;text-align:center;max-width:60px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($m['name']) ?></div>
      </div>
    <?php endforeach;
    if (!$has): ?>
      <div style="color:var(--mut);font-size:12px;padding:12px">Belum ada menu aktif di menu utama</div>
    <?php endif; ?>
  </div>
</div>

<!-- TWO-COLUMN EDITOR -->
<div class="row g-4">
  <div class="col-lg-6">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
      <div style="width:10px;height:10px;border-radius:50%;background:#3b82f6;flex-shrink:0"></div>
      <h2 style="font-size:14px;font-weight:700;margin:0">Menu Utama</h2>
      <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(59,130,246,.15);color:#3b82f6"><?= count($main_menus) ?></span>
      <div style="margin-left:auto;font-size:11px;color:var(--mut)">show_on_main = 1</div>
    </div>
    <div id="listMain" style="display:flex;flex-direction:column;gap:8px;min-height:80px">
      <?php if (empty($main_menus)): ?>
        <div style="display:flex;flex-direction:column;align-items:center;padding:32px;border:2px dashed var(--border);border-radius:12px;color:var(--mut);font-size:12px;gap:8px">
          <i class="ph ph-squares-four" style="font-size:28px;opacity:.4"></i>
          <div>Belum ada menu utama</div>
        </div>
      <?php else: foreach ($main_menus as $m) echo menus_card($m);
      endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
      <div style="width:10px;height:10px;border-radius:50%;background:#a855f7;flex-shrink:0"></div>
      <h2 style="font-size:14px;font-weight:700;margin:0">Menu Lainnya</h2>
      <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(168,85,247,.12);color:#a855f7"><?= count($other_menus) ?></span>
      <div style="margin-left:auto;font-size:11px;color:var(--mut)">show_on_main = 0</div>
    </div>
    <div id="listOther" style="display:flex;flex-direction:column;gap:8px;min-height:80px">
      <?php if (empty($other_menus)): ?>
        <div style="display:flex;flex-direction:column;align-items:center;padding:32px;border:2px dashed var(--border);border-radius:12px;color:var(--mut);font-size:12px;gap:8px">
          <i class="ph ph-list-bullets" style="font-size:28px;opacity:.4"></i>
          <div>Belum ada menu lainnya</div>
        </div>
      <?php else: foreach ($other_menus as $m) echo menus_card($m);
      endif; ?>
    </div>
  </div>
</div>

<!-- FLOATING SAVE ORDER -->
<button id="menusSaveBtn" onclick="menusSaveOrder()"
  style="position:fixed;bottom:28px;right:28px;z-index:1050;padding:10px 20px;border-radius:10px;background:#3b82f6;color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 20px rgba(59,130,246,.5);align-items:center;gap:8px;display:none">
  <i class="ph ph-floppy-disk"></i> Simpan Urutan
</button>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalAddMenu" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="background:var(--card) !important;border:1px solid var(--border) !important;border-radius:16px !important;box-shadow:0 25px 60px rgba(0,0,0,.6) !important">
      <div class="modal-header" style="border-bottom:1px solid var(--border) !important;padding:16px 22px;background:transparent !important">
        <h5 class="modal-title" style="font-size:15px;font-weight:700">
          <i class="ph ph-plus-circle me-2" style="color:#3b82f6"></i>Tambah Menu Baru
        </h5>
        <button type="button" class="btn-close" style="filter:invert(1);opacity:.7" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="add" />
        <div class="modal-body" style="padding:22px;background:transparent !important">
          <?php menus_form_fields('mAdd', []); ?>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--border) !important;padding:14px 22px;background:transparent !important">
          <button type="button" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px"><i class="ph ph-plus me-1"></i>Tambahkan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<?php if ($edit_data): ?>
  <div class="modal fade" id="modalEditMenu" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content" style="background:var(--card) !important;border:1px solid var(--border) !important;border-radius:16px !important;box-shadow:0 25px 60px rgba(0,0,0,.6) !important">
        <div class="modal-header" style="border-bottom:1px solid var(--border) !important;padding:16px 22px;background:transparent !important">
          <h5 class="modal-title" style="font-size:15px;font-weight:700">
            <i class="ph ph-pencil-simple me-2" style="color:#3b82f6"></i>Edit Menu
          </h5>
          <button type="button" class="btn-close" style="filter:invert(1);opacity:.7" data-bs-dismiss="modal" onclick="window.location='dashboard_menus.php'"></button>
        </div>
        <form method="POST">
          <input type="hidden" name="action" value="edit" />
          <input type="hidden" name="id" value="<?= $edit_data['id'] ?>" />
          <div class="modal-body" style="padding:22px;background:transparent !important">
            <?php menus_form_fields('mEdit', $edit_data); ?>
          </div>
          <div class="modal-footer" style="border-top:1px solid var(--border) !important;padding:14px 22px;background:transparent !important">
            <a href="dashboard_menus.php" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">Batal</a>
            <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px"><i class="ph ph-floppy-disk me-1"></i>Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- SCOPED CSS — hanya 3 hal yang tidak bisa inline: drag cursor, sortable state, save btn visibility -->
<style>
  .menus-drag-handle:active {
    cursor: grabbing !important;
  }

  #listMain>div.sortable-ghost,
  #listOther>div.sortable-ghost {
    opacity: .3 !important;
    border: 2px dashed #3b82f6 !important;
  }

  #listMain>div.sortable-drag,
  #listOther>div.sortable-drag {
    box-shadow: 0 8px 24px rgba(0, 0, 0, .4) !important;
    border-color: #3b82f6 !important;
  }

  #menusSaveBtn.menus-show {
    display: flex !important;
  }
</style>

<?php
$open_edit_js = $edit_data ? "new bootstrap.Modal(document.getElementById('modalEditMenu')).show();" : '';
$page_scripts = <<<SCRIPT
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
{$open_edit_js}

// Sortable
['listMain','listOther'].forEach(lid => {
  const el = document.getElementById(lid);
  if (!el) return;
  Sortable.create(el, {
    handle: '.menus-drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    dragClass: 'sortable-drag',
    onEnd() { document.getElementById('menusSaveBtn').classList.add('menus-show'); }
  });
});

function menusSaveOrder() {
  const ids = [
    ...document.querySelectorAll('#listMain > [data-id]'),
    ...document.querySelectorAll('#listOther > [data-id]'),
  ].map(el => el.dataset.id);
  const fd = new FormData();
  fd.append('action','reorder');
  fd.append('order', JSON.stringify(ids));
  fetch('dashboard_menus.php',{method:'POST',body:fd}).then(r=>r.json()).then(()=>{
    document.getElementById('menusSaveBtn').classList.remove('menus-show');
    menusToast('Urutan berhasil disimpan!');
  });
}

// Icon type pill
function menusIconTypeChange(mid) {
  const modalEl = document.getElementById(mid === 'mAdd' ? 'modalAddMenu' : 'modalEditMenu');
  const sel = modalEl?.querySelector('input[name="icon_type"]:checked')?.value;
  ['image_url','fontawesome','emoji'].forEach(v => {
    const lbl = document.getElementById(`\${mid}_itlbl_\${v}`);
    if (!lbl) return;
    const on = v === sel;
    lbl.style.borderColor = on ? '#3b82f6' : 'var(--border)';
    lbl.style.background  = on ? 'rgba(59,130,246,.15)' : 'var(--hover)';
    lbl.style.color       = on ? '#3b82f6' : 'var(--sub)';
  });
  const hints = {image_url:'URL lengkap gambar icon (contoh: https://img.icons8.com/fluency/96/gift.png)',fontawesome:'Class FontAwesome (contoh: fas fa-home)',emoji:'Satu karakter emoji (contoh: 🎁)'};
  const phs   = {image_url:'https://img.icons8.com/fluency/96/…',fontawesome:'fas fa-home',emoji:'🎁'};
  const h = document.getElementById(`\${mid}_iconHint`);
  const v = document.getElementById(`\${mid}_iconVal`);
  if (h) h.textContent = hints[sel]||'';
  if (v) v.placeholder = phs[sel]||'';
  menusUpdatePreview(mid);
}

// Live preview
function menusUpdatePreview(mid) {
  const modalEl = document.getElementById(mid==='mAdd'?'modalAddMenu':'modalEditMenu');
  if (!modalEl) return;
  const it  = modalEl.querySelector('input[name="icon_type"]:checked')?.value||'image_url';
  const iv  = document.getElementById(`\${mid}_iconVal`)?.value||'';
  const bg  = document.getElementById(`\${mid}_bgColor`)?.value||'#f0fdf4';
  const fg  = document.getElementById(`\${mid}_fgColor`)?.value||'#01d298';
  const nm  = modalEl.querySelector('input[name="name"]')?.value||'Nama Menu';
  const ie  = document.getElementById(`\${mid}_prevIcon`);
  const ne  = document.getElementById(`\${mid}_prevName`);
  if (!ie||!ne) return;
  ie.style.background = bg;
  ne.textContent = nm;
  let inner = `<i class="ph ph-image-square" style="color:#94a3b8;font-size:22px"></i>`;
  if (it==='image_url'&&iv)   inner=`<img src="\${iv}" style="width:30px;height:30px;object-fit:contain" onerror="this.style.display='none'"/>`;
  else if(it==='fontawesome'&&iv) inner=`<i class="\${iv}" style="color:\${fg};font-size:22px"></i>`;
  else if(it==='emoji'&&iv)   inner=`<span style="font-size:26px;line-height:1">\${iv}</span>`;
  ie.innerHTML = inner;
}

// Name → preview live
document.querySelectorAll('.modal input[name="name"]').forEach(inp=>{
  const mid = inp.closest('.modal')?.id==='modalAddMenu'?'mAdd':'mEdit';
  inp.addEventListener('input',()=>menusUpdatePreview(mid));
});

// Palette
function menusSetPalette(mid,which,color) {
  document.getElementById(`\${mid}_\${which}Color`).value = color;
  document.getElementById(`\${mid}_\${which}Hex`).value   = color;
  menusUpdatePreview(mid);
}

// Toggle switch
function menusSw(input) {
  const track = input.nextElementSibling;
  const dot   = track.querySelector('span');
  track.style.background = input.checked ? '#3b82f6' : 'rgba(255,255,255,.12)';
  dot.style.left         = input.checked ? '19px' : '3px';
}

// Toast
function menusToast(msg) {
  const wrap = document.querySelector('.toast-wrap');
  if (!wrap) return;
  const t = document.createElement('div');
  t.className = 'toast-item toast-ok';
  t.innerHTML = `<i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i>\${msg}`;
  wrap.appendChild(t);
  setTimeout(()=>t.style.opacity='0',3000);
  setTimeout(()=>t.remove(),3500);
}

document.querySelectorAll('.toast-item').forEach(t=>{
  setTimeout(()=>t.style.opacity='0',3500);
  setTimeout(()=>t.remove(),4000);
});
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>