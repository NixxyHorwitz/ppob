<?php
// backoffice/promo-banners.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Promo Banners';
$active_menu = 'promo_banners';

$toast = '';
$toast_e = '';
$action = $_POST['action'] ?? '';

/* ═══════════════════════
   ACTIONS
═══════════════════════ */
if ($action === 'add') {
    $title    = trim($_POST['title']          ?? '');
    $subtitle = trim($_POST['subtitle']       ?? '');
    $tag      = trim($_POST['tag_label']      ?? '');
    $img      = trim($_POST['image_url']      ?? '') ?: null;
    $cs       = trim($_POST['bg_color_start'] ?? '#01d298');
    $ce       = trim($_POST['bg_color_end']   ?? '#00b07e');
    $emoji    = trim($_POST['emoji_icon']     ?? '⚡');
    $href     = trim($_POST['href']           ?? '#');
    $so       = (int)($_POST['sort_order']    ?? 0);
    $ia       = isset($_POST['is_active'])    ? 1 : 0;

    if (!$title) {
        $toast_e = 'Judul banner wajib diisi.';
    } else {
        $pdo->prepare("INSERT INTO promo_banners(title,subtitle,tag_label,image_url,bg_color_start,bg_color_end,emoji_icon,href,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?,?)")
            ->execute([$title, $subtitle, $tag, $img, $cs, $ce, $emoji, $href, $so, $ia]);
        $toast = "Banner «{$title}» berhasil ditambahkan.";
    }
}

