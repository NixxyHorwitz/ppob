<?php
// backoffice/dashboard_hero_banner.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Hero Banner Builder';
$active_menu = 'hero_banner';

$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

// ── ACTIONS ───────────────────────────────────────────────────

if ($action === 'add') {
    $type               = in_array($_POST['type'] ?? '', ['image_only', 'layout', 'image_center']) ? $_POST['type'] : 'layout';
    $bg_image           = trim($_POST['bg_image']           ?? '');
    $bg_color_start     = trim($_POST['bg_color_start']     ?? '#0066cc');
    $bg_color_end       = trim($_POST['bg_color_end']       ?? '#0099ff');
    $bg_gradient_angle  = (int)($_POST['bg_gradient_angle'] ?? 135);
    $height             = (int)($_POST['height']            ?? 160);
    $img_left           = trim($_POST['img_left']           ?? '');
    $img_left_width     = (int)($_POST['img_left_width']    ?? 90);
    $img_left_anim      = trim($_POST['img_left_anim']      ?? '');
    $center_type        = in_array($_POST['center_type'] ?? '', ['text', 'image']) ? $_POST['center_type'] : 'text';
    $title              = trim($_POST['title']              ?? '');
    $title_color        = trim($_POST['title_color']        ?? '#ffffff');
    $subtitle           = trim($_POST['subtitle']           ?? '');
    $subtitle_color     = trim($_POST['subtitle_color']     ?? '#ffffffd9');
    $center_image       = trim($_POST['center_image']       ?? '');
    $center_image_width = (int)($_POST['center_image_width'] ?? 160);
    $center_image_anim  = trim($_POST['center_image_anim']  ?? '');
    $btn_text           = trim($_POST['btn_text']           ?? '');
    $btn_href           = trim($_POST['btn_href']           ?? '#');
    $btn_color          = trim($_POST['btn_color']          ?? '#FFD700');
    $btn_text_color     = trim($_POST['btn_text_color']     ?? '#000000');
    $btn_anim           = trim($_POST['btn_anim']           ?? 'pulse');
    $img_right          = trim($_POST['img_right']          ?? '');
    $img_right_width    = (int)($_POST['img_right_width']   ?? 90);
    $img_right_anim     = trim($_POST['img_right_anim']     ?? '');
    $is_active          = isset($_POST['is_active']) ? 1 : 0;
    $max = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM hero_banner")->fetchColumn();
    $pdo->prepare("INSERT INTO hero_banner
    (type,bg_image,bg_color_start,bg_color_end,bg_gradient_angle,height,img_left,img_left_width,img_left_anim,
     center_type,title,title_color,subtitle,subtitle_color,center_image,center_image_width,center_image_anim,
     btn_text,btn_href,btn_color,btn_text_color,btn_anim,img_right,img_right_width,img_right_anim,sort_order,is_active)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
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
            $max + 1,
            $is_active
        ]);
    $label = $title ?: $type;
    $toast = "Banner \"$label\" berhasil ditambahkan.";
}

if ($action === 'edit' && !empty($_POST['id'])) {
    $id                 = (int)$_POST['id'];
    $type               = in_array($_POST['type'] ?? '', ['image_only', 'layout', 'image_center']) ? $_POST['type'] : 'layout';
    $bg_image           = trim($_POST['bg_image']           ?? '');
    $bg_color_start     = trim($_POST['bg_color_start']     ?? '#0066cc');
    $bg_color_end       = trim($_POST['bg_color_end']       ?? '#0099ff');
    $bg_gradient_angle  = (int)($_POST['bg_gradient_angle'] ?? 135);
    $height             = (int)($_POST['height']            ?? 160);
    $img_left           = trim($_POST['img_left']           ?? '');
    $img_left_width     = (int)($_POST['img_left_width']    ?? 90);
    $img_left_anim      = trim($_POST['img_left_anim']      ?? '');
    $center_type        = in_array($_POST['center_type'] ?? '', ['text', 'image']) ? $_POST['center_type'] : 'text';
    $title              = trim($_POST['title']              ?? '');
    $title_color        = trim($_POST['title_color']        ?? '#ffffff');
    $subtitle           = trim($_POST['subtitle']           ?? '');
    $subtitle_color     = trim($_POST['subtitle_color']     ?? '#ffffffd9');
    $center_image       = trim($_POST['center_image']       ?? '');
    $center_image_width = (int)($_POST['center_image_width'] ?? 160);
    $center_image_anim  = trim($_POST['center_image_anim']  ?? '');
    $btn_text           = trim($_POST['btn_text']           ?? '');
    $btn_href           = trim($_POST['btn_href']           ?? '#');
    $btn_color          = trim($_POST['btn_color']          ?? '#FFD700');
    $btn_text_color     = trim($_POST['btn_text_color']     ?? '#000000');
    $btn_anim           = trim($_POST['btn_anim']           ?? 'pulse');
    $img_right          = trim($_POST['img_right']          ?? '');
    $img_right_width    = (int)($_POST['img_right_width']   ?? 90);
    $img_right_anim     = trim($_POST['img_right_anim']     ?? '');
    $is_active          = isset($_POST['is_active']) ? 1 : 0;
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
            $id
        ]);
    $label = $title ?: $type;
    $toast = "Banner \"$label\" berhasil diperbarui.";
}

