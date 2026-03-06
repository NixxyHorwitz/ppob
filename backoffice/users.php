<?php
// backoffice/users.php

// ✅ WAJIB PERTAMA — sebelum output apapun
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Manage Users';
$active_menu = 'users';

require_once __DIR__ . '/includes/header.php';

// ══ HELPERS ══════════════════════════════════════════════════
function fmt_rp(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function time_ago(string $ts): string {
    $d = time() - strtotime($ts);
    if ($d < 60)     return 'Baru saja';
    if ($d < 3600)   return floor($d / 60)   . ' mnt lalu';
    if ($d < 86400)  return floor($d / 3600)  . ' jam lalu';
    if ($d < 604800) return floor($d / 86400) . ' hari lalu';
    return date('d M Y', strtotime($ts));
}

// ══ ACTIONS ══════════════════════════════════════════════════
$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

if ($action === 'toggle_active' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    if ($id === $admin_id) { $toast_e = 'Tidak dapat mengubah status diri sendiri.'; }
    else {
        $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        $toast = 'Status berhasil diubah.';
    }
}

if ($action === 'reset_pin' && !empty($_POST['id'])) {
    $pdo->prepare("UPDATE users SET pin = '123456' WHERE id = ?")->execute([(int)$_POST['id']]);
    $toast = 'PIN direset ke 123456.';
}

if ($action === 'saldo' && !empty($_POST['id'])) {
    $id     = (int)$_POST['id'];
    $jumlah = abs((float)($_POST['jumlah'] ?? 0));
    $tipe   = ($_POST['tipe'] ?? '') === 'kurang' ? 'kurang' : 'tambah';
    if ($jumlah <= 0) {
        $toast_e = 'Jumlah tidak valid.';
    } elseif ($tipe === 'kurang') {
        $r = $pdo->prepare("SELECT saldo FROM users WHERE id = ?"); $r->execute([$id]);
        $cur = (float)$r->fetchColumn();
        if ($jumlah > $cur) { $toast_e = 'Saldo tidak cukup.'; }
        else { $pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id = ?")->execute([$jumlah, $id]); $toast = 'Saldo dikurangi ' . fmt_rp($jumlah) . '.'; }
    } else {
        $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?")->execute([$jumlah, $id]);
        $toast = 'Saldo ditambah ' . fmt_rp($jumlah) . '.';
    }
}

$edit_errors = [];
if ($action === 'edit' && !empty($_POST['id'])) {
    $id        = (int)$_POST['id'];
    $fullname  = trim($_POST['fullname']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $nik       = trim($_POST['nik']       ?? '');
    $address   = trim($_POST['address']   ?? '');
    $role      = in_array($_POST['role'] ?? '', ['admin','reseller','user']) ? $_POST['role'] : 'user';
    $is_vendor = isset($_POST['is_vendor']) ? 1 : 0;

    if (!$fullname) $edit_errors[] = 'Nama lengkap wajib diisi.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $edit_errors[] = 'Email tidak valid.';
    if (!$edit_errors && $email) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->execute([$email, $id]);
        if ($chk->fetch()) $edit_errors[] = 'Email sudah dipakai user lain.';
    }
    if (!$edit_errors) {
        $xsql = ''; $p = [$fullname, $email, $phone, $nik, $address, $role, $is_vendor];
        if (!empty($_POST['new_password']) && strlen($_POST['new_password']) >= 6) {
            $xsql = ', password = ?'; $p[] = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        }
        $p[] = $id;
        $pdo->prepare("UPDATE users SET fullname=?,email=?,phone=?,nik=?,address=?,role=?,is_vendor=?$xsql WHERE id=?")->execute($p);
        $toast = 'User berhasil diupdate.';
    } else {
        $toast_e = implode(' · ', $edit_errors);
    }
}

$add_errors = [];
if ($action === 'add') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $role     = in_array($_POST['role'] ?? '', ['admin','reseller','user']) ? $_POST['role'] : 'user';
    $password = $_POST['password'] ?? '';

    if (!$username) $add_errors[] = 'Username wajib diisi.';
    if (!$fullname) $add_errors[] = 'Nama lengkap wajib diisi.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $add_errors[] = 'Email tidak valid.';
    if (strlen($password) < 6) $add_errors[] = 'Password min. 6 karakter.';
    if (!$add_errors) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?"); $chk->execute([$username]);
        if ($chk->fetch()) $add_errors[] = 'Username sudah digunakan.';
    }
    if (!$add_errors) {
        $pdo->prepare("INSERT INTO users (username,fullname,email,phone,password,role,is_active,saldo,pin) VALUES (?,?,?,?,?,?,1,0,'123456')")
            ->execute([$username,$fullname,$email,$phone,password_hash($password,PASSWORD_BCRYPT),$role]);
        $toast = 'User baru berhasil ditambahkan.';
    } else {
        $toast_e = implode(' · ', $add_errors);
    }
}

