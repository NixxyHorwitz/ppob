<?php

/**
 * includes/footer.php
 * ─────────────────────────────────────────────
 * Include di PALING BAWAH setiap halaman, sebelum </body>.
 *
 * Yang ditangani:
 *  - Bottom navigation bar (dari tabel navbar_items)
 *  - Footer desktop (dari site_settings)
 *  - Bootstrap JS + jQuery
 *  - Live chat widget
 *
 * Variabel yang dibutuhkan (sudah tersedia via header.php):
 *  $pdo, $cfg, $brandName, $isAdmin
 */

// Pastikan $pdo & $cfg tersedia jika footer di-include tanpa header
if (!isset($pdo)) {
    require_once dirname(__DIR__) . '/config/database.php';
}
if (!isset($cfg)) {
    if (!function_exists('loadSettings')) {
        function loadSettings(PDO $pdo, string $table): array
        {
            try {
                $s = $pdo->query("SELECT setting_key, setting_value FROM $table");
                $r = [];
                foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $r[$row['setting_key']] = $row['setting_value'];
                }
                return $r;
            } catch (Exception $e) {
                return [];
            }
        }
    }
    $cfg = loadSettings($pdo, 'site_settings');
}
if (!isset($brandName)) $brandName = $cfg['brand_name'] ?? 'UsahaPPOB';