if ($action === 'toggle' && !empty($_POST['id']))
    $pdo->prepare("UPDATE hero_banner SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]);

if ($action === 'reorder' && !empty($_POST['order'])) {
    $ids = json_decode($_POST['order'], true);
    if (is_array($ids)) {
        $st = $pdo->prepare("UPDATE hero_banner SET sort_order=? WHERE id=?");
        foreach ($ids as $i => $id) $st->execute([$i + 1, (int)$id]);
        echo json_encode(['ok' => true]);
        exit;
    }
}

if ($action === 'delete' && !empty($_POST['id'])) {
    $r = $pdo->prepare("SELECT title FROM hero_banner WHERE id=?");
    $r->execute([(int)$_POST['id']]);
    $del_name = $r->fetchColumn() ?: 'Banner #' . $_POST['id'];
    $pdo->prepare("DELETE FROM hero_banner WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = "Banner \"$del_name\" berhasil dihapus.";
}

$banners = $pdo->query("SELECT * FROM hero_banner ORDER BY sort_order ASC, id ASC")->fetchAll();
$total   = count($banners);
$aktif   = count(array_filter($banners, fn($b) => $b['is_active']));

$edit_data = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare("SELECT * FROM hero_banner WHERE id=?");
    $es->execute([(int)$_GET['edit']]);
    $edit_data = $es->fetch();
}

// ── Helpers ──────────────────────────────────────────────────
$ANIM_OPTIONS = ['none' => 'Tidak ada', 'float' => 'Float ↕', 'bounce' => 'Bounce ↑', 'slide-left' => 'Slide Kiri', 'slide-right' => 'Slide Kanan', 'pulse' => 'Pulse', 'zoom-in' => 'Zoom In'];
$TYPE_LABELS  = ['layout' => ['ph-layout', 'Layout (Kiri+Tengah+Kanan)'], 'image_only' => ['ph-image', 'Full Background Image'], 'image_center' => ['ph-frame-corners', 'Gambar Tengah']];

// ── Banner preview card ───────────────────────────────────────
function hb_card(array $b): string
{
    global $TYPE_LABELS;
    $id    = (int)$b['id'];
    $title = htmlspecialchars($b['title'] ?? '(No title)');
    $type  = htmlspecialchars($b['type']);
    $sort  = (int)$b['sort_order'];
    $op    = $b['is_active'] ? '1' : '.45';
    $type_label = $TYPE_LABELS[$b['type']][1] ?? $type;
    $type_icon  = $TYPE_LABELS[$b['type']][0] ?? 'ph-image';

    // Mini gradient preview swatch
    $grad = "linear-gradient({$b['bg_gradient_angle']}deg,{$b['bg_color_start']},{$b['bg_color_end']})";
    $bg_preview = !empty($b['bg_image'])
        ? "background:url(" . htmlspecialchars($b['bg_image']) . ") center/cover"
        : "background:{$grad}";

    ob_start(); ?>
    <div data-id="<?= $id ?>" style="display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:10px 12px;transition:border-color .2s,box-shadow .2s;opacity:<?= $op ?>">

        <!-- Drag handle -->
        <div class="hb-drag-handle" title="Drag untuk reorder"
            style="color:var(--mut);font-size:20px;cursor:grab;flex-shrink:0;padding:4px 2px;touch-action:none;user-select:none">
            <i class="ph ph-dots-six-vertical"></i>
        </div>

        <!-- Mini banner preview -->
        <div style="width:72px;height:40px;border-radius:8px;overflow:hidden;flex-shrink:0;position:relative;border:1px solid rgba(255,255,255,.08);<?= $bg_preview ?>">
            <?php if (!empty($b['title'])): ?>
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:7px;font-weight:800;color:<?= htmlspecialchars($b['title_color'] ?? '#fff') ?>;text-shadow:0 1px 3px rgba(0,0,0,.5);padding:2px;text-align:center;line-height:1.2">
                    <?= mb_strimwidth(htmlspecialchars($b['title']), 0, 18, '…') ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div style="flex:1;min-width:0">
            <div style="font-size:13.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $b['title'] ? $title : '<span style="color:var(--mut);font-style:italic">(no title)</span>' ?></div>
            <div style="font-size:11px;color:var(--mut);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars($b['subtitle'] ?? '') ?>
            </div>
            <div style="display:flex;gap:4px;margin-top:5px;flex-wrap:wrap">
                <?php if ($b['is_active']): ?>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(16,185,129,.12);color:#10b981;display:inline-flex;align-items:center;gap:3px"><i class="ph ph-circle-fill" style="font-size:5px"></i>Aktif</span>
                <?php else: ?>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(255,255,255,.06);color:#4b5e7a;display:inline-flex;align-items:center;gap:3px"><i class="ph ph-circle" style="font-size:5px"></i>Nonaktif</span>
                <?php endif; ?>
                <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(255,255,255,.06);color:var(--sub);display:inline-flex;align-items:center;gap:3px"><i class="ph <?= $type_icon ?>"></i><?= $type_label ?></span>
                <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(255,255,255,.05);color:#4b5e7a;font-family:'JetBrains Mono',monospace"><?= $b['height'] ?>px</span>
                <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(255,255,255,.05);color:#4b5e7a;font-family:'JetBrains Mono',monospace">#<?= $sort ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:4px;flex-shrink:0">
            <!-- Toggle aktif -->
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="toggle" />
                <input type="hidden" name="id" value="<?= $id ?>" />
                <button type="submit" title="<?= $b['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"
                    style="width:28px;height:28px;border-radius:7px;border:none;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;<?= $b['is_active'] ? 'background:rgba(16,185,129,.12);color:#10b981' : 'background:rgba(255,255,255,.06);color:#4b5e7a' ?>">
                    <i class="ph <?= $b['is_active'] ? 'ph-eye' : 'ph-eye-slash' ?>"></i>
                </button>
            </form>
            <!-- Edit -->
            <a href="?edit=<?= $id ?>" title="Edit"
                style="width:28px;height:28px;border-radius:7px;background:rgba(255,255,255,.06);color:var(--sub);display:flex;align-items:center;justify-content:center;font-size:14px;text-decoration:none">
                <i class="ph ph-pencil-simple"></i>
            </a>
            <!-- Hapus -->
            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus banner <?= addslashes($title) ?>?')">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?= $id ?>" />
                <button type="submit" title="Hapus"
                    style="width:28px;height:28px;border-radius:7px;background:rgba(239,68,68,.12);color:#ef4444;border:none;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer">
                    <i class="ph ph-trash"></i>
                </button>
            </form>
        </div>
    </div>
<?php return ob_get_clean();
}

// ── Form fields for modal ─────────────────────────────────────
function hb_form_fields(string $mid, array $fd): void
{
    global $ANIM_OPTIONS, $TYPE_LABELS;
    $sel_type  = $fd['type']        ?? 'layout';
    $sel_ctype = $fd['center_type'] ?? 'text';
    $is_active = (!isset($fd['is_active']) || $fd['is_active']) ? 'checked' : '';
    $act_bg    = $is_active ? '#3b82f6' : 'rgba(255,255,255,.12)';
    $act_left  = $is_active ? '19px' : '3px';
    $label_style = "font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);display:block;margin-bottom:6px";
    $fi = "class=\"fi w-100\" style=\"padding-left:13px\"";

    function anim_select(string $name, string $sel, string $id = ''): string
    {
        global $ANIM_OPTIONS;
        $out = "<select name=\"$name\"" . ($id ? " id=\"$id\"" : '') . " class=\"fi w-100\" style=\"padding-left:13px\">";
        foreach ($ANIM_OPTIONS as $v => $l) {
            $out .= "<option value=\"$v\"" . ($sel === $v ? ' selected' : '') . ">$l</option>";
        }
        return $out . "</select>";
    }
?>
    <!-- Banner Type -->
    <div class="mb-3">
        <label style="<?= $label_style ?>">Tipe Banner *</label>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach ($TYPE_LABELS as $v => [$ic, $lb]): ?>
                <label id="<?= $mid ?>_ttlbl_<?= $v ?>"
                    style="flex:1;min-width:120px;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid <?= $sel_type === $v ? '#3b82f6' : 'var(--border)' ?>;background:<?= $sel_type === $v ? 'rgba(59,130,246,.15)' : 'var(--hover)' ?>;color:<?= $sel_type === $v ? '#3b82f6' : 'var(--sub)' ?>"
                    for="<?= $mid ?>_tt_<?= $v ?>">
                    <input type="radio" name="type" value="<?= $v ?>" id="<?= $mid ?>_tt_<?= $v ?>"
                        <?= $sel_type === $v ? 'checked' : '' ?> class="d-none"
                        onchange="hbTypeChange('<?= $mid ?>')" />
                    <i class="ph <?= $ic ?>"></i> <?= $lb ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- ── LEFT COLUMN ── -->
        <div class="col-md-7">

            <!-- Background -->
            <div style="background:var(--hover);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:16px">
                <div style="font-size:12px;font-weight:700;color:var(--sub);margin-bottom:12px;display:flex;align-items:center;gap:6px"><i class="ph ph-paint-bucket" style="color:#3b82f6"></i>Background</div>

                <div class="mb-3">
                    <label style="<?= $label_style ?>">URL Background Image <span style="font-weight:400;text-transform:none;color:var(--mut)">(opsional, override gradient)</span></label>
                    <input type="text" name="bg_image" <?= $fi ?> placeholder="https://…/banner-bg.jpg"
                        value="<?= htmlspecialchars($fd['bg_image'] ?? '') ?>"
                        oninput="hbUpdatePreview('<?= $mid ?>')" />
                </div>

                <div class="row g-2 align-items-end">
                    <div class="col-4">
                        <label style="<?= $label_style ?>">Warna Awal</label>
                        <div style="display:flex;gap:6px;align-items:center">
                            <input type="color" name="bg_color_start" value="<?= htmlspecialchars($fd['bg_color_start'] ?? '#0066cc') ?>"
                                style="width:36px;height:36px;border-radius:8px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer;flex-shrink:0"
                                oninput="hbUpdatePreview('<?= $mid ?>')" />
                            <input type="text" class="fi" value="<?= htmlspecialchars($fd['bg_color_start'] ?? '#0066cc') ?>" maxlength="7" style="padding-left:8px;font-family:'JetBrains Mono',monospace;font-size:11px"
                                oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){this.previousElementSibling.value=this.value;hbUpdatePreview('<?= $mid ?>')}" />
                        </div>
                    </div>
                    <div class="col-4">
                        <label style="<?= $label_style ?>">Warna Akhir</label>
                        <div style="display:flex;gap:6px;align-items:center">
                            <input type="color" name="bg_color_end" value="<?= htmlspecialchars($fd['bg_color_end'] ?? '#0099ff') ?>"
                                style="width:36px;height:36px;border-radius:8px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer;flex-shrink:0"
                                oninput="hbUpdatePreview('<?= $mid ?>')" />
                            <input type="text" class="fi" value="<?= htmlspecialchars($fd['bg_color_end'] ?? '#0099ff') ?>" maxlength="7" style="padding-left:8px;font-family:'JetBrains Mono',monospace;font-size:11px"
                                oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){this.previousElementSibling.value=this.value;hbUpdatePreview('<?= $mid ?>')}" />
                        </div>
                    </div>
                    <div class="col-4">
                        <label style="<?= $label_style ?>">Sudut Gradient (°)</label>
                        <input type="number" name="bg_gradient_angle" class="fi w-100" style="padding-left:13px" min="0" max="360"
                            value="<?= (int)($fd['bg_gradient_angle'] ?? 135) ?>"
                            oninput="hbUpdatePreview('<?= $mid ?>')" />
                    </div>
                </div>

                <div class="mt-2">
                    <label style="<?= $label_style ?>">Tinggi Banner (px)</label>
                    <input type="number" name="height" class="fi" style="padding-left:13px;width:120px" min="60" max="400"
                        value="<?= (int)($fd['height'] ?? 160) ?>"
                        oninput="hbUpdatePreview('<?= $mid ?>')" />
                </div>
            </div>

            <!-- Left Image (shown for layout type) -->
            <div id="<?= $mid ?>_secLeft" style="background:var(--hover);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:16px;<?= $sel_type !== 'layout' ? 'display:none' : '' ?>">
                <div style="font-size:12px;font-weight:700;color:var(--sub);margin-bottom:12px;display:flex;align-items:center;gap:6px"><i class="ph ph-align-left" style="color:#a855f7"></i>Gambar Kiri</div>
                <div class="mb-2">
                    <label style="<?= $label_style ?>">URL Gambar Kiri</label>
                    <input type="text" name="img_left" <?= $fi ?> placeholder="https://…/left-image.png"
                        value="<?= htmlspecialchars($fd['img_left'] ?? '') ?>"
                        oninput="hbUpdatePreview('<?= $mid ?>')" />
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label style="<?= $label_style ?>">Lebar (px)</label>
                        <input type="number" name="img_left_width" class="fi w-100" style="padding-left:13px" min="20" max="300"
                            value="<?= (int)($fd['img_left_width'] ?? 90) ?>" />
                    </div>
                    <div class="col-6">
                        <label style="<?= $label_style ?>">Animasi</label>
                        <?= anim_select('img_left_anim', $fd['img_left_anim'] ?? '') ?>
                    </div>
                </div>
            </div>

            <!-- Center -->
            <div id="<?= $mid ?>_secCenter" style="background:var(--hover);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:16px">
                <div style="font-size:12px;font-weight:700;color:var(--sub);margin-bottom:12px;display:flex;align-items:center;gap:6px"><i class="ph ph-align-center-horizontal" style="color:#3b82f6"></i>Tengah</div>

                <!-- Center type toggle (only for layout & image_center) -->
                <div id="<?= $mid ?>_ctypeRow" class="mb-3" style="<?= $sel_type === 'image_only' ? 'display:none' : '' ?>">
                    <label style="<?= $label_style ?>">Isi Tengah</label>
                    <div class="d-flex gap-2">
                        <?php foreach (['text' => ['ph-text-aa', 'Teks & Tombol'], 'image' => ['ph-image-square', 'Gambar']] as $v => [$ic, $lb]): ?>
                            <label id="<?= $mid ?>_ctlbl_<?= $v ?>"
                                style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:7px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid <?= $sel_ctype === $v ? '#3b82f6' : 'var(--border)' ?>;background:<?= $sel_ctype === $v ? 'rgba(59,130,246,.15)' : 'var(--card)' ?>;color:<?= $sel_ctype === $v ? '#3b82f6' : 'var(--sub)' ?>"
                                for="<?= $mid ?>_ct_<?= $v ?>">
                                <input type="radio" name="center_type" value="<?= $v ?>" id="<?= $mid ?>_ct_<?= $v ?>"
                                    <?= $sel_ctype === $v ? 'checked' : '' ?> class="d-none"
                                    onchange="hbCenterTypeChange('<?= $mid ?>')" />
                                <i class="ph <?= $ic ?>"></i> <?= $lb ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Text fields -->
                <div id="<?= $mid ?>_textFields" style="<?= ($sel_ctype === 'image' && $sel_type !== 'image_only') ? 'display:none' : '' ?>">
                    <div class="mb-2">
                        <label style="<?= $label_style ?>">Judul (Title)</label>
                        <div style="display:flex;gap:6px;align-items:center">
                            <input type="color" name="title_color" value="<?= htmlspecialchars($fd['title_color'] ?? '#ffffff') ?>"
                                style="width:32px;height:32px;border-radius:6px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer;flex-shrink:0"
                                oninput="hbUpdatePreview('<?= $mid ?>')" />
                            <input type="text" name="title" <?= $fi ?> maxlength="200" placeholder="KLAIM HADIAH"
                                value="<?= htmlspecialchars($fd['title'] ?? '') ?>"
                                oninput="hbUpdatePreview('<?= $mid ?>')" />
                        </div>
                    </div>
                    <div class="mb-2">
                        <label style="<?= $label_style ?>">Subtitle</label>
                        <div style="display:flex;gap:6px;align-items:center">
                            <input type="color" name="subtitle_color" value="<?= htmlspecialchars($fd['subtitle_color'] ?? '#ffffffd9') ?>"
                                style="width:32px;height:32px;border-radius:6px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer;flex-shrink:0"
                                oninput="hbUpdatePreview('<?= $mid ?>')" />
                            <input type="text" name="subtitle" <?= $fi ?> maxlength="300" placeholder="& Jutaan Rupiah"
                                value="<?= htmlspecialchars($fd['subtitle'] ?? '') ?>"
                                oninput="hbUpdatePreview('<?= $mid ?>')" />
                        </div>
                    </div>

                    <!-- Button -->
                    <div style="background:var(--card);border:1px solid var(--border);border-radius:8px;padding:10px;margin-top:10px">
                        <div style="font-size:11px;font-weight:700;color:var(--mut);margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">Tombol (opsional)</div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label style="<?= $label_style ?>">Teks Tombol</label>
                                <input type="text" name="btn_text" <?= $fi ?> maxlength="80" placeholder="SERBU"
                                    value="<?= htmlspecialchars($fd['btn_text'] ?? '') ?>"
                                    oninput="hbUpdatePreview('<?= $mid ?>')" />
                            </div>
                            <div class="col-6">
                                <label style="<?= $label_style ?>">Link Tombol</label>
                                <input type="text" name="btn_href" <?= $fi ?> maxlength="255" placeholder="#"
                                    value="<?= htmlspecialchars($fd['btn_href'] ?? '#') ?>" />
                            </div>
                            <div class="col-4">
                                <label style="<?= $label_style ?>">Warna BG</label>
                                <input type="color" name="btn_color" value="<?= htmlspecialchars($fd['btn_color'] ?? '#FFD700') ?>"
                                    style="width:100%;height:32px;border-radius:6px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer"
                                    oninput="hbUpdatePreview('<?= $mid ?>')" />
                            </div>
                            <div class="col-4">
                                <label style="<?= $label_style ?>">Warna Teks</label>
                                <input type="color" name="btn_text_color" value="<?= htmlspecialchars($fd['btn_text_color'] ?? '#000000') ?>"
                                    style="width:100%;height:32px;border-radius:6px;border:1px solid var(--border);padding:2px;background:var(--hover);cursor:pointer"
                                    oninput="hbUpdatePreview('<?= $mid ?>')" />
                            </div>
                            <div class="col-4">
                                <label style="<?= $label_style ?>">Animasi</label>
                                <?= anim_select('btn_anim', $fd['btn_anim'] ?? 'pulse') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Center image fields -->
                <div id="<?= $mid ?>_imgFields" style="<?= ($sel_ctype !== 'image' && $sel_type !== 'image_center') ? 'display:none' : '' ?>">
                    <div class="mb-2">
                        <label style="<?= $label_style ?>">URL Gambar Tengah</label>
                        <input type="text" name="center_image" <?= $fi ?> placeholder="https://…/center.png"
                            value="<?= htmlspecialchars($fd['center_image'] ?? '') ?>"
                            oninput="hbUpdatePreview('<?= $mid ?>')" />
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label style="<?= $label_style ?>">Lebar (px)</label>
                            <input type="number" name="center_image_width" class="fi w-100" style="padding-left:13px" min="20" max="600"
                                value="<?= (int)($fd['center_image_width'] ?? 160) ?>" />
                        </div>
                        <div class="col-6">
                            <label style="<?= $label_style ?>">Animasi</label>
                            <?= anim_select('center_image_anim', $fd['center_image_anim'] ?? '') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Image (layout only) -->
            <div id="<?= $mid ?>_secRight" style="background:var(--hover);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:16px;<?= $sel_type !== 'layout' ? 'display:none' : '' ?>">
                <div style="font-size:12px;font-weight:700;color:var(--sub);margin-bottom:12px;display:flex;align-items:center;gap:6px"><i class="ph ph-align-right" style="color:#f59e0b"></i>Gambar Kanan</div>
                <div class="mb-2">
                    <label style="<?= $label_style ?>">URL Gambar Kanan</label>
                    <input type="text" name="img_right" <?= $fi ?> placeholder="https://…/right-image.png"
                        value="<?= htmlspecialchars($fd['img_right'] ?? '') ?>"
                        oninput="hbUpdatePreview('<?= $mid ?>')" />
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label style="<?= $label_style ?>">Lebar (px)</label>
                        <input type="number" name="img_right_width" class="fi w-100" style="padding-left:13px" min="20" max="300"
                            value="<?= (int)($fd['img_right_width'] ?? 90) ?>" />
                    </div>
                    <div class="col-6">
                        <label style="<?= $label_style ?>">Animasi</label>
                        <?= anim_select('img_right_anim', $fd['img_right_anim'] ?? '') ?>
                    </div>
                </div>
            </div>

        </div>
        <!-- ── RIGHT COLUMN (Preview + Toggle) ── -->
        <div class="col-md-5">
            <label style="<?= $label_style ?>">Preview Banner</label>
            <div id="<?= $mid ?>_prevWrap" style="border-radius:12px;overflow:hidden;border:1px solid var(--border);position:relative;min-height:80px;background:linear-gradient(<?= $fd['bg_gradient_angle'] ?? 135 ?>deg,<?= $fd['bg_color_start'] ?? '#0066cc' ?>,<?= $fd['bg_color_end'] ?? '#0099ff' ?>)">
                <div id="<?= $mid ?>_prevInner" style="display:flex;align-items:center;justify-content:space-between;padding:10px;min-height:60px;gap:6px">
                    <div id="<?= $mid ?>_prevLeft" style="flex-shrink:0"></div>
                    <div id="<?= $mid ?>_prevCenter" style="flex:1;text-align:center">
                        <div id="<?= $mid ?>_prevTitle" style="font-size:13px;font-weight:800;color:<?= $fd['title_color'] ?? '#fff' ?>;text-shadow:0 1px 4px rgba(0,0,0,.4)"><?= htmlspecialchars($fd['title'] ?? '') ?></div>
                        <div id="<?= $mid ?>_prevSub" style="font-size:10px;color:<?= $fd['subtitle_color'] ?? '#ffffffd9' ?>;margin-top:2px"><?= htmlspecialchars($fd['subtitle'] ?? '') ?></div>
                        <?php if (!empty($fd['btn_text'])): ?>
                            <div id="<?= $mid ?>_prevBtn" style="display:inline-block;margin-top:6px;padding:3px 12px;border-radius:99px;font-size:9px;font-weight:800;background:<?= $fd['btn_color'] ?? '#FFD700' ?>;color:<?= $fd['btn_text_color'] ?? '#000' ?>"><?= htmlspecialchars($fd['btn_text']) ?></div>
                        <?php else: ?>
                            <div id="<?= $mid ?>_prevBtn" style="display:none;margin-top:6px;padding:3px 12px;border-radius:99px;font-size:9px;font-weight:800"></div>
                        <?php endif; ?>
                    </div>
                    <div id="<?= $mid ?>_prevRight" style="flex-shrink:0"></div>
                </div>
            </div>
            <div style="font-size:10px;color:var(--mut);text-align:center;margin-top:5px;margin-bottom:14px">Live Preview</div>

            <!-- Active toggle -->
            <div style="background:var(--hover);border:1px solid var(--border);border-radius:10px;overflow:hidden">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px">
                    <div>
                        <div style="font-size:13px;font-weight:600">Aktifkan Banner</div>
                        <div style="font-size:11px;color:var(--mut)">Tampilkan di halaman user</div>
                    </div>
                    <label style="position:relative;display:inline-block;width:38px;height:22px;flex-shrink:0;cursor:pointer">
                        <input type="checkbox" name="is_active" <?= $is_active ?>
                            style="position:absolute;opacity:0;width:0;height:0"
                            onchange="menusSw(this)" />
                        <span style="position:absolute;inset:0;border-radius:99px;background:<?= $act_bg ?>;transition:background .2s">
                            <span style="position:absolute;top:3px;left:<?= $act_left ?>;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .2s;display:block"></span>
                        </span>
                    </label>
                </div>
            </div>

            <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:10px 12px;font-size:11.5px;color:var(--sub);display:flex;align-items:flex-start;gap:6px;line-height:1.5;margin-top:14px">
                <i class="ph ph-lightbulb" style="color:#f59e0b;flex-shrink:0;margin-top:1px"></i>
                <span><strong>Tips:</strong> Gunakan gambar dengan rasio 1:1 atau transparan (PNG/WebP). Tinggi banner ideal 140–180px.</span>
            </div>
        </div>
    </div>
