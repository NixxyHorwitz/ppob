<?php
// backoffice/dashboard_hero_banner.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Hero Banner Studio';
$active_menu = 'hero_banner';
$toast = $toast_e = '';
$action = $_POST['action'] ?? '';

// ── Auto-insert default row jika tabel kosong ────────────────
if ((int)$pdo->query("SELECT COUNT(*) FROM hero_banner")->fetchColumn() === 0) {
    $pdo->exec("INSERT INTO hero_banner
        (height, sort_order, is_active,
         title_color, title_size, title_weight, title_mb,
         subtitle_color, subtitle_size, subtitle_mb,
         btn_color, btn_text_color, btn_pt, btn_pr, btn_pb, btn_pl,
         btn_radius, btn_size, btn_weight, btn_anim,
         img_left_w, img_left_x, img_left_y, img_left_z,
         img_right_w, img_right_x, img_right_y, img_right_z,
         center_y, center_z,
         center_mt, center_mb,
         btn_y, btn_w, btn_h, btn_show)
        VALUES
        (160, 1, 1,
         '#ffffff','15px','900','2px',
         '#ffffffd9','10.5px','10px',
         '#FFD700','#000000','7px','26px','7px','26px',
         '99px','12px','900','pulse',
         90,'0','0',1,
         90,'0','0',1,
         '0',2)");
}
$banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();

// ── Save ─────────────────────────────────────────────────────
function cssSan($v): ?string
{
    $v = preg_replace('/[^a-zA-Z0-9.\-% ]/', '', trim((string)$v));
    return $v === '' ? null : $v;
}

if ($action === 'save') {
    $f = $_POST;
    $so = fn($v) => cssSan($v);

    $cols = [
        'height'          => max(60, min(400, (int)($f['height']  ?? 160))),
        'sort_order'      => (int)($f['sort_order'] ?? 0),
        'is_active'       => isset($f['is_active']) ? 1 : 0,

        // Gambar Kiri
        'img_left'        => trim($f['img_left']       ?? '') ?: null,
        'img_left_w'      => max(10, (int)($f['img_left_w'] ?? 90)),
        'img_left_h'      => ($h = (int)($f['img_left_h'] ?? 0)) > 0 ? $h : null,
        'img_left_x'      => $so($f['img_left_x'] ?? '0') ?? '0',
        'img_left_y'      => $so($f['img_left_y'] ?? '0') ?? '0',
        'img_left_z'      => (int)($f['img_left_z']    ?? 1),
        'img_left_anim'   => trim($f['img_left_anim']  ?? '') ?: null,

        // Gambar Kanan
        'img_right'       => trim($f['img_right']      ?? '') ?: null,
        'img_right_w'     => max(10, (int)($f['img_right_w'] ?? 90)),
        'img_right_h'     => ($h = (int)($f['img_right_h'] ?? 0)) > 0 ? $h : null,
        'img_right_x'     => $so($f['img_right_x'] ?? '0') ?? '0',
        'img_right_y'     => $so($f['img_right_y'] ?? '0') ?? '0',
        'img_right_z'     => (int)($f['img_right_z']   ?? 1),
        'img_right_anim'  => trim($f['img_right_anim'] ?? '') ?: null,

        // Center
        'center_y'        => $so($f['center_y'] ?? '0') ?? '0',
        'center_w'        => ($cw = (int)($f['center_w'] ?? 0)) > 0 ? $cw : null,
        'center_z'        => (int)($f['center_z'] ?? 2),
        'center_mt'       => $so($f['center_mt'] ?? '') ?: null,
        'center_mb'       => $so($f['center_mb'] ?? '') ?: null,
        'btn_y'           => $so($f['btn_y']  ?? '') ?: null,
        'btn_w'           => $so($f['btn_w']  ?? '') ?: null,
        'btn_h'           => $so($f['btn_h']  ?? '') ?: null,
        'btn_show'        => isset($f['btn_show']) ? 1 : 0,

        // Title
        'title'           => trim($f['title']          ?? '') ?: null,
        'title_color'     => trim($f['title_color']    ?? '#ffffff'),
        'title_size'      => $so($f['title_size']      ?? '15px')   ?? '15px',
        'title_weight'    => $so($f['title_weight']    ?? '900')    ?? '900',
        'title_mb'        => $so($f['title_mb']        ?? '2px')    ?? '2px',

        // Subtitle
        'subtitle'        => trim($f['subtitle']       ?? '') ?: null,
        'subtitle_color'  => trim($f['subtitle_color'] ?? '#ffffffd9'),
        'subtitle_size'   => $so($f['subtitle_size']   ?? '10.5px') ?? '10.5px',
        'subtitle_mb'     => $so($f['subtitle_mb']     ?? '10px')   ?? '10px',

        // Center image
        'center_type'     => in_array($f['center_type'] ?? '', ['text', 'image']) ? $f['center_type'] : 'text',
        'center_image'    => trim($f['center_image']   ?? '') ?: null,
        'center_img_w'    => max(10, (int)($f['center_img_w'] ?? 160)),
        'center_img_h'    => ($h = (int)($f['center_img_h'] ?? 0)) > 0 ? $h : null,
        'center_img_mb'   => $so($f['center_img_mb']  ?? '0') ?? '0',
        'center_img_anim' => trim($f['center_img_anim'] ?? '') ?: null,

        // Tombol
        'btn_text'        => trim($f['btn_text']       ?? '') ?: null,
        'btn_href'        => trim($f['btn_href']       ?? '#'),
        'btn_color'       => trim($f['btn_color']      ?? '#FFD700'),
        'btn_text_color'  => trim($f['btn_text_color'] ?? '#000000'),
        'btn_pt'          => $so($f['btn_pt'] ?? '7px')   ?? '7px',
        'btn_pb'          => $so($f['btn_pb'] ?? '7px')   ?? '7px',
        'btn_pl'          => $so($f['btn_pl'] ?? '26px')  ?? '26px',
        'btn_pr'          => $so($f['btn_pr'] ?? '26px')  ?? '26px',
        'btn_radius'      => $so($f['btn_radius'] ?? '99px') ?? '99px',
        'btn_size'        => $so($f['btn_size']   ?? '12px') ?? '12px',
        'btn_weight'      => $so($f['btn_weight'] ?? '900')  ?? '900',
        'btn_anim'        => trim($f['btn_anim']       ?? 'pulse'),
    ];

    $set = implode(',', array_map(fn($k) => "$k=?", array_keys($cols)));
    $pdo->prepare("UPDATE hero_banner SET $set WHERE id=?")
        ->execute([...array_values($cols), (int)$banner['id']]);

    $toast  = 'Tersimpan!';
    $banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();
}

// ── Helpers ──────────────────────────────────────────────────
$ANIM = [
    ''           => 'Tidak Ada',
    'float'      => 'Float',
    'bounce'     => 'Bounce',
    'slide-left' => 'Slide Kiri',
    'slide-right' => 'Slide Kanan',
    'pulse'      => 'Pulse',
    'zoom-in'    => 'Zoom In',
];

function anim_sel(string $name, string $cur, string $cls = 'st-select'): string
{
    global $ANIM;
    $o = "<select name=\"$name\" class=\"$cls\" onchange=\"livePreview();markDirty()\">";
    foreach ($ANIM as $v => $l) {
        $o .= "<option value=\"$v\"" . ($cur === $v ? ' selected' : '') . ">$l</option>";
    }
    return $o . "</select>";
}

function animCSS_php(string $a): string
{
    return match ($a) {
        'float'       => 'animation:hb-float 3s ease-in-out infinite',
        'bounce'      => 'animation:hb-bounce 1.2s ease-in-out infinite',
        'slide-left'  => 'animation:hb-sliL .5s ease-out both',
        'slide-right' => 'animation:hb-sliR .5s ease-out both',
        'pulse'       => 'animation:hb-pulse 1.5s ease-in-out infinite',
        'zoom-in'     => 'animation:hb-zoom .4s ease-out both',
        default       => '',
    };
}

$b = $banner;

// Safe JSON for JS
$js_keys = [
    'height',
    'is_active',
    'sort_order',
    'img_left',
    'img_left_w',
    'img_left_h',
    'img_left_x',
    'img_left_y',
    'img_left_z',
    'img_left_anim',
    'img_right',
    'img_right_w',
    'img_right_h',
    'img_right_x',
    'img_right_y',
    'img_right_z',
    'img_right_anim',
    'center_y',
    'center_w',
    'center_z',
    'center_mt',
    'center_mb',
    'center_type',
    'title',
    'title_color',
    'title_size',
    'title_weight',
    'title_mb',
    'subtitle',
    'subtitle_color',
    'subtitle_size',
    'subtitle_mb',
    'center_image',
    'center_img_w',
    'center_img_h',
    'center_img_mb',
    'center_img_anim',
    'btn_text',
    'btn_href',
    'btn_color',
    'btn_text_color',
    'btn_show',
    'btn_pt',
    'btn_pb',
    'btn_pl',
    'btn_pr',
    'btn_radius',
    'btn_size',
    'btn_weight',
    'btn_anim',
    'btn_y',
    'btn_w',
    'btn_h',
];
$js_data = [];
foreach ($js_keys as $k) $js_data[$k] = $b[$k] ?? null;
$b_json = json_encode($js_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

require_once __DIR__ . '/includes/header.php';
?>
<script>
    const HB = <?php echo $b_json; ?>;
</script>

<!-- Toast -->
<div class="st-toast-wrap">
    <?php if ($toast):  ?><div class="st-toast st-toast-ok"><i class="ph ph-check-circle"></i><?= htmlspecialchars($toast)  ?></div><?php endif; ?>
    <?php if ($toast_e): ?><div class="st-toast st-toast-err"><i class="ph ph-warning-circle"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<style>
    /* ════════════════════════════════════════════════════
   HERO BANNER STUDIO — Dark editor + bright canvas
════════════════════════════════════════════════════ */
    .st-root {
        display: flex;
        height: calc(100vh - 56px);
        min-height: 600px;
        overflow: hidden;
        background: #0d0d14;
    }

    /* ── Tool strip (far left) ── */
    .st-strip {
        width: 48px;
        flex-shrink: 0;
        background: #111118;
        border-right: 1px solid #1e1e2c;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 0;
        gap: 3px;
    }

    .st-tb {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #44445a;
        font-size: 17px;
        border: none;
        background: transparent;
        transition: all .14s;
        position: relative;
    }

    .st-tb:hover {
        background: #1a1a28;
        color: #8080a0;
    }

    .st-tb.on {
        background: #1e3a8a;
        color: #93c5fd;
    }

    .st-tb-sep {
        width: 20px;
        height: 1px;
        background: #1e1e2c;
        margin: 3px 0;
    }

    /* ── Inspector (left panel) ── */
    .st-insp {
        width: 264px;
        flex-shrink: 0;
        background: #111118;
        border-right: 1px solid #1e1e2c;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .st-savebar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 12px;
        background: #0d0d14;
        border-bottom: 1px solid #1e1e2c;
        flex-shrink: 0;
    }

    .st-dirty {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #f59e0b;
        display: none;
        flex-shrink: 0;
    }

    .st-dirty.on {
        display: block;
    }

    .st-save-txt {
        font-size: 10px;
        color: #44445a;
        flex: 1;
    }

    .st-save-btn {
        display: flex;
        align-items: center;
        gap: 5px;
        background: #1e40af;
        color: #bfdbfe;
        border: none;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        transition: all .14s;
        font-family: inherit;
    }

    .st-save-btn:hover {
        background: #2563eb;
        color: #fff;
        box-shadow: 0 4px 14px rgba(37, 99, 235, .4);
    }

    /* Nav tabs */
    .st-tabs {
        display: flex;
        background: #0d0d14;
        border-bottom: 1px solid #1e1e2c;
        flex-shrink: 0;
    }

    .st-tab {
        flex: 1;
        padding: 8px 2px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        cursor: pointer;
        color: #3a3a52;
        border-bottom: 2px solid transparent;
        border: none;
        background: transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        transition: all .14s;
        font-family: inherit;
    }

    .st-tab i {
        font-size: 14px;
    }

    .st-tab.on {
        color: #60a5fa;
        border-bottom-color: #2563eb;
    }

    .st-body {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
        scrollbar-width: thin;
        scrollbar-color: #1e1e2c transparent;
    }

    .st-body::-webkit-scrollbar {
        width: 3px;
    }

    .st-body::-webkit-scrollbar-thumb {
        background: #1e1e2c;
        border-radius: 2px;
    }

    /* Form atoms */
    .st-sec {
        margin-bottom: 14px;
    }

    .st-sh {
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: #3a3a52;
        margin-bottom: 7px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .st-sh i {
        font-size: 12px;
    }

    .st-row {
        display: flex;
        gap: 5px;
        margin-bottom: 6px;
    }

    .st-row:last-child {
        margin-bottom: 0;
    }

    .st-col {
        flex: 1;
        min-width: 0;
    }

    .st-lbl {
        font-size: 9.5px;
        font-weight: 600;
        color: #44445a;
        display: block;
        margin-bottom: 3px;
    }

    .st-inp {
        width: 100%;
        background: #0a0a12;
        border: 1px solid #1e1e2c;
        border-radius: 5px;
        padding: 5px 7px;
        color: #b0b0cc;
        font-size: 11px;
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        outline: none;
        transition: border-color .14s;
    }

    .st-inp:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .15);
    }

    .st-inp::placeholder {
        color: #282838;
    }

    .st-sel {
        width: 100%;
        background: #0a0a12;
        border: 1px solid #1e1e2c;
        border-radius: 5px;
        padding: 5px 7px;
        color: #b0b0cc;
        font-size: 11px;
        font-family: inherit;
        outline: none;
        cursor: pointer;
    }

    select.st-select {
        width: 100%;
        background: #0a0a12;
        border: 1px solid #1e1e2c;
        border-radius: 5px;
        padding: 5px 7px;
        color: #b0b0cc;
        font-size: 11px;
        font-family: inherit;
        outline: none;
        cursor: pointer;
    }

    .st-pills {
        display: flex;
        gap: 4px;
    }

    .st-pill {
        flex: 1;
        padding: 6px 3px;
        border-radius: 6px;
        font-size: 9px;
        font-weight: 700;
        cursor: pointer;
        border: 1.5px solid #1e1e2c;
        background: #0a0a12;
        color: #44445a;
        text-align: center;
        transition: all .14s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 3px;
    }

    .st-pill.on {
        border-color: #2563eb;
        background: rgba(37, 99, 235, .14);
        color: #60a5fa;
    }

    .st-clr-row {
        display: flex;
        gap: 5px;
        align-items: center;
    }

    .st-sw-inp {
        width: 26px;
        height: 26px;
        border-radius: 5px;
        border: 1px solid #1e1e2c;
        padding: 2px;
        cursor: pointer;
        flex-shrink: 0;
        background: none;
    }

    .st-hex {
        flex: 1;
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        background: #0a0a12;
        border: 1px solid #1e1e2c;
        border-radius: 5px;
        padding: 4px 6px;
        color: #b0b0cc;
        outline: none;
    }

    .st-hex:focus {
        border-color: #2563eb;
    }

    .st-sld-row {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .st-sld-row input[type=range] {
        flex: 1;
        accent-color: #2563eb;
        cursor: pointer;
    }

    .st-sld-val {
        font-family: 'JetBrains Mono', monospace;
        font-size: 10px;
        color: #44445a;
        min-width: 32px;
        text-align: right;
    }

    .st-tog-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 3px 0;
    }

    .st-tog-lbl {
        font-size: 11px;
        font-weight: 500;
        color: #8080a0;
    }

    .st-sw {
        position: relative;
        width: 34px;
        height: 19px;
        cursor: pointer;
        display: inline-block;
        flex-shrink: 0;
    }

    .st-sw input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }

    .st-sw-tr {
        position: absolute;
        inset: 0;
        border-radius: 99px;
        background: #1e1e2c;
        transition: background .18s;
    }

    .st-sw input:checked~.st-sw-tr {
        background: #2563eb;
    }

    .st-sw-th {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background: #fff;
        transition: left .18s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .4);
    }

    .st-sw input:checked~.st-sw-th {
        left: 17px;
    }

    .st-div {
        height: 1px;
        background: #1e1e2c;
        margin: 10px 0;
    }

    .st-thumb {
        width: 100%;
        height: 30px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #1e1e2c;
        margin-top: 4px;
        display: none;
    }

    .st-sz {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4px;
    }

    .st-sz-f {
        position: relative;
    }

    .st-sz-f .st-inp {
        padding-right: 18px;
    }

    .st-sz-u {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 8px;
        font-weight: 700;
        color: #282838;
        pointer-events: none;
        font-family: 'JetBrains Mono', monospace;
    }

    /* XY position inputs — 2 side by side with unit label */
    .st-xy {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4px;
    }

    .st-xy-f {
        position: relative;
    }

    .st-xy-f .st-inp {
        padding-right: 14px;
    }

    .st-xy-u {
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 7.5px;
        font-weight: 800;
        color: #282838;
        pointer-events: none;
        font-family: 'JetBrains Mono', monospace;
    }

    /* ── Canvas ── */
    .st-canvas {
        flex: 1;
        overflow: auto;
        background: #0d0d14;
        background-image:
            linear-gradient(rgba(255, 255, 255, .012) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, .012) 1px, transparent 1px);
        background-size: 20px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 28px 20px;
        gap: 14px;
    }

    .st-canvas-lbl {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .9px;
        color: #282838;
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        max-width: 480px;
    }

    .st-canvas-lbl::before,
    .st-canvas-lbl::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #1a1a26;
    }

    .st-pw {
        width: 100%;
        max-width: 480px;
        border-radius: 16px;
        overflow: hidden;
        flex-shrink: 0;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, .05), 0 24px 64px rgba(0, 0, 0, .6);
    }

    /* ── Banner preview — exact replica of hd-strip system ── */
    #prevCanvas {
        position: relative;
        overflow: hidden;
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

    /* hd-strip exact replica */
    #prevStrip {
        position: relative;
        width: 100%;
        overflow: visible;
        margin-top: 12px;
    }

    /* gambar kiri — absolute anchor LEFT+BOTTOM */
    #prevImgLeft {
        position: absolute;
        object-fit: contain;
        display: block;
        left: 0;
        bottom: 0;
    }

    /* gambar kanan — absolute anchor RIGHT+BOTTOM */
    #prevImgRight {
        position: absolute;
        object-fit: contain;
        display: block;
        right: 0;
        bottom: 0;
    }

    /* center — absolute, horizontal center */
    #prevCenter {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        bottom: 0;
    }

    #prevTitle {
        line-height: 1.15;
        letter-spacing: -.3px;
        text-shadow: 0 1px 6px rgba(0, 0, 0, .25);
    }

    #prevSub {
        opacity: .92;
        text-shadow: 0 1px 3px rgba(0, 0, 0, .2);
        line-height: 1.3;
    }

    #prevCenterImg {
        object-fit: contain;
        display: block;
    }

    #prevBtn {
        display: inline-block;
        letter-spacing: .4px;
        text-decoration: none;
        cursor: pointer;
        border: none;
        font-family: inherit;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .22);
        transition: transform .15s;
        white-space: nowrap;
    }

    #prevInactiveOv {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .65);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 20;
        pointer-events: none;
    }

    .st-ib {
        background: rgba(220, 38, 38, .15);
        border: 1px solid #dc2626;
        color: #f87171;
        font-size: 11px;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Drag handles on canvas */
    .drg {
        position: relative;
        display: inline-block;
        outline: 2px solid transparent;
        outline-offset: 3px;
        border-radius: 4px;
        cursor: grab;
        transition: outline-color .12s;
        user-select: none;
    }

    .drg:hover {
        outline-color: rgba(96, 165, 250, .5);
    }

    .drg.sel {
        outline-color: #60a5fa;
    }

    .drg.dragging {
        cursor: grabbing;
    }

    .drg.sel::after {
        content: attr(data-lbl);
        position: absolute;
        top: -20px;
        left: 0;
        background: #2563eb;
        color: #fff;
        font-size: 8.5px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 4px 4px 0 0;
        white-space: nowrap;
        pointer-events: none;
        z-index: 100;
        font-family: system-ui, sans-serif;
    }

    /* Canva-style resize handles */
    .rsz {
        position: absolute;
        bottom: -5px;
        right: -5px;
        width: 11px;
        height: 11px;
        background: #2563eb;
        border: 2px solid #0d0d14;
        border-radius: 3px;
        cursor: se-resize;
        z-index: 50;
        display: none;
    }

    .drg.sel .rsz {
        display: block;
    }

    /* Multi-handle Canva system */
    .rsz-h {
        position: absolute;
        background: #2563eb;
        border: 2px solid #fff;
        border-radius: 2px;
        z-index: 60;
        display: none;
    }

    .drg.sel .rsz-h {
        display: block;
    }

    /* Corner SE */
    .rsz-se {
        width: 10px;
        height: 10px;
        right: -5px;
        bottom: -5px;
        cursor: se-resize;
        border-radius: 3px;
    }

    /* Edge E (right middle) */
    .rsz-e {
        width: 8px;
        height: 18px;
        right: -4px;
        top: 50%;
        transform: translateY(-50%);
        cursor: e-resize;
        border-radius: 2px;
    }

    /* Edge S (bottom middle) */
    .rsz-s {
        width: 18px;
        height: 8px;
        bottom: -4px;
        left: 50%;
        transform: translateX(-50%);
        cursor: s-resize;
        border-radius: 2px;
    }

    /* Edge W (left middle) */
    .rsz-w {
        width: 8px;
        height: 18px;
        left: -4px;
        top: 50%;
        transform: translateY(-50%);
        cursor: w-resize;
        border-radius: 2px;
    }

    /* Edge N (top middle) */
    .rsz-n {
        width: 18px;
        height: 8px;
        top: -4px;
        left: 50%;
        transform: translateX(-50%);
        cursor: n-resize;
        border-radius: 2px;
    }

    /* Corner NW */
    .rsz-nw {
        width: 10px;
        height: 10px;
        left: -5px;
        top: -5px;
        cursor: nw-resize;
        border-radius: 3px;
    }

    /* Corner NE */
    .rsz-ne {
        width: 10px;
        height: 10px;
        right: -5px;
        top: -5px;
        cursor: ne-resize;
        border-radius: 3px;
    }

    /* Corner SW */
    .rsz-sw {
        width: 10px;
        height: 10px;
        left: -5px;
        bottom: -5px;
        cursor: sw-resize;
        border-radius: 3px;
    }

    /* Selection border highlight */
    .drg.sel {
        outline-color: #2563eb !important;
    }

    /* Prevent pointer capture on resize handles from bubbling to click-outside */
    .rsz,
    .rsz-h {
        pointer-events: auto;
    }

    /* Ruler */
    .st-ruler {
        width: 100%;
        max-width: 480px;
        background: #111118;
        border: 1px solid #1e1e2c;
        border-radius: 8px;
        padding: 6px 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 9.5px;
        color: #3a3a52;
        flex-shrink: 0;
    }

    .st-ruler strong {
        color: #5a5a78;
        font-family: 'JetBrains Mono', monospace;
    }

    .st-rdot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* ── Right props panel ── */
    .st-props {
        width: 220px;
        flex-shrink: 0;
        background: #111118;
        border-left: 1px solid #1e1e2c;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .st-ph {
        padding: 10px 12px;
        border-bottom: 1px solid #1e1e2c;
        flex-shrink: 0;
    }

    .st-ph-t {
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: #3a3a52;
    }

    .st-ph-el {
        font-size: 10.5px;
        font-weight: 700;
        color: #5a5a78;
        margin-top: 2px;
    }

    .st-pb {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
        scrollbar-width: thin;
        scrollbar-color: #1e1e2c transparent;
    }

    .st-pb::-webkit-scrollbar {
        width: 3px;
    }

    .st-empty {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 16px;
        text-align: center;
    }

    .st-empty i {
        font-size: 26px;
        color: #1e1e2c;
    }

    .st-empty p {
        font-size: 10px;
        color: #2a2a3a;
        line-height: 1.6;
    }

    /* Toast */
    .st-toast-wrap {
        position: fixed;
        top: 14px;
        right: 14px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .st-toast {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .4);
    }

    .st-toast i {
        font-size: 14px;
    }

    .st-toast-ok {
        background: #064e3b;
        color: #6ee7b7;
        border: 1px solid #065f46;
    }

    .st-toast-err {
        background: #7f1d1d;
        color: #fca5a5;
        border: 1px solid #b91c1c;
    }

    /* Keyframes */
    @keyframes hb-float {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-5px)
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

    @keyframes hb-sliL {
        from {
            transform: translateX(-20px);
            opacity: 0
        }

        to {
            transform: none;
            opacity: 1
        }
    }

    @keyframes hb-sliR {
        from {
            transform: translateX(20px);
            opacity: 0
        }

        to {
            transform: none;
            opacity: 1
        }
    }

    @keyframes hb-pulse {

        0%,
        100% {
            transform: scale(1)
        }

        50% {
            transform: scale(1.07)
        }
    }

    @keyframes hb-zoom {
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

<?php $b = $banner; ?>
<form method="POST" id="stForm">
    <input type="hidden" name="action" value="save" />
    <div class="st-root">

        <!-- TOOL STRIP -->
        <div class="st-strip">
            <button type="button" class="st-tb on" id="tbGambar" onclick="swTool('Gambar')" title="Gambar"><i class="ph ph-images"></i></button>
            <button type="button" class="st-tb" id="tbKonten" onclick="swTool('Konten')" title="Konten"><i class="ph ph-text-aa"></i></button>
            <button type="button" class="st-tb" id="tbPosisi" onclick="swTool('Posisi')" title="Posisi &amp; Ukuran"><i class="ph ph-arrows-out-cardinal"></i></button>
            <button type="button" class="st-tb" id="tbTombol" onclick="swTool('Tombol')" title="Tombol"><i class="ph ph-cursor-click"></i></button>
            <div class="st-tb-sep"></div>
            <button type="button" class="st-tb" id="tbSet" onclick="swTool('Set')" title="Pengaturan"><i class="ph ph-gear-six"></i></button>
        </div>

        <!-- INSPECTOR -->
        <div class="st-insp">
            <div class="st-savebar">
                <span class="st-dirty" id="dirtyDot"></span>
                <span class="st-save-txt" id="saveTxt">Semua tersimpan</span>
                <button type="submit" class="st-save-btn"><i class="ph ph-floppy-disk"></i>Simpan</button>
            </div>
            <div class="st-tabs">
                <button type="button" class="st-tab on" data-p="pGambar"><i class="ph ph-images"></i>Gambar</button>
                <button type="button" class="st-tab" data-p="pKonten"><i class="ph ph-text-aa"></i>Konten</button>
                <button type="button" class="st-tab" data-p="pPosisi"><i class="ph ph-arrows-out-cardinal"></i>Posisi</button>
                <button type="button" class="st-tab" data-p="pTombol"><i class="ph ph-cursor-click"></i>Tombol</button>
                <button type="button" class="st-tab" data-p="pSet"><i class="ph ph-gear-six"></i>Set</button>
            </div>
            <div class="st-body">

                <!-- ══ PANEL GAMBAR ══ -->
                <div id="pGambar">
                    <!-- Gambar Kiri -->
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-align-left" style="color:#a855f7"></i>Gambar Kiri</div>
                        <input type="text" name="img_left" id="f_img_left" class="st-inp"
                            placeholder="https://…" value="<?= htmlspecialchars($b['img_left'] ?? '') ?>"
                            oninput="livePreview();showThumb(this,'ilT');markDirty()" />
                        <img id="ilT" class="st-thumb" src="<?= htmlspecialchars($b['img_left'] ?? '') ?>" alt=""
                            style="<?= !empty($b['img_left']) ? 'display:block' : '' ?>" />
                        <div class="st-row" style="margin-top:6px">
                            <div class="st-col">
                                <span class="st-lbl">W × H</span>
                                <div class="st-sz">
                                    <div class="st-sz-f">
                                        <input type="number" name="img_left_w" id="f_img_left_w" class="st-inp"
                                            min="10" max="400" value="<?= (int)($b['img_left_w'] ?? 90) ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-sz-u">W</span>
                                    </div>
                                    <div class="st-sz-f">
                                        <input type="number" name="img_left_h" id="f_img_left_h" class="st-inp"
                                            min="0" max="400" placeholder="auto"
                                            value="<?= (int)($b['img_left_h'] ?? 0) > 0 ? (int)$b['img_left_h'] : '' ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-sz-u">H</span>
                                    </div>
                                </div>
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Anim</span>
                                <?= anim_sel('img_left_anim', $b['img_left_anim'] ?? '') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Gambar Kanan -->
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-align-right" style="color:#f59e0b"></i>Gambar Kanan</div>
                        <input type="text" name="img_right" id="f_img_right" class="st-inp"
                            placeholder="https://…" value="<?= htmlspecialchars($b['img_right'] ?? '') ?>"
                            oninput="livePreview();showThumb(this,'irT');markDirty()" />
                        <img id="irT" class="st-thumb" src="<?= htmlspecialchars($b['img_right'] ?? '') ?>" alt=""
                            style="<?= !empty($b['img_right']) ? 'display:block' : '' ?>" />
                        <div class="st-row" style="margin-top:6px">
                            <div class="st-col">
                                <span class="st-lbl">W × H</span>
                                <div class="st-sz">
                                    <div class="st-sz-f">
                                        <input type="number" name="img_right_w" id="f_img_right_w" class="st-inp"
                                            min="10" max="400" value="<?= (int)($b['img_right_w'] ?? 90) ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-sz-u">W</span>
                                    </div>
                                    <div class="st-sz-f">
                                        <input type="number" name="img_right_h" id="f_img_right_h" class="st-inp"
                                            min="0" max="400" placeholder="auto"
                                            value="<?= (int)($b['img_right_h'] ?? 0) > 0 ? (int)$b['img_right_h'] : '' ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-sz-u">H</span>
                                    </div>
                                </div>
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Anim</span>
                                <?= anim_sel('img_right_anim', $b['img_right_anim'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                </div><!-- /pGambar -->

                <!-- ══ PANEL KONTEN ══ -->
                <div id="pKonten" style="display:none">
                    <!-- Center type pills -->
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-align-center-horizontal"></i>Isi Tengah</div>
                        <div class="st-pills">
                            <?php foreach (['text' => ['ph-text-aa', 'Teks & Tombol'], 'image' => ['ph-image-square', 'Gambar']] as $v => [$ic, $lb]): ?>
                                <div class="st-pill <?= ($b['center_type'] ?? 'text') === $v ? 'on' : '' ?>"
                                    onclick="setCT('<?= $v ?>')">
                                    <input type="radio" name="center_type" value="<?= $v ?>"
                                        <?= ($b['center_type'] ?? 'text') === $v ? 'checked' : '' ?> style="display:none" />
                                    <i class="ph <?= $ic ?>"></i><?= $lb ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Teks fields -->
                    <div id="ctT">
                        <div class="st-sec">
                            <div class="st-sh"><i class="ph ph-text-h" style="color:#f59e0b"></i>Judul</div>
                            <div class="st-row" style="align-items:flex-end">
                                <div style="flex-shrink:0">
                                    <span class="st-lbl">Clr</span>
                                    <input type="color" name="title_color" class="st-sw-inp"
                                        value="<?= htmlspecialchars($b['title_color'] ?? '#ffffff') ?>"
                                        oninput="livePreview()" />
                                </div>
                                <div class="st-col">
                                    <span class="st-lbl">Teks Judul</span>
                                    <input type="text" name="title" id="f_title" class="st-inp" maxlength="200"
                                        placeholder="KLAIM HADIAH"
                                        value="<?= htmlspecialchars($b['title'] ?? '') ?>"
                                        oninput="livePreview()" />
                                </div>
                            </div>
                            <div class="st-row" style="margin-top:5px">
                                <div class="st-col">
                                    <span class="st-lbl">Size</span>
                                    <input type="text" name="title_size" class="st-inp" maxlength="20"
                                        placeholder="15px"
                                        value="<?= htmlspecialchars($b['title_size'] ?? '15px') ?>"
                                        oninput="livePreview();markDirty()" />
                                </div>
                                <div class="st-col">
                                    <span class="st-lbl">Weight</span>
                                    <select name="title_weight" class="st-select" onchange="livePreview();markDirty()">
                                        <?php foreach (['400', '500', '600', '700', '800', '900'] as $w): ?>
                                            <option value="<?= $w ?>" <?= ($b['title_weight'] ?? '900') === $w ? 'selected' : '' ?>><?= $w ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="st-col">
                                    <span class="st-lbl">Margin B</span>
                                    <input type="text" name="title_mb" class="st-inp" maxlength="20"
                                        placeholder="2px"
                                        value="<?= htmlspecialchars($b['title_mb'] ?? '2px') ?>"
                                        oninput="livePreview();markDirty()" />
                                </div>
                            </div>
                        </div>
                        <div class="st-sec">
                            <div class="st-sh"><i class="ph ph-text-align-left" style="color:#10b981"></i>Subtitle</div>
                            <div class="st-row" style="align-items:flex-end">
                                <div style="flex-shrink:0">
                                    <span class="st-lbl">Clr</span>
                                    <input type="color" name="subtitle_color" class="st-sw-inp"
                                        value="<?= htmlspecialchars($b['subtitle_color'] ?? '#ffffffd9') ?>"
                                        oninput="livePreview()" />
                                </div>
                                <div class="st-col">
                                    <span class="st-lbl">Teks Subtitle</span>
                                    <input type="text" name="subtitle" id="f_subtitle" class="st-inp" maxlength="300"
                                        placeholder="& Jutaan Rupiah"
                                        value="<?= htmlspecialchars($b['subtitle'] ?? '') ?>"
                                        oninput="livePreview()" />
                                </div>
                            </div>
                            <div class="st-row" style="margin-top:5px">
                                <div class="st-col">
                                    <span class="st-lbl">Size</span>
                                    <input type="text" name="subtitle_size" class="st-inp" maxlength="20"
                                        placeholder="10.5px"
                                        value="<?= htmlspecialchars($b['subtitle_size'] ?? '10.5px') ?>"
                                        oninput="livePreview();markDirty()" />
                                </div>
                                <div class="st-col">
                                    <span class="st-lbl">Margin B</span>
                                    <input type="text" name="subtitle_mb" class="st-inp" maxlength="20"
                                        placeholder="10px"
                                        value="<?= htmlspecialchars($b['subtitle_mb'] ?? '10px') ?>"
                                        oninput="livePreview();markDirty()" />
                                </div>
                            </div>
                        </div>
                    </div><!-- /ctT -->

                    <!-- Gambar tengah fields -->
                    <div id="ctI" style="display:none">
                        <div class="st-sec">
                            <div class="st-sh"><i class="ph ph-image-square" style="color:#a855f7"></i>Gambar Tengah</div>
                            <input type="text" name="center_image" class="st-inp"
                                placeholder="https://…"
                                value="<?= htmlspecialchars($b['center_image'] ?? '') ?>"
                                oninput="livePreview();showThumb(this,'ciT');markDirty()" />
                            <img id="ciT" class="st-thumb" src="<?= htmlspecialchars($b['center_image'] ?? '') ?>" alt=""
                                style="<?= !empty($b['center_image']) ? 'display:block' : '' ?>" />
                            <div class="st-row" style="margin-top:6px">
                                <div class="st-col">
                                    <span class="st-lbl">W × H</span>
                                    <div class="st-sz">
                                        <div class="st-sz-f">
                                            <input type="number" name="center_img_w" id="f_center_img_w" class="st-inp"
                                                min="10" max="600"
                                                value="<?= (int)($b['center_img_w'] ?? 160) ?>"
                                                oninput="livePreview();markDirty()" />
                                            <span class="st-sz-u">W</span>
                                        </div>
                                        <div class="st-sz-f">
                                            <input type="number" name="center_img_h" id="f_center_img_h" class="st-inp"
                                                min="0" max="600" placeholder="auto"
                                                value="<?= (int)($b['center_img_h'] ?? 0) > 0 ? (int)$b['center_img_h'] : '' ?>"
                                                oninput="livePreview();markDirty()" />
                                            <span class="st-sz-u">H</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="st-col">
                                    <span class="st-lbl">Margin B</span>
                                    <input type="text" name="center_img_mb" class="st-inp" maxlength="20"
                                        placeholder="0"
                                        value="<?= htmlspecialchars($b['center_img_mb'] ?? '0') ?>"
                                        oninput="livePreview();markDirty()" />
                                </div>
                            </div>
                            <div class="st-row">
                                <div class="st-col">
                                    <span class="st-lbl">Anim</span>
                                    <?= anim_sel('center_img_anim', $b['center_img_anim'] ?? '') ?>
                                </div>
                            </div>
                        </div>
                    </div><!-- /ctI -->
                </div><!-- /pKonten -->

                <!-- ══ PANEL POSISI ══ -->
                <div id="pPosisi" style="display:none">

                    <!-- Gambar Kiri posisi -->
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-align-left" style="color:#a855f7"></i>Posisi Gambar Kiri</div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">X — dari tepi kiri</span>
                                <div class="st-xy">
                                    <div class="st-xy-f">
                                        <input type="text" name="img_left_x" id="f_img_left_x" class="st-inp"
                                            placeholder="0" maxlength="20"
                                            value="<?= htmlspecialchars($b['img_left_x'] ?? '0') ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-xy-u">X</span>
                                    </div>
                                    <div class="st-xy-f">
                                        <input type="text" name="img_left_y" id="f_img_left_y" class="st-inp"
                                            placeholder="0" maxlength="20"
                                            value="<?= htmlspecialchars($b['img_left_y'] ?? '0') ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-xy-u">Y</span>
                                    </div>
                                </div>
                                <div style="font-size:9px;color:#282838;margin-top:3px;line-height:1.4">
                                    X = offset kiri&nbsp;&nbsp;Y = offset bawah<br>contoh: <code style="color:#60a5fa">-10px</code> / <code style="color:#60a5fa">50%</code>
                                </div>
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Z-index</span>
                                <input type="number" name="img_left_z" class="st-inp" min="0" max="99"
                                    value="<?= (int)($b['img_left_z'] ?? 1) ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                        </div>
                    </div>

                    <!-- Gambar Kanan posisi -->
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-align-right" style="color:#f59e0b"></i>Posisi Gambar Kanan</div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">X — dari tepi kanan</span>
                                <div class="st-xy">
                                    <div class="st-xy-f">
                                        <input type="text" name="img_right_x" id="f_img_right_x" class="st-inp"
                                            placeholder="0" maxlength="20"
                                            value="<?= htmlspecialchars($b['img_right_x'] ?? '0') ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-xy-u">X</span>
                                    </div>
                                    <div class="st-xy-f">
                                        <input type="text" name="img_right_y" id="f_img_right_y" class="st-inp"
                                            placeholder="0" maxlength="20"
                                            value="<?= htmlspecialchars($b['img_right_y'] ?? '0') ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-xy-u">Y</span>
                                    </div>
                                </div>
                                <div style="font-size:9px;color:#282838;margin-top:3px;line-height:1.4">
                                    X = offset kanan&nbsp;&nbsp;Y = offset bawah
                                </div>
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Z-index</span>
                                <input type="number" name="img_right_z" class="st-inp" min="0" max="99"
                                    value="<?= (int)($b['img_right_z'] ?? 1) ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                        </div>
                    </div>

                    <!-- Center posisi -->
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-align-center-horizontal" style="color:#3b82f6"></i>Posisi Center</div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">Y — dari bawah strip</span>
                                <input type="text" name="center_y" id="f_center_y" class="st-inp"
                                    placeholder="0" maxlength="20"
                                    value="<?= htmlspecialchars($b['center_y'] ?? '0') ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Max-width (px)</span>
                                <input type="number" name="center_w" class="st-inp" min="40"
                                    placeholder="auto"
                                    value="<?= (int)($b['center_w'] ?? 0) > 0 ? (int)$b['center_w'] : '' ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Z-index</span>
                                <input type="number" name="center_z" class="st-inp" min="0" max="99"
                                    value="<?= (int)($b['center_z'] ?? 2) ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                        </div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">Margin Top</span>
                                <input type="text" name="center_mt" class="st-inp" maxlength="20"
                                    placeholder="—" value="<?= htmlspecialchars($b['center_mt'] ?? '') ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Margin Bottom</span>
                                <input type="text" name="center_mb" class="st-inp" maxlength="20"
                                    placeholder="—" value="<?= htmlspecialchars($b['center_mb'] ?? '') ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                        </div>
                        <div style="font-size:9px;color:#282838;margin-top:3px;line-height:1.4">
                            Center selalu horizontal-center otomatis.<br>
                            Y = offset dari bawah (bottom). MT/MB = margin layout.
                        </div>
                    </div>

                    <div style="font-size:9px;color:#282838;background:#0a0a12;border-radius:6px;padding:7px 9px;line-height:1.5;margin-top:4px">
                        <strong style="color:#3a3a52">Sistem Absolute</strong><br>
                        Gambar kiri/kanan pakai <code style="color:#60a5fa">position:absolute</code>.<br>
                        Ubah ukuran gambar tidak akan menggeser center.
                    </div>
                </div><!-- /pPosisi -->

                <!-- ══ PANEL TOMBOL ══ -->
                <div id="pTombol" style="display:none">
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-cursor-click" style="color:#10b981"></i>Tombol</div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">Teks (kosong = tidak tampil)</span>
                                <input type="text" name="btn_text" class="st-inp" maxlength="80"
                                    placeholder="SERBU"
                                    value="<?= htmlspecialchars($b['btn_text'] ?? '') ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Link</span>
                                <input type="text" name="btn_href" class="st-inp" maxlength="255"
                                    placeholder="#"
                                    value="<?= htmlspecialchars($b['btn_href'] ?? '#') ?>" />
                            </div>
                        </div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">BG Color</span>
                                <div class="st-clr-row">
                                    <input type="color" class="st-sw-inp" id="sw_bc"
                                        value="<?= htmlspecialchars(substr($b['btn_color'] ?? '#FFD700', 0, 7)) ?>"
                                        oninput="syncH(this,'hx_bc');livePreview();markDirty()" />
                                    <input type="text" id="hx_bc" class="st-hex" maxlength="9"
                                        value="<?= htmlspecialchars($b['btn_color'] ?? '#FFD700') ?>"
                                        oninput="syncS(this,'sw_bc');document.querySelector('[name=btn_color]').value=this.value;livePreview();markDirty()" />
                                    <input type="hidden" name="btn_color" value="<?= htmlspecialchars($b['btn_color'] ?? '#FFD700') ?>" />
                                </div>
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Text Color</span>
                                <div class="st-clr-row">
                                    <input type="color" class="st-sw-inp" id="sw_btc"
                                        value="<?= htmlspecialchars(substr($b['btn_text_color'] ?? '#000000', 0, 7)) ?>"
                                        oninput="syncH(this,'hx_btc');livePreview();markDirty()" />
                                    <input type="text" id="hx_btc" class="st-hex" maxlength="9"
                                        value="<?= htmlspecialchars($b['btn_text_color'] ?? '#000000') ?>"
                                        oninput="syncS(this,'sw_btc');document.querySelector('[name=btn_text_color]').value=this.value;livePreview();markDirty()" />
                                    <input type="hidden" name="btn_text_color" value="<?= htmlspecialchars($b['btn_text_color'] ?? '#000000') ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="st-div"></div>
                        <!-- Padding -->
                        <div class="st-sh" style="margin-bottom:5px"><i class="ph ph-frame-corners"></i>Padding</div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">Top / Bottom</span>
                                <div class="st-xy">
                                    <div class="st-xy-f">
                                        <input type="text" name="btn_pt" class="st-inp" maxlength="20" placeholder="7px"
                                            value="<?= htmlspecialchars($b['btn_pt'] ?? '7px') ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-xy-u">T</span>
                                    </div>
                                    <div class="st-xy-f">
                                        <input type="text" name="btn_pb" class="st-inp" maxlength="20" placeholder="7px"
                                            value="<?= htmlspecialchars($b['btn_pb'] ?? '7px') ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-xy-u">B</span>
                                    </div>
                                </div>
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Left / Right</span>
                                <div class="st-xy">
                                    <div class="st-xy-f">
                                        <input type="text" name="btn_pl" class="st-inp" maxlength="20" placeholder="26px"
                                            value="<?= htmlspecialchars($b['btn_pl'] ?? '26px') ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-xy-u">L</span>
                                    </div>
                                    <div class="st-xy-f">
                                        <input type="text" name="btn_pr" class="st-inp" maxlength="20" placeholder="26px"
                                            value="<?= htmlspecialchars($b['btn_pr'] ?? '26px') ?>"
                                            oninput="livePreview();markDirty()" />
                                        <span class="st-xy-u">R</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">Border Radius</span>
                                <input type="text" name="btn_radius" class="st-inp" maxlength="20" placeholder="99px"
                                    value="<?= htmlspecialchars($b['btn_radius'] ?? '99px') ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Font Size</span>
                                <input type="text" name="btn_size" class="st-inp" maxlength="20" placeholder="12px"
                                    value="<?= htmlspecialchars($b['btn_size'] ?? '12px') ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Weight</span>
                                <select name="btn_weight" class="st-select" onchange="livePreview();markDirty()">
                                    <?php foreach (['400', '500', '600', '700', '800', '900'] as $w): ?>
                                        <option value="<?= $w ?>" <?= ($b['btn_weight'] ?? '900') === $w ? 'selected' : '' ?>><?= $w ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">Animasi</span>
                                <?= anim_sel('btn_anim', $b['btn_anim'] ?? 'pulse') ?>
                            </div>
                        </div>
                        <div class="st-div"></div>
                        <div class="st-sh" style="margin-bottom:5px"><i class="ph ph-resize"></i>Ukuran &amp; Posisi Tombol</div>
                        <div class="st-row">
                            <div class="st-col">
                                <span class="st-lbl">Min-Width (px)</span>
                                <input type="text" name="btn_w" class="st-inp" maxlength="20" placeholder="auto"
                                    value="<?= htmlspecialchars($b['btn_w'] ?? '') ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Height (px)</span>
                                <input type="text" name="btn_h" class="st-inp" maxlength="20" placeholder="auto"
                                    value="<?= htmlspecialchars($b['btn_h'] ?? '') ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                            <div class="st-col">
                                <span class="st-lbl">Margin Top (Y)</span>
                                <input type="text" name="btn_y" class="st-inp" maxlength="20" placeholder="—"
                                    value="<?= htmlspecialchars($b['btn_y'] ?? '') ?>"
                                    oninput="livePreview();markDirty()" />
                            </div>
                        </div>
                        <div class="st-div"></div>
                        <div class="st-tog-row">
                            <span class="st-tog-lbl">Tampilkan Tombol</span>
                            <label class="st-sw">
                                <input type="checkbox" name="btn_show" id="f_btn_show"
                                    <?= ($b['btn_show'] ?? 1) ? 'checked' : '' ?>
                                    onchange="markDirty();livePreview()" />
                                <span class="st-sw-tr"></span><span class="st-sw-th"></span>
                            </label>
                        </div>
                    </div>
                </div><!-- /pTombol -->

                <!-- ══ PANEL SETTINGS ══ -->
                <div id="pSet" style="display:none">
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-arrows-vertical"></i>Strip</div>
                        <div class="st-sld-row">
                            <input type="range" name="height" min="60" max="280"
                                value="<?= (int)($b['height'] ?? 160) ?>"
                                oninput="document.getElementById('hV').textContent=this.value+'px';livePreview();markDirty()" />
                            <span id="hV" class="st-sld-val"><?= (int)$b['height'] ?>px</span>
                        </div>
                    </div>
                    <div class="st-div"></div>
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-toggle-right"></i>Status</div>
                        <div class="st-tog-row">
                            <span class="st-tog-lbl">Aktifkan Banner</span>
                            <label class="st-sw">
                                <input type="checkbox" name="is_active"
                                    <?= $b['is_active'] ? 'checked' : '' ?>
                                    onchange="markDirty();livePreview()" />
                                <span class="st-sw-tr"></span><span class="st-sw-th"></span>
                            </label>
                        </div>
                    </div>
                    <div class="st-div"></div>
                    <div class="st-sec">
                        <div class="st-sh"><i class="ph ph-database"></i>Info</div>
                        <?php foreach (
                            [
                                ['ID',      '#' . $b['id']],
                                ['Height',  $b['height'] . 'px'],
                                ['Dibuat',  date('d M Y', strtotime($b['created_at']))],
                                ['Update',  date('d M Y', strtotime($b['updated_at']))],
                            ] as [$k, $v]
                        ): ?>
                            <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #1a1a26">
                                <span style="font-size:10px;color:#3a3a52"><?= $k ?></span>
                                <span style="font-size:10px;font-weight:600;font-family:'JetBrains Mono',monospace;color:#5a5a78"><?= htmlspecialchars($v) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div><!-- /pSet -->

            </div><!-- /st-body -->
        </div><!-- /st-insp -->

        <!-- ══ CANVAS ══ -->
        <div class="st-canvas">
            <div class="st-canvas-lbl">Live Preview</div>
            <div class="st-pw">
                <?php
                // Build hero bg inline style
                $heroBg = "background:linear-gradient(135deg,#0066cc,#0099ff)"; // default, dioverride JS
                $h = (int)($b['height'] ?? 160);
                $leftX  = htmlspecialchars($b['img_left_x']  ?? '0');
                $leftY  = htmlspecialchars($b['img_left_y']  ?? '0');
                $leftW  = (int)($b['img_left_w']  ?? 90);
                $leftH  = (int)($b['img_left_h']  ?? 0) > 0 ? (int)$b['img_left_h'] . 'px' : 'auto';
                $leftZ  = (int)($b['img_left_z']  ?? 1);
                $rightX = htmlspecialchars($b['img_right_x'] ?? '0');
                $rightY = htmlspecialchars($b['img_right_y'] ?? '0');
                $rightW = (int)($b['img_right_w'] ?? 90);
                $rightH = (int)($b['img_right_h'] ?? 0) > 0 ? (int)$b['img_right_h'] . 'px' : 'auto';
                $rightZ = (int)($b['img_right_z'] ?? 1);
                $centerY  = htmlspecialchars($b['center_y']  ?? '0');
                $centerW  = (int)($b['center_w'] ?? 0) > 0 ? (int)$b['center_w'] . 'px' : 'auto';
                $centerZ  = (int)($b['center_z'] ?? 2);
                $centerMt = htmlspecialchars($b['center_mt'] ?? '');
                $centerMb = htmlspecialchars($b['center_mb'] ?? '');
                $centerMargin = ($centerMt !== '' || $centerMb !== '') ?
                    ($centerMt !== '' ? "margin-top:{$centerMt};" : '') .
                    ($centerMb !== '' ? "margin-bottom:{$centerMb};" : '') : '';
                $titleColor = htmlspecialchars($b['title_color'] ?? '#fff');
                $titleSize  = htmlspecialchars($b['title_size']  ?? '15px');
                $titleWeight = htmlspecialchars($b['title_weight'] ?? '900');
                $titleMb    = htmlspecialchars($b['title_mb']    ?? '2px');
                $subColor   = htmlspecialchars($b['subtitle_color'] ?? '#ffffffd9');
                $subSize    = htmlspecialchars($b['subtitle_size']  ?? '10.5px');
                $subMb      = htmlspecialchars($b['subtitle_mb']    ?? '10px');
                $cImgW  = (int)($b['center_img_w'] ?? 160);
                $cImgH  = (int)($b['center_img_h'] ?? 0) > 0 ? (int)$b['center_img_h'] . 'px' : 'auto';
                $cImgMb = htmlspecialchars($b['center_img_mb'] ?? '0');
                $btnBg   = htmlspecialchars($b['btn_color']      ?? '#FFD700');
                $btnClr  = htmlspecialchars($b['btn_text_color'] ?? '#000');
                $btnPad  = htmlspecialchars($b['btn_pt'] ?? '7px') . ' ' . htmlspecialchars($b['btn_pr'] ?? '26px') . ' ' . htmlspecialchars($b['btn_pb'] ?? '7px') . ' ' . htmlspecialchars($b['btn_pl'] ?? '26px');
                $btnRad  = htmlspecialchars($b['btn_radius'] ?? '99px');
                $btnSz   = htmlspecialchars($b['btn_size']   ?? '12px');
                $btnWt   = htmlspecialchars($b['btn_weight'] ?? '900');
                $btnY    = htmlspecialchars($b['btn_y']    ?? '');
                $btnW    = htmlspecialchars($b['btn_w']    ?? '');
                $btnH    = htmlspecialchars($b['btn_h']    ?? '');
                $btnShow = (int)($b['btn_show'] ?? 1);
                $btnMarginTop = $btnY !== '' ? "margin-top:{$btnY};" : '';
                $btnMinW      = $btnW !== '' ? "min-width:{$btnW};"  : '';
                $btnHStyle    = $btnH !== '' ? "height:{$btnH};"     : '';
                ?>
                <div id="prevCanvas" style="padding:28px 18px 0;position:relative;overflow:hidden;">
                    <div id="prevInactiveOv" style="<?= $b['is_active'] ? 'display:none' : '' ?>">
                        <span class="st-ib"><i class="ph ph-eye-slash"></i>Banner Nonaktif</span>
                    </div>

                    <!-- hd-strip: position:relative, height=h+44 -->
                    <div id="prevStrip" style="position:relative;width:100%;overflow:visible;margin-top:12px;height:<?= $h + 44 ?>px">

                        <!-- Gambar Kiri — absolute LEFT+BOTTOM -->
                        <?php if (!empty($b['img_left'])): ?>
                            <img id="prevImgLeft"
                                src="<?= htmlspecialchars($b['img_left']) ?>"
                                class="drg <?= $b['img_left_anim'] ? 'anim-' . htmlspecialchars($b['img_left_anim']) : '' ?>"
                                data-lbl="Gambar Kiri"
                                style="position:absolute;object-fit:contain;
                    width:<?= $leftW ?>px;height:<?= $leftH ?>;
                    left:<?= $leftX ?>;bottom:<?= $leftY ?>;z-index:<?= $leftZ ?>;
                    <?= $b['img_left_anim'] ? animCSS_php($b['img_left_anim']) : '' ?>"
                                alt="">
                            <div class="rsz" data-target="img_left" style="position:absolute;left:<?= $leftX ?>;bottom:calc(<?= $leftY ?> - 5px)"></div>
                        <?php endif; ?>

                        <!-- Gambar Kanan — absolute RIGHT+BOTTOM -->
                        <?php if (!empty($b['img_right'])): ?>
                            <img id="prevImgRight"
                                src="<?= htmlspecialchars($b['img_right']) ?>"
                                class="drg <?= $b['img_right_anim'] ? 'anim-' . htmlspecialchars($b['img_right_anim']) : '' ?>"
                                data-lbl="Gambar Kanan"
                                style="position:absolute;object-fit:contain;
                    width:<?= $rightW ?>px;height:<?= $rightH ?>;
                    right:<?= $rightX ?>;bottom:<?= $rightY ?>;z-index:<?= $rightZ ?>;
                    <?= $b['img_right_anim'] ? animCSS_php($b['img_right_anim']) : '' ?>"
                                alt="">
                        <?php endif; ?>

                        <!-- Center — absolute, horizontal center -->
                        <div id="prevCenter"
                            style="position:absolute;left:50%;transform:translateX(-50%);
                    display:flex;flex-direction:column;align-items:center;text-align:center;
                    bottom:<?= $centerY ?>;width:<?= $centerW ?>;z-index:<?= $centerZ ?>;<?= $centerMargin ?>">

                            <?php if (($b['center_type'] ?? 'text') === 'image' && !empty($b['center_image'])): ?>
                                <img id="prevCenterImg"
                                    src="<?= htmlspecialchars($b['center_image']) ?>"
                                    class="drg <?= $b['center_img_anim'] ? 'anim-' . htmlspecialchars($b['center_img_anim']) : '' ?>"
                                    data-lbl="Gambar Tengah"
                                    style="width:<?= $cImgW ?>px;height:<?= $cImgH ?>;
                        object-fit:contain;margin-bottom:<?= $cImgMb ?>;
                        <?= $b['center_img_anim'] ? animCSS_php($b['center_img_anim']) : '' ?>"
                                    alt="">
                                <div class="rsz" data-target="center_img"></div>
                            <?php else: ?>
                                <?php if (!empty($b['title'])): ?>
                                    <div id="prevTitle"
                                        style="color:<?= $titleColor ?>;font-size:<?= $titleSize ?>;
                        font-weight:<?= $titleWeight ?>;margin-bottom:<?= $titleMb ?>;
                        line-height:1.15;letter-spacing:-.3px;text-shadow:0 1px 6px rgba(0,0,0,.25)">
                                        <?= htmlspecialchars($b['title']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($b['subtitle'])): ?>
                                    <div id="prevSub"
                                        style="color:<?= $subColor ?>;font-size:<?= $subSize ?>;
                        margin-bottom:<?= $subMb ?>;
                        opacity:.92;text-shadow:0 1px 3px rgba(0,0,0,.2);line-height:1.3">
                                        <?= htmlspecialchars($b['subtitle']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($b['btn_text']) && $btnShow): ?>
                                <a id="prevBtn"
                                    href="<?= htmlspecialchars($b['btn_href'] ?? '#') ?>"
                                    class="drg <?= $b['btn_anim'] ? 'anim-' . htmlspecialchars($b['btn_anim']) : '' ?>"
                                    data-lbl="Tombol"
                                    style="display:inline-block;
                    background:<?= $btnBg ?>;color:<?= $btnClr ?>;
                    padding:<?= $btnPad ?>;border-radius:<?= $btnRad ?>;
                    font-size:<?= $btnSz ?>;font-weight:<?= $btnWt ?>;
                    <?= $btnMarginTop ?><?= $btnMinW ?><?= $btnHStyle ?>
                    letter-spacing:.4px;text-decoration:none;cursor:pointer;border:none;
                    font-family:inherit;box-shadow:0 4px 14px rgba(0,0,0,.22);
                    <?= $b['btn_anim'] ? animCSS_php($b['btn_anim']) : '' ?>">
                                    <?= htmlspecialchars($b['btn_text']) ?>
                                </a>
                                <?php /* Canva-style resize: se corner + 4 edge handles */ ?>
                                <div class="rsz-wrap drg-rsz" id="rszBtnWrap" data-target="btn"
                                    style="display:<?= $btnShow ? 'none' : 'none' ?>;position:absolute;pointer-events:none;inset:0">
                                    <div class="rsz-h rsz-se" data-target="btn" data-dir="se"></div>
                                    <div class="rsz-h rsz-e" data-target="btn" data-dir="e"></div>
                                    <div class="rsz-h rsz-s" data-target="btn" data-dir="s"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div><!-- /prevStrip -->
                </div><!-- /prevCanvas -->
            </div>

            <!-- Ruler -->
            <div class="st-ruler">
                <span>H: <strong id="rH"><?= $h ?>px</strong></span>
                <span>Center: <strong id="rC"><?= htmlspecialchars($b['center_type'] ?? 'text') ?></strong></span>
                <span>Strip total: <strong id="rS"><?= $h + 44 ?>px</strong></span>
                <span style="margin-left:auto;display:flex;align-items:center;gap:5px">
                    <span class="st-rdot" id="rDot" style="background:<?= $b['is_active'] ? '#059669' : '#dc2626' ?>"></span>
                    <span id="rAct" style="color:<?= $b['is_active'] ? '#059669' : '#dc2626' ?>"><?= $b['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
                </span>
            </div>
        </div><!-- /st-canvas -->

        <!-- ══ RIGHT PROPS PANEL ══ -->
        <div class="st-props">
            <div class="st-ph">
                <div class="st-ph-t">Properties</div>
                <div class="st-ph-el" id="propEl">klik elemen di preview</div>
            </div>
            <div class="st-pb" id="propBody">
                <div class="st-empty">
                    <i class="ph ph-cursor-click"></i>
                    <p>Klik gambar / tombol di preview untuk edit posisi &amp; ukuran langsung.</p>
                </div>
            </div>
        </div>

    </div><!-- /st-root -->
</form>

<script>
    /* ═══════════════════════════════════════════════════════════
   HERO BANNER STUDIO — JS
   Fixes:
   1. Click-outside tidak unselect saat klik input di props panel
   2. Canva-style resize handles (se/e/s/w/n corners+edges)
   3. Center mt/mb, center_img height
   4. Btn show/hide, btn_y, btn_w/h, resize tombol
═══════════════════════════════════════════════════════════ */

    // ── Tabs & Tool strip ──────────────────────────────────────
    function swTool(name) {
        document.querySelectorAll('.st-tb').forEach(b => b.classList.remove('on'));
        document.getElementById('tb' + name)?.classList.add('on');
        showPanel('p' + name);
    }

    function showPanel(id) {
        ['pGambar', 'pKonten', 'pPosisi', 'pTombol', 'pSet'].forEach(p => {
            const el = document.getElementById(p);
            if (el) el.style.display = p === id ? '' : 'none';
        });
        document.querySelectorAll('.st-tab').forEach(t => t.classList.toggle('on', t.dataset.p === id));
    }
    document.querySelectorAll('.st-tab').forEach(t => t.addEventListener('click', () => showPanel(t.dataset.p)));

    // ── Center type ────────────────────────────────────────────
    function setCT(v) {
        document.querySelectorAll('input[name="center_type"]').forEach(r => r.checked = r.value === v);
        document.querySelectorAll('#pKonten .st-pill').forEach(p => {
            const r = p.querySelector('input[name="center_type"]');
            if (r) p.classList.toggle('on', r.checked);
        });
        document.getElementById('ctT').style.display = v === 'text' ? '' : 'none';
        document.getElementById('ctI').style.display = v === 'image' ? '' : 'none';
        markDirty();
        livePreview();
    }

    // ── Color sync ─────────────────────────────────────────────
    function syncH(sw, hId) {
        const h = document.getElementById(hId);
        if (h) h.value = sw.value;
    }

    function syncS(h, swId) {
        if (/^#[0-9a-fA-F]{6}$/.test(h.value)) {
            const s = document.getElementById(swId);
            if (s) s.value = h.value;
        }
    }

    // ── Dirty state ────────────────────────────────────────────
    let _dirty = false;

    function markDirty() {
        _dirty = true;
        document.getElementById('dirtyDot')?.classList.add('on');
        const s = document.getElementById('saveTxt');
        if (s) s.textContent = 'Belum tersimpan...';
    }
    document.getElementById('stForm').addEventListener('input', markDirty);
    document.getElementById('stForm').addEventListener('submit', () => {
        _dirty = false;
        document.getElementById('dirtyDot')?.classList.remove('on');
    });
    window.addEventListener('beforeunload', e => {
        if (_dirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // ── Field helpers ──────────────────────────────────────────
    function gv(name) {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el) return '';
        if (el.type === 'checkbox') return el.checked;
        return el.value ?? '';
    }

    function sv(name, val) {
        document.querySelectorAll(`[name="${name}"]`).forEach(el => {
            if (el.type !== 'checkbox') el.value = val;
        });
        const fi = document.getElementById('f_' + name);
        if (fi && fi.type !== 'checkbox') fi.value = val;
    }

    function parsePx(s) {
        return parseFloat(String(s || '0').replace(/[^\d.\-]/g, '')) || 0;
    }
    const eH = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    // ── Animation CSS helper ───────────────────────────────────
    const ANIM_CSS = {
        'float': 'animation:hb-float 3s ease-in-out infinite',
        'bounce': 'animation:hb-bounce 1.2s ease-in-out infinite',
        'slide-left': 'animation:hb-sliL .5s ease-out both',
        'slide-right': 'animation:hb-sliR .5s ease-out both',
        'pulse': 'animation:hb-pulse 1.5s ease-in-out infinite',
        'zoom-in': 'animation:hb-zoom .4s ease-out both',
    };
    const aC = a => ANIM_CSS[a] || '';

    function showThumb(inp, id) {
        const i = document.getElementById(id);
        if (!i) return;
        i.src = inp.value.trim();
        i.style.display = inp.value.trim() ? 'block' : 'none';
    }

    // ══════════════════════════════════════════════════════════
    // LIVE PREVIEW
    // ══════════════════════════════════════════════════════════
    function livePreview() {
        const bh = Math.max(60, parseInt(gv('height')) || 160);
        const active = gv('is_active');
        const strip = document.getElementById('prevStrip');
        if (!strip) return;
        strip.style.height = (bh + 44) + 'px';

        const inactOv = document.getElementById('prevInactiveOv');
        if (inactOv) inactOv.style.display = active ? 'none' : '';

        // ── Gambar Kiri ────────────────────────────────────────
        const iL = gv('img_left').trim();
        const lW = parseInt(gv('img_left_w')) || 90;
        const lHpx = parseInt(gv('img_left_h')) || 0;
        const lX = gv('img_left_x') || '0';
        const lY = gv('img_left_y') || '0';
        const lZ = parseInt(gv('img_left_z')) || 1;
        const lAn = gv('img_left_anim');
        let elL = document.getElementById('prevImgLeft');
        if (iL) {
            if (!elL) {
                elL = document.createElement('img');
                elL.id = 'prevImgLeft';
                elL.alt = '';
                elL.className = 'drg';
                elL.dataset.lbl = 'Gambar Kiri';
                strip.appendChild(elL);
            }
            elL.src = iL;
            elL.style.cssText = `position:absolute;object-fit:contain;display:block;
            width:${lW}px;height:${lHpx>0?lHpx+'px':'auto'};
            left:${lX};bottom:${lY};z-index:${lZ};${aC(lAn)}`;
            // Canva handles
            ensureHandles(elL, ['se', 'e', 's', 'n', 'w', 'nw', 'ne', 'sw']);
        } else if (elL) {
            elL.remove();
        }

        // ── Gambar Kanan ───────────────────────────────────────
        const iR = gv('img_right').trim();
        const rW = parseInt(gv('img_right_w')) || 90;
        const rHpx = parseInt(gv('img_right_h')) || 0;
        const rX = gv('img_right_x') || '0';
        const rY = gv('img_right_y') || '0';
        const rZ = parseInt(gv('img_right_z')) || 1;
        const rAn = gv('img_right_anim');
        let elR = document.getElementById('prevImgRight');
        if (iR) {
            if (!elR) {
                elR = document.createElement('img');
                elR.id = 'prevImgRight';
                elR.alt = '';
                elR.className = 'drg';
                elR.dataset.lbl = 'Gambar Kanan';
                strip.appendChild(elR);
            }
            elR.src = iR;
            elR.style.cssText = `position:absolute;object-fit:contain;display:block;
            width:${rW}px;height:${rHpx>0?rHpx+'px':'auto'};
            right:${rX};bottom:${rY};z-index:${rZ};${aC(rAn)}`;
            ensureHandles(elR, ['se', 'e', 's', 'n', 'w', 'nw', 'ne', 'sw']);
        } else if (elR) {
            elR.remove();
        }

        // ── Center block ───────────────────────────────────────
        const cY = gv('center_y') || '0';
        const cW = parseInt(gv('center_w')) || 0;
        const cZ = parseInt(gv('center_z')) || 2;
        const cMt = gv('center_mt') || '';
        const cMb = gv('center_mb') || '';
        const cType = document.querySelector('input[name="center_type"]:checked')?.value || 'text';

        let elC = document.getElementById('prevCenter');
        if (!elC) {
            elC = document.createElement('div');
            elC.id = 'prevCenter';
            strip.appendChild(elC);
        }
        elC.style.cssText = `position:absolute;left:50%;transform:translateX(-50%);
        display:flex;flex-direction:column;align-items:center;text-align:center;
        bottom:${cY};width:${cW>0?cW+'px':'auto'};z-index:${cZ};
        ${cMt?'margin-top:'+cMt+';':''}${cMb?'margin-bottom:'+cMb+';':''}`;

        // Build center innerHTML
        let html = '';

        if (cType === 'image') {
            const ciUrl = gv('center_image').trim();
            if (ciUrl) {
                const ciW = parseInt(gv('center_img_w')) || 160;
                const ciHpx = parseInt(gv('center_img_h')) || 0;
                const ciMb = gv('center_img_mb') || '0';
                const ciAn = gv('center_img_anim');
                html = `<img id="prevCenterImg" class="drg" data-lbl="Gambar Tengah"
                src="${eH(ciUrl)}"
                style="display:block;object-fit:contain;
                       width:${ciW}px;height:${ciHpx>0?ciHpx+'px':'auto'};
                       margin-bottom:${ciMb};${aC(ciAn)}" alt="">`;
            }
        } else {
            const ti = gv('title').trim();
            const su = gv('subtitle').trim();
            const tc = gv('title_color') || '#fff';
            const tsz = gv('title_size') || '15px';
            const twt = gv('title_weight') || '900';
            const tmb = gv('title_mb') || '2px';
            const sc = gv('subtitle_color') || '#ffffffd9';
            const ssz = gv('subtitle_size') || '10.5px';
            const smb = gv('subtitle_mb') || '10px';
            if (ti) html += `<div id="prevTitle" style="color:${eH(tc)};font-size:${tsz};
            font-weight:${twt};margin-bottom:${tmb};
            line-height:1.15;letter-spacing:-.3px;text-shadow:0 1px 6px rgba(0,0,0,.25)">
            ${eH(ti)}</div>`;
            if (su) html += `<div id="prevSub" style="color:${eH(sc)};font-size:${ssz};
            margin-bottom:${smb};opacity:.92;
            text-shadow:0 1px 3px rgba(0,0,0,.2);line-height:1.3">
            ${eH(su)}</div>`;
        }

        // Button
        const bt = gv('btn_text').trim();
        const bShow = gv('btn_show');
        if (bt && bShow) {
            const bb = gv('btn_color') || '#FFD700';
            const bfc = gv('btn_text_color') || '#000';
            const bhr = gv('btn_href') || '#';
            const bpt = gv('btn_pt') || '7px';
            const bpb = gv('btn_pb') || '7px';
            const bpl = gv('btn_pl') || '26px';
            const bpr = gv('btn_pr') || '26px';
            const brd = gv('btn_radius') || '99px';
            const bsz = gv('btn_size') || '12px';
            const bwt = gv('btn_weight') || '900';
            const ban = gv('btn_anim');
            const byV = gv('btn_y') || '';
            const bwV = gv('btn_w') || '';
            const bhV = gv('btn_h') || '';
            html += `<a id="prevBtn" class="drg" data-lbl="Tombol"
            href="${eH(bhr)}" onclick="return false"
            style="display:inline-block;
                background:${eH(bb)};color:${eH(bfc)};
                padding:${bpt} ${bpr} ${bpb} ${bpl};
                border-radius:${brd};font-size:${bsz};font-weight:${bwt};
                letter-spacing:.4px;text-decoration:none;cursor:grab;border:none;
                font-family:inherit;box-shadow:0 4px 14px rgba(0,0,0,.22);
                ${byV?'margin-top:'+byV+';':''}
                ${bwV?'min-width:'+bwV+';':''}
                ${bhV?'height:'+bhV+';':''}
                ${aC(ban)}">${eH(bt)}</a>`;
        }

        elC.innerHTML = html;

        // Attach handles AFTER innerHTML rebuild
        if (cType === 'image') {
            const ci = document.getElementById('prevCenterImg');
            if (ci) ensureHandles(ci, ['se', 'e', 's']);
        }
        const pb2 = document.getElementById('prevBtn');
        if (pb2) ensureHandles(pb2, ['se', 'e', 's', 'w']);

        // Re-init drg for newly created elements
        initDrg(strip);

        // ── Ruler ──────────────────────────────────────────────
        const elRH = document.getElementById('rH');
        if (elRH) elRH.textContent = bh + 'px';
        const elRS = document.getElementById('rS');
        if (elRS) elRS.textContent = (bh + 44) + 'px';
        const elRC = document.getElementById('rC');
        if (elRC) elRC.textContent = cType;
        const elRDt = document.getElementById('rDot');
        const elRAc = document.getElementById('rAct');
        if (elRDt) elRDt.style.background = active ? '#059669' : '#dc2626';
        if (elRAc) {
            elRAc.textContent = active ? 'Aktif' : 'Nonaktif';
            elRAc.style.color = active ? '#059669' : '#dc2626';
        }

        // Refresh props panel if something is selected
        if (_selDrg) fillProps(_selDrg);
    }

    // ══════════════════════════════════════════════════════════
    // CANVA-STYLE SELECTION & DRAG & RESIZE
    // ══════════════════════════════════════════════════════════
    let _selDrg = null;
    let _isDragging = false; // prevent click-outside while dragging

    // Per-element field map
    const ELEM_FIELDS = {
        'Gambar Kiri': {
            px: 'img_left_x',
            py: 'img_left_y',
            w: 'img_left_w',
            h: 'img_left_h',
            draggable: true,
            invertX: false
        },
        'Gambar Kanan': {
            px: 'img_right_x',
            py: 'img_right_y',
            w: 'img_right_w',
            h: 'img_right_h',
            draggable: true,
            invertX: true
        },
        'Gambar Tengah': {
            w: 'center_img_w',
            h: 'center_img_h',
            draggable: false
        },
        'Tombol': {
            w: 'btn_w',
            h: 'btn_h',
            py: 'btn_y',
            draggable: false
        },
    };

    function selDrg(drg) {
        if (_selDrg && _selDrg !== drg) _selDrg.classList.remove('sel');
        _selDrg = drg;
        drg.classList.add('sel');
        fillProps(drg);
        const pe = document.getElementById('propEl');
        if (pe) pe.textContent = drg.dataset.lbl || 'Elemen';
    }

    // ── Click-outside: ONLY deselect if click is truly outside ──
    // Key fix: also exclude clicks inside #propBody and .st-props
    document.addEventListener('mousedown', e => {
        if (_isDragging) return;
        const inDrg = e.target.closest('.drg');
        const inProps = e.target.closest('.st-props');
        const inHandle = e.target.classList.contains('rsz-h') || e.target.classList.contains('rsz');
        if (!inDrg && !inProps && !inHandle) {
            if (_selDrg) {
                _selDrg.classList.remove('sel');
                _selDrg = null;
            }
            emptyProps();
        }
    });

    // ── Ensure Canva handles exist on element ──────────────────
    const CURSOR_MAP = {
        se: 'se-resize',
        e: 'e-resize',
        s: 's-resize',
        w: 'w-resize',
        n: 'n-resize',
        nw: 'nw-resize',
        ne: 'ne-resize',
        sw: 'sw-resize'
    };
    const POS_MAP = {
        se: {
            right: '-5px',
            bottom: '-5px',
            width: '10px',
            height: '10px'
        },
        e: {
            right: '-4px',
            top: '50%',
            width: '8px',
            height: '20px',
            ty: '-50%'
        },
        s: {
            bottom: '-4px',
            left: '50%',
            width: '20px',
            height: '8px',
            tx: '-50%'
        },
        w: {
            left: '-4px',
            top: '50%',
            width: '8px',
            height: '20px',
            ty: '-50%'
        },
        n: {
            top: '-4px',
            left: '50%',
            width: '20px',
            height: '8px',
            tx: '-50%'
        },
        nw: {
            left: '-5px',
            top: '-5px',
            width: '10px',
            height: '10px'
        },
        ne: {
            right: '-5px',
            top: '-5px',
            width: '10px',
            height: '10px'
        },
        sw: {
            left: '-5px',
            bottom: '-5px',
            width: '10px',
            height: '10px'
        },
    };

    function ensureHandles(el, dirs) {
        // Remove old handles first to avoid duplicate init
        el.querySelectorAll('.rsz-h').forEach(h => h.remove());
        dirs.forEach(dir => {
            const p = POS_MAP[dir];
            if (!p) return;
            const h = document.createElement('div');
            h.className = 'rsz-h';
            h.dataset.dir = dir;
            const tx = p.tx ? `translateX(${p.tx})` : '';
            const ty = p.ty ? `translateY(${p.ty})` : '';
            const tr = (tx || ty) ? `transform:${tx} ${ty};` : '';
            h.style.cssText = `position:absolute;background:#2563eb;border:2px solid #fff;
            border-radius:${dir.length===2?'3px':'2px'};z-index:60;display:none;
            cursor:${CURSOR_MAP[dir]};pointer-events:auto;
            width:${p.width};height:${p.height};
            ${p.right  !== undefined ? 'right:'+p.right+';'   : ''}
            ${p.left   !== undefined ? 'left:'+p.left+';'     : ''}
            ${p.top    !== undefined ? 'top:'+p.top+';'       : ''}
            ${p.bottom !== undefined ? 'bottom:'+p.bottom+';' : ''}
            ${tr}`;
            el.style.position = el.style.position || 'relative'; // needed for absolute children
            el.appendChild(h);
        });
    }

    // ── Show/hide handles based on selection ──────────────────
    // Override CSS with JS so handles always react to _selDrg
    function updateHandleVisibility() {
        document.querySelectorAll('.rsz-h').forEach(h => {
            const parent = h.closest('.drg');
            h.style.display = (parent && parent === _selDrg) ? 'block' : 'none';
        });
    }

    // ── Init drag on element ───────────────────────────────────
    function initDrg(container) {
        (container || document).querySelectorAll('.drg').forEach(drg => {
            if (drg._di) return;
            drg._di = true;

            // Click to select
            drg.addEventListener('mousedown', e => {
                // Don't start drag if clicking a resize handle
                if (e.target.classList.contains('rsz-h') || e.target.classList.contains('rsz')) return;
                e.preventDefault();
                e.stopPropagation();
                selDrg(drg);
                updateHandleVisibility();

                const lbl = drg.dataset.lbl || '';
                const fm = ELEM_FIELDS[lbl];
                if (!fm || !fm.draggable) return;

                _isDragging = true;
                const sx = e.clientX,
                    sy = e.clientY;
                const startX = parsePx(gv(fm.px));
                const startY = parsePx(gv(fm.py));
                drg.style.cursor = 'grabbing';

                const onMove = ev => {
                    const dx = ev.clientX - sx;
                    const dy = ev.clientY - sy;
                    const newX = Math.round(startX + (fm.invertX ? -dx : dx));
                    const newY = Math.round(startY - dy);
                    sv(fm.px, newX + 'px');
                    sv(fm.py, newY + 'px');
                    livePreview();
                    markDirty();
                };
                const onUp = () => {
                    _isDragging = false;
                    drg.style.cursor = '';
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                };
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });

            // Init resize handles
            initHandles(drg);
        });
    }

    // ── Init resize handles on element ────────────────────────
    function initHandles(drg) {
        drg.querySelectorAll('.rsz-h').forEach(rh => {
            if (rh._ri) return;
            rh._ri = true;
            rh.addEventListener('mousedown', e => {
                e.preventDefault();
                e.stopPropagation();
                selDrg(drg);
                updateHandleVisibility();
                _isDragging = true;

                const lbl = drg.dataset.lbl || '';
                const fm = ELEM_FIELDS[lbl];
                if (!fm) {
                    _isDragging = false;
                    return;
                }

                const dir = rh.dataset.dir || 'se';
                const sx = e.clientX,
                    sy = e.clientY;
                const sw = parsePx(gv(fm.w)) || (drg.offsetWidth || 90);
                const sh = parsePx(gv(fm.h)) || (drg.offsetHeight || 40);
                const spy = fm.py ? parsePx(gv(fm.py)) : 0;

                const onMove = ev => {
                    const dx = ev.clientX - sx;
                    const dy = ev.clientY - sy;

                    if (fm.w) {
                        if (['e', 'se', 'ne'].includes(dir)) sv(fm.w, Math.max(10, Math.round(sw + dx)) + 'px');
                        if (['w', 'sw', 'nw'].includes(dir)) sv(fm.w, Math.max(10, Math.round(sw - dx)) + 'px');
                    }
                    if (fm.h) {
                        if (['s', 'se', 'sw'].includes(dir)) sv(fm.h, Math.max(0, Math.round(sh + dy)) + 'px');
                        if (['n', 'ne', 'nw'].includes(dir)) {
                            sv(fm.h, Math.max(0, Math.round(sh - dy)) + 'px');
                            if (fm.py) sv(fm.py, Math.round(spy + dy) + 'px');
                        }
                    }
                    livePreview();
                    markDirty();
                    if (_selDrg) fillProps(_selDrg);
                };
                const onUp = () => {
                    _isDragging = false;
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                };
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
        });
    }

    // ── Write field from props panel ───────────────────────────
    function writeField(inp, name) {
        // stop propagation so this doesn't trigger click-outside
        sv(name, inp.value);
        livePreview();
        markDirty();
    }

    // ── Props panel ────────────────────────────────────────────
    const PROP_MAP = {
        'Gambar Kiri': [{
                g: 'pos',
                label: 'X (kiri)',
                name: 'img_left_x',
                type: 'text'
            },
            {
                g: 'pos',
                label: 'Y (bawah)',
                name: 'img_left_y',
                type: 'text'
            },
            {
                g: 'size',
                label: 'Width (px)',
                name: 'img_left_w',
                type: 'number',
                min: 10,
                max: 600
            },
            {
                g: 'size',
                label: 'Height (px)',
                name: 'img_left_h',
                type: 'number',
                min: 0,
                max: 600,
                ph: 'auto'
            },
            {
                g: 'full',
                label: 'Z-index',
                name: 'img_left_z',
                type: 'number',
                min: 0,
                max: 99
            },
        ],
        'Gambar Kanan': [{
                g: 'pos',
                label: 'X (kanan)',
                name: 'img_right_x',
                type: 'text'
            },
            {
                g: 'pos',
                label: 'Y (bawah)',
                name: 'img_right_y',
                type: 'text'
            },
            {
                g: 'size',
                label: 'Width (px)',
                name: 'img_right_w',
                type: 'number',
                min: 10,
                max: 600
            },
            {
                g: 'size',
                label: 'Height (px)',
                name: 'img_right_h',
                type: 'number',
                min: 0,
                max: 600,
                ph: 'auto'
            },
            {
                g: 'full',
                label: 'Z-index',
                name: 'img_right_z',
                type: 'number',
                min: 0,
                max: 99
            },
        ],
        'Gambar Tengah': [{
                g: 'size',
                label: 'Width (px)',
                name: 'center_img_w',
                type: 'number',
                min: 10,
                max: 600
            },
            {
                g: 'size',
                label: 'Height (px)',
                name: 'center_img_h',
                type: 'number',
                min: 0,
                max: 600,
                ph: 'auto'
            },
            {
                g: 'full',
                label: 'Margin Bawah',
                name: 'center_img_mb',
                type: 'text'
            },
        ],
        'Tombol': [{
                g: 'size',
                label: 'Min-Width',
                name: 'btn_w',
                type: 'text',
                ph: 'auto'
            },
            {
                g: 'size',
                label: 'Height',
                name: 'btn_h',
                type: 'text',
                ph: 'auto'
            },
            {
                g: 'full',
                label: 'Margin Top',
                name: 'btn_y',
                type: 'text',
                ph: '—'
            },
            {
                g: 'pos',
                label: 'Pad T',
                name: 'btn_pt',
                type: 'text'
            },
            {
                g: 'pos',
                label: 'Pad B',
                name: 'btn_pb',
                type: 'text'
            },
            {
                g: 'pos',
                label: 'Pad L',
                name: 'btn_pl',
                type: 'text'
            },
            {
                g: 'pos',
                label: 'Pad R',
                name: 'btn_pr',
                type: 'text'
            },
            {
                g: 'full',
                label: 'Radius',
                name: 'btn_radius',
                type: 'text'
            },
        ],
    };

    function fillProps(drg) {
        const lbl = drg.dataset.lbl || '';
        const pe = document.getElementById('propEl');
        if (pe) pe.textContent = lbl;
        const pb = document.getElementById('propBody');
        if (!pb) return;
        const fields = PROP_MAP[lbl];
        if (!fields || !fields.length) {
            pb.innerHTML = '<div class="st-empty"><i class="ph ph-cursor-click"></i><p>Tidak ada properti untuk elemen ini.</p></div>';
            return;
        }

        const inp = f => {
            const val = gv(f.name) || '';
            const ext = (f.min !== undefined ? `min="${f.min}" ` : '') + (f.max !== undefined ? `max="${f.max}" ` : '') +
                (f.ph ? `placeholder="${f.ph}"` : '');
            // CRITICAL: use oninput only, no onclick — prevents click-outside from firing
            return `<div style="margin-bottom:5px">
          <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;
               color:#3a3a52;margin-bottom:2px">${f.label}</div>
          <input type="${f.type}" ${ext} value="${eH(val)}"
            style="width:100%;box-sizing:border-box;background:#080810;border:1px solid #1a1a26;
                   border-radius:4px;padding:5px 7px;color:#b0b0cc;font-size:11px;
                   font-family:'JetBrains Mono',monospace;outline:none;"
            onfocus="this.style.borderColor='#2563eb';event.stopPropagation()"
            onblur="this.style.borderColor='#1a1a26'"
            oninput="writeField(this,'${f.name}');event.stopPropagation()"
            onmousedown="event.stopPropagation()"/></div>`;
        };

        // Lay out: 'pos' = 2-col pair, 'size' = 2-col pair, 'full' = full width
        let html = `<div style="font-size:9px;font-weight:800;text-transform:uppercase;
        letter-spacing:.6px;color:#44445a;margin-bottom:10px;padding-bottom:6px;
        border-bottom:1px solid #1e1e2c">${lbl}</div>`;

        // Group consecutive same-group fields into pairs
        const rows = [];
        let i = 0;
        while (i < fields.length) {
            const f = fields[i];
            if (f.g === 'full') {
                rows.push([f]);
                i++;
            } else if (fields[i + 1] && fields[i + 1].g === f.g) {
                rows.push([f, fields[i + 1]]);
                i += 2;
            } else {
                rows.push([f]);
                i++;
            }
        }

        rows.forEach(row => {
            if (row.length === 2) {
                html += `<div style="display:grid;grid-template-columns:1fr 1fr;gap:5px;margin-bottom:0">
                ${inp(row[0])}${inp(row[1])}</div>`;
            } else {
                html += inp(row[0]);
            }
        });

        pb.innerHTML = html;
    }

    function emptyProps() {
        const pe = document.getElementById('propEl');
        if (pe) pe.textContent = 'klik elemen di preview';
        const pb = document.getElementById('propBody');
        if (pb) pb.innerHTML = `<div class="st-empty">
        <i class="ph ph-cursor-click"></i>
        <p>Klik gambar / tombol di preview untuk edit posisi &amp; ukuran langsung.</p>
    </div>`;
    }

    // ── Init ───────────────────────────────────────────────────
    window.addEventListener('load', () => {
        const ct = document.querySelector('input[name="center_type"]:checked')?.value || 'text';
        setCT(ct);
        livePreview();
        initDrg();
        document.querySelectorAll('.st-toast').forEach(t => {
            setTimeout(() => {
                t.style.transition = 'opacity .4s';
                t.style.opacity = '0';
            }, 3000);
            setTimeout(() => t.remove(), 3400);
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>