if ($action === 'delete' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    if ($id === $admin_id) { $toast_e = 'Tidak dapat menghapus akun sendiri.'; }
    else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$id]);
        if ($stmt->rowCount()) $toast = 'User berhasil dihapus.';
        else $toast_e = 'Admin tidak bisa dihapus.';
    }
}

// ══ FETCH ════════════════════════════════════════════════════
$stats = $pdo->query("
    SELECT COUNT(*) total, SUM(is_active=1) aktif,
           SUM(role='reseller') resellers, COALESCE(SUM(saldo),0) total_saldo
    FROM users
")->fetch();

$q        = trim($_GET['q']      ?? '');
$f_role   = trim($_GET['role']   ?? '');
$f_status = $_GET['status'] ?? '';
$f_vendor = $_GET['vendor'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where = []; $params = [];
if ($q) { $where[] = "(username LIKE ? OR fullname LIKE ? OR email LIKE ? OR phone LIKE ? OR nik LIKE ?)"; $s="%$q%"; array_push($params,$s,$s,$s,$s,$s); }
if ($f_role)        { $where[] = "role = ?";      $params[] = $f_role; }
if ($f_status!=='') { $where[] = "is_active = ?"; $params[] = (int)$f_status; }
if ($f_vendor!=='') { $where[] = "is_vendor = ?"; $params[] = (int)$f_vendor; }
$wsql  = $where ? 'WHERE '.implode(' AND ',$where) : '';

$cnt   = $pdo->prepare("SELECT COUNT(*) FROM users $wsql"); $cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, ceil($total / $per_page));
$offset= ($page-1) * $per_page;

$stmt  = $pdo->prepare("SELECT * FROM users $wsql ORDER BY id DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params); $users = $stmt->fetchAll();

$edit_user  = null;
$saldo_user = null;
if (!empty($_GET['edit']))  { $eu=$pdo->prepare("SELECT * FROM users WHERE id=?"); $eu->execute([(int)$_GET['edit']]);  $edit_user  = $eu->fetch(); }
if (!empty($_GET['saldo'])) { $su=$pdo->prepare("SELECT id,username,fullname,saldo FROM users WHERE id=?"); $su->execute([(int)$_GET['saldo']]); $saldo_user = $su->fetch(); }

$rc_map = ['admin'=>['l'=>'Admin','c'=>'bd-err'],'reseller'=>['l'=>'Reseller','c'=>'bd-warn'],'user'=>['l'=>'User','c'=>'bd-acc']];
$qs = http_build_query(array_filter(['q'=>$q,'role'=>$f_role,'status'=>$f_status,'vendor'=>$f_vendor,'page'=>$page]));
?>

<!-- PAGE HEADER -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h1>Manage Users</h1>
    <nav><ol class="breadcrumb bc">
      <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
      <li class="breadcrumb-item active">Users</li>
    </ol></nav>
  </div>
  <button class="btn btn-primary" style="border-radius:8px" data-bs-toggle="modal" data-bs-target="#mAdd">
    <i class="ph ph-user-plus me-1"></i> Tambah User
  </button>
</div>

<!-- STATS -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6"><div class="sc blue"><div class="si blue"><i class="ph-fill ph-users"></i></div><div class="sv"><?= $stats['total'] ?></div><div class="sl">Total User</div></div></div>
  <div class="col-xl-3 col-sm-6"><div class="sc green"><div class="si green"><i class="ph-fill ph-user-check"></i></div><div class="sv"><?= $stats['aktif'] ?></div><div class="sl">Aktif</div></div></div>
  <div class="col-xl-3 col-sm-6"><div class="sc orange"><div class="si orange"><i class="ph-fill ph-storefront"></i></div><div class="sv"><?= $stats['resellers'] ?></div><div class="sl">Reseller</div></div></div>
  <div class="col-xl-3 col-sm-6"><div class="sc purple"><div class="si purple"><i class="ph-fill ph-currency-dollar"></i></div><div class="sv" style="font-size:18px"><?= fmt_rp((float)$stats['total_saldo']) ?></div><div class="sl">Total Saldo</div></div></div>
</div>

<!-- TABLE -->
<div class="card-c">
  <div class="ch">
    <div><p class="ct">Daftar User</p><p class="cs"><?= $total ?> user<?= $q?" · cari: <strong style='color:var(--accent)'>".htmlspecialchars($q)."</strong>":'' ?></p></div>
  </div>
  <div class="cb pb-0">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
      <div style="position:relative;flex:1;min-width:180px">
        <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mut);font-size:16px;pointer-events:none"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="fi" placeholder="Cari username, nama, email, NIK…" style="width:100%"/>
      </div>
      <select name="role" class="fs"><option value="">Semua Role</option><option value="admin" <?=$f_role==='admin'?'selected':''?>>Admin</option><option value="reseller" <?=$f_role==='reseller'?'selected':''?>>Reseller</option><option value="user" <?=$f_role==='user'?'selected':''?>>User</option></select>
      <select name="status" class="fs"><option value="">Semua Status</option><option value="1" <?=$f_status==='1'?'selected':''?>>Aktif</option><option value="0" <?=$f_status==='0'?'selected':''?>>Nonaktif</option></select>
      <select name="vendor" class="fs"><option value="">Semua</option><option value="1" <?=$f_vendor==='1'?'selected':''?>>Vendor</option><option value="0" <?=$f_vendor==='0'?'selected':''?>>Non-Vendor</option></select>
      <button type="submit" class="btn btn-primary btn-sm" style="border-radius:7px;padding:8px 16px"><i class="ph ph-funnel me-1"></i>Filter</button>
      <?php if ($q||$f_role||$f_status!==''||$f_vendor!==''): ?><a href="users.php" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub);padding:8px 14px"><i class="ph ph-x me-1"></i>Reset</a><?php endif; ?>
    </form>
  </div>
  <div class="cb">
    <div class="table-responsive">
      <table class="tbl">
        <thead><tr><th>ID</th><th>User</th><th>NIK</th><th>Phone</th><th>Role</th><th>Saldo</th><th>Status</th><th>Vendor</th><th>Bergabung</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="10" class="text-center py-5" style="color:var(--mut)"><i class="ph ph-users" style="font-size:36px;display:block;margin-bottom:8px"></i>Tidak ada user ditemukan</td></tr>
        <?php else: foreach ($users as $u):
          $rc = $rc_map[$u['role']] ?? $rc_map['user'];
          $is_me = $u['id'] == $admin_id; ?>
          <tr>
            <td><span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--mut)">#<?=$u['id']?></span></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <img src="https://ui-avatars.com/api/?name=<?=urlencode($u['fullname']?:$u['username'])?>&background=1a2540&color=3b82f6&size=64"
                     style="width:34px;height:34px;border-radius:8px;<?=!$u['is_active']?'filter:grayscale(1);opacity:.5':''?>"/>
                <div>
                  <div style="font-weight:600;font-size:13.5px"><?=htmlspecialchars($u['fullname']?:$u['username'])?><?php if($is_me):?> <span class="bd bd-acc" style="font-size:9px">Anda</span><?php endif;?></div>
                  <div style="font-size:11px;color:var(--mut)">@<?=htmlspecialchars($u['username'])?><?=$u['email']?" · ".htmlspecialchars($u['email']):''?></div>
                </div>
              </div>
            </td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--sub)"><?=$u['nik']?htmlspecialchars($u['nik']):'<span style="color:var(--mut)">—</span>'?></td>
            <td style="font-size:12px;color:var(--sub)"><?=$u['phone']?htmlspecialchars($u['phone']):'<span style="color:var(--mut)">—</span>'?></td>
            <td><span class="bd <?=$rc['c']?>"><?=$rc['l']?></span></td>
            <td><span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:<?=$u['saldo']>0?'var(--ok)':'var(--mut)'?>"><?=fmt_rp((float)$u['saldo'])?></span></td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_active"/>
                <input type="hidden" name="id" value="<?=$u['id']?>"/>
                <button type="submit" class="bd <?=$u['is_active']?'bd-ok':'bd-err'?>" style="border:none;cursor:<?=$is_me?'default':'pointer'?>" <?=$is_me?'disabled':''?>>
                  <i class="ph <?=$u['is_active']?'ph-check-circle':'ph-x-circle'?>"></i><?=$u['is_active']?'Aktif':'Nonaktif'?>
                </button>
              </form>
            </td>
            <td><?=$u['is_vendor']?'<span class="bd bd-warn"><i class="ph ph-check"></i> Vendor</span>':'<span style="color:var(--mut)">—</span>'?></td>
            <td style="font-size:12px;color:var(--mut)"><?=time_ago($u['created_at'])?></td>
            <td>
              <div class="d-flex gap-1">
                <a href="?edit=<?=$u['id']?>&<?=$qs?>" class="ab" title="Edit"><i class="ph ph-pencil-simple"></i></a>
                <a href="?saldo=<?=$u['id']?>&<?=$qs?>" class="ab green" title="Kelola Saldo"><i class="ph ph-currency-dollar"></i></a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Reset PIN <?=addslashes(htmlspecialchars($u['username']))?>?')">
                  <input type="hidden" name="action" value="reset_pin"/><input type="hidden" name="id" value="<?=$u['id']?>"/>
                  <button type="submit" class="ab" title="Reset PIN"><i class="ph ph-lock-key"></i></button>
                </form>
                <?php if (!$is_me): ?>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Hapus <?=addslashes(htmlspecialchars($u['username']))?>?')">
                    <input type="hidden" name="action" value="delete"/><input type="hidden" name="id" value="<?=$u['id']?>"/>
                    <button type="submit" class="ab red" title="Hapus"><i class="ph ph-trash"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
      <div class="d-flex align-items-center justify-content-between mt-4 flex-wrap gap-2">
        <div style="font-size:12px;color:var(--mut)">Menampilkan <?=$offset+1?>–<?=min($offset+$per_page,$total)?> dari <?=$total?></div>
        <div class="d-flex gap-1">
          <a href="?<?=http_build_query(array_merge($_GET,['page'=>max(1,$page-1)]))?>" class="pg <?=$page<=1?'dis':''?>"><i class="ph ph-caret-left"></i></a>
          <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++): ?><a href="?<?=http_build_query(array_merge($_GET,['page'=>$p]))?>" class="pg <?=$p==$page?'active':''?>"><?=$p?></a><?php endfor;?>
          <a href="?<?=http_build_query(array_merge($_GET,['page'=>min($pages,$page+1)]))?>" class="pg <?=$page>=$pages?'dis':''?>"><i class="ph ph-caret-right"></i></a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- TOAST -->
