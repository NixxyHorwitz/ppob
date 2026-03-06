<?php

/**
 * pages/inbox.php
 * Path dari htdocs/pages/ → includes & config naik 1 level
 */

$pageTitle = 'Kotak Masuk';
require_once __DIR__ . '/../includes/header.php';

// ── Mark semua notif as read ───────────────────────────────
if ($isAdmin) {
    $pdo->prepare("UPDATE notifications SET is_read = 1
                   WHERE user_id = ?
                   OR (SELECT role FROM users WHERE id = notifications.user_id) = 'user'")
        ->execute([$userId]);
} else {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
        ->execute([$userId]);
}

// ── Fetch notifications ────────────────────────────────────
$baseQuery = "SELECT n.*, u_sender.username AS sender_name
              FROM notifications n
              LEFT JOIN users u_sender
                ON n.message LIKE CONCAT('%User ID: ', u_sender.id, '%')";

if ($isAdmin) {
    $stmt = $pdo->prepare($baseQuery . "
        WHERE n.user_id = ?
           OR (SELECT role FROM users WHERE id = n.user_id) = 'user'
        ORDER BY n.created_at DESC");
} else {
    $stmt = $pdo->prepare($baseQuery . "
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC");
}
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// ── Icon & color helper ────────────────────────────────────
function inboxMeta(string $title): array
{
    $t = mb_strtolower($title);
    if (str_contains($t, 'berhasil') || str_contains($t, 'sukses'))
        return ['fas fa-circle-check', '#22c55e', '#f0fdf4'];
    if (str_contains($t, 'gagal') || str_contains($t, 'ditolak'))
        return ['fas fa-circle-xmark', '#ef4444', '#fff1f2'];
    if (str_contains($t, 'topup') || str_contains($t, 'deposit'))
        return ['fas fa-wallet', 'var(--cp)', 'var(--cpl)'];
    if (str_contains($t, 'konfirmasi') || str_contains($t, 'menunggu'))
        return ['fas fa-clock', '#f97316', '#fff7ed'];
    if (str_contains($t, 'promo') || str_contains($t, 'bonus'))
        return ['fas fa-tag', '#8b5cf6', '#fdf4ff'];
    if (str_contains($t, 'transaksi'))
        return ['fas fa-receipt', '#0ea5e9', '#f0f9ff'];
    return ['fas fa-bell', 'var(--cp)', 'var(--cpl)'];
}

function inboxTimeAgo(string $ts): string
{
    $d = time() - strtotime($ts);
    if ($d < 60)       return 'Baru saja';
    if ($d < 3600)     return (int)($d / 60) . ' mnt lalu';
    if ($d < 86400)    return (int)($d / 3600) . ' jam lalu';
    if ($d < 2592000)  return (int)($d / 86400) . ' hari lalu';
    return date('d M Y', strtotime($ts));
}
?>

<!-- ════════════════════════════════════════════
     PAGE CSS (ikut CSS variable dari header.php)
════════════════════════════════════════════ -->
<style>
    /* ── INBOX HEADER ──────────────────────────── */
    .inbox-hero {
        background: linear-gradient(145deg, var(--cpdd) 0%, var(--cpd) 45%, var(--cp) 100%);
        padding: 36px 18px 60px;
        position: relative;
        overflow: hidden;
        text-align: center;
    }

    .inbox-hero::before {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        background: rgba(255, 255, 255, .07);
        border-radius: 50%;
        top: -80px;
        right: -60px;
    }

    .inbox-hero::after {
        content: '';
        position: absolute;
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, .05);
        border-radius: 50%;
        bottom: 10px;
        left: -30px;
    }

    .inbox-hero-in {
        position: relative;
        z-index: 2;
    }

    .inbox-hero-ico {
        width: 58px;
        height: 58px;
        background: rgba(255, 255, 255, .20);
        border: 1.5px solid rgba(255, 255, 255, .30);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 24px;
        color: #fff;
    }

    .inbox-hero h1 {
        color: #fff;
        font-size: 20px;
        font-weight: 900;
        letter-spacing: -.3px;
        margin: 0 0 4px;
    }

    .inbox-hero p {
        color: rgba(255, 255, 255, .75);
        font-size: 12px;
        font-weight: 600;
        margin: 0;
    }

    /* ── FILTER BAR ─────────────────────────────── */
    .filter-wrap {
        padding: 0 14px;
        margin-top: -22px;
        position: relative;
        z-index: 10;
        margin-bottom: 12px;
    }

    .filter-bar {
        background: var(--cc);
        border-radius: 14px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 6px 24px rgba(0, 0, 0, .10);
        border: 1px solid rgba(0, 0, 0, .05);
    }

    .filter-bar i {
        color: #94a3b8;
        font-size: 13px;
    }

    .filter-bar input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-family: var(--f);
        font-size: 12px;
        font-weight: 600;
        color: var(--ct);
    }

    .filter-bar input::placeholder {
        color: #94a3b8;
    }

    .filter-count {
        background: var(--cpl);
        color: var(--cpd);
        font-size: 10px;
        font-weight: 800;
        padding: 2px 9px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* ── NOTIF LIST ─────────────────────────────── */
    .inbox-list {
        padding: 0 14px;
    }

    .ni-card {
        background: var(--cc);
        border-radius: 16px;
        padding: 13px 13px;
        display: flex;
        align-items: flex-start;
        gap: 11px;
        margin-bottom: 8px;
        border: 1px solid rgba(0, 0, 0, .05);
        box-shadow: 0 1px 6px rgba(0, 0, 0, .05);
        cursor: default;
        transition: transform .15s, box-shadow .15s;
        position: relative;
        overflow: hidden;
    }

    .ni-card:active {
        transform: scale(.985);
    }

    /* unread indicator */
    .ni-card.unread {
        border-left: 3px solid var(--cp);
    }

    .ni-card.unread::before {
        content: '';
        position: absolute;
        top: 12px;
        right: 12px;
        width: 7px;
        height: 7px;
        background: var(--cp);
        border-radius: 50%;
    }

    .ni-ico {
        width: 42px;
        height: 42px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .ni-body {
        flex: 1;
        min-width: 0;
    }

    .ni-title {
        font-size: 12.5px;
        font-weight: 800;
        color: var(--ct);
        margin-bottom: 3px;
        line-height: 1.3;
    }

    .ni-msg {
        font-size: 11px;
        color: var(--cm);
        font-weight: 500;
        line-height: 1.5;
        margin-bottom: 6px;
    }

    .ni-foot {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .ni-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--cbg);
        border: 1px solid rgba(0, 0, 0, .07);
        border-radius: 20px;
        padding: 2px 8px;
        font-size: 9.5px;
        font-weight: 700;
        color: var(--cm);
    }

    .ni-badge i {
        font-size: 9px;
    }

    .ni-time {
        font-size: 9.5px;
        font-weight: 700;
        color: #94a3b8;
        margin-left: auto;
    }

    /* ── EMPTY STATE ────────────────────────────── */
    .inbox-empty {
        text-align: center;
        padding: 48px 20px;
    }

    .inbox-empty-ico {
        width: 72px;
        height: 72px;
        background: var(--cpl);
        border-radius: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 30px;
        color: var(--cp);
    }

    .inbox-empty h3 {
        font-size: 15px;
        font-weight: 800;
        color: var(--ct);
        margin-bottom: 6px;
    }

    .inbox-empty p {
        font-size: 12px;
        color: var(--cm);
        font-weight: 500;
        margin: 0;
    }

    /* ── SECTION LABEL ──────────────────────────── */
    .inbox-date-label {
        font-size: 10px;
        font-weight: 800;
        color: var(--cm);
        text-transform: uppercase;
        letter-spacing: .6px;
        padding: 6px 0 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .inbox-date-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(0, 0, 0, .07);
    }
</style>

<!-- ════════════ HERO ════════════ -->
<div class="inbox-hero">
    <div class="inbox-hero-in">
        <div class="inbox-hero-ico">
            <i class="fas fa-inbox"></i>
        </div>
        <h1>Kotak Masuk</h1>
        <p><?= count($notifications) ?> pesan tersimpan</p>
    </div>
</div>

<!-- ════════════ FILTER BAR ════════════ -->
<div class="filter-wrap">
    <div class="filter-bar">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" id="inbox-search" placeholder="Cari notifikasi…" autocomplete="off">
        <span class="filter-count" id="notif-count"><?= count($notifications) ?> pesan</span>
    </div>
</div>

<!-- ════════════ LIST ════════════ -->
<div class="inbox-list" id="inbox-list">
    <?php if (empty($notifications)): ?>
        <div class="inbox-empty">
            <div class="inbox-empty-ico"><i class="fas fa-bell-slash"></i></div>
            <h3>Belum ada notifikasi</h3>
            <p>Semua notifikasi transaksi dan informasi akan muncul di sini.</p>
        </div>
        <?php else:
        // Group by date
        $grouped = [];
        foreach ($notifications as $n) {
            $dateKey = date('Y-m-d', strtotime($n['created_at']));
            $grouped[$dateKey][] = $n;
        }

        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        foreach ($grouped as $dateKey => $items):
            if ($dateKey === $today)          $dateLabel = 'Hari Ini';
            elseif ($dateKey === $yesterday)  $dateLabel = 'Kemarin';
            else                              $dateLabel = date('d M Y', strtotime($dateKey));
        ?>
            <div class="inbox-date-label notif-item"><?= $dateLabel ?></div>

            <?php foreach ($items as $n):
                [$ico, $col, $bg] = inboxMeta($n['title'] ?? '');

                // Clean up sender name
                $msg    = $n['message'] ?? '';
                $pelaku = !empty($n['sender_name']) ? $n['sender_name'] : 'User';
                $msg    = preg_replace('/User ID: \d+/', 'oleh ' . $pelaku, $msg);

                $unread = !(bool)$n['is_read'];
            ?>
                <div class="ni-card <?= $unread ? 'unread' : '' ?> notif-item"
                    data-title="<?= htmlspecialchars(mb_strtolower($n['title'] ?? '')) ?>"
                    data-msg="<?= htmlspecialchars(mb_strtolower($msg)) ?>">

                    <div class="ni-ico" style="background:<?= $bg ?>">
                        <i class="<?= $ico ?>" style="color:<?= $col ?>"></i>
                    </div>

                    <div class="ni-body">
                        <div class="ni-title"><?= htmlspecialchars($n['title'] ?? 'Notifikasi') ?></div>
                        <div class="ni-msg"><?= htmlspecialchars($msg) ?></div>
                        <div class="ni-foot">
                            <span class="ni-badge">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                            </span>
                            <span class="ni-time"><?= inboxTimeAgo($n['created_at']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="g20"></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
    // ── Live search ────────────────────────────────
    const searchInput = document.getElementById('inbox-search');
    const notifCount = document.getElementById('notif-count');
    const allCards = document.querySelectorAll('.ni-card');

    searchInput && searchInput.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        let visible = 0;

        allCards.forEach(card => {
            const title = card.dataset.title || '';
            const msg = card.dataset.msg || '';
            const match = !q || title.includes(q) || msg.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        // Hide date labels with no visible cards below them
        document.querySelectorAll('.inbox-date-label').forEach(label => {
            let next = label.nextElementSibling;
            let hasVisible = false;
            while (next && !next.classList.contains('inbox-date-label')) {
                if (next.classList.contains('ni-card') && next.style.display !== 'none') hasVisible = true;
                next = next.nextElementSibling;
            }
            label.style.display = hasVisible ? '' : 'none';
        });

        if (notifCount) notifCount.textContent = visible + ' pesan';
    });
</script>
</body>

</html>