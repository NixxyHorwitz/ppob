<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = $_SERVER['PHP_SELF'];
?>

<style>
    /* ===================== SIDEBAR OVERLAY ===================== */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 1040;
        backdrop-filter: blur(2px);
    }

    .sidebar-overlay.show { display: block; }

    /* ===================== SIDEBAR ===================== */
    #sidebar {
        position: fixed;
        top: 0; left: 0;
        width: 270px;
        height: 100%;
        background: #0f172a;
        z-index: 1050;
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        font-family: 'Nunito', sans-serif;
    }

    #sidebar.open {
        transform: translateX(0);
    }

    /* Desktop: always visible */
    @media (min-width: 992px) {
        #sidebar {
            transform: translateX(0);
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: auto;
        }
        .sidebar-overlay { display: none !important; }
        #sidebarToggle { display: none !important; }
    }

    /* ===================== SIDEBAR HEADER ===================== */
    .sidebar-profile {
        padding: 30px 20px 20px;
        background: linear-gradient(160deg, #01d298 0%, #009068 100%);
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .sidebar-profile::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 120px; height: 120px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }

    .sidebar-profile::after {
        content: '';
        position: absolute;
        bottom: -20px; left: -20px;
        width: 80px; height: 80px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }

    .sidebar-avatar {
        width: 64px; height: 64px;
        border-radius: 50%;
        background: rgba(255,255,255,0.25);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 10px;
        border: 3px solid rgba(255,255,255,0.4);
        position: relative; z-index: 1;
    }

    .sidebar-avatar i { color: white; font-size: 28px; }

    .sidebar-username {
        font-size: 16px;
        font-weight: 800;
        color: white;
        text-transform: capitalize;
        position: relative; z-index: 1;
        margin: 0;
    }

    .sidebar-role {
        font-size: 11px;
        color: rgba(255,255,255,0.75);
        font-weight: 600;
        position: relative; z-index: 1;
        display: flex; align-items: center; justify-content: center;
        gap: 4px;
        margin-top: 3px;
    }

    .sidebar-role .role-dot {
        width: 6px; height: 6px;
        background: white;
        border-radius: 50%;
        opacity: 0.7;
    }

    /* ===================== SIDEBAR NAV ===================== */
    .sidebar-nav {
        flex: 1;
        padding: 12px 0;
        list-style: none;
        margin: 0;
    }

    .nav-section-label {
        font-size: 10px;
        font-weight: 800;
        color: #475569;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 12px 20px 6px;
    }

    .sidebar-nav li a,
    .sidebar-nav li > div {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 20px;
        color: #94a3b8;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.15s;
        border-radius: 0;
        cursor: pointer;
    }

    .sidebar-nav li a:hover,
    .sidebar-nav li > div:hover {
        background: rgba(1,210,152,0.08);
        color: #01d298;
        padding-left: 26px;
    }

    .sidebar-nav li.active > a,
    .sidebar-nav li a.active-link {
        background: rgba(1,210,152,0.12);
        color: #01d298;
        border-right: 3px solid #01d298;
    }

    .sidebar-nav li a .nav-icon {
        width: 32px; height: 32px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        background: rgba(255,255,255,0.06);
        font-size: 14px;
    }

    .sidebar-nav li.active > a .nav-icon,
    .sidebar-nav li a.active-link .nav-icon {
        background: rgba(1,210,152,0.15);
        color: #01d298;
    }

    /* Sub menu */
    .sidebar-submenu {
        list-style: none;
        padding: 0;
        margin: 0;
        background: rgba(0,0,0,0.15);
    }

    .sidebar-submenu li a {
        padding: 9px 20px 9px 62px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #64748b !important;
    }

    .sidebar-submenu li a:hover {
        color: #01d298 !important;
        padding-left: 68px !important;
        background: rgba(1,210,152,0.06) !important;
    }

    .sidebar-submenu li a.active-link {
        color: #01d298 !important;
        background: rgba(1,210,152,0.08) !important;
        border-right: 3px solid #01d298;
    }

    .sidebar-submenu li a::before {
        content: '•';
        margin-right: 8px;
        opacity: 0.5;
    }

    /* Dropdown toggle arrow */
    .submenu-arrow {
        margin-left: auto;
        font-size: 11px;
        transition: transform 0.25s;
        color: #475569;
    }

    .submenu-open .submenu-arrow {
        transform: rotate(180deg);
        color: #01d298;
    }

    /* Divider */
    .sidebar-divider {
        height: 1px;
        background: rgba(255,255,255,0.06);
        margin: 8px 16px;
    }

    /* Logout */
    .logout-link {
        color: #f87171 !important;
    }

    .logout-link:hover {
        background: rgba(248,113,113,0.1) !important;
        color: #ef4444 !important;
    }

    .logout-link .nav-icon {
        background: rgba(248,113,113,0.1) !important;
        color: #f87171 !important;
    }

    /* ===================== TOGGLE BUTTON (Mobile) ===================== */
    #sidebarToggle {
        position: fixed;
        top: 16px; left: 16px;
        width: 40px; height: 40px;
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 12px;
        color: white;
        font-size: 16px;
        z-index: 1030;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        backdrop-filter: blur(5px);
        transition: background 0.2s;
    }

    #sidebarToggle:hover { background: rgba(255,255,255,0.3); }
