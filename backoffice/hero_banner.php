<?php
// ============================================================
// Admin: IDE Hero Banner
// Path: /htdocs/admin/hero_banner.php
// ============================================================
$pageTitle = 'IDE Hero Banner';
require_once __DIR__ . '/../includes/header.php';

if (!$isAdmin) {
    header('Location: /');
    exit;
}

$pdo = $pdo; // from header.php
$msg = '';
$err = '';

// ── Helper sanitize CSS ──────────────────────────────────────
function cssSan(?string $v): string
{
    return preg_replace('/[^a-zA-Z0-9.\-% ]/', '', trim((string)$v));
}
function intSan(?string $v): ?int
{
    $v = trim((string)$v);
    return $v === '' || $v === 'NULL' ? null : (int)$v;
}
function strSan(?string $v, int $max = 600): string
{
    return mb_substr(trim((string)$v), 0, $max);
}

// ── Handle POST (save) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    if ($action === 'save' || $action === 'add') {
        $fields = [
            'height'          => (int)($_POST['height'] ?? 160),
            'img_left'        => strSan($_POST['img_left'] ?? ''),
            'img_left_w'      => (int)($_POST['img_left_w'] ?? 90),
            'img_left_h'      => intSan($_POST['img_left_h'] ?? ''),
            'img_left_x'      => cssSan($_POST['img_left_x'] ?? '0'),
            'img_left_y'      => cssSan($_POST['img_left_y'] ?? '0'),
            'img_left_z'      => (int)($_POST['img_left_z'] ?? 1),
            'img_left_anim'   => strSan($_POST['img_left_anim'] ?? '', 50),
            'img_right'       => strSan($_POST['img_right'] ?? ''),
            'img_right_w'     => (int)($_POST['img_right_w'] ?? 90),
            'img_right_h'     => intSan($_POST['img_right_h'] ?? ''),
            'img_right_x'     => cssSan($_POST['img_right_x'] ?? '0'),
            'img_right_y'     => cssSan($_POST['img_right_y'] ?? '0'),
            'img_right_z'     => (int)($_POST['img_right_z'] ?? 1),
            'img_right_anim'  => strSan($_POST['img_right_anim'] ?? '', 50),
            'center_y'        => cssSan($_POST['center_y'] ?? '0'),
            'center_w'        => intSan($_POST['center_w'] ?? ''),
            'center_z'        => (int)($_POST['center_z'] ?? 2),
            'title'           => strSan($_POST['title'] ?? '', 200),
            'title_color'     => strSan($_POST['title_color'] ?? '#ffffff', 40),
            'title_size'      => cssSan($_POST['title_size'] ?? '15px'),
            'title_weight'    => cssSan($_POST['title_weight'] ?? '900'),
            'title_mb'        => cssSan($_POST['title_mb'] ?? '2px'),
            'subtitle'        => strSan($_POST['subtitle'] ?? '', 300),
            'subtitle_color'  => strSan($_POST['subtitle_color'] ?? '#ffffffd9', 40),
            'subtitle_size'   => cssSan($_POST['subtitle_size'] ?? '10.5px'),
            'subtitle_mb'     => cssSan($_POST['subtitle_mb'] ?? '10px'),
            'center_type'     => in_array($_POST['center_type'] ?? '', ['text', 'image']) ? $_POST['center_type'] : 'text',
            'center_image'    => strSan($_POST['center_image'] ?? ''),
            'center_img_w'    => (int)($_POST['center_img_w'] ?? 160),
            'center_img_h'    => intSan($_POST['center_img_h'] ?? ''),
            'center_img_mb'   => cssSan($_POST['center_img_mb'] ?? '0'),
            'center_img_anim' => strSan($_POST['center_img_anim'] ?? '', 50),
            'btn_text'        => strSan($_POST['btn_text'] ?? '', 80),
            'btn_href'        => strSan($_POST['btn_href'] ?? '#', 255),
            'btn_color'       => strSan($_POST['btn_color'] ?? '#FFD700', 40),
            'btn_text_color'  => strSan($_POST['btn_text_color'] ?? '#000000', 40),
            'btn_pt'          => cssSan($_POST['btn_pt'] ?? '7px'),
            'btn_pb'          => cssSan($_POST['btn_pb'] ?? '7px'),
            'btn_pl'          => cssSan($_POST['btn_pl'] ?? '26px'),
            'btn_pr'          => cssSan($_POST['btn_pr'] ?? '26px'),
            'btn_radius'      => cssSan($_POST['btn_radius'] ?? '99px'),
            'btn_size'        => cssSan($_POST['btn_size'] ?? '12px'),
            'btn_weight'      => cssSan($_POST['btn_weight'] ?? '900'),
            'btn_anim'        => strSan($_POST['btn_anim'] ?? 'pulse', 50),
            'sort_order'      => (int)($_POST['sort_order'] ?? 0),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ];

        try {
            if ($action === 'add') {
                $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($fields)));
                $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($fields)));
                $stmt = $pdo->prepare("INSERT INTO hero_banner ($cols) VALUES ($placeholders)");
            } else {
                $id = (int)$_POST['id'];
                $sets = implode(', ', array_map(fn($k) => "`$k`=:$k", array_keys($fields)));
                $stmt = $pdo->prepare("UPDATE hero_banner SET $sets WHERE id=:id");
                $fields['id'] = $id;
            }
            $stmt->execute($fields);
            $msg = $action === 'add' ? 'Banner baru berhasil ditambahkan.' : 'Banner berhasil disimpan.';
        } catch (PDOException $e) {
            $err = 'DB Error: ' . $e->getMessage();
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM hero_banner WHERE id=?")->execute([$id]);
        $msg = 'Banner dihapus.';
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE hero_banner SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
    }

    if ($action === 'reorder') {
        $orders = json_decode($_POST['orders'] ?? '[]', true);
        if (is_array($orders)) {
            $stmt = $pdo->prepare("UPDATE hero_banner SET sort_order=? WHERE id=?");
            foreach ($orders as $i => $id) $stmt->execute([$i, (int)$id]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }
}

// ── Fetch all banners ────────────────────────────────────────
$banners = $pdo->query("SELECT * FROM hero_banner ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

// ── Fetch one for editing ────────────────────────────────────
$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM hero_banner WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

// default blank row for "add" mode
$d = $editRow ?? [
    'id' => '',
    'height' => 160,
    'img_left' => '',
    'img_left_w' => 90,
    'img_left_h' => '',
    'img_left_x' => '0',
    'img_left_y' => '0',
    'img_left_z' => 1,
    'img_left_anim' => '',
    'img_right' => '',
    'img_right_w' => 90,
    'img_right_h' => '',
    'img_right_x' => '0',
    'img_right_y' => '0',
    'img_right_z' => 1,
    'img_right_anim' => '',
    'center_y' => '0',
    'center_w' => '',
    'center_z' => 2,
    'title' => '',
    'title_color' => '#ffffff',
    'title_size' => '15px',
    'title_weight' => '900',
    'title_mb' => '2px',
    'subtitle' => '',
    'subtitle_color' => '#ffffffd9',
    'subtitle_size' => '10.5px',
    'subtitle_mb' => '10px',
    'center_type' => 'text',
    'center_image' => '',
    'center_img_w' => 160,
    'center_img_h' => '',
    'center_img_mb' => '0',
    'center_img_anim' => '',
    'btn_text' => '',
    'btn_href' => '#',
    'btn_color' => '#FFD700',
    'btn_text_color' => '#000000',
    'btn_pt' => '7px',
    'btn_pb' => '7px',
    'btn_pl' => '26px',
    'btn_pr' => '26px',
    'btn_radius' => '99px',
    'btn_size' => '12px',
    'btn_weight' => '900',
    'btn_anim' => 'pulse',
    'sort_order' => 0,
    'is_active' => 1,
];

$isEdit = !empty($editRow);

// helper: echo field value safely
function fv($d, $k, $default = ''): string
{
    return htmlspecialchars((string)($d[$k] ?? $default));
}
?>

<style>
    /* ── IDE Hero Banner Admin ─────────────────────────────────── */
    :root {
        --ide-bg: #0d0f14;
        --ide-surface: #161920;
        --ide-border: #252932;
        --ide-accent: #01d298;
        --ide-accent2: #0099ff;
        --ide-muted: #4a5165;
        --ide-text: #c8cdd8;
        --ide-label: #7a8196;
        --ide-danger: #ff4d6a;
        --ide-warn: #ffb830;
        --ide-radius: 10px;
        --f: 'Plus Jakarta Sans', sans-serif;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: var(--ide-bg);
        color: var(--ide-text);
        font-family: var(--f);
        font-size: 13px;
    }

    /* ── Layout ─── */
    .ide-wrap {
        display: grid;
        grid-template-columns: 320px 1fr;
        grid-template-rows: auto 1fr;
        min-height: 100vh;
        gap: 0;
    }

    .ide-topbar {
        grid-column: 1 / -1;
        background: var(--ide-surface);
        border-bottom: 1px solid var(--ide-border);
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .ide-topbar h1 {
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        letter-spacing: .3px;
    }

    .ide-topbar .badge-ide {
        background: linear-gradient(135deg, var(--ide-accent), var(--ide-accent2));
        color: #000;
        font-size: 9px;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 99px;
        letter-spacing: .8px;
        text-transform: uppercase;
    }

    /* ── Left panel (list + form) ─── */
    .ide-left {
        background: var(--ide-surface);
        border-right: 1px solid var(--ide-border);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        max-height: calc(100vh - 49px);
    }

    /* ── Right panel (preview) ─── */
    .ide-right {
        background: var(--ide-bg);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .ide-preview-bar {
        background: var(--ide-surface);
        border-bottom: 1px solid var(--ide-border);
        padding: 8px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 11px;
        color: var(--ide-label);
    }

    .ide-preview-bar span {
        color: var(--ide-accent);
        font-weight: 700;
    }

    .preview-dots {
        display: flex;
        gap: 5px;
    }

    .preview-dots i {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: block;
    }

    .preview-dots i:nth-child(1) {
        background: #ff5f57;
    }

    .preview-dots i:nth-child(2) {
        background: #ffbd2e;
    }

    .preview-dots i:nth-child(3) {
        background: #28ca41;
    }

    .ide-preview-area {
        flex: 1;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 32px 20px;
        overflow-y: auto;
    }

    /* ── Phone mockup ─── */
    .phone-mock {
        width: 320px;
        min-height: 560px;
        background: #1a1e28;
        border-radius: 36px;
        border: 2px solid #2e3347;
        box-shadow: 0 0 0 6px #111419, 0 40px 80px rgba(0, 0, 0, .6);
        overflow: hidden;
        position: relative;
        flex-shrink: 0;
    }

    .phone-mock::before {
        content: '';
        display: block;
        width: 80px;
        height: 6px;
        background: #2e3347;
        border-radius: 99px;
        margin: 10px auto 0;
    }

    .phone-hero {
        /* gradient diset JS dari form */
        padding: 14px 14px 0;
        position: relative;
        transition: all .3s;
    }

    .phone-hero-inner {
        /* hd-strip simulasi */
        position: relative;
        width: 100%;
        overflow: visible;
        margin-top: 8px;
        transition: height .3s;
    }

    .phone-img-left {
        position: absolute;
        object-fit: contain;
        left: 0;
        bottom: 0;
        transition: all .3s;
    }

    .phone-img-right {
        position: absolute;
        object-fit: contain;
        right: 0;
        bottom: 0;
        transition: all .3s;
    }

    .phone-center {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        transition: all .3s;
        white-space: nowrap;
    }

    .phone-title {
        font-family: var(--f);
        line-height: 1.15;
        letter-spacing: -.3px;
        text-shadow: 0 1px 4px rgba(0, 0, 0, .3);
        transition: all .3s;
    }

    .phone-sub {
        line-height: 1.3;
        font-family: var(--f);
        text-shadow: 0 1px 2px rgba(0, 0, 0, .2);
        transition: all .3s;
    }

    .phone-btn {
        display: inline-block;
        text-decoration: none;
        font-family: var(--f);
        letter-spacing: .3px;
        cursor: default;
        box-shadow: 0 3px 10px rgba(0, 0, 0, .3);
        transition: all .3s;
    }

    .phone-center-img {
        object-fit: contain;
        display: block;
        transition: all .3s;
    }

    /* menu card overlap */
    .phone-menucard {
        background: #fff;
        border-radius: 16px 16px 0 0;
        margin-top: -20px;
        padding: 12px;
        position: relative;
        z-index: 10;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }

    .phone-menuitem {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
    }

    .phone-menuitem .ic {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #f0f2f8;
    }

    .phone-menuitem .lb {
        width: 28px;
        height: 5px;
        border-radius: 3px;
        background: #e4e6ed;
    }

    /* ── Sections ─── */
    .ide-section {
        border-bottom: 1px solid var(--ide-border);
    }

    .ide-section-head {
        padding: 10px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        background: rgba(255, 255, 255, .02);
        transition: background .15s;
    }

    .ide-section-head:hover {
        background: rgba(255, 255, 255, .04);
    }

    .ide-section-head .sh-left {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: var(--ide-label);
    }

    .ide-section-head .sh-left .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .ide-section-head .sh-arrow {
        font-size: 10px;
        color: var(--ide-muted);
        transition: transform .2s;
    }

    .ide-section-head.open .sh-arrow {
        transform: rotate(180deg);
    }

    .ide-section-body {
        padding: 12px 16px;
        display: none;
        flex-direction: column;
        gap: 10px;
    }

    .ide-section-body.open {
        display: flex;
    }

    /* ── Field ─── */
    .field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .field label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
        color: var(--ide-label);
    }

    .field input,
    .field select,
    .field textarea {
        background: #0d0f14;
        border: 1px solid var(--ide-border);
        border-radius: 6px;
        color: var(--ide-text);
        font-family: var(--f);
        font-size: 12px;
        padding: 6px 9px;
        outline: none;
        transition: border-color .15s;
        width: 100%;
    }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
        border-color: var(--ide-accent);
    }

    .field input[type="color"] {
        padding: 2px 4px;
        height: 32px;
        cursor: pointer;
    }

    .field textarea {
        resize: vertical;
        min-height: 52px;
    }

    .field .hint {
        font-size: 10px;
        color: var(--ide-muted);
        line-height: 1.4;
    }

    /* 2-col grid inside section */
    .g2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .g3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
    }

    /* ── Inline color+text row ─── */
    .color-row {
        display: grid;
        grid-template-columns: 36px 1fr;
        gap: 6px;
        align-items: end;
    }

    /* ── Toggle ─── */
    .toggle-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
    }

    .toggle-row label {
        color: var(--ide-text);
        font-size: 12px;
        font-weight: 600;
        text-transform: none;
        letter-spacing: 0;
    }

    .sw {
        position: relative;
        width: 36px;
        height: 20px;
    }

    .sw input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .sw-track {
        position: absolute;
        inset: 0;
        background: var(--ide-border);
        border-radius: 99px;
        cursor: pointer;
        transition: background .2s;
    }

    .sw-track::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #fff;
        transition: transform .2s;
    }

    .sw input:checked+.sw-track {
        background: var(--ide-accent);
    }

    .sw input:checked+.sw-track::after {
        transform: translateX(16px);
    }

    /* ── Buttons ─── */
    .btn-ide {
        border: none;
        border-radius: 7px;
        font-family: var(--f);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        padding: 8px 16px;
        transition: opacity .15s, transform .1s;
        letter-spacing: .3px;
    }

    .btn-ide:active {
        transform: scale(.97);
    }

    .btn-primary {
        background: var(--ide-accent);
        color: #000;
    }

    .btn-primary:hover {
        opacity: .88;
    }

    .btn-danger {
        background: var(--ide-danger);
        color: #fff;
    }

    .btn-danger:hover {
        opacity: .85;
    }

    .btn-ghost {
        background: transparent;
        color: var(--ide-label);
        border: 1px solid var(--ide-border);
    }

    .btn-ghost:hover {
        color: var(--ide-text);
        border-color: var(--ide-muted);
    }

    .btn-warn {
        background: var(--ide-warn);
        color: #000;
    }

    .btn-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding: 14px 16px;
        border-top: 1px solid var(--ide-border);
    }

    /* ── Banner list ─── */
    .banner-list {
        padding: 12px 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .banner-list-title {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: var(--ide-label);
        margin-bottom: 4px;
    }

    .banner-card {
        background: #0d0f14;
        border: 1px solid var(--ide-border);
        border-radius: 8px;
        padding: 9px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: border-color .15s;
    }

    .banner-card:hover {
        border-color: var(--ide-accent);
    }

    .banner-card.active {
        border-color: var(--ide-accent);
        background: rgba(1, 210, 152, .06);
    }

    .banner-card .bc-info {
        flex: 1;
        min-width: 0;
    }

    .banner-card .bc-title {
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .banner-card .bc-meta {
        font-size: 10px;
        color: var(--ide-muted);
        margin-top: 1px;
    }

    .banner-card .bc-status {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .banner-card .bc-status.on {
        background: var(--ide-accent);
    }

    .banner-card .bc-status.off {
        background: var(--ide-muted);
    }

    .bc-actions {
        display: flex;
        gap: 4px;
    }

    .bc-btn {
        border: none;
        background: transparent;
        color: var(--ide-muted);
        cursor: pointer;
        padding: 3px 5px;
        border-radius: 4px;
        font-size: 12px;
        transition: color .15s, background .15s;
    }

    .bc-btn:hover {
        color: #fff;
        background: rgba(255, 255, 255, .08);
    }

    .bc-btn.del:hover {
        color: var(--ide-danger);
    }

    /* ── Toast ─── */
    .toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: var(--ide-accent);
        color: #000;
        font-weight: 700;
        font-size: 12px;
        padding: 10px 18px;
        border-radius: 8px;
        z-index: 9999;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .4);
        opacity: 0;
        transform: translateY(10px);
        transition: opacity .3s, transform .3s;
        pointer-events: none;
    }

    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .toast.err {
        background: var(--ide-danger);
        color: #fff;
    }

    /* ── Resize handle ─── */
    .resize-hint {
        font-size: 10px;
        color: var(--ide-muted);
        text-align: center;
        padding: 6px;
        border-top: 1px solid var(--ide-border);
    }

    /* ── Add new banner button ─── */
    .btn-addnew {
        margin: 12px 16px;
        width: calc(100% - 32px);
        background: rgba(1, 210, 152, .1);
        border: 1px dashed var(--ide-accent);
        color: var(--ide-accent);
        border-radius: 8px;
        padding: 9px;
        font-family: var(--f);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: background .15s;
    }

    .btn-addnew:hover {
        background: rgba(1, 210, 152, .18);
    }

    /* ── Anim classes preview ─── */
    @keyframes hb-float {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-6px)
        }
    }

    @keyframes hb-bounce {

        0%,
        100% {
            transform: translateY(0)
        }

        40% {
            transform: translateY(-8px)
        }

        60% {
            transform: translateY(-4px)
        }
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

    @keyframes hb-zoom {
        from {
            transform: scale(.85);
            opacity: 0
        }

        to {
            transform: scale(1);
            opacity: 1
        }
    }

    @keyframes hb-slide-left {
        from {
            transform: translateX(-30px);
            opacity: 0
        }

        to {
            transform: translateX(0);
            opacity: 1
        }
    }

    @keyframes hb-slide-right {
        from {
            transform: translateX(30px);
            opacity: 0
        }

        to {
            transform: translateX(0);
            opacity: 1
        }
    }

    .pa-float {
        animation: hb-float 3s ease-in-out infinite;
    }

    .pa-bounce {
        animation: hb-bounce 2s ease-in-out infinite;
    }

    .pa-pulse {
        animation: hb-pulse 2s ease-in-out infinite;
    }

    .pa-zoom-in {
        animation: hb-zoom .6s ease forwards;
    }

    .pa-slide-left {
        animation: hb-slide-left .6s ease forwards;
    }

    .pa-slide-right {
        animation: hb-slide-right .6s ease forwards;
    }

    /* ── Gradient picker row ─── */
    .grad-row {
        display: grid;
        grid-template-columns: 36px 36px 1fr 1fr;
        gap: 6px;
        align-items: end;
    }

    /* ── Scrollbar ─── */
    ::-webkit-scrollbar {
        width: 5px;
        height: 5px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--ide-border);
        border-radius: 99px;
    }

    /* ── Radio tabs for center_type ─── */
    .rtabs {
        display: flex;
        gap: 0;
        border: 1px solid var(--ide-border);
        border-radius: 7px;
        overflow: hidden;
    }

    .rtab {
        flex: 1;
    }

    .rtab input {
        display: none;
    }

    .rtab label {
        display: block;
        text-align: center;
        padding: 6px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        color: var(--ide-muted);
        background: transparent;
        letter-spacing: .4px;
        text-transform: uppercase;
        border: none;
        transition: all .15s;
    }

    .rtab input:checked+label {
        background: var(--ide-accent);
        color: #000;
    }

    /* ── Preview bg gradient display ─── */
    .bg-preview-strip {
        height: 6px;
        border-radius: 3px;
        margin-bottom: 8px;
        transition: all .3s;
    }