<?php
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
        <h1>Hero Banner Builder</h1>
        <nav>
            <ol class="breadcrumb bc">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Hero Banner</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span style="font-size:12px;color:var(--mut)"><i class="ph ph-arrows-out-cardinal me-1"></i>Drag untuk ubah urutan</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddBanner" style="border-radius:8px">
            <i class="ph ph-plus me-1"></i>Tambah Banner
        </button>
    </div>
</div>

<!-- SUMMARY BAR -->
<div style="display:flex;align-items:center;background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px">
    <?php $sbitems = [
        [$total, 'Total Banner', ''],
        [$aktif, 'Aktif', 'color:#10b981'],
        [$total - $aktif, 'Nonaktif', 'color:var(--mut)'],
        [count(array_filter($banners, fn($b) => $b['type'] === 'layout')), 'Layout', 'color:#3b82f6'],
    ];
    foreach ($sbitems as $i => [$v, $l, $c]): ?>
        <?php if ($i): ?><div style="width:1px;background:var(--border);align-self:stretch"></div><?php endif; ?>
        <div style="flex:1;padding:14px 20px;text-align:center">
            <div style="font-size:24px;font-weight:700;font-family:'JetBrains Mono',monospace;line-height:1;<?= $c ?>"><?= $v ?></div>
            <div style="font-size:11px;color:var(--mut);font-weight:600;margin-top:3px"><?= $l ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- PREVIEW STRIP (active banners visual preview) -->
