<?php

/**
 * pages/profile/index.php
 * Halaman profil user — view only
 * Path: /htdocs/pages/profile/index.php
 */

$pageTitle = 'Profil Saya';
require_once __DIR__ . '/../../includes/header.php';

// ── Fetch user ─────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die('User tidak ditemukan');

// ── Stats ──────────────────────────────────────────────────
try {
    $stTrx = $pdo->prepare("SELECT COUNT(*) FROM topup_history WHERE user_id = ? AND status = 'success'");
    $stTrx->execute([$userId]);
    $totalTrx = (int)$stTrx->fetchColumn();

    $stNominal = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM topup_history WHERE user_id = ? AND status = 'success'");
    $stNominal->execute([$userId]);
    $totalNominal = (int)$stNominal->fetchColumn();

    $stMonth = $pdo->prepare("SELECT COUNT(*) FROM topup_history WHERE user_id = ? AND status = 'success' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
    $stMonth->execute([$userId]);
    $trxMonth = (int)$stMonth->fetchColumn();
} catch (Exception $e) {
    $totalTrx = $totalNominal = $trxMonth = 0;
}

// ── Unread notif ───────────────────────────────────────────
$stUnread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stUnread->execute([$userId]);
$unreadNotif = (int)$stUnread->fetchColumn();

// ── Avatar ─────────────────────────────────────────────────
$avatar = !empty($user['image']) && $user['image'] !== 'default.png'
    ? '/uploads/profile/' . $user['image']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['fullname'] ?: $user['username']) . '&background=01d298&color=fff&size=200&bold=true';

$displayName = $user['fullname'] ?: $user['username'];
$roleLabel   = ['admin' => 'Administrator', 'reseller' => 'Reseller', 'user' => 'Member'][$user['role']] ?? 'Member';
$roleColor   = ['admin' => '#ef4444', 'reseller' => '#8b5cf6', 'user' => 'var(--cp)'][$user['role']] ?? 'var(--cp)';
?>