if ($action === 'edit' && !empty($_POST['id'])) {
    $id       = (int)$_POST['id'];
    $title    = trim($_POST['title']          ?? '');
    $subtitle = trim($_POST['subtitle']       ?? '');
    $tag      = trim($_POST['tag_label']      ?? '');
    $img      = trim($_POST['image_url']      ?? '') ?: null;
    $cs       = trim($_POST['bg_color_start'] ?? '#01d298');
    $ce       = trim($_POST['bg_color_end']   ?? '#00b07e');
    $emoji    = trim($_POST['emoji_icon']     ?? '⚡');
    $href     = trim($_POST['href']           ?? '#');
    $so       = (int)($_POST['sort_order']    ?? 0);
    $ia       = isset($_POST['is_active'])    ? 1 : 0;

    if (!$title) {
        $toast_e = 'Judul banner wajib diisi.';
    } else {
        $pdo->prepare("UPDATE promo_banners SET title=?,subtitle=?,tag_label=?,image_url=?,bg_color_start=?,bg_color_end=?,emoji_icon=?,href=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$title, $subtitle, $tag, $img, $cs, $ce, $emoji, $href, $so, $ia, $id]);
        $toast = 'Banner berhasil disimpan.';
    }
}

if ($action === 'toggle' && !empty($_POST['id'])) {
    $pdo->prepare("UPDATE promo_banners SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Status banner diubah.';
}

if ($action === 'delete' && !empty($_POST['id'])) {
    $pdo->prepare("DELETE FROM promo_banners WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Banner dihapus.';
}

if ($action === 'reorder' && !empty($_POST['ids'])) {
    $ids = array_map('intval', explode(',', $_POST['ids']));
    $s = $pdo->prepare("UPDATE promo_banners SET sort_order=? WHERE id=?");
    foreach ($ids as $i => $id) $s->execute([$i + 1, $id]);
    $toast = 'Urutan banner disimpan.';
}

/* ═══════════════════════
   FETCH
═══════════════════════ */
$banners = $pdo->query("SELECT * FROM promo_banners ORDER BY sort_order ASC, id ASC")->fetchAll();

$edit_data = null;
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM promo_banners WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit_data = $s->fetch();
}

$total_active   = array_sum(array_column($banners, 'is_active'));
$total_inactive = count($banners) - $total_active;
$total_with_img = count(array_filter($banners, fn($b) => !empty($b['image_url'])));

/* Preset gradient presets */
$gradients = [
    ['label' => 'Hijau Mint',  'start' => '#01d298', 'end' => '#00b07e'],
    ['label' => 'Orange Fire', 'start' => '#f97316', 'end' => '#ea580c'],
    ['label' => 'Biru Ocean',  'start' => '#3b82f6', 'end' => '#1d4ed8'],
    ['label' => 'Ungu Royal',  'start' => '#8b5cf6', 'end' => '#6d28d9'],
    ['label' => 'Pink Rose',   'start' => '#ec4899', 'end' => '#be185d'],
    ['label' => 'Kuning Gold', 'start' => '#f59e0b', 'end' => '#d97706'],
    ['label' => 'Merah Ruby',  'start' => '#ef4444', 'end' => '#dc2626'],
    ['label' => 'Cyan Aqua',   'start' => '#06b6d4', 'end' => '#0891b2'],
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Toast -->
<div class="toast-wrap">
    <?php if ($toast):  ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast)  ?></div><?php endif; ?>
    <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<style>
    /* ─── Banner card (mirrors the real app card) ──────────────── */
    .banner-card {
        border-radius: 18px;
        padding: 20px 22px;
        position: relative;
        overflow: hidden;
        min-height: 110px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        cursor: default;
        transition: transform .2s, box-shadow .2s;
        user-select: none;
    }

    .banner-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 40px rgba(0, 0, 0, .35);
    }

    .banner-card.inactive {
        opacity: .42;
        filter: grayscale(.5);
    }

    .banner-tag {
        font-size: 10px;
        font-weight: 700;
        color: rgba(255, 255, 255, .85);
        background: rgba(255, 255, 255, .18);
        padding: 2px 9px;
        border-radius: 99px;
        display: inline-block;
        margin-bottom: 6px;
        width: fit-content;
    }

    .banner-title {
        font-size: 17px;
        font-weight: 800;
        color: #fff;
        line-height: 1.25;
        margin-bottom: 4px;
        text-shadow: 0 1px 4px rgba(0, 0, 0, .15);
    }

    .banner-sub {
        font-size: 12px;
        color: rgba(255, 255, 255, .8);
        font-weight: 500;
    }

    .banner-emoji {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 52px;
        opacity: .25;
        line-height: 1;
        pointer-events: none;
    }

    .banner-img {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 45%;
        object-fit: cover;
        object-position: center;
        border-radius: 0 18px 18px 0;
        opacity: .7;
    }

    /* ─── Banner overlay controls ──────────────────────────────── */
    .banner-ctrl {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        gap: 5px;
        opacity: 0;
        transition: opacity .18s;
    }

    .banner-card:hover .banner-ctrl {
        opacity: 1;
    }

    .bctrl-btn {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: rgba(0, 0, 0, .45);
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255, 255, 255, .12);
        color: #fff;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background .15s;
    }

    .bctrl-btn:hover {
        background: rgba(0, 0, 0, .7);
        color: #fff;
    }

    /* ─── Sort number badge ────────────────────────────────────── */
    .banner-order {
        position: absolute;
        top: 10px;
        left: 10px;
        width: 22px;
        height: 22px;
        border-radius: 6px;
        background: rgba(0, 0, 0, .4);
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255, 255, 255, .12);
        font-size: 10px;
        font-weight: 700;
        color: rgba(255, 255, 255, .7);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ─── Live preview card (in form) ──────────────────────────── */
    .preview-banner {
        border-radius: 16px;
        padding: 18px 20px;
        position: relative;
        overflow: hidden;
        min-height: 100px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin-bottom: 20px;
        transition: all .2s;
    }

    .preview-tag {
        font-size: 10px;
        font-weight: 700;
        color: rgba(255, 255, 255, .85);
        background: rgba(255, 255, 255, .18);
        padding: 2px 8px;
        border-radius: 99px;
        display: inline-block;
        margin-bottom: 5px;
        width: fit-content;
    }

    .preview-title {
        font-size: 16px;
        font-weight: 800;
        color: #fff;
        margin-bottom: 3px;
    }

    .preview-sub {
        font-size: 11px;
        color: rgba(255, 255, 255, .8);
    }

    .preview-emoji {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 46px;
        opacity: .25;
        pointer-events: none;
    }

    .preview-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: var(--mut);
        margin-bottom: 8px;
    }

    /* ─── Gradient presets ─────────────────────────────────────── */
    .grad-presets {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px;
    }

    .grad-preset {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all .15s;
        flex-shrink: 0;
    }

    .grad-preset:hover,
    .grad-preset.active {
        border-color: #fff;
        transform: scale(1.12);
    }

    /* ─── Color combos ─────────────────────────────────────────── */
    .col-pair {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .col-field {
        flex: 1;
    }

    .col-swatch {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        cursor: pointer;
        border: 1px solid var(--border);
        padding: 3px;
        background: none;
    }

    .col-txt {
        font-family: 'JetBrains Mono', monospace;
        font-size: 12.5px;
    }

    /* ─── Emoji picker ─────────────────────────────────────────── */
    .emoji-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 7px;
    }

    .emoji-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--hover);
        border: 1px solid var(--border);
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .12s;
    }

    .emoji-btn:hover,
    .emoji-btn.active {
        background: var(--as);
        border-color: var(--accent);
        transform: scale(1.1);
    }

    /* ─── Form fields ──────────────────────────────────────────── */
    .f-lbl {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--sub);
        display: block;
        margin-bottom: 6px;
    }

    .fml {
        margin-bottom: 16px;
    }

    /* ─── Drag handle ──────────────────────────────────────────── */
    .drag-handle {
        position: absolute;
        top: 50%;
        left: -4px;
        transform: translateY(-50%);
        color: var(--mut);
        font-size: 18px;
        cursor: grab;
        opacity: 0;
        transition: opacity .15s;
    }

    .banner-wrap:hover .drag-handle {
        opacity: 1;
    }

    .banner-wrap {
        position: relative;
        padding-left: 18px;
    }

    .banner-wrap.dragging .banner-card {
        opacity: .4;
        outline: 2px dashed var(--accent);
    }

    /* ─── Toggle switch ────────────────────────────────────────── */
    .fmchk {
        appearance: none;
        width: 40px;
        height: 22px;
        border-radius: 99px;
        background: var(--hover);
        border: 1.5px solid var(--border);
        cursor: pointer;
        position: relative;
        transition: all .2s;
        flex-shrink: 0;
    }

    .fmchk::after {
        content: '';
        position: absolute;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #fff;
        top: 50%;
        left: 3px;
        transform: translateY(-50%);
        transition: left .2s;
    }

    .fmchk:checked {
        background: var(--accent);
        border-color: var(--accent);
    }

    .fmchk:checked::after {
        left: 21px;
    }

    .tog-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        background: rgba(255, 255, 255, .03);
        border: 1px solid var(--border);
        border-radius: 9px;
        margin-bottom: 10px;
    }
