<?php
// backoffice/notifications.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Manage Notifikasi';
$active_menu = 'notifications';

// ══ ACTIONS ══════════════════════════════════════════════════
$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

// ── Push Notif ke user tertentu / semua ──────────────────────
if ($action === 'push') {
  $target     = $_POST['target'] ?? 'specific'; // specific | all | role
  $user_ids   = $_POST['user_ids'] ?? [];
  $role_target = trim($_POST['role_target'] ?? '');
  $title      = trim($_POST['title']   ?? '');
  $message    = trim($_POST['message'] ?? '');

  if (!$title) {
    $toast_e = 'Judul notifikasi wajib diisi.';
  } elseif (!$message) {
    $toast_e = 'Pesan notifikasi wajib diisi.';
  } else {
    $recipients = [];

    if ($target === 'all') {
      $rows = $pdo->query("SELECT id FROM users WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
      $recipients = $rows;
    } elseif ($target === 'role' && $role_target) {
      $st = $pdo->prepare("SELECT id FROM users WHERE role = ? AND is_active = 1");
      $st->execute([$role_target]);
      $recipients = $st->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($target === 'specific' && !empty($user_ids)) {
      // sanitize: only ints
      $recipients = array_map('intval', (array)$user_ids);
      $recipients = array_filter($recipients);
    }

    if (empty($recipients)) {
      $toast_e = 'Tidak ada penerima yang dipilih.';
    } else {
      $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
      foreach ($recipients as $uid) {
        $stmt->execute([$uid, $title, $message]);
      }
      $count  = count($recipients);
      $toast  = "Notifikasi berhasil dikirim ke $count pengguna.";
    }
  }
}

// ── Hapus satu ───────────────────────────────────────────────
if ($action === 'delete' && !empty($_POST['id'])) {
  $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([(int)$_POST['id']]);
  $toast = 'Notifikasi berhasil dihapus.';
}

// ── Hapus semua hasil filter / semua ─────────────────────────
if ($action === 'delete_bulk' && !empty($_POST['ids'])) {
  $ids = array_map('intval', explode(',', $_POST['ids']));
  $ids = array_filter($ids);
  if ($ids) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM notifications WHERE id IN ($ph)")->execute($ids);
    $toast = count($ids) . ' notifikasi berhasil dihapus.';
  }
}

// ── Hapus semua ──────────────────────────────────────────────
if ($action === 'delete_all') {
  $pdo->exec("DELETE FROM notifications");
  $toast = 'Semua notifikasi berhasil dihapus.';
}

// ══ FETCH STATS ══════════════════════════════════════════════
$stats = $pdo->query("
    SELECT
        COUNT(*)                    AS total,
        SUM(is_read = 0)            AS unread,
        SUM(is_read = 1)            AS read_count,
        COUNT(DISTINCT user_id)     AS unique_users
    FROM notifications
")->fetch();

// ══ FETCH USERS (untuk dropdown push) ════════════════════════
$all_users = $pdo->query("
    SELECT id, username, fullname, email, role
    FROM users
    WHERE is_active = 1
    ORDER BY role, username
")->fetchAll();

// ══ FILTER & LIST ════════════════════════════════════════════
$q        = trim($_GET['q']      ?? '');
$f_uid    = trim($_GET['uid']    ?? '');
$f_read   = $_GET['is_read']     ?? '';
$page     = max(1, (int)($_GET['p'] ?? 1));
$per_page = 25;

$where = [];
$params = [];
if ($q) {
  $where[] = "(n.title LIKE ? OR n.message LIKE ? OR u.username LIKE ? OR u.fullname LIKE ?)";
  $s = "%$q%";
  array_push($params, $s, $s, $s, $s);
}
if ($f_uid !== '') {
  $where[] = "n.user_id = ?";
  $params[] = (int)$f_uid;
}
if ($f_read !== '') {
  $where[] = "n.is_read = ?";
  $params[] = (int)$f_read;
}

$wsql  = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// total for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications n LEFT JOIN users u ON n.user_id = u.id $wsql");
$count_stmt->execute($params);
$total_rows = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT n.*, u.username, u.fullname, u.role AS user_role, u.image AS user_image
    FROM notifications n
    LEFT JOIN users u ON n.user_id = u.id
    $wsql
    ORDER BY n.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$notifs = $stmt->fetchAll();

// Collect all IDs on current page for bulk delete
$page_ids = array_column($notifs, 'id');

$qs = http_build_query(array_filter(['q' => $q, 'uid' => $f_uid, 'is_read' => $f_read]));

// Role badge helper
function role_badge(string $role): string
{
  $map = [
    'admin'    => ['bd-pur', 'ph-shield-star', 'Admin'],
    'reseller' => ['bd-acc', 'ph-storefront',  'Reseller'],
    'user'     => ['bd-ok',  'ph-user',         'User'],
  ];
  [$cls, $ic, $lbl] = $map[$role] ?? ['bd-ok', 'ph-user', $role];
  return "<span class=\"bd $cls\"><i class=\"ph $ic\"></i> $lbl</span>";
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ══ TOAST ══ -->
<div class="toast-wrap">
  <?php if ($toast):   ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>
  <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<!-- ══ PAGE HEADER ══ -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
  <div>
    <h1>Notifikasi</h1>
    <nav>
      <ol class="breadcrumb bc">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Notifikasi</li>
      </ol>
    </nav>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#mDeleteAll"
      style="border-radius:8px">
      <i class="ph ph-trash me-1"></i> Hapus Semua
    </button>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mPush"
      style="border-radius:8px">
      <i class="ph ph-paper-plane-tilt me-1"></i> Push Notifikasi
    </button>
  </div>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="sc blue">
      <div class="si blue"><i class="ph-fill ph-bell"></i></div>
      <div class="sv"><?= number_format($stats['total']) ?></div>
      <div class="sl">Total Notifikasi</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="sc orange">
      <div class="si orange"><i class="ph-fill ph-bell-ringing"></i></div>
      <div class="sv"><?= number_format($stats['unread']) ?></div>
      <div class="sl">Belum Dibaca</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="sc green">
      <div class="si green"><i class="ph-fill ph-check-circle"></i></div>
      <div class="sv"><?= number_format($stats['read_count']) ?></div>
      <div class="sl">Sudah Dibaca</div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="sc purple">
      <div class="si purple"><i class="ph-fill ph-users"></i></div>
      <div class="sv"><?= number_format($stats['unique_users']) ?></div>
      <div class="sl">Pengguna Unik</div>
    </div>
  </div>
</div>

<!-- ══ TABLE CARD ══ -->
<div class="card-c">
  <div class="ch">
    <div>
      <p class="ct">Daftar Notifikasi</p>
      <p class="cs">
        <?= number_format($total_rows) ?> notifikasi
        <?= $q ? "· cari: <strong style='color:var(--accent)'>" . htmlspecialchars($q) . "</strong>" : '' ?>
      </p>
    </div>
    <?php if (!empty($page_ids)): ?>
      <form method="POST" onsubmit="return confirm('Hapus semua notifikasi di halaman ini?')">
        <input type="hidden" name="action" value="delete_bulk" />
        <input type="hidden" name="ids" value="<?= implode(',', $page_ids) ?>" />
        <button type="submit" class="btn btn-sm"
          style="border-radius:7px;background:var(--es);border:1px solid rgba(239,68,68,.2);color:var(--err);font-size:12px">
          <i class="ph ph-trash me-1"></i>Hapus Halaman Ini
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Filter bar -->
  <div class="cb pb-0">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
      <div style="position:relative;flex:1;min-width:180px">
        <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mut);font-size:16px;pointer-events:none"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="fi"
          placeholder="Cari judul, pesan, username…" style="width:100%" />
      </div>
      <select name="uid" class="fs">
        <option value="">Semua User</option>
        <?php foreach ($all_users as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $f_uid == $u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['username'] . ' — ' . ($u['fullname'] ?: $u['email'])) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="is_read" class="fs">
        <option value="">Semua Status</option>
        <option value="0" <?= $f_read === '0' ? 'selected' : '' ?>>Belum Dibaca</option>
        <option value="1" <?= $f_read === '1' ? 'selected' : '' ?>>Sudah Dibaca</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm" style="border-radius:7px;padding:8px 16px">
        <i class="ph ph-funnel me-1"></i>Filter
      </button>
      <?php if ($q || $f_uid || $f_read !== ''): ?>
        <a href="notifications.php" class="btn btn-sm"
          style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub);padding:8px 14px">
          <i class="ph ph-x me-1"></i>Reset
        </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Table -->
  <div class="cb">
    <?php if (empty($notifs)): ?>
      <div class="text-center py-5" style="color:var(--mut)">
        <i class="ph ph-bell-slash" style="font-size:48px;display:block;margin-bottom:10px;opacity:.4"></i>
        <div style="font-size:14px;font-weight:600;margin-bottom:4px">Tidak ada notifikasi</div>
        <div style="font-size:12px">Gunakan tombol <strong>Push Notifikasi</strong> untuk mengirim</div>
      </div>
    <?php else: ?>

      <!-- Desktop Table -->
      <div class="table-responsive d-none d-lg-block">
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:48px">#</th>
              <th>Penerima</th>
              <th>Judul</th>
              <th>Pesan</th>
              <th style="width:110px">Status</th>
              <th style="width:140px">Waktu</th>
              <th style="width:70px;text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($notifs as $n): ?>
              <tr style="<?= !$n['is_read'] ? 'background:rgba(59,130,246,.04)' : '' ?>">

                <!-- ID -->
                <td><span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--mut)">#<?= $n['id'] ?></span></td>

                <!-- Penerima -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--as);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px">
                      <i class="ph ph-user" style="color:var(--accent)"></i>
                    </div>
                    <div>
                      <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($n['username'] ?? 'User #' . $n['user_id']) ?></div>
                      <div style="font-size:11px;color:var(--mut)"><?= role_badge($n['user_role'] ?? 'user') ?></div>
                    </div>
                  </div>
                </td>

                <!-- Judul -->
                <td>
                  <div style="font-weight:600;font-size:13px;color:var(--text);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?php if (!$n['is_read']): ?>
                      <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--accent);margin-right:5px;vertical-align:middle;flex-shrink:0"></span>
                    <?php endif; ?>
                    <?= htmlspecialchars($n['title'] ?? '—') ?>
                  </div>
                </td>

                <!-- Pesan -->
                <td style="max-width:280px">
                  <div style="font-size:12.5px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px"
                    title="<?= htmlspecialchars($n['message'] ?? '') ?>">
                    <?= htmlspecialchars($n['message'] ?? '—') ?>
                  </div>
                </td>

                <!-- Status -->
                <td>
                  <?php if ($n['is_read']): ?>
                    <span class="bd bd-ok"><i class="ph ph-check-circle"></i> Dibaca</span>
                  <?php else: ?>
                    <span class="bd bd-war"><i class="ph ph-bell-ringing"></i> Belum Dibaca</span>
                  <?php endif; ?>
                </td>

                <!-- Waktu -->
                <td style="font-size:11.5px;color:var(--mut);white-space:nowrap">
                  <div><?= date('d M Y', strtotime($n['created_at'])) ?></div>
                  <div style="font-family:'JetBrains Mono',monospace"><?= date('H:i:s', strtotime($n['created_at'])) ?></div>
                </td>

                <!-- Aksi -->
                <td>
                  <div class="d-flex justify-content-center gap-1">
                    <!-- Detail -->
                    <button type="button" class="ab" title="Lihat Detail"
                      onclick="showDetail(<?= htmlspecialchars(json_encode([
                                            'id'      => $n['id'],
                                            'user'    => $n['username'] ?? 'User #' . $n['user_id'],
                                            'role'    => $n['user_role'] ?? 'user',
                                            'title'   => $n['title'],
                                            'message' => $n['message'],
                                            'is_read' => $n['is_read'],
                                            'time'    => date('d M Y, H:i:s', strtotime($n['created_at'])),
                                          ]), ENT_QUOTES) ?>)">
                      <i class="ph ph-eye"></i>
                    </button>
                    <!-- Hapus -->
                    <form method="POST" style="display:inline"
                      onsubmit="return confirm('Hapus notifikasi ini?')">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="id" value="<?= $n['id'] ?>" />
                      <?php if ($qs): ?><input type="hidden" name="_qs" value="<?= htmlspecialchars($qs) ?>"><?php endif; ?>
                      <button type="submit" class="ab red" title="Hapus">
                        <i class="ph ph-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="d-lg-none">
        <?php foreach ($notifs as $n): ?>
          <div style="border:1px solid var(--border);border-radius:var(--r);padding:14px;margin-bottom:10px;
                      <?= !$n['is_read'] ? 'border-color:rgba(59,130,246,.25);background:rgba(59,130,246,.03)' : '' ?>">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
              <div>
                <?php if (!$n['is_read']): ?>
                  <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--accent);margin-right:4px;vertical-align:middle"></span>
                <?php endif; ?>
                <span style="font-weight:700;font-size:13px"><?= htmlspecialchars($n['title'] ?? '—') ?></span>
              </div>
              <?php if ($n['is_read']): ?>
                <span class="bd bd-ok" style="flex-shrink:0"><i class="ph ph-check-circle"></i> Dibaca</span>
              <?php else: ?>
                <span class="bd bd-war" style="flex-shrink:0"><i class="ph ph-bell-ringing"></i> Belum</span>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--sub);margin-bottom:10px"><?= htmlspecialchars($n['message'] ?? '—') ?></div>
            <div class="d-flex align-items-center justify-content-between">
              <div style="font-size:11px;color:var(--mut)">
                <i class="ph ph-user me-1"></i><?= htmlspecialchars($n['username'] ?? 'User #' . $n['user_id']) ?>
                &nbsp;·&nbsp;
                <?= date('d M Y H:i', strtotime($n['created_at'])) ?>
              </div>
              <form method="POST" onsubmit="return confirm('Hapus notifikasi ini?')">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?= $n['id'] ?>" />
                <button type="submit" class="btn btn-sm"
                  style="border-radius:7px;background:var(--es);border:1px solid rgba(239,68,68,.2);color:var(--err);font-size:11px;padding:4px 10px">
                  <i class="ph ph-trash me-1"></i>Hapus
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="d-flex align-items-center justify-content-between mt-4 flex-wrap gap-2">
          <div style="font-size:12px;color:var(--mut)">
            Halaman <?= $page ?> dari <?= $total_pages ?> · <?= number_format($total_rows) ?> total
          </div>
          <div class="d-flex gap-1">
            <?php
            $base = 'notifications.php?' . ($qs ? $qs . '&' : '');
            $start = max(1, $page - 2);
            $end   = min($total_pages, $page + 2);
            ?>
            <?php if ($page > 1): ?>
              <a href="<?= $base ?>p=<?= $page - 1 ?>" class="pag-btn"><i class="ph ph-caret-left"></i></a>
            <?php endif; ?>
            <?php for ($i = $start; $i <= $end; $i++): ?>
              <a href="<?= $base ?>p=<?= $i ?>"
                class="pag-btn <?= $i === $page ? 'pag-active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
              <a href="<?= $base ?>p=<?= $page + 1 ?>" class="pag-btn"><i class="ph ph-caret-right"></i></a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</div>

