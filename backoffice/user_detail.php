<?php
// backoffice/user_detail.php
// Embed endpoint — dipanggil via fetch(), mengembalikan fragment HTML modal saja.
// ob_start() di baris pertama memastikan tidak ada output apapun bocor sebelum HTML modal.

ob_start();

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

// Bersihkan semua output yang mungkin bocor dari include (whitespace, BOM, dll)
ob_clean();

// Auth check
if (empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
  ob_end_clean();
  http_response_code(403);
  header('Content-Type: text/html; charset=utf-8');
  echo '<div id="modalUserDetail" class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content mc"><div class="modal-body" style="padding:24px;text-align:center;color:#ef4444"><i class="ph ph-lock" style="font-size:32px;display:block;margin-bottom:8px"></i>Akses ditolak.</div></div></div></div>';
  exit;
}

$user_id = (int)($_GET['user_id'] ?? 0);
if (!$user_id) {
  ob_end_clean();
  http_response_code(400);
  header('Content-Type: text/html; charset=utf-8');
  echo '<div id="modalUserDetail" class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content mc"><div class="modal-body" style="padding:24px;text-align:center;color:#ef4444"><i class="ph ph-warning" style="font-size:32px;display:block;margin-bottom:8px"></i>User ID tidak valid.</div></div></div></div>';
  exit;
}

// Fetch user
$u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$u->execute([$user_id]);
$user = $u->fetch();

if (!$user) {
  ob_end_clean();
  http_response_code(404);
  header('Content-Type: text/html; charset=utf-8');
  echo '<div id="modalUserDetail" class="modal fade" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content mc"><div class="modal-body" style="padding:24px;text-align:center;color:#ef4444"><i class="ph ph-user-x" style="font-size:32px;display:block;margin-bottom:8px"></i>User tidak ditemukan.</div></div></div></div>';
  exit;
}

