<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = $_SERVER['REQUEST_URI'];
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
    /* ===================== BOTTOM NAV ===================== */
    .bottom-nav-bar {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: #ffffff;
        border-top: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding: 8px 0 20px;
        z-index: 999;
        box-shadow: 0 -4px 24px rgba(0,0,0,0.08);
    }

    .nav-tab {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        text-decoration: none;
        padding: 4px 14px;
        border-radius: 10px;
        transition: all 0.15s;
        min-width: 52px;
    }

    .nav-tab i {
        font-size: 20px;
        color: #adb5bd;
        transition: color 0.15s;
    }

    .nav-tab span {
        font-size: 10px;
        font-weight: 700;
        color: #adb5bd;
        font-family: 'Nunito', sans-serif;
        transition: color 0.15s;
    }

    .nav-tab.active i,
    .nav-tab.active span {
        color: #01d298;
    }

    .nav-tab:active i,
    .nav-tab:active span {
        color: #01d298;
    }

    /* Center PAY / Top Up button */
    .nav-center-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        text-decoration: none;
        margin-top: -26px;
    }

    .nav-center-btn {
        width: 58px; height: 58px;
        background: linear-gradient(135deg, #01d298, #00b07e);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 20px rgba(1, 210, 152, 0.45);
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .nav-center-wrapper:active .nav-center-btn {
        transform: scale(0.92);
        box-shadow: 0 3px 12px rgba(1, 210, 152, 0.3);
    }

    .nav-center-btn i {
        color: white;
        font-size: 22px;
    }

    .nav-center-wrapper span {
        font-size: 10px;
        font-weight: 700;
        color: #01d298;
        font-family: 'Nunito', sans-serif;
    }

    /* Footer desktop (hidden on mobile) */
    .footer-desktop {
        display: none;
        background: linear-gradient(135deg, #01d298, #00b07e);
        color: white;
        padding: 16px 0;
        margin-top: 40px;
    }

    @media (min-width: 992px) {
        .footer-desktop { display: block; }
        .bottom-nav-bar { display: none; }
        body { padding-bottom: 0 !important; }
    }

    .sosmed-link {
        width: 34px; height: 34px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: white;
        text-decoration: none;
        transition: background 0.2s;
    }
    .sosmed-link:hover { background: rgba(255,255,255,0.35); color: white; }
    .sosmed-link i { font-size: 14px; }

    .copyright-text {
        font-size: 13px;
        font-family: 'Nunito', sans-serif;
        color: rgba(255,255,255,0.85);
    }
</style>

<!-- ===================== BOTTOM NAV (Mobile) ===================== -->
<div class="bottom-nav-bar">
    <a href="/demo/dashboard.php"
       class="nav-tab <?= (strpos($current_page, 'dashboard') !== false) ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Beranda</span>
    </a>

    <a href="/demo/modules/user/riwayat.php"
       class="nav-tab <?= (strpos($current_page, 'riwayat') !== false) ? 'active' : '' ?>">
        <i class="fas fa-receipt"></i>
        <span>Aktivitas</span>
    </a>

    <!-- Center Button -->
    <a href="/demo/modules/user/topup.php" class="nav-center-wrapper">
        <div class="nav-center-btn">
            <i class="fas fa-qrcode"></i>
        </div>
        <span>Top Up</span>
    </a>

    <a href="#" class="nav-tab">
        <i class="fas fa-wallet"></i>
        <span>Dompet</span>
    </a>

    <a href="/demo/modules/user/profil.php"
       class="nav-tab <?= (strpos($current_page, 'profil') !== false) ? 'active' : '' ?>">
        <i class="fas fa-user-circle"></i>
        <span>Saya</span>
    </a>
</div>

<!-- ===================== FOOTER DESKTOP ===================== -->
<footer class="footer-desktop">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex gap-2">
                    <a href="#" class="sosmed-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="sosmed-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="sosmed-link"><i class="fab fa-telegram"></i></a>
                    <a href="#" class="sosmed-link"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <p class="mb-0 copyright-text">
                © 2026 <strong>UsahaPPOB</strong> — Revolusi Digital PPOB
            </p>
        </div>
    </div>
</footer>

<!-- ===================== LIVE CHAT WIDGET ===================== -->
<script>
$(document).ready(function () {
    const isAdmin = <?php echo json_encode(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'); ?>;
    let activeTargetId = new URLSearchParams(window.location.search).get('user_id') || 0;

    if (!isAdmin && activeTargetId == 0) {
        activeTargetId = 1;
    }

    window.showTab = function (type) {
        if (type === 'list') {
            $('#tab-list').addClass('active bg-info');
            $('#tab-chat').removeClass('active bg-info');
            $('#user-list-container').removeClass('d-none');
            $('#message-container, #input-container').addClass('d-none');
            loadUserList();
        } else {
            $('#tab-chat').addClass('active bg-info');
            $('#tab-list').removeClass('active bg-info');
            $('#user-list-container').addClass('d-none');
            $('#message-container, #input-container').removeClass('d-none');
        }
    };

    window.selectUser = function (id, name) {
        activeTargetId = id;
        showTab('message');
        loadMessages();
    };

    function loadUserList() {
        if ($('#user-list-container').length && !$('#user-list-container').hasClass('d-none')) {
            $('#user-list').load('/demo/core/chat_handler.php?action=get_users');
        }
    }

    function loadMessages() {
        if (!$('#chat-box').hasClass('d-none') && (isAdmin ? !$('#message-container').hasClass('d-none') : true)) {
            $.get('/demo/core/chat_handler.php', {
                action: 'fetch',
                user_id: activeTargetId
            }, function (data) {
                $('#chat-messages').html(data);
                let obj = document.getElementById('chat-messages');
                if (obj) obj.scrollTop = obj.scrollHeight;
            });
        }
    }

    function sendMessage() {
        let msg = $('#chat-input').val().trim();
        if (msg === '' || activeTargetId == 0) return;
        $.post('/demo/core/chat_handler.php?action=send&user_id=' + activeTargetId, {
            message: msg
        }, function () {
            $('#chat-input').val('');
            loadMessages();
        });
    }

    $('#btn-send').click(sendMessage);
    $('#chat-input').keypress(e => { if (e.which == 13) sendMessage(); });

    $('#chat-icon').click(() => {
        $('#chat-box').toggleClass('d-none');
        if (!$('#chat-box').hasClass('d-none')) {
            if (isAdmin && activeTargetId == 0) {
                showTab('list');
            } else {
                loadMessages();
            }
        }
    });

    $('#close-chat').click(() => $('#chat-box').addClass('d-none'));

    setInterval(loadMessages, 3000);
    if (isAdmin) setInterval(loadUserList, 5000);
});
</script>