<!-- ══ MODAL: PUSH NOTIFIKASI ══ -->
<div class="modal fade" id="mPush" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content mc">
      <div class="modal-header mh">
        <h5 class="modal-title">
          <i class="ph ph-paper-plane-tilt me-2" style="color:var(--accent)"></i>Push Notifikasi
        </h5>
        <button type="button" class="btn-close" style="filter:invert(1)" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="push" />
        <div class="modal-body">

          <!-- Target -->
          <div class="mb-4">
            <label class="ml mb-2">Target Penerima</label>
            <div class="d-flex flex-wrap gap-2" id="targetCards">
              <label class="tgt-card active" for="tgtSpecific">
                <input type="radio" name="target" value="specific" id="tgtSpecific" checked class="d-none" />
                <i class="ph ph-user-circle" style="font-size:22px;color:var(--accent)"></i>
                <div style="font-size:12px;font-weight:600;margin-top:4px">User Tertentu</div>
              </label>
              <label class="tgt-card" for="tgtRole">
                <input type="radio" name="target" value="role" id="tgtRole" class="d-none" />
                <i class="ph ph-users-three" style="font-size:22px;color:var(--ok)"></i>
                <div style="font-size:12px;font-weight:600;margin-top:4px">Per Role</div>
              </label>
              <label class="tgt-card" for="tgtAll">
                <input type="radio" name="target" value="all" id="tgtAll" class="d-none" />
                <i class="ph ph-broadcast" style="font-size:22px;color:var(--war)"></i>
                <div style="font-size:12px;font-weight:600;margin-top:4px">Semua User</div>
              </label>
            </div>
          </div>

          <!-- Specific user select -->
          <div id="specificFields" class="mb-3">
            <label class="ml">Pilih User <span style="color:var(--err)">*</span></label>
            <div class="position-relative mb-2">
              <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mut);font-size:15px;pointer-events:none;z-index:1"></i>
              <input type="text" id="userSearch" class="fi" placeholder="Ketik username untuk mencari…"
                style="padding-left:34px;width:100%" oninput="filterUsers(this.value)" />
            </div>
            <div id="userList" style="max-height:200px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--rs);background:var(--hover)">
              <?php foreach ($all_users as $u): ?>
                <label class="user-opt" for="u<?= $u['id'] ?>">
                  <input type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>" id="u<?= $u['id'] ?>" class="user-chk" />
                  <div class="d-flex align-items-center gap-2 flex-1">
                    <div style="width:28px;height:28px;border-radius:50%;background:var(--as);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                      <i class="ph ph-user" style="color:var(--accent);font-size:14px"></i>
                    </div>
                    <div>
                      <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($u['username']) ?></div>
                      <div style="font-size:11px;color:var(--mut)"><?= htmlspecialchars($u['fullname'] ?: $u['email']) ?> · <?= ucfirst($u['role']) ?></div>
                    </div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
            <div id="selectedCount" style="font-size:11px;color:var(--mut);margin-top:6px">0 user dipilih</div>
            <div class="d-flex gap-2 mt-2">
              <button type="button" class="btn btn-sm"
                style="border-radius:6px;background:var(--hover);border:1px solid var(--border);color:var(--sub);font-size:11px"
                onclick="selectAllUsers(true)">Pilih Semua</button>
              <button type="button" class="btn btn-sm"
                style="border-radius:6px;background:var(--hover);border:1px solid var(--border);color:var(--sub);font-size:11px"
                onclick="selectAllUsers(false)">Batal Semua</button>
            </div>
          </div>

          <!-- Role select -->
          <div id="roleFields" class="mb-3" style="display:none">
            <label class="ml">Pilih Role <span style="color:var(--err)">*</span></label>
            <div class="d-flex gap-2 flex-wrap mt-1">
              <label class="role-opt" for="roleAdmin">
                <input type="radio" name="role_target" value="admin" id="roleAdmin" class="d-none" />
                <i class="ph ph-shield-star" style="font-size:18px"></i> Admin
              </label>
              <label class="role-opt" for="roleReseller">
                <input type="radio" name="role_target" value="reseller" id="roleReseller" class="d-none" />
                <i class="ph ph-storefront" style="font-size:18px"></i> Reseller
              </label>
              <label class="role-opt" for="roleUser">
                <input type="radio" name="role_target" value="user" id="roleUser" class="d-none" />
                <i class="ph ph-user" style="font-size:18px"></i> User
              </label>
            </div>
          </div>

          <!-- All user warning -->
          <div id="allWarning" class="mb-3" style="display:none">
            <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);border-radius:var(--rs);padding:12px 14px;font-size:12.5px;color:var(--war)">
              <i class="ph ph-warning me-2"></i>
              Notifikasi akan dikirim ke <strong>seluruh pengguna aktif</strong>. Pastikan pesan sudah benar sebelum mengirim.
            </div>
          </div>

          <hr style="border-color:var(--border);margin:20px 0" />

          <!-- Konten notifikasi -->
          <div class="mb-3">
            <label class="ml">Judul Notifikasi <span style="color:var(--err)">*</span></label>
            <input type="text" name="title" class="fi w-100" placeholder="Contoh: Promo Spesial Hari Ini!" maxlength="255" />
          </div>

          <!-- Quick template -->
          <div class="mb-3">
            <label class="ml">Template Cepat</label>
            <div class="d-flex flex-wrap gap-2">
              <?php
              $templates = [
                ['🎉 Promo', 'Promo Spesial!', 'Dapatkan diskon eksklusif hari ini. Jangan sampai ketinggalan!'],
                ['🔔 Info', 'Informasi Sistem', 'Ada pembaruan sistem penting. Harap perhatikan notifikasi ini.'],
                ['⚠️ Maintenance', 'Maintenance Terjadwal', 'Sistem akan melakukan maintenance. Mohon maaf atas ketidaknyamanannya.'],
                ['💰 Saldo', 'Saldo Anda Berhasil Diisi', 'Saldo akun Anda telah berhasil diperbarui.'],
              ];
              foreach ($templates as $t): ?>
                <button type="button" class="tpl-btn"
                  onclick="fillTemplate(<?= htmlspecialchars(json_encode($t[1]), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($t[2]), ENT_QUOTES) ?>)">
                  <?= $t[0] ?>
                </button>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="ml">Pesan <span style="color:var(--err)">*</span></label>
            <textarea name="message" id="msgArea" class="fi w-100" rows="4"
              placeholder="Tulis pesan notifikasi di sini…" maxlength="1000"
              style="resize:vertical" oninput="updateCharCount(this)"></textarea>
            <div style="font-size:11px;color:var(--mut);text-align:right;margin-top:4px">
              <span id="charCount">0</span>/1000
            </div>
          </div>

        </div>
        <div class="modal-footer mf">
          <button type="button" class="btn btn-sm"
            style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)"
            data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px">
            <i class="ph ph-paper-plane-tilt me-1"></i>Kirim Notifikasi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ══ MODAL: DETAIL NOTIFIKASI ══ -->
