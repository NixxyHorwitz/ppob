<?php
$timeout = 86400;
ini_set('session.gc_maxlifetime', $timeout);
session_set_cookie_params($timeout);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

/* ======================
   LOAD WEBSITE SETTINGS
====================== */
$setting = $pdo->query("SELECT * FROM website_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$site_name = $setting['site_name'] ?? 'Website';
$tagline = $setting['site_tagline'] ?? '';
$description = $setting['site_description'] ?? '';
$keywords = $setting['site_keywords'] ?? '';
$logo = $setting['site_logo'] ?? 'assets/logo.png';
$favicon = $setting['site_favicon'] ?? 'assets/favicon.png';
$theme_color = $setting['theme_color'] ?? '#0ea5e9';


$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= htmlspecialchars($site_name) ?> - <?= htmlspecialchars($tagline) ?></title>

<meta name="description" content="<?= htmlspecialchars($description) ?>">
<meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
<meta name="author" content="<?= htmlspecialchars($site_name) ?>">

<!-- SEO ROBOT -->
<meta name="robots" content="index, follow">

<!-- THEME -->
<meta name="theme-color" content="<?= $theme_color ?>">

<!-- FAVICON -->
<link rel="icon" href="<?= $favicon ?>" type="image/png">

<!-- OPEN GRAPH (Facebook / WhatsApp SEO) -->
<meta property="og:title" content="<?= $site_name ?>">
<meta property="og:description" content="<?= $description ?>">
<meta property="og:image" content="<?= $logo ?>">
<meta property="og:type" content="website">

<!-- TWITTER SEO -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $site_name ?>">
<meta name="twitter:description" content="<?= $description ?>">
<meta name="twitter:image" content="<?= $logo ?>">

<link rel="manifest" href="/manifest.json">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/index.css?v=2.0">

</head>
<body>

<?php require_once 'includes/header.php'; ?>

<section class="hero text-center">
    <div class="container">
        <h1 class="mx-auto">Isi Kuota Internet & Pulsa Murah <br> dengan Cepat & Nyaman</h1>
        
       <?php if(!$user): ?>
            <div class="login-card-box mt-5 w-75 d-block mx-auto">
                <p class="mb-3">Untuk melakukan transaksi, silahkan Login terlebih dahulu</p>
                <button type="button" class="btn btn-success px-4 py-2 shadow-sm fw-bold" style="border-radius: 10px; background-color: #0ea5e9; " data-bs-toggle="modal" data-bs-target="#loginModal">
                    Login
                </button>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 150px;"></div>
        <?php endif; ?>
    </div>
</section>

<section class="container py-5 text-center">
    <p class="mb-5 fw-bold">Keuntungan Bertransaksi Online di UsahaPPOB</p>
    <div class="row">
        <div class="col-md-3">
            <div class="feature-circle"><i class="fas fa-map-marker-alt"></i></div>
            <h5>Mudah</h5>
            <p class="small text-muted">Segala transaksi dapat diselesaikan hanya dengan beberapa klik.</p>
        </div>
        <div class="col-md-3">
            <div class="feature-circle"><i class="fas fa-clock"></i></div>
            <h5>CS 24 Jam</h5>
            <p class="small text-muted">Customer Service siap membantu 24 jam jika ada kendala.</p>
        </div>
        <div class="col-md-3">
            <div class="feature-circle"><i class="fas fa-percent"></i></div>
            <h5>Praktis</h5>
            <p class="small text-muted">Transaksi dapat dilakukan melalui website atau aplikasi.</p>
        </div>
        <div class="col-md-3">
            <div class="feature-circle"><i class="fas fa-shield-alt"></i></div>
            <h5>Transaksi Aman</h5>
            <p class="small text-muted">Sistem keamanan terbaik menjamin data Anda tetap aman.</p>
        </div>
    </div>
</section>

<section id="product" class="ppob-section rounded-start-5 rounded-end-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="ppob-badge">Produk PPOB</span>
            <h2 class="ppob-title text-light">Layanan PPOB Lengkap & Terpercaya</h2>
            <p class="ppob-subtitle">
                Mendukung transaksi <strong>Prabayar</strong> dan <strong>Pascabayar</strong> dengan sistem otomatis & real-time.
            </p>
        </div>

        <div class="row g-4">
            <!-- Prabayar -->
            <div class="col-md-6">
                <div class="ppob-card">
                    <div class="ppob-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h5 class="ppob-card-title">PPOB Prabayar</h5>
                    <ul class="ppob-list">
                        <li>Pulsa All Operator</li>
                        <li>Paket Data Internet</li>
                        <li>Token Listrik PLN</li>
                        <li>Top-Up Game & E-Wallet</li>
                    </ul>
                    <span class="ppob-note">✔ Proses instan & otomatis</span>
                </div>
            </div>

            <div class="col-md-6">
                <div class="ppob-card">
                    <div class="ppob-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h5 class="ppob-card-title">PPOB Pascabayar</h5>
                    <ul class="ppob-list">
                        <li>Tagihan Listrik PLN</li>
                        <li>PDAM & Internet</li>
                        <li>BPJS Kesehatan</li>
                        <li>Multifinance & Telkom</li>
                    </ul>
                    <span class="ppob-note">✔ Pembayaran aman & valid</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="testimonial-section mt-3">
    <div class="testimonial-container">
        <h2 class="testimonial-title text-center mb-5">Apa Kata Pelanggan Kami</h2>
     
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <p class="testimonial-text">
                    “Isi pulsa dan kuota di sini cepat banget, nggak pernah gagal.
                    Harganya juga lebih murah dari tempat lain.”
                </p>
                <div class="testimonial-user">
                    <strong>Rizky A.</strong>
                    <span>Pengguna Aktif</span>
                </div>
            </div>

            <div class="testimonial-card">
                <p class="testimonial-text">
                    “Transaksi PPOB lancar, pembayaran listrik dan PDAM jadi praktis.
                    Customer servicenya responsif.”
                </p>
                <div class="testimonial-user">
                    <strong>Siti Nurhaliza</strong>
                    <span>Pelanggan PPOB</span>
                </div>
            </div>

            <div class="testimonial-card">
                <p class="testimonial-text">
                    “Tampilan simpel, saldo masuk cepat, cocok buat dipakai jualan juga.”
                </p>
                <div class="testimonial-user">
                    <strong>Andi Pratama</strong>
                    <span>Reseller</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div id="promo" class="d-flex justify-content-between align-items-center mb-4 px-3">
    <h5 class="fw-800 mb-0">Promo Spesial 🔥</h5>
    <a href="#" class="text-sky fw-bold small text-decoration-none">Lihat Semua</a>
</div>

<div class="row g-4 mb-5 px-3">
    <div class="col-md-4">
        <div class="card promo-card shadow-sm">
            <div class="promo-img-wrapper">
                <span class="promo-badge text-uppercase">Terbatas</span>
                <i class="fas fa-bolt"></i>
                <div class="position-absolute text-center text-dark">
                    <h4 class="fw-800 mb-0">PLN Hemat</h4>
                    <p class="small mb-0">Diskon Rp 5.000</p>
                </div>
            </div>
            <div class="card-body p-4">
                <h6 class="fw-800 mb-1">Bayar Listrik Jadi Irit</h6>
                <p class="text-muted small mb-3">Gunakan kode promo <strong>PLNCUAN</strong> untuk transaksi Token PLN.</p>
                <button class="btn-ambil w-100">Ambil Promo</button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card promo-card shadow-sm">
            <div class="promo-img-wrapper" style="background: #fff1f2;">
                <span class="promo-badge text-uppercase text-danger">New User</span>
                <i class="fas fa-gift" style="color: #f43f5e;"></i>
                <div class="position-absolute text-center text-dark">
                    <h4 class="fw-800 mb-0" style="color: #e11d48;">Saldo Gratis</h4>
                    <p class="small mb-0">Hingga Rp 10.000</p>
                </div>
            </div>
            <div class="card-body p-4">
                <h6 class="fw-800 mb-1">Bonus Pengguna Baru</h6>
                <p class="text-muted small mb-3">Top up pertama kali minimal 50rb langsung dapat bonus saldo.</p>
                <button class="btn-ambil w-100" style="background: #fff1f2; color: #e11d48;">Cek Syarat</button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card promo-card shadow-sm">
            <div class="promo-img-wrapper" style="background: #f0fdf4;">
                <span class="promo-badge text-uppercase text-success">Weekend</span>
                <i class="fas fa-gamepad" style="color: #22c55e;"></i>
                <div class="position-absolute text-center text-dark">
                    <h4 class="fw-800 mb-0" style="color: #16a34a;">Gaming Day</h4>
                    <p class="small mb-0">Cashback 10%</p>
                </div>
            </div>
            <div class="card-body p-4">
                <h6 class="fw-800 mb-1">Push Rank Tanpa Boros</h6>
                <p class="text-muted small mb-3">Top up Diamond & Voucher Game di akhir pekan lebih untung.</p>
                <button class="btn-ambil w-100" style="background: #f0fdf4; color: #16a34a;">Mainkan</button>
            </div>
        </div>
    </div>
</div>

<div class="footer-banner">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <h4 class="fw-bold">Download Aplikasi UsahaPPOB</h4>
                <p class="small">Nikmati kemudahan bertransaksi dari Smartphonemu</p>
                <img src="assets/play_store.png" height="45">
            </div>
           <div class="col-md-6 text-end d-none d-md-block">
                <img src="assets/phone.png" style="height: 350px; margin-top: -80px; margin-bottom: -20px; position: relative; z-index: 10;">
            </div>
        </div>
    </div>
</div>

<button id="backToTop" class="btn-to-top shadow-lg">
    <i class="fas fa-arrow-up"></i>
</button>

<style>

    .login-card-box {
        background-color: rgba(255, 255, 255, 0.9); 
        padding: 1rem;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        text-align: center;
        color: #0ea5e9;
    }
    
    .login-card-box .btn {
        color: #fff;
        border: none;
        transition: all 0.3s ease;
    }
    
    .login-card-box .btn:hover {
         transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(14, 165, 233, 0.3);
        color: skyblue !important; 
        border: 1px solid var(--ok-teal);
    }
    
    .login-card-box:hover {
        box-shadow: 0 6px 15px rgba(14, 165, 233, 0.3);
        color: skyblue !important; 
    }
</style>

<?php include 'includes/modal_auth.php'; ?>
<?php require_once 'includes/footer_guest.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const params = new window.URLSearchParams(window.location.search);
    const error = params.get('error');
    const msg = params.get('msg');

    if (error || msg) {
        Swal.fire({
            icon: error ? 'error' : 'success',
            title: error ? 'Wahh...' : 'Berhasil!',
            text: error === 'login_failed' ? 'Username atau password salah!' : 
                  error === 'user_exists' ? 'Username sudah terdaftar!' : 
                  'Registrasi berhasil, silakan login.',
            confirmButtonColor: '#00a896',
            customClass: { popup: 'rounded-4' }
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const backToTop = document.getElementById('backToTop');
    
        window.addEventListener('scroll', () => {
            if (backToTop) {
                if (window.scrollY > 100) {
                    backToTop.style.display = "flex";
                } else {
                    backToTop.style.display = "none";
                }
            }
        }, { passive: true });
    
        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });
</script>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/sw.js')
      .then(reg => console.log('SW registered', reg))
      .catch(err => console.log('SW registration failed', err));
  });
}
</script>

</body>
</html>