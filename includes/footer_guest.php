<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = $_SERVER['REQUEST_URI'];
?>

<div class="bottom-nav shadow-lg">
    <a href="/demo/index.php" class="nav-item-bottom <?= ($current_page == '/index.php' || $current_page == '/') ? 'active' : '' ?>">
        <i class="fas fa-home"></i> Beranda
    </a>
    <a href="/demo/modules/user/riwayat.php" class="nav-item-bottom <?= (strpos($current_page, 'riwayat.php') !== false) ? 'active' : '' ?>">
        <i class="fas fa-history"></i> Riwayat
    </a>
    <a href="/demo/modules/user/topup.php" class="nav-item-bottom">
        <i class="fas fa-wallet"></i> Top Up
    </a>
    <a href="/demo/logout.php" class="nav-item-bottom text-danger">
        <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
</div>


<footer class="bg-white pt-4 pb-3 border-top">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                <span class="text-muted me-3 small fw-bold">Follow Us</span>
                <div class="d-inline-flex gap-2">
                    <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/124/124010.png" class="social-icon"></a>
                    <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/124/124021.png" class="social-icon"></a>
                    <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" class="social-icon"></a>
                    <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111646.png" class="social-icon"></a>
                    <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/3046/3046121.png" class="social-icon"></a>
                </div>
            </div>
            
            <div class="col-md-4 text-center mb-3 mb-md-0">
                <div class="d-flex align-items-center justify-content-center">
                    <span class="text-muted small me-2">Secured By</span>
                    <img src="assets/geotrust.png" alt="GeoTrust" style="height: 20px;">
                </div>
            </div>

          <div class="col-md-4 text-center text-md-end">
    <p class="mb-0 fw-bold" style="font-size: 14px; color: #64748b;">
        Copyright © 2026 <span style="color: #0ea5e9;">UsahaPPOB - Revolusi Digital</span>
    </p>
</div>
        </div>
    </div>
</footer>
<a href="https://wa.me/6281244751352" class="wa-float shadow-sm" target="_blank">
    <i class="fab fa-whatsapp"></i>
</a>

<style>
    :root { --ok-green: #00a896; }
    
    .bottom-nav {
        background: #ffffff;
        border-top: 1px solid #e0e0e0;
        padding: 10px 0;
        position: fixed;
        bottom: 0;
        width: 100%;
        max-width: 600px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        display: none;
        justify-content: space-around;
    }

    .nav-item-bottom {
        text-align: center;
        color: #6c757d;
        text-decoration: none;
        font-size: 0.7rem;
        flex: 1;
    }

    .nav-item-bottom i { 
        font-size: 1.3rem; 
        display: block; 
        margin-bottom: 2px;
    }

    .nav-item-bottom.active { 
        color: var(--ok-green); 
        font-weight: bold;
    }

    .wa-float {
        position: fixed;
        bottom: 80px;
        right: 20px;
        background-color: #25d366;
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        z-index: 1050;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .wa-float:hover {
        transform: scale(1.1);
        color: white;
        text-decoration: none;
    }

    .social-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        object-fit: cover;
        transition: transform 0.2s;
    }
    
    .social-icon:hover {
        transform: scale(1.1);
    }

.nav-pay-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.nav-pay-btn {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%); 
    color: white;
    border-radius: 12px; 
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    margin-bottom: 4px;
    text-decoration: none;
    transition: 0.3s;
}

.nav-pay-btn:hover {
    transform: translateY(-3px);
    color: #ffc107; /* Warna warning saat hover */
}

.nav-pay-label {
    font-size: 11px;
    font-weight: bold;
    color: #64748b;
}

    @media (max-width: 768px) {
        .bottom-nav { 
            display: flex !important; 
        }
        body { 
            padding-bottom: 60px; 
        }
        footer {
            margin-bottom: 0 !important;
            padding-bottom: 20px !important;
        }
        .wa-float {
            bottom: 110px;
            right: 20px;
            width: 30px;
            height: 30px;
            font-size: 26px;
        }
    }
</style>