<div class="modal fade" id="mDetail" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content mc">
      <div class="modal-header mh">
        <h5 class="modal-title">
          <i class="ph ph-bell me-2" style="color:var(--accent)"></i>Detail Notifikasi
        </h5>
        <button type="button" class="btn-close" style="filter:invert(1)" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailBody">
        <!-- filled by JS -->
      </div>
      <div class="modal-footer mf">
        <button type="button" class="btn btn-sm"
          style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)"
          data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ══ MODAL: HAPUS SEMUA ══ -->
<div class="modal fade" id="mDeleteAll" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content mc">
      <div class="modal-header mh">
        <h5 class="modal-title" style="color:var(--err)">
          <i class="ph ph-warning me-2"></i>Hapus Semua
        </h5>
        <button type="button" class="btn-close" style="filter:invert(1)" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-3">
        <div style="font-size:42px;margin-bottom:12px">🗑️</div>
        <div style="font-weight:700;font-size:14px;margin-bottom:6px">Hapus semua notifikasi?</div>
        <div style="font-size:12px;color:var(--mut)">Tindakan ini tidak dapat dibatalkan. Seluruh <strong style="color:var(--err)"><?= number_format($stats['total']) ?></strong> notifikasi akan dihapus permanen.</div>
      </div>
      <div class="modal-footer mf">
        <button type="button" class="btn btn-sm"
          style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)"
          data-bs-dismiss="modal">Batal</button>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="delete_all" />
          <button type="submit" class="btn btn-sm btn-danger" style="border-radius:7px">
            <i class="ph ph-trash me-1"></i>Hapus Semua
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
  /* ── Extra styles for this page ── */
  .tgt-card {
    flex: 1;
    min-width: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 14px 10px;
    border: 2px solid var(--border);
    border-radius: var(--r);
    cursor: pointer;
    transition: all .2s;
    text-align: center;
    background: var(--hover);
  }

  .tgt-card.active {
    border-color: var(--accent);
    background: var(--as);
  }

  .tgt-card:hover {
    border-color: var(--accent);
  }

  .user-opt {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    cursor: pointer;
    transition: background .15s;
    border-bottom: 1px solid var(--border);
  }

  .user-opt:last-child {
    border-bottom: none;
  }

  .user-opt:hover {
    background: rgba(59, 130, 246, .07);
  }

  .user-chk {
    width: 16px;
    height: 16px;
    accent-color: var(--accent);
    flex-shrink: 0;
  }

  .user-opt .flex-1 {
    flex: 1;
  }

  .role-opt {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    border: 2px solid var(--border);
    border-radius: var(--rs);
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: var(--sub);
    transition: all .2s;
    background: var(--hover);
  }

  .role-opt:has(input:checked),
  .role-opt.active {
    border-color: var(--accent);
    background: var(--as);
    color: var(--accent);
  }

  .role-opt:hover {
    border-color: var(--accent);
  }

  .tpl-btn {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: var(--hover);
    border: 1px solid var(--border);
    color: var(--sub);
    cursor: pointer;
    transition: all .18s;
  }

  .tpl-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
  }

  .pag-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    background: var(--hover);
    border: 1px solid var(--border);
    color: var(--sub);
    text-decoration: none;
    transition: all .15s;
  }

  .pag-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
  }

  .pag-active {
    background: var(--accent) !important;
    border-color: var(--accent) !important;
    color: #fff !important;
  }

  .ml {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--sub);
    display: block;
    margin-bottom: 6px;
  }

  .bd-war {
    background: rgba(245, 158, 11, .12);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, .25);
  }
