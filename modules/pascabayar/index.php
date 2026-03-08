<?php
//pascabayar
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../core/transaction.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$category = $_GET['cat'] ?? 'PLN';
$category_title = strtoupper($category);
$inquiryData = null;
$message = "";

if (isset($_POST['cek_tagihan'])) {
    $sku = $_POST['sku'];
    $target = $_POST['target'];
    $inquiryData = cekTagihanPasca($_SESSION['user_id'], $sku, $target);

    if ($inquiryData['rc'] !== '00') {
        $message = "<div class='alert alert-danger'>Gagal: " . $inquiryData['message'] . "</div>";
        $inquiryData = null;
    }
}

if (isset($_POST['bayar_tagihan'])) {
    $sku = $_POST['sku'];
    $target = $_POST['target'];
    $refId = $_POST['ref_id'];
    $pin = $_POST['pin'];
    $message = bayarTagihanPasca($_SESSION['user_id'], $sku, $target, $refId, $pin);
    $message = "<div class='alert alert-info'>$message</div>";
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' AND category = ? AND type = 'pascabayar' ORDER BY product_name ASC");
$stmt->execute([$category]);
$products = $stmt->fetchAll();

$menu_pasca = [
    'PLN'      => 'fa-bolt',
    'PDAM'     => 'fa-tint',
    'HP'       => 'fa-phone-alt',
    'INTERNET' => 'fa-globe',
    'BPJS'     => 'fa-heartbeat',
    'TV'       => 'fa-tv',
    'E-MONEY'  => 'fa-wallet'
];

?>

<style>
    :root {
        --sky-blue: #0ea5e9;
        --sky-dark: #0284c7;
    }

    body {
        background-color: #f8fafc;
    }

    .card-custom {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }

    .nav-kategori {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 30px;
    }

    .tab-item {
        flex: 1 0 calc(25% - 10px);
        text-align: center;
        background: white;
        padding: 10px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        color: #64748b;

        border: 1px solid #1858ab;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        transition: 0.3s;
    }

    .tab-item.active {
        background: var(--sky-blue);
        color: white;
        border-color: var(--sky-blue);
    }

    .input-custom {
        background-color: #f1f5f9;
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 12px 15px;
        font-weight: 600;
        transition: 0.3s;
    }

    .input-custom:focus {
        background-color: #fff;
        border-color: var(--sky-blue);
        box-shadow: none;
    }

    .btn-navy {
        background-color: #134d74;
        color: white;
        border: none;
        border-radius: 15px;
        font-weight: 700;
        transition: 0.3s;
    }

    .btn-navy:hover {
        background-color: #1e5f8a;
        color: #fff;
    }

    .info-tagihan {
        background: #f0f9ff;
        border: 1px dashed var(--sky-blue);
        padding: 15px;
        border-radius: 12px;
    }

    .main-content {
        margin-left: 260px;
        transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
        }

        .tab-item {
            flex: 0 0 calc(50% - 10px);
        }
    }
</style>

<div class="main-content">
    <div class="container-fluid flex-grow-1">
        <div class="hero-mini text-center">
            <i class="fas fa-mobile-alt fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
            <h4 class="fw-bold mt-1">Pascabayar <?= $category_title ?></h4>
        </div>

        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="nav-kategori">
                        <?php foreach ($menu_pasca as $name => $icon): ?>
                            <a href="?cat=<?= $name; ?>" class="tab-item <?= ($category == $name) ? 'active' : ''; ?>">
                                <i class="fas <?= $icon; ?> d-block mb-1 fs-5"></i> <?= $name; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="card card-custom">
                        <div class="card-body p-4">
                            <?= $message ?>

                            <?php if (!$inquiryData): ?>
                                <form method="POST">
                                    <div class="mb-4">
                                        <label class="fw-bold small mb-2">Pilih Layanan</label>
                                        <select name="sku" class="form-select input-custom" required>
                                            <option value="" selected disabled>-- Pilih Produk --</option>
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?= $p['sku_code'] ?>"><?= $p['product_name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="fw-bold small mb-2">Nomor Pelanggan</label>
                                        <input type="text" name="target" class="form-control input-custom" placeholder="Masukkan ID Pelanggan" required>
                                    </div>
                                    <button type="submit" name="cek_tagihan" class="btn btn-navy w-100 py-3">Cek Tagihan</button>
                                </form>

                            <?php else: ?>
                                <form method="POST">
                                    <div class="info-tagihan mb-4">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">Nama Pelanggan</span>
                                            <span class="fw-bold text-uppercase"><?= $inquiryData['customer_name'] ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">ID Pelanggan</span>
                                            <span class="fw-bold"><?= $inquiryData['customer_no'] ?></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold text-primary">Total Tagihan</span>
                                            <span class="fw-bold text-primary fs-5">Rp <?= number_format($inquiryData['selling_price'], 0, ',', '.') ?></span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="sku" value="<?= $inquiryData['buyer_sku_code'] ?>">
                                    <input type="hidden" name="target" value="<?= $inquiryData['customer_no'] ?>">
                                    <input type="hidden" name="ref_id" value="<?= $inquiryData['ref_id'] ?>">

                                    <div class="mb-3">
                                        <label class="fw-bold small mb-2">PIN Transaksi</label>
                                        <input type="password" name="pin" class="form-control input-custom text-center" placeholder="****" required>
                                    </div>
                                    <button type="submit" name="bayar_tagihan" class="btn btn-success w-100 py-3 rounded-4 fw-bold">Bayar Sekarang</button>
                                    <a href="?cat=<?= $category ?>" class="btn btn-link w-100 text-muted mt-2 small text-decoration-none">Batal</a>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>