<div class="toast-wrap">
  <?php if ($toast):   ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?=htmlspecialchars($toast)?></div><?php endif;?>
  <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?=htmlspecialchars($toast_e)?></div><?php endif;?>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="mAdd" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content mc">
  <div class="modal-header mh"><h5 class="modal-title"><i class="ph ph-user-plus me-2" style="color:var(--accent)"></i>Tambah User</h5><button type="button" class="btn-close" style="filter:invert(1)" data-bs-dismiss="modal"></button></div>
  <form method="POST"><input type="hidden" name="action" value="add"/>
    <div class="modal-body">
      <?php if ($add_errors): ?><div class="mb-3 p-3" style="background:var(--es);border:1px solid rgba(239,68,68,.2);border-radius:8px;color:var(--err);font-size:13px"><?=implode('<br>',array_map('htmlspecialchars',$add_errors))?></div><?php endif;?>
      <div class="row g-3">
        <div class="col-md-6"><label class="fl">Username *</label><input type="text" name="username" class="fc" required placeholder="username"/></div>
        <div class="col-md-6"><label class="fl">Nama Lengkap *</label><input type="text" name="fullname" class="fc" required placeholder="Nama Lengkap"/></div>
        <div class="col-12"><label class="fl">Email</label><input type="email" name="email" class="fc" placeholder="email@domain.com"/></div>
        <div class="col-md-6"><label class="fl">No. Telepon</label><input type="text" name="phone" class="fc" placeholder="08xx"/></div>
        <div class="col-md-6"><label class="fl">Role *</label><select name="role" class="fc"><option value="user">User</option><option value="reseller">Reseller</option><option value="admin">Admin</option></select></div>
        <div class="col-12"><label class="fl">Password * (min. 6 karakter)</label><input type="password" name="password" class="fc" required placeholder="••••••••"/></div>
      </div>
    </div>
    <div class="modal-footer mf">
      <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">Batal</button>
      <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px"><i class="ph ph-user-plus me-1"></i>Tambah</button>
    </div>
  </form>