<?php if (!empty(array_filter($banners, fn($b) => $b['is_active']))): ?>
    <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px">
        <div style="font-size:12px;font-weight:700;color:var(--sub);padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:6px">
            <i class="ph ph-device-mobile"></i>Preview Tampilan App
            <span style="font-size:10px;color:var(--mut);font-weight:400;margin-left:4px">Hanya banner aktif</span>
        </div>
        <div style="padding:14px 16px;display:flex;flex-direction:column;gap:8px">
            <?php foreach (array_filter($banners, fn($b) => $b['is_active']) as $b):
                $grad = "linear-gradient({$b['bg_gradient_angle']}deg,{$b['bg_color_start']},{$b['bg_color_end']})";
                $bg_s = !empty($b['bg_image']) ? "background:url(" . htmlspecialchars($b['bg_image']) . ") center/cover no-repeat" : "background:{$grad}";
                $h = min((int)$b['height'], 80); // cap at 80px for preview
            ?>
                <div style="border-radius:10px;overflow:hidden;<?= $bg_s ?>;height:<?= $h ?>px;display:flex;align-items:center;justify-content:space-between;padding:0 10px;gap:6px;position:relative">
                    <?php if (!empty($b['img_left'])): ?>
                        <img src="<?= htmlspecialchars($b['img_left']) ?>" style="height:<?= $h * .8 ?>px;max-width:<?= (int)$b['img_left_width'] ?>px;object-fit:contain;flex-shrink:0" alt="" />
                    <?php endif; ?>
                    <div style="flex:1;text-align:center">
                        <?php if (!empty($b['title'])): ?><div style="font-size:11px;font-weight:800;color:<?= htmlspecialchars($b['title_color']) ?>;text-shadow:0 1px 4px rgba(0,0,0,.5)"><?= htmlspecialchars($b['title']) ?></div><?php endif; ?>
                        <?php if (!empty($b['subtitle'])): ?><div style="font-size:9px;color:<?= htmlspecialchars($b['subtitle_color']) ?>"><?= htmlspecialchars($b['subtitle']) ?></div><?php endif; ?>
                        <?php if (!empty($b['btn_text'])): ?><div style="display:inline-block;margin-top:4px;padding:2px 10px;border-radius:99px;font-size:8px;font-weight:800;background:<?= htmlspecialchars($b['btn_color']) ?>;color:<?= htmlspecialchars($b['btn_text_color']) ?>"><?= htmlspecialchars($b['btn_text']) ?></div><?php endif; ?>
                    </div>
                    <?php if (!empty($b['img_right'])): ?>
                        <img src="<?= htmlspecialchars($b['img_right']) ?>" style="height:<?= $h * .8 ?>px;max-width:<?= (int)$b['img_right_width'] ?>px;object-fit:contain;flex-shrink:0" alt="" />
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- BANNER LIST -->
<div id="hbList" style="display:flex;flex-direction:column;gap:8px;min-height:80px">
    <?php if (empty($banners)): ?>
        <div style="display:flex;flex-direction:column;align-items:center;padding:48px;border:2px dashed var(--border);border-radius:12px;color:var(--mut);font-size:12px;gap:10px">
            <i class="ph ph-image-square" style="font-size:36px;opacity:.3"></i>
            <div>Belum ada banner. Klik <strong>Tambah Banner</strong> untuk memulai.</div>
        </div>
    <?php else:
        foreach ($banners as $b) echo hb_card($b);
    endif; ?>