</style>

<?php
$page_scripts = <<<'SCRIPT'
<script>
// ── Target radio toggle ──────────────────────────────────────
document.querySelectorAll('input[name="target"]').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.tgt-card').forEach(c => c.classList.remove('active'));
    this.closest('.tgt-card').classList.add('active');

    const v = this.value;
    document.getElementById('specificFields').style.display = v === 'specific' ? 'block' : 'none';
    document.getElementById('roleFields').style.display     = v === 'role'     ? 'block' : 'none';
    document.getElementById('allWarning').style.display     = v === 'all'      ? 'block' : 'none';
  });
});

// ── User search filter ───────────────────────────────────────
function filterUsers(q) {
  const lower = q.toLowerCase();
  document.querySelectorAll('.user-opt').forEach(opt => {
    const text = opt.textContent.toLowerCase();
    opt.style.display = text.includes(lower) ? '' : 'none';
  });
}

// ── Select / deselect all users ──────────────────────────────
function selectAllUsers(state) {
  document.querySelectorAll('.user-chk').forEach(c => c.checked = state);
  updateSelectedCount();
}

function updateSelectedCount() {
  const n = document.querySelectorAll('.user-chk:checked').length;
  document.getElementById('selectedCount').textContent = n + ' user dipilih';
}
document.querySelectorAll('.user-chk').forEach(c => {
  c.addEventListener('change', updateSelectedCount);
});