</style>

<!-- Page header -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 style="display:flex;align-items:center;gap:10px">
            <span style="width:36px;height:36px;background:linear-gradient(135deg,#f97316,#ec4899);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:18px">🎨</span>
            Promo Banners
        </h1>
        <nav>
            <ol class="breadcrumb bc">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Promo Banners</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" style="border-radius:8px"
        onclick="openAddForm()">
        <i class="ph ph-plus me-1"></i>Banner Baru
    </button>
</div>

<!-- Stat strip -->
<div class="d-flex gap-3 mb-4 flex-wrap">
    <?php
    $strips = [
        ['Total Banner',   count($banners),    '#3b82f6', 'ph-images'],
        ['Aktif',          $total_active,      '#10b981', 'ph-check-circle'],
        ['Nonaktif',       $total_inactive,    '#ef4444', 'ph-x-circle'],
        ['Pakai Gambar',   $total_with_img,    '#f59e0b', 'ph-image'],
    ];
    foreach ($strips as [$lbl, $val, $clr, $ico]): ?>
        <div style="flex:1;min-width:120px;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px">
            <div style="width:36px;height:36px;border-radius:9px;background:<?= $clr ?>22;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="ph <?= $ico ?>" style="font-size:18px;color:<?= $clr ?>"></i>
            </div>
            <div>
                <div style="font-size:20px;font-weight:800;line-height:1"><?= $val ?></div>
                <div style="font-size:11px;color:var(--mut);margin-top:2px"><?= $lbl ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">

    <!-- ══ LEFT: Banner list ══════════════════════════════════════ -->
    <div class="col-xl-7">
        <div class="card-c">
            <div class="ch">
                <div>
                    <p class="ct">Daftar Banner</p>
                    <p class="cs">Drag ↕ untuk mengubah urutan tampil</p>
                </div>
                <?php if (count($banners) > 1): ?>
                    <button id="btn-save-order" style="display:none;border-radius:7px;background:var(--oks);border:1px solid rgba(16,185,129,.2);color:var(--ok);font-size:12px;padding:6px 14px;cursor:pointer;font-weight:600"
                        onclick="saveOrder()">
                        <i class="ph ph-floppy-disk me-1"></i>Simpan Urutan
                    </button>
                <?php endif; ?>
            </div>
            <div class="cb">
                <?php if (empty($banners)): ?>
                    <div style="text-align:center;padding:48px 20px;color:var(--mut)">
                        <div style="font-size:48px;opacity:.2;margin-bottom:12px">🎨</div>
                        <div style="font-weight:600;margin-bottom:4px">Belum ada banner</div>
                        <div style="font-size:12px">Klik "Banner Baru" untuk mulai</div>
                    </div>
                <?php else: ?>
                    <div id="banner-list" style="display:flex;flex-direction:column;gap:12px">
                        <?php foreach ($banners as $b): ?>
                            <div class="banner-wrap" data-id="<?= $b['id'] ?>" draggable="true">
                                <i class="ph ph-dots-six-vertical drag-handle"></i>

                                <div class="banner-card <?= !$b['is_active'] ? 'inactive' : '' ?>"
                                    style="background:linear-gradient(135deg,<?= htmlspecialchars($b['bg_color_start']) ?>,<?= htmlspecialchars($b['bg_color_end']) ?>)">

                                    <?php if (!empty($b['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($b['image_url']) ?>" alt="" class="banner-img"
                                            onerror="this.style.display='none'" />
                                    <?php else: ?>
                                        <div class="banner-emoji"><?= htmlspecialchars($b['emoji_icon'] ?? '⚡') ?></div>
                                    <?php endif; ?>

                                    <div class="banner-order"><?= $b['sort_order'] ?></div>

                                    <?php if (!empty($b['tag_label'])): ?>
                                        <div class="banner-tag"><?= htmlspecialchars($b['tag_label']) ?></div>
                                    <?php endif; ?>
                                    <div class="banner-title"><?= htmlspecialchars($b['title']) ?></div>
                                    <?php if (!empty($b['subtitle'])): ?>
                                        <div class="banner-sub"><?= htmlspecialchars($b['subtitle']) ?></div>
                                    <?php endif; ?>

                                    <!-- Controls (visible on hover) -->
                                    <div class="banner-ctrl">
                                        <!-- Toggle -->
                                        <form method="POST" style="display:contents">
                                            <input type="hidden" name="action" value="toggle" />
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>" />
                                            <button type="submit" class="bctrl-btn" title="<?= $b['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="ph <?= $b['is_active'] ? 'ph-eye-slash' : 'ph-eye' ?>"></i>
                                            </button>
                                        </form>
                                        <!-- Edit -->
                                        <a href="?edit=<?= $b['id'] ?>" class="bctrl-btn" title="Edit">
                                            <i class="ph ph-pencil-simple"></i>
                                        </a>
                                        <!-- Delete -->
                                        <form method="POST" style="display:contents"
                                            onsubmit="return confirm('Hapus banner «<?= addslashes(htmlspecialchars($b['title'])) ?>»?')">
                                            <input type="hidden" name="action" value="delete" />
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>" />
                                            <button type="submit" class="bctrl-btn" title="Hapus"
                                                style="background:rgba(239,68,68,.5)">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form method="POST" id="form-reorder" style="display:none">
                        <input type="hidden" name="action" value="reorder" />
                        <input type="hidden" name="ids" id="reorder-ids" />
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ RIGHT: Add / Edit form ════════════════════════════════ -->
    <div class="col-xl-5">
        <div id="form-panel" style="background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;position:sticky;top:calc(var(--hh,60px) + 20px)">

            <!-- Form header -->
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,rgba(249,115,22,.08),rgba(236,72,153,.06));display:flex;align-items:center;gap:12px">
                <span id="form-header-icon" style="font-size:22px"><?= $edit_data ? '✏️' : '🎨' ?></span>
                <div>
                    <div style="font-size:14px;font-weight:700" id="form-header-title"><?= $edit_data ? 'Edit Banner' : 'Banner Baru' ?></div>
                    <div style="font-size:11px;color:var(--mut)">Desain kartu promo yang menarik</div>
                </div>
                <?php if ($edit_data): ?>
                    <a href="promo-banners.php" style="margin-left:auto;width:28px;height:28px;border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--mut);display:flex;align-items:center;justify-content:center;text-decoration:none">
                        <i class="ph ph-x"></i>
                    </a>
                <?php endif; ?>
            </div>

            <div style="padding:20px;max-height:calc(100vh - 260px);overflow-y:auto">

                <!-- Live preview -->
                <div class="preview-label">Preview Langsung</div>
                <div id="prev-card" class="preview-banner"
                    style="background:linear-gradient(135deg,<?= htmlspecialchars($edit_data['bg_color_start'] ?? '#01d298') ?>,<?= htmlspecialchars($edit_data['bg_color_end'] ?? '#00b07e') ?>)">
                    <div id="prev-emoji" class="preview-emoji"><?= htmlspecialchars($edit_data['emoji_icon'] ?? '⚡') ?></div>
                    <?php if (!empty($edit_data['tag_label'])): ?>
                        <div id="prev-tag" class="preview-tag"><?= htmlspecialchars($edit_data['tag_label'] ?? '') ?></div>
                    <?php else: ?>
                        <div id="prev-tag" class="preview-tag" style="display:none"><?= htmlspecialchars($edit_data['tag_label'] ?? '') ?></div>
                    <?php endif; ?>
                    <div id="prev-title" class="preview-title"><?= htmlspecialchars($edit_data['title'] ?? 'Judul Banner') ?></div>
                    <div id="prev-sub" class="preview-sub"><?= htmlspecialchars($edit_data['subtitle'] ?? 'Subtitle / deskripsi singkat') ?></div>
                </div>

                <form method="POST" id="main-form">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>" />
                    <?php if ($edit_data): ?><input type="hidden" name="id" value="<?= $edit_data['id'] ?>"><?php endif; ?>

                    <!-- Title -->
                    <div class="fml">
                        <label class="f-lbl">Judul *</label>
                        <input type="text" name="title" id="inp-title" class="fi" required
                            placeholder="Cashback Token PLN 5%"
                            oninput="liveUpdate()"
                            value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" />
                    </div>

                    <!-- Subtitle -->
                    <div class="fml">
                        <label class="f-lbl">Subtitle</label>
                        <input type="text" name="subtitle" id="inp-sub" class="fi"
                            placeholder="Tanpa minimum pembelian"
                            oninput="liveUpdate()"
                            value="<?= htmlspecialchars($edit_data['subtitle'] ?? '') ?>" />
                    </div>

                    <!-- Tag label -->
                    <div class="fml">
                        <label class="f-lbl">Tag Label <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--mut)">(di atas judul)</span></label>
                        <input type="text" name="tag_label" id="inp-tag" class="fi"
                            placeholder="🎉 Promo Hari Ini"
                            oninput="liveUpdate()"
                            value="<?= htmlspecialchars($edit_data['tag_label'] ?? '') ?>" />
                    </div>

                    <!-- Emoji icon -->
                    <div class="fml">
                        <label class="f-lbl">Emoji Dekorasi <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--mut)">(kanan card, jika tanpa gambar)</span></label>
                        <div style="display:flex;gap:8px;align-items:center">
                            <input type="text" name="emoji_icon" id="inp-emoji" class="fi" style="flex:1;font-size:20px"
                                placeholder="⚡" oninput="liveUpdate()"
                                value="<?= htmlspecialchars($edit_data['emoji_icon'] ?? '⚡') ?>" />
                            <div id="emoji-big" style="font-size:32px;line-height:1;flex-shrink:0"><?= htmlspecialchars($edit_data['emoji_icon'] ?? '⚡') ?></div>
                        </div>
                        <div class="emoji-grid" id="emoji-picker">
                            <?php foreach (['⚡', '🎁', '💰', '🎉', '🔥', '✨', '💎', '🚀', '🌟', '🎯', '🏆', '💫', '🎊', '🎈', '💥', '🛍️', '💸', '🎪', '🎠', '🌈'] as $em): ?>
                                <button type="button" class="emoji-btn <?= ($edit_data['emoji_icon'] ?? '⚡') === $em ? 'active' : '' ?>"
                                    onclick="pickEmoji('<?= $em ?>')"><?= $em ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Gradient colors -->
                    <div class="fml">
                        <label class="f-lbl">Warna Gradient Background</label>
                        <!-- Presets -->
                        <div class="grad-presets" id="grad-presets">
                            <?php foreach ($gradients as $g): ?>
                                <div class="grad-preset <?= ($edit_data['bg_color_start'] ?? '#01d298') === $g['start'] && ($edit_data['bg_color_end'] ?? '#00b07e') === $g['end'] ? 'active' : '' ?>"
                                    style="background:linear-gradient(135deg,<?= $g['start'] ?>,<?= $g['end'] ?>)"
                                    title="<?= $g['label'] ?>"
                                    onclick="applyGradient('<?= $g['start'] ?>','<?= $g['end'] ?>')"></div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Custom pickers -->
                        <div class="col-pair" style="margin-top:12px">
                            <div class="col-field">
                                <div style="font-size:10px;color:var(--mut);margin-bottom:4px;font-weight:600">Mulai</div>
                                <div style="display:flex;gap:6px;align-items:center">
                                    <input type="color" name="bg_color_start" id="cp-cs" class="col-swatch"
                                        value="<?= htmlspecialchars($edit_data['bg_color_start'] ?? '#01d298') ?>"
                                        oninput="syncHex('cs');liveUpdate()" />
                                    <input type="text" id="ch-cs" class="fi col-txt" style="flex:1;font-size:12px;padding:8px 10px"
                                        value="<?= htmlspecialchars($edit_data['bg_color_start'] ?? '#01d298') ?>" maxlength="7"
                                        oninput="syncPicker('cs');liveUpdate()" />
                                </div>
                            </div>
                            <div style="display:flex;align-items:flex-end;padding-bottom:10px;color:var(--mut);font-size:14px">→</div>
                            <div class="col-field">
                                <div style="font-size:10px;color:var(--mut);margin-bottom:4px;font-weight:600">Akhir</div>
                                <div style="display:flex;gap:6px;align-items:center">
                                    <input type="color" name="bg_color_end" id="cp-ce" class="col-swatch"
                                        value="<?= htmlspecialchars($edit_data['bg_color_end'] ?? '#00b07e') ?>"
                                        oninput="syncHex('ce');liveUpdate()" />
                                    <input type="text" id="ch-ce" class="fi col-txt" style="flex:1;font-size:12px;padding:8px 10px"
                                        value="<?= htmlspecialchars($edit_data['bg_color_end'] ?? '#00b07e') ?>" maxlength="7"
                                        oninput="syncPicker('ce');liveUpdate()" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image URL -->
                    <div class="fml">
                        <label class="f-lbl">URL Gambar <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--mut)">(opsional, menggantikan emoji)</span></label>
                        <input type="url" name="image_url" id="inp-img" class="fi"
                            placeholder="https://cdn.example.com/banner.jpg"
                            value="<?= htmlspecialchars($edit_data['image_url'] ?? '') ?>" />
                    </div>

                    <!-- Href + Sort order -->
                    <div style="display:grid;grid-template-columns:1fr auto;gap:12px;margin-bottom:16px">
                        <div>
                            <label class="f-lbl">Link Tujuan</label>
                            <input type="text" name="href" class="fi" style="font-family:'JetBrains Mono',monospace"
                                placeholder="/modules/prabayar/pln"
                                value="<?= htmlspecialchars($edit_data['href'] ?? '#') ?>" />
                        </div>
                        <div>
                            <label class="f-lbl">Urutan</label>
                            <input type="number" name="sort_order" class="fi" min="0" style="width:72px"
                                value="<?= $edit_data['sort_order'] ?? count($banners) + 1 ?>" />
                        </div>
                    </div>

                    <!-- Active toggle -->
                    <label class="tog-row" style="cursor:pointer">
                        <div>
                            <div style="font-size:13px;font-weight:600">Aktif</div>
                            <div style="font-size:11px;color:var(--mut)">Tampilkan banner di aplikasi user</div>
                        </div>
                        <input type="checkbox" name="is_active" class="fmchk"
                            <?= !isset($edit_data) || !empty($edit_data['is_active']) ? 'checked' : '' ?>>
                    </label>

                    <!-- Submit -->
                    <div style="display:flex;gap:8px;margin-top:18px">
                        <?php if ($edit_data): ?>
                            <a href="promo-banners.php" class="btn btn-sm"
                                style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">
                                <i class="ph ph-x me-1"></i>Batal
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-sm btn-primary ms-auto" style="border-radius:8px;padding:8px 22px">
                            <i class="ph ph-<?= $edit_data ? 'floppy-disk' : 'plus' ?> me-1"></i>
                            <?= $edit_data ? 'Simpan Perubahan' : 'Tambah Banner' ?>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

