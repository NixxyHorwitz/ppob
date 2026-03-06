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
        align-items: center;
        justify-content: center;
        padding: 24px;
        overflow: auto;
    }

    /* Phone mockup */
    .hbe-phone-shell {
        width: 288px;
        background: #0d0d0d;
        border-radius: 36px;
        box-shadow: 0 0 0 2px #2a2a2a, 0 0 0 7px #141414, 0 28px 72px rgba(0, 0, 0, .75);
        overflow: hidden;
        position: relative;
        flex-shrink: 0;
    }

    .hbe-notch {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 90px;
        height: 26px;
        background: #0d0d0d;
        border-radius: 0 0 18px 18px;
        z-index: 10;
    }

    .hbe-screen {
        background: #0f172a;
        min-height: 500px;
        padding-top: 28px;
        overflow: hidden;
    }

    /* App chrome */
    .hbe-app-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 12px 5px;
    }

    .hbe-app-brand {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 800;
        color: #fff;
    }

    .hbe-app-brand-dot {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        background: rgba(255, 255, 255, .14);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hbe-app-icons {
        display: flex;
        gap: 6px;
    }

    .hbe-app-icon {
        width: 24px;
        height: 24px;
        border-radius: 7px;
        background: rgba(255, 255, 255, .1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: rgba(255, 255, 255, .7);
    }

    /* Banner strip */
    .hbe-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 10px;
        overflow: hidden;
        position: relative;
        transition: height .2s;
    }

    .hbe-strip-center {
        flex: 1;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
    }

    .hbe-strip-btn {
        border-radius: 99px;
        font-weight: 800;
        cursor: pointer;
        border: none;
    }

    /* Below banner fake content */
    .hbe-app-body {
        padding: 10px;
    }

    .hbe-app-card {
        background: rgba(255, 255, 255, .04);
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 8px;
    }

    .hbe-app-card-lbl {
        font-size: 8px;
        color: rgba(255, 255, 255, .35);
        margin-bottom: 3px;
    }

    .hbe-app-card-val {
        font-size: 17px;
        font-weight: 800;
        color: #fff;
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 2px;
    }

    .hbe-app-menu-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }

    .hbe-app-menu-item {
        background: rgba(255, 255, 255, .04);
        border-radius: 8px;
        padding: 7px 4px;
        text-align: center;
    }

    .hbe-app-menu-ico {
        width: 22px;
        height: 22px;
        border-radius: 7px;
        margin: 0 auto 3px;
    }

    .hbe-app-menu-lbl {
        font-size: 7px;
        color: rgba(255, 255, 255, .35);
    }

    /* VP buttons */
    .hbe-vp-btns {
        display: flex;
        gap: 4px;
    }

    .hbe-vp-btn {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--border);
        background: var(--hover);
        color: var(--mut);
        transition: all .15s;
    }

    .hbe-vp-btn.on {
        background: rgba(59, 130, 246, .15);
        border-color: #3b82f6;
        color: #3b82f6;
    }

    /* Wide preview */
    .hbe-wide-wrap {
        width: 100%;
        max-width: 560px;
    }

    /* Gradient preview bar */
    .hbe-grad-bar {
        height: 28px;
        border-radius: 7px;
        border: 1px solid var(--border);
        margin-bottom: 10px;
        transition: background .15s;
    }

    /* Anim keyframes */
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
            transform: translateX(-16px);
            opacity: 0
        }

        to {
            transform: none;
            opacity: 1
        }
    }

    @keyframes hbe-sliR {
        from {
            transform: translateX(16px);
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

        45% {
            transform: translateY(-7px)
        }

        65% {
            transform: translateY(-3px)
        }
    }

    @keyframes hbe-zoom {
        from {
            transform: scale(.82);
            opacity: 0
        }

        to {
            transform: scale(1);
            opacity: 1
        }
    }

    /* Inactive overlay */
    .hbe-inactive-ov {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5;
    }

    .hbe-inactive-badge {
        background: rgba(239, 68, 68, .2);
        border: 1px solid #ef4444;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 9px;
        font-weight: 700;
        color: #ef4444;
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
                    <i class="ph ph-device-mobile" style="color:#3b82f6;font-size:15px"></i>
                    <span style="font-size:13px;font-weight:700">Live Preview</span>
                    <span style="font-size:10px;color:var(--mut);background:rgba(59,130,246,.1);padding:2px 7px;border-radius:99px;color:#3b82f6">Realtime</span>
                </div>
                <div class="hbe-vp-btns">
                    <button type="button" class="hbe-vp-btn on" onclick="hbeVp(this,'phone')"><i class="ph ph-device-mobile"></i> Phone</button>
                    <button type="button" class="hbe-vp-btn" onclick="hbeVp(this,'wide')"><i class="ph ph-monitor"></i> Wide</button>
                </div>
            </div>

            <div class="hbe-preview-stage">

                <!-- Phone mockup -->
                <div id="phoneWrap">
                    <div class="hbe-phone-shell">
                        <div class="hbe-notch"></div>
                        <div class="hbe-screen">

                            <!-- App bar inside hero bg -->
                            <div id="prevHero" style="position:relative;background:linear-gradient(<?= $b['bg_gradient_angle'] ?>deg,<?= $b['bg_color_start'] ?>,<?= $b['bg_color_end'] ?>)">

                                <?php if (!$b['is_active']): ?>
                                    <div class="hbe-inactive-ov" id="inactiveOv">
                                        <span class="hbe-inactive-badge"><i class="ph ph-eye-slash me-1"></i>Banner Nonaktif</span>
                                    </div>
                                <?php else: ?>
                                    <div class="hbe-inactive-ov" id="inactiveOv" style="display:none">
                                        <span class="hbe-inactive-badge"><i class="ph ph-eye-slash me-1"></i>Banner Nonaktif</span>
                                    </div>
                                <?php endif; ?>

                                <div class="hbe-app-bar">
                                    <div class="hbe-app-brand">
                                        <div class="hbe-app-brand-dot"><i class="ph ph-lightning" style="color:#93c5fd;font-size:9px"></i></div>
                                        <span>BersamaKita</span>
                                    </div>
                                    <div class="hbe-app-icons">
                                        <div class="hbe-app-icon"><i class="ph ph-bell" style="font-size:9px"></i></div>
                                        <div class="hbe-app-icon"><i class="ph ph-user-circle" style="font-size:10px"></i></div>
                                    </div>
                                </div>

                                <!-- Banner strip -->
                                <div class="hbe-strip" id="prevStrip" style="height:<?= (int)$b['height'] ?>px">
                                    <div id="prevLeft" style="flex-shrink:0">
                                        <?php if (!empty($b['img_left']) && $b['type'] === 'layout'): ?>
                                            <img src="<?= htmlspecialchars($b['img_left']) ?>" style="height:<?= min((int)$b['height'] - 20, 66) ?>px;max-width:<?= (int)$b['img_left_width'] ?>px;object-fit:contain" />
                                        <?php endif; ?>
                                    </div>
                                    <div class="hbe-strip-center" id="prevCenter">
                                        <?php if (($b['center_type'] ?? 'text') === 'image' && !empty($b['center_image'])): ?>
                                            <img src="<?= htmlspecialchars($b['center_image']) ?>" style="max-height:<?= min((int)$b['height'] - 20, 76) ?>px;max-width:<?= (int)$b['center_image_width'] ?>px;object-fit:contain" />
                                        <?php else: ?>
                                            <?php if (!empty($b['title'])): ?><div style="font-size:14px;font-weight:900;color:<?= htmlspecialchars($b['title_color'] ?? '#fff') ?>;text-shadow:0 1px 6px rgba(0,0,0,.4);line-height:1.1"><?= htmlspecialchars($b['title']) ?></div><?php endif; ?>
                                            <?php if (!empty($b['subtitle'])): ?><div style="font-size:10px;color:<?= htmlspecialchars($b['subtitle_color'] ?? '#ffffffcc') ?>"><?= htmlspecialchars($b['subtitle']) ?></div><?php endif; ?>
                                            <?php if (!empty($b['btn_text'])): ?><button class="hbe-strip-btn" style="background:<?= htmlspecialchars($b['btn_color'] ?? '#FFD700') ?>;color:<?= htmlspecialchars($b['btn_text_color'] ?? '#000') ?>;font-size:9px;padding:3px 12px;margin-top:3px"><?= htmlspecialchars($b['btn_text']) ?></button><?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div id="prevRight" style="flex-shrink:0">
                                        <?php if (!empty($b['img_right']) && $b['type'] === 'layout'): ?>
                                            <img src="<?= htmlspecialchars($b['img_right']) ?>" style="height:<?= min((int)$b['height'] - 20, 66) ?>px;max-width:<?= (int)$b['img_right_width'] ?>px;object-fit:contain" />
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Fake app body -->
                            <div class="hbe-app-body">
                                <div class="hbe-app-card">
                                    <div class="hbe-app-card-lbl">Saldo Utama</div>
                                    <div class="hbe-app-card-val">Rp ••••••</div>
                                </div>
                                <div class="hbe-app-menu-grid">
                                    <?php $menuColors = ['rgba(99,179,237,.2)', 'rgba(167,139,250,.2)', 'rgba(52,211,153,.2)', 'rgba(251,146,60,.2)', 'rgba(244,114,182,.2)', 'rgba(250,204,21,.2)', 'rgba(96,165,250,.2)', 'rgba(74,222,128,.2)'];
                                    foreach (['Pulsa', 'Data', 'PLN', 'BPJS', 'Topup', 'Games', 'Transfer', 'Lainnya'] as $i => $item): ?>
                                        <div class="hbe-app-menu-item">
                                            <div class="hbe-app-menu-ico" style="background:<?= $menuColors[$i % count($menuColors)] ?>"></div>
                                            <div class="hbe-app-menu-lbl"><?= $item ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div><!-- /phoneWrap -->

                <!-- Wide view -->
                <div id="wideWrap" class="hbe-wide-wrap" style="display:none">
                    <div style="border-radius:12px;overflow:hidden;border:1px solid var(--border);box-shadow:0 4px 24px rgba(0,0,0,.3)">
                        <div id="prevStripWide"
                            class="hbe-strip"
                            style="height:<?= (int)$b['height'] ?>px;background:linear-gradient(<?= $b['bg_gradient_angle'] ?>deg,<?= $b['bg_color_start'] ?>,<?= $b['bg_color_end'] ?>)">
                            <div id="prevLeftW" style="flex-shrink:0"></div>
                            <div class="hbe-strip-center" id="prevCenterW"></div>
                            <div id="prevRightW" style="flex-shrink:0"></div>
                        </div>
                    </div>
                    <div style="font-size:10px;color:var(--mut);text-align:center;margin-top:8px">Wide · tablet / desktop view</div>
                </div>

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

// ─── Image URL preview ───────────────────────────────────────
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

// ─── Viewport toggle ─────────────────────────────────────────
function hbeVp(btn, mode) {
  document.querySelectorAll('.hbe-vp-btn').forEach(b => b.classList.remove('on'));
  btn.classList.add('on');
  document.getElementById('phoneWrap').style.display = mode === 'phone' ? '' : 'none';
  document.getElementById('wideWrap').style.display  = mode === 'wide'  ? '' : 'none';
}

// ─── Unsaved state ───────────────────────────────────────────
let _dirty = false;
function markUnsaved() {
  _dirty = true;
  document.getElementById('unsavedDot')?.classList.add('show');
  const txt = document.getElementById('saveTxt');
  const ico = document.getElementById('saveIco');
  if (txt) txt.textContent = 'Belum tersimpan';
  if (ico) { ico.className = ''; ico.className = 'ph ph-warning'; ico.style.color = '#f59e0b'; }
}
document.getElementById('hbeForm').addEventListener('input', markUnsaved);
document.getElementById('hbeForm').addEventListener('submit', () => {
  _dirty = false;
});
window.addEventListener('beforeunload', e => {
  if (_dirty) { e.preventDefault(); e.returnValue = ''; }
});

// ─── Animation → CSS ─────────────────────────────────────────
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
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function gv(name) {
  const el = document.querySelector(`[name="${name}"]`);
  if (!el) return '';
  if (el.type === 'checkbox') return el.checked;
  return el.value ?? '';
}

// ─── LIVE PREVIEW ────────────────────────────────────────────
function livePreview() {
  const type     = gv('type')   || 'layout';
  const bgImg    = gv('bg_image');
  const bgS      = gv('bg_color_start') || '#005bb5';
  const bgE      = gv('bg_color_end')   || '#0099ff';
  const bgA      = gv('bg_gradient_angle') || 135;
  const h        = Math.max(60, parseInt(gv('height')) || 160);
  const imgL     = gv('img_left'), imgLW = parseInt(gv('img_left_width'))||90, imgLA = gv('img_left_anim');
  const imgR     = gv('img_right'),imgRW = parseInt(gv('img_right_width'))||90, imgRA = gv('img_right_anim');
  const cType    = gv('center_type') || 'text';
  const title    = gv('title'), titleClr = gv('title_color')||'#fff';
  const sub      = gv('subtitle'),  subClr   = gv('subtitle_color')||'#ffffffcc';
  const ci       = gv('center_image'), ciW = parseInt(gv('center_image_width'))||160, ciA = gv('center_image_anim');
  const btnTxt   = gv('btn_text'), btnBg = gv('btn_color')||'#FFD700', btnClr = gv('btn_text_color')||'#000', btnA = gv('btn_anim');
  const isActive = gv('is_active');

  const bgVal = bgImg
    ? `url('${bgImg}') center/cover no-repeat`
    : `linear-gradient(${bgA}deg,${bgS},${bgE})`;

  // Hero bg
  const hero = document.getElementById('prevHero');
  if (hero) hero.style.background = bgVal;

  // Inactive overlay
  const ov = document.getElementById('inactiveOv');
  if (ov) ov.style.display = isActive ? 'none' : '';

  // Strip height
  const strip = document.getElementById('prevStrip');
  if (strip) strip.style.height = h + 'px';

  const leftH = Math.min(h - 20, 66);

  // Left image
  const pL = document.getElementById('prevLeft');
  if (pL) pL.innerHTML = (imgL && type === 'layout')
    ? `<img src="${imgL}" style="height:${leftH}px;max-width:${imgLW}px;object-fit:contain;${animCSS(imgLA)}" />`
    : '';

  // Right image
  const pR = document.getElementById('prevRight');
  if (pR) pR.innerHTML = (imgR && type === 'layout')
    ? `<img src="${imgR}" style="height:${leftH}px;max-width:${imgRW}px;object-fit:contain;${animCSS(imgRA)}" />`
    : '';

  // Center
  const pC = document.getElementById('prevCenter');
  if (pC) {
    if (cType === 'image' && ci) {
      pC.innerHTML = `<img src="${ci}" style="max-height:${Math.min(h-20,76)}px;max-width:${ciW}px;object-fit:contain;${animCSS(ciA)}" />`;
    } else {
      let html = '';
      if (title) html += `<div style="font-size:14px;font-weight:900;color:${titleClr};text-shadow:0 1px 6px rgba(0,0,0,.4);line-height:1.1">${escH(title)}</div>`;
      if (sub)   html += `<div style="font-size:10px;color:${subClr}">${escH(sub)}</div>`;
      if (btnTxt) html += `<button class="hbe-strip-btn" style="background:${btnBg};color:${btnClr};font-size:9px;padding:3px 12px;margin-top:3px;${animCSS(btnA)}">${escH(btnTxt)}</button>`;
      pC.innerHTML = html;
    }
  }

  // ─ Wide view ─
  const sw = document.getElementById('prevStripWide');
  if (sw) { sw.style.background = bgVal; sw.style.height = h + 'px'; }
  const pLW = document.getElementById('prevLeftW');
  const pRW = document.getElementById('prevRightW');
  const pCW = document.getElementById('prevCenterW');
  if (pLW) pLW.innerHTML = (imgL && type === 'layout') ? `<img src="${imgL}" style="height:${Math.min(h-20,80)}px;max-width:${imgLW}px;object-fit:contain" />` : '';
  if (pRW) pRW.innerHTML = (imgR && type === 'layout') ? `<img src="${imgR}" style="height:${Math.min(h-20,80)}px;max-width:${imgRW}px;object-fit:contain" />` : '';
  if (pCW && pC) pCW.innerHTML = pC.innerHTML;
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