</div></div></div>

<!-- MODAL EDIT -->
<?php if ($edit_user): ?>
<div class="modal fade" id="mEdit" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content mc">
  <div class="modal-header mh"><h5 class="modal-title"><i class="ph ph-pencil-simple me-2" style="color:var(--accent)"></i>Edit — @<?=htmlspecialchars($edit_user['username'])?></h5><a href="users.php?<?=$qs?>" class="btn-close" style="filter:invert(1)"></a></div>
  <form method="POST"><input type="hidden" name="action" value="edit"/><input type="hidden" name="id" value="<?=$edit_user['id']?>"/>
    <div class="modal-body">
      <?php if ($edit_errors): ?><div class="mb-3 p-3" style="background:var(--es);border:1px solid rgba(239,68,68,.2);border-radius:8px;color:var(--err);font-size:13px"><?=implode('<br>',array_map('htmlspecialchars',$edit_errors))?></div><?php endif;?>
      <div class="row g-3">
        <div class="col-md-6"><label class="fl">Username</label><input class="fc" value="<?=htmlspecialchars($edit_user['username'])?>" disabled style="opacity:.5"/></div>
        <div class="col-md-6"><label class="fl">API Key</label><input class="fc" value="<?=$edit_user['api_key']?htmlspecialchars(substr($edit_user['api_key'],0,20)).'…':'—'?>" disabled style="opacity:.5;font-size:11px;font-family:'JetBrains Mono',monospace"/></div>
        <div class="col-md-6"><label class="fl">Nama Lengkap *</label><input type="text" name="fullname" class="fc" value="<?=htmlspecialchars($edit_user['fullname'])?>" required/></div>
        <div class="col-md-6"><label class="fl">Email</label><input type="email" name="email" class="fc" value="<?=htmlspecialchars($edit_user['email'])?>"/></div>
        <div class="col-md-6"><label class="fl">No. Telepon</label><input type="text" name="phone" class="fc" value="<?=htmlspecialchars($edit_user['phone'])?>"/></div>
        <div class="col-md-6"><label class="fl">NIK</label><input type="text" name="nik" class="fc" value="<?=htmlspecialchars($edit_user['nik'])?>" maxlength="20"/></div>
        <div class="col-12"><label class="fl">Alamat</label><textarea name="address" class="fc" rows="2"><?=htmlspecialchars($edit_user['address'])?></textarea></div>
        <div class="col-md-6"><label class="fl">Role</label><select name="role" class="fc"><option value="user" <?=$edit_user['role']==='user'?'selected':''?>>User</option><option value="reseller" <?=$edit_user['role']==='reseller'?'selected':''?>>Reseller</option><option value="admin" <?=$edit_user['role']==='admin'?'selected':''?>>Admin</option></select></div>
        <div class="col-md-6"><label class="fl">PIN Saat Ini</label><input class="fc" value="<?=htmlspecialchars($edit_user['pin'])?>" disabled style="opacity:.5;font-family:'JetBrains Mono',monospace;letter-spacing:4px"/></div>
        <div class="col-12"><label class="fl">Ganti Password <span style="color:var(--mut)">(kosongkan jika tidak diganti)</span></label><input type="password" name="new_password" class="fc" placeholder="Min. 6 karakter"/></div>
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_vendor" id="chkVendor" <?=$edit_user['is_vendor']?'checked':''?>/><label class="form-check-label" for="chkVendor" style="color:var(--sub);font-size:13px">Tandai sebagai Vendor</label></div></div>
      </div>
    </div>
    <div class="modal-footer mf"><a href="users.php?<?=$qs?>" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">Batal</a><button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px"><i class="ph ph-floppy-disk me-1"></i>Simpan</button></div>
  </form>