// ── Template fill ────────────────────────────────────────────
function fillTemplate(title, msg) {
  document.querySelector('[name="title"]').value   = title;
  document.getElementById('msgArea').value          = msg;
  updateCharCount(document.getElementById('msgArea'));
}

// ── Char counter ─────────────────────────────────────────────
function updateCharCount(el) {
  document.getElementById('charCount').textContent = el.value.length;
}

// ── Detail modal ─────────────────────────────────────────────
function showDetail(data) {
  const readBadge = data.is_read
    ? '<span class="bd bd-ok"><i class="ph ph-check-circle"></i> Sudah Dibaca</span>'
    : '<span class="bd bd-war"><i class="ph ph-bell-ringing"></i> Belum Dibaca</span>';

  document.getElementById('detailBody').innerHTML = `
    <div style="display:flex;flex-direction:column;gap:14px">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:11px;color:var(--mut);font-family:'JetBrains Mono',monospace">#${data.id}</span>
        ${readBadge}
      </div>
      <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:4px">Penerima</div>
        <div style="font-weight:600;font-size:13.5px">${data.user}</div>
        <div style="font-size:11px;color:var(--mut)">${data.role}</div>
      </div>
      <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:4px">Judul</div>
        <div style="font-weight:700;font-size:14px">${data.title ?? '—'}</div>
      </div>
      <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:4px">Pesan</div>
        <div style="background:var(--hover);border:1px solid var(--border);border-radius:var(--rs);padding:12px;font-size:13px;line-height:1.6;white-space:pre-wrap">${data.message ?? '—'}</div>
      </div>
      <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);margin-bottom:4px">Waktu</div>
        <div style="font-size:12.5px;font-family:'JetBrains Mono',monospace;color:var(--sub)">${data.time}</div>
      </div>
    </div>
  `;
  new bootstrap.Modal(document.getElementById('mDetail')).show();
}

// ── Auto dismiss toast ───────────────────────────────────────
document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => t.style.opacity = '0', 3500);
  setTimeout(() => t.remove(), 4000);
});
</script>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>