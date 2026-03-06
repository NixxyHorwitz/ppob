<?php
// backoffice/frontend-manager.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Frontend Manager';
$active_menu = 'frontend_manager';

$toast = '';
$toast_e = '';
$act = $_POST['action'] ?? '';

/* ═══════════════════════════════════════════════════
   NAVBAR ACTIONS
═══════════════════════════════════════════════════ */
if ($act === 'nav_add') {
    $l = trim($_POST['label'] ?? '');
    $h = trim($_POST['href']  ?? '#');
    $ic = trim($_POST['icon_class'] ?? 'fas fa-circle');
    $mp = trim($_POST['match_path'] ?? '') ?: null;
    $so = (int)($_POST['sort_order'] ?? 0);
    $ct = isset($_POST['is_center']) ? 1 : 0;
    $ia = isset($_POST['is_active'])  ? 1 : 0;
    if (!$l) {
        $toast_e = 'Label wajib diisi.';
    } else {
        $pdo->prepare("INSERT INTO navbar_items(label,href,icon_class,is_center,match_path,sort_order,is_active) VALUES(?,?,?,?,?,?,?)")
            ->execute([$l, $h, $ic, $ct, $mp, $so, $ia]);
        $toast = "Navbar «{$l}» ditambahkan.";
    }
}
if ($act === 'nav_edit' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $l  = trim($_POST['label'] ?? '');
    $h  = trim($_POST['href']  ?? '#');
    $ic = trim($_POST['icon_class'] ?? 'fas fa-circle');
    $mp = trim($_POST['match_path'] ?? '') ?: null;
    $so = (int)($_POST['sort_order'] ?? 0);
    $ct = isset($_POST['is_center']) ? 1 : 0;
    $ia = isset($_POST['is_active'])  ? 1 : 0;
    if (!$l) {
        $toast_e = 'Label wajib diisi.';
    } else {
        $pdo->prepare("UPDATE navbar_items SET label=?,href=?,icon_class=?,is_center=?,match_path=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$l, $h, $ic, $ct, $mp, $so, $ia, $id]);
        $toast = 'Navbar item disimpan.';
    }
}
if ($act === 'nav_toggle' && !empty($_POST['id'])) {
    $pdo->prepare("UPDATE navbar_items SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Status diubah.';
}
if ($act === 'nav_delete' && !empty($_POST['id'])) {
    $pdo->prepare("DELETE FROM navbar_items WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Item dihapus.';
}

/* ═══════════════════════════════════════════════════
   QUICK ACTIONS
═══════════════════════════════════════════════════ */
if ($act === 'qa_add') {
    $l  = trim($_POST['label'] ?? '');
    $h  = trim($_POST['href']  ?? '#');
    $ic = trim($_POST['icon_class'] ?? 'fas fa-circle');
    $so = (int)($_POST['sort_order'] ?? 0);
    $ia = isset($_POST['is_active']) ? 1 : 0;
    if (!$l) {
        $toast_e = 'Label wajib diisi.';
    } else {
        $pdo->prepare("INSERT INTO quick_actions(label,href,icon_class,sort_order,is_active) VALUES(?,?,?,?,?)")
            ->execute([$l, $h, $ic, $so, $ia]);
        $toast = "Quick action «{$l}» ditambahkan.";
    }
}
if ($act === 'qa_edit' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $l  = trim($_POST['label'] ?? '');
    $h  = trim($_POST['href']  ?? '#');
    $ic = trim($_POST['icon_class'] ?? 'fas fa-circle');
    $so = (int)($_POST['sort_order'] ?? 0);
    $ia = isset($_POST['is_active']) ? 1 : 0;
    if (!$l) {
        $toast_e = 'Label wajib diisi.';
    } else {
        $pdo->prepare("UPDATE quick_actions SET label=?,href=?,icon_class=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$l, $h, $ic, $so, $ia, $id]);
        $toast = 'Quick action disimpan.';
    }
}
if ($act === 'qa_toggle' && !empty($_POST['id'])) {
    $pdo->prepare("UPDATE quick_actions SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Status diubah.';
}
if ($act === 'qa_delete' && !empty($_POST['id'])) {
    $pdo->prepare("DELETE FROM quick_actions WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Item dihapus.';
}

/* ═══════════════════════════════════════════════════
   RUNNING TEXT
═══════════════════════════════════════════════════ */
if ($act === 'rt_add') {
    $c    = trim($_POST['content']      ?? '');
    $ic   = trim($_POST['icon_class']   ?? 'fas fa-bolt');
    $icol = trim($_POST['icon_color']   ?? '#01d298');
    $tcol = trim($_POST['text_color']   ?? '#0f172a');
    $bgc  = trim($_POST['bg_color']     ?? '#ffffff');
    $brc  = trim($_POST['border_color'] ?? '#e2e8f0');
    $sp   = max(5, min(120, (int)($_POST['speed']      ?? 35)));
    $so   = (int)($_POST['sort_order']  ?? 0);
    $ia   = isset($_POST['is_active'])  ? 1 : 0;
    if (!$c) {
        $toast_e = 'Konten wajib diisi.';
    } else {
        $pdo->prepare("INSERT INTO running_text(content,icon_class,icon_color,text_color,bg_color,border_color,speed,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?)")
            ->execute([$c, $ic, $icol, $tcol, $bgc, $brc, $sp, $so, $ia]);
        $toast = 'Running text ditambahkan.';
    }
}
if ($act === 'rt_edit' && !empty($_POST['id'])) {
    $id   = (int)$_POST['id'];
    $c    = trim($_POST['content']      ?? '');
    $ic   = trim($_POST['icon_class']   ?? 'fas fa-bolt');
    $icol = trim($_POST['icon_color']   ?? '#01d298');
    $tcol = trim($_POST['text_color']   ?? '#0f172a');
    $bgc  = trim($_POST['bg_color']     ?? '#ffffff');
    $brc  = trim($_POST['border_color'] ?? '#e2e8f0');
    $sp   = max(5, min(120, (int)($_POST['speed']      ?? 35)));
    $so   = (int)($_POST['sort_order']  ?? 0);
    $ia   = isset($_POST['is_active'])  ? 1 : 0;
    if (!$c) {
        $toast_e = 'Konten wajib diisi.';
    } else {
        $pdo->prepare("UPDATE running_text SET content=?,icon_class=?,icon_color=?,text_color=?,bg_color=?,border_color=?,speed=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$c, $ic, $icol, $tcol, $bgc, $brc, $sp, $so, $ia, $id]);
        $toast = 'Running text disimpan.';
    }
}
if ($act === 'rt_toggle' && !empty($_POST['id'])) {
    $pdo->prepare("UPDATE running_text SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Status diubah.';
}
if ($act === 'rt_delete' && !empty($_POST['id'])) {
    $pdo->prepare("DELETE FROM running_text WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Running text dihapus.';
}

/* ── GLOBAL SPEED ──────────────────────────────────────────── */
if ($act === 'rt_speed_global' && isset($_POST['speed'])) {
    $sp = max(5, min(120, (int)$_POST['speed']));
    $pdo->exec("UPDATE running_text SET speed = {$sp}");
    $toast = "Kecepatan semua running text diubah ke {$sp}s.";
}

/* ── REORDER ───────────────────────────────────────────────── */
if ($act === 'nav_reorder' && !empty($_POST['ids'])) {
    $ids = array_map('intval', explode(',', $_POST['ids']));
    $s = $pdo->prepare("UPDATE navbar_items SET sort_order=? WHERE id=?");
    foreach ($ids as $i => $id) $s->execute([$i + 1, $id]);
    $toast = 'Urutan navbar disimpan.';
}
if ($act === 'qa_reorder' && !empty($_POST['ids'])) {
    $ids = array_map('intval', explode(',', $_POST['ids']));
    $s = $pdo->prepare("UPDATE quick_actions SET sort_order=? WHERE id=?");
    foreach ($ids as $i => $id) $s->execute([$i + 1, $id]);
    $toast = 'Urutan quick actions disimpan.';
}
if ($act === 'rt_reorder' && !empty($_POST['ids'])) {
    $ids = array_map('intval', explode(',', $_POST['ids']));
    $s = $pdo->prepare("UPDATE running_text SET sort_order=? WHERE id=?");
    foreach ($ids as $i => $id) $s->execute([$i + 1, $id]);
    $toast = 'Urutan running text disimpan.';
}

/* ═══════════════════════════════════════════════════
   HERO BANNER ACTIONS
═══════════════════════════════════════════════════ */
function hb_collect(array $p): array
{
    $anim_valid = ['slide-left', 'slide-right', 'float', 'bounce', 'pulse', 'none', ''];
    return [
        'type'               => in_array($p['type'] ?? '', ['image_only', 'layout', 'image_center']) ? $p['type'] : 'layout',
        'bg_image'           => trim($p['bg_image']          ?? '') ?: null,
        'bg_color_start'     => trim($p['bg_color_start']    ?? '#0066cc'),
        'bg_color_end'       => trim($p['bg_color_end']      ?? '#0099ff'),
        'bg_gradient_angle'  => (int)($p['bg_gradient_angle'] ?? 135),
        'height'             => max(60, min(400, (int)($p['height'] ?? 160))),
        'img_left'           => trim($p['img_left']   ?? '') ?: null,
        'img_left_width'     => max(20, (int)($p['img_left_width']  ?? 90)),
        'img_left_anim'      => in_array($p['img_left_anim'] ?? '', $anim_valid)  ? ($p['img_left_anim'] ?? '') : '',
        'center_type'        => ($p['center_type'] ?? 'text') === 'image' ? 'image' : 'text',
        'title'              => trim($p['title']      ?? '') ?: null,
        'title_color'        => trim($p['title_color']       ?? '#ffffff'),
        'subtitle'           => trim($p['subtitle']   ?? '') ?: null,
        'subtitle_color'     => trim($p['subtitle_color']    ?? '#ffffffd9'),
        'center_image'       => trim($p['center_image']      ?? '') ?: null,
        'center_image_width' => max(20, (int)($p['center_image_width'] ?? 160)),
        'center_image_anim'  => in_array($p['center_image_anim'] ?? '', $anim_valid) ? ($p['center_image_anim'] ?? '') : '',
        'btn_text'           => trim($p['btn_text']   ?? '') ?: null,
        'btn_href'           => trim($p['btn_href']   ?? '#'),
        'btn_color'          => trim($p['btn_color']  ?? '#FFD700'),
        'btn_text_color'     => trim($p['btn_text_color']    ?? '#000000'),
        'btn_anim'           => in_array($p['btn_anim'] ?? '', $anim_valid) ? ($p['btn_anim'] ?? 'pulse') : 'pulse',
        'img_right'          => trim($p['img_right']  ?? '') ?: null,
        'img_right_width'    => max(20, (int)($p['img_right_width'] ?? 90)),
        'img_right_anim'     => in_array($p['img_right_anim'] ?? '', $anim_valid) ? ($p['img_right_anim'] ?? '') : '',
        'sort_order'         => (int)($p['sort_order'] ?? 0),
        'is_active'          => isset($p['is_active']) ? 1 : 0,
    ];
}
$HB_COLS = 'type,bg_image,bg_color_start,bg_color_end,bg_gradient_angle,height,
    img_left,img_left_width,img_left_anim,
    center_type,title,title_color,subtitle,subtitle_color,
    center_image,center_image_width,center_image_anim,
    btn_text,btn_href,btn_color,btn_text_color,btn_anim,
    img_right,img_right_width,img_right_anim,sort_order,is_active';

if ($act === 'hb_add') {
    $f = hb_collect($_POST);
    $pdo->prepare("INSERT INTO hero_banner ({$HB_COLS}) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute(array_values($f));
    $toast = 'Hero banner ditambahkan.';
}
if ($act === 'hb_edit' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $f = hb_collect($_POST);
    $pdo->prepare("UPDATE hero_banner SET
        type=?,bg_image=?,bg_color_start=?,bg_color_end=?,bg_gradient_angle=?,height=?,
        img_left=?,img_left_width=?,img_left_anim=?,
        center_type=?,title=?,title_color=?,subtitle=?,subtitle_color=?,
        center_image=?,center_image_width=?,center_image_anim=?,
        btn_text=?,btn_href=?,btn_color=?,btn_text_color=?,btn_anim=?,
        img_right=?,img_right_width=?,img_right_anim=?,sort_order=?,is_active=?
        WHERE id=?")
        ->execute([...array_values($f), $id]);
    $toast = 'Hero banner disimpan.';
}
if ($act === 'hb_toggle' && !empty($_POST['id'])) {
    $pdo->prepare("UPDATE hero_banner SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Status diubah.';
}
if ($act === 'hb_delete' && !empty($_POST['id'])) {
    $pdo->prepare("DELETE FROM hero_banner WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Banner dihapus.';
}
if ($act === 'hb_reorder' && !empty($_POST['ids'])) {
    $ids = array_map('intval', explode(',', $_POST['ids']));
    $s = $pdo->prepare("UPDATE hero_banner SET sort_order=? WHERE id=?");
    foreach ($ids as $i => $id) $s->execute([$i + 1, $id]);
    $toast = 'Urutan banner disimpan.';
}

/* ═══════════════════════════════════════════════════
   FETCH
═══════════════════════════════════════════════════ */
$navbars      = $pdo->query("SELECT * FROM navbar_items  ORDER BY sort_order, id")->fetchAll();
$qactions     = $pdo->query("SELECT * FROM quick_actions ORDER BY sort_order, id")->fetchAll();
$rtexts       = $pdo->query("SELECT * FROM running_text  ORDER BY sort_order, id")->fetchAll();
$hbanners     = $pdo->query("SELECT * FROM hero_banner   ORDER BY sort_order, id")->fetchAll();
$global_speed = !empty($rtexts) ? (int)$rtexts[0]['speed'] : 35;

// Edit targets from GET
$nav_edit = $qa_edit = $rt_edit = $hb_edit = null;
$open_tab = $_GET['tab'] ?? 'nav';   // nav | qa | rt | hb
$editing  = false;

if (!empty($_GET['nav_edit'])) {
    $s = $pdo->prepare("SELECT * FROM navbar_items WHERE id=?");
    $s->execute([(int)$_GET['nav_edit']]);
    $nav_edit = $s->fetch();
    $open_tab = 'nav';
    $editing = true;
}
if (!empty($_GET['qa_edit'])) {
    $s = $pdo->prepare("SELECT * FROM quick_actions WHERE id=?");
    $s->execute([(int)$_GET['qa_edit']]);
    $qa_edit = $s->fetch();
    $open_tab = 'qa';
    $editing = true;
}
if (!empty($_GET['rt_edit'])) {
    $s = $pdo->prepare("SELECT * FROM running_text WHERE id=?");
    $s->execute([(int)$_GET['rt_edit']]);
    $rt_edit = $s->fetch();
    $open_tab = 'rt';
    $editing = true;
}
if (!empty($_GET['hb_edit'])) {
    $s = $pdo->prepare("SELECT * FROM hero_banner WHERE id=?");
    $s->execute([(int)$_GET['hb_edit']]);
    $hb_edit = $s->fetch();
    $open_tab = 'hb';
    $editing = true;
}

// Default values for hero banner form
$HB_ANIM = ['none' => 'Tidak ada', 'slide-left' => 'Slide Kiri', 'slide-right' => 'Slide Kanan', 'float' => 'Float', 'bounce' => 'Bounce', 'pulse' => 'Pulse'];
$hb = $hb_edit ?: [
    'type' => 'layout',
    'bg_image' => '',
    'bg_color_start' => '#005bb5',
    'bg_color_end' => '#0099ff',
    'bg_gradient_angle' => 135,
    'height' => 160,
    'img_left' => '',
    'img_left_width' => 85,
    'img_left_anim' => 'slide-left',
    'center_type' => 'text',
    'title' => 'KLAIM HADIAH',
    'title_color' => '#ffffff',
    'subtitle' => '& Jutaan Rupiah',
    'subtitle_color' => 'rgba(255,255,255,0.85)',
    'center_image' => '',
    'center_image_width' => 160,
    'center_image_anim' => 'float',
    'btn_text' => 'SERBU',
    'btn_href' => '#',
    'btn_color' => '#FFD700',
    'btn_text_color' => '#000000',
    'btn_anim' => 'pulse',
    'img_right' => '',
    'img_right_width' => 85,
    'img_right_anim' => 'slide-right',
    'sort_order' => count($hbanners) + 1,
    'is_active' => 1,
];

// Icon pool
$icon_pool = [
    'fas fa-home',
    'fas fa-receipt',
    'fas fa-qrcode',
    'fas fa-wallet',
    'fas fa-user-circle',
    'fas fa-plus',
    'fas fa-paper-plane',
    'fas fa-hand-holding-dollar',
    'fas fa-clock-rotate-left',
    'fas fa-bolt',
    'fas fa-gift',
    'fas fa-bell',
    'fas fa-star',
    'fas fa-tag',
    'fas fa-shield-halved',
    'fas fa-chart-line',
    'fas fa-gear',
    'fas fa-headset',
    'fas fa-store',
    'fas fa-percent',
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Toast ─────────────────────────────────────────────────── -->
<div class="toast-wrap">
    <?php if ($toast):  ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast)  ?></div><?php endif; ?>
    <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════
     STYLES  —  purposely different from every other page:
     • No card-c / .tbl / .ab patterns
     • Three-panel workspace (sidebar nav + tree list + editor)
     • Full-height, viewport-filling layout
     • Monospace accents, code-like field styling
════════════════════════════════════════════════════════════ -->
<style>
    /* ── Workspace chrome ──────────────────────────────────────── */
    .ws {
        display: grid;
        grid-template-columns: 56px 260px 1fr;
        grid-template-rows: auto 1fr;
        height: calc(100vh - var(--hh, 60px) - 80px);
        min-height: 560px;
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        background: var(--bg-surface, #0f1623);
    }

    /* ── Icon rail ─────────────────────────────────────────────── */
    .ws-rail {
        grid-row: 1 / 3;
        background: #070b14;
        border-right: 1px solid rgba(255, 255, 255, .05);
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 0;
        gap: 2px;
        z-index: 10;
    }

    .rail-item {
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 9px;
        border: none;
        background: transparent;
        color: var(--mut, #64748b);
        font-size: 17px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .15s;
    }

    .rail-item:hover {
        background: rgba(255, 255, 255, .06);
        color: #e2e8f0;
    }

    .rail-item.active {
        background: rgba(59, 130, 246, .15);
        color: var(--accent, #3b82f6);
    }

    .rail-item .rtip {
        position: absolute;
        left: 46px;
        top: 50%;
        transform: translateY(-50%);
        background: #1a2540;
        border: 1px solid rgba(255, 255, 255, .08);
        color: #e2e8f0;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        padding: 3px 9px;
        border-radius: 6px;
        pointer-events: none;
        opacity: 0;
        transition: opacity .12s;
        z-index: 100;
    }

    .rail-item:hover .rtip {
        opacity: 1;
    }

    .rail-sep {
        width: 22px;
        height: 1px;
        background: rgba(255, 255, 255, .05);
        margin: 4px 0;
    }

    /* ── Tree sidebar ──────────────────────────────────────────── */
    .ws-tree {
        grid-row: 1 / 3;
        background: #0a0f1c;
        border-right: 1px solid rgba(255, 255, 255, .05);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .tree-topbar {
        height: 42px;
        border-bottom: 1px solid rgba(255, 255, 255, .05);
        display: flex;
        align-items: center;
        padding: 0 14px;
        gap: 8px;
        flex-shrink: 0;
    }

    .tree-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: rgba(255, 255, 255, .25);
        flex: 1;
    }

    .tree-add-btn {
        width: 22px;
        height: 22px;
        border-radius: 5px;
        background: rgba(59, 130, 246, .15);
        border: none;
        color: var(--accent, #3b82f6);
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .15s;
    }

    .tree-add-btn:hover {
        background: var(--accent, #3b82f6);
        color: #fff;
    }

    .tree-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 6px 0;
    }

    .tree-scroll::-webkit-scrollbar {
        width: 3px;
    }

    .tree-scroll::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, .08);
        border-radius: 3px;
    }

    /* ── Tree rows ─────────────────────────────────────────────── */
    .tree-row {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 7px 14px;
        cursor: pointer;
        border-left: 2px solid transparent;
        transition: background .1s;
        user-select: none;
    }

    .tree-row:hover {
        background: rgba(255, 255, 255, .04);
    }

    .tree-row.active {
        background: rgba(59, 130, 246, .08);
        border-left-color: var(--accent, #3b82f6);
    }

    .tree-row.dimmed {
        opacity: .38;
    }

    .tree-row-ico {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }

    .tree-row-body {
        flex: 1;
        min-width: 0;
    }

    .tree-row-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #c8d3e8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.25;
    }

    .tree-row-sub {
        font-size: 10px;
        color: rgba(255, 255, 255, .25);
        font-family: 'JetBrains Mono', monospace;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tree-pill {
        font-size: 9px;
        padding: 1px 5px;
        border-radius: 4px;
        font-weight: 700;
        flex-shrink: 0;
        line-height: 1.5;
    }

    .tp-on {
        background: rgba(16, 185, 129, .18);
        color: #34d399;
    }

    .tp-off {
        background: rgba(239, 68, 68, .15);
        color: #f87171;
    }

    .tp-ctr {
        background: rgba(124, 58, 237, .2);
        color: #c084fc;
    }

    .tree-del-btn {
        width: 20px;
        height: 20px;
        border-radius: 5px;
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, .2);
        font-size: 11px;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all .12s;
    }

    .tree-row:hover .tree-del-btn {
        display: flex;
    }

    .tree-del-btn:hover {
        background: rgba(239, 68, 68, .2);
        color: #f87171;
    }

    /* ── Editor pane ───────────────────────────────────────────── */
    .ws-editor {
        grid-row: 1 / 3;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: var(--bg-card, #131d30);
    }

    .editor-topbar {
        height: 42px;
        flex-shrink: 0;
        border-bottom: 1px solid rgba(255, 255, 255, .05);
        display: flex;
        align-items: center;
        padding: 0 20px;
        gap: 10px;
        background: rgba(0, 0, 0, .2);
    }

    .editor-crumb {
        font-size: 11px;
        color: rgba(255, 255, 255, .3);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .editor-crumb b {
        color: #c8d3e8;
        font-weight: 600;
    }

    .editor-body {
        flex: 1;
        overflow-y: auto;
        padding: 24px 28px;
    }

    .editor-body::-webkit-scrollbar {
        width: 4px;
    }

    .editor-body::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, .07);
        border-radius: 4px;
    }

    /* ── Form elements (intentionally code-editor style) ────────── */
    .ef-title {
        font-size: 16px;
        font-weight: 700;
        color: #e2e8f0;
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 4px;
    }

    .ef-sub {
        font-size: 11.5px;
        color: rgba(255, 255, 255, .3);
        margin-bottom: 22px;
        font-family: 'JetBrains Mono', monospace;
    }

    .ef-section-lbl {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: rgba(255, 255, 255, .2);
        margin: 0 0 8px;
        display: block;
        padding-bottom: 6px;
        border-bottom: 1px solid rgba(255, 255, 255, .04);
    }

    .ef-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 14px;
    }

    .ef-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 14px;
        margin-bottom: 14px;
    }

    .ef-grid-4 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 10px;
        margin-bottom: 14px;
    }

    .ef-field {
        display: flex;
        flex-direction: column;
    }

    .ef-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: rgba(255, 255, 255, .35);
        margin-bottom: 6px;
    }

    .ef-input {
        background: rgba(255, 255, 255, .04);
        border: 1px solid rgba(255, 255, 255, .08);
        color: #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 13px;
        font-family: inherit;
        transition: border-color .18s, background .18s;
        width: 100%;
    }

    .ef-input:focus {
        outline: none;
        border-color: var(--accent, #3b82f6);
        background: rgba(59, 130, 246, .05);
    }

    .ef-input::placeholder {
        color: rgba(255, 255, 255, .2);
    }

    .ef-input option {
        background: #131d30;
    }

    .ef-mono {
        font-family: 'JetBrains Mono', monospace;
        font-size: 12px;
        letter-spacing: .3px;
    }

    textarea.ef-input {
        resize: vertical;
    }

    /* ── Icon picker ───────────────────────────────────────────── */
    .ico-row {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .ico-preview {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        border: 1px solid rgba(255, 255, 255, .08);
        background: rgba(255, 255, 255, .05);
    }

    .ico-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 8px;
    }

    .ico-chip {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .07);
        color: rgba(255, 255, 255, .4);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        transition: all .12s;
    }

    .ico-chip:hover {
        background: rgba(59, 130, 246, .12);
        color: var(--accent, #3b82f6);
        border-color: rgba(59, 130, 246, .3);
    }

    .ico-chip.picked {
        background: rgba(59, 130, 246, .18);
        color: var(--accent, #3b82f6);
        border-color: var(--accent, #3b82f6);
    }

    /* ── Toggle switch ─────────────────────────────────────────── */
    .togrow {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 13px;
        background: rgba(255, 255, 255, .03);
        border: 1px solid rgba(255, 255, 255, .06);
        border-radius: 8px;
        margin-bottom: 8px;
        cursor: pointer;
    }

    .togrow:hover {
        background: rgba(255, 255, 255, .05);
    }

    .togrow-text>div:first-child {
        font-size: 13px;
        font-weight: 600;
        color: #c8d3e8;
    }

    .togrow-text>div:last-child {
        font-size: 11px;
        color: rgba(255, 255, 255, .28);
        margin-top: 1px;
    }

    .togswitch {
        appearance: none;
        width: 38px;
        height: 21px;
        border-radius: 99px;
        background: rgba(255, 255, 255, .1);
        border: 1.5px solid rgba(255, 255, 255, .1);
        cursor: pointer;
        position: relative;
        transition: all .2s;
        flex-shrink: 0;
    }

    .togswitch::after {
        content: '';
        position: absolute;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        background: #fff;
        top: 50%;
        left: 3px;
        transform: translateY(-50%);
        transition: left .2s;
    }

    .togswitch:checked {
        background: var(--accent, #3b82f6);
        border-color: var(--accent, #3b82f6);
    }

    .togswitch:checked::after {
        left: 19px;
    }

    /* ── Color combos ──────────────────────────────────────────── */
    .col-combo {
        display: flex;
        gap: 7px;
        align-items: center;
    }

    .col-picker {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, .1);
        padding: 3px;
        background: none;
        flex-shrink: 0;
    }

    .col-hex {
        font-family: 'JetBrains Mono', monospace;
        font-size: 12px;
    }

    /* ── Speed slider ──────────────────────────────────────────── */
    .speed-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .speed-row input[type=range] {
        flex: 1;
        accent-color: var(--accent, #3b82f6);
        height: 4px;
        cursor: pointer;
    }

    .speed-val {
        font-family: 'JetBrains Mono', monospace;
        font-size: 13px;
        color: var(--accent, #3b82f6);
        min-width: 32px;
        text-align: right;
        font-weight: 700;
    }

    /* ── RT live preview strip ─────────────────────────────────── */
    .rt-prev-wrap {
        border-radius: 9px;
        padding: 9px 14px;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    @keyframes ticker {
        0% {
            transform: translateX(100%)
        }

        100% {
            transform: translateX(-100%)
        }
    }

    .rt-ticker {
        display: inline-block;
        white-space: nowrap;
        animation: ticker linear infinite;
    }

    /* ── Action footer ─────────────────────────────────────────── */
    .ef-footer {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px solid rgba(255, 255, 255, .05);
    }

    .ef-btn {
        padding: 8px 18px;
        border-radius: 8px;
        border: none;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ef-btn-ghost {
        background: rgba(255, 255, 255, .06);
        border: 1px solid rgba(255, 255, 255, .08);
        color: rgba(255, 255, 255, .5);
        text-decoration: none;
    }

    .ef-btn-ghost:hover {
        background: rgba(255, 255, 255, .09);
        color: #e2e8f0;
    }

    .ef-btn-primary {
        background: var(--accent, #3b82f6);
        color: #fff;
    }

    .ef-btn-primary:hover {
        background: #2563eb;
    }

    /* ── Phone mock (preview) ──────────────────────────────────── */
    .phone-wrap {
        display: none;
        margin-bottom: 24px;
    }

    .phone-wrap.open {
        display: block;
    }

    .phone-device {
        width: 200px;
        background: #050a14;
        border-radius: 22px;
        padding: 8px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .7), inset 0 0 0 1px rgba(255, 255, 255, .07);
    }

    .phone-screen {
        background: #f1f5f9;
        border-radius: 16px;
        height: 100px;
        position: relative;
        overflow: hidden;
    }

    .phone-nb {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: #fff;
        border-top: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding: 5px 2px 4px;
    }

    .pnav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        flex: 1;
    }

    .pnav-lbl {
        font-size: 8px;
        font-weight: 600;
        color: #64748b;
    }

    .pnav-center {
        position: relative;
        top: -12px;
    }

    .pnav-center-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #01d298, #06b6d4);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(1, 210, 152, .35);
    }

    .qa-preview-grid {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 14px;
    }

    .qa-pv-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }

    .qa-pv-ico {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: linear-gradient(135deg, #01d298, #06b6d4);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
    }

    .qa-pv-lbl {
        font-size: 9px;
        color: rgba(255, 255, 255, .4);
        font-weight: 600;
    }

    .rt-pv-strip {
        border-radius: 8px;
        padding: 7px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        overflow: hidden;
        margin-top: 10px;
    }

    /* ── Drag handle in tree ───────────────────────────────────── */
    .tree-drag-handle {
        font-size: 14px;
        color: rgba(255, 255, 255, .15);
        cursor: grab;
        flex-shrink: 0;
        padding: 2px 0;
        transition: color .12s;
    }

    .tree-row:hover .tree-drag-handle {
        color: rgba(255, 255, 255, .4);
    }

    .tree-row.drag-over {
        background: rgba(59, 130, 246, .12);
        border-left-color: var(--accent, #3b82f6);
    }

    .tree-row.dragging-src {
        opacity: .35;
    }

    /* ── Reorder save button ───────────────────────────────────── */
    .tree-save-btn {
        width: 22px;
        height: 22px;
        border-radius: 5px;
        background: rgba(16, 185, 129, .15);
        border: 1px solid rgba(16, 185, 129, .25);
        color: #34d399;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse-save 1.5s ease-in-out infinite;
    }

    @keyframes pulse-save {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .6
        }
    }

    .ef-empty {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, .2);
        text-align: center;
        padding: 40px;
    }

    .ef-empty-icon {
        font-size: 48px;
        margin-bottom: 14px;
        opacity: .15;
    }

    /* ── Hero Banner live preview ──────────────────────────────── */
    .hb-prev {
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 16px;
        position: relative;
        gap: 8px;
        transition: all .25s;
    }

    .hb-prev-left,
    .hb-prev-right {
        flex-shrink: 0;
        display: flex;
        align-items: flex-end;
    }

    .hb-prev-left img,
    .hb-prev-right img {
        object-fit: contain;
        display: block;
        filter: drop-shadow(0 4px 12px rgba(0, 0, 0, .3));
    }

    .hb-prev-center {
        flex: 1;
        text-align: center;
        padding: 0 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    .hb-prev-title {
        font-size: 18px;
        font-weight: 900;
        line-height: 1.1;
        text-shadow: 0 1px 6px rgba(0, 0, 0, .25);
    }

    .hb-prev-sub {
        font-size: 12px;
        font-weight: 500;
        opacity: .9;
    }

    .hb-prev-btn {
        display: inline-block;
        padding: 5px 16px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .5px;
        cursor: pointer;
        border: none;
        box-shadow: 0 3px 10px rgba(0, 0, 0, .2);
    }

    @keyframes hb-pulse {

        0%,
        100% {
            transform: scale(1)
        }

        50% {
            transform: scale(1.06)
        }
    }

    @keyframes hb-bounce {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-5px)
        }
    }

    @keyframes hb-slide-l {
        from {
            opacity: 0;
            transform: translateX(-22px)
        }

        to {
            opacity: 1;
            transform: none
        }
    }

    @keyframes hb-slide-r {
        from {
            opacity: 0;
            transform: translateX(22px)
        }

        to {
            opacity: 1;
            transform: none
        }
    }

    @keyframes hb-float {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-6px)
        }
    }

    .anim-pulse {
        animation: hb-pulse 2s ease-in-out infinite;
    }

    .anim-bounce {
        animation: hb-bounce 1.6s ease-in-out infinite;
    }

    .anim-slide-left {
        animation: hb-slide-l .6s ease forwards;
    }

    .anim-slide-right {
        animation: hb-slide-r .6s ease forwards;
    }

    .anim-float {
        animation: hb-float 3s ease-in-out infinite;
    }

    /* type selector tabs */
    .hb-type-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 16px;
    }

    .hb-type-tab {
        flex: 1;
        padding: 7px 4px;
        border-radius: 8px;
        cursor: pointer;
        border: none;
        background: rgba(255, 255, 255, .04);
        border: 1px solid rgba(255, 255, 255, .07);
        color: rgba(255, 255, 255, .4);
        font-size: 10px;
        font-weight: 700;
        text-align: center;
        transition: all .15s;
        line-height: 1.3;
    }

    .hb-type-tab:hover {
        background: rgba(255, 255, 255, .08);
        color: #c8d3e8;
    }

    .hb-type-tab.active {
        background: rgba(99, 102, 241, .18);
        border-color: rgba(99, 102, 241, .4);
        color: #a5b4fc;
    }

    /* col pair (gradient pickers) */
    .hb-col-pair {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .hb-col-swatch {
        width: 32px;
        height: 32px;
        border-radius: 7px;
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, .1);
        padding: 2px;
        background: none;
        flex-shrink: 0;
    }

    .hb-col-txt {
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
    }

    .hb-arrow {
        color: rgba(255, 255, 255, .2);
        font-size: 12px;
        flex-shrink: 0;
    }

    /* section panel (collapsible in banner editor) */
    .hb-panel {
        border: 1px solid rgba(255, 255, 255, .06);
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .hb-panel-head {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        cursor: pointer;
        background: rgba(255, 255, 255, .03);
        font-size: 11px;
        font-weight: 700;
        color: rgba(255, 255, 255, .5);
        transition: background .15s;
        user-select: none;
    }

    .hb-panel-head:hover {
        background: rgba(255, 255, 255, .06);
    }

    .hb-panel-head .ph-caret-down {
        transition: transform .2s;
        font-size: 13px;
        margin-left: auto;
    }

    .hb-panel.open .hb-panel-head .ph-caret-down {
        transform: rotate(180deg);
    }

    .hb-panel-body {
        display: none;
        padding: 14px;
        border-top: 1px solid rgba(255, 255, 255, .05);
    }

    .hb-panel.open .hb-panel-body {
        display: block;
    }

    /* tp-img badge */
    .tp-img {
        background: rgba(99, 102, 241, .18);
        color: #a5b4fc;
    }

    /* Responsive ─────────────────────────────────────────────────── */
    @media(max-width:991px) {
        .ws {
            grid-template-columns: 56px 1fr;
        }

        .ws-tree {
            display: none;
        }

        .ws-editor {
            grid-column: 2;
        }
    }

    @media(max-width:575px) {
        .ws {
            grid-template-columns: 1fr;
            min-height: auto;
            height: auto;
        }

        .ws-rail {
            display: none;
        }

        .ws-editor {
            grid-column: 1;
        }
    }
</style>

<!-- ═══════════════════════════════════════════════════════════
     PAGE HEADER  (compact — workspace takes the space)
════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:10px">
        <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#6366f1,#06b6d4);display:flex;align-items:center;justify-content:center;font-size:16px">🎛️</div>
        <div>
            <div style="font-size:16px;font-weight:700;color:var(--text)">Frontend Manager</div>
            <div style="font-size:11px;color:var(--mut)">navbar · quick actions · running text · hero banner</div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--mut);background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.15);padding:5px 12px;border-radius:99px">
        <span style="width:6px;height:6px;border-radius:50%;background:#34d399;flex-shrink:0"></span>
        Perubahan langsung live ke aplikasi user
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     WORKSPACE
════════════════════════════════════════════════════════════ -->
<div class="ws">

    <!-- ── ICON RAIL ──────────────────────────────────────────── -->
    <nav class="ws-rail">
        <button id="rail-nav" class="rail-item active" onclick="switchTab('nav',this)">
            <i class="ph ph-navigation-arrow"></i>
            <span class="rtip">Navbar</span>
        </button>
        <button id="rail-qa" class="rail-item" onclick="switchTab('qa',this)">
            <i class="ph ph-lightning"></i>
            <span class="rtip">Quick Actions</span>
        </button>
        <div class="rail-sep"></div>
        <button id="rail-rt" class="rail-item" onclick="switchTab('rt',this)">
            <i class="ph ph-megaphone-simple"></i>
            <span class="rtip">Running Text</span>
        </button>
        <button id="rail-hb" class="rail-item" onclick="switchTab('hb',this)">
            <i class="ph ph-image"></i>
            <span class="rtip">Hero Banner</span>
        </button>
        <div class="rail-sep" style="margin-top:auto"></div>
        <button class="rail-item" onclick="togglePhonePreview()" id="rail-phone" title="">
            <i class="ph ph-device-mobile"></i>
            <span class="rtip">Preview HP</span>
        </button>
    </nav>

    <!-- ── TREE SIDEBAR ───────────────────────────────────────── -->
    <aside class="ws-tree">

        <!-- Navbar tree -->
        <div id="tree-nav" style="display:flex;flex-direction:column;height:100%">
            <div class="tree-topbar">
                <span class="tree-title">navbar_items</span>
                <div style="display:flex;gap:4px;align-items:center">
                    <button id="nav-save-order" class="tree-save-btn" style="display:none" onclick="submitReorder('nav')" title="Simpan urutan"><i class="ph ph-floppy-disk"></i></button>
                    <button class="tree-add-btn" onclick="openNew('nav')" title="Tambah item baru"><i class="ph ph-plus"></i></button>
                </div>
            </div>
            <div class="tree-scroll" id="sortable-nav">
                <?php foreach ($navbars as $n): ?>
                    <div class="tree-row <?= !$n['is_active'] ? 'dimmed' : '' ?> <?= ($nav_edit && $nav_edit['id'] === $n['id']) ? 'active' : '' ?>"
                        data-id="<?= $n['id'] ?>" draggable="true" data-group="nav">
                        <i class="ph ph-dots-six-vertical tree-drag-handle"></i>
                        <div class="tree-row-ico" style="background:<?= $n['is_active'] ? 'rgba(59,130,246,.12)' : 'rgba(255,255,255,.04)' ?>" onclick="gotoEdit('nav', <?= $n['id'] ?>)">
                            <i class="<?= htmlspecialchars($n['icon_class']) ?>" style="color:<?= $n['is_active'] ? '#60a5fa' : 'rgba(255,255,255,.25)' ?>"></i>
                        </div>
                        <div class="tree-row-body" onclick="gotoEdit('nav', <?= $n['id'] ?>)">
                            <div class="tree-row-label"><?= htmlspecialchars($n['label']) ?></div>
                            <div class="tree-row-sub"><?= htmlspecialchars($n['href']) ?></div>
                        </div>
                        <?php if ($n['is_center']): ?><span class="tree-pill tp-ctr">CTR</span><?php endif; ?>
                        <span class="tree-pill <?= $n['is_active'] ? 'tp-on' : 'tp-off' ?>"><?= $n['is_active'] ? 'ON' : 'OFF' ?></span>
                        <form method="POST" style="display:contents" onsubmit="return confirm('Hapus «<?= addslashes($n['label']) ?>»?')">
                            <input type="hidden" name="action" value="nav_delete" />
                            <input type="hidden" name="id" value="<?= $n['id'] ?>" />
                            <button type="submit" class="tree-del-btn" onclick="event.stopPropagation()"><i class="ph ph-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($navbars)): ?>
                    <div style="padding:20px 14px;text-align:center;font-size:11px;color:rgba(255,255,255,.18)">Belum ada item</div>
                <?php endif; ?>
            </div>
            <form method="POST" id="form-nav-reorder" style="display:none">
                <input type="hidden" name="action" value="nav_reorder" />
                <input type="hidden" name="ids" id="nav-reorder-ids" />
            </form>
        </div>

        <!-- Quick actions tree -->
        <div id="tree-qa" style="display:none;flex-direction:column;height:100%">
            <div class="tree-topbar">
                <span class="tree-title">quick_actions</span>
                <div style="display:flex;gap:4px;align-items:center">
                    <button id="qa-save-order" class="tree-save-btn" style="display:none" onclick="submitReorder('qa')" title="Simpan urutan"><i class="ph ph-floppy-disk"></i></button>
                    <button class="tree-add-btn" onclick="openNew('qa')" title="Tambah action baru"><i class="ph ph-plus"></i></button>
                </div>
            </div>
            <div class="tree-scroll" id="sortable-qa">
                <?php foreach ($qactions as $q): ?>
                    <div class="tree-row <?= !$q['is_active'] ? 'dimmed' : '' ?> <?= ($qa_edit && $qa_edit['id'] === $q['id']) ? 'active' : '' ?>"
                        data-id="<?= $q['id'] ?>" draggable="true" data-group="qa">
                        <i class="ph ph-dots-six-vertical tree-drag-handle"></i>
                        <div class="tree-row-ico" style="background:rgba(1,210,152,.1)" onclick="gotoEdit('qa', <?= $q['id'] ?>)">
                            <i class="<?= htmlspecialchars($q['icon_class']) ?>" style="color:#34d399"></i>
                        </div>
                        <div class="tree-row-body" onclick="gotoEdit('qa', <?= $q['id'] ?>)">
                            <div class="tree-row-label"><?= htmlspecialchars($q['label']) ?></div>
                            <div class="tree-row-sub">#<?= $q['sort_order'] ?> · <?= htmlspecialchars(mb_substr($q['href'], 0, 22)) ?></div>
                        </div>
                        <span class="tree-pill <?= $q['is_active'] ? 'tp-on' : 'tp-off' ?>"><?= $q['is_active'] ? 'ON' : 'OFF' ?></span>
                        <form method="POST" style="display:contents" onsubmit="return confirm('Hapus «<?= addslashes($q['label']) ?>»?')">
                            <input type="hidden" name="action" value="qa_delete" />
                            <input type="hidden" name="id" value="<?= $q['id'] ?>" />
                            <button type="submit" class="tree-del-btn" onclick="event.stopPropagation()"><i class="ph ph-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($qactions)): ?>
                    <div style="padding:20px 14px;text-align:center;font-size:11px;color:rgba(255,255,255,.18)">Belum ada action</div>
                <?php endif; ?>
            </div>
            <form method="POST" id="form-qa-reorder" style="display:none">
                <input type="hidden" name="action" value="qa_reorder" />
                <input type="hidden" name="ids" id="qa-reorder-ids" />
            </form>
        </div>

        <!-- Running text tree -->
        <div id="tree-rt" style="display:none;flex-direction:column;height:100%">
            <div class="tree-topbar">
                <span class="tree-title">running_text</span>
                <div style="display:flex;gap:4px;align-items:center">
                    <button id="rt-save-order" class="tree-save-btn" style="display:none" onclick="submitReorder('rt')" title="Simpan urutan"><i class="ph ph-floppy-disk"></i></button>
                    <button class="tree-add-btn" onclick="openNew('rt')" title="Tambah teks baru"><i class="ph ph-plus"></i></button>
                </div>
            </div>
            <!-- Global speed control -->
            <div style="padding:10px 14px;border-bottom:1px solid rgba(255,255,255,.05);flex-shrink:0">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.25);margin-bottom:7px;display:flex;align-items:center;justify-content:space-between">
                    <span>⚡ Kecepatan Global</span>
                    <span id="gspd-label" style="color:var(--accent,#3b82f6);font-family:'JetBrains Mono',monospace"><?= $global_speed ?>s</span>
                </div>
                <input type="range" id="global-speed-slider" min="5" max="120" step="5"
                    value="<?= $global_speed ?>"
                    style="width:100%;accent-color:var(--accent,#3b82f6);height:4px;cursor:pointer"
                    oninput="document.getElementById('gspd-label').textContent=this.value+'s'" />
                <form method="POST" id="form-global-speed" style="display:none">
                    <input type="hidden" name="action" value="rt_speed_global" />
                    <input type="hidden" name="speed" id="gspd-hidden" />
                </form>
                <button onclick="saveGlobalSpeed()" style="margin-top:7px;width:100%;background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.2);color:#60a5fa;border-radius:6px;padding:5px;font-size:11px;font-weight:600;cursor:pointer">
                    <i class="ph ph-floppy-disk" style="font-size:12px"></i> Terapkan ke semua
                </button>
            </div>
            <div class="tree-scroll" id="sortable-rt">
                <?php foreach ($rtexts as $r): ?>
                    <div class="tree-row <?= !$r['is_active'] ? 'dimmed' : '' ?> <?= ($rt_edit && $rt_edit['id'] === $r['id']) ? 'active' : '' ?>"
                        data-id="<?= $r['id'] ?>" draggable="true" data-group="rt">
                        <i class="ph ph-dots-six-vertical tree-drag-handle"></i>
                        <div class="tree-row-ico" style="background:rgba(239,68,68,.1)" onclick="gotoEdit('rt', <?= $r['id'] ?>)">
                            <i class="<?= htmlspecialchars($r['icon_class']) ?>" style="color:<?= htmlspecialchars($r['icon_color']) ?>"></i>
                        </div>
                        <div class="tree-row-body" onclick="gotoEdit('rt', <?= $r['id'] ?>)">
                            <div class="tree-row-label"><?= htmlspecialchars(mb_substr($r['content'], 0, 28)) ?><?= mb_strlen($r['content']) > 28 ? '…' : '' ?></div>
                            <div class="tree-row-sub"><?= $r['speed'] ?>s · #<?= $r['sort_order'] ?></div>
                        </div>
                        <span class="tree-pill <?= $r['is_active'] ? 'tp-on' : 'tp-off' ?>"><?= $r['is_active'] ? 'ON' : 'OFF' ?></span>
                        <form method="POST" style="display:contents" onsubmit="return confirm('Hapus running text ini?')">
                            <input type="hidden" name="action" value="rt_delete" />
                            <input type="hidden" name="id" value="<?= $r['id'] ?>" />
                            <button type="submit" class="tree-del-btn" onclick="event.stopPropagation()"><i class="ph ph-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($rtexts)): ?>
                    <div style="padding:20px 14px;text-align:center;font-size:11px;color:rgba(255,255,255,.18)">Belum ada teks</div>
                <?php endif; ?>
            </div>
            <form method="POST" id="form-rt-reorder" style="display:none">
                <input type="hidden" name="action" value="rt_reorder" />
                <input type="hidden" name="ids" id="rt-reorder-ids" />
            </form>
        </div>

        <!-- Hero Banner tree -->
        <div id="tree-hb" style="display:none;flex-direction:column;height:100%">
            <div class="tree-topbar">
                <span class="tree-title">hero_banner</span>
                <div style="display:flex;gap:4px;align-items:center">
                    <button id="hb-save-order" class="tree-save-btn" style="display:none" onclick="submitReorder('hb')" title="Simpan urutan"><i class="ph ph-floppy-disk"></i></button>
                    <button class="tree-add-btn" onclick="openNew('hb')" title="Tambah banner baru"><i class="ph ph-plus"></i></button>
                </div>
            </div>
            <div class="tree-scroll" id="sortable-hb">
                <?php foreach ($hbanners as $b): ?>
                    <?php
                    $typeLabel = ['image_only' => 'IMG', 'layout' => 'LAY', 'image_center' => 'CTR'][$b['type']] ?? '?';
                    $typeColor = ['image_only' => 'tp-img', 'layout' => 'tp-ctr', 'image_center' => 'tp-on'][$b['type']] ?? 'tp-off';
                    $previewBg = "linear-gradient({$b['bg_gradient_angle']}deg,{$b['bg_color_start']},{$b['bg_color_end']})";
                    ?>
                    <div class="tree-row <?= !$b['is_active'] ? 'dimmed' : '' ?> <?= ($hb_edit && $hb_edit['id'] === $b['id']) ? 'active' : '' ?>"
                        data-id="<?= $b['id'] ?>" draggable="true" data-group="hb">
                        <i class="ph ph-dots-six-vertical tree-drag-handle"></i>
                        <div class="tree-row-ico" style="background:<?= $previewBg ?>;border-radius:6px;overflow:hidden" onclick="gotoEdit('hb', <?= $b['id'] ?>)">
                            <span style="font-size:9px;font-weight:900;color:rgba(255,255,255,.9)">HB</span>
                        </div>
                        <div class="tree-row-body" onclick="gotoEdit('hb', <?= $b['id'] ?>)">
                            <div class="tree-row-label"><?= htmlspecialchars(mb_substr($b['title'] ?? '(no title)', 0, 22)) ?></div>
                            <div class="tree-row-sub"><?= $b['height'] ?>px · <?= $b['type'] ?></div>
                        </div>
                        <span class="tree-pill <?= $typeColor ?>"><?= $typeLabel ?></span>
                        <span class="tree-pill <?= $b['is_active'] ? 'tp-on' : 'tp-off' ?>"><?= $b['is_active'] ? 'ON' : 'OFF' ?></span>
                        <form method="POST" style="display:contents" onsubmit="return confirm('Hapus banner ini?')">
                            <input type="hidden" name="action" value="hb_delete" />
                            <input type="hidden" name="id" value="<?= $b['id'] ?>" />
                            <button type="submit" class="tree-del-btn" onclick="event.stopPropagation()"><i class="ph ph-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($hbanners)): ?>
                    <div style="padding:20px 14px;text-align:center;font-size:11px;color:rgba(255,255,255,.18)">Belum ada banner</div>
                <?php endif; ?>
            </div>
            <form method="POST" id="form-hb-reorder" style="display:none">
                <input type="hidden" name="action" value="hb_reorder" />
                <input type="hidden" name="ids" id="hb-reorder-ids" />
            </form>
        </div>

    </aside>

    <!-- ── EDITOR PANE ────────────────────────────────────────── -->
    <main class="ws-editor">

        <!-- breadcrumb topbar -->
        <div class="editor-topbar">
            <i class="ph ph-code" style="color:rgba(255,255,255,.25);font-size:14px"></i>
            <div class="editor-crumb">
                <span>frontend /</span>
                <b id="ed-crumb-label"><?=
                                        $nav_edit ? 'navbar_items / #' . $nav_edit['id'] : ($qa_edit  ? 'quick_actions / #' . $qa_edit['id'] : ($rt_edit  ? 'running_text / #' . $rt_edit['id'] : ($hb_edit  ? 'hero_banner / #' . $hb_edit['id'] : ($open_tab === 'qa' ? 'quick_actions / new' : ($open_tab === 'rt' ? 'running_text / new' : ($open_tab === 'hb' ? 'hero_banner / new' : 'navbar_items / new'))))))
                                        ?></b>
            </div>
            <!-- status chip -->
            <?php
            $current_status = $nav_edit ? ($nav_edit['is_active'] ? 'active' : 'inactive')
                : ($qa_edit ? ($qa_edit['is_active'] ? 'active' : 'inactive')
                    : ($rt_edit ? ($rt_edit['is_active'] ? 'active' : 'inactive')
                        : ($hb_edit ? ($hb_edit['is_active'] ? 'active' : 'inactive') : null)));
            $toggle_action  = $nav_edit ? 'nav_toggle' : ($qa_edit ? 'qa_toggle' : ($rt_edit ? 'rt_toggle' : 'hb_toggle'));
            $toggle_item    = $nav_edit ?? $qa_edit ?? $rt_edit ?? $hb_edit;
            ?>
            <?php if ($current_status): ?>
                <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
                    <span style="font-size:10px;padding:2px 8px;border-radius:4px;font-weight:700;
          background:<?= $current_status === 'active' ? 'rgba(52,211,153,.15)' : 'rgba(248,113,113,.12)' ?>;
          color:<?= $current_status === 'active' ? '#34d399' : '#f87171' ?>">
                        <?= $current_status === 'active' ? '● AKTIF' : '○ NONAKTIF' ?>
                    </span>
                    <!-- Quick toggle -->
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="<?= $toggle_action ?>" />
                        <input type="hidden" name="id" value="<?= $toggle_item['id'] ?>" />
                        <button type="submit" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.4);border-radius:5px;padding:3px 8px;cursor:pointer;font-size:11px">
                            Toggle
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="editor-body">

            <!-- Phone preview (hidden by default) -->
            <div class="phone-wrap" id="phone-preview">
                <div style="display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap">
                    <!-- Navbar preview -->
                    <div>
                        <div style="font-size:9px;color:rgba(255,255,255,.25);text-align:center;margin-bottom:6px;font-weight:700;text-transform:uppercase;letter-spacing:.8px">Navbar</div>
                        <div class="phone-device">
                            <div class="phone-screen">
                                <div class="phone-nb">
                                    <?php foreach (array_filter($navbars, fn($x) => $x['is_active']) as $n): ?>
                                        <div class="pnav-item <?= $n['is_center'] ? 'pnav-center' : '' ?>">
                                            <?php if ($n['is_center']): ?>
                                                <div class="pnav-center-btn"><i class="<?= htmlspecialchars($n['icon_class']) ?>"></i></div>
                                            <?php else: ?>
                                                <i class="<?= htmlspecialchars($n['icon_class']) ?>" style="font-size:13px;color:#64748b"></i>
                                                <span class="pnav-lbl"><?= htmlspecialchars($n['label']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Quick actions preview -->
                    <div>
                        <div style="font-size:9px;color:rgba(255,255,255,.25);text-align:center;margin-bottom:6px;font-weight:700;text-transform:uppercase;letter-spacing:.8px">Quick Actions</div>
                        <div style="background:#090f1e;border-radius:12px;padding:12px 14px" class="qa-preview-grid">
                            <?php foreach (array_filter($qactions, fn($x) => $x['is_active']) as $qa): ?>
                                <div class="qa-pv-item">
                                    <div class="qa-pv-ico"><i class="<?= htmlspecialchars($qa['icon_class']) ?>"></i></div>
                                    <span class="qa-pv-lbl"><?= htmlspecialchars($qa['label']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <!-- Running text previews -->
                <?php foreach (array_filter($rtexts, fn($x) => $x['is_active']) as $r): ?>
                    <div class="rt-pv-strip" style="background:<?= htmlspecialchars($r['bg_color']) ?>;border:1px solid <?= htmlspecialchars($r['border_color']) ?>">
                        <i class="<?= htmlspecialchars($r['icon_class']) ?>" style="color:<?= htmlspecialchars($r['icon_color']) ?>;font-size:13px;flex-shrink:0"></i>
                        <div style="overflow:hidden;flex:1">
                            <span class="rt-ticker" style="color:<?= htmlspecialchars($r['text_color']) ?>;font-size:12px;animation-duration:<?= $r['speed'] ?>s">
                                <?= htmlspecialchars($r['content']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <hr style="border-color:rgba(255,255,255,.05);margin:18px 0" />
            </div>

            <!-- ══ EDITOR CONTENT: NAVBAR ══ -->
            <div id="ed-nav" style="display:<?= $open_tab === 'nav' ? 'block' : 'none' ?>">
                <div class="ef-title">
                    <i class="ph ph-navigation-arrow" style="color:#60a5fa"></i>
                    <?= $nav_edit ? 'Edit Navbar Item' : 'Navbar Item Baru' ?>
                </div>
                <div class="ef-sub"><?= $nav_edit ? 'id: ' . $nav_edit['id'] . ' · ' : 'insert into navbar_items · ' ?>bottom navigation bar</div>

                <form method="POST">
                    <input type="hidden" name="action" value="<?= $nav_edit ? 'nav_edit' : 'nav_add' ?>" />
                    <?php if ($nav_edit): ?><input type="hidden" name="id" value="<?= $nav_edit['id'] ?>"><?php endif; ?>

                    <span class="ef-section-lbl">Fields</span>
                    <div class="ef-grid-2">
                        <div class="ef-field">
                            <label class="ef-label">label *</label>
                            <input type="text" name="label" class="ef-input" required placeholder="Beranda"
                                value="<?= htmlspecialchars($nav_edit['label'] ?? '') ?>" />
                        </div>
                        <div class="ef-field">
                            <label class="ef-label">href</label>
                            <input type="text" name="href" class="ef-input ef-mono" placeholder="/dashboard"
                                value="<?= htmlspecialchars($nav_edit['href'] ?? '#') ?>" />
                        </div>
                    </div>

                    <div class="ef-field" style="margin-bottom:14px">
                        <label class="ef-label">icon_class <span style="font-weight:400;opacity:.6">(FontAwesome)</span></label>
                        <div class="ico-row">
                            <input type="text" name="icon_class" id="nav-ico-inp" class="ef-input ef-mono" style="flex:1"
                                placeholder="fas fa-home" value="<?= htmlspecialchars($nav_edit['icon_class'] ?? 'fas fa-circle') ?>"
                                oninput="liveIcoPreview('nav-ico-inp','nav-ico-prev')" />
                            <div class="ico-preview" id="nav-ico-prev">
                                <i class="<?= htmlspecialchars($nav_edit['icon_class'] ?? 'fas fa-circle') ?>" style="color:#60a5fa"></i>
                            </div>
                        </div>
                        <div class="ico-chips">
                            <?php foreach ($icon_pool as $ico): ?>
                                <button type="button" class="ico-chip <?= ($nav_edit['icon_class'] ?? '') === $ico ? 'picked' : '' ?>"
                                    onclick="pickIco('nav-ico-inp','nav-ico-prev',this,'<?= $ico ?>')"><i class="<?= $ico ?>"></i></button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <input type="hidden" name="sort_order" value="<?= $nav_edit['sort_order'] ?? count($navbars) + 1 ?>" />
                    <div class="ef-field" style="margin-bottom:14px">
                        <label class="ef-label">match_path</label>
                        <input type="text" name="match_path" class="ef-input ef-mono" placeholder="dashboard"
                            value="<?= htmlspecialchars($nav_edit['match_path'] ?? '') ?>" />
                    </div>

                    <span class="ef-section-lbl" style="margin-top:6px">Options</span>
                    <label class="togrow">
                        <div class="togrow-text">
                            <div>is_center</div>
                            <div>Tombol melayang di tengah navbar</div>
                        </div>
                        <input type="checkbox" name="is_center" class="togswitch" <?= !empty($nav_edit['is_center']) ? 'checked' : '' ?>>
                    </label>
                    <label class="togrow">
                        <div class="togrow-text">
                            <div>is_active</div>
                            <div>Tampilkan item di navbar user</div>
                        </div>
                        <input type="checkbox" name="is_active" class="togswitch" <?= !isset($nav_edit) || !empty($nav_edit['is_active']) ? 'checked' : '' ?>>
                    </label>

                    <div class="ef-footer">
                        <?php if ($nav_edit): ?>
                            <a href="?tab=nav" class="ef-btn ef-btn-ghost"><i class="ph ph-x"></i> Batal</a>
                        <?php endif; ?>
                        <button type="submit" class="ef-btn ef-btn-primary ms-auto">
                            <i class="ph ph-<?= $nav_edit ? 'floppy-disk' : 'plus' ?>"></i>
                            <?= $nav_edit ? 'Simpan Perubahan' : 'Tambah Item' ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ══ EDITOR CONTENT: QUICK ACTIONS ══ -->
            <div id="ed-qa" style="display:<?= $open_tab === 'qa' ? 'block' : 'none' ?>">
                <div class="ef-title">
                    <i class="ph ph-lightning" style="color:#fbbf24"></i>
                    <?= $qa_edit ? 'Edit Quick Action' : 'Quick Action Baru' ?>
                </div>
                <div class="ef-sub"><?= $qa_edit ? 'id: ' . $qa_edit['id'] . ' · ' : 'insert into quick_actions · ' ?>shortcut beranda</div>

                <form method="POST">
                    <input type="hidden" name="action" value="<?= $qa_edit ? 'qa_edit' : 'qa_add' ?>" />
                    <?php if ($qa_edit): ?><input type="hidden" name="id" value="<?= $qa_edit['id'] ?>"><?php endif; ?>

                    <span class="ef-section-lbl">Fields</span>
                    <div class="ef-grid-2">
                        <div class="ef-field">
                            <label class="ef-label">label *</label>
                            <input type="text" name="label" class="ef-input" required placeholder="Top Up"
                                value="<?= htmlspecialchars($qa_edit['label'] ?? '') ?>" />
                        </div>
                        <div class="ef-field">
                            <label class="ef-label">href</label>
                            <input type="text" name="href" class="ef-input ef-mono" placeholder="/modules/user/topup"
                                value="<?= htmlspecialchars($qa_edit['href'] ?? '#') ?>" />
                        </div>
                    </div>

                    <div class="ef-field" style="margin-bottom:14px">
                        <label class="ef-label">icon_class <span style="font-weight:400;opacity:.6">(FontAwesome)</span></label>
                        <div class="ico-row">
                            <input type="text" name="icon_class" id="qa-ico-inp" class="ef-input ef-mono" style="flex:1"
                                placeholder="fas fa-plus" value="<?= htmlspecialchars($qa_edit['icon_class'] ?? 'fas fa-circle') ?>"
                                oninput="liveIcoPreview('qa-ico-inp','qa-ico-prev')" />
                            <div class="ico-preview" id="qa-ico-prev" style="background:rgba(1,210,152,.1)">
                                <i class="<?= htmlspecialchars($qa_edit['icon_class'] ?? 'fas fa-circle') ?>" style="color:#34d399"></i>
                            </div>
                        </div>
                        <div class="ico-chips">
                            <?php foreach ($icon_pool as $ico): ?>
                                <button type="button" class="ico-chip <?= ($qa_edit['icon_class'] ?? '') === $ico ? 'picked' : '' ?>"
                                    onclick="pickIco('qa-ico-inp','qa-ico-prev',this,'<?= $ico ?>')"><i class="<?= $ico ?>"></i></button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <input type="hidden" name="sort_order" value="<?= $qa_edit['sort_order'] ?? count($qactions) + 1 ?>" />

                    <span class="ef-section-lbl">Options</span>
                    <label class="togrow">
                        <div class="togrow-text">
                            <div>is_active</div>
                            <div>Tampilkan di beranda user</div>
                        </div>
                        <input type="checkbox" name="is_active" class="togswitch" <?= !isset($qa_edit) || !empty($qa_edit['is_active']) ? 'checked' : '' ?>>
                    </label>

                    <div class="ef-footer">
                        <?php if ($qa_edit): ?>
                            <a href="?tab=qa" class="ef-btn ef-btn-ghost"><i class="ph ph-x"></i> Batal</a>
                        <?php endif; ?>
                        <button type="submit" class="ef-btn ef-btn-primary ms-auto">
                            <i class="ph ph-<?= $qa_edit ? 'floppy-disk' : 'plus' ?>"></i>
                            <?= $qa_edit ? 'Simpan Perubahan' : 'Tambah Action' ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ══ EDITOR CONTENT: RUNNING TEXT ══ -->
            <div id="ed-rt" style="display:<?= $open_tab === 'rt' ? 'block' : 'none' ?>">
                <div class="ef-title">
                    <i class="ph ph-megaphone-simple" style="color:#f87171"></i>
                    <?= $rt_edit ? 'Edit Running Text' : 'Running Text Baru' ?>
                </div>
                <div class="ef-sub"><?= $rt_edit ? 'id: ' . $rt_edit['id'] . ' · ' : 'insert into running_text · ' ?>teks berjalan beranda</div>

                <!-- Live preview -->
                <div id="rt-live-bar" class="rt-prev-wrap"
                    style="background:<?= htmlspecialchars($rt_edit['bg_color'] ?? '#ffffff') ?>;border:1px solid <?= htmlspecialchars($rt_edit['border_color'] ?? '#e2e8f0') ?>">
                    <i id="rt-live-icon" class="<?= htmlspecialchars($rt_edit['icon_class'] ?? 'fas fa-bolt') ?>"
                        style="color:<?= htmlspecialchars($rt_edit['icon_color'] ?? '#01d298') ?>;font-size:15px;flex-shrink:0"></i>
                    <div style="overflow:hidden;flex:1">
                        <span id="rt-live-text" class="rt-ticker"
                            style="color:<?= htmlspecialchars($rt_edit['text_color'] ?? '#0f172a') ?>;font-size:13px;animation-duration:<?= $rt_edit['speed'] ?? 35 ?>s">
                            <?= htmlspecialchars($rt_edit['content'] ?? 'Ketik konten untuk melihat preview...') ?>
                        </span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="<?= $rt_edit ? 'rt_edit' : 'rt_add' ?>" />
                    <?php if ($rt_edit): ?><input type="hidden" name="id" value="<?= $rt_edit['id'] ?>"><?php endif; ?>

                    <span class="ef-section-lbl">Content</span>
                    <div class="ef-field" style="margin-bottom:14px">
                        <label class="ef-label">content *</label>
                        <textarea name="content" id="rt-content" class="ef-input" rows="2" required
                            oninput="rtLive()" placeholder="🎉 Promo hari ini! Cashback 5%..."><?= htmlspecialchars($rt_edit['content'] ?? '') ?></textarea>
                    </div>

                    <div class="ef-field" style="margin-bottom:14px">
                        <label class="ef-label">icon_class</label>
                        <div class="ico-row">
                            <input type="text" name="icon_class" id="rt-ico-inp" class="ef-input ef-mono" style="flex:1"
                                placeholder="fas fa-bolt" oninput="liveIcoPreview('rt-ico-inp','rt-ico-prev');rtLive()"
                                value="<?= htmlspecialchars($rt_edit['icon_class'] ?? 'fas fa-bolt') ?>" />
                            <div class="ico-preview" id="rt-ico-prev">
                                <i id="rt-ico-prev-i" class="<?= htmlspecialchars($rt_edit['icon_class'] ?? 'fas fa-bolt') ?>"
                                    style="color:<?= htmlspecialchars($rt_edit['icon_color'] ?? '#01d298') ?>"></i>
                            </div>
                        </div>
                        <div class="ico-chips">
                            <?php foreach (array_slice($icon_pool, 0, 12) as $ico): ?>
                                <button type="button" class="ico-chip <?= ($rt_edit['icon_class'] ?? '') === $ico ? 'picked' : '' ?>"
                                    onclick="pickIco('rt-ico-inp','rt-ico-prev',this,'<?= $ico ?>');rtLive()"><i class="<?= $ico ?>"></i></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- hidden speed – nilai diisi dari global speed saat submit -->
                    <input type="hidden" name="speed" id="rt-speed-hidden" value="<?= $rt_edit['speed'] ?? $global_speed ?>" />

                    <span class="ef-section-lbl">Colors</span>
                    <div class="ef-grid-4">
                        <?php
                        $colDefs = [
                            ['icon_color',  'icon_color',   $rt_edit['icon_color']  ?? '#01d298'],
                            ['text_color',  'text_color',   $rt_edit['text_color']  ?? '#0f172a'],
                            ['bg_color',    'bg_color',     $rt_edit['bg_color']    ?? '#ffffff'],
                            ['border_color', 'border_color', $rt_edit['border_color'] ?? '#e2e8f0'],
                        ];
                        foreach ($colDefs as [$nm, $nm2, $val]): ?>
                            <div class="ef-field">
                                <label class="ef-label"><?= $nm ?></label>
                                <div class="col-combo">
                                    <input type="color" name="<?= $nm2 ?>" id="cp-<?= $nm ?>" class="col-picker"
                                        value="<?= htmlspecialchars($val) ?>" oninput="syncHex('<?= $nm ?>');rtLive()" />
                                    <input type="text" id="ch-<?= $nm ?>" class="ef-input col-hex" style="padding:7px 9px;font-size:11px"
                                        value="<?= htmlspecialchars($val) ?>" maxlength="7"
                                        oninput="syncPicker('<?= $nm ?>');rtLive()" />
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <span class="ef-section-lbl">Meta</span>
                    <input type="hidden" name="sort_order" value="<?= $rt_edit['sort_order'] ?? count($rtexts) + 1 ?>" />
                    <label class="togrow">
                        <div class="togrow-text">
                            <div>is_active</div>
                            <div>Tampilkan di beranda user</div>
                        </div>
                        <input type="checkbox" name="is_active" class="togswitch" <?= !isset($rt_edit) || !empty($rt_edit['is_active']) ? 'checked' : '' ?>>
                    </label>

                    <div class="ef-footer">
                        <?php if ($rt_edit): ?>
                            <a href="?tab=rt" class="ef-btn ef-btn-ghost"><i class="ph ph-x"></i> Batal</a>
                        <?php endif; ?>
                        <button type="submit" class="ef-btn ef-btn-primary ms-auto">
                            <i class="ph ph-<?= $rt_edit ? 'floppy-disk' : 'plus' ?>"></i>
                            <?= $rt_edit ? 'Simpan Perubahan' : 'Tambah Teks' ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ══ EDITOR CONTENT: HERO BANNER ══ -->
            <div id="ed-hb" style="display:<?= $open_tab === 'hb' ? 'block' : 'none' ?>">
                <div class="ef-title">
                    <i class="ph ph-image" style="color:#a5b4fc"></i>
                    <?= $hb_edit ? 'Edit Hero Banner' : 'Hero Banner Baru' ?>
                </div>
                <div class="ef-sub"><?= $hb_edit ? 'id: ' . $hb_edit['id'] . ' · ' : 'insert into hero_banner · ' ?>banner besar di atas beranda</div>

                <!-- Live preview -->
                <div id="hb-live-prev" class="hb-prev"
                    style="background:linear-gradient(<?= $hb['bg_gradient_angle'] ?>deg,<?= $hb['bg_color_start'] ?>,<?= $hb['bg_color_end'] ?>);height:<?= $hb['height'] ?>px">
                    <div class="hb-prev-left" id="hb-prev-left">
                        <?php if ($hb['img_left']): ?>
                            <img id="hb-prev-img-left" src="<?= htmlspecialchars($hb['img_left']) ?>"
                                style="width:<?= $hb['img_left_width'] ?>px;max-height:<?= $hb['height'] ?>px"
                                class="anim-<?= htmlspecialchars($hb['img_left_anim'] ?? '') ?>" />
                        <?php else: ?>
                            <div id="hb-prev-img-left" style="width:<?= $hb['img_left_width'] ?>px;height:70%;opacity:.2;background:rgba(255,255,255,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff">IMG</div>
                        <?php endif; ?>
                    </div>
                    <div class="hb-prev-center" id="hb-prev-center">
                        <?php if ($hb['center_type'] === 'image' && $hb['center_image']): ?>
                            <img id="hb-prev-cimg" src="<?= htmlspecialchars($hb['center_image']) ?>"
                                style="width:<?= $hb['center_image_width'] ?>px;max-height:<?= ($hb['height'] - 20) ?>px;object-fit:contain"
                                class="anim-<?= htmlspecialchars($hb['center_image_anim'] ?? '') ?>" />
                        <?php else: ?>
                            <?php if ($hb['title']): ?>
                                <div id="hb-prev-title" class="hb-prev-title"
                                    style="color:<?= htmlspecialchars($hb['title_color']) ?>"><?= htmlspecialchars($hb['title']) ?></div>
                            <?php endif; ?>
                            <?php if ($hb['subtitle']): ?>
                                <div id="hb-prev-sub" class="hb-prev-sub"
                                    style="color:<?= htmlspecialchars($hb['subtitle_color']) ?>"><?= htmlspecialchars($hb['subtitle']) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($hb['btn_text']): ?>
                            <button id="hb-prev-btn" class="hb-prev-btn anim-<?= htmlspecialchars($hb['btn_anim'] ?? 'pulse') ?>"
                                style="background:<?= htmlspecialchars($hb['btn_color']) ?>;color:<?= htmlspecialchars($hb['btn_text_color']) ?>"><?= htmlspecialchars($hb['btn_text']) ?></button>
                        <?php endif; ?>
                    </div>
                    <div class="hb-prev-right" id="hb-prev-right">
                        <?php if ($hb['img_right']): ?>
                            <img id="hb-prev-img-right" src="<?= htmlspecialchars($hb['img_right']) ?>"
                                style="width:<?= $hb['img_right_width'] ?>px;max-height:<?= $hb['height'] ?>px"
                                class="anim-<?= htmlspecialchars($hb['img_right_anim'] ?? '') ?>" />
                        <?php else: ?>
                            <div id="hb-prev-img-right" style="width:<?= $hb['img_right_width'] ?>px;height:70%;opacity:.2;background:rgba(255,255,255,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff">IMG</div>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" id="hb-form">
                    <input type="hidden" name="action" value="<?= $hb_edit ? 'hb_edit' : 'hb_add' ?>" />
                    <?php if ($hb_edit): ?><input type="hidden" name="id" value="<?= $hb_edit['id'] ?>"><?php endif; ?>

                    <!-- TYPE -->
                    <span class="ef-section-lbl">Tipe Banner</span>
                    <div class="hb-type-tabs" style="margin-bottom:18px">
                        <?php foreach (['layout' => '🗂 Layout (L·M·R)', 'image_only' => '🖼 Full Image', 'image_center' => '⭕ Image Center'] as $tv => $tl): ?>
                            <button type="button" class="hb-type-tab <?= $hb['type'] === $tv ? 'active' : '' ?>"
                                data-type="<?= $tv ?>" onclick="hbSetType('<?= $tv ?>', this)"><?= $tl ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="type" id="hb-type-val" value="<?= htmlspecialchars($hb['type']) ?>" />

                    <!-- BACKGROUND -->
                    <div class="hb-panel open" id="hb-sec-bg">
                        <div class="hb-panel-head" onclick="hbTogglePanel(this)">
                            <i class="ph ph-paint-bucket" style="color:#818cf8"></i> Background
                            <i class="ph ph-caret-down"></i>
                        </div>
                        <div class="hb-panel-body">
                            <div class="ef-grid-2" style="margin-bottom:12px">
                                <div class="ef-field">
                                    <label class="ef-label">Gradient Awal</label>
                                    <div class="hb-col-pair">
                                        <input type="color" id="hb-cp-cs" class="hb-col-swatch" name="bg_color_start"
                                            value="<?= htmlspecialchars($hb['bg_color_start']) ?>"
                                            oninput="hbSyncHex('cs');hbLive()" />
                                        <input type="text" id="hb-ch-cs" class="ef-input hb-col-txt" maxlength="7"
                                            value="<?= htmlspecialchars($hb['bg_color_start']) ?>"
                                            oninput="hbSyncPicker('cs');hbLive()" />
                                    </div>
                                </div>
                                <div class="ef-field">
                                    <label class="ef-label">Gradient Akhir</label>
                                    <div class="hb-col-pair">
                                        <input type="color" id="hb-cp-ce" class="hb-col-swatch" name="bg_color_end"
                                            value="<?= htmlspecialchars($hb['bg_color_end']) ?>"
                                            oninput="hbSyncHex('ce');hbLive()" />
                                        <input type="text" id="hb-ch-ce" class="ef-input hb-col-txt" maxlength="7"
                                            value="<?= htmlspecialchars($hb['bg_color_end']) ?>"
                                            oninput="hbSyncPicker('ce');hbLive()" />
                                    </div>
                                </div>
                            </div>
                            <div class="ef-grid-2">
                                <div class="ef-field">
                                    <label class="ef-label">Sudut Gradient (deg)</label>
                                    <input type="number" name="bg_gradient_angle" id="hb-angle" class="ef-input" min="0" max="360"
                                        value="<?= $hb['bg_gradient_angle'] ?>" oninput="hbLive()" />
                                </div>
                                <div class="ef-field">
                                    <label class="ef-label">Tinggi Banner (px)</label>
                                    <input type="number" name="height" id="hb-height" class="ef-input" min="60" max="400"
                                        value="<?= $hb['height'] ?>" oninput="hbLive()" />
                                </div>
                            </div>
                            <div class="ef-field">
                                <label class="ef-label">Background Image URL <span style="opacity:.5">(opsional, override gradient)</span></label>
                                <input type="text" name="bg_image" id="hb-bgimg" class="ef-input ef-mono"
                                    placeholder="https://..." value="<?= htmlspecialchars($hb['bg_image'] ?? '') ?>"
                                    oninput="hbLive()" />
                            </div>
                        </div>
                    </div>

                    <!-- GAMBAR KIRI -->
                    <div class="hb-panel open" id="hb-sec-left">
                        <div class="hb-panel-head" onclick="hbTogglePanel(this)">
                            <i class="ph ph-arrow-left" style="color:#60a5fa"></i> Gambar Kiri
                            <i class="ph ph-caret-down"></i>
                        </div>
                        <div class="hb-panel-body">
                            <div class="ef-field" style="margin-bottom:12px">
                                <label class="ef-label">URL Gambar</label>
                                <input type="text" name="img_left" id="hb-imgl" class="ef-input ef-mono"
                                    placeholder="https://..." value="<?= htmlspecialchars($hb['img_left'] ?? '') ?>"
                                    oninput="hbLive()" />
                            </div>
                            <div class="ef-grid-2">
                                <div class="ef-field">
                                    <label class="ef-label">Lebar (px)</label>
                                    <input type="number" name="img_left_width" id="hb-imglw" class="ef-input" min="20" max="300"
                                        value="<?= $hb['img_left_width'] ?>" oninput="hbLive()" />
                                </div>
                                <div class="ef-field">
                                    <label class="ef-label">Animasi</label>
                                    <select name="img_left_anim" id="hb-imglanim" class="ef-input" onchange="hbLive()">
                                        <?php foreach (['' => 'Tidak ada', 'slide-left' => 'Slide Kiri', 'float' => 'Float', 'bounce' => 'Bounce'] as $av => $al): ?>
                                            <option value="<?= $av ?>" <?= $hb['img_left_anim'] === $av ? 'selected' : '' ?>><?= $al ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KONTEN TENGAH -->
                    <div class="hb-panel open" id="hb-sec-center">
                        <div class="hb-panel-head" onclick="hbTogglePanel(this)">
                            <i class="ph ph-text-t" style="color:#34d399"></i> Konten Tengah
                            <i class="ph ph-caret-down"></i>
                        </div>
                        <div class="hb-panel-body">
                            <div style="display:flex;gap:8px;margin-bottom:14px">
                                <button type="button" id="hb-ctype-text"
                                    class="hb-type-tab <?= $hb['center_type'] === 'text' ? 'active' : '' ?>"
                                    onclick="hbSetCenterType('text')">✏️ Teks</button>
                                <button type="button" id="hb-ctype-img"
                                    class="hb-type-tab <?= $hb['center_type'] === 'image' ? 'active' : '' ?>"
                                    onclick="hbSetCenterType('image')">🖼 Gambar</button>
                            </div>
                            <input type="hidden" name="center_type" id="hb-ctype-val" value="<?= htmlspecialchars($hb['center_type']) ?>" />

                            <!-- Text fields -->
                            <div id="hb-center-text-fields" style="display:<?= $hb['center_type'] === 'text' ? 'block' : 'none' ?>">
                                <div class="ef-grid-2" style="margin-bottom:12px">
                                    <div class="ef-field">
                                        <label class="ef-label">Judul</label>
                                        <input type="text" name="title" id="hb-title" class="ef-input" placeholder="KLAIM HADIAH"
                                            value="<?= htmlspecialchars($hb['title'] ?? '') ?>" oninput="hbLive()" />
                                    </div>
                                    <div class="ef-field">
                                        <label class="ef-label">Warna Judul</label>
                                        <div class="hb-col-pair">
                                            <input type="color" id="hb-cp-tc" class="hb-col-swatch" name="title_color"
                                                value="<?= htmlspecialchars($hb['title_color'] ?? '#ffffff') ?>"
                                                oninput="hbSyncHex('tc');hbLive()" />
                                            <input type="text" id="hb-ch-tc" class="ef-input hb-col-txt" maxlength="7"
                                                value="<?= htmlspecialchars($hb['title_color'] ?? '#ffffff') ?>"
                                                oninput="hbSyncPicker('tc');hbLive()" />
                                        </div>
                                    </div>
                                </div>
                                <div class="ef-grid-2">
                                    <div class="ef-field">
                                        <label class="ef-label">Subtitle</label>
                                        <input type="text" name="subtitle" id="hb-sub" class="ef-input" placeholder="& Jutaan Rupiah"
                                            value="<?= htmlspecialchars($hb['subtitle'] ?? '') ?>" oninput="hbLive()" />
                                    </div>
                                    <div class="ef-field">
                                        <label class="ef-label">Warna Subtitle</label>
                                        <div class="hb-col-pair">
                                            <input type="color" id="hb-cp-sc" class="hb-col-swatch" name="subtitle_color"
                                                value="<?= htmlspecialchars($hb['subtitle_color'] ?? '#ffffffd9') ?>"
                                                oninput="hbSyncHex('sc');hbLive()" />
                                            <input type="text" id="hb-ch-sc" class="ef-input hb-col-txt" maxlength="7"
                                                value="<?= htmlspecialchars($hb['subtitle_color'] ?? '#ffffffd9') ?>"
                                                oninput="hbSyncPicker('sc');hbLive()" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Image fields -->
                            <div id="hb-center-img-fields" style="display:<?= $hb['center_type'] === 'image' ? 'block' : 'none' ?>">
                                <div class="ef-field" style="margin-bottom:12px">
                                    <label class="ef-label">URL Gambar Tengah</label>
                                    <input type="text" name="center_image" id="hb-cimg" class="ef-input ef-mono"
                                        placeholder="https://..." value="<?= htmlspecialchars($hb['center_image'] ?? '') ?>"
                                        oninput="hbLive()" />
                                </div>
                                <div class="ef-grid-2">
                                    <div class="ef-field">
                                        <label class="ef-label">Lebar (px)</label>
                                        <input type="number" name="center_image_width" id="hb-cimgw" class="ef-input" min="20" max="400"
                                            value="<?= $hb['center_image_width'] ?>" oninput="hbLive()" />
                                    </div>
                                    <div class="ef-field">
                                        <label class="ef-label">Animasi</label>
                                        <select name="center_image_anim" id="hb-cimganim" class="ef-input" onchange="hbLive()">
                                            <?php foreach (['' => 'Tidak ada', 'float' => 'Float', 'bounce' => 'Bounce', 'slide-left' => 'Slide Kiri'] as $av => $al): ?>
                                                <option value="<?= $av ?>" <?= $hb['center_image_anim'] === $av ? 'selected' : '' ?>><?= $al ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL -->
                    <div class="hb-panel open" id="hb-sec-btn">
                        <div class="hb-panel-head" onclick="hbTogglePanel(this)">
                            <i class="ph ph-cursor-click" style="color:#fbbf24"></i> Tombol CTA
                            <i class="ph ph-caret-down"></i>
                        </div>
                        <div class="hb-panel-body">
                            <div class="ef-grid-2" style="margin-bottom:12px">
                                <div class="ef-field">
                                    <label class="ef-label">Teks Tombol <span style="opacity:.5">(kosong = sembunyikan)</span></label>
                                    <input type="text" name="btn_text" id="hb-btntxt" class="ef-input" placeholder="SERBU"
                                        value="<?= htmlspecialchars($hb['btn_text'] ?? '') ?>" oninput="hbLive()" />
                                </div>
                                <div class="ef-field">
                                    <label class="ef-label">Link Tombol</label>
                                    <input type="text" name="btn_href" id="hb-btnhref" class="ef-input ef-mono" placeholder="/promo"
                                        value="<?= htmlspecialchars($hb['btn_href'] ?? '#') ?>" />
                                </div>
                            </div>
                            <div class="ef-grid-3">
                                <div class="ef-field">
                                    <label class="ef-label">Warna BG</label>
                                    <div class="hb-col-pair">
                                        <input type="color" id="hb-cp-bc" class="hb-col-swatch" name="btn_color"
                                            value="<?= htmlspecialchars($hb['btn_color'] ?? '#FFD700') ?>"
                                            oninput="hbSyncHex('bc');hbLive()" />
                                        <input type="text" id="hb-ch-bc" class="ef-input hb-col-txt" maxlength="7"
                                            value="<?= htmlspecialchars($hb['btn_color'] ?? '#FFD700') ?>"
                                            oninput="hbSyncPicker('bc');hbLive()" />
                                    </div>
                                </div>
                                <div class="ef-field">
                                    <label class="ef-label">Warna Teks</label>
                                    <div class="hb-col-pair">
                                        <input type="color" id="hb-cp-btc" class="hb-col-swatch" name="btn_text_color"
                                            value="<?= htmlspecialchars($hb['btn_text_color'] ?? '#000000') ?>"
                                            oninput="hbSyncHex('btc');hbLive()" />
                                        <input type="text" id="hb-ch-btc" class="ef-input hb-col-txt" maxlength="7"
                                            value="<?= htmlspecialchars($hb['btn_text_color'] ?? '#000000') ?>"
                                            oninput="hbSyncPicker('btc');hbLive()" />
                                    </div>
                                </div>
                                <div class="ef-field">
                                    <label class="ef-label">Animasi</label>
                                    <select name="btn_anim" id="hb-btnanim" class="ef-input" onchange="hbLive()">
                                        <?php foreach (['pulse' => 'Pulse', 'bounce' => 'Bounce', 'none' => 'Tidak ada'] as $av => $al): ?>
                                            <option value="<?= $av ?>" <?= $hb['btn_anim'] === $av ? 'selected' : '' ?>><?= $al ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GAMBAR KANAN -->
                    <div class="hb-panel open" id="hb-sec-right">
                        <div class="hb-panel-head" onclick="hbTogglePanel(this)">
                            <i class="ph ph-arrow-right" style="color:#f87171"></i> Gambar Kanan
                            <i class="ph ph-caret-down"></i>
                        </div>
                        <div class="hb-panel-body">
                            <div class="ef-field" style="margin-bottom:12px">
                                <label class="ef-label">URL Gambar</label>
                                <input type="text" name="img_right" id="hb-imgr" class="ef-input ef-mono"
                                    placeholder="https://..." value="<?= htmlspecialchars($hb['img_right'] ?? '') ?>"
                                    oninput="hbLive()" />
                            </div>
                            <div class="ef-grid-2">
                                <div class="ef-field">
                                    <label class="ef-label">Lebar (px)</label>
                                    <input type="number" name="img_right_width" id="hb-imgrw" class="ef-input" min="20" max="300"
                                        value="<?= $hb['img_right_width'] ?>" oninput="hbLive()" />
                                </div>
                                <div class="ef-field">
                                    <label class="ef-label">Animasi</label>
                                    <select name="img_right_anim" id="hb-imgranim" class="ef-input" onchange="hbLive()">
                                        <?php foreach (['' => 'Tidak ada', 'slide-right' => 'Slide Kanan', 'float' => 'Float', 'bounce' => 'Bounce'] as $av => $al): ?>
                                            <option value="<?= $av ?>" <?= $hb['img_right_anim'] === $av ? 'selected' : '' ?>><?= $al ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- META -->
                    <span class="ef-section-lbl" style="margin-top:6px">Meta</span>
                    <input type="hidden" name="sort_order" value="<?= $hb['sort_order'] ?>" />
                    <label class="togrow">
                        <div class="togrow-text">
                            <div>is_active</div>
                            <div>Tampilkan banner di beranda user</div>
                        </div>
                        <input type="checkbox" name="is_active" class="togswitch" <?= !empty($hb['is_active']) ? 'checked' : '' ?>>
                    </label>

                    <div class="ef-footer">
                        <?php if ($hb_edit): ?>
                            <a href="?tab=hb" class="ef-btn ef-btn-ghost"><i class="ph ph-x"></i> Batal</a>
                        <?php endif; ?>
                        <button type="submit" class="ef-btn ef-btn-primary ms-auto">
                            <i class="ph ph-<?= $hb_edit ? 'floppy-disk' : 'plus' ?>"></i>
                            <?= $hb_edit ? 'Simpan Perubahan' : 'Tambah Banner' ?>
                        </button>
                    </div>
                </form>
            </div>

        </div><!-- /editor-body -->
    </main>

</div><!-- /ws -->

<?php
$initTab   = json_encode($open_tab);
$navEditId = $nav_edit ? $nav_edit['id'] : 0;
$qaEditId  = $qa_edit  ? $qa_edit['id']  : 0;
$rtEditId  = $rt_edit  ? $rt_edit['id']  : 0;
$hbEditId  = $hb_edit  ? $hb_edit['id']  : 0;

$page_scripts = <<<SCRIPT
<script>
/* ── Tab / panel switching ─────────────────────────────────── */
const TABS = {
  nav: { rail:'rail-nav', tree:'tree-nav', ed:'ed-nav', crumb:'navbar_items' },
  qa:  { rail:'rail-qa',  tree:'tree-qa',  ed:'ed-qa',  crumb:'quick_actions' },
  rt:  { rail:'rail-rt',  tree:'tree-rt',  ed:'ed-rt',  crumb:'running_text'  },
  hb:  { rail:'rail-hb',  tree:'tree-hb',  ed:'ed-hb',  crumb:'hero_banner'   },
};
let currentTab = {$initTab};

function switchTab(tab, railBtn) {
  Object.values(TABS).forEach(({rail,tree,ed}) => {
    document.getElementById(rail)?.classList.remove('active');
    const t=document.getElementById(tree); if(t) t.style.display='none';
    const e=document.getElementById(ed);   if(e) e.style.display='none';
  });
  const {rail,tree,ed,crumb} = TABS[tab];
  document.getElementById(rail)?.classList.add('active');
  const t=document.getElementById(tree); if(t) t.style.display='flex';
  const e=document.getElementById(ed);   if(e) e.style.display='block';
  document.getElementById('ed-crumb-label').textContent = crumb + ' / new';
  currentTab = tab;
  history.replaceState(null,'','?tab='+tab);
}

function gotoEdit(type, id) {
  window.location.href = '?' + type + '_edit=' + id + '&tab=' + type;
}

function openNew(type) {
  const rb = document.getElementById(TABS[type].rail);
  switchTab(type, rb);
  document.getElementById('ed-crumb-label').textContent = TABS[type].crumb + ' / new';
}

/* ── Icon helpers ─────────────────────────────────────────── */
function liveIcoPreview(inputId, previewId) {
  const val  = document.getElementById(inputId)?.value || '';
  const prev = document.getElementById(previewId);
  if (!prev) return;
  const i = prev.querySelector('i');
  if (i) i.className = val || 'fas fa-circle';
}
function pickIco(inputId, previewId, chip, cls) {
  document.getElementById(inputId).value = cls;
  liveIcoPreview(inputId, previewId);
  chip.closest('.ico-chips').querySelectorAll('.ico-chip').forEach(c => c.classList.remove('picked'));
  chip.classList.add('picked');
}

/* ── Running text live preview ────────────────────────────── */
function rtLive() {
  const content     = document.getElementById('rt-content')?.value     || 'Preview...';
  const iconCls     = document.getElementById('rt-ico-inp')?.value     || 'fas fa-bolt';
  const iconColor   = document.getElementById('cp-icon_color')?.value  || '#01d298';
  const textColor   = document.getElementById('cp-text_color')?.value  || '#0f172a';
  const bgColor     = document.getElementById('cp-bg_color')?.value    || '#ffffff';
  const borderColor = document.getElementById('cp-border_color')?.value|| '#e2e8f0';
  // use global speed for preview
  const speed = document.getElementById('global-speed-slider')?.value || 35;

  const bar  = document.getElementById('rt-live-bar');
  const icon = document.getElementById('rt-live-icon');
  const text = document.getElementById('rt-live-text');
  const ipv  = document.getElementById('rt-ico-prev-i');

  if (bar)  { bar.style.background = bgColor; bar.style.borderColor = borderColor; }
  if (icon) { icon.className = iconCls; icon.style.color = iconColor; }
  if (text) { text.textContent = content; text.style.color = textColor; text.style.animationDuration = speed+'s'; }
  if (ipv)  { ipv.className = iconCls; ipv.style.color = iconColor; }
}

/* Color picker ↔ hex text sync */
function syncHex(nm)    { const p=document.getElementById('cp-'+nm), h=document.getElementById('ch-'+nm); if(p&&h) h.value=p.value; }
function syncPicker(nm) {
  const h=document.getElementById('ch-'+nm), p=document.getElementById('cp-'+nm);
  if(h&&p&&/^#[0-9a-fA-F]{6}$/.test(h.value)) p.value=h.value;
}
['icon_color','text_color','bg_color','border_color'].forEach(nm => {
  document.getElementById('cp-'+nm)?.addEventListener('input', () => { syncHex(nm); rtLive(); });
});

/* ── Global speed ─────────────────────────────────────────── */
function saveGlobalSpeed() {
  const sp = document.getElementById('global-speed-slider')?.value;
  if (!sp) return;
  document.getElementById('gspd-hidden').value = sp;
  document.getElementById('form-global-speed').submit();
}

/* ── Drag-sort for tree lists ─────────────────────────────── */
function initDragSort(containerId, group) {
  const container = document.getElementById(containerId);
  if (!container) return;
  let dragSrc = null;

  container.querySelectorAll('.tree-row[draggable="true"]').forEach(row => {
    row.addEventListener('dragstart', function(e) {
      dragSrc = this;
      setTimeout(() => this.classList.add('dragging-src'), 0);
      e.dataTransfer.effectAllowed = 'move';
    });
    row.addEventListener('dragend', function() {
      this.classList.remove('dragging-src');
      container.querySelectorAll('.tree-row').forEach(r => r.classList.remove('drag-over'));
      // Show save button
      const btn = document.getElementById(group+'-save-order');
      if (btn) btn.style.display = 'flex';
    });
    row.addEventListener('dragover', function(e) {
      e.preventDefault();
      if (this === dragSrc) return;
      container.querySelectorAll('.tree-row').forEach(r => r.classList.remove('drag-over'));
      this.classList.add('drag-over');
      const rect = this.getBoundingClientRect();
      if (e.clientY < rect.top + rect.height / 2) {
        container.insertBefore(dragSrc, this);
      } else {
        container.insertBefore(dragSrc, this.nextSibling);
      }
    });
    row.addEventListener('dragleave', function() {
      this.classList.remove('drag-over');
    });
    row.addEventListener('drop', function(e) {
      e.preventDefault();
      this.classList.remove('drag-over');
    });
  });
}

function submitReorder(group) {
  const container = document.getElementById('sortable-'+group);
  if (!container) return;
  const ids = [...container.querySelectorAll('.tree-row[data-id]')]
              .map(r => r.dataset.id).join(',');
  document.getElementById(group+'-reorder-ids').value = ids;
  document.getElementById('form-'+group+'-reorder').submit();
}

/* ── Phone preview toggle ─────────────────────────────────── */
function togglePhonePreview() {
  const wrap = document.getElementById('phone-preview');
  const btn  = document.getElementById('rail-phone');
  const open = wrap.classList.toggle('open');
  btn.classList.toggle('active', open);
}

/* ── Hero Banner helpers ──────────────────────────────────── */
const HB_COLOR_MAP = {
  cs: { cp:'hb-cp-cs', ch:'hb-ch-cs' },
  ce: { cp:'hb-cp-ce', ch:'hb-ch-ce' },
  tc: { cp:'hb-cp-tc', ch:'hb-ch-tc' },
  sc: { cp:'hb-cp-sc', ch:'hb-ch-sc' },
  bc: { cp:'hb-cp-bc', ch:'hb-ch-bc' },
  btc:{ cp:'hb-cp-btc',ch:'hb-ch-btc'},
};

function hbSyncHex(k) {
  const m = HB_COLOR_MAP[k];
  const p = document.getElementById(m.cp), h = document.getElementById(m.ch);
  if (p && h) h.value = p.value;
}
function hbSyncPicker(k) {
  const m = HB_COLOR_MAP[k];
  const h = document.getElementById(m.ch), p = document.getElementById(m.cp);
  if (h && p && /^#[0-9a-fA-F]{6}$/.test(h.value)) p.value = h.value;
}

function hbSetType(type, btn) {
  document.getElementById('hb-type-val').value = type;
  document.querySelectorAll('.hb-type-tabs .hb-type-tab').forEach(function(b){ b.classList.remove('active'); });
  btn.classList.add('active');
  var showSides = (type !== 'image_only');
  var sl = document.getElementById('hb-sec-left');
  var sr = document.getElementById('hb-sec-right');
  if (sl) sl.style.display = showSides ? '' : 'none';
  if (sr) sr.style.display = showSides ? '' : 'none';
  hbLive();
}

function hbSetCenterType(ct) {
  document.getElementById('hb-ctype-val').value = ct;
  document.getElementById('hb-center-text-fields').style.display = ct === 'text'  ? 'block' : 'none';
  document.getElementById('hb-center-img-fields').style.display  = ct === 'image' ? 'block' : 'none';
  document.getElementById('hb-ctype-text').classList.toggle('active', ct === 'text');
  document.getElementById('hb-ctype-img').classList.toggle('active', ct === 'image');
  hbLive();
}

function hbTogglePanel(head) {
  head.closest('.hb-panel').classList.toggle('open');
}

function hbLive() {
  var prev = document.getElementById('hb-live-prev');
  if (!prev) return;

  var cs    = (document.getElementById('hb-cp-cs')?.value)  || '#005bb5';
  var ce    = (document.getElementById('hb-cp-ce')?.value)  || '#0099ff';
  var angle = (document.getElementById('hb-angle')?.value)  || 135;
  var ht    = (document.getElementById('hb-height')?.value) || 160;
  var bgImg = (document.getElementById('hb-bgimg')?.value)  || '';

  if (bgImg) {
    prev.style.background = "url('" + bgImg + "') center/cover no-repeat";
  } else {
    prev.style.background = 'linear-gradient(' + angle + 'deg,' + cs + ',' + ce + ')';
  }
  prev.style.height = ht + 'px';

  // left image
  var imgl  = (document.getElementById('hb-imgl')?.value)      || '';
  var imglw = (document.getElementById('hb-imglw')?.value)     || 85;
  var imgla = (document.getElementById('hb-imglanim')?.value)  || '';
  var leftEl = document.getElementById('hb-prev-img-left');
  if (leftEl) {
    if (imgl) {
      leftEl.outerHTML = '<img id="hb-prev-img-left" src="' + imgl + '" style="width:' + imglw + 'px;max-height:' + ht + 'px" class="anim-' + imgla + '"/>';
    } else {
      var le2 = document.getElementById('hb-prev-img-left');
      if (le2) { le2.style.width = imglw + 'px'; le2.className = 'anim-' + imgla; }
    }
  }

  // right image
  var imgr  = (document.getElementById('hb-imgr')?.value)      || '';
  var imgrw = (document.getElementById('hb-imgrw')?.value)     || 85;
  var imgra = (document.getElementById('hb-imgranim')?.value)  || '';
  var rightEl = document.getElementById('hb-prev-img-right');
  if (rightEl) {
    if (imgr) {
      rightEl.outerHTML = '<img id="hb-prev-img-right" src="' + imgr + '" style="width:' + imgrw + 'px;max-height:' + ht + 'px" class="anim-' + imgra + '"/>';
    } else {
      var re2 = document.getElementById('hb-prev-img-right');
      if (re2) { re2.style.width = imgrw + 'px'; re2.className = 'anim-' + imgra; }
    }
  }

  // center
  var ct     = (document.getElementById('hb-ctype-val')?.value) || 'text';
  var titleEl = document.getElementById('hb-prev-title');
  var subEl   = document.getElementById('hb-prev-sub');
  var btnEl   = document.getElementById('hb-prev-btn');

  if (ct === 'text') {
    var title  = (document.getElementById('hb-title')?.value)  || '';
    var titleC = (document.getElementById('hb-cp-tc')?.value)  || '#ffffff';
    var sub    = (document.getElementById('hb-sub')?.value)    || '';
    var subC   = (document.getElementById('hb-cp-sc')?.value)  || '#ffffffd9';
    if (titleEl) { titleEl.textContent = title; titleEl.style.color = titleC; titleEl.style.display = title ? '' : 'none'; }
    if (subEl)   { subEl.textContent   = sub;   subEl.style.color   = subC;   subEl.style.display   = sub   ? '' : 'none'; }
  }

  // button
  var btntxt  = (document.getElementById('hb-btntxt')?.value)  || '';
  var btnC    = (document.getElementById('hb-cp-bc')?.value)   || '#FFD700';
  var btnTC   = (document.getElementById('hb-cp-btc')?.value)  || '#000000';
  var btnAnim = (document.getElementById('hb-btnanim')?.value) || 'pulse';
  if (btnEl) {
    btnEl.textContent      = btntxt;
    btnEl.style.background = btnC;
    btnEl.style.color      = btnTC;
    btnEl.className        = 'hb-prev-btn anim-' + btnAnim;
    btnEl.style.display    = btntxt ? '' : 'none';
  }
}

/* ── Init on load ─────────────────────────────────────────── */
(function(){
  switchTab(currentTab, document.getElementById(TABS[currentTab].rail));

  // Mark correct tree row as active
  const eids = {nav:{$navEditId}, qa:{$qaEditId}, rt:{$rtEditId}, hb:{$hbEditId}};
  Object.entries(eids).forEach(([type, id]) => {
    if (!id) return;
    document.querySelectorAll('#tree-'+type+' .tree-row').forEach(row => {
      if (row.getAttribute('data-id') === String(id)) row.classList.add('active');
    });
  });

  const crumbMap = {nav:'navbar_items', qa:'quick_actions', rt:'running_text', hb:'hero_banner'};
  const editIds  = {nav:{$navEditId}, qa:{$qaEditId}, rt:{$rtEditId}, hb:{$hbEditId}};
  const editId   = editIds[currentTab];
  if (editId) {
    document.getElementById('ed-crumb-label').textContent = crumbMap[currentTab]+' / #'+editId;
  }

  // Init drag-sort for all lists
  initDragSort('sortable-nav', 'nav');
  initDragSort('sortable-qa',  'qa');
  initDragSort('sortable-rt',  'rt');
  initDragSort('sortable-hb',  'hb');

  rtLive();
  hbLive();
})();

/* ── Toast auto-dismiss ───────────────────────────────────── */
document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(16px)'; }, 3200);
  setTimeout(() => t.remove(), 3700);
});
</script>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>