</div>

<!-- FLOATING SAVE ORDER -->
<button id="hbSaveBtn" onclick="hbSaveOrder()"
    style="position:fixed;bottom:28px;right:28px;z-index:1050;padding:10px 20px;border-radius:10px;background:#3b82f6;color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 20px rgba(59,130,246,.5);align-items:center;gap:8px;display:none">
    <i class="ph ph-floppy-disk"></i> Simpan Urutan
</button>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalAddBanner" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="background:var(--card) !important;border:1px solid var(--border) !important;border-radius:16px !important;box-shadow:0 25px 60px rgba(0,0,0,.6) !important">
            <div class="modal-header" style="border-bottom:1px solid var(--border) !important;padding:16px 22px;background:transparent !important">
                <h5 class="modal-title" style="font-size:15px;font-weight:700">
                    <i class="ph ph-plus-circle me-2" style="color:#3b82f6"></i>Tambah Hero Banner
                </h5>
                <button type="button" class="btn-close" style="filter:invert(1);opacity:.7" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add" />
                <div class="modal-body" style="padding:22px;background:transparent !important;max-height:80vh;overflow-y:auto">
                    <?php hb_form_fields('hbAdd', []); ?>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border) !important;padding:14px 22px;background:transparent !important">
                    <button type="button" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px"><i class="ph ph-plus me-1"></i>Tambahkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<?php if ($edit_data): ?>
    <div class="modal fade" id="modalEditBanner" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content" style="background:var(--card) !important;border:1px solid var(--border) !important;border-radius:16px !important;box-shadow:0 25px 60px rgba(0,0,0,.6) !important">
                <div class="modal-header" style="border-bottom:1px solid var(--border) !important;padding:16px 22px;background:transparent !important">
                    <h5 class="modal-title" style="font-size:15px;font-weight:700">
                        <i class="ph ph-pencil-simple me-2" style="color:#3b82f6"></i>Edit Hero Banner
                    </h5>
                    <button type="button" class="btn-close" style="filter:invert(1);opacity:.7" data-bs-dismiss="modal" onclick="window.location='dashboard_hero_banner.php'"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit" />
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>" />
                    <div class="modal-body" style="padding:22px;background:transparent !important;max-height:80vh;overflow-y:auto">
                        <?php hb_form_fields('hbEdit', $edit_data); ?>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--border) !important;padding:14px 22px;background:transparent !important">
                        <a href="dashboard_hero_banner.php" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">Batal</a>
                        <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px"><i class="ph ph-floppy-disk me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .hb-drag-handle:active {
        cursor: grabbing !important;
    }

    #hbList>div.sortable-ghost {
        opacity: .3 !important;
        border: 2px dashed #3b82f6 !important;
    }

    #hbList>div.sortable-drag {
        box-shadow: 0 8px 24px rgba(0, 0, 0, .4) !important;
        border-color: #3b82f6 !important;
    }

    #hbSaveBtn.hb-show {
        display: flex !important;
    }
