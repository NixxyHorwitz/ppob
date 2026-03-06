<?php
// backoffice/dashboard_hero_banner.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Hero Banner Editor';
$active_menu = 'hero_banner';

$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

// ── Ensure exactly one row exists ─────────────────────────────
$count = (int)$pdo->query("SELECT COUNT(*) FROM hero_banner")->fetchColumn();
if ($count === 0) {
    $pdo->exec("INSERT INTO hero_banner
    (type,bg_color_start,bg_color_end,bg_gradient_angle,height,sort_order,is_active,btn_pt,btn_pr,btn_pb,btn_pl)
    VALUES ('layout','#005bb5','#0099ff',135,160,1,1,'7px','26px','7px','26px')");
}
$banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();

// ── All 8 element prefixes with their 12 offset suffixes ──────
// Used for save, render, and JS preview
const HB_ELEMENTS = [
    'strip'      => 'strip_',
    'img_left'   => 'img_left_',
    'center'     => 'center_',
    'title'      => 'title_',
    'sub'        => 'sub_',
    'center_img' => 'center_img_',
    'btn'        => 'btn_',
    'img_right'  => 'img_right_',
];
// The 12 offset suffixes per element (no leading underscore — added by logic below)
const HB_OFFSETS = ['pt', 'pr', 'pb', 'pl', 'mt', 'mr', 'mb', 'ml', 'top', 'right', 'bottom', 'left'];

// ── _hbStyle: mirror the user-page helper ────────────────────
// Reads DB row for a given prefix, builds inline style string.
// $extra = extra CSS props injected alongside (e.g. background, color).
function _hbStyle(array $hb, string $prefix, array $extra = [], bool $forceRelative = false): string
{
    $parts = [];
    $hasPos = false;

    // Padding
    foreach (['pt' => 'padding-top', 'pr' => 'padding-right', 'pb' => 'padding-bottom', 'pl' => 'padding-left'] as $s => $prop) {
        $v = trim((string)($hb[$prefix . $s] ?? ''));
        if ($v !== '') $parts[] = "$prop:$v";
    }
    // Margin
    foreach (['mt' => 'margin-top', 'mr' => 'margin-right', 'mb' => 'margin-bottom', 'ml' => 'margin-left'] as $s => $prop) {
        $v = trim((string)($hb[$prefix . $s] ?? ''));
        if ($v !== '') $parts[] = "$prop:$v";
    }
    // Position offsets
    foreach (['top' => 'top', 'right' => 'right', 'bottom' => 'bottom', 'left' => 'left'] as $s => $prop) {
        $v = trim((string)($hb[$prefix . $s] ?? ''));
        if ($v !== '') {
            $parts[] = "$prop:$v";
            $hasPos = true;
        }
    }
    if ($hasPos || $forceRelative) $parts[] = 'position:relative';

    // Merge extra props
    foreach ($extra as $prop => $val) {
        if ($val !== null && $val !== '') $parts[] = "$prop:$val";
    }

    return implode(';', $parts);
}

// ── SAVE ──────────────────────────────────────────────────────
if ($action === 'save') {
    $f = $_POST;

    // Sanitise offset values: allow a-z A-Z 0-9 . - % space (per spec)
    $sanitizeOffset = fn($v) => preg_replace('/[^a-zA-Z0-9.\-% ]/', '', trim((string)$v));

    $cols = [
        'type'               => in_array($f['type'] ?? '', ['image_only', 'layout', 'image_center']) ? $f['type'] : 'layout',
        'bg_image'           => trim($f['bg_image']        ?? '') ?: null,
        'bg_color_start'     => trim($f['bg_color_start']  ?? '#005bb5'),
        'bg_color_end'       => trim($f['bg_color_end']    ?? '#0099ff'),
        'bg_gradient_angle'  => (int)($f['bg_gradient_angle'] ?? 135),
        'height'             => max(60, min(400, (int)($f['height'] ?? 160))),
        'img_left'           => trim($f['img_left']        ?? '') ?: null,
        'img_left_width'     => (int)($f['img_left_width'] ?? 90),
        'img_left_anim'      => trim($f['img_left_anim']   ?? '') ?: null,
        'center_type'        => in_array($f['center_type'] ?? '', ['text', 'image']) ? $f['center_type'] : 'text',
        'title'              => trim($f['title']           ?? '') ?: null,
        'title_color'        => trim($f['title_color']     ?? '#ffffff'),
        'subtitle'           => trim($f['subtitle']        ?? '') ?: null,
        'subtitle_color'     => trim($f['subtitle_color']  ?? '#ffffffd9'),
        'center_image'       => trim($f['center_image']    ?? '') ?: null,
        'center_image_width' => (int)($f['center_image_width'] ?? 160),
        'center_image_anim'  => trim($f['center_image_anim']   ?? '') ?: null,
        'btn_text'           => trim($f['btn_text']        ?? '') ?: null,
        'btn_href'           => trim($f['btn_href']        ?? '#'),
        'btn_color'          => trim($f['btn_color']       ?? '#FFD700'),
        'btn_text_color'     => trim($f['btn_text_color']  ?? '#000000'),
        'btn_anim'           => trim($f['btn_anim']        ?? 'pulse'),
        'img_right'          => trim($f['img_right']       ?? '') ?: null,
        'img_right_width'    => (int)($f['img_right_width'] ?? 90),
        'img_right_anim'     => trim($f['img_right_anim']  ?? '') ?: null,
        'is_active'          => isset($f['is_active']) ? 1 : 0,
    ];

    // Collect all 96 offset columns (8 elements × 12 suffixes)
    foreach (HB_ELEMENTS as $key => $prefix) {
        foreach (HB_OFFSETS as $sfx) {
            $dbCol = $prefix . $sfx;
            // Fix: strip_ prefix maps to strip_pt etc, img_left_ maps to img_left_pt etc
            $raw = $f[$dbCol] ?? '';
            $cols[$dbCol] = $sanitizeOffset($raw) ?: null;
        }
    }

    $setClauses = implode(',', array_map(fn($k) => "$k=?", array_keys($cols)));
    $pdo->prepare("UPDATE hero_banner SET $setClauses WHERE id=?")
        ->execute([...array_values($cols), (int)$banner['id']]);

    $toast  = 'Hero banner berhasil disimpan.';
    $banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();
}

// ── Animation options ─────────────────────────────────────────
$ANIM_OPT = [
    ''           => 'Tidak Ada',
    'float'      => 'Float ↕',
    'bounce'     => 'Bounce ↑',
    'slide-left' => 'Slide Kiri',
    'slide-right' => 'Slide Kanan',
    'pulse'      => 'Pulse',
    'zoom-in'    => 'Zoom In',
];
function anim_sel(string $name, string $cur, string $cls = 'hbe-input'): string
{
    global $ANIM_OPT;
    $o = "<select name=\"$name\" class=\"$cls\" onchange=\"livePreview();markUnsaved()\">";
    foreach ($ANIM_OPT as $v => $l)
        $o .= "<option value=\"$v\"" . ($cur === $v ? ' selected' : '') . ">$l</option>";
    return $o . "</select>";
}

// ── Offset field group (12 inputs for one element) ────────────
// Renders the 12 box-model inputs: pt/pr/pb/pl, mt/mr/mb/ml, top/right/bottom/left
function hbe_offset_group(string $prefix, array $b): void
{
    // Prefix in DB is like "strip_", "img_left_", "btn_", etc.
    // Input names must match DB column names exactly.
    $g = [
        'PADDING' => ['pt' => 'T', 'pr' => 'R', 'pb' => 'B', 'pl' => 'L'],
        'MARGIN'  => ['mt' => 'T', 'mr' => 'R', 'mb' => 'B', 'ml' => 'L'],
        'POSISI'  => ['top' => 'T', 'right' => 'R', 'bottom' => 'B', 'left' => 'L'],
    ];
    echo '<div class="hbe-offset-wrap">';
    foreach ($g as $label => $fields) {
        echo "<div class=\"hbe-offset-group\">";
        echo "<div class=\"hbe-offset-label\">$label</div>";
        echo '<div class="hbe-offset-4">';
        foreach ($fields as $sfx => $dir) {
            $colName = $prefix . $sfx;
            $val = htmlspecialchars($b[$colName] ?? '');
            $hint = match ($sfx) {
                'top', 'right', 'bottom', 'left' => 'pos',
                'pt', 'pb', 'mt', 'mb'           => 'v',
                default                       => 'h',
            };
            echo "<div class=\"hbe-offset-cell\">";
            echo "<span class=\"hbe-offset-dir\">$dir</span>";
            echo "<input type=\"text\" name=\"$colName\" class=\"hbe-offset-inp\" 
             value=\"$val\" placeholder=\"—\" maxlength=\"10\"
             title=\"$colName\" oninput=\"livePreview();markUnsaved()\" />";
            echo "</div>";
        }
        echo '</div></div>';
    }
    echo '</div>';
}

$b = $banner;
require_once __DIR__ . '/includes/header.php';
?>

<!-- TOAST -->
<div class="toast-wrap">
    <?php if ($toast):   ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>
    <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<style>
    /* ══ Layout ═══════════════════════════════════════════════ */
    .hbe-wrap {
        display: flex;
        height: calc(100vh - 128px);
        min-height: 580px;
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        background: var(--card);
    }

    /* ══ Left panel ════════════════════════════════════════════ */
    .hbe-panel {
        width: 400px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        border-right: 1px solid var(--border);
    }

    .hbe-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
        background: var(--surface);
    }

    .hbe-tabs {
        display: flex;
        gap: 2px;
        background: var(--hover);
        border-radius: 9px;
        padding: 3px;
        margin: 12px 14px 0;
        flex-shrink: 0;
    }

    .hbe-tab {
        flex: 1;
        padding: 6px 4px;
        border-radius: 7px;
        font-size: 10.5px;
        font-weight: 700;
        cursor: pointer;
        color: var(--mut);
        transition: all .15s;
        border: none;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        white-space: nowrap;
    }

    .hbe-tab.active {
        background: var(--card);
        color: var(--fg);
        box-shadow: 0 1px 6px rgba(0, 0, 0, .25);
    }

    .hbe-fields {
        flex: 1;
        overflow-y: auto;
        padding: 14px;
        scrollbar-width: thin;
        scrollbar-color: var(--border) transparent;
    }

    .hbe-fields::-webkit-scrollbar {
        width: 4px;
    }

    .hbe-fields::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 2px;
    }

    /* Section */
    .hbe-sec {
        background: var(--hover);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 10px;
    }

    .hbe-sec-title {
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: var(--mut);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .hbe-label {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--sub);
        display: block;
        margin-bottom: 5px;
    }

    .hbe-row {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
    }

    .hbe-row:last-child {
        margin-bottom: 0;
    }

    .hbe-col {
        flex: 1;
        min-width: 0;
    }

    .hbe-input {
        width: 100%;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 7px;
        padding: 7px 10px;
        color: var(--fg);
        font-size: 12px;
        outline: none;
        transition: border-color .15s;
    }

    .hbe-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, .12);
    }

    .hbe-color-row {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .hbe-swatch-inp {
        width: 32px;
        height: 32px;
        border-radius: 7px;
        border: 1px solid var(--border);
        padding: 2px;
        background: var(--hover);
        cursor: pointer;
        flex-shrink: 0;
    }

    .hbe-img-prev {
        width: 100%;
        max-height: 38px;
        border-radius: 6px;
        object-fit: cover;
        margin-top: 6px;
        border: 1px solid var(--border);
        display: none;
    }

    /* Type pills */
    .hbe-pills {
        display: flex;
        gap: 5px;
    }

    .hbe-pill {
        flex: 1;
        padding: 7px 4px;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 700;
        text-align: center;
        cursor: pointer;
        border: 1.5px solid var(--border);
        background: var(--card);
        color: var(--sub);
        transition: all .15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .hbe-pill.on {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, .15);
        color: #3b82f6;
    }

    /* Gradient bar */
    .hbe-grad-bar {
        height: 26px;
        border-radius: 7px;
        border: 1px solid var(--border);
        margin-bottom: 10px;
        transition: background .1s;
    }

    /* Palette */
    .hbe-palette {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        margin-top: 6px;
    }

    .hbe-dot {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        cursor: pointer;
        border: 1.5px solid rgba(255, 255, 255, .1);
        transition: transform .12s;
    }

    .hbe-dot:hover {
        transform: scale(1.35);
    }

    /* ══ Offset grid ════════════════════════════════════════════ */
    .hbe-offset-wrap {
        display: flex;
        gap: 6px;
    }

    .hbe-offset-group {
        flex: 1;
    }

    .hbe-offset-label {
        font-size: 8.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--mut);
        margin-bottom: 4px;
        text-align: center;
    }

    .hbe-offset-4 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3px;
    }

    .hbe-offset-cell {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
    }

    .hbe-offset-dir {
        font-size: 8px;
        font-weight: 700;
        color: var(--mut);
        line-height: 1;
    }

    .hbe-offset-inp {
        width: 100%;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 3px 4px;
        color: var(--fg);
        font-size: 10px;
        font-family: 'JetBrains Mono', monospace;
        text-align: center;
        outline: none;
        transition: border-color .12s;
    }

    .hbe-offset-inp:focus {
        border-color: #3b82f6;
    }

    .hbe-offset-inp::placeholder {
        color: var(--mut);
        opacity: .5;
    }

    /* Tip */
    .hbe-tip {
        background: rgba(245, 158, 11, .07);
        border: 1px solid rgba(245, 158, 11, .18);
        border-radius: 8px;
        padding: 9px 11px;
        font-size: 11px;
        color: var(--sub);
        display: flex;
        gap: 6px;
        line-height: 1.5;
        margin-top: 10px;
    }

    /* Toggle switch */
    .hbe-sw {
        position: relative;
        width: 38px;
        height: 22px;
        cursor: pointer;
        display: inline-block;
    }

    .hbe-sw input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }

    .hbe-sw-track {
        position: absolute;
        inset: 0;
        border-radius: 99px;
        transition: background .2s;
    }

    .hbe-sw-dot {
        position: absolute;
        top: 3px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #fff;
        transition: left .2s;
    }

    /* Save bar */
    .hbe-savebar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 16px;
        border-top: 1px solid var(--border);
        flex-shrink: 0;
        background: var(--surface);
    }

    .hbe-unsaved {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #f59e0b;
        display: none;
        margin-left: 4px;
        vertical-align: middle;
    }

    .hbe-unsaved.show {
        display: inline-block;
    }

    /* ══ Right panel: Preview ══════════════════════════════════ */
    .hbe-preview {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--surface);
        min-width: 0;
    }

    .hbe-prev-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 18px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }

    .hbe-prev-stage {
        flex: 1;
        overflow: auto;
    }

    .hbe-prev-ruler {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 7px 16px;
        border-top: 1px solid var(--border);
        font-size: 10px;
        color: var(--mut);
        flex-shrink: 0;
        background: var(--card);
        flex-wrap: wrap;
    }

    .hbe-prev-ruler strong {
        color: var(--sub);
    }

    /* ── Preview canvas: exact copy of .hero from user page ── */
    #prevCanvas {
        position: relative;
        overflow: hidden;
        padding: 28px 18px 0;
        width: 100%;
    }

    /* Decorative circles from .hero::before / ::after */
    #prevCanvas::before,
    #prevCanvas::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    #prevCanvas::before {
        width: 240px;
        height: 240px;
        background: rgba(255, 255, 255, .07);
        top: -80px;
        right: -60px;
    }

    #prevCanvas::after {
        width: 130px;
        height: 130px;
        background: rgba(255, 255, 255, .05);
        bottom: 60px;
        left: -40px;
    }

    /* .hero-ov */
    #prevHeroOv {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 1;
    }

    /* .hd-strip — exact from user CSS */
    #prevStrip {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-top: 14px;
        padding-bottom: 44px;
        pointer-events: none;
    }

    #prevStrip a,
    #prevStrip button {
        pointer-events: auto;
    }

    /* .hd-side */
    #prevLeft,
    #prevRight {
        display: flex;
        align-items: flex-end;
        flex-shrink: 0;
    }

    #prevLeft img,
    #prevRight img {
        object-fit: contain;
        display: block;
    }

    /* .hd-center */
    #prevCenter {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        text-align: center;
        padding: 0 4px 6px;
    }

    /* .hd-title */
    #prevTitle {
        font-size: 16px;
        font-weight: 900;
        line-height: 1.15;
        letter-spacing: -.3px;
        text-shadow: 0 1px 6px rgba(0, 0, 0, .25);
        margin-bottom: 2px;
    }

    /* .hd-sub */
    #prevSub {
        font-size: 10.5px;
        font-weight: 700;
        margin-bottom: 10px;
        opacity: .88;
        text-shadow: 0 1px 3px rgba(0, 0, 0, .2);
    }

    /* .hd-center-img */
    #prevCenterImg {
        max-width: 100%;
        object-fit: contain;
        margin-bottom: 10px;
    }

    /* .hd-btn */
    #prevBtn {
        display: inline-block;
        padding: 7px 26px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .4px;
        text-decoration: none;
        cursor: pointer;
        border: none;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .22);
        transition: transform .15s;
    }

    /* Inactive overlay */
    #prevInactiveOv {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .62);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 20;
    }

    .hbe-inactive-badge {
        background: rgba(239, 68, 68, .18);
        border: 1px solid #ef4444;
        border-radius: 7px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 700;
        color: #ef4444;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    /* Anim keyframes — exact timing from user page */
    @keyframes anim-float {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-5px)
        }
    }

    @keyframes anim-bounce {

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

    @keyframes anim-sliL {
        from {
            transform: translateX(-20px);
            opacity: 0
        }

        to {
            transform: none;
            opacity: 1
        }
    }

    @keyframes anim-sliR {
        from {
            transform: translateX(20px);
            opacity: 0
        }

        to {
            transform: none;
            opacity: 1
        }
    }

    @keyframes anim-pulse {

        0%,
        100% {
            transform: scale(1)
        }

        50% {
            transform: scale(1.07)
        }
    }

    @keyframes anim-zoom {
        from {
            transform: scale(.8);
            opacity: 0
        }

        to {
            transform: scale(1);
            opacity: 1
        }
    }
</style>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h1 style="font-size:17px;font-weight:800;margin:0">Hero Banner Editor</h1>
        <nav>
            <ol class="breadcrumb bc" style="margin:2px 0 0">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Hero Banner</li>
            </ol>
        </nav>
    </div>
    <div style="font-size:11px;color:var(--mut);display:flex;align-items:center;gap:6px">
        <i class="ph ph-magic-wand" style="color:#3b82f6"></i>
        Preview akurat · 8 elemen · 96 kolom offset
    </div>
</div>

<form method="POST" id="hbeForm">
    <input type="hidden" name="action" value="save" />

    <div class="hbe-wrap">

        <!-- ═══════════════════ LEFT PANEL ══════════════════════════ -->
        <div class="hbe-panel">

            <div class="hbe-topbar">
                <div style="display:flex;align-items:center;gap:7px">
                    <i class="ph ph-sliders-horizontal" style="color:#3b82f6;font-size:15px"></i>
                    <span style="font-size:13px;font-weight:700">Editor</span>
                    <span class="hbe-unsaved" id="unsavedDot"></span>
                </div>
                <span style="font-size:10px;color:var(--mut)">1 banner · realtime</span>
            </div>

            <div class="hbe-tabs">
                <button type="button" class="hbe-tab active" onclick="hbeTab(this,'tabBg')"><i class="ph ph-paint-bucket"></i> BG</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabContent')"><i class="ph ph-text-aa"></i> Konten</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabImages')"><i class="ph ph-images"></i> Gambar</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabOffsets')"><i class="ph ph-arrows-out"></i> Offset</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabSettings')"><i class="ph ph-gear-six"></i></button>
            </div>

            <!-- ───────────────── TAB: Background ───────────────────── -->
            <div class="hbe-fields" id="tabBg">

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-layout" style="color:#3b82f6"></i>Tipe Layout</div>
                    <div class="hbe-pills">
                        <?php foreach (['layout' => ['ph-layout', 'Kiri·Tengah·Kanan'], 'image_center' => ['ph-frame-corners', 'Gambar Tengah'], 'image_only' => ['ph-image', 'Full BG']] as $v => [$ic, $lb]): ?>
                            <div class="hbe-pill <?= $b['type'] === $v ? 'on' : '' ?>" onclick="hbeSetType('<?= $v ?>')">
                                <input type="radio" name="type" value="<?= $v ?>" <?= $b['type'] === $v ? 'checked' : '' ?> style="display:none" />
                                <i class="ph <?= $ic ?>"></i><span style="font-size:9px"><?= $lb ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-image" style="color:#a855f7"></i>Gambar BG <span style="font-weight:400;text-transform:none;font-size:10px;color:var(--mut)"> – override gradient</span></div>
                    <input type="text" name="bg_image" class="hbe-input" placeholder="https://…/bg.jpg"
                        value="<?= htmlspecialchars($b['bg_image'] ?? '') ?>"
                        oninput="livePreview();hbeImgThumb(this,'bgThumb')" />
                    <img id="bgThumb" class="hbe-img-prev" src="<?= htmlspecialchars($b['bg_image'] ?? '') ?>" alt=""
                        style="<?= !empty($b['bg_image']) ? 'display:block' : '' ?>" />
                </div>

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-gradient" style="color:#f59e0b"></i>Gradient</div>
                    <div id="gradBar" class="hbe-grad-bar"
                        style="background:linear-gradient(<?= $b['bg_gradient_angle'] ?>deg,<?= $b['bg_color_start'] ?>,<?= $b['bg_color_end'] ?>)"></div>
                    <div class="hbe-row">
                        <div class="hbe-col">
                            <label class="hbe-label">Warna Awal</label>
                            <div class="hbe-color-row">
                                <input type="color" name="bg_color_start" class="hbe-swatch-inp" id="gc_start"
                                    value="<?= htmlspecialchars($b['bg_color_start'] ?? '#005bb5') ?>"
                                    oninput="syncHex(this,'gcS');livePreview();updateGradBar()" />
                                <input type="text" id="gcS" class="hbe-input" maxlength="7"
                                    style="font-family:'JetBrains Mono',monospace;font-size:11px"
                                    value="<?= htmlspecialchars($b['bg_color_start'] ?? '#005bb5') ?>"
                                    oninput="syncPicker(this,'gc_start');livePreview();updateGradBar()" />
                            </div>
                            <div class="hbe-palette">
                                <?php foreach (['#005bb5', '#0f172a', '#7c3aed', '#065f46', '#9a3412', '#1e1b4b', '#0c4a6e', '#7f1d1d'] as $c): ?>
                                    <div class="hbe-dot" style="background:<?= $c ?>" onclick="setColor('gc_start','gcS','<?= $c ?>')"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="hbe-col">
                            <label class="hbe-label">Warna Akhir</label>
                            <div class="hbe-color-row">
                                <input type="color" name="bg_color_end" class="hbe-swatch-inp" id="gc_end"
                                    value="<?= htmlspecialchars($b['bg_color_end'] ?? '#0099ff') ?>"
                                    oninput="syncHex(this,'gcE');livePreview();updateGradBar()" />
                                <input type="text" id="gcE" class="hbe-input" maxlength="7"
                                    style="font-family:'JetBrains Mono',monospace;font-size:11px"
                                    value="<?= htmlspecialchars($b['bg_color_end'] ?? '#0099ff') ?>"
                                    oninput="syncPicker(this,'gc_end');livePreview();updateGradBar()" />
                            </div>
                            <div class="hbe-palette">
                                <?php foreach (['#0099ff', '#38bdf8', '#a78bfa', '#34d399', '#fb923c', '#f472b6', '#facc15', '#4ade80'] as $c): ?>
                                    <div class="hbe-dot" style="background:<?= $c ?>" onclick="setColor('gc_end','gcE','<?= $c ?>')"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <label class="hbe-label" style="margin-top:8px">Sudut Gradient</label>
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="range" name="bg_gradient_angle" min="0" max="360"
                            value="<?= (int)($b['bg_gradient_angle'] ?? 135) ?>"
                            style="flex:1;accent-color:#3b82f6"
                            oninput="document.getElementById('gradAngleVal').textContent=this.value+'°';livePreview();updateGradBar()" />
                        <span id="gradAngleVal" style="font-size:12px;font-weight:700;color:var(--sub);min-width:38px;text-align:right;font-family:'JetBrains Mono',monospace"><?= (int)$b['bg_gradient_angle'] ?>°</span>
                    </div>
                </div>

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-arrows-vertical" style="color:#10b981"></i>Tinggi Banner</div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="range" name="height" min="60" max="280"
                            value="<?= (int)($b['height'] ?? 160) ?>"
                            style="flex:1;accent-color:#10b981"
                            oninput="document.getElementById('heightVal').textContent=this.value+'px';livePreview()" />
                        <span id="heightVal" style="font-size:12px;font-weight:700;color:var(--sub);min-width:42px;text-align:right;font-family:'JetBrains Mono',monospace"><?= (int)$b['height'] ?>px</span>
                    </div>
                </div>

            </div><!-- /tabBg -->

            <!-- ───────────────── TAB: Content ──────────────────────── -->
            <div class="hbe-fields" id="tabContent" style="display:none">

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-align-center-horizontal" style="color:#3b82f6"></i>Isi Tengah</div>
                    <div class="hbe-pills">
                        <?php foreach (['text' => ['ph-text-aa', 'Teks & Tombol'], 'image' => ['ph-image-square', 'Gambar']] as $v => [$ic, $lb]): ?>
                            <div class="hbe-pill <?= ($b['center_type'] ?? 'text') === $v ? 'on' : '' ?>" onclick="hbeSetCenterType('<?= $v ?>')">
                                <input type="radio" name="center_type" value="<?= $v ?>" <?= ($b['center_type'] ?? 'text') === $v ? 'checked' : '' ?> style="display:none" />
                                <i class="ph <?= $ic ?>"></i> <?= $lb ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Text fields -->
                <div id="ctText" style="<?= ($b['center_type'] ?? 'text') === 'image' ? 'display:none' : '' ?>">
                    <div class="hbe-sec">
                        <div class="hbe-sec-title"><i class="ph ph-text-h" style="color:#f59e0b"></i>Teks</div>
                        <div style="display:flex;gap:6px;align-items:flex-end;margin-bottom:10px">
                            <div><label class="hbe-label">Warna</label>
                                <input type="color" name="title_color" class="hbe-swatch-inp"
                                    value="<?= htmlspecialchars($b['title_color'] ?? '#ffffff') ?>" oninput="livePreview()" />
                            </div>
                            <div style="flex:1"><label class="hbe-label">Judul</label>
                                <input type="text" name="title" class="hbe-input" maxlength="200" placeholder="KLAIM HADIAH"
                                    value="<?= htmlspecialchars($b['title'] ?? '') ?>" oninput="livePreview()" />
                            </div>
                        </div>
                        <div style="display:flex;gap:6px;align-items:flex-end">
                            <div><label class="hbe-label">Warna</label>
                                <input type="color" name="subtitle_color" class="hbe-swatch-inp"
                                    value="<?= htmlspecialchars($b['subtitle_color'] ?? '#ffffffd9') ?>" oninput="livePreview()" />
                            </div>
                            <div style="flex:1"><label class="hbe-label">Subtitle</label>
                                <input type="text" name="subtitle" class="hbe-input" maxlength="300" placeholder="& Jutaan Rupiah"
                                    value="<?= htmlspecialchars($b['subtitle'] ?? '') ?>" oninput="livePreview()" />
                            </div>
                        </div>
                    </div>
                    <div class="hbe-sec">
                        <div class="hbe-sec-title"><i class="ph ph-cursor-click" style="color:#10b981"></i>Tombol <span style="font-weight:400;text-transform:none;font-size:10px;color:var(--mut)">(kosong = tidak tampil)</span></div>
                        <div class="hbe-row">
                            <div class="hbe-col"><label class="hbe-label">Teks</label>
                                <input type="text" name="btn_text" class="hbe-input" maxlength="80" placeholder="SERBU"
                                    value="<?= htmlspecialchars($b['btn_text'] ?? '') ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col"><label class="hbe-label">Link</label>
                                <input type="text" name="btn_href" class="hbe-input" maxlength="255" placeholder="#"
                                    value="<?= htmlspecialchars($b['btn_href'] ?? '#') ?>" />
                            </div>
                        </div>
                        <div class="hbe-row">
                            <div class="hbe-col"><label class="hbe-label">BG Tombol</label>
                                <input type="color" name="btn_color" class="hbe-swatch-inp" style="width:100%;height:32px"
                                    value="<?= htmlspecialchars($b['btn_color'] ?? '#FFD700') ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col"><label class="hbe-label">Teks Tombol</label>
                                <input type="color" name="btn_text_color" class="hbe-swatch-inp" style="width:100%;height:32px"
                                    value="<?= htmlspecialchars($b['btn_text_color'] ?? '#000000') ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col"><label class="hbe-label">Animasi</label>
                                <?= anim_sel('btn_anim', $b['btn_anim'] ?? 'pulse') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Center image fields -->
                <div id="ctImg" style="<?= ($b['center_type'] ?? 'text') !== 'image' ? 'display:none' : '' ?>">
                    <div class="hbe-sec">
                        <div class="hbe-sec-title"><i class="ph ph-image-square" style="color:#a855f7"></i>Gambar Tengah</div>
                        <label class="hbe-label">URL</label>
                        <input type="text" name="center_image" class="hbe-input" placeholder="https://…/center.png"
                            value="<?= htmlspecialchars($b['center_image'] ?? '') ?>"
                            oninput="livePreview();hbeImgThumb(this,'ciThumb')" />
                        <img id="ciThumb" class="hbe-img-prev" src="<?= htmlspecialchars($b['center_image'] ?? '') ?>" alt=""
                            style="<?= !empty($b['center_image']) ? 'display:block' : '' ?>" />
                        <div class="hbe-row" style="margin-top:10px">
                            <div class="hbe-col"><label class="hbe-label">Lebar (px)</label>
                                <input type="number" name="center_image_width" class="hbe-input" min="20" max="600"
                                    value="<?= (int)($b['center_image_width'] ?? 160) ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col"><label class="hbe-label">Animasi</label>
                                <?= anim_sel('center_image_anim', $b['center_image_anim'] ?? '') ?></div>
                        </div>
                    </div>
                </div>

            </div><!-- /tabContent -->

            <!-- ───────────────── TAB: Images ───────────────────────── -->
            <div class="hbe-fields" id="tabImages" style="display:none">

                <div id="sideImgSec">
                    <?php foreach (
                        [
                            ['img_left_', 'ph-align-left',  '#a855f7', 'Kiri',  'img_left',  'img_left_width',  'img_left_anim',  'ilThumb'],
                            ['img_right_', 'ph-align-right', '#f59e0b', 'Kanan', 'img_right', 'img_right_width', 'img_right_anim', 'irThumb'],
                        ] as [$pfx, $ic, $clr, $lbl, $fn, $fnw, $fna, $tid]
                    ): ?>
                        <div class="hbe-sec">
                            <div class="hbe-sec-title"><i class="ph <?= $ic ?>" style="color:<?= $clr ?>"></i>Gambar <?= $lbl ?></div>
                            <label class="hbe-label">URL</label>
                            <input type="text" name="<?= $fn ?>" class="hbe-input" placeholder="https://…/<?= strtolower($lbl) ?>.png"
                                value="<?= htmlspecialchars($b[$fn] ?? '') ?>"
                                oninput="livePreview();hbeImgThumb(this,'<?= $tid ?>')" />
                            <img id="<?= $tid ?>" class="hbe-img-prev" src="<?= htmlspecialchars($b[$fn] ?? '') ?>" alt=""
                                style="<?= !empty($b[$fn]) ? 'display:block' : '' ?>" />
                            <div class="hbe-row" style="margin-top:10px">
                                <div class="hbe-col"><label class="hbe-label">Lebar (px)</label>
                                    <input type="number" name="<?= $fnw ?>" class="hbe-input" min="20" max="300"
                                        value="<?= (int)($b[$fnw] ?? 90) ?>" oninput="livePreview()" />
                                </div>
                                <div class="hbe-col"><label class="hbe-label">Animasi</label>
                                    <?= anim_sel($fna, $b[$fna] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="noSideNote" class="hbe-tip" style="display:none">
                    <i class="ph ph-info" style="color:#3b82f6;flex-shrink:0"></i>
                    Gambar kiri/kanan hanya tersedia untuk tipe <strong>Kiri·Tengah·Kanan</strong>.
                </div>

            </div><!-- /tabImages -->

            <!-- ───────────────── TAB: Offset ───────────────────────── -->
            <div class="hbe-fields" id="tabOffsets" style="display:none">

                <?php
                $offsetDefs = [
                    ['strip_',      'ph-frame-corners',      '#64748b', 'Strip (hd-strip)',       ''],
                    ['img_left_',   'ph-align-left',          '#a855f7', 'Gambar Kiri (hd-side)',  'sideOffsetEl'],
                    ['center_',     'ph-align-center-horizontal', '#3b82f6', 'Blok Tengah (hd-center)', ''],
                    ['title_',      'ph-text-h',             '#f59e0b', 'Judul (hd-title)',       ''],
                    ['sub_',        'ph-text-align-left',    '#10b981', 'Subtitle (hd-sub)',      ''],
                    ['center_img_', 'ph-image-square',       '#a855f7', 'Gambar Tengah (hd-center-img)', ''],
                    ['btn_',        'ph-cursor-click',       '#ef4444', 'Tombol (hd-btn)',        ''],
                    ['img_right_',  'ph-align-right',        '#f59e0b', 'Gambar Kanan (hd-side)', 'sideOffsetEl'],
                ];
                foreach ($offsetDefs as [$pfx, $ic, $clr, $label, $extraId]):
                ?>
                    <div class="hbe-sec <?= $extraId ?>">
                        <div class="hbe-sec-title"><i class="ph <?= $ic ?>" style="color:<?= $clr ?>"></i><?= $label ?></div>
                        <?php hbe_offset_group($pfx, $b); ?>
                    </div>
                <?php endforeach; ?>

                <div class="hbe-tip">
                    <i class="ph ph-lightbulb" style="color:#f59e0b;flex-shrink:0"></i>
                    <span>Format: <code style="font-family:'JetBrains Mono',monospace;font-size:10px">10px</code> / <code style="font-family:'JetBrains Mono',monospace;font-size:10px">-5px</code> / <code style="font-family:'JetBrains Mono',monospace;font-size:10px">50%</code>. Kosong = tidak override. Posisi otomatis tambah <code style="font-size:10px">position:relative</code>.</span>
                </div>
            </div><!-- /tabOffsets -->

            <!-- ───────────────── TAB: Settings ─────────────────────── -->
            <div class="hbe-fields" id="tabSettings" style="display:none">

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-toggle-right" style="color:#10b981"></i>Status</div>
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="font-size:13px;font-weight:600;margin-bottom:2px">Aktifkan Banner</div>
                            <div style="font-size:11px;color:var(--mut)">Tampilkan di halaman utama app</div>
                        </div>
                        <label class="hbe-sw">
                            <input type="checkbox" name="is_active" id="isActiveCk" <?= $b['is_active'] ? 'checked' : '' ?>
                                onchange="hbeSwitch(this);markUnsaved();livePreview()" />
                            <span class="hbe-sw-track" id="isActiveTrack"
                                style="background:<?= $b['is_active'] ? '#3b82f6' : 'rgba(255,255,255,.12)' ?>">
                                <span class="hbe-sw-dot" style="left:<?= $b['is_active'] ? '19px' : '3px' ?>"></span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-database" style="color:#3b82f6"></i>Info</div>
                    <?php foreach ([['ID', '#' . $b['id']], ['Tipe', $b['type']], ['Dibuat', date('d M Y · H:i', strtotime($b['created_at']))], ['Update', date('d M Y · H:i', strtotime($b['updated_at']))]] as [$k, $v]): ?>
                        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)">
                            <span style="font-size:11px;color:var(--mut)"><?= $k ?></span>
                            <span style="font-size:11px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--sub)"><?= htmlspecialchars($v) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="hbe-tip">
                    <i class="ph ph-lightbulb" style="color:#f59e0b;flex-shrink:0"></i>
                    <span>Tinggi ideal 140–180px. Gunakan PNG/WebP transparan untuk gambar kiri/kanan. Offset di tab <strong>Offset</strong> untuk fine-tuning posisi tiap elemen.</span>
                </div>
            </div><!-- /tabSettings -->

            <!-- Save bar -->
            <div class="hbe-savebar">
                <div style="font-size:11px;color:var(--mut);display:flex;align-items:center;gap:4px">
                    <i class="ph ph-cloud-check" id="saveIco"></i>
                    <span id="saveTxt">Tersimpan</span>
                    <span class="hbe-unsaved" id="unsavedDot"></span>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"
                    style="border-radius:8px;font-size:12.5px;padding:7px 22px;display:flex;align-items:center;gap:6px">
                    <i class="ph ph-floppy-disk"></i> Simpan
                </button>
            </div>

        </div><!-- /hbe-panel -->

        <!-- ═══════════════════ RIGHT PANEL ═════════════════════════ -->
        <div class="hbe-preview">
            <div class="hbe-prev-bar">
                <div style="display:flex;align-items:center;gap:7px">
                    <i class="ph ph-eye" style="color:#3b82f6;font-size:15px"></i>
                    <span style="font-size:13px;font-weight:700">Live Preview</span>
                    <span style="font-size:10px;background:rgba(59,130,246,.1);padding:2px 8px;border-radius:99px;color:#3b82f6;font-weight:600">Akurat · realtime</span>
                </div>
                <span style="font-size:11px;color:var(--mut)">Tampilan persis seperti di app user</span>
            </div>

            <!-- ── Preview canvas: sama persis dengan .hero di user page ── -->
            <div class="hbe-prev-stage">
                <div id="prevCanvas"
                    style="background:<?= !empty($b['bg_image']) ? "url('" . htmlspecialchars($b['bg_image']) . "') center/cover no-repeat" : "linear-gradient({$b['bg_gradient_angle']}deg,{$b['bg_color_start']},{$b['bg_color_end']}" ?>)">

                    <!-- .hero-ov -->
                    <div id="prevHeroOv"></div>

                    <!-- Inactive overlay -->
                    <div id="prevInactiveOv" style="<?= $b['is_active'] ? 'display:none' : '' ?>">
                        <span class="hbe-inactive-badge"><i class="ph ph-eye-slash"></i>Banner Nonaktif — tidak tampil di app</span>
                    </div>

                    <!-- .hd-strip — exact same class, driven by strip_ offset columns -->
                    <div id="prevStrip"
                        style="<?= _hbStyle($b, 'strip_', ['padding-bottom' => ($b['strip_pb'] ?? '44px')]) ?>">

                        <!-- .hd-side kiri -->
                        <div id="prevLeft"
                            style="<?= _hbStyle($b, 'img_left_', ['width' => ($b['type'] === 'layout' && !empty($b['img_left']) ? (int)$b['img_left_width'] . 'px' : '0'), 'height' => (int)$b['height'] . 'px']) ?>">
                            <?php if (!empty($b['img_left']) && $b['type'] === 'layout'): ?>
                                <img src="<?= htmlspecialchars($b['img_left']) ?>"
                                    style="width:100%;max-height:<?= (int)$b['height'] ?>px;object-fit:contain;<?= animCSS_php($b['img_left_anim'] ?? '') ?>" />
                            <?php endif; ?>
                        </div>

                        <!-- .hd-center -->
                        <div id="prevCenter"
                            style="<?= _hbStyle($b, 'center_', ['min-height' => (int)$b['height'] . 'px']) ?>">

                            <?php if (($b['center_type'] ?? 'text') === 'image' && !empty($b['center_image'])): ?>
                                <!-- .hd-center-img -->
                                <img id="prevCenterImg" src="<?= htmlspecialchars($b['center_image']) ?>"
                                    style="width:<?= (int)$b['center_image_width'] ?>px;max-height:<?= (int)$b['height'] - 20 ?>px;<?= _hbStyle($b, 'center_img_') ?>;<?= animCSS_php($b['center_image_anim'] ?? '') ?>" />
                            <?php else: ?>
                                <?php if (!empty($b['title'])): ?>
                                    <!-- .hd-title -->
                                    <div id="prevTitle"
                                        style="color:<?= htmlspecialchars($b['title_color'] ?? '#fff') ?>;<?= _hbStyle($b, 'title_') ?>">
                                        <?= htmlspecialchars($b['title']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($b['subtitle'])): ?>
                                    <!-- .hd-sub -->
                                    <div id="prevSub"
                                        style="color:<?= htmlspecialchars($b['subtitle_color'] ?? '#ffffffd9') ?>;<?= _hbStyle($b, 'sub_') ?>">
                                        <?= htmlspecialchars($b['subtitle']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($b['btn_text'])): ?>
                                <!-- .hd-btn -->
                                <a id="prevBtn" href="<?= htmlspecialchars($b['btn_href'] ?? '#') ?>"
                                    style="background:<?= htmlspecialchars($b['btn_color'] ?? '#FFD700') ?>;color:<?= htmlspecialchars($b['btn_text_color'] ?? '#000') ?>;<?= _hbStyle($b, 'btn_') ?>;<?= animCSS_php($b['btn_anim'] ?? '') ?>">
                                    <?= htmlspecialchars($b['btn_text']) ?>
                                </a>
                            <?php endif; ?>

                        </div><!-- /hd-center -->

                        <!-- .hd-side kanan -->
                        <div id="prevRight"
                            style="<?= _hbStyle($b, 'img_right_', ['width' => ($b['type'] === 'layout' && !empty($b['img_right']) ? (int)$b['img_right_width'] . 'px' : '0'), 'height' => (int)$b['height'] . 'px']) ?>">
                            <?php if (!empty($b['img_right']) && $b['type'] === 'layout'): ?>
                                <img src="<?= htmlspecialchars($b['img_right']) ?>"
                                    style="width:100%;max-height:<?= (int)$b['height'] ?>px;object-fit:contain;<?= animCSS_php($b['img_right_anim'] ?? '') ?>" />
                            <?php endif; ?>
                        </div>

                    </div><!-- /hd-strip -->
                </div><!-- /prevCanvas -->
            </div>

            <!-- Ruler -->
            <div class="hbe-prev-ruler">
                <span>Tinggi: <strong id="rH"><?= (int)$b['height'] ?>px</strong></span>
                <span>Tipe: <strong id="rT"><?= htmlspecialchars($b['type']) ?></strong></span>
                <span>Tengah: <strong id="rC"><?= htmlspecialchars($b['center_type'] ?? 'text') ?></strong></span>
                <span id="rS" style="<?= $b['is_active'] ? 'color:#10b981' : 'color:#ef4444' ?>;margin-left:auto">
                    <i class="ph ph-circle-fill" style="font-size:6px;margin-right:3px"></i><?= $b['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </div>
        </div><!-- /hbe-preview -->

    </div><!-- /hbe-wrap -->
</form>

<?php
// PHP version of animCSS (for server-side render)
function animCSS_php(string $a): string
{
    return match ($a) {
        'float'       => 'animation:anim-float 3s ease-in-out infinite',
        'bounce'      => 'animation:anim-bounce 1.2s ease-in-out infinite',
        'slide-left'  => 'animation:anim-sliL .5s ease-out both',
        'slide-right' => 'animation:anim-sliR .5s ease-out both',
        'pulse'       => 'animation:anim-pulse 1.5s ease-in-out infinite',
        'zoom-in'     => 'animation:anim-zoom .4s ease-out both',
        default       => '',
    };
}

// ── Build JS-side DATA object (all 96 offset cols + main fields) ─
// We pass the full $b row as JSON so JS can replicate _hbStyle() exactly.
$b_json = json_encode($b, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

$page_scripts = <<<SCRIPT
<script>
// ── Row data from DB (used by JS _hbStyle) ──
const HB = $b_json;

// ─── Tabs ────────────────────────────────────────────────────
function hbeTab(btn, id) {
  document.querySelectorAll('.hbe-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  ['tabBg','tabContent','tabImages','tabOffsets','tabSettings'].forEach(t => {
    const el = document.getElementById(t);
    if (el) el.style.display = t === id ? '' : 'none';
  });
}

// ─── Type pills ──────────────────────────────────────────────
function hbeSetType(v) {
  document.querySelectorAll('input[name="type"]').forEach(r => r.checked = r.value === v);
  document.querySelectorAll('[name="type"]').closest
  document.querySelectorAll('.hbe-pill').forEach(p => {
    const r = p.querySelector('input[name="type"]');
    if (r) p.classList.toggle('on', r.checked);
  });
  const isLayout = v === 'layout';
  ['sideImgSec'].forEach(id => { const el=document.getElementById(id); if(el) el.style.display=isLayout?'':'none'; });
  ['noSideNote'].forEach(id => { const el=document.getElementById(id); if(el) el.style.display=isLayout?'none':''; });
  // Also hide side offset groups when not layout
  document.querySelectorAll('.sideOffsetEl').forEach(el => el.style.display = isLayout ? '' : 'none');
  markUnsaved(); livePreview();
}

function hbeSetCenterType(v) {
  document.querySelectorAll('input[name="center_type"]').forEach(r => r.checked = r.value === v);
  document.querySelectorAll('#tabContent .hbe-pill').forEach(p => {
    const r = p.querySelector('input[name="center_type"]');
    if (r) p.classList.toggle('on', r.checked);
  });
  document.getElementById('ctText').style.display = v === 'text'  ? '' : 'none';
  document.getElementById('ctImg').style.display  = v === 'image' ? '' : 'none';
  markUnsaved(); livePreview();
}

// ─── Color helpers ───────────────────────────────────────────
function syncHex(picker, hexId) { const el=document.getElementById(hexId); if(el) el.value=picker.value; }
function syncPicker(hex, picId)  { if(/^#[0-9a-fA-F]{6}$/.test(hex.value)) { const el=document.getElementById(picId); if(el) el.value=hex.value; } }
function setColor(picId,hexId,c) {
  const p=document.getElementById(picId), h=document.getElementById(hexId);
  if(p) p.value=c; if(h) h.value=c;
  markUnsaved(); livePreview(); updateGradBar();
}
function updateGradBar() {
  const bar=document.getElementById('gradBar'); if(!bar) return;
  const s=document.getElementById('gc_start')?.value||'#005bb5';
  const e=document.getElementById('gc_end')?.value  ||'#0099ff';
  const a=document.querySelector('input[name="bg_gradient_angle"]')?.value||135;
  bar.style.background=`linear-gradient(\${a}deg,\${s},\${e})`;
}

// ─── Image thumb ─────────────────────────────────────────────
function hbeImgThumb(input,imgId) {
  const img=document.getElementById(imgId); if(!img) return;
  img.src=input.value.trim();
  img.style.display=input.value.trim()?'block':'none';
}

// ─── Toggle switch ───────────────────────────────────────────
function hbeSwitch(inp) {
  const track=document.getElementById('isActiveTrack'); if(!track) return;
  const dot=track.querySelector('.hbe-sw-dot');
  track.style.background=inp.checked?'#3b82f6':'rgba(255,255,255,.12)';
  if(dot) dot.style.left=inp.checked?'19px':'3px';
}

// ─── Unsaved ─────────────────────────────────────────────────
let _dirty = false;
function markUnsaved() {
  _dirty = true;
  document.getElementById('unsavedDot')?.classList.add('show');
  const txt=document.getElementById('saveTxt'), ico=document.getElementById('saveIco');
  if(txt) txt.textContent='Belum tersimpan';
  if(ico) { ico.className='ph ph-warning'; ico.style.color='#f59e0b'; }
}
document.getElementById('hbeForm').addEventListener('input', markUnsaved);
document.getElementById('hbeForm').addEventListener('submit', () => { _dirty=false; });
window.addEventListener('beforeunload', e => { if(_dirty){e.preventDefault();e.returnValue='';} });

// ─── Get form value ──────────────────────────────────────────
function gv(name) {
  const el=document.querySelector(`[name="\${name}"]`);
  if(!el) return '';
  if(el.type==='checkbox') return el.checked;
  return el.value??'';
}

// ─── JS mirror of PHP _hbStyle() ─────────────────────────────
// Reads current FORM values (not HB object) for the 12 offset cols.
// Combines with extra CSS props.
function hbStyle(prefix, extra={}) {
  const parts = [];
  const PAD = [['pt','padding-top'],['pr','padding-right'],['pb','padding-bottom'],['pl','padding-left']];
  const MAR = [['mt','margin-top'], ['mr','margin-right'], ['mb','margin-bottom'],['ml','margin-left']];
  const POS = [['top','top'],['right','right'],['bottom','bottom'],['left','left']];
  let hasPos = false;

  for (const [sfx, prop] of [...PAD,...MAR]) {
    const v = gv(prefix + sfx);
    if (v) parts.push(`\${prop}:\${v}`);
  }
  for (const [sfx, prop] of POS) {
    const v = gv(prefix + sfx);
    if (v) { parts.push(`\${prop}:\${v}`); hasPos = true; }
  }
  if (hasPos) parts.push('position:relative');

  for (const [prop, val] of Object.entries(extra)) {
    if (val != null && val !== '') parts.push(`\${prop}:\${val}`);
  }
  return parts.join(';');
}

// ─── Anim CSS ────────────────────────────────────────────────
const ANIM = {
  'float':       'animation:anim-float 3s ease-in-out infinite',
  'bounce':      'animation:anim-bounce 1.2s ease-in-out infinite',
  'slide-left':  'animation:anim-sliL .5s ease-out both',
  'slide-right': 'animation:anim-sliR .5s ease-out both',
  'pulse':       'animation:anim-pulse 1.5s ease-in-out infinite',
  'zoom-in':     'animation:anim-zoom .4s ease-out both',
};
function animCSS(a) { return ANIM[a]||''; }
function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ─── LIVE PREVIEW ────────────────────────────────────────────
// Mirrors exactly how user page PHP renders the banner:
//   background → canvas
//   hd-strip   → prevStrip  (strip_ offsets)
//   hd-side L  → prevLeft   (img_left_ offsets)
//   hd-center  → prevCenter (center_ offsets)
//   hd-title   → prevTitle  (title_ offsets)
//   hd-sub     → prevSub    (sub_ offsets)
//   hd-center-img → prevCenterImg (center_img_ offsets)
//   hd-btn     → prevBtn    (btn_ offsets)
//   hd-side R  → prevRight  (img_right_ offsets)
function livePreview() {
  const type     = gv('type')    || 'layout';
  const bgImg    = gv('bg_image');
  const bgS      = gv('bg_color_start') || '#005bb5';
  const bgE      = gv('bg_color_end')   || '#0099ff';
  const bgA      = gv('bg_gradient_angle') || 135;
  const h        = Math.max(60, parseInt(gv('height'))||160);
  const imgL     = gv('img_left'),  imgLW=parseInt(gv('img_left_width'))||90,  imgLA=gv('img_left_anim');
  const imgR     = gv('img_right'), imgRW=parseInt(gv('img_right_width'))||90, imgRA=gv('img_right_anim');
  const cType    = gv('center_type') || 'text';
  const title    = gv('title'),    titClr=gv('title_color')    ||'#fff';
  const sub      = gv('subtitle'), subClr=gv('subtitle_color') ||'#ffffffd9';
  const ci       = gv('center_image'), ciW=parseInt(gv('center_image_width'))||160, ciA=gv('center_image_anim');
  const btnTxt   = gv('btn_text'), btnBg=gv('btn_color')||'#FFD700', btnClr=gv('btn_text_color')||'#000', btnHref=gv('btn_href')||'#', btnA=gv('btn_anim');
  const isActive = gv('is_active');

  // 1. Canvas background
  const canvas = document.getElementById('prevCanvas');
  if (canvas) canvas.style.background = bgImg
    ? `url('\${bgImg}') center/cover no-repeat`
    : `linear-gradient(\${bgA}deg,\${bgS},\${bgE})`;

  // 2. Inactive overlay
  const ov = document.getElementById('prevInactiveOv');
  if (ov) ov.style.display = isActive ? 'none' : '';

  // 3. Ruler
  const rH=document.getElementById('rH'), rT=document.getElementById('rT'),
        rC=document.getElementById('rC'), rS=document.getElementById('rS');
  if(rH) rH.textContent=h+'px';
  if(rT) rT.textContent=type;
  if(rC) rC.textContent=cType;
  if(rS) { rS.style.color=isActive?'#10b981':'#ef4444';
    rS.innerHTML=`<i class="ph ph-circle-fill" style="font-size:6px;margin-right:3px"></i>\${isActive?'Aktif':'Nonaktif'}`; }

  // 4. hd-strip (strip_ offsets)
  const stripPb = gv('strip_pb') || '44px';
  const strip = document.getElementById('prevStrip');
  if (strip) strip.style.cssText = hbStyle('strip_', {'padding-bottom': stripPb});

  // 5. hd-side kiri (img_left_ offsets)
  const pL = document.getElementById('prevLeft');
  if (pL) {
    const showL = imgL && type === 'layout';
    pL.style.cssText = hbStyle('img_left_', {
      width:  showL ? imgLW+'px' : '0',
      height: h+'px',
    });
    pL.innerHTML = showL
      ? `<img src="\${imgL}" style="width:100%;max-height:\${h}px;object-fit:contain;\${animCSS(imgLA)}" />`
      : '';
  }

  // 6. hd-center (center_ offsets)
  const pC = document.getElementById('prevCenter');
  if (pC) {
    pC.style.cssText = hbStyle('center_', {'min-height': h+'px'});
    let html = '';

    if (cType === 'image' && ci) {
      // hd-center-img (center_img_ offsets)
      const ciStyle = hbStyle('center_img_') + ';' + animCSS(ciA);
      html = `<img id="prevCenterImg" src="\${ci}"
        style="width:\${ciW}px;max-height:\${h-20}px;\${ciStyle}" />`;
    } else {
      // hd-title (title_ offsets)
      if (title) {
        const titStyle = hbStyle('title_');
        html += `<div id="prevTitle" style="color:\${escH(titClr)};\${titStyle}">\${escH(title)}</div>`;
      }
      // hd-sub (sub_ offsets)
      if (sub) {
        const subStyle = hbStyle('sub_');
        html += `<div id="prevSub" style="color:\${escH(subClr)};\${subStyle}">\${escH(sub)}</div>`;
      }
    }

    // hd-btn (btn_ offsets) — outside if/else, same as user page
    if (btnTxt) {
      const btnStyle = hbStyle('btn_') + ';' + animCSS(btnA);
      html += `<a id="prevBtn" href="\${escH(btnHref)}"
        style="background:\${escH(btnBg)};color:\${escH(btnClr)};\${btnStyle}">\${escH(btnTxt)}</a>`;
    }

    pC.innerHTML = html;
  }

  // 7. hd-side kanan (img_right_ offsets)
  const pR = document.getElementById('prevRight');
  if (pR) {
    const showR = imgR && type === 'layout';
    pR.style.cssText = hbStyle('img_right_', {
      width:  showR ? imgRW+'px' : '0',
      height: h+'px',
    });
    pR.innerHTML = showR
      ? `<img src="\${imgR}" style="width:100%;max-height:\${h}px;object-fit:contain;\${animCSS(imgRA)}" />`
      : '';
  }
}

// ─── Init ────────────────────────────────────────────────────
window.addEventListener('load', () => {
  updateGradBar();
  livePreview();
  const t = document.querySelector('input[name="type"]:checked')?.value || 'layout';
  hbeSetType(t);

  // Auto-dismiss toasts
  document.querySelectorAll('.toast-item').forEach(t => {
    setTimeout(() => t.style.opacity='0', 3500);
    setTimeout(() => t.remove(), 4000);
  });
});
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>