// Stats topup
$topup = $pdo->prepare("
    SELECT
        COUNT(*)                                               AS total_topup,
        SUM(status = 'success')                               AS topup_sukses,
        SUM(CASE WHEN status='success' THEN amount ELSE 0 END) AS total_masuk,
        SUM(status = 'pending')                               AS topup_pending,
        SUM(status = 'failed')                                AS topup_gagal,
        MAX(created_at)                                       AS last_topup
    FROM topup_history WHERE user_id = ?
");
$topup->execute([$user_id]);
$ts = $topup->fetch();

// Stats transaksi
$trx = $pdo->prepare("
    SELECT
        COUNT(*)                                               AS total_trx,
        SUM(status = 'success')                               AS trx_sukses,
        SUM(CASE WHEN status='success' THEN amount ELSE 0 END) AS total_belanja,
        SUM(status = 'failed')                                AS trx_gagal,
        MAX(created_at)                                       AS last_trx
    FROM transactions WHERE user_id = ?
");
$trx->execute([$user_id]);
$tr = $trx->fetch();

// 5 topup terakhir
$recent_topup = $pdo->prepare("
    SELECT id, amount, amount_original, payment_method, status, created_at
    FROM topup_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5
");
$recent_topup->execute([$user_id]);
$topups = $recent_topup->fetchAll();

// 5 transaksi terakhir
$recent_trx = $pdo->prepare("
    SELECT t.id, t.ref_id, t.target, t.amount, t.type, t.status, t.created_at,
           p.product_name
    FROM transactions t
    LEFT JOIN products p ON p.sku_code = t.sku_code
    WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT 5
");
$recent_trx->execute([$user_id]);
$trxs = $recent_trx->fetchAll();

// Helpers
function ud_rp(float|int|null $n): string
{
  return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
function ud_status(string $s): string
{
  return match ($s) {
    'success' => '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(16,185,129,.12);color:#10b981">Sukses</span>',
    'failed'  => '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(239,68,68,.12);color:#ef4444">Gagal</span>',
    default   => '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(245,158,11,.12);color:#f59e0b">Pending</span>',
  };
}
function ud_role(string $r): string
{
  $map = ['admin' => ['#ef4444', 'rgba(239,68,68,.12)'], 'reseller' => ['#a855f7', 'rgba(168,85,247,.12)'], 'user' => ['#3b82f6', 'rgba(59,130,246,.15)']];
  [$c, $bg] = $map[$r] ?? ['#7a90b0', 'rgba(255,255,255,.08)'];
  return "<span style=\"font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;background:$bg;color:$c\">" . ucfirst($r) . "</span>";
}

$avatar       = "https://ui-avatars.com/api/?name=" . urlencode($user['fullname'] ?: $user['username']) . "&background=3b82f6&color=fff&size=128";
$active_color = $user['is_active'] ? '#10b981' : '#ef4444';
$active_bg    = $user['is_active'] ? 'rgba(16,185,129,.12)' : 'rgba(239,68,68,.12)';
$active_label = $user['is_active'] ? 'Aktif' : 'Nonaktif';

// Bersihkan buffer sekali lagi sebelum output final
ob_clean();
header('Content-Type: text/html; charset=utf-8');
?>
<div class="modal fade" id="modalUserDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content mc" style="border-radius:16px !important;overflow:hidden">

      <!-- Header -->
      <div class="modal-header mh" style="padding:0;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:14px;padding:18px 22px;flex:1;min-width:0">
          <img src="<?= htmlspecialchars($avatar) ?>"
            style="width:52px;height:52px;border-radius:50%;border:2px solid var(--accent);object-fit:cover;flex-shrink:0" alt="" />
          <div style="min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <span style="font-size:16px;font-weight:700"><?= htmlspecialchars($user['fullname'] ?: $user['username']) ?></span>
              <?= ud_role($user['role']) ?>
              <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;background:<?= $active_bg ?>;color:<?= $active_color ?>"><?= $active_label ?></span>
            </div>
            <div style="font-size:12px;color:var(--mut);margin-top:2px">
              @<?= htmlspecialchars($user['username']) ?>
              <?php if ($user['email']): ?> · <?= htmlspecialchars($user['email']) ?><?php endif; ?>
            </div>
          </div>
        </div>
        <button type="button" class="btn-close" style="filter:invert(1);opacity:.6;margin:18px 18px 18px 0;flex-shrink:0" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" style="padding:22px;background:transparent">

        <!-- Stat boxes -->
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:20px">
          <?php
          $boxes = [
            ['ph-wallet',           '#3b82f6', 'rgba(59,130,246,.15)',  ud_rp($user['saldo']),              'Saldo saat ini'],
            ['ph-shopping-cart',    '#10b981', 'rgba(16,185,129,.12)',  ud_rp($tr['total_belanja'] ?? 0),     number_format($tr['total_trx'] ?? 0) . ' transaksi'],
            ['ph-arrow-circle-down', '#a855f7', 'rgba(168,85,247,.12)',  ud_rp($ts['total_masuk'] ?? 0),       number_format($ts['topup_sukses'] ?? 0) . ' topup sukses'],
            ['ph-calendar',         '#f59e0b', 'rgba(245,158,11,.12)',  date('d M Y', strtotime($user['created_at'])), 'Bergabung'],
          ];
          foreach ($boxes as [$ico, $clr, $bg, $val, $lbl]):
          ?>
            <div style="background:var(--hover);border:1px solid var(--border);border-radius:10px;padding:14px;display:flex;align-items:center;gap:12px">
              <div style="width:38px;height:38px;border-radius:9px;background:<?= $bg ?>;color:<?= $clr ?>;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">
                <i class="ph <?= $ico ?>"></i>
              </div>
              <div>
                <div style="font-size:15px;font-weight:700;font-family:'JetBrains Mono',monospace;color:<?= $clr ?>"><?= $val ?></div>
                <div style="font-size:11px;color:var(--mut)"><?= $lbl ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Info akun -->
        <div style="background:var(--hover);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:20px">
          <div style="padding:11px 16px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--sub);display:flex;align-items:center;gap:6px">
            <i class="ph ph-user"></i> Informasi Akun
          </div>
          <?php
          $rows = [
            ['ph-identification-card', 'NIK',     $user['nik']],
            ['ph-phone',              'Telepon',  $user['phone']],
            ['ph-envelope',           'Email',    $user['email']],
            ['ph-map-pin',            'Alamat',   $user['address']],
            ['ph-key',                'API Key',  $user['api_key'] ? substr($user['api_key'], 0, 24) . '…' : null],
            ['ph-storefront',         'Vendor',   $user['is_vendor'] ? 'Ya' : 'Tidak'],
          ];
          foreach ($rows as $i => [$ico, $lbl, $val]):
          ?>
            <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 16px;<?= $i < count($rows) - 1 ? 'border-bottom:1px solid rgba(255,255,255,.03)' : '' ?>">
              <i class="ph <?= $ico ?>" style="font-size:15px;color:var(--mut);flex-shrink:0;margin-top:1px"></i>
              <span style="font-size:12px;color:var(--mut);width:68px;flex-shrink:0"><?= $lbl ?></span>
              <span style="font-size:13px;font-weight:500;word-break:break-all"><?= $val ? htmlspecialchars($val) : '<span style="color:var(--mut)">—</span>' ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Topup terakhir -->
        <div style="background:var(--hover);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:20px">
          <div style="padding:11px 16px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--sub);display:flex;align-items:center;justify-content:space-between">
            <span style="display:flex;align-items:center;gap:6px"><i class="ph ph-arrow-circle-down"></i> Topup Terakhir</span>
            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px;background:rgba(168,85,247,.12);color:#a855f7"><?= number_format($ts['total_topup'] ?? 0) ?> total</span>
          </div>
          <?php if (empty($topups)): ?>
            <div style="padding:20px;text-align:center;color:var(--mut);font-size:12px">
              <i class="ph ph-arrow-circle-down" style="font-size:24px;display:block;opacity:.3;margin-bottom:6px"></i>Belum ada riwayat topup
            </div>
            <?php else: foreach ($topups as $i => $tp): ?>
              <div style="display:flex;align-items:center;gap:12px;padding:11px 16px;<?= $i < count($topups) - 1 ? 'border-bottom:1px solid rgba(255,255,255,.03)' : '' ?>">
                <div style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:<?= $tp['status'] === 'success' ? '#10b981' : ($tp['status'] === 'failed' ? '#ef4444' : '#f59e0b') ?>"></div>
                <div style="flex:1;min-width:0">
                  <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                    <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700"><?= ud_rp($tp['amount']) ?></span>
                    <span style="font-size:10px;font-weight:700;padding:1px 7px;border-radius:99px;background:rgba(255,255,255,.06);color:var(--sub)"><?= htmlspecialchars($tp['payment_method'] ?? 'QRIS') ?></span>
                    <?= ud_status($tp['status']) ?>
                  </div>
                  <div style="font-size:11px;color:var(--mut);margin-top:2px"><?= date('d M Y H:i', strtotime($tp['created_at'])) ?></div>
                </div>
                <?php $fee = ($tp['amount'] ?? 0) - ($tp['amount_original'] ?? 0);
                if ($fee > 0): ?>
                  <div style="font-size:11px;color:var(--mut);text-align:right;flex-shrink:0">
                    <?= ud_rp($tp['amount_original']) ?><br>
                    <span style="color:var(--ok)">+<?= ud_rp($fee) ?> fee</span>
                  </div>
                <?php endif; ?>
              </div>
          <?php endforeach;
          endif; ?>
        </div>

        <!-- Transaksi terakhir -->
        <div style="background:var(--hover);border:1px solid var(--border);border-radius:10px;overflow:hidden">
          <div style="padding:11px 16px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--sub);display:flex;align-items:center;justify-content:space-between">
            <span style="display:flex;align-items:center;gap:6px"><i class="ph ph-swap"></i> Transaksi Terakhir</span>
            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px;background:rgba(16,185,129,.12);color:#10b981"><?= number_format($tr['total_trx'] ?? 0) ?> total</span>
          </div>
          <?php if (empty($trxs)): ?>
            <div style="padding:20px;text-align:center;color:var(--mut);font-size:12px">
              <i class="ph ph-swap" style="font-size:24px;display:block;opacity:.3;margin-bottom:6px"></i>Belum ada riwayat transaksi
            </div>
            <?php else: foreach ($trxs as $i => $tx): ?>
              <div style="display:flex;align-items:center;gap:12px;padding:11px 16px;<?= $i < count($trxs) - 1 ? 'border-bottom:1px solid rgba(255,255,255,.03)' : '' ?>">
                <div style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:<?= $tx['status'] === 'success' ? '#10b981' : ($tx['status'] === 'failed' ? '#ef4444' : '#f59e0b') ?>"></div>
                <div style="flex:1;min-width:0">
                  <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                    <span style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px"><?= htmlspecialchars($tx['product_name'] ?: $tx['ref_id'] ?: '#' . $tx['id']) ?></span>
                    <?php if ($tx['target']): ?><span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--sub)"><?= htmlspecialchars($tx['target']) ?></span><?php endif; ?>
                    <?= ud_status($tx['status']) ?>
                  </div>
                  <div style="font-size:11px;color:var(--mut);margin-top:2px"><?= date('d M Y H:i', strtotime($tx['created_at'])) ?></div>
                </div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;flex-shrink:0"><?= ud_rp($tx['amount']) ?></div>
              </div>
          <?php endforeach;
          endif; ?>
        </div>

      </div><!-- /modal-body -->

      <div class="modal-footer mh" style="padding:14px 22px;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div style="font-size:11px;color:var(--mut)">
          User ID: <span style="font-family:'JetBrains Mono',monospace;color:var(--sub)">#<?= $user_id ?></span>
          <?php if ($ts['last_topup']): ?> · Topup terakhir: <?= date('d M Y', strtotime($ts['last_topup'])) ?><?php endif; ?>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <a href="users.php?edit=<?= $user_id ?>"
            style="font-size:13px;font-weight:600;padding:7px 16px;border-radius:8px;background:var(--as);border:1px solid var(--ba);color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:6px">
            <i class="ph ph-pencil-simple"></i> Edit User
          </a>
          <button type="button" data-bs-dismiss="modal"
            style="font-size:13px;font-weight:500;padding:7px 16px;border-radius:8px;background:var(--hover);border:1px solid var(--border);color:var(--sub);cursor:pointer">
            Tutup
          </button>
        </div>
      </div>

    </div>
  </div>
</div>
<?php
// Ambil buffer bersih dan kirim
$output = ob_get_clean();
echo $output;
?>