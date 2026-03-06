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
    $pdo->exec("INSERT INTO hero_banner (type,bg_color_start,bg_color_end,bg_gradient_angle,height,sort_order,is_active)
              VALUES ('layout','#005bb5','#0099ff',135,160,1,1)");
}
$banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();

// ── SAVE ──────────────────────────────────────────────────────
if ($action === 'save') {
    $f = $_POST;
    $type               = in_array($f['type'] ?? '', ['image_only', 'layout', 'image_center']) ? $f['type'] : 'layout';
    $bg_image           = trim($f['bg_image']           ?? '');
    $bg_color_start     = trim($f['bg_color_start']     ?? '#005bb5');
    $bg_color_end       = trim($f['bg_color_end']       ?? '#0099ff');
    $bg_gradient_angle  = (int)($f['bg_gradient_angle'] ?? 135);
    $height             = max(60, min(400, (int)($f['height'] ?? 160)));
    $img_left           = trim($f['img_left']           ?? '');
    $img_left_width     = (int)($f['img_left_width']    ?? 90);
    $img_left_anim      = trim($f['img_left_anim']      ?? '');
    $center_type        = in_array($f['center_type'] ?? '', ['text', 'image']) ? $f['center_type'] : 'text';
    $title              = trim($f['title']              ?? '');
    $title_color        = trim($f['title_color']        ?? '#ffffff');
    $subtitle           = trim($f['subtitle']           ?? '');
    $subtitle_color     = trim($f['subtitle_color']     ?? '#ffffffd9');
    $center_image       = trim($f['center_image']       ?? '');
    $center_image_width = (int)($f['center_image_width'] ?? 160);
    $center_image_anim  = trim($f['center_image_anim']  ?? '');
    $btn_text           = trim($f['btn_text']           ?? '');
    $btn_href           = trim($f['btn_href']           ?? '#');
    $btn_color          = trim($f['btn_color']          ?? '#FFD700');
    $btn_text_color     = trim($f['btn_text_color']     ?? '#000000');
    $btn_anim           = trim($f['btn_anim']           ?? 'pulse');
    $img_right          = trim($f['img_right']          ?? '');
    $img_right_width    = (int)($f['img_right_width']   ?? 90);
    $img_right_anim     = trim($f['img_right_anim']     ?? '');
    $is_active          = isset($f['is_active']) ? 1 : 0;

    $pdo->prepare("UPDATE hero_banner SET
    type=?,bg_image=?,bg_color_start=?,bg_color_end=?,bg_gradient_angle=?,height=?,
    img_left=?,img_left_width=?,img_left_anim=?,
    center_type=?,title=?,title_color=?,subtitle=?,subtitle_color=?,
    center_image=?,center_image_width=?,center_image_anim=?,
    btn_text=?,btn_href=?,btn_color=?,btn_text_color=?,btn_anim=?,
    img_right=?,img_right_width=?,img_right_anim=?,is_active=?
    WHERE id=?")
        ->execute([
            $type,
            $bg_image ?: null,
            $bg_color_start,
            $bg_color_end,
            $bg_gradient_angle,
            $height,
            $img_left ?: null,
            $img_left_width,
            $img_left_anim ?: null,
            $center_type,
            $title ?: null,
            $title_color,
            $subtitle ?: null,
            $subtitle_color,
            $center_image ?: null,
            $center_image_width,
            $center_image_anim ?: null,
            $btn_text ?: null,
            $btn_href,
            $btn_color,
            $btn_text_color,
            $btn_anim ?: null,
            $img_right ?: null,
            $img_right_width,
            $img_right_anim ?: null,
            $is_active,
            (int)$banner['id']
        ]);
    $toast = 'Hero banner berhasil disimpan.';
    $banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();
}

$ANIM_OPT = ['none' => 'Tidak Ada', 'float' => 'Float', 'bounce' => 'Bounce', 'slide-left' => 'Slide Kiri', 'slide-right' => 'Slide Kanan', 'pulse' => 'Pulse', 'zoom-in' => 'Zoom In'];

function anim_sel(string $name, string $cur): string
{
    global $ANIM_OPT;
    $o = "<select name=\"$name\" class=\"hbe-input\" onchange=\"livePreview();markUnsaved()\">";
    foreach ($ANIM_OPT as $v => $l) $o .= "<option value=\"$v\"" . ($cur === $v ? ' selected' : '') . ">$l</option>";
    return $o . "</select>";
}

require_once __DIR__ . '/includes/header.php';
$b = $banner;
?>

<!-- TOAST -->
<div class="toast-wrap">
    <?php if ($toast):   ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>
    <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<style>
    /* ══ Editor Shell ══════════════════════════════════════════ */
    .hbe-wrap {
        display: flex;
        gap: 0;
        height: calc(100vh - 128px);
        min-height: 580px;
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        background: var(--card);
    }

    /* ══ Left Panel ══════════════════════════════════════════ */
    .hbe-panel {
        width: 390px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        border-right: 1px solid var(--border);
    }

    .hbe-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 13px 16px;
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
        font-size: 11px;
        font-weight: 700;
        text-align: center;
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

    /* Section card */
    .hbe-sec {
        background: var(--hover);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 13px;
        margin-bottom: 10px;
    }

    .hbe-sec-title {
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: var(--mut);
        margin-bottom: 11px;
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

    .hbe-color-swatch {
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
        max-height: 40px;
        border-radius: 6px;
        object-fit: cover;
        margin-top: 6px;
        border: 1px solid var(--border);
        display: none;
    }

    /* Type pills */
    .hbe-type-pills {
        display: flex;
        gap: 5px;
    }

    .hbe-type-pill {
        flex: 1;
        padding: 7px 4px;
        border-radius: 8px;
        font-size: 10.5px;
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

    .hbe-type-pill.on {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, .15);
        color: #3b82f6;
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
    }

    .hbe-unsaved.show {
        display: inline-block;
    }

    /* Palette swatches */
    .hbe-palette {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        margin-top: 6px;
    }

    .hbe-swatch {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        cursor: pointer;
        border: 1.5px solid rgba(255, 255, 255, .1);
        transition: transform .12s;
    }

    .hbe-swatch:hover {
        transform: scale(1.35);
    }

    /* Tip box */
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

    /* ══ Right Panel: Preview ════════════════════════════════ */
    .hbe-preview {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--surface);
    }

    .hbe-preview-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 18px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }

    .hbe-preview-stage {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: flex-start;
        overflow: auto;
    }

    /* Preview wrapper — mirrors the actual .hero container */
    #prevCanvas {
        position: relative;
        overflow: hidden;
    }

    /* Exact copies of user-side CSS classes scoped to preview */
    #prevCanvas .hero-ov {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

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

    #prevCanvas .hero-deco {
        position: absolute;
        bottom: 0;
        pointer-events: none;
        z-index: 1;
    }

    /* hd-strip: exact match from user CSS */
    #prevCanvas .hd-strip {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-top: 14px;
        padding-bottom: 44px;
        pointer-events: none;
    }

    #prevCanvas .hd-strip a,
    #prevCanvas .hd-strip button {
        pointer-events: auto;
    }

    #prevCanvas .hd-side {
        display: flex;
        align-items: flex-end;
        flex-shrink: 0;
    }

    #prevCanvas .hd-side img {
        object-fit: contain;
        display: block;
    }

    #prevCanvas .hd-center {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        text-align: center;
        padding: 0 4px 6px;
    }

    #prevCanvas .hd-title {
        font-size: 16px;
        font-weight: 900;
        line-height: 1.15;
        letter-spacing: -.3px;
        text-shadow: 0 1px 6px rgba(0, 0, 0, .25);
        margin-bottom: 2px;
    }

    #prevCanvas .hd-sub {
        font-size: 10.5px;
        font-weight: 700;
        margin-bottom: 10px;
        opacity: .88;
        text-shadow: 0 1px 3px rgba(0, 0, 0, .2);
    }

    #prevCanvas .hd-center-img {
        max-width: 100%;
        object-fit: contain;
        margin-bottom: 10px;
    }

    #prevCanvas .hd-btn {
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

    #prevCanvas .hd-btn:active {
        transform: scale(.93);
    }

    /* Inactive overlay */
    #prevInactiveOv {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    .hbe-inactive-badge {
        background: rgba(239, 68, 68, .18);
        border: 1px solid #ef4444;
        border-radius: 6px;
        padding: 5px 12px;
        font-size: 11px;
        font-weight: 700;
        color: #ef4444;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Gradient preview bar */
    .hbe-grad-bar {
        height: 28px;
        border-radius: 7px;
        border: 1px solid var(--border);
        margin-bottom: 10px;
        transition: background .15s;
    }

    /* Scale ruler below preview */
    .hbe-prev-ruler {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 16px;
        border-top: 1px solid var(--border);
        font-size: 10px;
        color: var(--mut);
        flex-shrink: 0;
        background: var(--card);
    }

    /* Anim keyframes — match exactly user side */
    @keyframes hbe-float {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-5px)
        }
    }

    @keyframes hbe-pulse {

        0%,
        100% {
            transform: scale(1)
        }

        50% {
            transform: scale(1.08)
        }
    }

    @keyframes hbe-sliL {
        from {
            transform: translateX(-20px);
            opacity: 0
        }

        to {
            transform: none;
            opacity: 1
        }
    }

    @keyframes hbe-sliR {
        from {
            transform: translateX(20px);
            opacity: 0
        }

        to {
            transform: none;
            opacity: 1
        }
    }

    @keyframes hbe-bounce {

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

    @keyframes hbe-zoom {
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

<!-- Page header (compact) -->
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
        Edit langsung di panel kiri · preview realtime di kanan
    </div>
</div>

<form method="POST" id="hbeForm">
    <input type="hidden" name="action" value="save" />

    <div class="hbe-wrap">

        <!-- ═══════════════ LEFT PANEL ═══════════════════════════ -->
        <div class="hbe-panel">

            <!-- Topbar -->
            <div class="hbe-topbar">
                <div style="display:flex;align-items:center;gap:7px">
                    <i class="ph ph-sliders-horizontal" style="color:#3b82f6;font-size:15px"></i>
                    <span style="font-size:13px;font-weight:700">Editor Panel</span>
                    <span class="hbe-unsaved" id="unsavedDot"></span>
                </div>
                <span style="font-size:10px;color:var(--mut)">1 banner · selalu aktif</span>
            </div>

            <!-- Tabs -->
            <div class="hbe-tabs">
                <button type="button" class="hbe-tab active" onclick="hbeTab(this,'tabBg')"><i class="ph ph-paint-bucket"></i> BG</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabContent')"><i class="ph ph-text-aa"></i> Konten</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabImages')"><i class="ph ph-images"></i> Gambar</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabSettings')"><i class="ph ph-gear-six"></i> Setting</button>
            </div>

            <!-- ─── TAB: Background ─── -->
            <div class="hbe-fields" id="tabBg">

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-layout" style="color:#3b82f6"></i>Tipe Layout</div>
                    <div class="hbe-type-pills">
                        <?php foreach (['layout' => ['ph-layout', 'Kiri·Tengah·Kanan'], 'image_center' => ['ph-frame-corners', 'Gambar Tengah'], 'image_only' => ['ph-image', 'Full BG']] as $v => [$ic, $lb]): ?>
                            <div class="hbe-type-pill <?= $b['type'] === $v ? 'on' : '' ?>" onclick="hbeSetType('<?= $v ?>')">
                                <input type="radio" name="type" value="<?= $v ?>" <?= $b['type'] === $v ? 'checked' : '' ?> style="display:none" />
                                <i class="ph <?= $ic ?>"></i><span style="font-size:9.5px"><?= $lb ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-image" style="color:#a855f7"></i>Gambar BG <span style="font-weight:400;text-transform:none;font-size:10px;color:var(--mut)"> – override gradient</span></div>
                    <input type="text" name="bg_image" class="hbe-input" placeholder="https://…/banner-bg.jpg"
                        value="<?= htmlspecialchars($b['bg_image'] ?? '') ?>"
                        oninput="livePreview();hbeImgPrev(this,'bgPrev')" />
                    <img id="bgPrev" class="hbe-img-prev" src="<?= htmlspecialchars($b['bg_image'] ?? '') ?>" alt=""
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
                                <input type="color" name="bg_color_start" class="hbe-color-swatch" id="gc_start"
                                    value="<?= htmlspecialchars($b['bg_color_start'] ?? '#005bb5') ?>"
                                    oninput="syncHex(this,'gcStartHex');livePreview();updateGradBar()" />
                                <input type="text" id="gcStartHex" class="hbe-input" style="font-family:'JetBrains Mono',monospace;font-size:11px" maxlength="7"
                                    value="<?= htmlspecialchars($b['bg_color_start'] ?? '#005bb5') ?>"
                                    oninput="syncColor(this,'gc_start');livePreview();updateGradBar()" />
                            </div>
                            <div class="hbe-palette">
                                <?php foreach (['#005bb5', '#0f172a', '#7c3aed', '#065f46', '#9a3412', '#1e1b4b', '#0c4a6e', '#7f1d1d'] as $c): ?>
                                    <div class="hbe-swatch" style="background:<?= $c ?>" onclick="hbeSetColor('gc_start','gcStartHex','<?= $c ?>')"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="hbe-col">
                            <label class="hbe-label">Warna Akhir</label>
                            <div class="hbe-color-row">
                                <input type="color" name="bg_color_end" class="hbe-color-swatch" id="gc_end"
                                    value="<?= htmlspecialchars($b['bg_color_end'] ?? '#0099ff') ?>"
                                    oninput="syncHex(this,'gcEndHex');livePreview();updateGradBar()" />
                                <input type="text" id="gcEndHex" class="hbe-input" style="font-family:'JetBrains Mono',monospace;font-size:11px" maxlength="7"
                                    value="<?= htmlspecialchars($b['bg_color_end'] ?? '#0099ff') ?>"
                                    oninput="syncColor(this,'gc_end');livePreview();updateGradBar()" />
                            </div>
                            <div class="hbe-palette">
                                <?php foreach (['#0099ff', '#38bdf8', '#a78bfa', '#34d399', '#fb923c', '#f472b6', '#facc15', '#4ade80'] as $c): ?>
                                    <div class="hbe-swatch" style="background:<?= $c ?>" onclick="hbeSetColor('gc_end','gcEndHex','<?= $c ?>')"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <label class="hbe-label" style="margin-top:8px">Sudut</label>
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
                        <input type="range" name="height" min="60" max="280" id="heightRange"
                            value="<?= (int)($b['height'] ?? 160) ?>"
                            style="flex:1;accent-color:#10b981"
                            oninput="document.getElementById('heightVal').textContent=this.value+'px';livePreview()" />
                        <span id="heightVal" style="font-size:12px;font-weight:700;color:var(--sub);min-width:42px;text-align:right;font-family:'JetBrains Mono',monospace"><?= (int)$b['height'] ?>px</span>
                    </div>
                </div>

            </div><!-- /tabBg -->

            <!-- ─── TAB: Content ─── -->
            <div class="hbe-fields" id="tabContent" style="display:none">

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-align-center-horizontal" style="color:#3b82f6"></i>Isi Tengah</div>
                    <div class="hbe-type-pills">
                        <?php foreach (['text' => ['ph-text-aa', 'Teks & Tombol'], 'image' => ['ph-image-square', 'Gambar']] as $v => [$ic, $lb]): ?>
                            <div class="hbe-type-pill <?= ($b['center_type'] ?? 'text') === $v ? 'on' : '' ?>" onclick="hbeSetCenterType('<?= $v ?>')">
                                <input type="radio" name="center_type" value="<?= $v ?>" <?= ($b['center_type'] ?? 'text') === $v ? 'checked' : '' ?> style="display:none" />
                                <i class="ph <?= $ic ?>"></i> <?= $lb ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="contentTextFields" style="<?= ($b['center_type'] ?? 'text') === 'image' ? 'display:none' : '' ?>">
                    <div class="hbe-sec">
                        <div class="hbe-sec-title"><i class="ph ph-text-h" style="color:#f59e0b"></i>Teks</div>
                        <div style="display:flex;gap:6px;align-items:flex-end;margin-bottom:10px">
                            <div>
                                <label class="hbe-label">Warna</label>
                                <input type="color" name="title_color" class="hbe-color-swatch" value="<?= htmlspecialchars($b['title_color'] ?? '#ffffff') ?>" oninput="livePreview()" title="Warna judul" />
                            </div>
                            <div style="flex:1">
                                <label class="hbe-label">Judul</label>
                                <input type="text" name="title" class="hbe-input" maxlength="200" placeholder="KLAIM HADIAH"
                                    value="<?= htmlspecialchars($b['title'] ?? '') ?>" oninput="livePreview()" />
                            </div>
                        </div>
                        <div style="display:flex;gap:6px;align-items:flex-end">
                            <div>
                                <label class="hbe-label">Warna</label>
                                <input type="color" name="subtitle_color" class="hbe-color-swatch" value="<?= htmlspecialchars($b['subtitle_color'] ?? '#ffffffcc') ?>" oninput="livePreview()" title="Warna subtitle" />
                            </div>
                            <div style="flex:1">
                                <label class="hbe-label">Subtitle</label>
                                <input type="text" name="subtitle" class="hbe-input" maxlength="300" placeholder="& Jutaan Rupiah"
                                    value="<?= htmlspecialchars($b['subtitle'] ?? '') ?>" oninput="livePreview()" />
                            </div>
                        </div>
                    </div>

                    <div class="hbe-sec">
                        <div class="hbe-sec-title"><i class="ph ph-cursor-click" style="color:#10b981"></i>Tombol <span style="font-weight:400;text-transform:none;font-size:10px;color:var(--mut)">(kosong = tidak tampil)</span></div>
                        <div class="hbe-row">
                            <div class="hbe-col">
                                <label class="hbe-label">Teks</label>
                                <input type="text" name="btn_text" class="hbe-input" maxlength="80" placeholder="SERBU"
                                    value="<?= htmlspecialchars($b['btn_text'] ?? '') ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col">
                                <label class="hbe-label">Link</label>
                                <input type="text" name="btn_href" class="hbe-input" maxlength="255" placeholder="#"
                                    value="<?= htmlspecialchars($b['btn_href'] ?? '#') ?>" />
                            </div>
                        </div>
                        <div class="hbe-row">
                            <div class="hbe-col">
                                <label class="hbe-label">BG Tombol</label>
                                <input type="color" name="btn_color" class="hbe-color-swatch" style="width:100%;height:32px"
                                    value="<?= htmlspecialchars($b['btn_color'] ?? '#FFD700') ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col">
                                <label class="hbe-label">Teks Tombol</label>
                                <input type="color" name="btn_text_color" class="hbe-color-swatch" style="width:100%;height:32px"
                                    value="<?= htmlspecialchars($b['btn_text_color'] ?? '#000000') ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col">
                                <label class="hbe-label">Animasi</label>
                                <?= anim_sel('btn_anim', $b['btn_anim'] ?? 'pulse') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="contentImgFields" style="<?= ($b['center_type'] ?? 'text') !== 'image' ? 'display:none' : '' ?>">
                    <div class="hbe-sec">
                        <div class="hbe-sec-title"><i class="ph ph-image-square" style="color:#a855f7"></i>Gambar Tengah</div>
                        <label class="hbe-label">URL</label>
                        <input type="text" name="center_image" class="hbe-input" placeholder="https://…/center.png"
                            value="<?= htmlspecialchars($b['center_image'] ?? '') ?>"
                            oninput="livePreview();hbeImgPrev(this,'ciPrev')" />
                        <img id="ciPrev" class="hbe-img-prev" src="<?= htmlspecialchars($b['center_image'] ?? '') ?>" alt=""
                            style="<?= !empty($b['center_image']) ? 'display:block' : '' ?>" />
                        <div class="hbe-row" style="margin-top:10px">
                            <div class="hbe-col">
                                <label class="hbe-label">Lebar (px)</label>
                                <input type="number" name="center_image_width" class="hbe-input" min="20" max="600"
                                    value="<?= (int)($b['center_image_width'] ?? 160) ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col">
                                <label class="hbe-label">Animasi</label>
                                <?= anim_sel('center_image_anim', $b['center_image_anim'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /tabContent -->

            <!-- ─── TAB: Images ─── -->
            <div class="hbe-fields" id="tabImages" style="display:none">
                <div id="sideImgSection">
                    <?php foreach (['left' => ['ph-align-left', 'Kiri', '#a855f7', 'img_left', 'img_left_width', 'img_left_anim', 'ilPrev'], 'right' => ['ph-align-right', 'Kanan', '#f59e0b', 'img_right', 'img_right_width', 'img_right_anim', 'irPrev']] as $side => [$ic, $lbl, $clr, $fn, $fnw, $fna, $pid]): ?>
                        <div class="hbe-sec">
                            <div class="hbe-sec-title"><i class="ph <?= $ic ?>" style="color:<?= $clr ?>"></i>Gambar <?= $lbl ?></div>
                            <label class="hbe-label">URL</label>
                            <input type="text" name="<?= $fn ?>" class="hbe-input" placeholder="https://…/<?= $side ?>.png"
                                value="<?= htmlspecialchars($b[$fn] ?? '') ?>"
                                oninput="livePreview();hbeImgPrev(this,'<?= $pid ?>')" />
                            <img id="<?= $pid ?>" class="hbe-img-prev" src="<?= htmlspecialchars($b[$fn] ?? '') ?>" alt=""
                                style="<?= !empty($b[$fn]) ? 'display:block' : '' ?>" />
                            <div class="hbe-row" style="margin-top:10px">
                                <div class="hbe-col">
                                    <label class="hbe-label">Lebar (px)</label>
                                    <input type="number" name="<?= $fnw ?>" class="hbe-input" min="20" max="300"
                                        value="<?= (int)($b[$fnw] ?? 90) ?>" oninput="livePreview()" />
                                </div>
                                <div class="hbe-col">
                                    <label class="hbe-label">Animasi</label>
                                    <?= anim_sel($fna, $b[$fna] ?? '') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="noSideImgNote" class="hbe-tip" style="display:none">
                    <i class="ph ph-info" style="color:#3b82f6;flex-shrink:0"></i>
                    Gambar kiri/kanan hanya tersedia untuk tipe layout <strong>Kiri·Tengah·Kanan</strong>.
                </div>
            </div><!-- /tabImages -->

            <!-- ─── TAB: Settings ─── -->
            <div class="hbe-fields" id="tabSettings" style="display:none">
                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-toggle-right" style="color:#10b981"></i>Status Banner</div>
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="font-size:13px;font-weight:600;margin-bottom:2px">Aktifkan Banner</div>
                            <div style="font-size:11px;color:var(--mut)">Tampilkan di halaman utama app</div>
                        </div>
                        <label class="hbe-sw">
                            <input type="checkbox" name="is_active" id="isActiveChk" <?= $b['is_active'] ? 'checked' : '' ?>
                                onchange="hbeSwitch(this);markUnsaved();livePreview()" />
                            <span class="hbe-sw-track" id="isActiveTrack" style="background:<?= $b['is_active'] ? '#3b82f6' : 'rgba(255,255,255,.12)' ?>">
                                <span class="hbe-sw-dot" style="left:<?= $b['is_active'] ? '19px' : '3px' ?>"></span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-database" style="color:#3b82f6"></i>Info</div>
                    <?php foreach ([['ID', '#' . $b['id']], ['Tipe', $b['type']], ['Dibuat', date('d M Y · H:i', strtotime($b['created_at']))], ['Update', date('d M Y · H:i', strtotime($b['updated_at']))]] as [$k, $v]): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border)">
                            <span style="font-size:11px;color:var(--mut)"><?= $k ?></span>
                            <span style="font-size:11px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--sub)"><?= htmlspecialchars($v) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="hbe-tip">
                    <i class="ph ph-lightbulb" style="color:#f59e0b;flex-shrink:0"></i>
                    <span><strong>Tips:</strong> Gunakan PNG/WebP transparan untuk gambar kiri/kanan. Tinggi ideal 140–180px. Tes di preview wide untuk melihat tampilan layar besar.</span>
                </div>
            </div><!-- /tabSettings -->

            <!-- Save bar -->
            <div class="hbe-savebar">
                <div style="font-size:11px;color:var(--mut);display:flex;align-items:center;gap:5px">
                    <i class="ph ph-cloud-check" id="saveIco"></i>
                    <span id="saveTxt">Tersimpan</span>
                    <span class="hbe-unsaved" id="unsavedDot"></span>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;font-size:12.5px;padding:7px 22px;gap:6px;display:flex;align-items:center">
                    <i class="ph ph-floppy-disk"></i> Simpan
                </button>
            </div>

        </div><!-- /hbe-panel -->

        <!-- ═══════════════ RIGHT PANEL: Preview ═════════════════ -->
        <div class="hbe-preview">
            <div class="hbe-preview-bar">
                <div style="display:flex;align-items:center;gap:7px">
                    <i class="ph ph-eye" style="color:#3b82f6;font-size:15px"></i>
                    <span style="font-size:13px;font-weight:700">Live Preview</span>
                    <span style="font-size:10px;background:rgba(59,130,246,.1);padding:2px 8px;border-radius:99px;color:#3b82f6;font-weight:600">Realtime · akurat</span>
                </div>
                <div style="font-size:11px;color:var(--mut)">Tampilan persis seperti di app user</div>
            </div>

            <!-- Preview canvas: mirrors the exact .hero structure from user page -->
            <div class="hbe-preview-stage">
                <div id="prevCanvas" style="width:100%;position:relative;overflow:hidden;padding:28px 18px 0;">

                    <!-- Inactive overlay -->
                    <div id="prevInactiveOv" style="<?= $b['is_active'] ? 'display:none' : '' ?>">
                        <span class="hbe-inactive-badge"><i class="ph ph-eye-slash"></i>Banner Nonaktif — tidak tampil di app</span>
                    </div>

                    <!-- .hero-ov overlay -->
                    <div class="hero-ov" id="prevHeroOv" style="background:rgba(0,0,0,0)"></div>

                    <!-- hd-strip: exact same structure as user page -->
                    <div class="hd-strip" id="prevStrip">

                        <!-- Gambar Kiri (.hd-side) -->
                        <div class="hd-side" id="prevLeft"
                            style="width:<?= (int)($b['img_left_width'] ?? 90) ?>px;height:<?= (int)($b['height'] ?? 160) ?>px">
                            <?php if (!empty($b['img_left']) && $b['type'] === 'layout'): ?>
                                <img src="<?= htmlspecialchars($b['img_left']) ?>"
                                    style="width:100%;max-height:<?= (int)$b['height'] ?>px;object-fit:contain" />
                            <?php endif; ?>
                        </div>

                        <!-- Tengah (.hd-center) -->
                        <div class="hd-center" id="prevCenter" style="min-height:<?= (int)($b['height'] ?? 160) ?>px">
                            <?php if (($b['center_type'] ?? 'text') === 'image' && !empty($b['center_image'])): ?>
                                <img src="<?= htmlspecialchars($b['center_image']) ?>"
                                    class="hd-center-img"
                                    style="width:<?= (int)($b['center_image_width'] ?? 160) ?>px;max-height:<?= (int)($b['height'] ?? 160) - 20 ?>px" />
                            <?php else: ?>
                                <?php if (!empty($b['title'])): ?>
                                    <div class="hd-title" style="color:<?= htmlspecialchars($b['title_color'] ?? '#fff') ?>"><?= htmlspecialchars($b['title']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($b['subtitle'])): ?>
                                    <div class="hd-sub" style="color:<?= htmlspecialchars($b['subtitle_color'] ?? '#ffffffd9') ?>"><?= htmlspecialchars($b['subtitle']) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($b['btn_text'])): ?>
                                <a href="<?= htmlspecialchars($b['btn_href'] ?? '#') ?>"
                                    class="hd-btn"
                                    style="background:<?= htmlspecialchars($b['btn_color'] ?? '#FFD700') ?>;color:<?= htmlspecialchars($b['btn_text_color'] ?? '#000') ?>">
                                    <?= htmlspecialchars($b['btn_text']) ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Gambar Kanan (.hd-side) -->
                        <div class="hd-side" id="prevRight"
                            style="width:<?= (int)($b['img_right_width'] ?? 90) ?>px;height:<?= (int)($b['height'] ?? 160) ?>px">
                            <?php if (!empty($b['img_right']) && $b['type'] === 'layout'): ?>
                                <img src="<?= htmlspecialchars($b['img_right']) ?>"
                                    style="width:100%;max-height:<?= (int)$b['height'] ?>px;object-fit:contain" />
                            <?php endif; ?>
                        </div>

                    </div><!-- /hd-strip -->
                </div><!-- /prevCanvas -->
            </div>

            <!-- Ruler / meta bar -->
            <div class="hbe-prev-ruler">
                <span>Tinggi: <strong id="rulerH"><?= (int)$b['height'] ?>px</strong></span>
                <span>Tipe: <strong id="rulerType"><?= htmlspecialchars($b['type']) ?></strong></span>
                <span id="rulerStatus" style="<?= $b['is_active'] ? 'color:#10b981' : 'color:#ef4444' ?>">
                    <i class="ph ph-circle-fill" style="font-size:6px;margin-right:3px"></i><?= $b['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </div>
        </div><!-- /hbe-preview -->

    </div><!-- /hbe-wrap -->
</form>

<?php
$page_scripts = <<<'SCRIPT'
<script>
// ─── Tabs ────────────────────────────────────────────────────
function hbeTab(btn, id) {
  document.querySelectorAll('.hbe-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  ['tabBg','tabContent','tabImages','tabSettings'].forEach(t => {
    const el = document.getElementById(t);
    if (el) el.style.display = t === id ? '' : 'none';
  });
}

// ─── Type pills ──────────────────────────────────────────────
function hbeSetType(v) {
  document.querySelectorAll('input[name="type"]').forEach(r => r.checked = r.value === v);
  document.querySelectorAll('.hbe-type-pill').forEach(p => {
    const r = p.querySelector('input[name="type"]');
    if (r) p.classList.toggle('on', r.checked);
  });
  const isLayout = v === 'layout';
  const ss = document.getElementById('sideImgSection');
  const ns = document.getElementById('noSideImgNote');
  if (ss) ss.style.display = isLayout ? '' : 'none';
  if (ns) ns.style.display = isLayout ? 'none' : '';
  markUnsaved(); livePreview();
}

function hbeSetCenterType(v) {
  document.querySelectorAll('input[name="center_type"]').forEach(r => r.checked = r.value === v);
  document.querySelectorAll('#tabContent .hbe-type-pill').forEach(p => {
    const r = p.querySelector('input[name="center_type"]');
    if (r) p.classList.toggle('on', r.checked);
  });
  document.getElementById('contentTextFields').style.display = v === 'text'  ? '' : 'none';
  document.getElementById('contentImgFields').style.display  = v === 'image' ? '' : 'none';
  markUnsaved(); livePreview();
}

// ─── Color helpers ───────────────────────────────────────────
function syncHex(picker, hexId) {
  const el = document.getElementById(hexId);
  if (el) el.value = picker.value;
}
function syncColor(hex, picId) {
  if (/^#[0-9a-fA-F]{6}$/.test(hex.value)) {
    const el = document.getElementById(picId);
    if (el) el.value = hex.value;
  }
}
function hbeSetColor(picId, hexId, color) {
  const p = document.getElementById(picId), h = document.getElementById(hexId);
  if (p) p.value = color;
  if (h) h.value = color;
  markUnsaved(); livePreview(); updateGradBar();
}

// ─── Gradient bar ────────────────────────────────────────────
function updateGradBar() {
  const bar = document.getElementById('gradBar'); if (!bar) return;
  const s = document.getElementById('gc_start')?.value || '#005bb5';
  const e = document.getElementById('gc_end')?.value   || '#0099ff';
  const a = document.querySelector('input[name="bg_gradient_angle"]')?.value || 135;
  bar.style.background = `linear-gradient(${a}deg,${s},${e})`;
}

// ─── Image URL preview (thumbnail in editor) ─────────────────
function hbeImgPrev(input, imgId) {
  const img = document.getElementById(imgId); if (!img) return;
  img.src = input.value.trim();
  img.style.display = input.value.trim() ? 'block' : 'none';
}

// ─── Toggle switch ───────────────────────────────────────────
function hbeSwitch(inp) {
  const track = document.getElementById('isActiveTrack');
  if (!track) return;
  const dot = track.querySelector('.hbe-sw-dot');
  track.style.background = inp.checked ? '#3b82f6' : 'rgba(255,255,255,.12)';
  if (dot) dot.style.left = inp.checked ? '19px' : '3px';
}

// ─── Unsaved state ───────────────────────────────────────────
let _dirty = false;
function markUnsaved() {
  _dirty = true;
  document.getElementById('unsavedDot')?.classList.add('show');
  const txt = document.getElementById('saveTxt');
  const ico = document.getElementById('saveIco');
  if (txt) txt.textContent = 'Belum tersimpan';
  if (ico) { ico.className = 'ph ph-warning'; ico.style.color = '#f59e0b'; }
}
document.getElementById('hbeForm').addEventListener('input', markUnsaved);
document.getElementById('hbeForm').addEventListener('submit', () => { _dirty = false; });
window.addEventListener('beforeunload', e => {
  if (_dirty) { e.preventDefault(); e.returnValue = ''; }
});

// ─── Helpers ─────────────────────────────────────────────────
const ANIM_MAP = {
  'float':       'animation:hbe-float 3s ease-in-out infinite',
  'bounce':      'animation:hbe-bounce 1.2s ease-in-out infinite',
  'slide-left':  'animation:hbe-sliL .5s ease-out both',
  'slide-right': 'animation:hbe-sliR .5s ease-out both',
  'pulse':       'animation:hbe-pulse 1.5s ease-in-out infinite',
  'zoom-in':     'animation:hbe-zoom .4s ease-out both',
};
function animCSS(a) { return ANIM_MAP[a] || ''; }
function escH(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function gv(name) {
  const el = document.querySelector(`[name="${name}"]`);
  if (!el) return '';
  if (el.type === 'checkbox') return el.checked;
  return el.value ?? '';
}

// ─── LIVE PREVIEW ─────────────────────────────────────────────
// Mirrors exactly how the user page renders:
//   .hero { background: ... ; padding: 28px 18px 0 }
//   .hd-strip { display:flex; align-items:flex-end; ... padding-bottom:44px }
//   .hd-side  { display:flex; align-items:flex-end; flex-shrink:0 }
//   .hd-center { flex:1; flex-direction:column; align-items:center; justify-content:flex-end }
//   .hd-title / .hd-sub / .hd-btn / .hd-center-img  (exact class names)
function livePreview() {
  const type     = gv('type')   || 'layout';
  const bgImg    = gv('bg_image');
  const bgS      = gv('bg_color_start') || '#005bb5';
  const bgE      = gv('bg_color_end')   || '#0099ff';
  const bgA      = gv('bg_gradient_angle') || 135;
  const h        = Math.max(60, parseInt(gv('height')) || 160);
  const imgL     = gv('img_left'),  imgLW = parseInt(gv('img_left_width'))||90,  imgLA = gv('img_left_anim');
  const imgR     = gv('img_right'), imgRW = parseInt(gv('img_right_width'))||90, imgRA = gv('img_right_anim');
  const cType    = gv('center_type') || 'text';
  const title    = gv('title'),    titleClr = gv('title_color')    || '#fff';
  const sub      = gv('subtitle'), subClr   = gv('subtitle_color') || '#ffffffd9';
  const ci       = gv('center_image'), ciW = parseInt(gv('center_image_width'))||160, ciA = gv('center_image_anim');
  const btnTxt   = gv('btn_text'), btnBg = gv('btn_color')||'#FFD700',
        btnClr   = gv('btn_text_color')||'#000', btnHref = gv('btn_href')||'#', btnA = gv('btn_anim');
  const isActive = gv('is_active');

  // ── 1. Canvas background (= .hero background) ──
  const canvas = document.getElementById('prevCanvas');
  if (canvas) {
    canvas.style.background = bgImg
      ? `url('${bgImg}') center/cover no-repeat`
      : `linear-gradient(${bgA}deg,${bgS},${bgE})`;
  }

  // ── 2. Inactive overlay ──
  const ov = document.getElementById('prevInactiveOv');
  if (ov) ov.style.display = isActive ? 'none' : '';

  // ── 3. Ruler info ──
  const rH = document.getElementById('rulerH');
  const rT = document.getElementById('rulerType');
  const rS = document.getElementById('rulerStatus');
  if (rH) rH.textContent = h + 'px';
  if (rT) rT.textContent = type;
  if (rS) {
    rS.style.color = isActive ? '#10b981' : '#ef4444';
    rS.innerHTML = `<i class="ph ph-circle-fill" style="font-size:6px;margin-right:3px"></i>${isActive ? 'Aktif' : 'Nonaktif'}`;
  }

  // ── 4. Left image (.hd-side) ──
  const pL = document.getElementById('prevLeft');
  if (pL) {
    if (imgL && type === 'layout') {
      pL.style.width  = imgLW + 'px';
      pL.style.height = h + 'px';
      pL.innerHTML = `<img src="${imgL}"
        style="width:100%;max-height:${h}px;object-fit:contain;${animCSS(imgLA)}" />`;
    } else {
      pL.style.width = '0'; pL.style.height = h + 'px';
      pL.innerHTML = '';
    }
  }

  // ── 5. Right image (.hd-side) ──
  const pR = document.getElementById('prevRight');
  if (pR) {
    if (imgR && type === 'layout') {
      pR.style.width  = imgRW + 'px';
      pR.style.height = h + 'px';
      pR.innerHTML = `<img src="${imgR}"
        style="width:100%;max-height:${h}px;object-fit:contain;${animCSS(imgRA)}" />`;
    } else {
      pR.style.width = '0'; pR.style.height = h + 'px';
      pR.innerHTML = '';
    }
  }

  // ── 6. Center (.hd-center) ──
  const pC = document.getElementById('prevCenter');
  if (pC) {
    pC.style.minHeight = h + 'px';
    let html = '';

    if (cType === 'image' && ci) {
      // image_center mode: <img class="hd-center-img">
      html = `<img src="${ci}" class="hd-center-img"
        style="width:${ciW}px;max-height:${h - 20}px;${animCSS(ciA)}" />`;
    } else {
      // text mode: .hd-title, .hd-sub, .hd-btn  — exact class names from user CSS
      if (title) {
        html += `<div class="hd-title" style="color:${escH(titleClr)}">${escH(title)}</div>`;
      }
      if (sub) {
        html += `<div class="hd-sub" style="color:${escH(subClr)}">${escH(sub)}</div>`;
      }
    }

    // Button always rendered via .hd-btn (outside the if/else, same as user page)
    if (btnTxt) {
      html += `<a href="${escH(btnHref)}" class="hd-btn"
        style="background:${escH(btnBg)};color:${escH(btnClr)};${animCSS(btnA)}">${escH(btnTxt)}</a>`;
    }

    pC.innerHTML = html;
  }

  // ── 7. Strip padding-bottom mirrors .hd-strip { padding-bottom:44px } ──
  // Already set in CSS via .hd-strip — no override needed.
}

// ─── Init ────────────────────────────────────────────────────
window.addEventListener('load', () => {
  livePreview();
  updateGradBar();
  const curType = document.querySelector('input[name="type"]:checked')?.value || 'layout';
  const ss = document.getElementById('sideImgSection');
  const ns = document.getElementById('noSideImgNote');
  if (ss) ss.style.display = curType === 'layout' ? '' : 'none';
  if (ns) ns.style.display = curType === 'layout' ? 'none' : '';
});

// Auto-dismiss toasts
document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => t.style.opacity = '0', 3500);
  setTimeout(() => t.remove(), 4000);
});
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>