</style>

<!-- Mobile Toggle Button (shown inside hero header area) -->
<button id="sidebarToggle" onclick="openSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ===================== SIDEBAR ===================== -->
<div id="sidebar">

    <!-- Profile Header -->
    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            <i class="fas fa-user"></i>
        </div>
        <p class="sidebar-username"><?= htmlspecialchars($user['username'] ?? 'Guest') ?></p>
        <div class="sidebar-role">
            <div class="role-dot"></div>
            <?= isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'Administrator' : 'Pelanggan Setia' ?>
        </div>
    </div>

    <!-- Navigation -->
    <ul class="sidebar-nav">

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <!-- Admin Section -->
        <li class="nav-section-label">Admin Panel</li>

        <li>
            <div onclick="toggleSubmenu('submenu-admin', this)" class="<?= strpos($current_page, 'modules/admin') !== false ? 'submenu-open' : '' ?>">
                <span class="nav-icon text-warning"><i class="fas fa-shield-alt"></i></span>
                <span>Panel Admin</span>
                <i class="fas fa-chevron-down submenu-arrow"></i>
            </div>
            <ul class="sidebar-submenu" id="submenu-admin" style="display: <?= strpos($current_page, 'modules/admin') !== false ? 'block' : 'none' ?>">
                <li><a href="/modules/admin/dashboard"  class="<?= strpos($current_page, 'modules/admin/dashboard') !== false ? 'active-link' : '' ?>">Dashboard Admin</a></li>
                <li><a href="/modules/admin/monitoring" class="<?= strpos($current_page, 'modules/admin/monitoring') !== false ? 'active-link' : '' ?>">Monitoring PPOB</a></li>
                <li><a href="/modules/admin/konfirmasi" class="<?= strpos($current_page, 'modules/admin/konfirmasi') !== false ? 'active-link' : '' ?>">Konfirmasi Topup</a></li>
                <li><a href="/modules/admin/manage_users" class="<?= strpos($current_page, 'modules/admin/manage_users') !== false ? 'active-link' : '' ?>">Manage Users</a></li>
                <li><a href="/modules/admin/products"   class="<?= strpos($current_page, 'modules/admin/products') !== false ? 'active-link' : '' ?>">Manage Products</a></li>
                <li><a href="/modules/admin/reports"    class="<?= strpos($current_page, 'modules/admin/reports') !== false ? 'active-link' : '' ?>">Reports</a></li>
                <li><a href="/modules/admin/website"    class="<?= strpos($current_page, 'modules/admin/website') !== false ? 'active-link' : '' ?>">Settings Website</a></li>
            </ul>
        </li>

        <div class="sidebar-divider"></div>
        <?php endif; ?>

        <!-- Main Section -->
        <li class="nav-section-label">Menu Utama</li>

        <li class="<?= strpos($current_page, 'dashboard') !== false && strpos($current_page, 'admin') === false ? 'active' : '' ?>">
            <a href="/dashboard">
                <span class="nav-icon"><i class="fas fa-home"></i></span>
                Dashboard
            </a>
        </li>

        <!-- Layanan PPOB Submenu -->
        <li>
            <div onclick="toggleSubmenu('submenu-layanan', this)" class="<?= (strpos($current_page, 'prabayar') !== false || strpos($current_page, 'pascabayar') !== false) ? 'submenu-open' : '' ?>">
                <span class="nav-icon"><i class="fas fa-layer-group"></i></span>
                <span>Layanan PPOB</span>
                <i class="fas fa-chevron-down submenu-arrow"></i>
            </div>
            <ul class="sidebar-submenu" id="submenu-layanan" style="display: <?= (strpos($current_page, 'prabayar') !== false || strpos($current_page, 'pascabayar') !== false) ? 'block' : 'none' ?>">
                <li><a href="/modules/prabayar/index"   class="<?= strpos($current_page, 'prabayar/index') !== false ? 'active-link' : '' ?>">Prabayar</a></li>
                <li><a href="/modules/pascabayar/index" class="<?= strpos($current_page, 'pascabayar/index') !== false ? 'active-link' : '' ?>">Pascabayar</a></li>
            </ul>
        </li>

        <li class="<?= strpos($current_page, 'user/topup') !== false && strpos($current_page, 'topup_history') === false && strpos($current_page, 'topup_manual') === false ? 'active' : '' ?>">
            <a href="/modules/user/topup">
                <span class="nav-icon"><i class="fas fa-coins"></i></span>
                Topup Saldo
            </a>
        </li>

        <div class="sidebar-divider"></div>
        <li class="nav-section-label">Riwayat</li>

        <li class="<?= strpos($current_page, 'topup_history') !== false ? 'active' : '' ?>">
            <a href="/modules/user/topup_history">
                <span class="nav-icon"><i class="fas fa-clock-rotate-left"></i></span>
                Riwayat Topup
            </a>
        </li>

        <li class="<?= strpos($current_page, 'user/riwayat') !== false ? 'active' : '' ?>">
            <a href="/modules/user/riwayat">
                <span class="nav-icon"><i class="fas fa-receipt"></i></span>
                Riwayat Transaksi
            </a>
        </li>

        <div class="sidebar-divider"></div>
        <li class="nav-section-label">Akun</li>

        <li class="<?= strpos($current_page, 'user/profil') !== false ? 'active' : '' ?>">
            <a href="/modules/user/profil">
                <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
                Profil Saya
            </a>
        </li>

        <li class="<?= strpos($current_page, 'api/api_docs') !== false ? 'active' : '' ?>">
            <a href="/api/api_docs">
                <span class="nav-icon"><i class="fas fa-code"></i></span>
                Dokumentasi API
            </a>
        </li>

        <div class="sidebar-divider"></div>

        <li>
            <a href="/logout" class="logout-link">
                <span class="nav-icon"><i class="fas fa-power-off"></i></span>
                Keluar
            </a>
        </li>
    </ul>

</div>

<script>
    function openSidebar() {
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
        document.body.style.overflow = '';
    }

    function toggleSubmenu(id, trigger) {
        const sub = document.getElementById(id);
        const isOpen = sub.style.display === 'block';
        sub.style.display = isOpen ? 'none' : 'block';
        trigger.classList.toggle('submenu-open', !isOpen);
    }
</script>