</style>

<div class="ide-wrap">

    <!-- TOPBAR -->
    <div class="ide-topbar">
        <div style="display:flex;align-items:center;gap:6px">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <rect width="16" height="16" rx="4" fill="#01d298" fill-opacity=".15" />
                <path d="M3 12V8l5-5 5 5v4H10V9H6v3H3z" fill="#01d298" />
            </svg>
            <h1>Hero Banner IDE</h1>
        </div>
        <span class="badge-ide">v5</span>
        <div style="flex:1"></div>
        <a href="/admin" class="btn-ide btn-ghost" style="font-size:11px;padding:5px 12px;">← Back</a>
    </div>

    <!-- LEFT PANEL -->
    <div class="ide-left">

        <?php if ($msg): ?>
            <script>
                window._toastMsg = <?= json_encode($msg) ?>;
                window._toastType = 'ok';
            </script>
        <?php endif; ?>
        <?php if ($err): ?>
            <script>
                window._toastMsg = <?= json_encode($err) ?>;
                window._toastType = 'err';
            </script>
        <?php endif; ?>

        <!-- Banner list -->
        <div class="banner-list">
            <div class="banner-list-title">Semua Banner (<?= count($banners) ?>)</div>
            <?php foreach ($banners as $b): ?>
                <div class="banner-card <?= $isEdit && $editRow['id'] == $b['id'] ? 'active' : '' ?>"
                    onclick="loadBannerIntoForm(<?= htmlspecialchars(json_encode($b)) ?>)">
                    <div class="bc-status <?= $b['is_active'] ? 'on' : 'off' ?>"></div>
                    <div class="bc-info">
                        <div class="bc-title"><?= htmlspecialchars($b['title'] ?: ($b['btn_text'] ?: 'Banner #' . $b['id'])) ?></div>
                        <div class="bc-meta">h=<?= $b['height'] ?>px · #<?= $b['id'] ?> · sort=<?= $b['sort_order'] ?></div>
                    </div>
                    <div class="bc-actions">
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button class="bc-btn" type="submit" title="Toggle aktif" onclick="event.stopPropagation()">
                                <?= $b['is_active'] ? '◉' : '○' ?>
                            </button>
                        </form>
                        <form method="post" style="display:inline" onsubmit="return confirm('Hapus banner ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button class="bc-btn del" type="submit" title="Hapus" onclick="event.stopPropagation()">✕</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="btn-addnew" onclick="resetForm()">+ Tambah Banner Baru</button>

        <!-- FORM -->
        <form method="post" id="hbForm">
            <input type="hidden" name="action" value="<?= $isEdit ? 'save' : 'add' ?>" id="formAction">
            <input type="hidden" name="id" value="<?= fv($d, 'id') ?>" id="formId">

            <!-- ── STRIP ── -->
            <div class="ide-section">
                <div class="ide-section-head open" onclick="toggleSection(this)">
                    <div class="sh-left">
                        <span class="dot" style="background:#ff9f43"></span> Strip
                    </div>
                    <span class="sh-arrow">▾</span>
                </div>
                <div class="ide-section-body open">
                    <div class="field">
                        <label>Height (px)</label>
                        <input type="number" name="height" id="f_height" value="<?= fv($d, 'height', '160') ?>" min="60" max="400" oninput="updatePreview()">
                        <div class="hint">Tinggi strip. Total render = height + 44px (ruang overlap menu card)</div>
                    </div>
                    <div class="g2">
                        <div class="field">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" id="f_sort_order" value="<?= fv($d, 'sort_order', '0') ?>" oninput="updatePreview()">
                        </div>
                        <div class="toggle-row" style="padding-top:16px">
                            <label class="sw">
                                <input type="checkbox" name="is_active" id="f_is_active" <?= ($d['is_active'] ?? 1) ? 'checked' : '' ?> onchange="updatePreview()">
                                <span class="sw-track"></span>
                            </label>
                            <label for="f_is_active">Aktif</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── GAMBAR KIRI ── -->
            <div class="ide-section">
                <div class="ide-section-head open" onclick="toggleSection(this)">
                    <div class="sh-left"><span class="dot" style="background:#54a0ff"></span> Gambar Kiri</div>
                    <span class="sh-arrow">▾</span>
                </div>
                <div class="ide-section-body open">
                    <div class="field">
                        <label>URL Gambar</label>
                        <input type="text" name="img_left" id="f_img_left" value="<?= fv($d, 'img_left') ?>" placeholder="https://..." oninput="updatePreview()">
                    </div>
                    <div class="g2">
                        <div class="field">
                            <label>Width (px)</label>
                            <input type="number" name="img_left_w" id="f_img_left_w" value="<?= fv($d, 'img_left_w', '90') ?>" min="10" max="300" oninput="updatePreview()">
                        </div>
                        <div class="field">
                            <label>Height (px, kosong=auto)</label>
                            <input type="number" name="img_left_h" id="f_img_left_h" value="<?= fv($d, 'img_left_h') ?>" min="10" max="400" placeholder="auto" oninput="updatePreview()">
                        </div>
                    </div>
                    <div class="g3">
                        <div class="field">
                            <label>X (left)</label>
                            <input type="text" name="img_left_x" id="f_img_left_x" value="<?= fv($d, 'img_left_x', '0') ?>" placeholder="0" oninput="updatePreview()">
                            <div class="hint">dari tepi kiri</div>
                        </div>
                        <div class="field">
                            <label>Y (bottom)</label>
                            <input type="text" name="img_left_y" id="f_img_left_y" value="<?= fv($d, 'img_left_y', '0') ?>" placeholder="0" oninput="updatePreview()">
                            <div class="hint">dari bawah strip</div>
                        </div>
                        <div class="field">
                            <label>Z-index</label>
                            <input type="number" name="img_left_z" id="f_img_left_z" value="<?= fv($d, 'img_left_z', '1') ?>" oninput="updatePreview()">
                        </div>
                    </div>
                    <div class="field">
                        <label>Animasi</label>
                        <select name="img_left_anim" id="f_img_left_anim" onchange="updatePreview()">
                            <?php foreach (['' => 'Tidak ada', 'float' => 'Float', 'bounce' => 'Bounce', 'slide-left' => 'Slide Left', 'zoom-in' => 'Zoom In'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($d['img_left_anim'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ── GAMBAR KANAN ── -->
            <div class="ide-section">
                <div class="ide-section-head" onclick="toggleSection(this)">
                    <div class="sh-left"><span class="dot" style="background:#ff6b81"></span> Gambar Kanan</div>
                    <span class="sh-arrow">▾</span>
                </div>
                <div class="ide-section-body">
                    <div class="field">
                        <label>URL Gambar</label>
                        <input type="text" name="img_right" id="f_img_right" value="<?= fv($d, 'img_right') ?>" placeholder="https://..." oninput="updatePreview()">
                    </div>
                    <div class="g2">
                        <div class="field">
                            <label>Width (px)</label>
                            <input type="number" name="img_right_w" id="f_img_right_w" value="<?= fv($d, 'img_right_w', '90') ?>" min="10" max="300" oninput="updatePreview()">
                        </div>
                        <div class="field">
                            <label>Height (px, kosong=auto)</label>
                            <input type="number" name="img_right_h" id="f_img_right_h" value="<?= fv($d, 'img_right_h') ?>" min="10" max="400" placeholder="auto" oninput="updatePreview()">
                        </div>
                    </div>
                    <div class="g3">
                        <div class="field">
                            <label>X (right)</label>
                            <input type="text" name="img_right_x" id="f_img_right_x" value="<?= fv($d, 'img_right_x', '0') ?>" placeholder="0" oninput="updatePreview()">
                            <div class="hint">dari tepi kanan</div>
                        </div>
                        <div class="field">
                            <label>Y (bottom)</label>
                            <input type="text" name="img_right_y" id="f_img_right_y" value="<?= fv($d, 'img_right_y', '0') ?>" placeholder="0" oninput="updatePreview()">
                            <div class="hint">dari bawah strip</div>
                        </div>
                        <div class="field">
                            <label>Z-index</label>
                            <input type="number" name="img_right_z" id="f_img_right_z" value="<?= fv($d, 'img_right_z', '1') ?>" oninput="updatePreview()">
                        </div>
                    </div>
                    <div class="field">
                        <label>Animasi</label>
                        <select name="img_right_anim" id="f_img_right_anim" onchange="updatePreview()">
                            <?php foreach (['' => 'Tidak ada', 'float' => 'Float', 'bounce' => 'Bounce', 'slide-right' => 'Slide Right', 'zoom-in' => 'Zoom In'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($d['img_right_anim'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ── CENTER ── -->
            <div class="ide-section">
                <div class="ide-section-head open" onclick="toggleSection(this)">
                    <div class="sh-left"><span class="dot" style="background:#ffd32a"></span> Center</div>
                    <span class="sh-arrow">▾</span>
                </div>
                <div class="ide-section-body open">
                    <div class="g3">
                        <div class="field">
                            <label>Y (bottom)</label>
                            <input type="text" name="center_y" id="f_center_y" value="<?= fv($d, 'center_y', '0') ?>" placeholder="0" oninput="updatePreview()">
                            <div class="hint">offset dari bawah strip</div>
                        </div>
                        <div class="field">
                            <label>Width (px, kosong=auto)</label>
                            <input type="number" name="center_w" id="f_center_w" value="<?= fv($d, 'center_w') ?>" placeholder="auto" min="40" oninput="updatePreview()">
                        </div>
                        <div class="field">
                            <label>Z-index</label>
                            <input type="number" name="center_z" id="f_center_z" value="<?= fv($d, 'center_z', '2') ?>" oninput="updatePreview()">
                        </div>
                    </div>

                    <!-- Center type tabs -->
                    <div class="field">
                        <label>Tipe Konten Tengah</label>
                        <div class="rtabs">
                            <div class="rtab">
                                <input type="radio" name="center_type" id="ct_text" value="text"
                                    <?= ($d['center_type'] ?? 'text') === 'text' ? 'checked' : '' ?> onchange="switchCenterType(); updatePreview()">
                                <label for="ct_text">Teks</label>
                            </div>
                            <div class="rtab">
                                <input type="radio" name="center_type" id="ct_image" value="image"
                                    <?= ($d['center_type'] ?? 'text') === 'image' ? 'checked' : '' ?> onchange="switchCenterType(); updatePreview()">
                                <label for="ct_image">Gambar</label>
                            </div>
                        </div>
                    </div>

                    <!-- Text fields -->
                    <div id="centerTextFields">
                        <div style="display:flex;flex-direction:column;gap:10px">
                            <div class="field">
                                <label>Title</label>
                                <input type="text" name="title" id="f_title" value="<?= fv($d, 'title') ?>" placeholder="KLAIM HADIAH" oninput="updatePreview()">
                            </div>
                            <div class="g3">
                                <div class="field">
                                    <label>Warna Title</label>
                                    <div class="color-row">
                                        <input type="color" name="title_color" id="f_title_color" value="<?= fv($d, 'title_color', '#ffffff') ?>" oninput="updatePreview()">
                                        <input type="text" id="f_title_color_txt" value="<?= fv($d, 'title_color', '#ffffff') ?>" oninput="syncColor('title_color')">
                                    </div>
                                </div>
                                <div class="field">
                                    <label>Font Size</label>
                                    <input type="text" name="title_size" id="f_title_size" value="<?= fv($d, 'title_size', '15px') ?>" placeholder="15px" oninput="updatePreview()">
                                </div>
                                <div class="field">
                                    <label>Font Weight</label>
                                    <select name="title_weight" id="f_title_weight" onchange="updatePreview()">
                                        <?php foreach (['400', '500', '600', '700', '800', '900'] as $w): ?>
                                            <option value="<?= $w ?>" <?= ($d['title_weight'] ?? '900') === $w ? 'selected' : '' ?>><?= $w ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="field">
                                <label>Margin Bottom Title</label>
                                <input type="text" name="title_mb" id="f_title_mb" value="<?= fv($d, 'title_mb', '2px') ?>" placeholder="2px" oninput="updatePreview()">
                            </div>

                            <div class="field">
                                <label>Subtitle</label>
                                <input type="text" name="subtitle" id="f_subtitle" value="<?= fv($d, 'subtitle') ?>" placeholder="& Jutaan Rupiah" oninput="updatePreview()">
                            </div>
                            <div class="g3">
                                <div class="field">
                                    <label>Warna Subtitle</label>
                                    <div class="color-row">
                                        <input type="color" name="subtitle_color" id="f_subtitle_color" value="<?= fv($d, 'subtitle_color', '#ffffffd9') ?>" oninput="updatePreview()">
                                        <input type="text" id="f_subtitle_color_txt" value="<?= fv($d, 'subtitle_color', '#ffffffd9') ?>" oninput="syncColor('subtitle_color')">
                                    </div>
                                </div>
                                <div class="field">
                                    <label>Font Size</label>
                                    <input type="text" name="subtitle_size" id="f_subtitle_size" value="<?= fv($d, 'subtitle_size', '10.5px') ?>" placeholder="10.5px" oninput="updatePreview()">
                                </div>
                                <div class="field">
                                    <label>Margin Bottom Sub</label>
                                    <input type="text" name="subtitle_mb" id="f_subtitle_mb" value="<?= fv($d, 'subtitle_mb', '10px') ?>" placeholder="10px" oninput="updatePreview()">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image center fields -->
                    <div id="centerImageFields" style="display:none;flex-direction:column;gap:10px">
                        <div class="field">
                            <label>URL Gambar Tengah</label>
                            <input type="text" name="center_image" id="f_center_image" value="<?= fv($d, 'center_image') ?>" placeholder="https://..." oninput="updatePreview()">
                        </div>
                        <div class="g2">
                            <div class="field">
                                <label>Width (px)</label>
                                <input type="number" name="center_img_w" id="f_center_img_w" value="<?= fv($d, 'center_img_w', '160') ?>" min="10" oninput="updatePreview()">
                            </div>
                            <div class="field">
                                <label>Height (px, kosong=auto)</label>
                                <input type="number" name="center_img_h" id="f_center_img_h" value="<?= fv($d, 'center_img_h') ?>" placeholder="auto" min="10" oninput="updatePreview()">
                            </div>
                        </div>
                        <div class="g2">
                            <div class="field">
                                <label>Margin Bottom</label>
                                <input type="text" name="center_img_mb" id="f_center_img_mb" value="<?= fv($d, 'center_img_mb', '0') ?>" placeholder="0" oninput="updatePreview()">
                            </div>
                            <div class="field">
                                <label>Animasi</label>
                                <select name="center_img_anim" id="f_center_img_anim" onchange="updatePreview()">
                                    <?php foreach (['' => 'Tidak ada', 'float' => 'Float', 'bounce' => 'Bounce', 'pulse' => 'Pulse', 'zoom-in' => 'Zoom In'] as $v => $l): ?>
                                        <option value="<?= $v ?>" <?= ($d['center_img_anim'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TOMBOL ── -->
            <div class="ide-section">
                <div class="ide-section-head" onclick="toggleSection(this)">
                    <div class="sh-left"><span class="dot" style="background:#01d298"></span> Tombol</div>
                    <span class="sh-arrow">▾</span>
                </div>
                <div class="ide-section-body">
                    <div class="g2">
                        <div class="field">
                            <label>Teks Tombol (kosong = tidak tampil)</label>
                            <input type="text" name="btn_text" id="f_btn_text" value="<?= fv($d, 'btn_text') ?>" placeholder="SERBU" oninput="updatePreview()">
                        </div>
                        <div class="field">
                            <label>Href / Link</label>
                            <input type="text" name="btn_href" id="f_btn_href" value="<?= fv($d, 'btn_href', '#') ?>" oninput="updatePreview()">
                        </div>
                    </div>
                    <div class="g2">
                        <div class="field">
                            <label>Bg Color</label>
                            <div class="color-row">
                                <input type="color" name="btn_color" id="f_btn_color" value="<?= fv($d, 'btn_color', '#FFD700') ?>" oninput="updatePreview()">
                                <input type="text" id="f_btn_color_txt" value="<?= fv($d, 'btn_color', '#FFD700') ?>" oninput="syncColor('btn_color')">
                            </div>
                        </div>
                        <div class="field">
                            <label>Text Color</label>
                            <div class="color-row">
                                <input type="color" name="btn_text_color" id="f_btn_text_color" value="<?= fv($d, 'btn_text_color', '#000000') ?>" oninput="updatePreview()">
                                <input type="text" id="f_btn_text_color_txt" value="<?= fv($d, 'btn_text_color', '#000000') ?>" oninput="syncColor('btn_text_color')">
                            </div>
                        </div>
                    </div>
                    <div class="g2">
                        <div class="field">
                            <label>Padding T / B</label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                                <input type="text" name="btn_pt" id="f_btn_pt" value="<?= fv($d, 'btn_pt', '7px') ?>" placeholder="7px" oninput="updatePreview()">
                                <input type="text" name="btn_pb" id="f_btn_pb" value="<?= fv($d, 'btn_pb', '7px') ?>" placeholder="7px" oninput="updatePreview()">
                            </div>
                        </div>
                        <div class="field">
                            <label>Padding L / R</label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                                <input type="text" name="btn_pl" id="f_btn_pl" value="<?= fv($d, 'btn_pl', '26px') ?>" placeholder="26px" oninput="updatePreview()">
                                <input type="text" name="btn_pr" id="f_btn_pr" value="<?= fv($d, 'btn_pr', '26px') ?>" placeholder="26px" oninput="updatePreview()">
                            </div>
                        </div>
                    </div>
                    <div class="g3">
                        <div class="field">
                            <label>Border Radius</label>
                            <input type="text" name="btn_radius" id="f_btn_radius" value="<?= fv($d, 'btn_radius', '99px') ?>" placeholder="99px" oninput="updatePreview()">
                        </div>
                        <div class="field">
                            <label>Font Size</label>
                            <input type="text" name="btn_size" id="f_btn_size" value="<?= fv($d, 'btn_size', '12px') ?>" placeholder="12px" oninput="updatePreview()">
                        </div>
                        <div class="field">
                            <label>Font Weight</label>
                            <select name="btn_weight" id="f_btn_weight" onchange="updatePreview()">
                                <?php foreach (['400', '500', '600', '700', '800', '900'] as $w): ?>
                                    <option value="<?= $w ?>" <?= ($d['btn_weight'] ?? '900') === $w ? 'selected' : '' ?>><?= $w ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label>Animasi Tombol</label>
                        <select name="btn_anim" id="f_btn_anim" onchange="updatePreview()">
                            <?php foreach (['' => 'Tidak ada', 'pulse' => 'Pulse', 'bounce' => 'Bounce', 'float' => 'Float'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($d['btn_anim'] ?? 'pulse') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ── SAVE BUTTONS ── -->
            <div class="btn-row">
                <button type="submit" class="btn-ide btn-primary" id="btnSave">
                    <?= $isEdit ? '💾 Simpan Perubahan' : '＋ Tambah Banner' ?>
                </button>
                <?php if ($isEdit): ?>
                    <button type="button" class="btn-ide btn-ghost" onclick="resetForm()">Cancel</button>
                <?php endif; ?>
            </div>

        </form>
    </div><!-- /ide-left -->

    <!-- RIGHT PANEL (PREVIEW) -->
    <div class="ide-right">
        <div class="ide-preview-bar">
            <div class="preview-dots">
                <i></i><i></i><i></i>
            </div>
            <span>LIVE PREVIEW</span>
            <div style="flex:1"></div>
            <div style="font-size:10px;color:var(--ide-muted)">320px · Hero Strip</div>
        </div>
        <div class="ide-preview-area">
            <div>
                <!-- hero bg wrapper simulation -->
                <div style="width:320px;background:linear-gradient(135deg,#0066cc,#0099ff);border-radius:20px 20px 0 0;padding:14px 14px 0;position:relative;" id="prevHeroBg">
                    <!-- hd-strip -->
                    <div id="prevStrip" style="position:relative;width:100%;overflow:visible;margin-top:8px;">
                        <img id="prevImgLeft" class="phone-img-left" src="" alt="" style="display:none">
                        <img id="prevImgRight" class="phone-img-right" src="" alt="" style="display:none">
                        <div id="prevCenter" class="phone-center" style="bottom:0;width:auto;z-index:2">
                            <div id="prevTitle" class="phone-title" style="display:none"></div>
                            <div id="prevSub" class="phone-sub" style="display:none"></div>
                            <img id="prevCenterImg" class="phone-center-img" src="" alt="" style="display:none">
                            <a id="prevBtn" class="phone-btn" href="#" style="display:none" onclick="return false"></a>
                        </div>
                    </div>
                </div>
                <!-- menu card overlap -->
                <div class="phone-menucard">
                    <?php for ($i = 0; $i < 8; $i++): ?>
                        <div class="phone-menuitem">
                            <div class="ic"></div>
                            <div class="lb"></div>
                        </div>
                    <?php endfor; ?>
                </div>
                <!-- height indicator -->
                <div style="margin-top:12px;text-align:center;font-size:10px;color:var(--ide-muted)" id="prevMeta">
                    strip height: <span id="prevH">–</span>px
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
    // ── animClass map ───────────────────────────────────────────
    const ANIM = {
        'float': 'pa-float',
        'bounce': 'pa-bounce',
        'slide-left': 'pa-slide-left',
        'slide-right': 'pa-slide-right',
        'zoom-in': 'pa-zoom-in',
        'pulse': 'pa-pulse',
    };

    function animCls(v) {
        return ANIM[v] || '';
    }

    // ── get form value helpers ──────────────────────────────────
    const g = id => document.getElementById(id);
    const gv = id => (g(id) ? g(id).value : '');

    // ── updatePreview — core ────────────────────────────────────
    function updatePreview() {
        const h = parseInt(gv('f_height')) || 160;
        const strip = g('prevStrip');
        const heroBg = g('prevHeroBg');

        // strip height
        strip.style.height = (h + 44) + 'px';
        g('prevH').textContent = h + 44;

        // ── IMG LEFT ───────────────────────────────────────────
        const elL = g('prevImgLeft');
        const lUrl = gv('f_img_left').trim();
        if (lUrl) {
            elL.style.display = 'block';
            elL.src = lUrl;
            elL.style.width = (parseInt(gv('f_img_left_w')) || 90) + 'px';
            elL.style.height = gv('f_img_left_h') ? (parseInt(gv('f_img_left_h')) + 'px') : 'auto';
            elL.style.left = gv('f_img_left_x') || '0';
            elL.style.bottom = gv('f_img_left_y') || '0';
            elL.style.zIndex = gv('f_img_left_z') || '1';
            elL.className = 'phone-img-left ' + animCls(gv('f_img_left_anim'));
        } else {
            elL.style.display = 'none';
            elL.src = '';
        }

        // ── IMG RIGHT ──────────────────────────────────────────
        const elR = g('prevImgRight');
        const rUrl = gv('f_img_right').trim();
        if (rUrl) {
            elR.style.display = 'block';
            elR.src = rUrl;
            elR.style.width = (parseInt(gv('f_img_right_w')) || 90) + 'px';
            elR.style.height = gv('f_img_right_h') ? (parseInt(gv('f_img_right_h')) + 'px') : 'auto';
            elR.style.right = gv('f_img_right_x') || '0';
            elR.style.bottom = gv('f_img_right_y') || '0';
            elR.style.zIndex = gv('f_img_right_z') || '1';
            elR.className = 'phone-img-right ' + animCls(gv('f_img_right_anim'));
        } else {
            elR.style.display = 'none';
            elR.src = '';
        }

        // ── CENTER ─────────────────────────────────────────────
        const elC = g('prevCenter');
        elC.style.bottom = gv('f_center_y') || '0';
        elC.style.width = gv('f_center_w') ? (parseInt(gv('f_center_w')) + 'px') : 'auto';
        elC.style.zIndex = gv('f_center_z') || '2';

        const ctype = document.querySelector('input[name="center_type"]:checked')?.value || 'text';

        // title
        const elTitle = g('prevTitle');
        const elSub = g('prevSub');
        const elCI = g('prevCenterImg');

        if (ctype === 'text') {
            elCI.style.display = 'none';
            const titleTxt = gv('f_title').trim();
            if (titleTxt) {
                elTitle.style.display = 'block';
                elTitle.textContent = titleTxt;
                elTitle.style.color = gv('f_title_color') || '#fff';
                elTitle.style.fontSize = gv('f_title_size') || '15px';
                elTitle.style.fontWeight = gv('f_title_weight') || '900';
                elTitle.style.marginBottom = gv('f_title_mb') || '2px';
            } else {
                elTitle.style.display = 'none';
            }

            const subTxt = gv('f_subtitle').trim();
            if (subTxt) {
                elSub.style.display = 'block';
                elSub.textContent = subTxt;
                elSub.style.color = gv('f_subtitle_color') || '#fff';
                elSub.style.fontSize = gv('f_subtitle_size') || '10.5px';
                elSub.style.marginBottom = gv('f_subtitle_mb') || '10px';
            } else {
                elSub.style.display = 'none';
            }
        } else {
            elTitle.style.display = 'none';
            elSub.style.display = 'none';
            const ciUrl = gv('f_center_image').trim();
            if (ciUrl) {
                elCI.style.display = 'block';
                elCI.src = ciUrl;
                elCI.style.width = (parseInt(gv('f_center_img_w')) || 160) + 'px';
                elCI.style.height = gv('f_center_img_h') ? parseInt(gv('f_center_img_h')) + 'px' : 'auto';
                elCI.style.marginBottom = gv('f_center_img_mb') || '0';
                elCI.className = 'phone-center-img ' + animCls(gv('f_center_img_anim'));
            } else {
                elCI.style.display = 'none';
            }
        }

        // ── BUTTON ────────────────────────────────────────────
        const elBtn = g('prevBtn');
        const btnTxt = gv('f_btn_text').trim();
        if (btnTxt) {
            elBtn.style.display = 'inline-block';
            elBtn.textContent = btnTxt;
            elBtn.style.background = gv('f_btn_color') || '#FFD700';
            elBtn.style.color = gv('f_btn_text_color') || '#000';
            elBtn.style.padding = `${gv('f_btn_pt')||'7px'} ${gv('f_btn_pr')||'26px'} ${gv('f_btn_pb')||'7px'} ${gv('f_btn_pl')||'26px'}`;
            elBtn.style.borderRadius = gv('f_btn_radius') || '99px';
            elBtn.style.fontSize = gv('f_btn_size') || '12px';
            elBtn.style.fontWeight = gv('f_btn_weight') || '900';
            elBtn.className = 'phone-btn ' + animCls(gv('f_btn_anim'));
        } else {
            elBtn.style.display = 'none';
        }
    }

    // ── switchCenterType ────────────────────────────────────────
    function switchCenterType() {
        const t = document.querySelector('input[name="center_type"]:checked')?.value || 'text';
        g('centerTextFields').style.display = t === 'text' ? 'flex' : 'none';
        g('centerImageFields').style.display = t === 'image' ? 'flex' : 'none';
    }

    // ── Section toggle ──────────────────────────────────────────
    function toggleSection(head) {
        head.classList.toggle('open');
        const body = head.nextElementSibling;
        body.classList.toggle('open');
    }

    // ── Sync color picker ↔ text input ─────────────────────────
    function syncColor(name) {
        const txt = g('f_' + name + '_txt');
        const picker = g('f_' + name);
        if (!txt || !picker) return;
        // only sync if it looks like a valid hex
        if (/^#[0-9a-fA-F]{3,8}$/.test(txt.value)) {
            picker.value = txt.value.slice(0, 7); // color input only supports 6-digit hex
        }
        updatePreview();
    }
    // also sync from picker → txt
    ['title_color', 'subtitle_color', 'btn_color', 'btn_text_color'].forEach(name => {
        const p = g('f_' + name);
        if (p) p.addEventListener('input', () => {
            const t = g('f_' + name + '_txt');
            if (t) t.value = p.value;
            updatePreview();
        });
    });

    // ── loadBannerIntoForm ──────────────────────────────────────
    function loadBannerIntoForm(b) {
        // action
        g('formAction').value = 'save';
        g('formId').value = b.id;
        g('btnSave').textContent = '💾 Simpan Perubahan';

        const setV = (id, key, def = '') => {
            if (g(id)) g(id).value = b[key] ?? def;
        };
        setV('f_height', 'height', 160);
        setV('f_sort_order', 'sort_order', 0);
        if (g('f_is_active')) g('f_is_active').checked = !!parseInt(b['is_active'] ?? 1);

        setV('f_img_left', 'img_left', '');
        setV('f_img_left_w', 'img_left_w', 90);
        setV('f_img_left_h', 'img_left_h', '');
        setV('f_img_left_x', 'img_left_x', '0');
        setV('f_img_left_y', 'img_left_y', '0');
        setV('f_img_left_z', 'img_left_z', 1);
        setV('f_img_left_anim', 'img_left_anim', '');

        setV('f_img_right', 'img_right', '');
        setV('f_img_right_w', 'img_right_w', 90);
        setV('f_img_right_h', 'img_right_h', '');
        setV('f_img_right_x', 'img_right_x', '0');
        setV('f_img_right_y', 'img_right_y', '0');
        setV('f_img_right_z', 'img_right_z', 1);
        setV('f_img_right_anim', 'img_right_anim', '');

        setV('f_center_y', 'center_y', '0');
        setV('f_center_w', 'center_w', '');
        setV('f_center_z', 'center_z', 2);

        setV('f_title', 'title', '');
        setV('f_title_color', 'title_color', '#ffffff');
        setV('f_title_color_txt', 'title_color', '#ffffff');
        setV('f_title_size', 'title_size', '15px');
        setV('f_title_weight', 'title_weight', '900');
        setV('f_title_mb', 'title_mb', '2px');
        setV('f_subtitle', 'subtitle', '');
        setV('f_subtitle_color', 'subtitle_color', '#ffffffd9');
        setV('f_subtitle_color_txt', 'subtitle_color', '#ffffffd9');
        setV('f_subtitle_size', 'subtitle_size', '10.5px');
        setV('f_subtitle_mb', 'subtitle_mb', '10px');

        // center type radio
        const ct = b['center_type'] || 'text';
        const radio = document.querySelector(`input[name="center_type"][value="${ct}"]`);
        if (radio) radio.checked = true;
        switchCenterType();

        setV('f_center_image', 'center_image', '');
        setV('f_center_img_w', 'center_img_w', 160);
        setV('f_center_img_h', 'center_img_h', '');
        setV('f_center_img_mb', 'center_img_mb', '0');
        setV('f_center_img_anim', 'center_img_anim', '');

        setV('f_btn_text', 'btn_text', '');
        setV('f_btn_href', 'btn_href', '#');
        setV('f_btn_color', 'btn_color', '#FFD700');
        setV('f_btn_color_txt', 'btn_color', '#FFD700');
        setV('f_btn_text_color', 'btn_text_color', '#000000');
        setV('f_btn_text_color_txt', 'btn_text_color', '#000000');
        setV('f_btn_pt', 'btn_pt', '7px');
        setV('f_btn_pb', 'btn_pb', '7px');
        setV('f_btn_pl', 'btn_pl', '26px');
        setV('f_btn_pr', 'btn_pr', '26px');
        setV('f_btn_radius', 'btn_radius', '99px');
        setV('f_btn_size', 'btn_size', '12px');
        setV('f_btn_weight', 'btn_weight', '900');
        setV('f_btn_anim', 'btn_anim', 'pulse');

        // mark active card
        document.querySelectorAll('.banner-card').forEach(c => c.classList.remove('active'));
        event?.currentTarget?.classList.add('active');

        updatePreview();
        g('hbForm').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    // ── resetForm ───────────────────────────────────────────────
    function resetForm() {
        g('formAction').value = 'add';
        g('formId').value = '';
        g('btnSave').textContent = '＋ Tambah Banner';
        g('hbForm').reset();
        document.querySelectorAll('.banner-card').forEach(c => c.classList.remove('active'));
        switchCenterType();
        updatePreview();
    }

    // ── Toast ───────────────────────────────────────────────────
    function showToast(msg, type = 'ok') {
        const t = g('toast');
        t.textContent = msg;
        t.className = 'toast' + (type === 'err' ? ' err' : '') + ' show';
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    // ── Init ────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        switchCenterType();
        updatePreview();
        if (window._toastMsg) showToast(window._toastMsg, window._toastType || 'ok');
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>