</style>

<?php
$open_edit_js = $edit_data ? "new bootstrap.Modal(document.getElementById('modalEditBanner')).show();" : '';
$page_scripts = <<<SCRIPT
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
{$open_edit_js}

// Sortable
const hbListEl = document.getElementById('hbList');
if (hbListEl) {
  Sortable.create(hbListEl, {
    handle: '.hb-drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    dragClass: 'sortable-drag',
    onEnd() { document.getElementById('hbSaveBtn').classList.add('hb-show'); }
  });
}

function hbSaveOrder() {
  const ids = [...document.querySelectorAll('#hbList > [data-id]')].map(el => el.dataset.id);
  const fd = new FormData();
  fd.append('action', 'reorder');
  fd.append('order', JSON.stringify(ids));
  fetch('dashboard_hero_banner.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(() => {
      document.getElementById('hbSaveBtn').classList.remove('hb-show');
      hbToast('Urutan banner berhasil disimpan!');
    });
}

// Banner type change → show/hide sections
function hbTypeChange(mid) {
  const modalEl = document.getElementById(mid === 'hbAdd' ? 'modalAddBanner' : 'modalEditBanner');
  const type = modalEl?.querySelector('input[name="type"]:checked')?.value || 'layout';

  ['layout','image_only','image_center'].forEach(v => {
    const lbl = document.getElementById(`\${mid}_ttlbl_\${v}`);
    if (!lbl) return;
    const on = v === type;
    lbl.style.borderColor = on ? '#3b82f6' : 'var(--border)';
    lbl.style.background  = on ? 'rgba(59,130,246,.15)' : 'var(--hover)';
    lbl.style.color       = on ? '#3b82f6' : 'var(--sub)';
  });

  const secLeft   = document.getElementById(`\${mid}_secLeft`);
  const secRight  = document.getElementById(`\${mid}_secRight`);
  const ctypeRow  = document.getElementById(`\${mid}_ctypeRow`);
  const imgFields = document.getElementById(`\${mid}_imgFields`);
  const txtFields = document.getElementById(`\${mid}_textFields`);

  if (secLeft)  secLeft.style.display  = type === 'layout' ? '' : 'none';
  if (secRight) secRight.style.display = type === 'layout' ? '' : 'none';
  if (ctypeRow) ctypeRow.style.display = type === 'image_only' ? 'none' : '';

  if (type === 'image_center') {
    if (imgFields) imgFields.style.display = '';
    if (txtFields) txtFields.style.display = 'none';
  } else if (type === 'image_only') {
    if (imgFields) imgFields.style.display = 'none';
    if (txtFields) txtFields.style.display = '';
  } else {
    // layout → respect center_type radio
    hbCenterTypeChange(mid);
  }

  hbUpdatePreview(mid);
}

