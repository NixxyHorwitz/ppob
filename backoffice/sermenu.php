<?php
// backoffice/sermenu.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Service Menus';
$active_menu = 'service_menus';

$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

// ══════════════════════════════════════════════════════════════
//  POST ACTIONS
// ══════════════════════════════════════════════════════════════

// ── Reorder AJAX ──────────────────────────────────────────────
if ($action === 'reorder') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (is_array($ids)) {
        $st = $pdo->prepare("UPDATE service_menus SET sort_order=? WHERE id=?");
        foreach ($ids as $i => $id) $st->execute([($i + 1) * 10, (int)$id]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ── Toggle AJAX ───────────────────────────────────────────────
if ($action === 'toggle') {
    $id  = (int)$_POST['id'];
    $cur = (int)$pdo->query("SELECT is_active FROM service_menus WHERE id=$id")->fetchColumn();
    $new = $cur ? 0 : 1;
    $pdo->prepare("UPDATE service_menus SET is_active=? WHERE id=?")->execute([$new, $id]);
    echo json_encode(['ok' => true, 'is_active' => $new]);
    exit;
}

// ── Delete AJAX ───────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $rt = $pdo->query("SELECT row_type FROM service_menus WHERE id=$id")->fetchColumn();
    if ($rt === 'category') $pdo->prepare("DELETE FROM service_menus WHERE category_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM service_menus WHERE id=?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Move item AJAX ────────────────────────────────────────────
if ($action === 'move_item') {
    $pdo->prepare("UPDATE service_menus SET category_id=? WHERE id=? AND row_type='item'")
        ->execute([(int)$_POST['category_id'], (int)$_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Save Kategori (form POST → redirect) ─────────────────────
if ($action === 'save_cat') {
    $id       = (int)($_POST['id']         ?? 0);
    $cat_slug = trim($_POST['cat_slug']    ?? '');
    $cat_name = trim($_POST['cat_name']    ?? '');
    $sort     = (int)($_POST['sort_order'] ?? 10);
    $active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$cat_name || !$cat_slug) {
        $toast_e = 'Nama dan slug wajib diisi.';
    } elseif ($id) {
        $pdo->prepare("UPDATE service_menus SET cat_slug=?,cat_name=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$cat_slug, $cat_name, $sort, $active, $id]);
        header('Location: sermenu.php?saved=1&msg=' . urlencode("Kategori \"$cat_name\" diperbarui."));
        exit;
    } else {
        if (!$sort) $sort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+10 FROM service_menus WHERE row_type='category'")->fetchColumn();
        $pdo->prepare("INSERT INTO service_menus (row_type,cat_slug,cat_name,sort_order,is_active) VALUES ('category',?,?,?,?)")
            ->execute([$cat_slug, $cat_name, $sort, $active]);
        header('Location: sermenu.php?saved=1&msg=' . urlencode("Kategori \"$cat_name\" ditambahkan."));
        exit;
    }
}

// ── Save Item (form POST → redirect) ─────────────────────────
if ($action === 'save_item') {
    $id          = (int)($_POST['id']          ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name        = trim($_POST['name']         ?? '');
    $icon_type   = in_array($_POST['icon_type'] ?? '', ['ph', 'fa', 'img']) ? $_POST['icon_type'] : 'ph';
    $icon_value  = trim($_POST['icon_value']   ?? '');
    $icon_bg     = trim($_POST['icon_bg']      ?? '#e0f2fe');
    $icon_color  = trim($_POST['icon_color']   ?? '#0ea5e9');
    $href        = trim($_POST['href']         ?? '#');
    $query_cat   = trim($_POST['query_cat']    ?? '') ?: null;
    $badge       = trim($_POST['badge']        ?? '') ?: null;
    $badge_color = trim($_POST['badge_color']  ?? '#ef4444');
    $sort        = (int)($_POST['sort_order']  ?? 0);
    $active      = isset($_POST['is_active'])   ? 1 : 0;

    if (!$name || !$category_id) {
        $toast_e = 'Nama item dan kategori wajib diisi.';
    } elseif ($id) {
        $pdo->prepare("UPDATE service_menus SET category_id=?,name=?,icon_type=?,icon_value=?,icon_bg=?,icon_color=?,href=?,query_cat=?,badge=?,badge_color=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$category_id, $name, $icon_type, $icon_value, $icon_bg, $icon_color, $href, $query_cat, $badge, $badge_color, $sort, $active, $id]);
        header('Location: sermenu.php?saved=1&msg=' . urlencode("Item \"$name\" diperbarui."));
        exit;
    } else {
        if (!$sort) $sort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM service_menus WHERE category_id=$category_id")->fetchColumn();
        $pdo->prepare("INSERT INTO service_menus (row_type,category_id,name,icon_type,icon_value,icon_bg,icon_color,href,query_cat,badge,badge_color,sort_order,is_active) VALUES ('item',?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$category_id, $name, $icon_type, $icon_value, $icon_bg, $icon_color, $href, $query_cat, $badge, $badge_color, $sort, $active]);
        header('Location: sermenu.php?saved=1&msg=' . urlencode("Item \"$name\" ditambahkan."));
        exit;
    }
}

// ══════════════════════════════════════════════════════════════
//  FETCH DATA
// ══════════════════════════════════════════════════════════════
$all      = $pdo->query("SELECT * FROM service_menus ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$cats     = array_values(array_filter($all, fn($r) => $r['row_type'] === 'category'));
$items_all = array_filter($all, fn($r) => $r['row_type'] === 'item');

$items_by_cat = [];
foreach ($items_all as $item) $items_by_cat[(int)$item['category_id']][] = $item;

// ── ?edit=ID ──────────────────────────────────────────────────
$edit_id  = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id) foreach ($all as $r) {
    if ((int)$r['id'] === $edit_id) {
        $edit_row = $r;
        break;
    }
}

// ── ?new=cat|item ─────────────────────────────────────────────
$new_mode   = $_GET['new'] ?? '';
$new_cat_id = (int)($_GET['cat'] ?? ($cats[0]['id'] ?? 0));

// ── ?saved=1&msg= ─────────────────────────────────────────────
if (!empty($_GET['saved'])) $toast = urldecode($_GET['msg'] ?? 'Berhasil disimpan.');

// ── Stats ─────────────────────────────────────────────────────
$total_cats   = count($cats);
$total_items  = count($items_all);
$active_items = count(array_filter($items_all, fn($r) => $r['is_active']));

// ══════════════════════════════════════════════════════════════
//  HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════
function smlbl(string $t): string
{
    return "<label style=\"font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);display:block;margin-bottom:6px\">$t</label>";
}
function sminp(string $name, string $val = '', string $ph = ''): string
{
    return "<input type=\"text\" name=\"$name\" class=\"fi w-100\" style=\"padding-left:13px\" value=\"" . htmlspecialchars($val) . "\" placeholder=\"" . htmlspecialchars($ph) . "\"/>";
}
function smsw(string $name, bool $on, string $uid): string
{
    $bg  = $on ? '#3b82f6' : 'rgba(255,255,255,.12)';
    $lft = $on ? '21px' : '3px';
    return "<label class=\"sm-sw\" for=\"sw_$uid\">
      <input type=\"checkbox\" name=\"$name\" id=\"sw_$uid\" " . ($on ? 'checked' : '') . "
             style=\"position:absolute;opacity:0;width:0;height:0\" onchange=\"smSw(this)\"/>
      <span class=\"sm-sw-track\" style=\"background:$bg\">
        <span class=\"sm-sw-dot\" style=\"left:$lft\"></span>
      </span>
    </label>";
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
        <h1 style="display:flex;align-items:center;gap:10px">
            <span style="width:34px;height:34px;border-radius:9px;background:var(--as);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="ph ph-squares-four" style="font-size:18px;color:var(--accent)"></i>
            </span>
            Service Menus
        </h1>
        <nav>
            <ol class="breadcrumb bc">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Service Menus</li>
            </ol>
        </nav>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="sermenu.php?new=cat"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;background:var(--hover);border:1px solid var(--border);color:var(--sub);font-size:12px;font-weight:600;text-decoration:none">
            <i class="ph ph-folder-plus"></i>Tambah Kategori
        </a>
        <a href="sermenu.php?new=item<?= $cats ? '&cat=' . $cats[0]['id'] : '' ?>"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;background:var(--accent);color:#fff;font-size:12px;font-weight:700;text-decoration:none">
            <i class="ph ph-plus"></i>Tambah Item
        </a>
    </div>
</div>

<!-- STAT BAR -->
<div style="display:flex;background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:24px">
    <?php foreach ([[$total_cats, 'Kategori', ''], [$total_items, 'Total Item', ''], [$active_items, 'Aktif', 'color:var(--ok)'], [$total_items - $active_items, 'Nonaktif', 'color:var(--mut)']] as $si => [$sv, $sl, $sc]): ?>
        <?php if ($si): ?><div style="width:1px;background:var(--border)"></div><?php endif; ?>
        <div style="flex:1;padding:14px;text-align:center">
            <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace;<?= $sc ?>"><?= $sv ?></div>
            <div style="font-size:10px;font-weight:700;color:var(--mut);text-transform:uppercase;letter-spacing:.5px;margin-top:3px"><?= $sl ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- MAIN 2 COL -->
<div class="row g-4 align-items-start">

    <!-- ══════════════════ KIRI: panel + struktur ══════════════════ -->
    <div class="col-lg-7">

        <?php
        $show_panel   = ($edit_row !== null) || $new_mode !== '';
        $panel_is_cat = ($edit_row && $edit_row['row_type'] === 'category') || $new_mode === 'cat';
        $panel_is_item = ($edit_row && $edit_row['row_type'] === 'item')     || $new_mode === 'item';
        $d = $edit_row ?? [];
        ?>

        <?php if ($show_panel): ?>
            <!-- ══ EDIT / ADD PANEL ══ -->
            <div style="background:var(--card);border:1px solid var(--ba);border-radius:var(--r);margin-bottom:20px;overflow:hidden">

                <!-- Panel header -->
                <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 18px;background:var(--as);border-bottom:1px solid var(--ba)">
                    <div style="display:flex;align-items:center;gap:8px">
                        <i class="ph <?= $edit_row ? 'ph-pencil-simple' : 'ph-plus-circle' ?>" style="font-size:16px;color:var(--accent)"></i>
                        <span style="font-size:14px;font-weight:700">
                            <?php if ($edit_row): ?>Edit <?= $panel_is_cat ? 'Kategori' : 'Item' ?>:
                            <em style="font-weight:500"><?= htmlspecialchars($edit_row['cat_name'] ?? $edit_row['name'] ?? '') ?></em>
                            <?php else: ?>Tambah <?= $new_mode === 'cat' ? 'Kategori' : 'Item' ?> Baru<?php endif; ?>
                        </span>
                    </div>
                    <a href="sermenu.php" style="color:var(--mut);font-size:18px;line-height:1;text-decoration:none" title="Tutup">
                        <i class="ph ph-x"></i>
                    </a>
                </div>

                <div style="padding:20px">

                    <!-- ════ FORM KATEGORI ════ -->
                    <?php if ($panel_is_cat): ?>
                        <form method="POST" action="sermenu.php">
                            <input type="hidden" name="action" value="save_cat" />
                            <input type="hidden" name="id" value="<?= (int)($d['id'] ?? 0) ?>" />
                            <div class="row g-3">
                                <div class="col-md-7" style="display:flex;flex-direction:column;gap:14px">
                                    <div>
                                        <?= smlbl('Nama Kategori *') ?>
                                        <?= sminp('cat_name', $d['cat_name'] ?? '', 'Top Up & Digital, Tagihan…') ?>
                                    </div>
                                    <div>
                                        <?= smlbl('Slug *') ?>
                                        <input type="text" name="cat_slug" id="catSlug" class="fi w-100"
                                            style="padding-left:13px;font-family:'JetBrains Mono',monospace;font-size:12px"
                                            value="<?= htmlspecialchars($d['cat_slug'] ?? '') ?>"
                                            placeholder="topup, bills…" />
                                        <div style="font-size:11px;color:var(--mut);margin-top:4px">Lowercase, tanpa spasi, unik</div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <?= smlbl('Sort Order') ?>
                                            <input type="number" name="sort_order" class="fi w-100" style="padding-left:13px"
                                                value="<?= (int)($d['sort_order'] ?? 10) ?>" min="0" step="10" />
                                        </div>
                                        <div class="col-6">
                                            <?= smlbl('Status') ?>
                                            <div style="display:flex;align-items:center;gap:10px;margin-top:4px">
                                                <?= smsw('is_active', (bool)($d['is_active'] ?? true), 'cat') ?>
                                                <span style="font-size:12px;color:var(--sub)">Aktif</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div style="background:var(--hover);border:1px solid var(--border);border-radius:9px;padding:14px;font-size:11px;color:var(--mut);line-height:1.7">
                                        <div style="font-weight:700;color:var(--sub);margin-bottom:6px;display:flex;align-items:center;gap:6px">
                                            <i class="ph ph-folder-open" style="color:var(--accent)"></i>Tentang Kategori
                                        </div>
                                        Kategori mengelompokkan item layanan. Slug dipakai sebagai identifier unik.
                                        Items dalam kategori nonaktif tidak tampil ke user.
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px;margin-top:18px;padding-top:16px;border-top:1px solid var(--border)">
                                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:7px;padding:8px 20px">
                                    <i class="ph ph-floppy-disk me-1"></i><?= $edit_row ? 'Perbarui' : 'Simpan' ?> Kategori
                                </button>
                                <a href="sermenu.php" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">Batal</a>
                            </div>
                        </form>

                        <!-- ════ FORM ITEM ════ -->
                    <?php elseif ($panel_is_item):
                        $itype = $d['icon_type'] ?? 'ph';
                        $ibg   = $d['icon_bg']    ?? '#dbeafe';
                        $iclr  = $d['icon_color'] ?? '#3b82f6';
                        $ival  = $d['icon_value'] ?? '';
                    ?>
                        <form method="POST" action="sermenu.php">
                            <input type="hidden" name="action" value="save_item" />
                            <input type="hidden" name="id" value="<?= (int)($d['id'] ?? 0) ?>" />
                            <div class="row g-3">

                                <!-- kiri: fields -->
                                <div class="col-md-7" style="display:flex;flex-direction:column;gap:14px">

                                    <div class="row g-2">
                                        <div class="col-7">
                                            <?= smlbl('Nama Item *') ?>
                                            <input type="text" name="name" id="itemName" class="fi w-100" style="padding-left:13px"
                                                value="<?= htmlspecialchars($d['name'] ?? '') ?>" placeholder="Pulsa, BPJS…"
                                                oninput="smPrevName(this.value)" />
                                        </div>
                                        <div class="col-5">
                                            <?= smlbl('Kategori *') ?>
                                            <select name="category_id" class="fs w-100" style="padding:8px 12px">
                                                <?php foreach ($cats as $c): ?>
                                                    <option value="<?= $c['id'] ?>" <?= ((int)($d['category_id'] ?? $new_cat_id)) === (int)$c['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($c['cat_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <?= smlbl('Tipe Icon *') ?>
                                        <div style="display:flex;gap:6px">
                                            <?php foreach (['ph' => ['ph-star', 'Phosphor'], 'fa' => ['ph-flag', 'Font Awesome'], 'img' => ['ph-image', 'URL Gambar']] as $tv => [$tic, $tlb]): ?>
                                                <label class="sm-itype-pill <?= $itype === $tv ? 'active' : '' ?>" for="it_<?= $tv ?>">
                                                    <input type="radio" name="icon_type" value="<?= $tv ?>" id="it_<?= $tv ?>" <?= $itype === $tv ? 'checked' : '' ?> class="d-none" onchange="smIconTypeChange()" />
                                                    <i class="ph <?= $tic ?>"></i><?= $tlb ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div>
                                        <?= smlbl('Nilai Icon *') ?>
                                        <input type="text" name="icon_value" id="iconVal" class="fi w-100" style="padding-left:13px"
                                            value="<?= htmlspecialchars($ival) ?>"
                                            placeholder="<?= $itype === 'ph' ? 'ph-star' : ($itype === 'fa' ? 'fas fa-home' : 'https://…') ?>"
                                            oninput="smPrev()" />
                                        <div id="iconHint" style="font-size:11px;color:var(--mut);margin-top:4px">
                                            <?= match ($itype) {
                                                'fa'  => 'Class Font Awesome, contoh: <code style="background:var(--hover);padding:0 4px;border-radius:3px">fas fa-home</code>',
                                                'img' => 'URL lengkap gambar JPG/PNG/SVG',
                                                default => 'Nama class Phosphor — cari di <a href="https://phosphoricons.com" target="_blank" style="color:var(--accent)">phosphoricons.com</a>',
                                            } ?>
                                        </div>
                                    </div>

                                    <!-- Warna -->
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <?= smlbl('Background') ?>
                                            <div style="display:flex;gap:6px;align-items:center;margin-bottom:5px">
                                                <input type="color" name="icon_bg" id="bgPick" value="<?= htmlspecialchars($ibg) ?>"
                                                    style="width:32px;height:32px;border-radius:7px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer;flex-shrink:0"
                                                    oninput="bgHex.value=this.value;smPrev()" />
                                                <input type="text" id="bgHex" value="<?= htmlspecialchars($ibg) ?>" maxlength="7"
                                                    style="font-family:'JetBrains Mono',monospace;font-size:11px;width:78px;padding:6px 8px;background:var(--hover);border:1px solid var(--border);border-radius:7px;color:var(--text)"
                                                    oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){bgPick.value=this.value;smPrev()}" />
                                            </div>
                                            <div style="display:flex;gap:3px;flex-wrap:wrap">
                                                <?php foreach (['#dbeafe', '#ede9fe', '#fce7f3', '#fef9c3', '#dcfce7', '#ffedd5', '#cffafe', '#f0fdf4', '#fef3c7', '#f1f5f9'] as $sw): ?>
                                                    <button type="button" class="sm-swatch" style="background:<?= $sw ?>"
                                                        onclick="bgPick.value='<?= $sw ?>';bgHex.value='<?= $sw ?>';smPrev()"></button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <?= smlbl('Warna Icon') ?>
                                            <div style="display:flex;gap:6px;align-items:center;margin-bottom:5px">
                                                <input type="color" name="icon_color" id="fgPick" value="<?= htmlspecialchars($iclr) ?>"
                                                    style="width:32px;height:32px;border-radius:7px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer;flex-shrink:0"
                                                    oninput="fgHex.value=this.value;smPrev()" />
                                                <input type="text" id="fgHex" value="<?= htmlspecialchars($iclr) ?>" maxlength="7"
                                                    style="font-family:'JetBrains Mono',monospace;font-size:11px;width:78px;padding:6px 8px;background:var(--hover);border:1px solid var(--border);border-radius:7px;color:var(--text)"
                                                    oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){fgPick.value=this.value;smPrev()}" />
                                            </div>
                                            <div style="display:flex;gap:3px;flex-wrap:wrap">
                                                <?php foreach (['#3b82f6', '#7c3aed', '#db2777', '#ca8a04', '#16a34a', '#ea580c', '#0891b2', '#0ea5e9', '#64748b', '#d97706'] as $sw): ?>
                                                    <button type="button" class="sm-swatch" style="background:<?= $sw ?>"
                                                        onclick="fgPick.value='<?= $sw ?>';fgHex.value='<?= $sw ?>';smPrev()"></button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-7">
                                            <?= smlbl('URL / href') ?>
                                            <?= sminp('href', $d['href'] ?? '#', '/pages/prabayar.php') ?>
                                        </div>
                                        <div class="col-5">
                                            <?= smlbl('Query Cat') ?>
                                            <?= sminp('query_cat', $d['query_cat'] ?? '', 'Pulsa, PLN…') ?>
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-5">
                                            <?= smlbl('Badge <span style="font-weight:400;color:var(--mut)">(opsional)</span>') ?>
                                            <input type="text" name="badge" id="badgeVal" class="fi w-100" style="padding-left:13px"
                                                value="<?= htmlspecialchars($d['badge'] ?? '') ?>" placeholder="NEW, HOT…" maxlength="10"
                                                oninput="smPrev()" />
                                        </div>
                                        <div class="col-4">
                                            <?= smlbl('Warna Badge') ?>
                                            <input type="color" name="badge_color" id="badgeColor" value="<?= htmlspecialchars($d['badge_color'] ?? '#ef4444') ?>"
                                                style="width:100%;height:34px;border-radius:7px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer"
                                                oninput="smPrev()" />
                                        </div>
                                        <div class="col-3">
                                            <?= smlbl('Sort') ?>
                                            <input type="number" name="sort_order" class="fi w-100" style="padding-left:10px"
                                                value="<?= (int)($d['sort_order'] ?? 0) ?>" min="0" />
                                        </div>
                                    </div>

                                    <div style="display:flex;align-items:center;gap:10px">
                                        <?= smsw('is_active', (bool)($d['is_active'] ?? true), 'item') ?>
                                        <span style="font-size:12px;color:var(--sub);font-weight:600">Aktifkan item ini</span>
                                    </div>
                                </div><!-- /kiri -->

                                <!-- kanan: preview -->
                                <div class="col-md-5">
                                    <?= smlbl('Preview') ?>
                                    <div style="background:var(--hover);border:1px solid var(--border);border-radius:12px;padding:28px 16px;text-align:center;margin-bottom:12px">
                                        <div style="display:inline-flex;flex-direction:column;align-items:center;gap:8px">
                                            <div id="prevBox" style="width:58px;height:58px;border-radius:16px;display:flex;align-items:center;justify-content:center;position:relative;background:<?= htmlspecialchars($ibg) ?>">
                                                <?php
                                                if ($ival) {
                                                    if ($itype === 'ph')  echo "<i class=\"ph " . htmlspecialchars($ival) . "\" style=\"font-size:26px;color:" . htmlspecialchars($iclr) . "\"></i>";
                                                    elseif ($itype === 'fa') echo "<i class=\"" . htmlspecialchars($ival) . "\" style=\"font-size:22px;color:" . htmlspecialchars($iclr) . "\"></i>";
                                                    elseif ($itype === 'img') echo "<img src=\"" . htmlspecialchars($ival) . "\" style=\"width:32px;height:32px;object-fit:contain\"/>";
                                                } else echo "<i class=\"ph ph-image-square\" style=\"font-size:28px;color:#94a3b8\"></i>";
                                                ?>
                                                <div id="prevBadge" style="position:absolute;top:-5px;right:-5px;color:#fff;font-size:7px;font-weight:800;padding:1px 5px;border-radius:99px;line-height:1.5;<?= empty($d['badge']) ? 'display:none' : 'background:' . htmlspecialchars($d['badge_color'] ?? '#ef4444') ?>">
                                                    <?= htmlspecialchars($d['badge'] ?? '') ?>
                                                </div>
                                            </div>
                                            <div id="prevName" style="font-size:11px;font-weight:700;color:var(--sub);max-width:64px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                                <?= htmlspecialchars($d['name'] ?? 'Nama Item') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="https://phosphoricons.com" target="_blank"
                                        style="display:flex;align-items:center;gap:8px;background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15);border-radius:8px;padding:10px 12px;text-decoration:none;color:var(--sub);font-size:11px;line-height:1.5">
                                        <i class="ph ph-arrow-square-out" style="font-size:15px;color:var(--accent);flex-shrink:0"></i>
                                        <span><strong style="color:var(--accent)">phosphoricons.com</strong><br>
                                            Cari icon → copy nama class, contoh: <code style="background:var(--hover);padding:0 4px;border-radius:3px">ph-lightning</code></span>
                                    </a>
                                </div>

                            </div>
                            <div style="display:flex;gap:8px;margin-top:18px;padding-top:16px;border-top:1px solid var(--border)">
                                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:7px;padding:8px 20px">
                                    <i class="ph ph-floppy-disk me-1"></i><?= $edit_row ? 'Perbarui' : 'Simpan' ?> Item
                                </button>
                                <a href="sermenu.php" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">Batal</a>
                            </div>
                        </form>
                    <?php endif; ?>

                </div><!-- /panel body -->
            </div><!-- /panel -->
        <?php endif; ?>

        <!-- ══ STRUKTUR MENU ══ -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <div style="display:flex;align-items:center;gap:8px">
                <i class="ph ph-tree-structure" style="color:var(--accent)"></i>
                <span style="font-size:14px;font-weight:700">Struktur Menu</span>
                <span style="font-size:11px;color:var(--mut)">— drag reorder</span>
            </div>
            <button id="saveOrderBtn" onclick="smSaveOrder()"
                style="display:none;align-items:center;gap:6px;padding:6px 14px;border-radius:7px;background:var(--ok);border:none;color:#fff;font-size:12px;font-weight:700;cursor:pointer">
                <i class="ph ph-floppy-disk"></i> Simpan Urutan
            </button>
        </div>

        <div id="catList" style="display:flex;flex-direction:column;gap:10px">
            <?php if (empty($cats)): ?>
                <div style="border:2px dashed var(--border);border-radius:var(--r);padding:48px;text-align:center;color:var(--mut)">
                    <i class="ph ph-folder-open" style="font-size:36px;display:block;margin-bottom:10px;opacity:.25"></i>
                    Belum ada kategori. <a href="?new=cat" style="color:var(--accent)">Tambah kategori</a> untuk memulai.
                </div>
            <?php endif; ?>

            <?php foreach ($cats as $cat):
                $citems = $items_by_cat[(int)$cat['id']] ?? [];
                $hi_cat = $edit_id && $edit_row && (int)$edit_row['id'] === (int)$cat['id'];
            ?>
                <div class="sm-cat-block" data-id="<?= $cat['id'] ?>"
                    style="background:var(--card);border:1px solid <?= $hi_cat ? 'var(--ba)' : 'var(--border)' ?>;border-radius:var(--r);overflow:hidden">

                    <div style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:<?= $hi_cat ? 'var(--as)' : 'rgba(255,255,255,.02)' ?>">
                        <div class="sm-drag-cat" style="cursor:grab;color:var(--mut);font-size:18px;flex-shrink:0;line-height:1">
                            <i class="ph ph-dots-six-vertical"></i>
                        </div>
                        <div style="width:30px;height:30px;border-radius:7px;background:var(--as);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="ph ph-folder-open" style="font-size:15px;color:var(--accent)"></i>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                                <span style="font-size:13px;font-weight:700"><?= htmlspecialchars($cat['cat_name']) ?></span>
                                <code style="font-size:10px;background:var(--hover);color:var(--mut);padding:1px 6px;border-radius:4px"><?= htmlspecialchars($cat['cat_slug']) ?></code>
                                <?php if (!$cat['is_active']): ?><span style="font-size:9px;font-weight:800;padding:1px 7px;border-radius:99px;background:rgba(239,68,68,.1);color:var(--err)">NONAKTIF</span><?php endif; ?>
                            </div>
                            <div style="font-size:10px;color:var(--mut);margin-top:2px"><?= count($citems) ?> item · sort #<?= $cat['sort_order'] ?></div>
                        </div>
                        <div style="display:flex;gap:3px;flex-shrink:0">
                            <a href="sermenu.php?edit=<?= $cat['id'] ?>" class="ab" title="Edit" style="<?= $hi_cat ? 'color:var(--accent)' : '' ?>">
                                <i class="ph ph-pencil-simple"></i>
                            </a>
                            <a href="sermenu.php?new=item&cat=<?= $cat['id'] ?>" class="ab" style="color:var(--ok)" title="Tambah item">
                                <i class="ph ph-plus-circle"></i>
                            </a>
                            <button class="ab red" onclick="smDeleteCat(<?= $cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['cat_name'])) ?>')" title="Hapus">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="sm-item-list" data-cat-id="<?= $cat['id'] ?>" style="min-height:8px">
                        <?php foreach ($citems as $item):
                            $hi_item = $edit_id && $edit_row && (int)$edit_row['id'] === (int)$item['id'];
                            $ibg  = htmlspecialchars($item['icon_bg']    ?? '#e0f2fe');
                            $iclr = htmlspecialchars($item['icon_color'] ?? '#0ea5e9');
                            $ival = htmlspecialchars($item['icon_value'] ?? '');
                        ?>
                            <div class="sm-item-row" data-id="<?= $item['id'] ?>" data-cat="<?= $item['category_id'] ?>"
                                style="display:flex;align-items:center;gap:10px;padding:8px 14px;border-top:1px solid rgba(255,255,255,.04);transition:background .15s;<?= $item['is_active'] ? '' : 'opacity:.42;' ?><?= $hi_item ? 'background:var(--as);border-left:3px solid var(--accent);' : '' ?>">

                                <div class="sm-drag-item" style="cursor:grab;color:var(--mut);font-size:15px;flex-shrink:0;line-height:1">
                                    <i class="ph ph-dots-six-vertical"></i>
                                </div>
                                <div style="width:34px;height:34px;border-radius:9px;background:<?= $ibg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative">
                                    <?php if ($item['icon_type'] === 'ph'): ?>
                                        <i class="ph <?= $ival ?>" style="font-size:17px;color:<?= $iclr ?>"></i>
                                    <?php elseif ($item['icon_type'] === 'fa'): ?>
                                        <i class="<?= $ival ?>" style="font-size:15px;color:<?= $iclr ?>"></i>
                                    <?php elseif ($item['icon_type'] === 'img'): ?>
                                        <img src="<?= $ival ?>" style="width:22px;height:22px;object-fit:contain" onerror="this.style.display='none'" />
                                    <?php endif; ?>
                                    <?php if ($item['badge']): ?>
                                        <div style="position:absolute;top:-4px;right:-4px;background:<?= htmlspecialchars($item['badge_color']) ?>;color:#fff;font-size:7px;font-weight:800;padding:1px 4px;border-radius:99px">
                                            <?= htmlspecialchars($item['badge']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </div>
                                    <div style="font-size:10px;color:var(--mut);font-family:'JetBrains Mono',monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px">
                                        <?= htmlspecialchars($item['href'] ?? '#') ?><?= $item['query_cat'] ? '?cat=' . htmlspecialchars($item['query_cat']) : '' ?>
                                    </div>
                                </div>
                                <span style="font-size:9px;font-weight:700;padding:2px 6px;border-radius:99px;background:var(--hover);color:var(--mut);flex-shrink:0;text-transform:uppercase"><?= $item['icon_type'] ?></span>
                                <div style="display:flex;gap:3px;flex-shrink:0">
                                    <button class="ab sm-tog" onclick="smToggle(this,<?= $item['id'] ?>)" title="Toggle"
                                        style="<?= $item['is_active'] ? 'color:var(--ok)' : '' ?>">
                                        <i class="ph <?= $item['is_active'] ? 'ph-eye' : 'ph-eye-slash' ?>"></i>
                                    </button>
                                    <a href="sermenu.php?edit=<?= $item['id'] ?>" class="ab" title="Edit" style="<?= $hi_item ? 'color:var(--accent)' : '' ?>">
                                        <i class="ph ph-pencil-simple"></i>
                                    </a>
                                    <button class="ab red" onclick="smDeleteItem(<?= $item['id'] ?>,'<?= htmlspecialchars(addslashes($item['name'])) ?>')" title="Hapus">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="padding:7px 14px">
                            <a href="sermenu.php?new=item&cat=<?= $cat['id'] ?>"
                                style="display:flex;align-items:center;justify-content:center;gap:5px;width:100%;background:transparent;border:1px dashed rgba(255,255,255,.07);border-radius:7px;padding:6px;font-size:11px;color:var(--mut);text-decoration:none;transition:all .15s"
                                onmouseover="this.style.borderColor='var(--ba)';this.style.color='var(--accent)'"
                                onmouseout="this.style.borderColor='rgba(255,255,255,.07)';this.style.color='var(--mut)'">
                                <i class="ph ph-plus" style="font-size:12px"></i>
                                Tambah item ke <strong style="margin-left:2px"><?= htmlspecialchars($cat['cat_name']) ?></strong>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div><!-- /catList -->

    </div><!-- /col kiri -->

    <!-- ══════════════════ KANAN: preview ══════════════════════════ -->
    <div class="col-lg-5">
        <div style="position:sticky;top:calc(var(--hh)+20px)">

            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                <i class="ph ph-device-mobile" style="color:var(--accent)"></i>
                <span style="font-size:14px;font-weight:700">Preview Aplikasi</span>
            </div>

            <div style="max-width:320px;margin:0 auto;background:var(--card);border:1px solid var(--border);border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.3)">
                <div style="background:var(--surface);padding:10px 16px 8px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)">
                    <span style="font-size:10px;font-weight:700;color:var(--mut)">Layanan</span>
                    <div style="display:flex;gap:4px;color:var(--mut)">
                        <i class="ph ph-wifi-high" style="font-size:11px"></i>
                        <i class="ph ph-battery-full" style="font-size:11px"></i>
                    </div>
                </div>
                <div style="padding:10px;max-height:480px;overflow-y:auto;scrollbar-width:none">
                    <?php foreach ($cats as $cat):
                        $ci = array_filter($items_by_cat[(int)$cat['id']] ?? [], fn($i) => $i['is_active']);
                        if (!$cat['is_active'] || empty($ci)) continue;
                    ?>
                        <div style="margin-bottom:14px">
                            <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--mut);margin-bottom:7px;padding:0 2px">
                                <?= htmlspecialchars($cat['cat_name']) ?>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:5px">
                                <?php foreach ($ci as $pi):
                                    $pibg  = htmlspecialchars($pi['icon_bg']    ?? '#e0f2fe');
                                    $piclr = htmlspecialchars($pi['icon_color'] ?? '#0ea5e9');
                                    $pival = htmlspecialchars($pi['icon_value'] ?? '');
                                ?>
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                                        <div style="width:46px;height:46px;border-radius:12px;background:<?= $pibg ?>;display:flex;align-items:center;justify-content:center;position:relative">
                                            <?php if ($pi['icon_type'] === 'ph'): ?>
                                                <i class="ph <?= $pival ?>" style="font-size:21px;color:<?= $piclr ?>"></i>
                                            <?php elseif ($pi['icon_type'] === 'fa'): ?>
                                                <i class="<?= $pival ?>" style="font-size:17px;color:<?= $piclr ?>"></i>
                                            <?php elseif ($pi['icon_type'] === 'img'): ?>
                                                <img src="<?= $pival ?>" style="width:26px;height:26px;object-fit:contain" />
                                            <?php endif; ?>
                                            <?php if ($pi['badge']): ?>
                                                <div style="position:absolute;top:-4px;right:-4px;background:<?= htmlspecialchars($pi['badge_color']) ?>;color:#fff;font-size:7px;font-weight:800;padding:1px 4px;border-radius:99px">
                                                    <?= htmlspecialchars($pi['badge']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:9px;font-weight:600;color:var(--sub);text-align:center;max-width:50px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            <?= htmlspecialchars($pi['name']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($cats)): ?>
                        <div style="text-align:center;padding:32px;color:var(--mut);font-size:11px">
                            <i class="ph ph-image" style="font-size:28px;display:block;margin-bottom:8px;opacity:.2"></i>Preview akan tampil di sini
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Icon ref -->
            <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;margin-top:14px;max-width:320px;margin-left:auto;margin-right:auto">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--mut);margin-bottom:10px">
                    <i class="ph ph-book-open me-1"></i>Referensi Icon
                </div>
                <?php foreach ([['ph', 'ph-star', 'Phosphor', 'phosphoricons.com'], ['fa', 'ph-flag', 'Font Awesome', 'fas fa-home'], ['img', 'ph-image', 'URL Gambar', 'https://…/icon.png']] as [$rt, $ric, $rlb, $rex]): ?>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:7px;font-size:11px">
                        <div style="width:24px;height:24px;border-radius:5px;background:var(--hover);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="ph <?= $ric ?>" style="font-size:13px;color:var(--mut)"></i>
                        </div>
                        <span><strong><?= $rt ?></strong> <span style="color:var(--mut)">— <?= $rlb ?></span></span>
                        <code style="margin-left:auto;font-size:10px;background:var(--hover);padding:1px 5px;border-radius:3px;color:var(--sub)"><?= $rex ?></code>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div><!-- /col kanan -->

</div><!-- /row -->

<!-- SCOPED CSS -->
<style>
    .sm-itype-pill {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 7px 8px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        border: 1.5px solid var(--border);
        background: var(--hover);
        color: var(--sub);
        transition: all .15s;
        user-select: none
    }

    .sm-itype-pill.active {
        border-color: var(--accent);
        background: var(--as);
        color: var(--accent)
    }

    .sm-swatch {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 1.5px solid rgba(255, 255, 255, .1);
        cursor: pointer;
        padding: 0;
        transition: transform .12s
    }

    .sm-swatch:hover {
        transform: scale(1.35)
    }

    .sm-sw {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        position: relative
    }

    .sm-sw-track {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
        border-radius: 99px;
        transition: background .2s;
        flex-shrink: 0
    }

    .sm-sw-dot {
        position: absolute;
        top: 3px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #fff;
        transition: left .2s;
        display: block
    }

    .sm-item-row:hover {
        background: var(--hover)
    }

    .sm-cat-block.sortable-ghost,
    .sm-item-row.sortable-ghost {
        opacity: .25;
        border: 2px dashed var(--accent) !important
    }

    .ab.red:hover {
        background: rgba(239, 68, 68, .15) !important;
        color: var(--err) !important
    }
</style>

<!-- JS inline (global scope, tidak di dalam DOMContentLoaded) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
    const SM_URL = 'sermenu.php';

    function smPost(data) {
        const fd = new FormData();
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));
        return fetch(SM_URL, {
            method: 'POST',
            body: fd
        }).then(r => r.json()).catch(() => ({
            ok: false
        }));
    }

    function smToggle(btn, id) {
        smPost({
            action: 'toggle',
            id
        }).then(r => {
            if (!r.ok) return;
            const row = btn.closest('.sm-item-row');
            const ico = btn.querySelector('i');
            row.style.opacity = r.is_active ? '' : '.42';
            ico.className = r.is_active ? 'ph ph-eye' : 'ph ph-eye-slash';
            btn.style.color = r.is_active ? 'var(--ok)' : '';
        });
    }

    function smDeleteCat(id, name) {
        if (!confirm(`Hapus kategori "${name}" dan semua item di dalamnya?`)) return;
        smPost({
            action: 'delete',
            id
        }).then(r => {
            if (r.ok) document.querySelector(`.sm-cat-block[data-id="${id}"]`)?.remove();
        });
    }

    function smDeleteItem(id, name) {
        if (!confirm(`Hapus item "${name}"?`)) return;
        smPost({
            action: 'delete',
            id
        }).then(r => {
            if (r.ok) document.querySelector(`.sm-item-row[data-id="${id}"]`)?.remove();
        });
    }

    // Sortable — di dalam DOMContentLoaded karena butuh elemen DOM
    document.addEventListener('DOMContentLoaded', function() {
        const cl = document.getElementById('catList');
        if (cl) Sortable.create(cl, {
            handle: '.sm-drag-cat',
            animation: 180,
            ghostClass: 'sortable-ghost',
            onEnd: () => smDirty(true)
        });
        document.querySelectorAll('.sm-item-list').forEach(el => {
            Sortable.create(el, {
                handle: '.sm-drag-item',
                animation: 150,
                group: 'sm-items',
                ghostClass: 'sortable-ghost',
                onEnd(evt) {
                    const nc = evt.to.dataset.catId,
                        ii = evt.item.dataset.id;
                    if (evt.from !== evt.to && nc && ii) smPost({
                        action: 'move_item',
                        id: ii,
                        category_id: nc
                    });
                    smDirty(true);
                }
            });
        });
        // Init switch states
        document.querySelectorAll('.sm-sw input[type="checkbox"]').forEach(smSw);
        // Toast auto-dismiss
        document.querySelectorAll('.toast-item').forEach(t => {
            setTimeout(() => t.style.opacity = '0', 3500);
            setTimeout(() => t.remove(), 4000);
        });
    });

    let _dirty = false;

    function smDirty(v) {
        _dirty = v;
        const b = document.getElementById('saveOrderBtn');
        if (b) b.style.display = v ? 'inline-flex' : 'none';
    }

    function smSaveOrder() {
        const ids = [];
        document.querySelectorAll('#catList .sm-cat-block').forEach(c => {
            ids.push(c.dataset.id);
            c.querySelectorAll('.sm-item-list .sm-item-row').forEach(i => ids.push(i.dataset.id));
        });
        smPost({
            action: 'reorder',
            ids: JSON.stringify(ids)
        }).then(() => smDirty(false));
    }

    function smSw(inp) {
        const t = inp.nextElementSibling;
        if (!t) return;
        t.style.background = inp.checked ? '#3b82f6' : 'rgba(255,255,255,.12)';
        const d = t.querySelector('.sm-sw-dot');
        if (d) d.style.left = inp.checked ? '21px' : '3px';
    }

    function smIconTypeChange() {
        const sel = document.querySelector('input[name="icon_type"]:checked')?.value || 'ph';
        document.querySelectorAll('.sm-itype-pill').forEach(p => p.classList.toggle('active', p.querySelector('input').value === sel));
        const hints = {
            ph: 'Nama class Phosphor — cari di <a href="https://phosphoricons.com" target="_blank" style="color:var(--accent)">phosphoricons.com</a>',
            fa: 'Class Font Awesome, contoh: <code style="background:var(--hover);padding:0 4px;border-radius:3px">fas fa-home</code>',
            img: 'URL lengkap gambar JPG/PNG/SVG'
        };
        const phs = {
            ph: 'ph-star',
            fa: 'fas fa-home',
            img: 'https://…'
        };
        const h = document.getElementById('iconHint'),
            v = document.getElementById('iconVal');
        if (h) h.innerHTML = hints[sel] || '';
        if (v) v.placeholder = phs[sel] || '';
        smPrev();
    }

    function smPrev() {
        const box = document.getElementById('prevBox');
        if (!box) return;
        const itype = document.querySelector('input[name="icon_type"]:checked')?.value || 'ph';
        const ival = (document.getElementById('iconVal')?.value || '').trim();
        const ibg = document.getElementById('bgPick')?.value || '#dbeafe';
        const ifg = document.getElementById('fgPick')?.value || '#3b82f6';
        const badge = (document.getElementById('badgeVal')?.value || '').trim();
        const bclr = document.getElementById('badgeColor')?.value || '#ef4444';
        box.style.background = ibg;
        let inner = `<i class="ph ph-image-square" style="font-size:28px;color:#94a3b8"></i>`;
        if (ival) {
            if (itype === 'ph') inner = `<i class="ph ${esc(ival)}" style="font-size:26px;color:${esc(ifg)}"></i>`;
            if (itype === 'fa') inner = `<i class="${esc(ival)}" style="font-size:22px;color:${esc(ifg)}"></i>`;
            if (itype === 'img') inner = `<img src="${esc(ival)}" style="width:32px;height:32px;object-fit:contain" onerror="this.style.display='none'"/>`;
        }
        const bd = document.getElementById('prevBadge');
        box.innerHTML = inner;
        if (bd) {
            bd.textContent = badge;
            bd.style.background = bclr;
            bd.style.display = badge ? '' : 'none';
            box.appendChild(bd);
        }
    }

    function smPrevName(v) {
        const e = document.getElementById('prevName');
        if (e) e.textContent = v || 'Nama Item';
    }

    // Auto slug untuk form kategori
    const _catName = document.querySelector('input[name="cat_name"]');
    const _catSlug = document.getElementById('catSlug');
    if (_catName && _catSlug) {
        _catName.addEventListener('input', function() {
            if (!_catSlug.dataset.manual)
                _catSlug.value = this.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        });
        _catSlug.addEventListener('input', function() {
            this.dataset.manual = '1';
        });
    }

    function esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>