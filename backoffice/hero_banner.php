<?php
// backoffice/dashboard_hero_banner.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Hero Banner Studio';
$active_menu = 'hero_banner';
$toast = $toast_e = '';
$action = $_POST['action'] ?? '';

if ((int)$pdo->query("SELECT COUNT(*) FROM hero_banner")->fetchColumn() === 0) {
  $pdo->exec("INSERT INTO hero_banner
    (type,bg_color_start,bg_color_end,bg_gradient_angle,height,sort_order,is_active,btn_pt,btn_pr,btn_pb,btn_pl)
    VALUES ('layout','#005bb5','#0099ff',135,160,1,1,'7px','26px','7px','26px')");
}
$banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();

const HB_ELEMENTS = ['strip_','img_left_','center_','title_','sub_','center_img_','btn_','img_right_'];
const HB_OFFSETS  = ['pt','pr','pb','pl','mt','mr','mb','ml','top','right','bottom','left'];

function _hbStyle(array $hb, string $pfx, array $extra = []): string {
  $p = []; $hasPos = false;
  foreach (['pt'=>'padding-top','pr'=>'padding-right','pb'=>'padding-bottom','pl'=>'padding-left',
            'mt'=>'margin-top','mr'=>'margin-right','mb'=>'margin-bottom','ml'=>'margin-left'] as $s=>$css) {
    $v = trim((string)($hb[$pfx.$s]??'')); if($v!=='') $p[]="$css:$v";
  }
  foreach (['top','right','bottom','left'] as $s) {
    $v = trim((string)($hb[$pfx.$s]??'')); if($v!=='') { $p[]="$s:$v"; $hasPos=true; }
  }
  if($hasPos) $p[]='position:relative';
  foreach($extra as $k=>$v) if($v!==null&&$v!=='') $p[]="$k:$v";
  return implode(';',$p);
}

function animCSS_php(string $a): string {
  return match($a) {
    'float'       => 'animation:anim-float 3s ease-in-out infinite',
    'bounce'      => 'animation:anim-bounce 1.2s ease-in-out infinite',
    'slide-left'  => 'animation:anim-sliL .5s ease-out both',
    'slide-right' => 'animation:anim-sliR .5s ease-out both',
    'pulse'       => 'animation:anim-pulse 1.5s ease-in-out infinite',
    'zoom-in'     => 'animation:anim-zoom .4s ease-out both',
    default       => '',
  };
}

if ($action === 'save') {
  $f = $_POST;
  $so = fn($v) => preg_replace('/[^a-zA-Z0-9.\-% ]/','',(string)$v) ?: null;
  $cols = [
    'type'                => in_array($f['type']??'',['image_only','layout','image_center'])?$f['type']:'layout',
    'bg_image'            => trim($f['bg_image']??'')?:null,
    'bg_color_start'      => trim($f['bg_color_start']??'#005bb5'),
    'bg_color_end'        => trim($f['bg_color_end']??'#0099ff'),
    'bg_gradient_angle'   => (int)($f['bg_gradient_angle']??135),
    'height'              => max(60,min(400,(int)($f['height']??160))),
    'img_left'            => trim($f['img_left']??'')?:null,
    'img_left_width'      => max(10,(int)($f['img_left_width']??90)),
    'img_left_height'     => max(0,(int)($f['img_left_height']??0)),
    'img_left_anim'       => trim($f['img_left_anim']??'')?:null,
    'center_type'         => in_array($f['center_type']??'',['text','image'])?$f['center_type']:'text',
    'title'               => trim($f['title']??'')?:null,
    'title_color'         => trim($f['title_color']??'#ffffff'),
    'subtitle'            => trim($f['subtitle']??'')?:null,
    'subtitle_color'      => trim($f['subtitle_color']??'#ffffffd9'),
    'center_image'        => trim($f['center_image']??'')?:null,
    'center_image_width'  => max(10,(int)($f['center_image_width']??160)),
    'center_image_height' => max(0,(int)($f['center_image_height']??0)),
    'center_image_anim'   => trim($f['center_image_anim']??'')?:null,
    'btn_text'            => trim($f['btn_text']??'')?:null,
    'btn_href'            => trim($f['btn_href']??'#'),
    'btn_color'           => trim($f['btn_color']??'#FFD700'),
    'btn_text_color'      => trim($f['btn_text_color']??'#000000'),
    'btn_anim'            => trim($f['btn_anim']??'pulse'),
    'img_right'           => trim($f['img_right']??'')?:null,
    'img_right_width'     => max(10,(int)($f['img_right_width']??90)),
    'img_right_height'    => max(0,(int)($f['img_right_height']??0)),
    'img_right_anim'      => trim($f['img_right_anim']??'')?:null,
    'is_active'           => isset($f['is_active'])?1:0,
  ];
  foreach(HB_ELEMENTS as $pfx)
    foreach(HB_OFFSETS as $sfx)
      $cols[$pfx.$sfx] = $so($f[$pfx.$sfx]??'');
  $set = implode(',',array_map(fn($k)=>"$k=?",array_keys($cols)));
  $pdo->prepare("UPDATE hero_banner SET $set WHERE id=?")
      ->execute([...array_values($cols),(int)$banner['id']]);
  $toast = 'Tersimpan!';
  $banner = $pdo->query("SELECT * FROM hero_banner ORDER BY id ASC LIMIT 1")->fetch();
}

$ANIM = [''=> 'Tidak Ada','float'=>'Float','bounce'=>'Bounce',
         'slide-left'=>'Slide Kiri','slide-right'=>'Slide Kanan','pulse'=>'Pulse','zoom-in'=>'Zoom In'];
function anim_sel(string $name, string $cur, string $cls='st-select'): string {
  global $ANIM;
  $o="<select name=\"$name\" class=\"$cls\" onchange=\"livePreview();markDirty()\">";
  foreach($ANIM as $v=>$l) $o.="<option value=\"$v\"".($cur===$v?' selected':'').">$l</option>";
  return $o."</select>";
}

$b = $banner;

// Safe JSON for JS
$js_keys = ['type','bg_image','bg_color_start','bg_color_end','bg_gradient_angle','height',
  'img_left','img_left_width','img_left_height','img_left_anim',
  'center_type','title','title_color','subtitle','subtitle_color',
  'center_image','center_image_width','center_image_height','center_image_anim',
  'btn_text','btn_href','btn_color','btn_text_color','btn_anim',
  'img_right','img_right_width','img_right_height','img_right_anim','is_active'];
foreach(HB_ELEMENTS as $pfx) foreach(HB_OFFSETS as $sfx) $js_keys[]=$pfx.$sfx;
$js_data = [];
foreach($js_keys as $k) $js_data[$k] = $b[$k] ?? null;
$b_json = json_encode($js_data, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
$page_scripts = '';

require_once __DIR__ . '/includes/header.php';
?>
<script>const HB=<?php echo $b_json; ?>;</script>

<!-- Toast -->
<div class="st-toast-wrap">
  <?php if($toast):?><div class="st-toast st-toast-ok"><i class="ph ph-check-circle"></i><?=htmlspecialchars($toast)?></div><?php endif;?>
  <?php if($toast_e):?><div class="st-toast st-toast-err"><i class="ph ph-warning-circle"></i><?=htmlspecialchars($toast_e)?></div><?php endif;?>
</div>

<style>
/* ════════════════════════════════════════════════════
   HERO BANNER STUDIO — Dark editor + bright canvas
════════════════════════════════════════════════════ */
.st-root{display:flex;height:calc(100vh - 56px);min-height:600px;overflow:hidden;background:#0d0d14;}

/* ── Tool strip (far left) ── */
.st-strip{
  width:48px;flex-shrink:0;background:#111118;border-right:1px solid #1e1e2c;
  display:flex;flex-direction:column;align-items:center;padding:10px 0;gap:3px;
}
.st-tb{
  width:34px;height:34px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;color:#44445a;font-size:17px;
  border:none;background:transparent;transition:all .14s;
  position:relative;
}
.st-tb:hover{background:#1a1a28;color:#8080a0;}
.st-tb.on{background:#1e3a8a;color:#93c5fd;}
.st-tb-sep{width:20px;height:1px;background:#1e1e2c;margin:3px 0;}

/* ── Inspector (left panel) ── */
.st-insp{
  width:264px;flex-shrink:0;background:#111118;
  border-right:1px solid #1e1e2c;
  display:flex;flex-direction:column;overflow:hidden;
}
.st-savebar{
  display:flex;align-items:center;gap:8px;padding:9px 12px;
  background:#0d0d14;border-bottom:1px solid #1e1e2c;flex-shrink:0;
}
.st-dirty{width:6px;height:6px;border-radius:50%;background:#f59e0b;display:none;flex-shrink:0;}
.st-dirty.on{display:block;}
.st-save-txt{font-size:10px;color:#44445a;flex:1;}
.st-save-btn{
  display:flex;align-items:center;gap:5px;
  background:#1e40af;color:#bfdbfe;border:none;
  padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;
  cursor:pointer;transition:all .14s;font-family:inherit;
}
.st-save-btn:hover{background:#2563eb;color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.4);}

/* Nav tabs */
.st-tabs{display:flex;background:#0d0d14;border-bottom:1px solid #1e1e2c;flex-shrink:0;}
.st-tab{
  flex:1;padding:8px 2px;font-size:9px;font-weight:700;text-transform:uppercase;
  letter-spacing:.5px;cursor:pointer;color:#3a3a52;
  border-bottom:2px solid transparent;border:none;background:transparent;
  display:flex;flex-direction:column;align-items:center;gap:2px;
  transition:all .14s;font-family:inherit;
}
.st-tab i{font-size:14px;}
.st-tab.on{color:#60a5fa;border-bottom-color:#2563eb;}

.st-body{flex:1;overflow-y:auto;padding:12px;scrollbar-width:thin;scrollbar-color:#1e1e2c transparent;}
.st-body::-webkit-scrollbar{width:3px;}
.st-body::-webkit-scrollbar-thumb{background:#1e1e2c;border-radius:2px;}

/* Form atoms */
.st-sec{margin-bottom:14px;}
.st-sh{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:#3a3a52;margin-bottom:7px;display:flex;align-items:center;gap:4px;}
.st-sh i{font-size:12px;}
.st-row{display:flex;gap:5px;margin-bottom:6px;}
.st-row:last-child{margin-bottom:0;}
.st-col{flex:1;min-width:0;}
.st-lbl{font-size:9.5px;font-weight:600;color:#44445a;display:block;margin-bottom:3px;}
.st-inp{
  width:100%;background:#0a0a12;border:1px solid #1e1e2c;border-radius:5px;
  padding:5px 7px;color:#b0b0cc;font-size:11px;
  font-family:'JetBrains Mono',ui-monospace,monospace;
  outline:none;transition:border-color .14s;
}
.st-inp:focus{border-color:#2563eb;box-shadow:0 0 0 2px rgba(37,99,235,.15);}
.st-inp::placeholder{color:#282838;}
.st-sel{
  width:100%;background:#0a0a12;border:1px solid #1e1e2c;border-radius:5px;
  padding:5px 7px;color:#b0b0cc;font-size:11px;font-family:inherit;
  outline:none;cursor:pointer;
}
.st-sel:focus{border-color:#2563eb;}
select.st-select{
  width:100%;background:#0a0a12;border:1px solid #1e1e2c;border-radius:5px;
  padding:5px 7px;color:#b0b0cc;font-size:11px;font-family:inherit;
  outline:none;cursor:pointer;
}
.st-pills{display:flex;gap:4px;}
.st-pill{
  flex:1;padding:6px 3px;border-radius:6px;font-size:9px;font-weight:700;
  cursor:pointer;border:1.5px solid #1e1e2c;background:#0a0a12;color:#44445a;
  text-align:center;transition:all .14s;display:flex;align-items:center;justify-content:center;gap:3px;
}
.st-pill.on{border-color:#2563eb;background:rgba(37,99,235,.14);color:#60a5fa;}
.st-clr-row{display:flex;gap:5px;align-items:center;}
.st-sw-inp{width:26px;height:26px;border-radius:5px;border:1px solid #1e1e2c;padding:2px;cursor:pointer;flex-shrink:0;background:none;}
.st-hex{flex:1;font-family:'JetBrains Mono',monospace;font-size:11px;background:#0a0a12;border:1px solid #1e1e2c;border-radius:5px;padding:4px 6px;color:#b0b0cc;outline:none;}
.st-hex:focus{border-color:#2563eb;}
.st-palette{display:flex;flex-wrap:wrap;gap:3px;margin-top:4px;}
.st-dot{width:15px;height:15px;border-radius:3px;cursor:pointer;border:1px solid rgba(255,255,255,.07);transition:transform .12s;}
.st-dot:hover{transform:scale(1.5);}
.st-grad{height:18px;border-radius:4px;border:1px solid #1e1e2c;margin-bottom:7px;}
.st-sld-row{display:flex;align-items:center;gap:7px;}
.st-sld-row input[type=range]{flex:1;accent-color:#2563eb;cursor:pointer;}
.st-sld-val{font-family:'JetBrains Mono',monospace;font-size:10px;color:#44445a;min-width:32px;text-align:right;}
.st-tog-row{display:flex;align-items:center;justify-content:space-between;padding:3px 0;}
.st-tog-lbl{font-size:11px;font-weight:500;color:#8080a0;}
.st-sw{position:relative;width:34px;height:19px;cursor:pointer;display:inline-block;flex-shrink:0;}
.st-sw input{opacity:0;width:0;height:0;position:absolute;}
.st-sw-tr{position:absolute;inset:0;border-radius:99px;background:#1e1e2c;transition:background .18s;}
.st-sw input:checked ~ .st-sw-tr{background:#2563eb;}
.st-sw-th{position:absolute;top:2px;left:2px;width:15px;height:15px;border-radius:50%;background:#fff;transition:left .18s;box-shadow:0 1px 3px rgba(0,0,0,.4);}
.st-sw input:checked ~ .st-sw-th{left:17px;}
.st-div{height:1px;background:#1e1e2c;margin:10px 0;}
.st-thumb{width:100%;height:30px;object-fit:cover;border-radius:4px;border:1px solid #1e1e2c;margin-top:4px;display:none;}
.st-sz{display:grid;grid-template-columns:1fr 1fr;gap:4px;}
.st-sz-f{position:relative;}
.st-sz-f .st-inp{padding-right:18px;}
.st-sz-u{position:absolute;right:6px;top:50%;transform:translateY(-50%);font-size:8px;font-weight:700;color:#282838;pointer-events:none;font-family:'JetBrains Mono',monospace;}
.st-off-wrap{display:flex;gap:4px;}
.st-off-col{flex:1;}
.st-off-h{font-size:7.5px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#282838;text-align:center;margin-bottom:3px;}
.st-off-g{display:grid;grid-template-columns:1fr 1fr;gap:2px;}
.st-off-c{display:flex;flex-direction:column;align-items:center;gap:1px;}
.st-off-d{font-size:7.5px;font-weight:700;color:#282838;}
.st-off-i{
  width:100%;background:#080810;border:1px solid #1a1a26;border-radius:3px;
  padding:3px 2px;color:#8080a0;font-size:9.5px;
  font-family:'JetBrains Mono',monospace;text-align:center;outline:none;
}
.st-off-i:focus{border-color:#2563eb;}
.st-off-i::placeholder{color:#1e1e28;opacity:1;}

/* ── Canvas ── */
.st-canvas{
  flex:1;overflow:auto;
  background:#0d0d14;
  background-image:
    linear-gradient(rgba(255,255,255,.012) 1px,transparent 1px),
    linear-gradient(90deg,rgba(255,255,255,.012) 1px,transparent 1px);
  background-size:20px 20px;
  display:flex;flex-direction:column;align-items:center;
  padding:28px 20px;gap:14px;
}
.st-canvas-lbl{
  font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.9px;
  color:#282838;display:flex;align-items:center;gap:8px;width:100%;max-width:480px;
}
.st-canvas-lbl::before,.st-canvas-lbl::after{content:'';flex:1;height:1px;background:#1a1a26;}

.st-pw{
  width:100%;max-width:480px;
  border-radius:16px;overflow:hidden;flex-shrink:0;
  box-shadow:0 0 0 1px rgba(255,255,255,.05),0 24px 64px rgba(0,0,0,.6);
}

/* ── Banner elements (exact user-page CSS) ── */
#prevCanvas{position:relative;overflow:hidden;padding:28px 18px 0;width:100%;}
#prevCanvas::before,#prevCanvas::after{content:'';position:absolute;border-radius:50%;pointer-events:none;}
#prevCanvas::before{width:240px;height:240px;background:rgba(255,255,255,.07);top:-80px;right:-60px;}
#prevCanvas::after{width:130px;height:130px;background:rgba(255,255,255,.05);bottom:60px;left:-40px;}
#prevHeroOv{position:absolute;inset:0;pointer-events:none;z-index:1;}
#prevStrip{position:relative;z-index:2;display:flex;align-items:flex-end;justify-content:space-between;margin-top:14px;padding-bottom:44px;pointer-events:none;}
#prevStrip .drg,#prevStrip a{pointer-events:auto;}
#prevLeft,#prevRight{display:flex;align-items:flex-end;flex-shrink:0;}
#prevLeft img,#prevRight img{object-fit:contain;display:block;}
#prevCenter{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;text-align:center;padding:0 4px 6px;}
#prevTitle{font-size:16px;font-weight:900;line-height:1.15;letter-spacing:-.3px;text-shadow:0 1px 6px rgba(0,0,0,.25);margin-bottom:2px;}
#prevSub{font-size:10.5px;font-weight:700;margin-bottom:10px;opacity:.88;text-shadow:0 1px 3px rgba(0,0,0,.2);}
#prevCenterImg{max-width:100%;object-fit:contain;margin-bottom:10px;}
#prevBtn{display:inline-block;padding:7px 26px;border-radius:99px;font-size:12px;font-weight:900;letter-spacing:.4px;text-decoration:none;cursor:pointer;border:none;box-shadow:0 4px 14px rgba(0,0,0,.22);}
#prevInactiveOv{position:absolute;inset:0;background:rgba(0,0,0,.65);display:flex;align-items:center;justify-content:center;z-index:20;pointer-events:none;}
.st-ib{background:rgba(220,38,38,.15);border:1px solid #dc2626;color:#f87171;font-size:11px;font-weight:700;padding:5px 12px;border-radius:6px;display:flex;align-items:center;gap:6px;}

/* Drag handles */
.drg{position:relative;display:inline-block;outline:2px solid transparent;outline-offset:3px;border-radius:4px;cursor:grab;transition:outline-color .12s;user-select:none;}
.drg:hover{outline-color:rgba(96,165,250,.5);}
.drg.sel{outline-color:#60a5fa;}
.drg.dragging{cursor:grabbing;}
.drg.sel::after{content:attr(data-lbl);position:absolute;top:-20px;left:0;background:#2563eb;color:#fff;font-size:8.5px;font-weight:700;padding:2px 7px;border-radius:4px 4px 0 0;white-space:nowrap;pointer-events:none;z-index:100;font-family:system-ui,sans-serif;}
.rsz{position:absolute;bottom:-5px;right:-5px;width:11px;height:11px;background:#2563eb;border:2px solid #0d0d14;border-radius:3px;cursor:se-resize;z-index:50;display:none;}
.drg.sel .rsz{display:block;}

/* Ruler */
.st-ruler{
  width:100%;max-width:480px;
  background:#111118;border:1px solid #1e1e2c;
  border-radius:8px;padding:6px 12px;
  display:flex;align-items:center;gap:12px;flex-wrap:wrap;
  font-size:9.5px;color:#3a3a52;flex-shrink:0;
}
.st-ruler strong{color:#5a5a78;font-family:'JetBrains Mono',monospace;}
.st-rdot{width:5px;height:5px;border-radius:50%;flex-shrink:0;}

/* ── Right props panel ── */
.st-props{
  width:220px;flex-shrink:0;background:#111118;
  border-left:1px solid #1e1e2c;
  display:flex;flex-direction:column;overflow:hidden;
}
.st-ph{padding:10px 12px;border-bottom:1px solid #1e1e2c;flex-shrink:0;}
.st-ph-t{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:#3a3a52;}
.st-ph-el{font-size:10.5px;font-weight:700;color:#5a5a78;margin-top:2px;}
.st-pb{flex:1;overflow-y:auto;padding:10px;scrollbar-width:thin;scrollbar-color:#1e1e2c transparent;}
.st-pb::-webkit-scrollbar{width:3px;}
.st-pb::-webkit-scrollbar-thumb{background:#1e1e2c;}
.st-empty{height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:16px;text-align:center;}
.st-empty i{font-size:26px;color:#1e1e2c;}
.st-empty p{font-size:10px;color:#2a2a3a;line-height:1.6;}

/* Context popup */
#ctxPop{position:fixed;z-index:9999;background:#16161e;border:1px solid #2a2a3e;border-radius:11px;padding:12px;min-width:196px;box-shadow:0 16px 48px rgba(0,0,0,.7);display:none;}
.ctx-t{font-size:10.5px;font-weight:800;color:#8080a0;margin-bottom:9px;display:flex;align-items:center;gap:5px;}
.ctx-t i{color:#60a5fa;font-size:13px;}
.ctx-lbl{font-size:8.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#3a3a50;margin-bottom:4px;}
.ctx-pills{display:flex;gap:4px;margin-bottom:7px;}
.ctx-pill{flex:1;padding:5px 4px;text-align:center;border-radius:5px;font-size:9.5px;font-weight:700;cursor:pointer;border:1.5px solid #2a2a3e;background:#0d0d16;color:#44445a;transition:all .12s;}
.ctx-pill.on{border-color:#2563eb;background:rgba(37,99,235,.16);color:#60a5fa;}
.ctx-hint{font-size:9.5px;color:#3a3a50;background:#0d0d16;border-radius:5px;padding:5px 7px;line-height:1.5;}

/* Toast */
.st-toast-wrap{position:fixed;top:14px;right:14px;z-index:9999;display:flex;flex-direction:column;gap:6px;}
.st-toast{display:flex;align-items:center;gap:7px;padding:8px 14px;border-radius:8px;font-size:12px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.4);}
.st-toast i{font-size:14px;}
.st-toast-ok{background:#064e3b;color:#6ee7b7;border:1px solid #065f46;}
.st-toast-err{background:#7f1d1d;color:#fca5a5;border:1px solid #b91c1c;}

/* Keyframes */
@keyframes anim-float {0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
@keyframes anim-bounce{0%,100%{transform:translateY(0)}40%{transform:translateY(-8px)}60%{transform:translateY(-4px)}}
@keyframes anim-sliL  {from{transform:translateX(-20px);opacity:0}to{transform:none;opacity:1}}
@keyframes anim-sliR  {from{transform:translateX(20px);opacity:0}to{transform:none;opacity:1}}
@keyframes anim-pulse {0%,100%{transform:scale(1)}50%{transform:scale(1.07)}}
@keyframes anim-zoom  {from{transform:scale(.8);opacity:0}to{transform:scale(1);opacity:1}}
</style>

<?php $b=$banner; ?>
<form method="POST" id="stForm">
<input type="hidden" name="action" value="save"/>
<div class="st-root">

<!-- TOOL STRIP -->
<div class="st-strip">
  <button type="button" class="st-tb on" id="tbBg" onclick="swTool('Bg')" title="Background"><i class="ph ph-paint-bucket"></i></button>
  <button type="button" class="st-tb" id="tbKonten" onclick="swTool('Konten')" title="Konten"><i class="ph ph-text-aa"></i></button>
  <button type="button" class="st-tb" id="tbGambar" onclick="swTool('Gambar')" title="Gambar"><i class="ph ph-images"></i></button>
  <button type="button" class="st-tb" id="tbOffset" onclick="swTool('Offset')" title="Offset"><i class="ph ph-arrows-out"></i></button>
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
    <button type="button" class="st-tab on" data-p="pBg"><i class="ph ph-paint-bucket"></i>BG</button>
    <button type="button" class="st-tab" data-p="pKonten"><i class="ph ph-text-aa"></i>Konten</button>
    <button type="button" class="st-tab" data-p="pGambar"><i class="ph ph-images"></i>Gambar</button>
    <button type="button" class="st-tab" data-p="pOffset"><i class="ph ph-arrows-out"></i>Offset</button>
    <button type="button" class="st-tab" data-p="pSet"><i class="ph ph-gear-six"></i>Set</button>
  </div>
  <div class="st-body">

    <!-- PANEL BG -->
    <div id="pBg">
      <div class="st-sec">
        <div class="st-sh"><i class="ph ph-layout"></i>Tipe Layout</div>
        <div class="st-pills">
          <?php foreach(['layout'=>['ph-layout','Kiri·C·Kanan'],'image_center'=>['ph-frame-corners','Gambar Tengah'],'image_only'=>['ph-image','Full BG']] as $v=>[$ic,$lb]):?>
          <div class="st-pill <?=$b['type']===$v?'on':''?>" onclick="setType('<?=$v?>')">
            <input type="radio" name="type" value="<?=$v?>" <?=$b['type']===$v?'checked':''?> style="display:none"/>
            <i class="ph <?=$ic?>"></i><span style="font-size:8.5px"><?=$lb?></span>
          </div>
          <?php endforeach;?>
        </div>
      </div>
      <div class="st-div"></div>
      <div class="st-sec">
        <div class="st-sh"><i class="ph ph-image"></i>BG Image <span style="font-weight:400;text-transform:none;color:#282838;font-size:8.5px">override gradient</span></div>
        <input type="text" name="bg_image" class="st-inp" placeholder="https://…" value="<?=htmlspecialchars($b['bg_image']??'')?>" oninput="livePreview();showThumb(this,'bgT')"/>
        <img id="bgT" class="st-thumb" src="<?=htmlspecialchars($b['bg_image']??'')?>" alt="" style="<?=!empty($b['bg_image'])?'display:block':''?>"/>
      </div>
      <div class="st-sec">
        <div class="st-sh"><i class="ph ph-gradient"></i>Gradient</div>
        <div id="gradBar" class="st-grad" style="background:linear-gradient(<?=$b['bg_gradient_angle']?>deg,<?=$b['bg_color_start']?>,<?=$b['bg_color_end']?>)"></div>
        <div class="st-row">
          <div class="st-col">
            <span class="st-lbl">Awal</span>
            <div class="st-clr-row">
              <input type="color" name="bg_color_start" class="st-sw-inp" id="sw_s" value="<?=htmlspecialchars($b['bg_color_start']??'#005bb5')?>" oninput="syncH(this,'hx_s');livePreview();updateGrad()"/>
              <input type="text" id="hx_s" class="st-hex" maxlength="7" value="<?=htmlspecialchars($b['bg_color_start']??'#005bb5')?>" oninput="syncS(this,'sw_s');livePreview();updateGrad()"/>
            </div>
            <div class="st-palette">
              <?php foreach(['#005bb5','#0f172a','#7c3aed','#065f46','#9a3412','#1e1b4b','#0c4a6e','#7f1d1d'] as $c):?>
              <div class="st-dot" style="background:<?=$c?>" onclick="setClr('sw_s','hx_s','<?=$c?>')"></div>
              <?php endforeach;?>
            </div>
          </div>
          <div class="st-col">
            <span class="st-lbl">Akhir</span>
            <div class="st-clr-row">
              <input type="color" name="bg_color_end" class="st-sw-inp" id="sw_e" value="<?=htmlspecialchars($b['bg_color_end']??'#0099ff')?>" oninput="syncH(this,'hx_e');livePreview();updateGrad()"/>
              <input type="text" id="hx_e" class="st-hex" maxlength="7" value="<?=htmlspecialchars($b['bg_color_end']??'#0099ff')?>" oninput="syncS(this,'sw_e');livePreview();updateGrad()"/>
            </div>
            <div class="st-palette">
              <?php foreach(['#0099ff','#38bdf8','#a78bfa','#34d399','#fb923c','#f472b6','#facc15','#4ade80'] as $c):?>
              <div class="st-dot" style="background:<?=$c?>" onclick="setClr('sw_e','hx_e','<?=$c?>')"></div>
              <?php endforeach;?>
            </div>
          </div>
        </div>
        <span class="st-lbl" style="margin-top:6px;display:block">Sudut</span>
        <div class="st-sld-row">
          <input type="range" name="bg_gradient_angle" min="0" max="360" value="<?=(int)($b['bg_gradient_angle']??135)?>" oninput="document.getElementById('gaV').textContent=this.value+'deg';livePreview();updateGrad()"/>
          <span id="gaV" class="st-sld-val"><?=(int)$b['bg_gradient_angle']?>deg</span>
        </div>
      </div>
      <div class="st-sec">
        <div class="st-sh"><i class="ph ph-arrows-vertical"></i>Tinggi</div>
        <div class="st-sld-row">
          <input type="range" name="height" min="60" max="280" value="<?=(int)($b['height']??160)?>" oninput="document.getElementById('hV').textContent=this.value+'px';livePreview()"/>
          <span id="hV" class="st-sld-val"><?=(int)$b['height']?>px</span>
        </div>
      </div>
    </div><!-- /pBg -->

    <!-- PANEL KONTEN -->
    <div id="pKonten" style="display:none">
      <div class="st-sec">
        <div class="st-sh"><i class="ph ph-align-center-horizontal"></i>Isi Tengah</div>
        <div class="st-pills">
          <?php foreach(['text'=>['ph-text-aa','Teks & Tombol'],'image'=>['ph-image-square','Gambar']] as $v=>[$ic,$lb]):?>
          <div class="st-pill <?=($b['center_type']??'text')===$v?'on':''?>" onclick="setCT('<?=$v?>')">
            <input type="radio" name="center_type" value="<?=$v?>" <?=($b['center_type']??'text')===$v?'checked':''?> style="display:none"/>
            <i class="ph <?=$ic?>"></i><?=$lb?>
          </div>
          <?php endforeach;?>
        </div>
      </div>
      <div id="ctT">
        <div class="st-sec">
          <div class="st-sh"><i class="ph ph-text-h" style="color:#f59e0b"></i>Teks</div>
          <div class="st-row" style="align-items:flex-end">
            <div style="flex-shrink:0"><span class="st-lbl">Clr</span><input type="color" name="title_color" class="st-sw-inp" value="<?=htmlspecialchars($b['title_color']??'#ffffff')?>" oninput="livePreview()"/></div>
            <div class="st-col"><span class="st-lbl">Judul</span><input type="text" name="title" class="st-inp" maxlength="200" placeholder="KLAIM HADIAH" value="<?=htmlspecialchars($b['title']??'')?>" oninput="livePreview()"/></div>
          </div>
          <div class="st-row" style="align-items:flex-end;margin-top:4px">
            <div style="flex-shrink:0"><span class="st-lbl">Clr</span><input type="color" name="subtitle_color" class="st-sw-inp" value="<?=htmlspecialchars($b['subtitle_color']??'#ffffffd9')?>" oninput="livePreview()"/></div>
            <div class="st-col"><span class="st-lbl">Subtitle</span><input type="text" name="subtitle" class="st-inp" maxlength="300" placeholder="& Jutaan Rupiah" value="<?=htmlspecialchars($b['subtitle']??'')?>" oninput="livePreview()"/></div>
          </div>
        </div>
        <div class="st-sec">
          <div class="st-sh"><i class="ph ph-cursor-click" style="color:#10b981"></i>Tombol</div>
          <div class="st-row">
            <div class="st-col"><span class="st-lbl">Teks</span><input type="text" name="btn_text" class="st-inp" maxlength="80" placeholder="SERBU" value="<?=htmlspecialchars($b['btn_text']??'')?>" oninput="livePreview()"/></div>
            <div class="st-col"><span class="st-lbl">Link</span><input type="text" name="btn_href" class="st-inp" maxlength="255" placeholder="#" value="<?=htmlspecialchars($b['btn_href']??'#')?>"/></div>
          </div>
          <div class="st-row">
            <div class="st-col"><span class="st-lbl">BG</span><input type="color" name="btn_color" class="st-sw-inp" style="width:100%;height:26px" value="<?=htmlspecialchars($b['btn_color']??'#FFD700')?>" oninput="livePreview()"/></div>
            <div class="st-col"><span class="st-lbl">Teks</span><input type="color" name="btn_text_color" class="st-sw-inp" style="width:100%;height:26px" value="<?=htmlspecialchars($b['btn_text_color']??'#000000')?>" oninput="livePreview()"/></div>
            <div class="st-col"><span class="st-lbl">Anim</span><?=anim_sel('btn_anim',$b['btn_anim']??'pulse','st-select')?></div>
          </div>
        </div>
      </div>
      <div id="ctI" style="display:none">
        <div class="st-sec">
          <div class="st-sh"><i class="ph ph-image-square" style="color:#a855f7"></i>Gambar Tengah</div>
          <input type="text" name="center_image" class="st-inp" placeholder="https://…" value="<?=htmlspecialchars($b['center_image']??'')?>" oninput="livePreview();showThumb(this,'ciT')"/>
          <img id="ciT" class="st-thumb" src="<?=htmlspecialchars($b['center_image']??'')?>" alt="" style="<?=!empty($b['center_image'])?'display:block':''?>"/>
          <div class="st-row" style="margin-top:6px">
            <div class="st-col"><span class="st-lbl">W×H</span>
              <div class="st-sz">
                <div class="st-sz-f"><input type="number" name="center_image_width" id="f_center_image_width" class="st-inp" min="10" max="600" value="<?=(int)($b['center_image_width']??160)?>" oninput="livePreview();markDirty()"/><span class="st-sz-u">W</span></div>
             <div class="st-sz-f">
<input type="number" 
       name="center_image_height" 
       id="f_center_image_height" 
       class="st-inp" 
       min="0" 
       max="600" 
       placeholder="auto"
       value="<?= ((int)($b['center_image_height'] ?? 0)) ? (int)$b['center_image_height'] : '' ?>"
       oninput="livePreview();markDirty()"/>
<span class="st-sz-u">H</span>
</div>
              </div>
            </div>
            <div class="st-col"><span class="st-lbl">Anim</span><?=anim_sel('center_image_anim',$b['center_image_anim']??'','st-select')?></div>
          </div>
        </div>
      </div>
    </div><!-- /pKonten -->

    <!-- PANEL GAMBAR -->
    <div id="pGambar" style="display:none">
      <div id="sideArea">
        <?php
        $imgs=[
          ['img_left_','ph-align-left','#a855f7','Kiri','img_left','img_left_width','img_left_height','img_left_anim','ilT'],
          ['img_right_','ph-align-right','#f59e0b','Kanan','img_right','img_right_width','img_right_height','img_right_anim','irT'],
        ];
        foreach($imgs as [$pfx,$ic,$clr,$lbl,$fn,$fnw,$fnh,$fna,$tid]):?>
        <div class="st-sec">
          <div class="st-sh"><i class="ph <?=$ic?>" style="color:<?=$clr?>"></i>Gambar <?=$lbl?></div>
          <input type="text" name="<?=$fn?>" class="st-inp" placeholder="https://…" value="<?=htmlspecialchars($b[$fn]??'')?>" oninput="livePreview();showThumb(this,'<?=$tid?>')"/>
          <img id="<?=$tid?>" class="st-thumb" src="<?=htmlspecialchars($b[$fn]??'')?>" alt="" style="<?=!empty($b[$fn])?'display:block':''?>"/>
          <div class="st-row" style="margin-top:6px">
            <div class="st-col"><span class="st-lbl">W×H</span>
              <div class="st-sz">
                <div class="st-sz-f"><input type="number" name="<?=$fnw?>" id="f_<?=$fnw?>" class="st-inp" min="10" max="400" value="<?=(int)($b[$fnw]??90)?>" oninput="livePreview();markDirty()"/><span class="st-sz-u">W</span></div>
              <div class="st-sz-f">
    <input type="number"
           name="<?=$fnh?>"
           id="f_<?=$fnh?>"
           class="st-inp"
           min="0"
           max="400"
           placeholder="auto"
           value="<?= (int)($b[$fnh] ?? 0) ? (int)$b[$fnh] : '' ?>"
           oninput="livePreview();markDirty()"/>
    <span class="st-sz-u">H</span>
</div>
              </div>
            </div>
            <div class="st-col"><span class="st-lbl">Anim</span><?=anim_sel($fna,$b[$fna]??'','st-select')?></div>
          </div>
        </div>
        <?php endforeach;?>
      </div>
      <div id="noSide" style="display:none;font-size:10px;color:#3a3a52;background:#0a0a12;border-radius:7px;padding:10px;line-height:1.6">
        <i class="ph ph-info" style="color:#2563eb"></i> Gambar kiri/kanan hanya untuk tipe <strong style="color:#60a5fa">Kiri·C·Kanan</strong>.
      </div>
    </div><!-- /pGambar -->

    <!-- PANEL OFFSET -->
    <div id="pOffset" style="display:none">
      <?php
      $offs=[
        ['strip_','ph-frame-corners','#64748b','Strip',''],
        ['img_left_','ph-align-left','#a855f7','Gambar Kiri','sideOff'],
        ['center_','ph-align-center-horizontal','#3b82f6','Blok Tengah',''],
        ['title_','ph-text-h','#f59e0b','Judul',''],
        ['sub_','ph-text-align-left','#10b981','Subtitle',''],
        ['center_img_','ph-image-square','#a855f7','Gambar Tengah',''],
        ['btn_','ph-cursor-click','#ef4444','Tombol',''],
        ['img_right_','ph-align-right','#f59e0b','Gambar Kanan','sideOff'],
      ];
      foreach($offs as [$pfx,$ic,$clr,$lbl,$cls]):?>
      <div class="st-sec <?=$cls?>">
        <div class="st-sh"><i class="ph <?=$ic?>" style="color:<?=$clr?>"></i><?=$lbl?></div>
        <div class="st-off-wrap">
          <?php foreach(['PAD'=>['pt','pr','pb','pl'],'MAR'=>['mt','mr','mb','ml'],'POS'=>['top','right','bottom','left']] as $gl=>$sfxs):?>
          <div class="st-off-col">
            <div class="st-off-h"><?=$gl?></div>
            <div class="st-off-g">
              <?php foreach($sfxs as $sfx):
                $col=$pfx.$sfx; $val=htmlspecialchars($b[$col]??'');
                $dir=strtoupper(substr($sfx,0,1));?>
              <div class="st-off-c">
                <span class="st-off-d"><?=$dir?></span>
                <input type="text" name="<?=$col?>" id="f_<?=$col?>" class="st-off-i"
                  value="<?=$val?>" placeholder="—" maxlength="10"
                  oninput="livePreview();markDirty()"/>
              </div>
              <?php endforeach;?>
            </div>
          </div>
          <?php endforeach;?>
        </div>
      </div>
      <?php endforeach;?>
      <div style="font-size:9px;color:#282838;background:#0a0a12;border-radius:6px;padding:7px 9px;line-height:1.5;margin-top:4px">
        Format: <code style="color:#60a5fa">10px</code> / <code style="color:#60a5fa">-5px</code> / <code style="color:#60a5fa">50%</code>. Kosong = skip.
      </div>
    </div><!-- /pOffset -->

    <!-- PANEL SETTINGS -->
    <div id="pSet" style="display:none">
      <div class="st-sec">
        <div class="st-sh"><i class="ph ph-toggle-right"></i>Status</div>
        <div class="st-tog-row">
          <span class="st-tog-lbl">Aktifkan Banner</span>
          <label class="st-sw">
            <input type="checkbox" name="is_active" <?=$b['is_active']?'checked':''?> onchange="markDirty();livePreview()"/>
            <span class="st-sw-tr"></span><span class="st-sw-th"></span>
          </label>
        </div>
      </div>
      <div class="st-div"></div>
      <div class="st-sec">
        <div class="st-sh"><i class="ph ph-database"></i>Info</div>
        <?php foreach([['ID','#'.$b['id']],['Tipe',$b['type']],['Dibuat',date('d M Y',strtotime($b['created_at']))],['Update',date('d M Y',strtotime($b['updated_at']))]] as [$k,$v]):?>
        <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #1a1a26">
          <span style="font-size:10px;color:#3a3a52"><?=$k?></span>
          <span style="font-size:10px;font-weight:600;font-family:'JetBrains Mono',monospace;color:#5a5a78"><?=htmlspecialchars($v)?></span>
        </div>
        <?php endforeach;?>
      </div>
    </div><!-- /pSet -->

  </div><!-- /st-body -->
</div><!-- /st-insp -->

<!-- CANVAS -->
<div class="st-canvas">
  <div class="st-canvas-lbl">Live Preview</div>
  <div class="st-pw">
    <div id="prevCanvas" style="background:<?=!empty($b['bg_image'])?"url('".htmlspecialchars($b['bg_image'])."') center/cover no-repeat":"linear-gradient({$b['bg_gradient_angle']}deg,{$b['bg_color_start']},{$b['bg_color_end']}"?>)">
      <div id="prevHeroOv"></div>
      <div id="prevInactiveOv" style="<?=$b['is_active']?'display:none':''?>">
        <span class="st-ib"><i class="ph ph-eye-slash"></i>Banner Nonaktif</span>
      </div>
      <div id="prevStrip" style="<?=_hbStyle($b,'strip_',['padding-bottom'=>($b['strip_pb']??'44px')])?>">

        <div class="drg" id="drgL" data-lbl="Gambar Kiri" data-pfx="img_left_"
          style="flex-shrink:0;display:<?=($b['type']==='layout'&&!empty($b['img_left']))?'flex':'none'?>">
          <div id="prevLeft" style="<?=_hbStyle($b,'img_left_')?>">
            <?php if(!empty($b['img_left'])&&$b['type']==='layout'):?>
            <img src="<?=htmlspecialchars($b['img_left'])?>"
              style="width:<?=(int)$b['img_left_width']?>px;<?=(int)($b['img_left_height']??0)>0?'height:'.(int)$b['img_left_height'].'px;':'max-height:'.(int)$b['height'].'px;'?>object-fit:contain;<?=animCSS_php($b['img_left_anim']??'')?>"/>
            <?php endif;?>
          </div>
          <div class="rsz" data-target="img_left"></div>
        </div>

        <div id="prevCenter" style="<?=_hbStyle($b,'center_',['min-height'=>(int)($b['height']??160).'px'])?>">
          <?php if(($b['center_type']??'text')==='image'&&!empty($b['center_image'])):?>
          <div class="drg" id="drgCI" data-lbl="Gambar Tengah" data-pfx="center_img_" style="display:inline-block">
            <img id="prevCenterImg" src="<?=htmlspecialchars($b['center_image'])?>"
              style="width:<?=(int)($b['center_image_width']??160)?>px;<?=(int)($b['center_image_height']??0)>0?'height:'.(int)$b['center_image_height'].'px;':'max-height:'.(int)($b['height']??160).'px;'?>object-fit:contain;<?=animCSS_php($b['center_image_anim']??'')?>"/>
            <div class="rsz" data-target="center_image"></div>
          </div>
          <?php else:?>
            <?php if(!empty($b['title'])):?><div id="prevTitle" style="color:<?=htmlspecialchars($b['title_color']??'#fff')?>;<?=_hbStyle($b,'title_')?>"><?=htmlspecialchars($b['title']??'')?></div><?php endif;?>
            <?php if(!empty($b['subtitle'])):?><div id="prevSub" style="color:<?=htmlspecialchars($b['subtitle_color']??'#ffffffd9')?>;<?=_hbStyle($b,'sub_')?>"><?=htmlspecialchars($b['subtitle']??'')?></div><?php endif;?>
          <?php endif;?>
          <?php if(!empty($b['btn_text'])):?>
          <div class="drg" id="drgBtn" data-lbl="Tombol" data-pfx="btn_" style="display:inline-block">
            <a id="prevBtn" href="<?=htmlspecialchars($b['btn_href']??'#')?>"
              style="background:<?=htmlspecialchars($b['btn_color']??'#FFD700')?>;color:<?=htmlspecialchars($b['btn_text_color']??'#000')?>;<?=_hbStyle($b,'btn_')?>;<?=animCSS_php($b['btn_anim']??'')?>">
              <?=htmlspecialchars($b['btn_text'])?>
            </a>
          </div>
          <?php endif;?>
        </div>

        <div class="drg" id="drgR" data-lbl="Gambar Kanan" data-pfx="img_right_"
          style="flex-shrink:0;display:<?=($b['type']==='layout'&&!empty($b['img_right']))?'flex':'none'?>">
          <div id="prevRight" style="<?=_hbStyle($b,'img_right_')?>">
            <?php if(!empty($b['img_right'])&&$b['type']==='layout'):?>
            <img src="<?=htmlspecialchars($b['img_right'])?>"
              style="width:<?=(int)$b['img_right_width']?>px;<?=(int)($b['img_right_height']??0)>0?'height:'.(int)$b['img_right_height'].'px;':'max-height:'.(int)$b['height'].'px;'?>object-fit:contain;<?=animCSS_php($b['img_right_anim']??'')?>"/>
            <?php endif;?>
          </div>
          <div class="rsz" data-target="img_right"></div>
        </div>

      </div>
    </div>
  </div>

  <div class="st-ruler">
    <span>H: <strong id="rH"><?=(int)$b['height']?>px</strong></span>
    <span>Tipe: <strong id="rT"><?=htmlspecialchars($b['type'])?></strong></span>
    <span>Center: <strong id="rC"><?=htmlspecialchars($b['center_type']??'text')?></strong></span>
    <span style="margin-left:auto;display:flex;align-items:center;gap:5px">
      <span class="st-rdot" id="rDot" style="background:<?=$b['is_active']?'#059669':'#dc2626'?>"></span>
      <span id="rS" style="color:<?=$b['is_active']?'#059669':'#dc2626'?>"><?=$b['is_active']?'Aktif':'Nonaktif'?></span>
    </span>
  </div>
</div><!-- /st-canvas -->

<!-- RIGHT PROPS PANEL -->
<div class="st-props">
  <div class="st-ph">
    <div class="st-ph-t">Properties</div>
    <div class="st-ph-el" id="propEl">klik elemen di preview</div>
  </div>
  <div class="st-pb" id="propBody">
    <div class="st-empty"><i class="ph ph-cursor-click"></i><p>Klik gambar / tombol di preview untuk edit offset &amp; ukuran langsung.</p></div>
  </div>
</div>

</div><!-- /st-root -->
</form>

<!-- Context popup -->
<div id="ctxPop">
  <div class="ctx-t"><i class="ph ph-arrows-out-cardinal"></i><span id="ctxTitle">Elemen</span></div>
  <div class="ctx-lbl">Mode Geser</div>
  <div class="ctx-pills">
    <div class="ctx-pill on" id="cpM" onclick="setMode('margin')"><i class="ph ph-squares-four"></i> Margin</div>
    <div class="ctx-pill"   id="cpP" onclick="setMode('position')"><i class="ph ph-crosshair"></i> Posisi</div>
  </div>
  <div class="ctx-hint" id="ctxHint"></div>
</div>

<script>
// ─── Tabs & Tool strip ────────────────────────────────────
function swTool(name){
  document.querySelectorAll('.st-tb').forEach(b=>b.classList.remove('on'));
  document.getElementById('tb'+name)?.classList.add('on');
  showPanel('p'+name);
}
function showPanel(id){
  ['pBg','pKonten','pGambar','pOffset','pSet'].forEach(p=>{
    const el=document.getElementById(p);if(el)el.style.display=p===id?'':'none';
  });
  document.querySelectorAll('.st-tab').forEach(t=>t.classList.toggle('on',t.dataset.p===id));
}
document.querySelectorAll('.st-tab').forEach(t=>t.addEventListener('click',()=>showPanel(t.dataset.p)));

// ─── Type / center-type ───────────────────────────────────
function setType(v){
  document.querySelectorAll('input[name="type"]').forEach(r=>r.checked=r.value===v);
  document.querySelectorAll('#pBg .st-pill').forEach(p=>{
    const r=p.querySelector('input[name="type"]');if(r)p.classList.toggle('on',r.checked);
  });
  const isL=v==='layout';
  document.getElementById('sideArea').style.display=isL?'':'none';
  document.getElementById('noSide').style.display=isL?'none':'';
  document.querySelectorAll('.sideOff').forEach(e=>e.style.display=isL?'':'none');
  markDirty();livePreview();
}
function setCT(v){
  document.querySelectorAll('input[name="center_type"]').forEach(r=>r.checked=r.value===v);
  document.querySelectorAll('#pKonten .st-pill').forEach(p=>{
    const r=p.querySelector('input[name="center_type"]');if(r)p.classList.toggle('on',r.checked);
  });
  document.getElementById('ctT').style.display=v==='text'?'':'none';
  document.getElementById('ctI').style.display=v==='image'?'':'none';
  markDirty();livePreview();
}

// ─── Color ────────────────────────────────────────────────
function syncH(sw,hId){const h=document.getElementById(hId);if(h)h.value=sw.value;}
function syncS(h,swId){if(/^#[0-9a-fA-F]{6}$/.test(h.value)){const s=document.getElementById(swId);if(s)s.value=h.value;}}
function setClr(swId,hId,c){const s=document.getElementById(swId),h=document.getElementById(hId);if(s)s.value=c;if(h)h.value=c;markDirty();livePreview();updateGrad();}
function updateGrad(){
  const bar=document.getElementById('gradBar');if(!bar)return;
  const s=document.getElementById('sw_s')?.value||'#005bb5';
  const e=document.getElementById('sw_e')?.value||'#0099ff';
  const a=document.querySelector('input[name="bg_gradient_angle"]')?.value||135;
  bar.style.background=`linear-gradient(${a}deg,${s},${e})`;
}
function showThumb(inp,id){const i=document.getElementById(id);if(!i)return;i.src=inp.value.trim();i.style.display=inp.value.trim()?'block':'none';}

// ─── Dirty state ──────────────────────────────────────────
let _dirty=false;
function markDirty(){
  _dirty=true;
  document.getElementById('dirtyDot')?.classList.add('on');
  const s=document.getElementById('saveTxt');if(s)s.textContent='Belum tersimpan...';
}
document.getElementById('stForm').addEventListener('input',markDirty);
document.getElementById('stForm').addEventListener('submit',()=>{_dirty=false;document.getElementById('dirtyDot')?.classList.remove('on');});
window.addEventListener('beforeunload',e=>{if(_dirty){e.preventDefault();e.returnValue='';}});

// ─── gv / sv ──────────────────────────────────────────────
function gv(name){
  const el=document.querySelector(`[name="${name}"]`);
  if(!el)return'';if(el.type==='checkbox')return el.checked;return el.value??'';
}
function sv(name,val){
  document.querySelectorAll(`[name="${name}"]`).forEach(el=>el.value=val);
  const fi=document.getElementById('f_'+name);if(fi)fi.value=val;
}

// ─── hbStyle ──────────────────────────────────────────────
function hbStyle(pfx,extra={}){
  const parts=[];let hasPos=false;
  [['pt','padding-top'],['pr','padding-right'],['pb','padding-bottom'],['pl','padding-left'],
   ['mt','margin-top'],['mr','margin-right'],['mb','margin-bottom'],['ml','margin-left']
  ].forEach(([s,css])=>{const v=gv(pfx+s);if(v)parts.push(`${css}:${v}`);});
  ['top','right','bottom','left'].forEach(s=>{const v=gv(pfx+s);if(v){parts.push(`${s}:${v}`);hasPos=true;}});
  if(hasPos)parts.push('position:relative');
  Object.entries(extra).forEach(([k,v])=>{if(v!=null&&v!=='')parts.push(`${k}:${v}`);});
  return parts.join(';');
}
const ANIM={'float':'animation:anim-float 3s ease-in-out infinite','bounce':'animation:anim-bounce 1.2s ease-in-out infinite','slide-left':'animation:anim-sliL .5s ease-out both','slide-right':'animation:anim-sliR .5s ease-out both','pulse':'animation:anim-pulse 1.5s ease-in-out infinite','zoom-in':'animation:anim-zoom .4s ease-out both'};
const aC=a=>ANIM[a]||'';
const eH=s=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

// ─── LIVE PREVIEW ─────────────────────────────────────────
function livePreview(){
  const type=gv('type')||'layout';
  const bgImg=gv('bg_image'),bgS=gv('bg_color_start')||'#005bb5',bgE=gv('bg_color_end')||'#0099ff',bgA=gv('bg_gradient_angle')||135;
  const bh=Math.max(60,parseInt(gv('height'))||160);
  const iL=gv('img_left'),ilW=parseInt(gv('img_left_width'))||90,ilH=parseInt(gv('img_left_height'))||0,ilA=gv('img_left_anim');
  const iR=gv('img_right'),irW=parseInt(gv('img_right_width'))||90,irH=parseInt(gv('img_right_height'))||0,irA=gv('img_right_anim');
  const ct=gv('center_type')||'text';
  const ti=gv('title'),tc=gv('title_color')||'#fff';
  const su=gv('subtitle'),sc=gv('subtitle_color')||'#ffffffd9';
  const ci=gv('center_image'),ciW=parseInt(gv('center_image_width'))||160,ciH=parseInt(gv('center_image_height'))||0,ciA=gv('center_image_anim');
  const bt=gv('btn_text'),bb=gv('btn_color')||'#FFD700',bfc=gv('btn_text_color')||'#000',bhr=gv('btn_href')||'#',ba=gv('btn_anim');
  const active=gv('is_active');

  const cv=document.getElementById('prevCanvas');
  if(cv)cv.style.background=bgImg?`url('${bgImg}') center/cover no-repeat`:`linear-gradient(${bgA}deg,${bgS},${bgE})`;

  const ov=document.getElementById('prevInactiveOv');if(ov)ov.style.display=active?'none':'';

  const strip=document.getElementById('prevStrip');
  if(strip)strip.style.cssText=hbStyle('strip_',{'padding-bottom':gv('strip_pb')||'44px'});

  const dL=document.getElementById('drgL'),pL=document.getElementById('prevLeft');
  const showL=!!(iL&&type==='layout');
  if(dL)dL.style.display=showL?'flex':'none';
  if(pL&&showL){pL.style.cssText=hbStyle('img_left_');pL.innerHTML=`<img src="${iL}" style="width:${ilW}px;${ilH>0?'height:'+ilH+'px;':'max-height:'+bh+'px;'}object-fit:contain;${aC(ilA)}"/>`;}
  else if(pL)pL.innerHTML='';

  const pC=document.getElementById('prevCenter');
  if(pC){
    pC.style.cssText=hbStyle('center_',{'min-height':bh+'px'});
    let html='';
    if(ct==='image'&&ci){
      html=`<div class="drg" id="drgCI" data-lbl="Gambar Tengah" data-pfx="center_img_" style="display:inline-block;pointer-events:auto">
        <img id="prevCenterImg" src="${ci}" style="width:${ciW}px;${ciH>0?'height:'+ciH+'px;':'max-height:'+bh+'px;'}object-fit:contain;${hbStyle('center_img_')};${aC(ciA)}"/>
        <div class="rsz" data-target="center_image"></div></div>`;
    } else {
      if(ti)html+=`<div id="prevTitle" style="color:${eH(tc)};${hbStyle('title_')}">${eH(ti)}</div>`;
      if(su)html+=`<div id="prevSub" style="color:${eH(sc)};${hbStyle('sub_')}">${eH(su)}</div>`;
    }
    if(bt)html+=`<div class="drg" id="drgBtn" data-lbl="Tombol" data-pfx="btn_" style="display:inline-block;pointer-events:auto">
      <a id="prevBtn" href="${eH(bhr)}" style="background:${eH(bb)};color:${eH(bfc)};${hbStyle('btn_')};${aC(ba)}">${eH(bt)}</a>
      <div class="rsz" data-target="btn"></div></div>`;
    pC.innerHTML=html;
    initDrg(pC);
  }

  const dR=document.getElementById('drgR'),pR=document.getElementById('prevRight');
  const showR=!!(iR&&type==='layout');
  if(dR)dR.style.display=showR?'flex':'none';
  if(pR&&showR){pR.style.cssText=hbStyle('img_right_');pR.innerHTML=`<img src="${iR}" style="width:${irW}px;${irH>0?'height:'+irH+'px;':'max-height:'+bh+'px;'}object-fit:contain;${aC(irA)}"/>`;}
  else if(pR)pR.innerHTML='';

  ['rH','rT','rC','rS','rDot'].forEach(id=>{
    const el=document.getElementById(id);if(!el)return;
    if(id==='rH')el.textContent=bh+'px';
    else if(id==='rT')el.textContent=type;
    else if(id==='rC')el.textContent=ct;
    else if(id==='rS'){el.textContent=active?'Aktif':'Nonaktif';el.style.color=active?'#059669':'#dc2626';}
    else if(id==='rDot')el.style.background=active?'#059669':'#dc2626';
  });

  initDrg(document.getElementById('prevStrip'));
  if(_selDrg)fillProps(_selDrg);
}

// ═══════════════════════════════════════════════════════════
// DRAG & RESIZE
// ═══════════════════════════════════════════════════════════
let _selDrg=null, _mode='margin';

function showCtx(drg,x,y){
  const p=document.getElementById('ctxPop');
  document.getElementById('ctxTitle').textContent=drg.dataset.lbl||'Elemen';
  updCtxPills();updCtxHint();
  p.style.display='block';
  p.style.left=Math.min(x+12,window.innerWidth-210)+'px';
  p.style.top =Math.min(y+12,window.innerHeight-130)+'px';
}
function hideCtx(){document.getElementById('ctxPop').style.display='none';}
function setMode(m){_mode=m;updCtxPills();updCtxHint();}
function updCtxPills(){document.getElementById('cpM').classList.toggle('on',_mode==='margin');document.getElementById('cpP').classList.toggle('on',_mode==='position');}
function updCtxHint(){const h=document.getElementById('ctxHint');if(h)h.textContent=_mode==='margin'?'Menggeser via margin-top/left — elemen lain terpengaruh.':'Menggeser via top/left (position:relative) — hanya visual.';}

function selDrg(drg,e){
  if(_selDrg&&_selDrg!==drg)_selDrg.classList.remove('sel');
  _selDrg=drg;drg.classList.add('sel');
  showCtx(drg,e.clientX,e.clientY);
  fillProps(drg);
}
document.addEventListener('click',e=>{
  if(!e.target.closest('.drg')&&!e.target.closest('#ctxPop')){
    if(_selDrg){_selDrg.classList.remove('sel');_selDrg=null;}
    hideCtx();emptyProps();
  }
});

// Props panel fill
function fillProps(drg){
  const pfx=drg.dataset.pfx||'';
  const lbl=drg.dataset.lbl||'Elemen';
  const pe=document.getElementById('propEl');if(pe)pe.textContent=lbl;
  const pb=document.getElementById('propBody');if(!pb)return;

  const sizeTargets={'img_left_':'img_left','img_right_':'img_right','center_img_':'center_image'};
  const hasSize=sizeTargets[pfx];

  let html='';
  [['PAD',['pt','T','pr','R','pb','B','pl','L']],
   ['MAR',['mt','T','mr','R','mb','B','ml','L']],
   ['POS',['top','T','right','R','bottom','B','left','L']]
  ].forEach(([gl,flds])=>{
    html+=`<div style="margin-bottom:8px"><div style="font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#282838;margin-bottom:3px">${gl}</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px">`;
    for(let i=0;i<flds.length;i+=2){
      const sfx=flds[i],dir=flds[i+1],col=pfx+sfx;
      const val=(document.getElementById('f_'+col)?.value||gv(col)||'');
      html+=`<div style="display:flex;flex-direction:column;align-items:center;gap:1px">
        <span style="font-size:7.5px;font-weight:700;color:#282838">${dir}</span>
        <input type="text" class="st-off-i" style="width:100%" data-col="${col}" value="${eH(val)}" placeholder="—" maxlength="10"
          oninput="writeOf(this,'${col}')"/></div>`;
    }
    html+='</div></div>';
  });

  if(hasSize){
    const t=hasSize;
    html+=`<div style="height:1px;background:#1e1e2c;margin:8px 0"></div>
    <div style="margin-bottom:6px"><div style="font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#282838;margin-bottom:3px">UKURAN</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px">
      <div style="position:relative"><input type="number" class="st-off-i" style="width:100%;padding-right:16px" value="${gv(t+'_width')||''}" min="10" max="600" oninput="writeNum(this,'${t}_width')"/><span style="position:absolute;right:5px;top:50%;transform:translateY(-50%);font-size:7.5px;color:#282838;font-family:monospace">W</span></div>
      <div style="position:relative"><input type="number" class="st-off-i" style="width:100%;padding-right:16px" value="${gv(t+'_height')||''}" min="0" max="600" placeholder="auto" oninput="writeNum(this,'${t}_height')"/><span style="position:absolute;right:5px;top:50%;transform:translateY(-50%);font-size:7.5px;color:#282838;font-family:monospace">H</span></div>
    </div></div>`;
  }
  pb.innerHTML=html;
}
function emptyProps(){
  const pe=document.getElementById('propEl');if(pe)pe.textContent='klik elemen di preview';
  const pb=document.getElementById('propBody');if(pb)pb.innerHTML='<div class="st-empty"><i class="ph ph-cursor-click"></i><p>Klik gambar / tombol di preview untuk edit offset &amp; ukuran langsung.</p></div>';
}
function writeOf(inp,col){
  const fi=document.getElementById('f_'+col);if(fi)fi.value=inp.value;
  document.querySelectorAll(`[name="${col}"]`).forEach(el=>el.value=inp.value);
  livePreview();markDirty();
}
function writeNum(inp,name){sv(name,inp.value);livePreview();markDirty();}

// Init draggers
function initDrg(container){
  (container||document).querySelectorAll('.drg').forEach(drg=>{
    if(drg._di)return;drg._di=true;
    drg.addEventListener('mousedown',e=>{
      if(e.target.classList.contains('rsz'))return;
      e.preventDefault();e.stopPropagation();
      selDrg(drg,e);
      const pfx=drg.dataset.pfx||'';
      const sx=e.clientX,sy=e.clientY;
      const sMT=parsePx(gv(pfx+'mt')),sML=parsePx(gv(pfx+'ml'));
      const sTop=parsePx(gv(pfx+'top')),sLeft=parsePx(gv(pfx+'left'));
      drg.classList.add('dragging');
      const onMove=ev=>{
        const dx=ev.clientX-sx,dy=ev.clientY-sy;
        if(_mode==='margin'){writeOff(pfx+'mt',Math.round(sMT+dy)+'px');writeOff(pfx+'ml',Math.round(sML+dx)+'px');}
        else{writeOff(pfx+'top',Math.round(sTop+dy)+'px');writeOff(pfx+'left',Math.round(sLeft+dx)+'px');}
        livePreview();markDirty();
      };
      const onUp=()=>{drg.classList.remove('dragging');document.removeEventListener('mousemove',onMove);document.removeEventListener('mouseup',onUp);};
      document.addEventListener('mousemove',onMove);document.addEventListener('mouseup',onUp);
    });
    drg.querySelectorAll('.rsz').forEach(rh=>{
      if(rh._ri)return;rh._ri=true;
      rh.addEventListener('mousedown',e=>{
        e.preventDefault();e.stopPropagation();
        const t=rh.dataset.target||'';
        const sx=e.clientX,sy=e.clientY;
        const sw=parseInt(gv(t+'_width'))||90,sh=parseInt(gv(t+'_height'))||0;
        const onMove=ev=>{
          sv(t+'_width',Math.max(10,Math.round(sw+ev.clientX-sx)));
          sv(t+'_height',Math.max(0,Math.round(sh+ev.clientY-sy)));
          livePreview();markDirty();if(_selDrg)fillProps(_selDrg);
        };
        const onUp=()=>{document.removeEventListener('mousemove',onMove);document.removeEventListener('mouseup',onUp);};
        document.addEventListener('mousemove',onMove);document.addEventListener('mouseup',onUp);
      });
    });
  });
}

function writeOff(name,val){
  const fi=document.getElementById('f_'+name);if(fi)fi.value=val;
  document.querySelectorAll(`[name="${name}"]`).forEach(el=>el.value=val);
  const ps=document.querySelector(`[data-col="${name}"]`);if(ps)ps.value=val;
}
function parsePx(s){return parseInt(String(s||'0').replace('px',''))||0;}

// ─── Init ─────────────────────────────────────────────────
window.addEventListener('load',()=>{
  updateGrad();
  const t=document.querySelector('input[name="type"]:checked')?.value||'layout';
  setType(t);
  const ct=document.querySelector('input[name="center_type"]:checked')?.value||'text';
  setCT(ct);
  livePreview();
  initDrg();
  document.querySelectorAll('.st-toast').forEach(t=>{
    setTimeout(()=>{t.style.transition='opacity .4s';t.style.opacity='0';},3000);
    setTimeout(()=>t.remove(),3400);
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>