<?php
$page_scripts = <<<'SCRIPT'
<script>
/* ── Live preview ──────────────────────────────────────────── */
function liveUpdate() {
  const title = document.getElementById('inp-title')?.value  || 'Judul Banner';
  const sub   = document.getElementById('inp-sub')?.value    || 'Subtitle / deskripsi singkat';
  const tag   = document.getElementById('inp-tag')?.value    || '';
  const emoji = document.getElementById('inp-emoji')?.value  || '⚡';
  const cs    = document.getElementById('cp-cs')?.value      || '#01d298';
  const ce    = document.getElementById('cp-ce')?.value      || '#00b07e';

  document.getElementById('prev-title').textContent = title;
  document.getElementById('prev-sub').textContent   = sub;

  const tagEl = document.getElementById('prev-tag');
  if (tagEl) { tagEl.textContent = tag; tagEl.style.display = tag ? 'inline-block' : 'none'; }

  const emojiEl = document.getElementById('prev-emoji');
  if (emojiEl) emojiEl.textContent = emoji;

  document.getElementById('emoji-big').textContent = emoji;

  const card = document.getElementById('prev-card');
  if (card) card.style.background = `linear-gradient(135deg, ${cs}, ${ce})`;
}

/* ── Emoji picker ──────────────────────────────────────────── */
function pickEmoji(em) {
  const inp = document.getElementById('inp-emoji');
  if (inp) inp.value = em;
  document.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('active'));
  event.target.closest('.emoji-btn')?.classList.add('active');
  liveUpdate();
}