// Center type change
function hbCenterTypeChange(mid) {
  const modalEl = document.getElementById(mid === 'hbAdd' ? 'modalAddBanner' : 'modalEditBanner');
  const ct = modalEl?.querySelector('input[name="center_type"]:checked')?.value || 'text';

  ['text','image'].forEach(v => {
    const lbl = document.getElementById(`\${mid}_ctlbl_\${v}`);
    if (!lbl) return;
    const on = v === ct;
    lbl.style.borderColor = on ? '#3b82f6' : 'var(--border)';
    lbl.style.background  = on ? 'rgba(59,130,246,.15)' : 'var(--card)';
    lbl.style.color       = on ? '#3b82f6' : 'var(--sub)';
  });

  const imgFields = document.getElementById(`\${mid}_imgFields`);
  const txtFields = document.getElementById(`\${mid}_textFields`);
  if (imgFields) imgFields.style.display = ct === 'image' ? '' : 'none';
  if (txtFields) txtFields.style.display = ct === 'text'  ? '' : 'none';
  hbUpdatePreview(mid);
}

// Live preview update
function hbUpdatePreview(mid) {
  const modalEl = document.getElementById(mid === 'hbAdd' ? 'modalAddBanner' : 'modalEditBanner');
  if (!modalEl) return;

  const wrap   = document.getElementById(`\${mid}_prevWrap`);
  const leftEl = document.getElementById(`\${mid}_prevLeft`);
  const rightEl= document.getElementById(`\${mid}_prevRight`);
  const titleEl= document.getElementById(`\${mid}_prevTitle`);
  const subEl  = document.getElementById(`\${mid}_prevSub`);
  const btnEl  = document.getElementById(`\${mid}_prevBtn`);
  if (!wrap) return;

  const bgImg  = modalEl.querySelector('input[name="bg_image"]')?.value || '';
  const bgS    = modalEl.querySelector('input[name="bg_color_start"]')?.value || '#0066cc';
  const bgE    = modalEl.querySelector('input[name="bg_color_end"]')?.value   || '#0099ff';
  const bgA    = modalEl.querySelector('input[name="bg_gradient_angle"]')?.value || 135;
  const imgL   = modalEl.querySelector('input[name="img_left"]')?.value  || '';
  const imgR   = modalEl.querySelector('input[name="img_right"]')?.value || '';
  const tit    = modalEl.querySelector('input[name="title"]')?.value     || '';
  const sub    = modalEl.querySelector('input[name="subtitle"]')?.value  || '';
  const btn    = modalEl.querySelector('input[name="btn_text"]')?.value  || '';
  const titClr = modalEl.querySelector('input[name="title_color"]')?.value     || '#fff';
  const subClr = modalEl.querySelector('input[name="subtitle_color"]')?.value  || '#ffffffd9';
  const btnBg  = modalEl.querySelector('input[name="btn_color"]')?.value       || '#FFD700';
  const btnClr = modalEl.querySelector('input[name="btn_text_color"]')?.value  || '#000';

  wrap.style.background = bgImg
    ? `url('\${bgImg}') center/cover no-repeat`
    : `linear-gradient(\${bgA}deg,\${bgS},\${bgE})`;

  if (leftEl)  leftEl.innerHTML  = imgL ? `<img src="\${imgL}" style="height:38px;max-width:60px;object-fit:contain" />` : '';
  if (rightEl) rightEl.innerHTML = imgR ? `<img src="\${imgR}" style="height:38px;max-width:60px;object-fit:contain" />` : '';
  if (titleEl) { titleEl.textContent = tit; titleEl.style.color = titClr; }
  if (subEl)   { subEl.textContent   = sub; subEl.style.color   = subClr; }
  if (btnEl) {
    if (btn) {
      btnEl.textContent = btn;
      btnEl.style.display = 'inline-block';
      btnEl.style.background = btnBg;
      btnEl.style.color = btnClr;
    } else {
      btnEl.style.display = 'none';
    }
  }
}

// Name inputs trigger preview
document.querySelectorAll('.modal input[name="title"], .modal input[name="subtitle"]').forEach(inp => {
  const mid = inp.closest('.modal')?.id === 'modalAddBanner' ? 'hbAdd' : 'hbEdit';
  inp.addEventListener('input', () => hbUpdatePreview(mid));
});

// Toggle switch (reuse pattern from menus)
function menusSw(input) {
  const track = input.nextElementSibling;
  const dot   = track.querySelector('span');
  track.style.background = input.checked ? '#3b82f6' : 'rgba(255,255,255,.12)';
  dot.style.left         = input.checked ? '19px' : '3px';
}

// Toast
function hbToast(msg) {
  const wrap = document.querySelector('.toast-wrap');
  if (!wrap) return;
  const t = document.createElement('div');
  t.className = 'toast-item toast-ok';
  t.innerHTML = `<i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i>\${msg}`;
  wrap.appendChild(t);
  setTimeout(() => t.style.opacity = '0', 3000);
  setTimeout(() => t.remove(), 3500);
}

document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => t.style.opacity = '0', 3500);
  setTimeout(() => t.remove(), 4000);
});
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>