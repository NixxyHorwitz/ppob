<?php
// backoffice/service_menus.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Service Menus';
$active_menu = 'service_menus';

// ══════════════════════════════════════════════════════════════
//  AJAX / POST ACTIONS
// ══════════════════════════════════════════════════════════════
$action = $_POST['action'] ?? '';

// ── Reorder (AJAX, dipanggil oleh SortableJS) ────────────────
if ($action === 'reorder') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (is_array($ids)) {
        $st = $pdo->prepare("UPDATE service_menus SET sort_order=? WHERE id=?");
        foreach ($ids as $sort => $id) {
            $st->execute([($sort + 1) * 10, (int)$id]);
        }
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ── Pindah item antar kategori (drag cross-list) ──────────────
if ($action === 'move_item') {
    $pdo->prepare("UPDATE service_menus SET category_id=? WHERE id=? AND row_type='item'")
        ->execute([(int)$_POST['category_id'], (int)$_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Toggle aktif/nonaktif (AJAX) ─────────────────────────────
if ($action === 'toggle') {
    $id  = (int)$_POST['id'];
    $row = $pdo->prepare("SELECT is_active FROM service_menus WHERE id=?");
    $row->execute([$id]);
    $cur = (int)$row->fetchColumn();
    $new = $cur ? 0 : 1;
    $pdo->prepare("UPDATE service_menus SET is_active=? WHERE id=?")->execute([$new, $id]);
    echo json_encode(['ok' => true, 'is_active' => $new]);
    exit;
}

// ── Hapus ─────────────────────────────────────────────────────
if ($action === 'delete') {
    $id   = (int)$_POST['id'];
    $type = $pdo->prepare("SELECT row_type FROM service_menus WHERE id=?");
    $type->execute([$id]);
    $rt = $type->fetchColumn();
    if ($rt === 'category') {
        $pdo->prepare("DELETE FROM service_menus WHERE category_id=?")->execute([$id]);
    }
    $pdo->prepare("DELETE FROM service_menus WHERE id=?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Simpan kategori ───────────────────────────────────────────
if (in_array($action, ['save_cat'])) {
    $id        = (int)($_POST['id'] ?? 0);
    $cat_slug  = trim($_POST['cat_slug'] ?? '');
    $cat_name  = trim($_POST['cat_name'] ?? '');
    $sort      = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!$cat_name || !$cat_slug) {
        echo json_encode(['ok' => false, 'msg' => 'Nama dan slug wajib diisi.']);
        exit;
    }
    if ($id) {
        $pdo->prepare("UPDATE service_menus SET cat_slug=?,cat_name=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$cat_slug, $cat_name, $sort, $is_active, $id]);
    } else {
        if (!$sort) $sort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+10 FROM service_menus WHERE row_type='category'")->fetchColumn();
        $pdo->prepare("INSERT INTO service_menus (row_type,cat_slug,cat_name,sort_order,is_active) VALUES ('category',?,?,?,?)")
            ->execute([$cat_slug, $cat_name, $sort, $is_active]);
        $id = (int)$pdo->lastInsertId();
    }
    echo json_encode(['ok' => true, 'id' => $id]);
    exit;
}

// ── Simpan item ───────────────────────────────────────────────
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
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$name || !$category_id) {
        echo json_encode(['ok' => false, 'msg' => 'Nama item dan kategori wajib diisi.']);
        exit;
    }
    if ($id) {
        $pdo->prepare("UPDATE service_menus SET category_id=?,name=?,icon_type=?,icon_value=?,icon_bg=?,icon_color=?,href=?,query_cat=?,badge=?,badge_color=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$category_id, $name, $icon_type, $icon_value, $icon_bg, $icon_color, $href, $query_cat, $badge, $badge_color, $sort, $is_active, $id]);
    } else {
        if (!$sort) $sort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM service_menus WHERE category_id=$category_id")->fetchColumn();
        $pdo->prepare("INSERT INTO service_menus (row_type,category_id,name,icon_type,icon_value,icon_bg,icon_color,href,query_cat,badge,badge_color,sort_order,is_active) VALUES ('item',?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$category_id, $name, $icon_type, $icon_value, $icon_bg, $icon_color, $href, $query_cat, $badge, $badge_color, $sort, $is_active]);
        $id = (int)$pdo->lastInsertId();
    }
    // Return updated row untuk JS refresh
    $updated = $pdo->prepare("SELECT * FROM service_menus WHERE id=?");
    $updated->execute([$id]);
    echo json_encode(['ok' => true, 'row' => $updated->fetch()]);
    exit;
}

// ══════════════════════════════════════════════════════════════
//  FETCH DATA
// ══════════════════════════════════════════════════════════════
$all   = $pdo->query("SELECT * FROM service_menus ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$cats  = array_values(array_filter($all, fn($r) => $r['row_type'] === 'category'));
$items = array_filter($all, fn($r) => $r['row_type'] === 'item');

$items_by_cat = [];
foreach ($items as $item) {
    $items_by_cat[(int)$item['category_id']][] = $item;
}

$total_items  = count($items);
$active_items = count(array_filter($items, fn($r) => $r['is_active']));
$total_cats   = count($cats);

require_once __DIR__ . '/includes/header.php';
?>

<!-- ══ TOAST ══ -->
<div class="toast-wrap" id="smToastWrap"></div>

<!-- ══ PAGE HEADER ══ -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 style="display:flex;align-items:center;gap:10px">
            <span style="width:36px;height:36px;border-radius:10px;background:var(--as);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="ph ph-squares-four" style="font-size:20px;color:var(--accent)"></i>
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
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <button onclick="smOpenCatModal(null)"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;background:var(--hover);border:1px solid var(--border);color:var(--sub);font-size:12px;font-weight:600;cursor:pointer">
            <i class="ph ph-folder-plus" style="font-size:15px"></i>Tambah Kategori
        </button>
        <button onclick="smOpenItemModal(null, <?= $cats ? $cats[0]['id'] : 0 ?>)"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;background:var(--accent);border:none;color:#fff;font-size:12px;font-weight:700;cursor:pointer">
            <i class="ph ph-plus" style="font-size:15px"></i>Tambah Item
        </button>
    </div>
</div>

<!-- ══ STAT BAR ══ -->
<div style="display:flex;background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:24px">
    <?php
    $stats = [
        [$total_cats,               'Kategori',     ''],
        [$total_items,              'Total Item',   ''],
        [$active_items,             'Aktif',        'color:var(--ok);font-weight:700'],
        [$total_items - $active_items, 'Nonaktif',     'color:var(--mut)'],
    ];
    foreach ($stats as $i => [$v, $lbl, $vc]):
    ?>
        <?php if ($i > 0): ?><div style="width:1px;background:var(--border)"></div><?php endif; ?>
        <div style="flex:1;padding:14px 16px;text-align:center">
            <div style="font-size:24px;font-weight:700;font-family:'JetBrains Mono',monospace;line-height:1;<?= $vc ?>"><?= $v ?></div>
            <div style="font-size:11px;color:var(--mut);font-weight:600;margin-top:4px;text-transform:uppercase;letter-spacing:.5px"><?= $lbl ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ══ MAIN LAYOUT ══ -->
<div class="row g-4 align-items-start">

    <!-- ══ LEFT: Struktur editor ══ -->
    <div class="col-xl-7 col-lg-7">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <div style="display:flex;align-items:center;gap:8px">
                <i class="ph ph-tree-structure" style="color:var(--accent);font-size:16px"></i>
                <span style="font-size:14px;font-weight:700">Struktur Menu</span>
                <span style="font-size:11px;color:var(--mut)">— drag untuk reorder</span>
            </div>
            <button id="smSaveOrderBtn" onclick="smSaveOrder()" style="display:none;align-items:center;gap:6px;padding:6px 14px;border-radius:7px;background:var(--ok);border:none;color:#fff;font-size:12px;font-weight:700;cursor:pointer">
                <i class="ph ph-floppy-disk"></i> Simpan Urutan
            </button>
        </div>

        <!-- Category list (outer sortable) -->
        <div id="smCatList" style="display:flex;flex-direction:column;gap:10px">

            <?php if (empty($cats)): ?>
                <div style="border:2px dashed var(--border);border-radius:var(--r);padding:52px;text-align:center;color:var(--mut)">
                    <i class="ph ph-folder-open" style="font-size:40px;display:block;margin-bottom:12px;opacity:.25"></i>
                    Belum ada kategori.<br>
                    <span style="font-size:12px">Klik <strong>Tambah Kategori</strong> untuk memulai.</span>
                </div>
            <?php endif; ?>

            <?php foreach ($cats as $cat):
                $citems = $items_by_cat[(int)$cat['id']] ?? [];
            ?>
                <div class="sm-cat-block" data-id="<?= $cat['id'] ?>"
                    style="background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;transition:box-shadow .2s">

                    <!-- ── Category header ── -->
                    <div style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:rgba(255,255,255,.02)">

                        <!-- Drag handle (kategori) -->
                        <div class="sm-drag-cat"
                            style="cursor:grab;color:var(--mut);font-size:18px;flex-shrink:0;line-height:1;padding:2px 0">
                            <i class="ph ph-dots-six-vertical"></i>
                        </div>

                        <!-- Category icon -->
                        <div style="width:32px;height:32px;border-radius:8px;background:var(--as);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="ph ph-folder-open" style="font-size:16px;color:var(--accent)"></i>
                        </div>

                        <!-- Info -->
                        <div style="flex:1;min-width:0">
                            <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                                <span style="font-size:13.5px;font-weight:700"><?= htmlspecialchars($cat['cat_name']) ?></span>
                                <code style="font-size:10px;background:var(--hover);color:var(--mut);padding:1px 6px;border-radius:4px;border:1px solid var(--border)"><?= htmlspecialchars($cat['cat_slug']) ?></code>
                                <?php if (!$cat['is_active']): ?>
                                    <span style="font-size:9px;font-weight:800;padding:2px 7px;border-radius:99px;background:rgba(239,68,68,.1);color:var(--err);text-transform:uppercase;letter-spacing:.5px">Nonaktif</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:11px;color:var(--mut);margin-top:2px">
                                <?= count($citems) ?> item · sort #<?= $cat['sort_order'] ?>
                            </div>
                        </div>

                        <!-- Cat actions -->
                        <div style="display:flex;gap:3px;flex-shrink:0">
                            <button class="ab" title="Edit kategori" onclick="smOpenCatModal(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES) ?>)">
                                <i class="ph ph-pencil-simple"></i>
                            </button>
                            <button class="ab" title="Tambah item ke sini" onclick="smOpenItemModal(null, <?= $cat['id'] ?>)"
                                style="color:var(--ok)">
                                <i class="ph ph-plus-circle"></i>
                            </button>
                            <button class="ab red" title="Hapus kategori" onclick="smDeleteCat(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['cat_name'])) ?>')">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ── Item list (inner sortable) ── -->
                    <div class="sm-item-list" data-cat-id="<?= $cat['id'] ?>" style="min-height:10px">

                        <?php foreach ($citems as $item):
                            $ibg  = htmlspecialchars($item['icon_bg']    ?? '#e0f2fe');
                            $iclr = htmlspecialchars($item['icon_color'] ?? '#0ea5e9');
                            $ival = htmlspecialchars($item['icon_value'] ?? '');
                        ?>
                            <div class="sm-item-row" data-id="<?= $item['id'] ?>" data-cat="<?= $item['category_id'] ?>"
                                style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-top:1px solid rgba(255,255,255,.04);transition:background .15s;<?= $item['is_active'] ? '' : 'opacity:.42' ?>">

                                <!-- Drag handle (item) -->
                                <div class="sm-drag-item" style="cursor:grab;color:var(--mut);font-size:15px;flex-shrink:0;line-height:1">
                                    <i class="ph ph-dots-six-vertical"></i>
                                </div>

                                <!-- Icon preview -->
                                <div style="width:36px;height:36px;border-radius:9px;background:<?= $ibg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative">
                                    <?php if ($item['icon_type'] === 'ph'): ?>
                                        <i class="ph <?= $ival ?>" style="font-size:18px;color:<?= $iclr ?>"></i>
                                    <?php elseif ($item['icon_type'] === 'fa'): ?>
                                        <i class="<?= $ival ?>" style="font-size:16px;color:<?= $iclr ?>"></i>
                                    <?php elseif ($item['icon_type'] === 'img'): ?>
                                        <img src="<?= $ival ?>" style="width:22px;height:22px;object-fit:contain" onerror="this.style.display='none'" />
                                    <?php endif; ?>
                                    <?php if ($item['badge']): ?>
                                        <div style="position:absolute;top:-4px;right:-4px;background:<?= htmlspecialchars($item['badge_color']) ?>;color:#fff;font-size:7px;font-weight:800;padding:1px 4px;border-radius:99px;line-height:1.4;white-space:nowrap">
                                            <?= htmlspecialchars($item['badge']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Item info -->
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </div>
                                    <div style="font-size:10px;color:var(--mut);font-family:'JetBrains Mono',monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px">
                                        <?= htmlspecialchars($item['href'] ?: '#') ?><?= $item['query_cat'] ? '?cat=' . htmlspecialchars($item['query_cat']) : '' ?>
                                    </div>
                                </div>

                                <!-- Type badge -->
                                <div style="flex-shrink:0;display:flex;align-items:center;gap:4px">
                                    <span style="font-size:9px;font-weight:700;padding:2px 6px;border-radius:99px;background:var(--hover);color:var(--mut);text-transform:uppercase;letter-spacing:.4px">
                                        <?= $item['icon_type'] ?>
                                    </span>
                                </div>

                                <!-- Item actions -->
                                <div style="display:flex;gap:3px;flex-shrink:0">
                                    <!-- Toggle -->
                                    <button class="ab sm-toggle-btn" title="<?= $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                        data-id="<?= $item['id'] ?>"
                                        onclick="smToggle(this, <?= $item['id'] ?>)"
                                        style="<?= $item['is_active'] ? 'color:var(--ok)' : '' ?>">
                                        <i class="ph <?= $item['is_active'] ? 'ph-eye' : 'ph-eye-slash' ?>"></i>
                                    </button>
                                    <!-- Edit -->
                                    <button class="ab" title="Edit item"
                                        onclick="smOpenItemModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES) ?>, <?= $item['category_id'] ?>)">
                                        <i class="ph ph-pencil-simple"></i>
                                    </button>
                                    <!-- Hapus -->
                                    <button class="ab red" title="Hapus item"
                                        onclick="smDeleteItem(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>')">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Empty hint + quick-add -->
                        <div style="padding:8px 14px">
                            <button onclick="smOpenItemModal(null, <?= $cat['id'] ?>)"
                                style="width:100%;background:transparent;border:1px dashed rgba(255,255,255,.07);border-radius:7px;padding:7px;font-size:11px;color:var(--mut);cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:5px"
                                onmouseover="this.style.borderColor='var(--ba)';this.style.color='var(--accent)'"
                                onmouseout="this.style.borderColor='rgba(255,255,255,.07)';this.style.color='var(--mut)'">
                                <i class="ph ph-plus"></i>
                                <?php if (empty($citems)): ?>
                                    Belum ada item — klik untuk menambahkan
                                <?php else: ?>
                                    Tambah item ke <strong style="margin-left:3px"><?= htmlspecialchars($cat['cat_name']) ?></strong>
                                <?php endif; ?>
                            </button>
                        </div>

                    </div><!-- /sm-item-list -->
                </div><!-- /sm-cat-block -->
            <?php endforeach; ?>

        </div><!-- /smCatList -->
    </div>

    <!-- ══ RIGHT: Preview + Legend ══ -->
    <div class="col-xl-5 col-lg-5">
        <div style="position:sticky;top:calc(var(--hh) + 20px)">

            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
                <i class="ph ph-device-mobile" style="color:var(--accent);font-size:16px"></i>
                <span style="font-size:14px;font-weight:700">Live Preview</span>
                <span style="font-size:11px;color:var(--mut)">— tampilan di aplikasi</span>
            </div>

            <!-- Phone shell -->
            <div style="max-width:340px;margin:0 auto;background:var(--card);border:1px solid var(--border);border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.35)">

                <!-- Notch bar -->
                <div style="background:var(--surface);padding:10px 18px 8px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)">
                    <span style="font-size:10px;font-weight:700;color:var(--mut)">Layanan</span>
                    <div style="display:flex;gap:5px;align-items:center;color:var(--mut)">
                        <i class="ph ph-wifi-high" style="font-size:11px"></i>
                        <i class="ph ph-battery-full" style="font-size:11px"></i>
                    </div>
                </div>

                <!-- Preview content -->
                <div id="smPreviewScroll" style="padding:12px 10px;max-height:500px;overflow-y:auto;scrollbar-width:none">
                    <?php foreach ($cats as $cat):
                        $citems_active = array_filter($items_by_cat[(int)$cat['id']] ?? [], fn($i) => $i['is_active']);
                        if (!$cat['is_active'] || empty($citems_active)) continue;
                    ?>
                        <div style="margin-bottom:16px">
                            <div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:var(--mut);margin-bottom:8px;padding:0 4px">
                                <?= htmlspecialchars($cat['cat_name']) ?>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px">
                                <?php foreach ($citems_active as $item):
                                    $ibg  = htmlspecialchars($item['icon_bg']    ?? '#e0f2fe');
                                    $iclr = htmlspecialchars($item['icon_color'] ?? '#0ea5e9');
                                    $ival = htmlspecialchars($item['icon_value'] ?? '');
                                ?>
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                                        <div style="width:48px;height:48px;border-radius:13px;background:<?= $ibg ?>;display:flex;align-items:center;justify-content:center;position:relative">
                                            <?php if ($item['icon_type'] === 'ph'): ?>
                                                <i class="ph <?= $ival ?>" style="font-size:22px;color:<?= $iclr ?>"></i>
                                            <?php elseif ($item['icon_type'] === 'fa'): ?>
                                                <i class="<?= $ival ?>" style="font-size:18px;color:<?= $iclr ?>"></i>
                                            <?php elseif ($item['icon_type'] === 'img'): ?>
                                                <img src="<?= $ival ?>" style="width:28px;height:28px;object-fit:contain" />
                                            <?php endif; ?>
                                            <?php if ($item['badge']): ?>
                                                <div style="position:absolute;top:-4px;right:-4px;background:<?= htmlspecialchars($item['badge_color']) ?>;color:#fff;font-size:7px;font-weight:800;padding:1px 4px;border-radius:99px;line-height:1.5">
                                                    <?= htmlspecialchars($item['badge']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:9px;font-weight:600;color:var(--sub);text-align:center;max-width:52px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($cats)): ?>
                        <div style="text-align:center;padding:32px 0;color:var(--mut);font-size:11px">
                            <i class="ph ph-image" style="font-size:32px;display:block;margin-bottom:8px;opacity:.2"></i>
                            Tambah menu untuk melihat preview
                        </div>
                    <?php endif; ?>
                </div>
            </div><!-- /phone shell -->

            <!-- Icon reference card -->
            <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;margin-top:14px;max-width:340px;margin-left:auto;margin-right:auto">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--mut);margin-bottom:10px">
                    <i class="ph ph-book-open me-1"></i>Referensi Icon
                </div>
                <?php foreach (
                    [
                        ['ph',  'ph-lightning', 'Phosphor Icons',  'phosphoricons.com — cari nama class, contoh: <code>ph-star</code>'],
                        ['fa',  'ph-flag',      'Font Awesome',     'Prefix "fas fa-" atau "fab fa-", contoh: <code>fas fa-home</code>'],
                        ['img', 'ph-image',     'URL Gambar',       'Link JPG/PNG/SVG, contoh: <code>https://cdn…/icon.png</code>'],
                    ] as [$t, $ic, $lbl, $desc]
                ): ?>
                    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:8px">
                        <div style="width:26px;height:26px;border-radius:6px;background:var(--hover);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px">
                            <i class="ph <?= $ic ?>" style="font-size:13px;color:var(--sub)"></i>
                        </div>
                        <div>
                            <div style="font-size:12px;font-weight:600"><?= $t ?> <span style="color:var(--mut);font-weight:400">— <?= $lbl ?></span></div>
                            <div style="font-size:11px;color:var(--mut);margin-top:1px"><?= $desc ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div><!-- /col right -->

</div><!-- /row -->

<!-- ══════════════════════════════════════════════════════════════
     MODAL: KATEGORI
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="smModalCat" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content mc" style="border-radius:14px">
            <div class="modal-header mh" style="padding:16px 20px">
                <h5 class="modal-title" id="smModalCatTitle" style="font-size:15px;font-weight:700">
                    <i class="ph ph-folder-plus me-2" style="color:var(--accent)"></i>Tambah Kategori
                </h5>
                <button type="button" class="btn-close" style="filter:invert(1);opacity:.5" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px;background:transparent">

                <input type="hidden" id="smCatId" value="0" />

                <!-- Nama -->
                <div style="margin-bottom:16px">
                    <label class="sm-lbl">Nama Kategori *</label>
                    <input type="text" id="smCatName" class="fi w-100" style="padding-left:13px" placeholder="Top Up & Digital, Tagihan…" oninput="smAutoSlug()" />
                </div>

                <!-- Slug -->
                <div style="margin-bottom:16px">
                    <label class="sm-lbl">Slug *</label>
                    <input type="text" id="smCatSlug" class="fi w-100" style="padding-left:13px;font-family:'JetBrains Mono',monospace;font-size:12px" placeholder="topup, bills, voucher…" />
                    <div style="font-size:11px;color:var(--mut);margin-top:4px">Lowercase, tanpa spasi, unik</div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label class="sm-lbl">Sort Order</label>
                        <input type="number" id="smCatSort" class="fi w-100" style="padding-left:13px" value="10" min="0" step="10" />
                    </div>
                    <div class="col-6">
                        <label class="sm-lbl">Status</label>
                        <div style="margin-top:4px">
                            <label class="sm-sw-wrap">
                                <input type="checkbox" id="smCatActive" checked onchange="smSwAnim(this)" />
                                <span class="sm-sw-track"><span class="sm-sw-dot"></span></span>
                                <span style="font-size:12px;color:var(--sub)">Aktif</span>
                            </label>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer mf" style="padding:14px 20px;background:transparent">
                <button class="ab" data-bs-dismiss="modal" style="padding:7px 16px;border-radius:7px">Batal</button>
                <button onclick="smSaveCat()" id="smCatSaveBtn"
                    style="padding:8px 20px;border-radius:7px;background:var(--accent);border:none;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px">
                    <i class="ph ph-floppy-disk"></i><span id="smCatSaveLbl">Simpan</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: ITEM
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="smModalItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:720px">
        <div class="modal-content mc" style="border-radius:14px">
            <div class="modal-header mh" style="padding:16px 20px">
                <h5 class="modal-title" id="smModalItemTitle" style="font-size:15px;font-weight:700">
                    <i class="ph ph-plus-circle me-2" style="color:var(--accent)"></i>Tambah Item
                </h5>
                <button type="button" class="btn-close" style="filter:invert(1);opacity:.5" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px;background:transparent">

                <input type="hidden" id="smItemId" value="0" />

                <div class="row g-3">

                    <!-- LEFT: Form fields -->
                    <div class="col-md-7" style="display:flex;flex-direction:column;gap:14px">

                        <!-- Nama + Kategori -->
                        <div class="row g-2">
                            <div class="col-7">
                                <label class="sm-lbl">Nama Item *</label>
                                <input type="text" id="smItemName" class="fi w-100" style="padding-left:13px" placeholder="Pulsa, BPJS, PLN…"
                                    oninput="smItemPreviewName()" />
                            </div>
                            <div class="col-5">
                                <label class="sm-lbl">Kategori *</label>
                                <select id="smItemCat" class="fs w-100" style="padding:8px 12px">
                                    <?php foreach ($cats as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cat_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Icon type -->
                        <div>
                            <label class="sm-lbl">Tipe Icon *</label>
                            <div style="display:flex;gap:6px">
                                <?php foreach (['ph' => ['ph-star', 'Phosphor'], 'fa' => ['ph-flag', 'Font Awesome'], 'img' => ['ph-image', 'Gambar URL']] as $v => [$ic, $lb]): ?>
                                    <label class="sm-itype-pill <?= $v === 'ph' ? 'active' : '' ?>" for="smIt_<?= $v ?>">
                                        <input type="radio" name="smIconType" value="<?= $v ?>" id="smIt_<?= $v ?>" <?= $v === 'ph' ? 'checked' : '' ?> class="d-none"
                                            onchange="smOnIconTypeChange()" />
                                        <i class="ph <?= $ic ?>"></i><?= $lb ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Icon value -->
                        <div>
                            <label class="sm-lbl">Nilai Icon *</label>
                            <input type="text" id="smItemIconVal" class="fi w-100" style="padding-left:13px" placeholder="ph-star"
                                oninput="smItemPreview()" />
                            <div id="smItemIconHint" style="font-size:11px;color:var(--mut);margin-top:4px">
                                Nama class Phosphor, contoh: <code style="background:var(--hover);padding:1px 5px;border-radius:4px">ph-star</code>
                            </div>
                        </div>

                        <!-- Warna -->
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="sm-lbl">Background Icon</label>
                                <div style="display:flex;gap:6px;align-items:center;margin-bottom:5px">
                                    <input type="color" id="smItemBg" value="#dbeafe"
                                        style="width:32px;height:32px;border-radius:7px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer;flex-shrink:0"
                                        oninput="smItemBgHex.value=this.value;smItemPreview()" />
                                    <input type="text" id="smItemBgHex" value="#dbeafe" maxlength="7"
                                        style="font-family:'JetBrains Mono',monospace;font-size:11px;padding:6px 8px;background:var(--hover);border:1px solid var(--border);border-radius:7px;color:var(--text);width:80px"
                                        oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){smItemBg.value=this.value;smItemPreview()}" />
                                </div>
                                <!-- BG swatches -->
                                <div style="display:flex;gap:3px;flex-wrap:wrap">
                                    <?php foreach (['#dbeafe', '#ede9fe', '#fce7f3', '#fef9c3', '#dcfce7', '#ffedd5', '#cffafe', '#f0fdf4', '#fef3c7', '#f1f5f9'] as $c): ?>
                                        <button type="button" class="sm-swatch" onclick="smPickBg('<?= $c ?>')"
                                            style="background:<?= $c ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="sm-lbl">Warna Icon</label>
                                <div style="display:flex;gap:6px;align-items:center;margin-bottom:5px">
                                    <input type="color" id="smItemFg" value="#3b82f6"
                                        style="width:32px;height:32px;border-radius:7px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer;flex-shrink:0"
                                        oninput="smItemFgHex.value=this.value;smItemPreview()" />
                                    <input type="text" id="smItemFgHex" value="#3b82f6" maxlength="7"
                                        style="font-family:'JetBrains Mono',monospace;font-size:11px;padding:6px 8px;background:var(--hover);border:1px solid var(--border);border-radius:7px;color:var(--text);width:80px"
                                        oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){smItemFg.value=this.value;smItemPreview()}" />
                                </div>
                                <!-- FG swatches -->
                                <div style="display:flex;gap:3px;flex-wrap:wrap">
                                    <?php foreach (['#3b82f6', '#7c3aed', '#db2777', '#ca8a04', '#16a34a', '#ea580c', '#0891b2', '#0ea5e9', '#64748b', '#d97706'] as $c): ?>
                                        <button type="button" class="sm-swatch" onclick="smPickFg('<?= $c ?>')"
                                            style="background:<?= $c ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Href + Query -->
                        <div class="row g-2">
                            <div class="col-7">
                                <label class="sm-lbl">URL / href</label>
                                <input type="text" id="smItemHref" class="fi w-100" style="padding-left:13px" value="#" placeholder="/pages/prabayar.php" />
                            </div>
                            <div class="col-5">
                                <label class="sm-lbl">Query Cat <span style="color:var(--mut);font-weight:400">(opsional)</span></label>
                                <input type="text" id="smItemQueryCat" class="fi w-100" style="padding-left:13px" placeholder="Pulsa, PLN…" />
                            </div>
                        </div>

                        <!-- Badge + Sort -->
                        <div class="row g-2">
                            <div class="col-4">
                                <label class="sm-lbl">Badge</label>
                                <input type="text" id="smItemBadge" class="fi w-100" style="padding-left:13px" placeholder="NEW, HOT…" maxlength="10"
                                    oninput="smItemPreview()" />
                            </div>
                            <div class="col-4">
                                <label class="sm-lbl">Warna Badge</label>
                                <input type="color" id="smItemBadgeColor" value="#ef4444"
                                    style="width:100%;height:34px;border-radius:7px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer"
                                    oninput="smItemPreview()" />
                            </div>
                            <div class="col-4">
                                <label class="sm-lbl">Sort</label>
                                <input type="number" id="smItemSort" class="fi w-100" style="padding-left:13px" value="0" min="0" />
                            </div>
                        </div>

                    </div><!-- /left -->

                    <!-- RIGHT: Preview + Status -->
                    <div class="col-md-5" style="display:flex;flex-direction:column;gap:12px">

                        <div>
                            <label class="sm-lbl">Preview</label>
                            <div style="background:var(--hover);border:1px solid var(--border);border-radius:11px;padding:24px 16px;text-align:center">
                                <div style="display:inline-flex;flex-direction:column;align-items:center;gap:8px">
                                    <!-- Icon box -->
                                    <div id="smItemPrevBox"
                                        style="width:58px;height:58px;border-radius:16px;display:flex;align-items:center;justify-content:center;position:relative;background:#dbeafe">
                                        <i id="smItemPrevIco" class="ph ph-image-square" style="font-size:26px;color:#94a3b8"></i>
                                    </div>
                                    <!-- Name -->
                                    <div id="smItemPrevName" style="font-size:11px;font-weight:700;color:var(--sub);max-width:64px;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        Nama Item
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status toggle -->
                        <div style="background:var(--hover);border:1px solid var(--border);border-radius:9px;padding:12px 14px">
                            <div style="display:flex;align-items:center;justify-content:space-between">
                                <div>
                                    <div style="font-size:13px;font-weight:600">Aktifkan Item</div>
                                    <div style="font-size:11px;color:var(--mut)">Tampilkan ke user</div>
                                </div>
                                <label class="sm-sw-wrap">
                                    <input type="checkbox" id="smItemActive" checked onchange="smSwAnim(this)" />
                                    <span class="sm-sw-track"><span class="sm-sw-dot"></span></span>
                                </label>
                            </div>
                        </div>

                        <!-- Phosphor hint -->
                        <a href="https://phosphoricons.com" target="_blank"
                            style="display:flex;align-items:flex-start;gap:10px;background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15);border-radius:8px;padding:10px 12px;text-decoration:none;color:var(--sub)">
                            <i class="ph ph-arrow-square-out" style="font-size:16px;color:var(--accent);flex-shrink:0;margin-top:1px"></i>
                            <div style="font-size:11px;line-height:1.6">
                                <strong style="color:var(--accent)">phosphoricons.com</strong><br>
                                Cari icon → copy nama class (contoh: <code style="background:var(--hover);padding:0 4px;border-radius:3px">ph-lightning</code>)
                            </div>
                        </a>

                    </div><!-- /right -->
                </div><!-- /row -->
            </div>
            <div class="modal-footer mf" style="padding:14px 20px;background:transparent">
                <button class="ab" data-bs-dismiss="modal" style="padding:7px 16px;border-radius:7px">Batal</button>
                <button onclick="smSaveItem()" id="smItemSaveBtn"
                    style="padding:8px 20px;border-radius:7px;background:var(--accent);border:none;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px">
                    <i class="ph ph-floppy-disk"></i><span id="smItemSaveLbl">Simpan Item</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ SCOPED CSS ══ -->
<style>
    /* Label utility */
    .sm-lbl {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--sub);
        display: block;
        margin-bottom: 6px;
    }

    /* Icon type pills */
    .sm-itype-pill {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 7px 10px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        border: 1.5px solid var(--border);
        background: var(--hover);
        color: var(--sub);
        transition: all .15s;
        user-select: none;
    }

    .sm-itype-pill.active {
        border-color: var(--accent);
        background: var(--as);
        color: var(--accent);
    }

    /* Color swatches */
    .sm-swatch {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 1.5px solid rgba(255, 255, 255, .1);
        cursor: pointer;
        padding: 0;
        transition: transform .12s;
    }

    .sm-swatch:hover {
        transform: scale(1.35);
    }

    /* Toggle switch */
    .sm-sw-wrap {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .sm-sw-track {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
        border-radius: 99px;
        background: rgba(255, 255, 255, .12);
        transition: background .2s;
        flex-shrink: 0;
    }

    .sm-sw-track .sm-sw-dot {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #fff;
        transition: left .2s;
        display: block;
    }

    input:checked~.sm-sw-track {
        background: #3b82f6;
    }

    input:checked~.sm-sw-track .sm-sw-dot {
        left: 21px;
    }

    /* Item row hover */
    .sm-item-row:hover {
        background: var(--hover);
    }

    /* Sortable ghost */
    .sm-cat-block.sortable-ghost {
        opacity: .3;
        border: 2px dashed var(--accent) !important;
    }

    .sm-item-row.sortable-ghost {
        opacity: .25;
        background: var(--as) !important;
    }

    /* Red action button */
    .ab.red:hover {
        background: rgba(239, 68, 68, .15) !important;
        color: var(--err) !important;
    }
</style>

<?php
$cats_json = json_encode(
    array_values(array_map(fn($c) => ['id' => $c['id'], 'cat_name' => $c['cat_name']], $cats))
);

// JS rendered inline below
$page_scripts = '';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
    // ══ DATA ══════════════════════════════════════════════════════
    const SM_CATS = <?= $cats_json ?>;
    const SM_URL = 'service_menus.php';

    // Modal instances — lazy init inside DOMContentLoaded
    let smModalCat, smModalItem;

    document.addEventListener('DOMContentLoaded', function() {

        // ── Modal instances ───────────────────────────────────────────
        smModalCat = new bootstrap.Modal(document.getElementById('smModalCat'));
        smModalItem = new bootstrap.Modal(document.getElementById('smModalItem'));

        // ══ SORTABLE ══════════════════════════════════════════════════

        // Outer: kategori
        Sortable.create(document.getElementById('smCatList'), {
            handle: '.sm-drag-cat',
            animation: 180,
            ghostClass: 'sortable-ghost',
            onEnd: () => smDirty(true),
        });

        // Inner: items per kategori (dengan cross-list drag)
        document.querySelectorAll('.sm-item-list').forEach(el => {
            Sortable.create(el, {
                handle: '.sm-drag-item',
                animation: 150,
                group: 'sm-items',
                ghostClass: 'sortable-ghost',
                onEnd(evt) {
                    const newCatId = evt.to.dataset.catId;
                    const itemId = evt.item.dataset.id;
                    if (evt.from !== evt.to && newCatId && itemId) {
                        // Kirim update category_id
                        smPost({
                            action: 'move_item',
                            id: itemId,
                            category_id: newCatId
                        });
                    }
                    smDirty(true);
                }
            });
        });

        let _dirty = false;
    }); // end DOMContentLoaded

    function smDirty(v) {
        _dirty = v;
        const btn = document.getElementById('smSaveOrderBtn');
        if (btn) btn.style.display = v ? 'inline-flex' : 'none';
    }

    // ── Kumpulkan & simpan urutan ─────────────────────────────────
    function smSaveOrder() {
        const ids = [];
        document.querySelectorAll('#smCatList .sm-cat-block').forEach(catEl => {
            ids.push(catEl.dataset.id);
            catEl.querySelectorAll('.sm-item-list .sm-item-row').forEach(itemEl => {
                ids.push(itemEl.dataset.id);
            });
        });
        smPost({
            action: 'reorder',
            ids: JSON.stringify(ids)
        }).then(() => {
            smDirty(false);
            smToast('Urutan berhasil disimpan!');
        });
    }

    // ── Toggle aktif/nonaktif ─────────────────────────────────────
    function smToggle(btn, id) {
        smPost({
            action: 'toggle',
            id
        }).then(r => {
            if (!r.ok) return;
            const row = btn.closest('.sm-item-row');
            const icon = btn.querySelector('i');
            if (r.is_active) {
                row.style.opacity = '';
                icon.className = 'ph ph-eye';
                btn.style.color = 'var(--ok)';
                btn.title = 'Nonaktifkan';
            } else {
                row.style.opacity = '.42';
                icon.className = 'ph ph-eye-slash';
                btn.style.color = '';
                btn.title = 'Aktifkan';
            }
            smToast(r.is_active ? 'Item diaktifkan.' : 'Item dinonaktifkan.');
        });
    }

    // ── Hapus kategori ─────────────────────────────────────────────
    function smDeleteCat(id, name) {
        if (!confirm(`Hapus kategori "${name}" beserta semua item di dalamnya?`)) return;
        smPost({
            action: 'delete',
            id
        }).then(r => {
            if (!r.ok) return smToast('Gagal menghapus.', true);
            document.querySelector(`.sm-cat-block[data-id="${id}"]`)?.remove();
            smToast(`Kategori "${name}" dihapus.`);
        });
    }

    // ── Hapus item ─────────────────────────────────────────────────
    function smDeleteItem(id, name) {
        if (!confirm(`Hapus item "${name}"?`)) return;
        smPost({
            action: 'delete',
            id
        }).then(r => {
            if (!r.ok) return smToast('Gagal menghapus.', true);
            document.querySelector(`.sm-item-row[data-id="${id}"]`)?.remove();
            smToast(`Item "${name}" dihapus.`);
        });
    }

    // ══ MODAL: KATEGORI ════════════════════════════════════════════

    function smOpenCatModal(cat) {
        const editing = !!cat;
        document.getElementById('smModalCatTitle').innerHTML =
            `<i class="ph ph-folder${editing?'':'-plus'} me-2" style="color:var(--accent)"></i>${editing ? 'Edit Kategori' : 'Tambah Kategori'}`;
        document.getElementById('smCatId').value = cat?.id || 0;
        document.getElementById('smCatName').value = cat?.cat_name || '';
        document.getElementById('smCatSlug').value = cat?.cat_slug || '';
        document.getElementById('smCatSort').value = cat?.sort_order || 10;
        const chk = document.getElementById('smCatActive');
        chk.checked = cat ? !!+cat.is_active : true;
        smSwAnim(chk);
        document.getElementById('smCatSaveLbl').textContent = editing ? 'Perbarui' : 'Simpan';
        smModalCat.show();
    }

    function smAutoSlug() {
        const slug = document.getElementById('smCatSlug');
        if (!slug.dataset.manual) {
            slug.value = document.getElementById('smCatName').value
                .toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        }
    }
    document.getElementById('smCatSlug').addEventListener('input', function() {
        this.dataset.manual = '1';
    });
    // Clear manual flag when modal opens
    document.getElementById('smModalCat').addEventListener('show.bs.modal', () => {
        delete document.getElementById('smCatSlug').dataset.manual;
    });

    function smSaveCat() {
        const id = document.getElementById('smCatId').value;
        const cat_name = document.getElementById('smCatName').value.trim();
        const cat_slug = document.getElementById('smCatSlug').value.trim();
        const sort_order = document.getElementById('smCatSort').value;
        const is_active = document.getElementById('smCatActive').checked ? 1 : 0;

        if (!cat_name || !cat_slug) return smToast('Nama dan slug wajib diisi.', true);

        const btn = document.getElementById('smCatSaveBtn');
        btn.disabled = true;

        smPost({
                action: 'save_cat',
                id,
                cat_name,
                cat_slug,
                sort_order,
                ...(is_active ? {
                    is_active: 1
                } : {})
            })
            .then(r => {
                btn.disabled = false;
                if (!r.ok) return smToast(r.msg || 'Gagal menyimpan.', true);
                smModalCat.hide();
                smToast(+id ? 'Kategori diperbarui.' : 'Kategori ditambahkan.');
                setTimeout(() => location.reload(), 500);
            });
    }

    // ══ MODAL: ITEM ════════════════════════════════════════════════

    function smOpenItemModal(item, defaultCatId) {
        const editing = !!item;
        document.getElementById('smModalItemTitle').innerHTML =
            `<i class="ph ph-${editing?'pencil-simple':'plus-circle'} me-2" style="color:var(--accent)"></i>${editing?'Edit Item':'Tambah Item'}`;

        document.getElementById('smItemId').value = item?.id || 0;
        document.getElementById('smItemName').value = item?.name || '';
        document.getElementById('smItemIconVal').value = item?.icon_value || '';
        document.getElementById('smItemBg').value = item?.icon_bg || '#dbeafe';
        document.getElementById('smItemBgHex').value = item?.icon_bg || '#dbeafe';
        document.getElementById('smItemFg').value = item?.icon_color || '#3b82f6';
        document.getElementById('smItemFgHex').value = item?.icon_color || '#3b82f6';
        document.getElementById('smItemHref').value = item?.href || '#';
        document.getElementById('smItemQueryCat').value = item?.query_cat || '';
        document.getElementById('smItemBadge').value = item?.badge || '';
        document.getElementById('smItemBadgeColor').value = item?.badge_color || '#ef4444';
        document.getElementById('smItemSort').value = item?.sort_order || 0;

        // Kategori dropdown
        const sel = document.getElementById('smItemCat');
        sel.value = item?.category_id || defaultCatId || (SM_CATS[0]?.id ?? '');

        // Icon type pills
        const itype = item?.icon_type || 'ph';
        document.querySelectorAll('input[name="smIconType"]').forEach(r => {
            r.checked = r.value === itype;
        });
        document.querySelectorAll('.sm-itype-pill').forEach(p => {
            p.classList.toggle('active', p.querySelector('input').value === itype);
        });
        smUpdateIconHint(itype);

        // Status toggle
        const chk = document.getElementById('smItemActive');
        chk.checked = item ? !!+item.is_active : true;
        smSwAnim(chk);

        document.getElementById('smItemSaveLbl').textContent = editing ? 'Perbarui Item' : 'Simpan Item';

        smItemPreview();
        smModalItem.show();
    }

    function smOnIconTypeChange() {
        const itype = document.querySelector('input[name="smIconType"]:checked')?.value || 'ph';
        document.querySelectorAll('.sm-itype-pill').forEach(p => {
            p.classList.toggle('active', p.querySelector('input').value === itype);
        });
        smUpdateIconHint(itype);
        smItemPreview();
    }

    function smUpdateIconHint(itype) {
        const hints = {
            ph: 'Nama class Phosphor — contoh: <code style="background:var(--hover);padding:0 5px;border-radius:3px">ph-lightning</code>',
            fa: 'Class Font Awesome — contoh: <code style="background:var(--hover);padding:0 5px;border-radius:3px">fas fa-home</code>',
            img: 'URL lengkap gambar JPG/PNG/SVG',
        };
        document.getElementById('smItemIconHint').innerHTML = hints[itype] || '';
        document.getElementById('smItemIconVal').placeholder = {
            ph: 'ph-star',
            fa: 'fas fa-home',
            img: 'https://…'
        } [itype] || '';
    }

    function smItemPreview() {
        const itype = document.querySelector('input[name="smIconType"]:checked')?.value || 'ph';
        const ival = document.getElementById('smItemIconVal').value.trim();
        const ibg = document.getElementById('smItemBg').value || '#dbeafe';
        const ifg = document.getElementById('smItemFg').value || '#3b82f6';
        const badge = document.getElementById('smItemBadge').value.trim();
        const bclr = document.getElementById('smItemBadgeColor').value;

        const box = document.getElementById('smItemPrevBox');
        if (!box) return;
        box.style.background = ibg;

        let inner = `<i class="ph ph-image-square" style="font-size:26px;color:#94a3b8"></i>`;
        if (ival) {
            if (itype === 'ph') inner = `<i class="ph ${esc(ival)}" style="font-size:26px;color:${esc(ifg)}"></i>`;
            if (itype === 'fa') inner = `<i class="${esc(ival)}" style="font-size:22px;color:${esc(ifg)}"></i>`;
            if (itype === 'img') inner = `<img src="${esc(ival)}" style="width:32px;height:32px;object-fit:contain" onerror="this.style.display='none'"/>`;
        }
        if (badge) {
            inner += `<div style="position:absolute;top:-5px;right:-5px;background:${esc(bclr)};color:#fff;font-size:7px;font-weight:800;padding:1px 5px;border-radius:99px;line-height:1.5">${esc(badge)}</div>`;
        }
        box.innerHTML = inner;
    }

    function smItemPreviewName() {
        const el = document.getElementById('smItemPrevName');
        if (el) el.textContent = document.getElementById('smItemName').value || 'Nama Item';
    }

    function smPickBg(c) {
        document.getElementById('smItemBg').value = c;
        document.getElementById('smItemBgHex').value = c;
        smItemPreview();
    }

    function smPickFg(c) {
        document.getElementById('smItemFg').value = c;
        document.getElementById('smItemFgHex').value = c;
        smItemPreview();
    }

    function smSaveItem() {
        const id = document.getElementById('smItemId').value;
        const category_id = document.getElementById('smItemCat').value;
        const name = document.getElementById('smItemName').value.trim();
        const icon_type = document.querySelector('input[name="smIconType"]:checked')?.value || 'ph';
        const icon_value = document.getElementById('smItemIconVal').value.trim();
        const icon_bg = document.getElementById('smItemBg').value;
        const icon_color = document.getElementById('smItemFg').value;
        const href = document.getElementById('smItemHref').value.trim() || '#';
        const query_cat = document.getElementById('smItemQueryCat').value.trim();
        const badge = document.getElementById('smItemBadge').value.trim();
        const badge_color = document.getElementById('smItemBadgeColor').value;
        const sort_order = document.getElementById('smItemSort').value;
        const is_active = document.getElementById('smItemActive').checked ? 1 : 0;

        if (!name) return smToast('Nama item wajib diisi.', true);
        if (!category_id) return smToast('Pilih kategori.', true);

        const btn = document.getElementById('smItemSaveBtn');
        btn.disabled = true;

        smPost({
                action: 'save_item',
                id,
                category_id,
                name,
                icon_type,
                icon_value,
                icon_bg,
                icon_color,
                href,
                query_cat,
                badge,
                badge_color,
                sort_order,
                ...(is_active ? {
                    is_active: 1
                } : {})
            })
            .then(r => {
                btn.disabled = false;
                if (!r.ok) return smToast(r.msg || 'Gagal menyimpan.', true);
                smModalItem.hide();
                smToast(+id ? `Item "${name}" diperbarui.` : `Item "${name}" ditambahkan.`);
                setTimeout(() => location.reload(), 500);
            });
    }

    // ══ HELPERS ════════════════════════════════════════════════════
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

    function smToast(msg, err = false) {
        const wrap = document.getElementById('smToastWrap');
        if (!wrap) return;
        const t = document.createElement('div');
        t.className = `toast-item ${err ? 'toast-err' : 'toast-ok'}`;
        t.innerHTML = `<i class="ph ${err?'ph-warning-circle':'ph-check-circle'}" style="font-size:18px;flex-shrink:0"></i>${msg}`;
        wrap.appendChild(t);
        setTimeout(() => t.style.opacity = '0', 3000);
        setTimeout(() => t.remove(), 3500);
    }

    function smSwAnim(inp) {
        const track = inp.nextElementSibling;
        if (!track) return;
        track.style.background = inp.checked ? '#3b82f6' : 'rgba(255,255,255,.12)';
        const dot = track.querySelector('.sm-sw-dot');
        if (dot) dot.style.left = inp.checked ? '21px' : '3px';
    }

    function esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Init toggle states on load
    document.querySelectorAll('.sm-sw-wrap input').forEach(smSwAnim);
</script>
<?php
require_once __DIR__ . '/includes/footer.php';
?>