<style>
    /* ── PROFILE HERO ───────────────────────────── */
    .ph {
        background: linear-gradient(145deg, var(--cpdd) 0%, var(--cpd) 45%, var(--cp) 100%);
        padding: 44px 18px 70px;
        position: relative;
        overflow: hidden;
        text-align: center;
    }

    .ph::before {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        background: rgba(255, 255, 255, .07);
        border-radius: 50%;
        top: -80px;
        right: -60px;
    }

    .ph::after {
        content: '';
        position: absolute;
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, .05);
        border-radius: 50%;
        bottom: 10px;
        left: -30px;
    }

    .ph-in {
        position: relative;
        z-index: 2
    }

    .ph-avatar-wrap {
        position: relative;
        display: inline-block;
        margin-bottom: 12px;
    }

    .ph-avatar {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 255, 255, .40);
        box-shadow: 0 6px 24px rgba(0, 0, 0, .20);
    }

    .ph-online {
        position: absolute;
        bottom: 4px;
        right: 4px;
        width: 16px;
        height: 16px;
        background: #22c55e;
        border: 2.5px solid #fff;
        border-radius: 50%;
    }

    .ph-name {
        color: #fff;
        font-size: 18px;
        font-weight: 900;
        letter-spacing: -.3px;
        margin-bottom: 3px;
    }

    .ph-username {
        color: rgba(255, 255, 255, .72);
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .ph-badges {
        display: flex;
        justify-content: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .ph-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: rgba(255, 255, 255, .20);
        border: 1px solid rgba(255, 255, 255, .28);
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 10px;
        font-weight: 800;
        color: #fff;
    }

    /* ── STATS ROW ──────────────────────────────── */
    .ps-wrap {
        padding: 0 14px;
        margin-top: -38px;
        position: relative;
        z-index: 10;
        margin-bottom: 12px;
    }

    .ps-card {
        background: var(--cc);
        border-radius: 18px;
        padding: 16px 12px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        box-shadow: 0 8px 32px rgba(0, 0, 0, .11);
        border: 1px solid rgba(0, 0, 0, .05);
    }

    .ps-item {
        text-align: center;
        padding: 4px 8px;
        border-right: 1px solid #f1f5f9;
    }

    .ps-item:last-child {
        border-right: none;
    }

    .ps-val {
        font-size: 17px;
        font-weight: 900;
        color: var(--ct);
        letter-spacing: -.5px;
        margin-bottom: 2px;
    }

    .ps-lbl {
        font-size: 9.5px;
        font-weight: 700;
        color: var(--cm);
    }

    /* ── SECTION ────────────────────────────────── */
    .sec {
        padding: 0 14px 12px;
    }

    .sec-title {
        font-size: 11px;
        font-weight: 800;
        color: var(--cm);
        text-transform: uppercase;
        letter-spacing: .6px;
        padding: 12px 0 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sec-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(0, 0, 0, .07);
    }

    /* ── INFO CARD ──────────────────────────────── */
    .info-card {
        background: var(--cc);
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, .05);
        box-shadow: 0 1px 6px rgba(0, 0, 0, .05);
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 14px;
        border-bottom: 1px solid #f4f6f9;
        transition: background .15s;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-ico {
        width: 36px;
        height: 36px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }

    .info-key {
        font-size: 10px;
        font-weight: 700;
        color: var(--cm);
        margin-bottom: 2px;
    }

    .info-val {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--ct);
    }

    .info-val.muted {
        color: var(--cm);
        font-weight: 500;
        font-style: italic;
    }

    /* ── ACTION BUTTONS ─────────────────────────── */
    .action-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 9px;
    }

    .act-btn {
        background: var(--cc);
        border: 1px solid rgba(0, 0, 0, .06);
        border-radius: 14px;
        padding: 14px 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 7px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, .05);
        transition: transform .15s, box-shadow .15s;
        text-decoration: none;
        -webkit-tap-highlight-color: transparent;
    }

    .act-btn:active {
        transform: scale(.95);
    }

    .act-ico {
        width: 42px;
        height: 42px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .act-lbl {
        font-size: 11px;
        font-weight: 800;
        color: var(--ct);
        text-align: center;
    }

    .act-sub {
        font-size: 9.5px;
        font-weight: 500;
        color: var(--cm);
        text-align: center;
        margin-top: -4px;
    }

    /* ── SALDO STRIP ────────────────────────────── */
    .saldo-strip {
        background: linear-gradient(135deg, var(--cpdd), var(--cp));
        border-radius: 16px;
        padding: 16px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 20px rgba(1, 210, 152, .25);
        margin-bottom: 12px;
    }

    .ss-left {}

    .ss-lbl {
        color: rgba(255, 255, 255, .75);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: 3px;
    }

    .ss-val {
        color: #fff;
        font-size: 22px;
        font-weight: 900;
        letter-spacing: -1px;
    }

    .ss-right {
        display: flex;
        gap: 8px;
    }

    .ss-btn {
        background: rgba(255, 255, 255, .20);
        border: 1px solid rgba(255, 255, 255, .28);
        border-radius: 11px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        text-decoration: none;
        transition: background .2s;
    }

    .ss-btn:active {
        background: rgba(255, 255, 255, .35);
        color: #fff;
    }
</style>

<!-- ════════════ PROFILE HERO ════════════ -->
<div class="ph">
    <div class="ph-in">
        <div class="ph-avatar-wrap">
            <img src="<?= $avatar ?>" class="ph-avatar" alt="Avatar">
            <div class="ph-online"></div>
        </div>
        <div class="ph-name"><?= htmlspecialchars($displayName) ?></div>
        <div class="ph-username">@<?= htmlspecialchars($user['username']) ?></div>
        <div class="ph-badges">
            <span class="ph-badge" style="background:<?= $roleColor ?>44;border-color:<?= $roleColor ?>66">
                <i class="fas fa-shield-halved"></i> <?= $roleLabel ?>
            </span>
            <?php if ($user['is_active']): ?>
                <span class="ph-badge"><i class="fas fa-circle-check"></i> Aktif</span>
            <?php else: ?>
                <span class="ph-badge" style="background:rgba(239,68,68,.25)"><i class="fas fa-circle-xmark"></i> Nonaktif</span>
            <?php endif; ?>
            <?php if (!empty($user['nik'])): ?>
                <span class="ph-badge"><i class="fas fa-id-card"></i> Terverifikasi</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ════════════ STATS ════════════ -->
<div class="ps-wrap">
    <div class="ps-card">
        <div class="ps-item">
            <div class="ps-val"><?= $totalTrx ?></div>
            <div class="ps-lbl">Total Trx</div>
        </div>
        <div class="ps-item">
            <div class="ps-val"><?= $trxMonth ?></div>
            <div class="ps-lbl">Trx Bulan Ini</div>
        </div>
        <div class="ps-item">
            <div class="ps-val"><?= $unreadNotif ?></div>
            <div class="ps-lbl">Notif Baru</div>
        </div>
    </div>
</div>

<div class="sec">

    <!-- SALDO -->
    <div class="saldo-strip">
        <div class="ss-left">
            <div class="ss-lbl">Saldo Aktif</div>
            <div class="ss-val" id="saldo-val"><span style="letter-spacing:4px;opacity:.8">••••••</span></div>
        </div>
        <div class="ss-right">
            <button class="ss-btn" onclick="toggleSaldo()" id="saldo-tog"><i class="fas fa-eye" id="saldo-ico"></i></button>
            <a href="/pages/topup" class="ss-btn"><i class="fas fa-plus"></i></a>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="action-grid" style="margin-bottom:12px">
        <a href="/pages/profile/edit" class="act-btn">
            <div class="act-ico" style="background:var(--cpl)"><i class="fas fa-pen" style="color:var(--cpd)"></i></div>
            <div class="act-lbl">Edit Profil</div>
            <div class="act-sub">Ubah data & foto</div>
        </a>
        <a href="/pages/inbox" class="act-btn">
            <div class="act-ico" style="background:#fff7ed"><i class="fas fa-bell" style="color:#f97316"></i></div>
            <div class="act-lbl">Notifikasi</div>
            <div class="act-sub"><?= $unreadNotif ?> belum dibaca</div>
        </a>
        <a href="/modules/user/riwayat" class="act-btn">
            <div class="act-ico" style="background:#f0f9ff"><i class="fas fa-receipt" style="color:#0ea5e9"></i></div>
            <div class="act-lbl">Riwayat Trx</div>
            <div class="act-sub">Lihat semua transaksi</div>
        </a>
        <a href="/logout" class="act-btn">
            <div class="act-ico" style="background:#fff1f2"><i class="fas fa-arrow-right-from-bracket" style="color:#ef4444"></i></div>
            <div class="act-lbl">Keluar</div>
            <div class="act-sub">Logout dari akun</div>
        </a>
    </div>

    <!-- INFO AKUN -->
    <div class="sec-title">Informasi Akun</div>
    <div class="info-card">
        <div class="info-row">
            <div class="info-ico" style="background:var(--cpl)"><i class="fas fa-envelope" style="color:var(--cpd)"></i></div>
            <div>
                <div class="info-key">Email</div>
                <div class="info-val"><?= htmlspecialchars($user['email'] ?: '-') ?></div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-ico" style="background:#f0f9ff"><i class="fas fa-phone" style="color:#0ea5e9"></i></div>
            <div>
                <div class="info-key">No. HP</div>
                <div class="info-val <?= empty($user['phone']) ? 'muted' : '' ?>"><?= htmlspecialchars($user['phone'] ?: 'Belum diisi') ?></div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-ico" style="background:#fdf4ff"><i class="fas fa-id-card" style="color:#8b5cf6"></i></div>
            <div>
                <div class="info-key">NIK</div>
                <div class="info-val <?= empty($user['nik']) ? 'muted' : '' ?>"><?= !empty($user['nik']) ? substr($user['nik'], 0, 4) . '••••••••' . substr($user['nik'], -4) : 'Belum diisi' ?></div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-ico" style="background:#fff7ed"><i class="fas fa-location-dot" style="color:#f97316"></i></div>
            <div>
                <div class="info-key">Alamat</div>
                <div class="info-val <?= empty($user['address']) ? 'muted' : '' ?>"><?= htmlspecialchars($user['address'] ?: 'Belum diisi') ?></div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-ico" style="background:#f0fdf4"><i class="fas fa-calendar-alt" style="color:#22c55e"></i></div>
            <div>
                <div class="info-key">Bergabung Sejak</div>
                <div class="info-val"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
            </div>
        </div>
    </div>

</div>

<div class="g20"></div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    const _saldo = 'Rp <?= number_format($user['saldo'] ?? 0, 0, ',', '.') ?>';
    let _sHidden = true;

    function toggleSaldo() {
        const v = document.getElementById('saldo-val');
        const i = document.getElementById('saldo-ico');
        _sHidden
            ?
            (v.innerHTML = '<span>' + _saldo + '</span>', i.className = 'fas fa-eye-slash') :
            (v.innerHTML = '<span style="letter-spacing:4px;opacity:.8">••••••</span>', i.className = 'fas fa-eye');
        _sHidden = !_sHidden;
    }
</script>
</body>

</html>