// ── Load navbar items ──────────────────────────────────────
try {
    $navItems = $pdo->query("SELECT * FROM navbar_items WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $navItems = [];
}

// Support key lama (primary_color) dan key baru (color_primary)
$cp  = $cfg['color_primary']      ?? $cfg['primary_color']      ?? '#01d298';
$cpd = $cfg['color_primary_dark'] ?? $cfg['primary_dark_color'] ?? '#00b07e';
$cur = $_SERVER['REQUEST_URI'];

// Social media links
$sosmed = [
    'sosmed_instagram' => 'fab fa-instagram',
    'sosmed_facebook'  => 'fab fa-facebook-f',
    'sosmed_telegram'  => 'fab fa-telegram',
    'sosmed_whatsapp'  => 'fab fa-whatsapp',
];
?>

<!-- ═══════════ BOTTOM NAV ═══════════ -->
<nav class="ppob-bnav">
    <?php foreach ($navItems as $nav):
        $isCenter = (bool)($nav['is_center'] ?? 0);
        $match    = trim($nav['match_path'] ?? '');
        $isActive = $match !== '' && str_contains($cur, $match);
    ?>
        <?php if ($isCenter): ?>
            <a href="<?= htmlspecialchars($nav['href']) ?>" class="ppob-ncen">
                <div class="ppob-ncen-btn">
                    <i class="<?= htmlspecialchars($nav['icon_class']) ?>"></i>
                </div>
                <span><?= htmlspecialchars($nav['label']) ?></span>
            </a>
        <?php else: ?>
            <a href="<?= htmlspecialchars($nav['href']) ?>" class="ppob-ntab<?= $isActive ? ' active' : '' ?>">
                <i class="<?= htmlspecialchars($nav['icon_class']) ?>"></i>
                <span><?= htmlspecialchars($nav['label']) ?></span>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

<!-- ═══════════ FOOTER DESKTOP ═══════════ -->
<footer class="ppob-footer">
    <div class="ppob-footer-inner">
        <div class="ppob-footer-brand">
            <i class="fas fa-bolt"></i> <?= htmlspecialchars($brandName) ?>
        </div>
        <div class="ppob-footer-sm">
            <?php foreach ($sosmed as $key => $icon):
                $url = $cfg[$key] ?? '#';
                if ($url && $url !== '#'): ?>
                    <a href="<?= htmlspecialchars($url) ?>" class="ppob-sm-link" target="_blank" rel="noopener">
                        <i class="<?= $icon ?>"></i>
                    </a>
            <?php endif;
            endforeach; ?>
        </div>
        <p class="ppob-footer-copy"><?= htmlspecialchars($cfg['footer_copyright'] ?? '© 2026 ' . $brandName) ?></p>
    </div>
</footer>

<!-- ═══════════ SCRIPTS ═══════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
    /* ── BOTTOM NAV ──────────────────────────────── */
    .ppob-bnav {
        position: fixed;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        max-width: 480px;
        background: rgba(255, 255, 255, .97);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-top: 1px solid rgba(220, 228, 238, .9);
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding: 8px 0 env(safe-area-inset-bottom, 16px);
        z-index: 1000;
        box-shadow: 0 -2px 20px rgba(0, 0, 0, .07);
    }

    .ppob-ntab {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        padding: 3px 12px;
        border-radius: 10px;
        min-width: 48px;
        text-decoration: none;
        -webkit-tap-highlight-color: transparent;
        transition: opacity .15s;
    }

    .ppob-ntab i {
        font-size: 19px;
        color: #c4cdd9;
        transition: color .15s;
    }

    .ppob-ntab span {
        font-size: 9.5px;
        font-weight: 800;
        color: #c4cdd9;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .ppob-ntab.active i,
    .ppob-ntab.active span {
        color: <?= htmlspecialchars($cpd) ?>;
    }

    .ppob-ntab:active {
        opacity: .7;
    }

    /* Center floating button */
    .ppob-ncen {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        margin-top: -26px;
        text-decoration: none;
        -webkit-tap-highlight-color: transparent;
    }

    .ppob-ncen-btn {
        width: 54px;
        height: 54px;
        background: linear-gradient(135deg, <?= htmlspecialchars($cp) ?>, <?= htmlspecialchars($cpd) ?>);
        border-radius: 17px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 22px <?= htmlspecialchars($cp) ?>55;
        transition: transform .15s, box-shadow .15s;
    }

    .ppob-ncen:active .ppob-ncen-btn {
        transform: scale(.90);
        box-shadow: 0 3px 10px <?= htmlspecialchars($cp) ?>33;
    }

    .ppob-ncen-btn i {
        color: #fff;
        font-size: 21px;
    }

    .ppob-ncen span {
        font-size: 9.5px;
        font-weight: 800;
        color: <?= htmlspecialchars($cpd) ?>;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* ── FOOTER DESKTOP ──────────────────────────── */
    .ppob-footer {
        display: none;
        background: linear-gradient(135deg, <?= htmlspecialchars($cpd) ?>, <?= htmlspecialchars($cp) ?>);
        margin-top: 40px;
    }

    .ppob-footer-inner {
        max-width: 480px;
        margin: 0 auto;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    .ppob-footer-brand {
        color: #fff;
        font-size: 14px;
        font-weight: 900;
        font-family: 'Plus Jakarta Sans', sans-serif;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ppob-footer-sm {
        display: flex;
        gap: 7px;
    }

    .ppob-sm-link {
        width: 30px;
        height: 30px;
        background: rgba(255, 255, 255, .2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 13px;
        transition: background .2s;
    }

    .ppob-sm-link:hover {
        background: rgba(255, 255, 255, .35);
        color: #fff;
    }

    .ppob-footer-copy {
        color: rgba(255, 255, 255, .75);
        font-size: 11.5px;
        font-weight: 500;
        margin: 0;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Desktop: show footer, hide bottom nav */
    @media (min-width: 992px) {
        .ppob-footer {
            display: block;
        }

        .ppob-bnav {
            display: none !important;
        }

        body {
            padding-bottom: 0 !important;
        }
    }
</style>

<!-- ═══════════ LIVE CHAT WIDGET ═══════════ -->
<script>
    $(function() {
        const isAdmin = <?= json_encode(isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ?>;
        let tid = parseInt(new URLSearchParams(location.search).get('user_id')) || 0;
        if (!isAdmin && tid === 0) tid = 1;

        window.showTab = function(type) {
            if (type === 'list') {
                $('#tab-list').addClass('active bg-info');
                $('#tab-chat').removeClass('active bg-info');
                $('#user-list-container').removeClass('d-none');
                $('#message-container, #input-container').addClass('d-none');
                loadList();
            } else {
                $('#tab-chat').addClass('active bg-info');
                $('#tab-list').removeClass('active bg-info');
                $('#user-list-container').addClass('d-none');
                $('#message-container, #input-container').removeClass('d-none');
            }
        };
        window.selectUser = function(id) {
            tid = id;
            showTab('message');
            loadMsgs();
        };

        function loadList() {
            if (!$('#user-list-container').hasClass('d-none'))
                $('#user-list').load('/core/chat_handler.php?action=get_users');
        }

        function loadMsgs() {
            if ($('#chat-box').hasClass('d-none')) return;
            if (isAdmin && $('#message-container').hasClass('d-none')) return;
            $.get('/core/chat_handler.php', {
                action: 'fetch',
                user_id: tid
            }, function(d) {
                $('#chat-messages').html(d);
                const o = document.getElementById('chat-messages');
                if (o) o.scrollTop = o.scrollHeight;
            });
        }

        function sendMsg() {
            const m = $('#chat-input').val().trim();
            if (!m || tid === 0) return;
            $.post('/core/chat_handler.php?action=send&user_id=' + tid, {
                message: m
            }, function() {
                $('#chat-input').val('');
                loadMsgs();
            });
        }

        $('#btn-send').on('click', sendMsg);
        $('#chat-input').on('keypress', e => {
            if (e.which === 13) sendMsg();
        });
        $('#chat-icon').on('click', function() {
            $('#chat-box').toggleClass('d-none');
            if (!$('#chat-box').hasClass('d-none')) {
                isAdmin && tid === 0 ? showTab('list') : loadMsgs();
            }
        });
        $('#close-chat').on('click', () => $('#chat-box').addClass('d-none'));

        // setInterval(loadMsgs, 3000);
        // if (isAdmin) setInterval(loadList, 5000);
    });
</script>