</div></div></div>
<?php endif; ?>

<!-- MODAL SALDO -->
<?php if ($saldo_user): ?>
<div class="modal fade" id="mSaldo" tabindex="-1"><div class="modal-dialog modal-dialog-centered" style="max-width:400px"><div class="modal-content mc">
  <div class="modal-header mh"><h5 class="modal-title"><i class="ph ph-currency-dollar me-2" style="color:var(--ok)"></i>Kelola Saldo</h5><a href="users.php?<?=$qs?>" class="btn-close" style="filter:invert(1)"></a></div>
  <form method="POST"><input type="hidden" name="action" value="saldo"/><input type="hidden" name="id" value="<?=$saldo_user['id']?>"/>
    <div class="modal-body">
      <div class="d-flex align-items-center gap-3 p-3 mb-4 rounded" style="background:var(--hover);border:1px solid var(--border)">
        <img src="https://ui-avatars.com/api/?name=<?=urlencode($saldo_user['fullname'])?>&background=1a2540&color=3b82f6&size=64" style="width:40px;height:40px;border-radius:8px"/>
        <div><div style="font-weight:600"><?=htmlspecialchars($saldo_user['fullname'])?></div><div style="font-size:12px;color:var(--mut)">@<?=htmlspecialchars($saldo_user['username'])?></div></div>
        <div style="margin-left:auto;text-align:right"><div style="font-size:10px;color:var(--mut)">Saldo saat ini</div><div style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--ok)"><?=fmt_rp((float)$saldo_user['saldo'])?></div></div>
      </div>
      <div class="row g-3">
        <div class="col-12"><label class="fl">Jenis Operasi</label><select name="tipe" class="fc" id="saldoTipe"><option value="tambah">➕ Tambah Saldo</option><option value="kurang">➖ Kurangi Saldo</option></select></div>
        <div class="col-12"><label class="fl">Jumlah (Rp)</label><input type="number" name="jumlah" class="fc" placeholder="0" min="1" required style="font-family:'JetBrains Mono',monospace"/></div>
      </div>
    </div>
    <div class="modal-footer mf"><a href="users.php?<?=$qs?>" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">Batal</a><button type="submit" class="btn btn-sm btn-success" style="border-radius:7px" id="saldoBtn"><i class="ph ph-check me-1"></i>Terapkan</button></div>
  </form>
</div></div></div>
<?php endif; ?>

<?php
$page_scripts = '<script>
' . ($edit_user  ? 'new bootstrap.Modal(document.getElementById("mEdit")).show();'  : '') . '
' . ($saldo_user ? 'new bootstrap.Modal(document.getElementById("mSaldo")).show();' : '') . '
' . ($add_errors ? 'new bootstrap.Modal(document.getElementById("mAdd")).show();'   : '') . '
var st = document.getElementById("saldoTipe");
if (st) st.addEventListener("change", function() {
    var btn = document.getElementById("saldoBtn");
    btn.className = "btn btn-sm btn-" + (this.value==="tambah"?"success":"warning") + " rounded-2";
    btn.innerHTML = "<i class=\"ph ph-"+(this.value==="tambah"?"plus":"minus")+" me-1\"></i>"+(this.value==="tambah"?"Tambah":"Kurangi");
});
</script>';

require_once __DIR__ . '/includes/footer.php';
?>