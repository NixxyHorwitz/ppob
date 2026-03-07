<?php
// backoffice/dashboard_hero_banner.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Hero Banner Editor';
$active_menu = 'hero_banner';
$toast = $toast_e = '';
$action = $_POST['action'] ?? '';

// ── Ensure exactly one row ─────────────────────────────────────
if ((int)$pdo->query("SELECT COUNT(*) FROM hero_banner")->fetchColumn() === 0) {
    $pdo->exec("INSERT INTO hero_banner
    (type,bg_color_start,bg_color_end,bg_gradient_angle,height,sort_order,is_active,btn_pt,btn_pr,btn_pb,btn_pl)
    VALUES ('layout','#005bb5','#0099ff',135,160,1,1,'7px','26px','7px','26px')");
}
$banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();

// ── Element prefixes & 12 offset suffixes ─────────────────────
const HB_ELEMENTS = ['strip_', 'img_left_', 'center_', 'title_', 'sub_', 'center_img_', 'btn_', 'img_right_'];
const HB_OFFSETS  = ['pt', 'pr', 'pb', 'pl', 'mt', 'mr', 'mb', 'ml', 'top', 'right', 'bottom', 'left'];

// ── PHP _hbStyle: mirrors user-page helper ─────────────────────
function _hbStyle(array $hb, string $pfx, array $extra = []): string
{
    $p = [];
    $hasPos = false;
    foreach (
        [
            'pt' => 'padding-top',
            'pr' => 'padding-right',
            'pb' => 'padding-bottom',
            'pl' => 'padding-left',
            'mt' => 'margin-top',
            'mr' => 'margin-right',
            'mb' => 'margin-bottom',
            'ml' => 'margin-left'
        ] as $s => $css
    ) {
        $v = trim((string)($hb[$pfx . $s] ?? ''));
        if ($v !== '') $p[] = "$css:$v";
    }
    foreach (['top', 'right', 'bottom', 'left'] as $s) {
        $v = trim((string)($hb[$pfx . $s] ?? ''));
        if ($v !== '') {
            $p[] = "$s:$v";
            $hasPos = true;
        }
    }
    if ($hasPos) $p[] = 'position:relative';
    foreach ($extra as $k => $v) if ($v !== null && $v !== '') $p[] = "$k:$v";
    return implode(';', $p);
}

// ── PHP animCSS ────────────────────────────────────────────────
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

// ── SAVE ──────────────────────────────────────────────────────
if ($action === 'save') {
    $f = $_POST;
    $so = fn($v) => preg_replace('/[^a-zA-Z0-9.\-% ]/', '', (string)$v) ?: null;

    $cols = [
        'type'                => in_array($f['type'] ?? '', ['image_only', 'layout', 'image_center']) ? $f['type'] : 'layout',
        'bg_image'            => trim($f['bg_image'] ?? '') ?: null,
        'bg_color_start'      => trim($f['bg_color_start'] ?? '#005bb5'),
        'bg_color_end'        => trim($f['bg_color_end'] ?? '#0099ff'),
        'bg_gradient_angle'   => (int)($f['bg_gradient_angle'] ?? 135),
        'height'              => max(60, min(400, (int)($f['height'] ?? 160))),
        'img_left'            => trim($f['img_left'] ?? '') ?: null,
        'img_left_width'      => max(10, (int)($f['img_left_width'] ?? 90)),
        'img_left_height'     => max(0, (int)($f['img_left_height'] ?? 0)),
        'img_left_anim'       => trim($f['img_left_anim'] ?? '') ?: null,
        'center_type'         => in_array($f['center_type'] ?? '', ['text', 'image']) ? $f['center_type'] : 'text',
        'title'               => trim($f['title'] ?? '') ?: null,
        'title_color'         => trim($f['title_color'] ?? '#ffffff'),
        'subtitle'            => trim($f['subtitle'] ?? '') ?: null,
        'subtitle_color'      => trim($f['subtitle_color'] ?? '#ffffffd9'),
        'center_image'        => trim($f['center_image'] ?? '') ?: null,
        'center_image_width'  => max(10, (int)($f['center_image_width'] ?? 160)),
        'center_image_height' => max(0, (int)($f['center_image_height'] ?? 0)),
        'center_image_anim'   => trim($f['center_image_anim'] ?? '') ?: null,
        'btn_text'            => trim($f['btn_text'] ?? '') ?: null,
        'btn_href'            => trim($f['btn_href'] ?? '#'),
        'btn_color'           => trim($f['btn_color'] ?? '#FFD700'),
        'btn_text_color'      => trim($f['btn_text_color'] ?? '#000000'),
        'btn_anim'            => trim($f['btn_anim'] ?? 'pulse'),
        'img_right'           => trim($f['img_right'] ?? '') ?: null,
        'img_right_width'     => max(10, (int)($f['img_right_width'] ?? 90)),
        'img_right_height'    => max(0, (int)($f['img_right_height'] ?? 0)),
        'img_right_anim'      => trim($f['img_right_anim'] ?? '') ?: null,
        'is_active'           => isset($f['is_active']) ? 1 : 0,
    ];
    foreach (HB_ELEMENTS as $pfx)
        foreach (HB_OFFSETS as $sfx)
            $cols[$pfx . $sfx] = $so($f[$pfx . $sfx] ?? '');

    $set = implode(',', array_map(fn($k) => "$k=?", array_keys($cols)));
    $pdo->prepare("UPDATE hero_banner SET $set WHERE id=?")
        ->execute([...array_values($cols), (int)$banner['id']]);
    $toast = 'Hero banner berhasil disimpan.';
    $banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();
}

// ── Anim select helper ────────────────────────────────────────
$ANIM = [
    '' => 'Tidak Ada',
    'float' => 'Float ↕',
    'bounce' => 'Bounce ↑',
    'slide-left' => 'Slide Kiri',
    'slide-right' => 'Slide Kanan',
    'pulse' => 'Pulse',
    'zoom-in' => 'Zoom In'
];
function anim_sel(string $name, string $cur): string
{
    global $ANIM;
    $o = "<select name=\"$name\" class=\"hbe-input\" onchange=\"livePreview();markUnsaved()\">";
    foreach ($ANIM as $v => $l) $o .= "<option value=\"$v\"" . ($cur === $v ? ' selected' : '') . ">$l</option>";
    return $o . "</select>";
}

// ── Offset group renderer ─────────────────────────────────────
function hbe_offset_group(string $pfx, array $b): void
{
    $groups = [
        'PADDING' => ['pt' => 'T', 'pr' => 'R', 'pb' => 'B', 'pl' => 'L'],
        'MARGIN'  => ['mt' => 'T', 'mr' => 'R', 'mb' => 'B', 'ml' => 'L'],
        'POSISI'  => ['top' => 'T', 'right' => 'R', 'bottom' => 'B', 'left' => 'L']
    ];
    echo '<div class="hbe-offset-wrap">';
    foreach ($groups as $label => $fields) {
        echo "<div class=\"hbe-offset-group\"><div class=\"hbe-offset-label\">$label</div><div class=\"hbe-offset-4\">";
        foreach ($fields as $sfx => $dir) {
            $col = $pfx . $sfx;
            $val = htmlspecialchars($b[$col] ?? '');
            echo "<div class=\"hbe-offset-cell\"><span class=\"hbe-offset-dir\">$dir</span>
            <input type=\"text\" name=\"$col\" id=\"f_$col\" class=\"hbe-offset-inp\"
              value=\"$val\" placeholder=\"—\" maxlength=\"10\"
              oninput=\"livePreview();markUnsaved()\" /></div>";
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
    <?php if ($toast): ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>
    <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<style>
    /* ══ Shell ═══════════════════════════════════════════════════ */
    .hbe-wrap {
        display: flex;
        height: calc(100vh - 128px);
        min-height: 600px;
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        background: var(--card);
    }

    /* ══ Left panel ══════════════════════════════════════════════ */
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
        padding: 11px 16px;
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
        margin: 11px 13px 0;
        flex-shrink: 0;
    }

    .hbe-tab {
        flex: 1;
        padding: 5px 3px;
        border-radius: 7px;
        font-size: 10px;
        font-weight: 700;
        cursor: pointer;
        color: var(--mut);
        border: none;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 3px;
        white-space: nowrap;
        transition: all .15s;
    }

    .hbe-tab.active {
        background: var(--card);
        color: var(--fg);
        box-shadow: 0 1px 5px rgba(0, 0, 0, .25);
    }

    .hbe-fields {
        flex: 1;
        overflow-y: auto;
        padding: 13px;
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
        font-size: 10px;
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
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--sub);
        display: block;
        margin-bottom: 4px;
    }

    .hbe-row {
        display: flex;
        gap: 7px;
        margin-bottom: 9px;
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
        padding: 6px 9px;
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
        width: 30px;
        height: 30px;
        border-radius: 7px;
        border: 1px solid var(--border);
        padding: 2px;
        background: var(--hover);
        cursor: pointer;
        flex-shrink: 0;
    }

    .hbe-img-prev {
        width: 100%;
        max-height: 36px;
        border-radius: 6px;
        object-fit: cover;
        margin-top: 5px;
        border: 1px solid var(--border);
        display: none;
    }

    .hbe-pills {
        display: flex;
        gap: 5px;
    }

    .hbe-pill {
        flex: 1;
        padding: 6px 3px;
        border-radius: 8px;
        font-size: 9.5px;
        font-weight: 700;
        cursor: pointer;
        border: 1.5px solid var(--border);
        background: var(--card);
        color: var(--sub);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 3px;
        transition: all .15s;
    }

    .hbe-pill.on {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, .15);
        color: #3b82f6;
    }

    .hbe-grad-bar {
        height: 24px;
        border-radius: 7px;
        border: 1px solid var(--border);
        margin-bottom: 9px;
        transition: background .1s;
    }

    .hbe-palette {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        margin-top: 5px;
    }

    .hbe-dot {
        width: 17px;
        height: 17px;
        border-radius: 4px;
        cursor: pointer;
        border: 1.5px solid rgba(255, 255, 255, .1);
        transition: transform .12s;
    }

    .hbe-dot:hover {
        transform: scale(1.35);
    }

    .hbe-tip {
        background: rgba(245, 158, 11, .07);
        border: 1px solid rgba(245, 158, 11, .18);
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 10.5px;
        color: var(--sub);
        display: flex;
        gap: 6px;
        line-height: 1.5;
        margin-top: 9px;
    }

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

    .hbe-savebar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 15px;
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

    /* ── Size row (W × H) ── */
    .hbe-size-row {
        display: flex;
        gap: 6px;
        align-items: flex-end;
    }

    .hbe-size-row .hbe-col {
        position: relative;
    }

    .hbe-size-unit {
        position: absolute;
        right: 8px;
        bottom: 7px;
        font-size: 10px;
        color: var(--mut);
        pointer-events: none;
        font-family: 'JetBrains Mono', monospace;
    }

    /* ── Offset grid ── */
    .hbe-offset-wrap {
        display: flex;
        gap: 5px;
    }

    .hbe-offset-group {
        flex: 1;
    }

    .hbe-offset-label {
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--mut);
        margin-bottom: 3px;
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
        padding: 3px 3px;
        color: var(--fg);
        font-size: 9.5px;
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
        opacity: .4;
    }

    /* ══ Right panel ═════════════════════════════════════════════ */
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
        padding: 10px 16px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }

    .hbe-prev-stage {
        flex: 1;
        overflow: auto;
        background: repeating-conic-gradient(rgba(255, 255, 255, .03) 0% 25%, transparent 0% 50%) 0 0/24px 24px;
    }

    .hbe-prev-ruler {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 6px 14px;
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

    /* ── Preview canvas (= .hero from user page) ── */
    #prevCanvas {
        position: relative;
        overflow: visible;
        padding: 28px 18px 0;
        width: 100%;
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

    #prevHeroOv {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 1;
    }

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

    #prevCenter {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        text-align: center;
        padding: 0 4px 6px;
    }

    #prevTitle {
        font-size: 16px;
        font-weight: 900;
        line-height: 1.15;
        letter-spacing: -.3px;
        text-shadow: 0 1px 6px rgba(0, 0, 0, .25);
        margin-bottom: 2px;
    }

    #prevSub {
        font-size: 10.5px;
        font-weight: 700;
        margin-bottom: 10px;
        opacity: .88;
        text-shadow: 0 1px 3px rgba(0, 0, 0, .2);
    }

    #prevCenterImg {
        max-width: 100%;
        object-fit: contain;
        margin-bottom: 10px;
    }

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

    #prevInactiveOv {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .62);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 20;
        pointer-events: none;
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

    /* ── Drag handles ── */
    .hbe-dragger {
        position: absolute;
        cursor: move;
        z-index: 30;
        pointer-events: auto;
        outline: 2px dashed rgba(59, 130, 246, 0);
        outline-offset: 2px;
        transition: outline-color .15s;
        border-radius: 3px;
    }

    .hbe-dragger.hbe-sel {
        outline-color: rgba(59, 130, 246, .9);
    }

    .hbe-dragger.hbe-sel::after {
        content: attr(data-lbl);
        position: absolute;
        top: -20px;
        left: 0;
        background: #3b82f6;
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px 4px 0 0;
        white-space: nowrap;
        pointer-events: none;
    }

    /* Corner resize handle */
    .hbe-rhandle {
        position: absolute;
        bottom: -5px;
        right: -5px;
        width: 12px;
        height: 12px;
        background: #3b82f6;
        border-radius: 3px;
        cursor: se-resize;
        z-index: 31;
        display: none;
    }

    .hbe-sel .hbe-rhandle {
        display: block;
    }

    /* ── Drag popup ── */
    #dragPopup {
        position: fixed;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 12px 14px;
        z-index: 9999;
        box-shadow: 0 8px 32px rgba(0, 0, 0, .4);
        min-width: 220px;
        font-size: 12px;
        display: none;
    }

    #dragPopup .dp-title {
        font-size: 11px;
        font-weight: 800;
        color: var(--sub);
        margin-bottom: 9px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    #dragPopup label {
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--mut);
        display: block;
        margin-bottom: 3px;
    }

    #dragPopup .dp-pills {
        display: flex;
        gap: 4px;
        margin-bottom: 2px;
    }

    #dragPopup .dp-pill {
        flex: 1;
        padding: 5px 4px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        text-align: center;
        cursor: pointer;
        border: 1.5px solid var(--border);
        background: var(--hover);
        color: var(--sub);
        transition: all .12s;
    }

    #dragPopup .dp-pill.on {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, .15);
        color: #3b82f6;
    }

    #dragPopup .dp-hint {
        font-size: 9.5px;
        color: var(--mut);
        margin-top: 6px;
        line-height: 1.5;
    }

    /* Anim keyframes */
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
        <i class="ph ph-hand" style="color:#3b82f6"></i>
        Drag &amp; resize langsung di preview · 8 elemen · 96 kolom offset
    </div>
