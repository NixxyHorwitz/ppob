<?php
//prabayar
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../core/transaction.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$category = $_GET['cat'] ?? 'Pulsa';
$category_title = ucfirst($category);

$message = "";
if (isset($_POST['beli'])) {
    $pinInput = $_POST['pin_transaksi'] ?? '';
    $message = prosesTransaksi($_SESSION['user_id'], $_POST['sku'], $_POST['target'], $pinInput);
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' AND category = ? ORDER BY price_sell ASC");
$stmt->execute([$category]);
$products = $stmt->fetchAll();

$menu_kategori = [
    'Pulsa'            => 'fa-mobile-alt',
    'Data'             => 'fa-wifi',
    'Aktivasi Perdana' => 'fa-sim-card',
    'Masa Aktif'       => 'fa-calendar-alt',
    'Paket SMS & Telpon' => 'fa-phone-volume',
    'PLN'              => 'fa-bolt',
    'Gas'              => 'fa-fire-alt',
    'Voucher'          => 'fa-ticket-alt',
    'Games'             => 'fa-gamepad',
    'E-money'           => 'fa-wallet',

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
        margin-top: -30px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }


    .nav-kategori {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 10px 0;
        margin-bottom: 20px;
        scrollbar-width: none;
    }

    .nav-kategori {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 50px;
    }

    .tab-item {
        flex: 0 0 calc(20% - 10px);
        text-align: center;
        background: white;
        padding: 10px;
        border-radius: 12px;
        border: 1px solid #1858ab;
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s ease-in-out;

    }


    @media (max-width: 576px) {
        .tab-item {
            flex: 0 0 calc(50% - 10px);
        }
    }


    .tab-item.active {
        background: var(--sky-blue);
        color: white;
        border-color: var(--sky-blue);
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
        max-height: 450px;
        overflow-y: auto;
        padding: 10px;
    }

    .product-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 15px;
        cursor: pointer;
        background: #fff;
        text-align: center;
        transition: 0.2s;
    }

    .product-card:hover {
        border-color: var(--sky-blue);
    }

    .product-card.active {
        border-color: var(--sky-blue);
        background: #f0f9ff;
    }

    .product-name {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        display: block;
        height: 40px;
        overflow: hidden;
    }

    .product-price {
        font-size: 14px;
        font-weight: 700;
        color: var(--sky-dark);
        display: block;
        margin-top: 5px;
    }

    .btn-navy {
        background-color: #134d74;
        color: white;
        border: none;
        border-radius: 15px;
        font-weight: 700;
    }

    .btn-navy:hover {
        background-color: #1e5f8a;
        color: #0ea5e9;
    }

    .main-content {
        margin-left: 260px;
        transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
        }

        .product-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="main-content">
    <div class="container-fluid flex-grow-1">
        <div class="hero-mini text-center">
            <i class="fas fa-mobile-alt fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
            <h4 class="fw-bold mt-1">Layanan <?= $category_title; ?></h4>
        </div>

        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">

                    <div class="nav-kategori">
                        <?php foreach ($menu_kategori as $name => $icon): ?>
                            <a href="?cat=<?= urlencode($name); ?>" class="tab-item <?= ($category == $name) ? 'active' : ''; ?>">
                                <i class="fas <?= $icon; ?> me-2 text-info"></i> <?= $name; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="card card-custom">
                        <div class="card-body p-4">
                            <?php if ($message): ?>
                                <div class="alert alert-info border-0 rounded-3 alert-dismissible fade show"><?= $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-secondary">Nomor Tujuan / ID Pelanggan</label>
                                    <input type="number" id="nomor_hp" name="target" class="form-control" placeholder="Masukkan nomor..." required autofocus>
                                    <div id="operator_label" class="mt-1 small fw-bold text-uppercase" style="color: #0ea5e9; min-height: 20px;"></div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-secondary">Pilih Produk <?= $category_title; ?></label>
                                    <input type="hidden" name="sku" id="selected_sku" required>
                                    <div class="product-grid">
                                        <?php foreach ($products as $p): ?>
                                            <?php
                                            $brand = strtolower($p['brand'] ?? '');
                                            $logo_url = "";
                                            $logos = [
                                                'telkomsel' => 'https://upload.wikimedia.org/wikipedia/commons/b/bc/Telkomsel_2021_icon.svg',
                                                'by.u'      => '/assets/logo/byu.svg',
                                                'xl'        => 'https://www.logo.wine/a/logo/XL_Axiata/XL_Axiata-Logo.wine.svg',
                                                'indosat'   => '/assets/logo/indosat.svg',
                                                'tri'       => 'https://www.logo.wine/a/logo/3_(telecommunications)/3_(telecommunications)-Logo.wine.svg',
                                                'three'     => 'https://www.logo.wine/a/logo/3_(telecommunications)/3_(telecommunications)-Logo.wine.svg',
                                                'axis'      => 'https://www.logo.wine/a/logo/Axis_Telecom/Axis_Telecom-Logo.wine.svg',
                                                'smartfren' => '/assets/logo/smartfren.svg',
                                                'pln'       => 'https://upload.wikimedia.org/wikipedia/commons/a/af/Logo_PLN.svg'
                                            ];

                                            foreach ($logos as $key => $url) {
                                                if (strpos($brand, $key) !== false) {
                                                    $logo_url = $url;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <div class="product-card shadow-sm" data-sku="<?= $p['sku_code']; ?>" data-info="<?= strtolower(($p['brand'] ?? '') . ' ' . $p['product_name']); ?>">
                                                <?php if ($logo_url): ?>
                                                    <img src="<?= $logo_url ?>" style="height: 40px; max-width: 80%; object-fit: contain; margin-bottom: 8px;">
                                                <?php endif; ?>
                                                <span class="product-name"><?= $p['product_name']; ?></span>
                                                <span class="product-price">Rp <?= number_format($p['price_sell'], 0, ',', '.'); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-navy w-100 py-3" id="btnProses">Beli Sekarang</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../../includes/modal_konfirmasi.php'; ?>
    <?php include __DIR__ . '/../../includes/modal_pin.php'; ?>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.product-card');
        const skuInput = document.getElementById('selected_sku');
        const inputHP = document.getElementById('nomor_hp');
        const labelOperator = document.getElementById('operator_label');

        const prefixMap = {
            '0811': 'telkomsel',
            '0812': 'telkomsel',
            '0813': 'telkomsel',
            '0821': 'telkomsel',
            '0822': 'telkomsel',
            '0852': 'telkomsel',
            '0853': 'telkomsel',
            '0817': 'xl',
            '0818': 'xl',
            '0819': 'xl',
            '0859': 'xl',
            '0877': 'xl',
            '0878': 'xl',
            '0814': 'indosat',
            '0815': 'indosat',
            '0816': 'indosat',
            '0855': 'indosat',
            '0856': 'indosat',
            '0857': 'indosat',
            '0858': 'indosat',
            '0895': 'three',
            '0896': 'three',
            '0897': 'three',
            '0898': 'three',
            '0899': 'three',
            '0831': 'axis',
            '0832': 'axis',
            '0833': 'axis',
            '0838': 'axis',
            '0881': 'smartfren',
            '0882': 'smartfren',
            '0883': 'smartfren',
            '0884': 'smartfren',
            '0885': 'smartfren'
        };

        inputHP.addEventListener('input', function() {
            const nomor = this.value;
            const prefix = nomor.substring(0, 4);
            const operator = prefixMap[prefix] || '';

            labelOperator.innerText = operator;
            cards.forEach(card => {
                const info = card.getAttribute('data-info');
                if (operator && (info.includes(operator))) {
                    card.style.display = 'block';
                } else if (!operator) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });


        cards.forEach(card => {
            card.addEventListener('click', function() {
                cards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                skuInput.value = this.getAttribute('data-sku');
            });
        });

        document.getElementById('btnProses').addEventListener('click', function() {
            if (!skuInput.value || !inputHP.value) {
                alert("Lengkapi nomor dan pilih produk!");
                return;
            }

            document.getElementById('confirm_target').innerText = inputHP.value;
            document.getElementById('confirm_product').innerText = document.querySelector('.product-card.active .product-name').innerText;
            document.getElementById('confirm_price').innerText = document.querySelector('.product-card.active .product-price').innerText;

            new bootstrap.Modal(document.getElementById('modalKonfirmasi')).show();
        });

        document.getElementById('btnLanjutPin').addEventListener('click', function() {
            bootstrap.Modal.getInstance(document.getElementById('modalKonfirmasi')).hide();
            new bootstrap.Modal(document.getElementById('modalPin')).show();
        });

        document.getElementById('btnFinalBayar').addEventListener('click', function() {
            const pin = document.getElementById('input_pin').value;
            const form = document.querySelector('form');

            const hPin = document.createElement('input');
            hPin.type = 'hidden';
            hPin.name = 'pin_transaksi';
            hPin.value = pin;
            const hBeli = document.createElement('input');
            hBeli.type = 'hidden';
            hBeli.name = 'beli';
            hBeli.value = '1';

            form.appendChild(hPin);
            form.appendChild(hBeli);
            form.submit();
        });
    });
</script>