/* ── Gradient presets ──────────────────────────────────────── */
function applyGradient(start, end) {
  const cs = document.getElementById('cp-cs'), ce = document.getElementById('cp-ce');
  const hs = document.getElementById('ch-cs'), he = document.getElementById('ch-ce');
  if (cs) cs.value = start; if (hs) hs.value = start;
  if (ce) ce.value = end;   if (he) he.value = end;
  document.querySelectorAll('.grad-preset').forEach(p => p.classList.remove('active'));
  event.target.classList.add('active');
  liveUpdate();
}

/* ── Color sync ────────────────────────────────────────────── */
function syncHex(k) {
  const p = document.getElementById('cp-' + k), h = document.getElementById('ch-' + k);
  if (p && h) h.value = p.value;
}
function syncPicker(k) {
  const h = document.getElementById('ch-' + k), p = document.getElementById('cp-' + k);
  if (h && p && /^#[0-9a-fA-F]{6}$/.test(h.value)) p.value = h.value;
}
['cs','ce'].forEach(k => {
  document.getElementById('cp-' + k)?.addEventListener('input', () => { syncHex(k); liveUpdate(); });
});

/* ── Add form scroll helper ────────────────────────────────── */
function openAddForm() {
  document.getElementById('form-panel')?.scrollIntoView({ behavior:'smooth', block:'start' });
  document.getElementById('inp-title')?.focus();
}

/* ── Drag-sort banners ─────────────────────────────────────── */
(function(){
  const list = document.getElementById('banner-list');
  if (!list) return;
  let dragged = null;

  list.querySelectorAll('.banner-wrap').forEach(row => {
    row.addEventListener('dragstart', function(e) {
      dragged = this;
      setTimeout(() => this.classList.add('dragging'), 0);
      e.dataTransfer.effectAllowed = 'move';
    });
    row.addEventListener('dragend', function() {
      this.classList.remove('dragging');
      document.getElementById('btn-save-order').style.display = 'inline-block';
    });
    row.addEventListener('dragover', function(e) {
      e.preventDefault();
      if (this === dragged) return;
      const rect = this.getBoundingClientRect();
      if (e.clientY < rect.top + rect.height / 2) {
        list.insertBefore(dragged, this);
      } else {
        list.insertBefore(dragged, this.nextSibling);
      }
    });
  });
})();

function saveOrder() {
  const ids = [...document.querySelectorAll('.banner-wrap')].map(r => r.dataset.id).join(',');
  document.getElementById('reorder-ids').value = ids;
  document.getElementById('form-reorder').submit();
}

/* ── Toast auto-dismiss ────────────────────────────────────── */
document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(16px)'; }, 3200);
  setTimeout(() => t.remove(), 3700);
});

liveUpdate();
</script>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>