</div>

<form method="POST" id="hbeForm">
    <input type="hidden" name="action" value="save" />

    <div class="hbe-wrap">

        <!-- ════════════════ LEFT PANEL ═════════════════════════════ -->
        <div class="hbe-panel">

            <div class="hbe-topbar">
                <div style="display:flex;align-items:center;gap:7px">
                    <i class="ph ph-sliders-horizontal" style="color:#3b82f6;font-size:15px"></i>
                    <span style="font-size:13px;font-weight:700">Editor</span>
                </div>
                <span class="hbe-unsaved" id="unsavedDot"></span>
            </div>

            <div class="hbe-tabs">
                <button type="button" class="hbe-tab active" onclick="hbeTab(this,'tabBg')"><i class="ph ph-paint-bucket"></i>BG</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabContent')"><i class="ph ph-text-aa"></i>Konten</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabImages')"><i class="ph ph-images"></i>Gambar</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabOffsets')"><i class="ph ph-arrows-out"></i>Offset</button>
                <button type="button" class="hbe-tab" onclick="hbeTab(this,'tabSettings')"><i class="ph ph-gear-six"></i></button>
            </div>

            <!-- ─────────── TAB: BG ─────────── -->
            <div class="hbe-fields" id="tabBg">
                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-layout" style="color:#3b82f6"></i>Tipe Layout</div>
                    <div class="hbe-pills">
                        <?php foreach (['layout' => ['ph-layout', 'Kiri·Tengah·Kanan'], 'image_center' => ['ph-frame-corners', 'Gambar Tengah'], 'image_only' => ['ph-image', 'Full BG']] as $v => [$ic, $lb]): ?>
                            <div class="hbe-pill <?= $b['type'] === $v ? 'on' : '' ?>" onclick="hbeSetType('<?= $v ?>')">
                                <input type="radio" name="type" value="<?= $v ?>" <?= $b['type'] === $v ? 'checked' : '' ?> style="display:none" />
                                <i class="ph <?= $ic ?>"></i><span style="font-size:8.5px"><?= $lb ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-image" style="color:#a855f7"></i>Gambar BG <span style="font-weight:400;text-transform:none;font-size:10px;color:var(--mut)">– override gradient</span></div>
                    <input type="text" name="bg_image" class="hbe-input" placeholder="https://…/bg.jpg"
                        value="<?= htmlspecialchars($b['bg_image'] ?? '') ?>"
                        oninput="livePreview();hbeThumb(this,'bgThumb')" />
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
                                <input type="color" name="bg_color_start" class="hbe-swatch-inp" id="gc_s"
                                    value="<?= htmlspecialchars($b['bg_color_start'] ?? '#005bb5') ?>"
                                    oninput="syncHex(this,'gcSh');livePreview();updateGradBar()" />
                                <input type="text" id="gcSh" class="hbe-input" maxlength="7"
                                    style="font-family:'JetBrains Mono',monospace;font-size:11px"
                                    value="<?= htmlspecialchars($b['bg_color_start'] ?? '#005bb5') ?>"
                                    oninput="syncPic(this,'gc_s');livePreview();updateGradBar()" />
                            </div>
                            <div class="hbe-palette">
                                <?php foreach (['#005bb5', '#0f172a', '#7c3aed', '#065f46', '#9a3412', '#1e1b4b', '#0c4a6e', '#7f1d1d'] as $c): ?>
                                    <div class="hbe-dot" style="background:<?= $c ?>" onclick="setClr('gc_s','gcSh','<?= $c ?>')"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="hbe-col">
                            <label class="hbe-label">Warna Akhir</label>
                            <div class="hbe-color-row">
                                <input type="color" name="bg_color_end" class="hbe-swatch-inp" id="gc_e"
                                    value="<?= htmlspecialchars($b['bg_color_end'] ?? '#0099ff') ?>"
                                    oninput="syncHex(this,'gcEh');livePreview();updateGradBar()" />
                                <input type="text" id="gcEh" class="hbe-input" maxlength="7"
                                    style="font-family:'JetBrains Mono',monospace;font-size:11px"
                                    value="<?= htmlspecialchars($b['bg_color_end'] ?? '#0099ff') ?>"
                                    oninput="syncPic(this,'gc_e');livePreview();updateGradBar()" />
                            </div>
                            <div class="hbe-palette">
                                <?php foreach (['#0099ff', '#38bdf8', '#a78bfa', '#34d399', '#fb923c', '#f472b6', '#facc15', '#4ade80'] as $c): ?>
                                    <div class="hbe-dot" style="background:<?= $c ?>" onclick="setClr('gc_e','gcEh','<?= $c ?>')"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <label class="hbe-label" style="margin-top:8px">Sudut</label>
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="range" name="bg_gradient_angle" min="0" max="360"
                            value="<?= (int)($b['bg_gradient_angle'] ?? 135) ?>" style="flex:1;accent-color:#3b82f6"
                            oninput="document.getElementById('gaVal').textContent=this.value+'°';livePreview();updateGradBar()" />
                        <span id="gaVal" style="font-size:11px;font-weight:700;color:var(--sub);min-width:36px;text-align:right;font-family:'JetBrains Mono',monospace"><?= (int)$b['bg_gradient_angle'] ?>°</span>
                    </div>
                </div>
                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-arrows-vertical" style="color:#10b981"></i>Tinggi Banner</div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="range" name="height" min="60" max="280"
                            value="<?= (int)($b['height'] ?? 160) ?>" style="flex:1;accent-color:#10b981"
                            oninput="document.getElementById('hVal').textContent=this.value+'px';livePreview()" />
                        <span id="hVal" style="font-size:11px;font-weight:700;color:var(--sub);min-width:40px;text-align:right;font-family:'JetBrains Mono',monospace"><?= (int)$b['height'] ?>px</span>
                    </div>
                </div>
            </div><!-- /tabBg -->

            <!-- ─────────── TAB: Konten ─────────── -->
            <div class="hbe-fields" id="tabContent" style="display:none">
                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-align-center-horizontal" style="color:#3b82f6"></i>Isi Tengah</div>
                    <div class="hbe-pills">
                        <?php foreach (['text' => ['ph-text-aa', 'Teks & Tombol'], 'image' => ['ph-image-square', 'Gambar']] as $v => [$ic, $lb]): ?>
                            <div class="hbe-pill <?= ($b['center_type'] ?? 'text') === $v ? 'on' : '' ?>" onclick="hbeSetCT('<?= $v ?>')">
                                <input type="radio" name="center_type" value="<?= $v ?>" <?= ($b['center_type'] ?? 'text') === $v ? 'checked' : '' ?> style="display:none" />
                                <i class="ph <?= $ic ?>"></i><?= $lb ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div id="ctText" style="<?= ($b['center_type'] ?? 'text') === 'image' ? 'display:none' : '' ?>">
                    <div class="hbe-sec">
                        <div class="hbe-sec-title"><i class="ph ph-text-h" style="color:#f59e0b"></i>Teks</div>
                        <div style="display:flex;gap:6px;align-items:flex-end;margin-bottom:9px">
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
                            <div class="hbe-col"><label class="hbe-label">BG</label>
                                <input type="color" name="btn_color" class="hbe-swatch-inp" style="width:100%;height:30px"
                                    value="<?= htmlspecialchars($b['btn_color'] ?? '#FFD700') ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col"><label class="hbe-label">Teks</label>
                                <input type="color" name="btn_text_color" class="hbe-swatch-inp" style="width:100%;height:30px"
                                    value="<?= htmlspecialchars($b['btn_text_color'] ?? '#000000') ?>" oninput="livePreview()" />
                            </div>
                            <div class="hbe-col"><label class="hbe-label">Animasi</label>
                                <?= anim_sel('btn_anim', $b['btn_anim'] ?? 'pulse') ?></div>
                        </div>
                    </div>
                </div>
                <div id="ctImg" style="<?= ($b['center_type'] ?? 'text') !== 'image' ? 'display:none' : '' ?>">
                    <div class="hbe-sec">
                        <div class="hbe-sec-title"><i class="ph ph-image-square" style="color:#a855f7"></i>Gambar Tengah</div>
                        <label class="hbe-label">URL</label>
                        <input type="text" name="center_image" class="hbe-input" placeholder="https://…/img.png"
                            value="<?= htmlspecialchars($b['center_image'] ?? '') ?>"
                            oninput="livePreview();hbeThumb(this,'ciThumb')" />
                        <img id="ciThumb" class="hbe-img-prev" src="<?= htmlspecialchars($b['center_image'] ?? '') ?>" alt=""
                            style="<?= !empty($b['center_image']) ? 'display:block' : '' ?>" />
                        <div class="hbe-row" style="margin-top:9px">
                            <div class="hbe-col">
                                <label class="hbe-label">Lebar (px)</label>
                                <div class="hbe-size-row">
                                    <div class="hbe-col" style="position:relative">
                                        <input type="number" name="center_image_width" id="f_center_image_width" class="hbe-input"
                                            style="padding-right:26px" min="10" max="600"
                                            value="<?= (int)($b['center_image_width'] ?? 160) ?>" oninput="livePreview();markUnsaved()" />
                                        <span class="hbe-size-unit">W</span>
                                    </div>
                                    <div class="hbe-col" style="position:relative">
                                        <input type="number" name="center_image_height" id="f_center_image_height" class="hbe-input"
                                            style="padding-right:26px" min="0" max="600" placeholder="auto"
                                            value="<?= (int)($b['center_image_height'] ?? 0) ? 0 : '' ?>" oninput="livePreview();markUnsaved()" />
                                        <span class="hbe-size-unit">H</span>
                                    </div>
                                </div>
                            </div>
                            <div class="hbe-col"><label class="hbe-label">Animasi</label>
                                <?= anim_sel('center_image_anim', $b['center_image_anim'] ?? '') ?></div>
                        </div>
                    </div>
                </div>
            </div><!-- /tabContent -->

            <!-- ─────────── TAB: Gambar ─────────── -->
            <div class="hbe-fields" id="tabImages" style="display:none">
                <div id="sideImgSec">
                    <?php
                    $imgs = [
                        ['img_left_',  'ph-align-left',  '#a855f7', 'Kiri',  'img_left',  'img_left_width',  'img_left_height',  'img_left_anim',  'ilThumb'],
                        ['img_right_', 'ph-align-right', '#f59e0b', 'Kanan', 'img_right', 'img_right_width', 'img_right_height', 'img_right_anim', 'irThumb'],
                    ];
                    foreach ($imgs as [$pfx, $ic, $clr, $lbl, $fn, $fnw, $fnh, $fna, $tid]):
                    ?>
                        <div class="hbe-sec">
                            <div class="hbe-sec-title"><i class="ph <?= $ic ?>" style="color:<?= $clr ?>"></i>Gambar <?= $lbl ?></div>
                            <label class="hbe-label">URL</label>
                            <input type="text" name="<?= $fn ?>" class="hbe-input" placeholder="https://…/<?= strtolower($lbl) ?>.png"
                                value="<?= htmlspecialchars($b[$fn] ?? '') ?>"
                                oninput="livePreview();hbeThumb(this,'<?= $tid ?>')" />
                            <img id="<?= $tid ?>" class="hbe-img-prev" src="<?= htmlspecialchars($b[$fn] ?? '') ?>" alt=""
                                style="<?= !empty($b[$fn]) ? 'display:block' : '' ?>" />
                            <div class="hbe-size-row" style="margin-top:9px">
                                <div class="hbe-col" style="position:relative">
                                    <label class="hbe-label">Lebar</label>
                                    <input type="number" name="<?= $fnw ?>" id="f_<?= $fnw ?>" class="hbe-input"
                                        style="padding-right:26px" min="10" max="400"
                                        value="<?= (int)($b[$fnw] ?? 90) ?>" oninput="livePreview();markUnsaved()" />
                                    <span class="hbe-size-unit" style="bottom:7px">W</span>
                                </div>
                                <div class="hbe-col" style="position:relative">
                                    <label class="hbe-label">Tinggi</label>
                                    <input type="number" name="<?= $fnh ?>" id="f_<?= $fnh ?>" class="hbe-input"
                                        style="padding-right:26px" min="0" max="400" placeholder="auto"
                                        value="<?= (int)($b[$fnh] ?? 0) ? 0 : '' ?>" oninput="livePreview();markUnsaved()" />
                                    <span class="hbe-size-unit" style="bottom:7px">H</span>
                                </div>
                                <div class="hbe-col"><label class="hbe-label">Animasi</label>
                                    <?= anim_sel($fna, $b[$fna] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="noSideNote" class="hbe-tip" style="display:none">
                    <i class="ph ph-info" style="color:#3b82f6;flex-shrink:0"></i>
                    Gambar kiri/kanan hanya untuk tipe <strong>Kiri·Tengah·Kanan</strong>.
                </div>
            </div><!-- /tabImages -->

            <!-- ─────────── TAB: Offset ─────────── -->
            <div class="hbe-fields" id="tabOffsets" style="display:none">
                <?php
                $offDefs = [
                    ['strip_',      'ph-frame-corners',        '#64748b', 'Strip (hd-strip)',        ''],
                    ['img_left_',   'ph-align-left',            '#a855f7', 'Gambar Kiri (hd-side)',   'sideOffEl'],
                    ['center_',     'ph-align-center-horizontal', '#3b82f6', 'Blok Tengah (hd-center)', ''],
                    ['title_',      'ph-text-h',               '#f59e0b', 'Judul (hd-title)',         ''],
                    ['sub_',        'ph-text-align-left',       '#10b981', 'Subtitle (hd-sub)',        ''],
                    ['center_img_', 'ph-image-square',          '#a855f7', 'Gambar Tengah',            ''],
                    ['btn_',        'ph-cursor-click',          '#ef4444', 'Tombol (hd-btn)',          ''],
                    ['img_right_',  'ph-align-right',           '#f59e0b', 'Gambar Kanan (hd-side)',  'sideOffEl'],
                ];
                foreach ($offDefs as [$pfx, $ic, $clr, $label, $cls]):
                ?>
                    <div class="hbe-sec <?= $cls ?>">
                        <div class="hbe-sec-title"><i class="ph <?= $ic ?>" style="color:<?= $clr ?>"></i><?= $label ?></div>
                        <?php hbe_offset_group($pfx, $b); ?>
                    </div>
                <?php endforeach; ?>
                <div class="hbe-tip">
                    <i class="ph ph-lightbulb" style="color:#f59e0b;flex-shrink:0"></i>
                    <span>Format: <code style="font-size:9.5px;font-family:'JetBrains Mono',monospace">10px / -5px / 50%</code>. Kosong = tidak override. Nilai posisi otomatis tambah <code style="font-size:9.5px">position:relative</code>.</span>
                </div>
            </div><!-- /tabOffsets -->

            <!-- ─────────── TAB: Settings ─────────── -->
            <div class="hbe-fields" id="tabSettings" style="display:none">
                <div class="hbe-sec">
                    <div class="hbe-sec-title"><i class="ph ph-toggle-right" style="color:#10b981"></i>Status</div>
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="font-size:13px;font-weight:600;margin-bottom:2px">Aktifkan Banner</div>
                            <div style="font-size:11px;color:var(--mut)">Tampilkan di halaman utama</div>
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
                        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border)">
                            <span style="font-size:11px;color:var(--mut)"><?= $k ?></span>
                            <span style="font-size:11px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--sub)"><?= htmlspecialchars($v) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div><!-- /tabSettings -->

            <div class="hbe-savebar">
                <div style="font-size:11px;color:var(--mut);display:flex;align-items:center;gap:4px">
                    <i class="ph ph-cloud-check" id="saveIco"></i>
                    <span id="saveTxt">Tersimpan</span>
                    <span class="hbe-unsaved" id="unsavedDot2"></span>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"
                    style="border-radius:8px;font-size:12px;padding:7px 20px;display:flex;align-items:center;gap:6px">
                    <i class="ph ph-floppy-disk"></i>Simpan
                </button>
            </div>

        </div><!-- /hbe-panel -->

        <!-- ════════════════ RIGHT PANEL ════════════════════════════ -->
        <div class="hbe-preview">
            <div class="hbe-prev-bar">
                <div style="display:flex;align-items:center;gap:7px">
                    <i class="ph ph-eye" style="color:#3b82f6;font-size:15px"></i>
                    <span style="font-size:13px;font-weight:700">Preview</span>
                    <span style="font-size:9.5px;background:rgba(59,130,246,.1);padding:2px 7px;border-radius:99px;color:#3b82f6;font-weight:600">Drag · Resize · Akurat</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:10px;color:var(--mut);display:flex;align-items:center;gap:4px">
                        <i class="ph ph-hand" style="color:#64748b"></i>Klik elemen untuk drag/resize
                    </span>
                </div>
            </div>

            <div class="hbe-prev-stage">
                <!-- prevCanvas = .hero dari user page -->
                <div id="prevCanvas"
                    style="background:<?= !empty($b['bg_image']) ? "url('" . htmlspecialchars($b['bg_image']) . "') center/cover no-repeat" : "linear-gradient({$b['bg_gradient_angle']}deg,{$b['bg_color_start']},{$b['bg_color_end']}" ?>)">

                    <div id="prevHeroOv"></div>
                    <div id="prevInactiveOv" style="<?= $b['is_active'] ? 'display:none' : '' ?>">
                        <span class="hbe-inactive-badge"><i class="ph ph-eye-slash"></i>Banner Nonaktif</span>
                    </div>

                    <!-- hd-strip -->
                    <div id="prevStrip" style="<?= _hbStyle($b, 'strip_', ['padding-bottom' => ($b['strip_pb'] ?? '44px')]) ?>">

                        <!-- hd-side kiri — wrapped in dragger -->
                        <div class="hbe-dragger" id="drgLeft" data-lbl="Gambar Kiri" data-pfx="img_left_" data-axis="xy"
                            style="flex-shrink:0;display:<?= ($b['type'] === 'layout' && !empty($b['img_left'])) ? 'flex' : 'none' ?>">
                            <div id="prevLeft" style="<?= _hbStyle($b, 'img_left_', ['width' => (int)($b['img_left_width'] ?? 90) . 'px', 'height' => (int)($b['height'] ?? 160) . 'px']) ?>">
                                <?php if (!empty($b['img_left']) && $b['type'] === 'layout'): ?>
                                    <img src="<?= htmlspecialchars($b['img_left']) ?>"
                                        style="width:<?= (int)$b['img_left_width'] ?>px;<?= (int)($b['img_left_height'] ?? 0) > 0 ? 'height:' . (int)$b['img_left_height'] . 'px;' : 'max-height:' . (int)$b['height'] . 'px;' ?>object-fit:contain;<?= animCSS_php($b['img_left_anim'] ?? '') ?>" />
                                <?php endif; ?>
                            </div>
                            <div class="hbe-rhandle" id="rszLeft" data-target="img_left"></div>
                        </div>

                        <!-- hd-center -->
                        <div id="prevCenter" style="<?= _hbStyle($b, 'center_', ['min-height' => (int)($b['height'] ?? 160) . 'px']) ?>">
                            <?php if (($b['center_type'] ?? 'text') === 'image' && !empty($b['center_image'])): ?>
                                <div class="hbe-dragger" id="drgCenterImg" data-lbl="Gambar Tengah" data-pfx="center_img_" data-axis="xy" style="display:inline-block">
                                    <img id="prevCenterImg" src="<?= htmlspecialchars($b['center_image']) ?>"
                                        style="width:<?= (int)($b['center_image_width'] ?? 160) ?>px;<?= (int)($b['center_image_height'] ?? 0) > 0 ? 'height:' . (int)$b['center_image_height'] . 'px;' : 'max-height:' . (int)($b['height'] ?? 160) . 'px;' ?>object-fit:contain;<?= animCSS_php($b['center_image_anim'] ?? '') ?>" />
                                    <div class="hbe-rhandle" id="rszCenterImg" data-target="center_image"></div>
                                </div>
                            <?php else: ?>
                                <?php if (!empty($b['title'])): ?>
                                    <div id="prevTitle" style="color:<?= htmlspecialchars($b['title_color'] ?? '#fff') ?>;<?= _hbStyle($b, 'title_') ?>"><?= htmlspecialchars($b['title'] ?? '') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($b['subtitle'])): ?>
                                    <div id="prevSub" style="color:<?= htmlspecialchars($b['subtitle_color'] ?? '#ffffffd9') ?>;<?= _hbStyle($b, 'sub_') ?>"><?= htmlspecialchars($b['subtitle'] ?? '') ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($b['btn_text'])): ?>
                                <div class="hbe-dragger" id="drgBtn" data-lbl="Tombol" data-pfx="btn_" data-axis="xy" style="display:inline-block">
                                    <a id="prevBtn" href="<?= htmlspecialchars($b['btn_href'] ?? '#') ?>"
                                        style="background:<?= htmlspecialchars($b['btn_color'] ?? '#FFD700') ?>;color:<?= htmlspecialchars($b['btn_text_color'] ?? '#000') ?>;<?= _hbStyle($b, 'btn_') ?>;<?= animCSS_php($b['btn_anim'] ?? '') ?>">
                                        <?= htmlspecialchars($b['btn_text']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- hd-side kanan -->
                        <div class="hbe-dragger" id="drgRight" data-lbl="Gambar Kanan" data-pfx="img_right_" data-axis="xy"
                            style="flex-shrink:0;display:<?= ($b['type'] === 'layout' && !empty($b['img_right'])) ? 'flex' : 'none' ?>">
                            <div id="prevRight" style="<?= _hbStyle($b, 'img_right_', ['width' => (int)($b['img_right_width'] ?? 90) . 'px', 'height' => (int)($b['height'] ?? 160) . 'px']) ?>">
                                <?php if (!empty($b['img_right']) && $b['type'] === 'layout'): ?>
                                    <img src="<?= htmlspecialchars($b['img_right']) ?>"
                                        style="width:<?= (int)$b['img_right_width'] ?>px;<?= (int)($b['img_right_height'] ?? 0) > 0 ? 'height:' . (int)$b['img_right_height'] . 'px;' : 'max-height:' . (int)$b['height'] . 'px;' ?>object-fit:contain;<?= animCSS_php($b['img_right_anim'] ?? '') ?>" />
                                <?php endif; ?>
                            </div>
                            <div class="hbe-rhandle" id="rszRight" data-target="img_right"></div>
                        </div>

                    </div><!-- /prevStrip -->
                </div><!-- /prevCanvas -->
            </div>

            <div class="hbe-prev-ruler">
                <span>H: <strong id="rH"><?= (int)$b['height'] ?>px</strong></span>
                <span>Tipe: <strong id="rT"><?= htmlspecialchars($b['type']) ?></strong></span>
                <span>Tengah: <strong id="rC"><?= htmlspecialchars($b['center_type'] ?? 'text') ?></strong></span>
                <span id="rS" style="<?= $b['is_active'] ? 'color:#10b981' : 'color:#ef4444' ?>;margin-left:auto">
                    <i class="ph ph-circle-fill" style="font-size:6px;margin-right:3px"></i><?= $b['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </div>
        </div><!-- /hbe-preview -->
    </div><!-- /hbe-wrap -->
</form>

<!-- ─── Drag Popup ─────────────────────────────────────────── -->
<div id="dragPopup">
    <div class="dp-title"><i class="ph ph-arrows-out-cardinal" style="color:#3b82f6"></i><span id="dpTitle">Elemen</span></div>
    <label>Mode Geser</label>
    <div class="dp-pills">
        <div class="dp-pill" id="dpMargin" onclick="setDragMode('margin')"><i class="ph ph-squares-four"></i> Margin</div>
        <div class="dp-pill" id="dpPosition" onclick="setDragMode('position')"><i class="ph ph-crosshair"></i> Posisi</div>
    </div>
    <div class="dp-hint" id="dpHint"></div>
</div>


<?php
$b_json = json_encode($b, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
$page_scripts = '';
?>
<script>
    <?php echo 'const HB=' . $b_json . ';'; ?>


    // ─── Tabs ──────────────────────────────────────────────────
    function hbeTab(btn, id) {
        document.querySelectorAll('.hbe-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        ['tabBg', 'tabContent', 'tabImages', 'tabOffsets', 'tabSettings'].forEach(t => {
            const el = document.getElementById(t);
            if (el) el.style.display = t === id ? '' : 'none';
        });
    }

    // ─── Type / center-type pills ──────────────────────────────
    function hbeSetType(v) {
        document.querySelectorAll('input[name="type"]').forEach(r => r.checked = r.value === v);
        document.querySelectorAll('.hbe-pill').forEach(p => {
            const r = p.querySelector('input[name="type"]');
            if (r) p.classList.toggle('on', r.checked);
        });
        const isL = v === 'layout';
        ['sideImgSec'].forEach(id => {
            const e = document.getElementById(id);
            if (e) e.style.display = isL ? '' : 'none';
        });
        ['noSideNote'].forEach(id => {
            const e = document.getElementById(id);
            if (e) e.style.display = isL ? 'none' : '';
        });
        document.querySelectorAll('.sideOffEl').forEach(e => e.style.display = isL ? '' : 'none');
        markUnsaved();
        livePreview();
    }

    function hbeSetCT(v) {
        document.querySelectorAll('input[name="center_type"]').forEach(r => r.checked = r.value === v);
        document.querySelectorAll('#tabContent .hbe-pill').forEach(p => {
            const r = p.querySelector('input[name="center_type"]');
            if (r) p.classList.toggle('on', r.checked);
        });
        document.getElementById('ctText').style.display = v === 'text' ? '' : 'none';
        document.getElementById('ctImg').style.display = v === 'image' ? '' : 'none';
        markUnsaved();
        livePreview();
    }

    // ─── Color helpers ─────────────────────────────────────────
    function syncHex(p, hId) {
        const e = document.getElementById(hId);
        if (e) e.value = p.value;
    }

    function syncPic(h, pId) {
        if (/^#[0-9a-fA-F]{6}$/.test(h.value)) {
            const e = document.getElementById(pId);
            if (e) e.value = h.value;
        }
    }

    function setClr(pId, hId, c) {
        const p = document.getElementById(pId),
            h = document.getElementById(hId);
        if (p) p.value = c;
        if (h) h.value = c;
        markUnsaved();
        livePreview();
        updateGradBar();
    }

    function updateGradBar() {
        const bar = document.getElementById('gradBar');
        if (!bar) return;
        const s = document.getElementById('gc_s')?.value || '#005bb5';
        const e = document.getElementById('gc_e')?.value || '#0099ff';
        const a = document.querySelector('input[name="bg_gradient_angle"]')?.value || 135;
        bar.style.background = \`linear-gradient(\${a}deg,\${s},\${e})\`;
}
function hbeThumb(inp,id){const i=document.getElementById(id);if(!i)return;i.src=inp.value.trim();i.style.display=inp.value.trim()?'block':'none';}
function hbeSwitch(inp){
  const t=document.getElementById('isActiveTrack');if(!t)return;
  const d=t.querySelector('.hbe-sw-dot');
  t.style.background=inp.checked?'#3b82f6':'rgba(255,255,255,.12)';
  if(d)d.style.left=inp.checked?'19px':'3px';
}

// ─── Unsaved ───────────────────────────────────────────────
let _dirty=false;
function markUnsaved(){
  _dirty=true;
  ['unsavedDot','unsavedDot2'].forEach(id=>document.getElementById(id)?.classList.add('show'));
  const t=document.getElementById('saveTxt'),i=document.getElementById('saveIco');
  if(t)t.textContent='Belum tersimpan';
  if(i){i.className='ph ph-warning';i.style.color='#f59e0b';}
}
document.getElementById('hbeForm').addEventListener('input',markUnsaved);
document.getElementById('hbeForm').addEventListener('submit',()=>{_dirty=false;});
window.addEventListener('beforeunload',e=>{if(_dirty){e.preventDefault();e.returnValue='';}});

// ─── Form value getter ─────────────────────────────────────
function gv(name){
  const el=document.querySelector(\`[name="\${name}"]\`);
  if(!el)return'';if(el.type==='checkbox')return el.checked;return el.value??'';
}
function sv(name,val){
  const el=document.querySelector(\`[name="\${name}"]\`);if(el)el.value=val;
  const fEl=document.getElementById('f_'+name);if(fEl)fEl.value=val;
}

// ─── JS _hbStyle mirror ────────────────────────────────────
function hbStyle(pfx,extra={}){
  const parts=[];let hasPos=false;
  const P=[['pt','padding-top'],['pr','padding-right'],['pb','padding-bottom'],['pl','padding-left'],
           ['mt','margin-top'], ['mr','margin-right'], ['mb','margin-bottom'],['ml','margin-left']];
  const POS=[['top','top'],['right','right'],['bottom','bottom'],['left','left']];
  for(const[s,css] of P){const v=gv(pfx+s);if(v)parts.push(\`\${css}:\${v}\`);}
  for(const[s,css] of POS){const v=gv(pfx+s);if(v){parts.push(\`\${css}:\${v}\`);hasPos=true;}}
  if(hasPos)parts.push('position:relative');
  for(const[k,v] of Object.entries(extra))if(v!=null&&v!=='')parts.push(\`\${k}:\${v}\`);
  return parts.join(';');
}

// ─── Anim CSS ─────────────────────────────────────────────
const ANIM={
  'float':      'animation:anim-float 3s ease-in-out infinite',
  'bounce':     'animation:anim-bounce 1.2s ease-in-out infinite',
  'slide-left': 'animation:anim-sliL .5s ease-out both',
  'slide-right':'animation:anim-sliR .5s ease-out both',
  'pulse':      'animation:anim-pulse 1.5s ease-in-out infinite',
  'zoom-in':    'animation:anim-zoom .4s ease-out both',
};
const animCSS=a=>ANIM[a]||'';
const escH=s=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

// ─── LIVE PREVIEW ──────────────────────────────────────────
function livePreview(){
  const type   =gv('type')||'layout';
  const bgImg  =gv('bg_image');
  const bgS    =gv('bg_color_start')||'#005bb5';
  const bgE    =gv('bg_color_end')||'#0099ff';
  const bgA    =gv('bg_gradient_angle')||135;
  const bh     =Math.max(60,parseInt(gv('height'))||160);
  const imgL   =gv('img_left'), ilW=parseInt(gv('img_left_width'))||90, ilH=parseInt(gv('img_left_height'))||0, ilA=gv('img_left_anim');
  const imgR   =gv('img_right'),irW=parseInt(gv('img_right_width'))||90,irH=parseInt(gv('img_right_height'))||0,irA=gv('img_right_anim');
  const cType  =gv('center_type')||'text';
  const title  =gv('title'),   titClr=gv('title_color')||'#fff';
  const sub    =gv('subtitle'),subClr=gv('subtitle_color')||'#ffffffd9';
  const ci     =gv('center_image'),ciW=parseInt(gv('center_image_width'))||160,ciH=parseInt(gv('center_image_height'))||0,ciA=gv('center_image_anim');
  const btnTxt =gv('btn_text'),btnBg=gv('btn_color')||'#FFD700',btnClr=gv('btn_text_color')||'#000',btnHref=gv('btn_href')||'#',btnA=gv('btn_anim');
  const active =gv('is_active');

  // 1. Canvas BG
  const cv=document.getElementById('prevCanvas');
  if(cv)cv.style.background=bgImg?\`url('\${bgImg}') center/cover no-repeat\`:\`linear-gradient(\${bgA}deg,\${bgS},\${bgE})\`;

  // 2. Inactive overlay
  const ov=document.getElementById('prevInactiveOv');
  if(ov)ov.style.display=active?'none':'';

  // 3. Ruler
  const rH=document.getElementById('rH'),rT=document.getElementById('rT'),
        rC=document.getElementById('rC'),rS=document.getElementById('rS');
  if(rH)rH.textContent=bh+'px';if(rT)rT.textContent=type;if(rC)rC.textContent=cType;
  if(rS){rS.style.color=active?'#10b981':'#ef4444';
    rS.innerHTML=\`<i class="ph ph-circle-fill" style="font-size:6px;margin-right:3px"></i>\${active?'Aktif':'Nonaktif'}\`;}

  // 4. Strip
  const strip=document.getElementById('prevStrip');
  if(strip)strip.style.cssText=hbStyle('strip_',{'padding-bottom':gv('strip_pb')||'44px'});

  // 5. Left image
  const drgL=document.getElementById('drgLeft');
  const pL=document.getElementById('prevLeft');
  const showL=!!(imgL&&type==='layout');
  if(drgL)drgL.style.display=showL?'flex':'none';
  if(pL&&showL){
    pL.style.cssText=hbStyle('img_left_',{width:ilW+'px',height:bh+'px'});
    pL.innerHTML=\`<img src="\${imgL}" style="width:\${ilW}px;\${ilH>0?'height:'+ilH+'px;':'max-height:'+bh+'px;'}object-fit:contain;\${animCSS(ilA)}"/>\`;
  }else if(pL){pL.innerHTML='';}

  // 6. Center
  const pC=document.getElementById('prevCenter');
  if(pC){
    pC.style.cssText=hbStyle('center_',{'min-height':bh+'px'});
    let html='';
    if(cType==='image'&&ci){
      const imgStyle=hbStyle('center_img_')+';'+animCSS(ciA);
      html=\`<div class="hbe-dragger" id="drgCenterImg" data-lbl="Gambar Tengah" data-pfx="center_img_" data-axis="xy" style="display:inline-block;pointer-events:auto">
        <img id="prevCenterImg" src="\${ci}" style="width:\${ciW}px;\${ciH>0?'height:'+ciH+'px;':'max-height:'+bh+'px;'}object-fit:contain;\${imgStyle}"/>
        <div class="hbe-rhandle" id="rszCenterImg" data-target="center_image"></div>
      </div>\`;
    }else{
      if(title)html+=\`<div id="prevTitle" style="color:\${escH(titClr)};\${hbStyle('title_')}">\${escH(title)}</div>\`;
      if(sub)  html+=\`<div id="prevSub"   style="color:\${escH(subClr)};\${hbStyle('sub_')}">\${escH(sub)}</div>\`;
    }
    if(btnTxt){
      html+=\`<div class="hbe-dragger" id="drgBtn" data-lbl="Tombol" data-pfx="btn_" data-axis="xy" style="display:inline-block;pointer-events:auto">
        <a id="prevBtn" href="\${escH(btnHref)}" style="background:\${escH(btnBg)};color:\${escH(btnClr)};\${hbStyle('btn_')};\${animCSS(btnA)}">\${escH(btnTxt)}</a>
      </div>\`;
    }
    pC.innerHTML=html;
    // Re-init dragger for dynamically injected elements
    initDraggers(pC);
  }

  // 7. Right image
  const drgR=document.getElementById('drgRight');
  const pR=document.getElementById('prevRight');
  const showR=!!(imgR&&type==='layout');
  if(drgR)drgR.style.display=showR?'flex':'none';
  if(pR&&showR){
    pR.style.cssText=hbStyle('img_right_',{width:irW+'px',height:bh+'px'});
    pR.innerHTML=\`<img src="\${imgR}" style="width:\${irW}px;\${irH>0?'height:'+irH+'px;':'max-height:'+bh+'px;'}object-fit:contain;\${animCSS(irA)}"/>\`;
  }else if(pR){pR.innerHTML='';}

  // Re-attach drag on static draggers that may have been reset
  initDraggers(document.getElementById('prevStrip'));
}

// ══════════════════════════════════════════════════════════
// ─── CANVA-STYLE DRAG & RESIZE SYSTEM ─────────────────────
// ══════════════════════════════════════════════════════════

let activeDrg=null, dragMode='margin', popupEl=null;
let _dragStartX=0,_dragStartY=0,_dragStartMT=0,_dragStartML=0,_dragStartTop=0,_dragStartLeft=0;
let _resizeTarget='',_resizeStartX=0,_resizeStartY=0,_resizeStartW=0,_resizeStartH=0;

// ─── Popup ────────────────────────────────────────────────
function showDragPopup(drg,x,y){
  const popup=document.getElementById('dragPopup');
  const lbl=drg.dataset.lbl||'Elemen';
  document.getElementById('dpTitle').textContent=lbl;
  document.getElementById('dpMargin').classList.toggle('on',dragMode==='margin');
  document.getElementById('dpPosition').classList.toggle('on',dragMode==='position');
  updateDpHint();
  popup.style.display='block';
  popup.style.left=(x+12)+'px';
  popup.style.top=(y+12)+'px';
}
function hideDragPopup(){document.getElementById('dragPopup').style.display='none';}
function setDragMode(m){
  dragMode=m;
  document.getElementById('dpMargin').classList.toggle('on',m==='margin');
  document.getElementById('dpPosition').classList.toggle('on',m==='position');
  updateDpHint();
}
function updateDpHint(){
  const h=document.getElementById('dpHint');if(!h)return;
  h.textContent=dragMode==='margin'
    ?'Menggeser dengan margin-top / margin-left. Mempengaruhi layout di sekitarnya.'
    :'Menggeser dengan top / left. Visual saja, layout tidak terganggu.';
}

// ─── Select dragger ───────────────────────────────────────
function selectDrg(drg,e){
  if(activeDrg&&activeDrg!==drg)activeDrg.classList.remove('hbe-sel');
  activeDrg=drg;
  drg.classList.add('hbe-sel');
  showDragPopup(drg,e.clientX,e.clientY);
}
document.addEventListener('click',e=>{
  if(!e.target.closest('.hbe-dragger')&&!e.target.closest('#dragPopup')){
    if(activeDrg){activeDrg.classList.remove('hbe-sel');activeDrg=null;}
    hideDragPopup();
  }
});

// ─── Init draggers ────────────────────────────────────────
function initDraggers(container){
  (container||document).querySelectorAll('.hbe-dragger').forEach(drg=>{
    if(drg._dragInited)return; drg._dragInited=true;

    drg.addEventListener('mousedown',e=>{
      if(e.target.classList.contains('hbe-rhandle'))return; // handled by resize
      e.preventDefault();e.stopPropagation();
      selectDrg(drg,e);

      const pfx=drg.dataset.pfx||'';
      _dragStartX=e.clientX; _dragStartY=e.clientY;

      if(dragMode==='margin'){
        _dragStartMT=parsePx(gv(pfx+'mt')||'0');
        _dragStartML=parsePx(gv(pfx+'ml')||'0');
      }else{
        _dragStartTop=parsePx(gv(pfx+'top')||'0');
        _dragStartLeft=parsePx(gv(pfx+'left')||'0');
      }

      function onMove(ev){
        const dx=ev.clientX-_dragStartX, dy=ev.clientY-_dragStartY;
        if(dragMode==='margin'){
          const newMT=Math.round(_dragStartMT+dy), newML=Math.round(_dragStartML+dx);
          setOffsetField(pfx+'mt',newMT+'px');
          setOffsetField(pfx+'ml',newML+'px');
        }else{
          const newTop=Math.round(_dragStartTop+dy), newLeft=Math.round(_dragStartLeft+dx);
          setOffsetField(pfx+'top',newTop+'px');
          setOffsetField(pfx+'left',newLeft+'px');
        }
        livePreview(); markUnsaved();
      }
      function onUp(){
        document.removeEventListener('mousemove',onMove);
        document.removeEventListener('mouseup',onUp);
      }
      document.addEventListener('mousemove',onMove);
      document.addEventListener('mouseup',onUp);
    });
  });

  // Resize handles
  (container||document).querySelectorAll('.hbe-rhandle').forEach(rh=>{
    if(rh._resizeInited)return; rh._resizeInited=true;
    rh.addEventListener('mousedown',e=>{
      e.preventDefault();e.stopPropagation();
      _resizeTarget=rh.dataset.target||'';
      _resizeStartX=e.clientX; _resizeStartY=e.clientY;
      _resizeStartW=parseInt(gv(_resizeTarget+'_width'))||90;
      _resizeStartH=parseInt(gv(_resizeTarget+'_height'))||0;

      function onMove(ev){
        const dx=ev.clientX-_resizeStartX, dy=ev.clientY-_resizeStartY;
        const newW=Math.max(10,Math.round(_resizeStartW+dx));
        const newH=Math.max(0,Math.round(_resizeStartH+dy));
        sv(_resizeTarget+'_width', newW);
        sv(_resizeTarget+'_height', newH>4?newH:0);
        livePreview(); markUnsaved();
      }
      function onUp(){
        document.removeEventListener('mousemove',onMove);
        document.removeEventListener('mouseup',onUp);
      }
      document.addEventListener('mousemove',onMove);
      document.addEventListener('mouseup',onUp);
    });
  });
}

// ─── Helper: parse px string to int ──────────────────────
function parsePx(s){return parseInt(String(s).replace('px',''))||0;}

// ─── Write to both offset input + form field ──────────────
function setOffsetField(name,val){
  // offset inp (in tab Offset)
  const offEl=document.getElementById('f_'+name);
  if(offEl)offEl.value=val;
  // also write via generic form field
  const formEl=document.querySelector(\`[name="\${name}"]\`);
  if(formEl&&formEl!==offEl)formEl.value=val;
}

// ─── Init ─────────────────────────────────────────────────
window.addEventListener('load',()=>{
  updateGradBar();
  livePreview();
  const t=document.querySelector('input[name="type"]:checked')?.value||'layout';
  hbeSetType(t);
  initDraggers();
  document.querySelectorAll('.toast-item').forEach(t=>{
    setTimeout(()=>t.style.opacity='0',3500);
    setTimeout(()=>t.remove(),4000);
  });
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>