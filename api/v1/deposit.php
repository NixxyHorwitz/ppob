<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/qrcode/qrlib.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors',0);

/* ===============================
   VALIDASI METHOD
================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => false,
        "message" => "Invalid request"
    ]);
    exit;
}

/* ===============================
   AMBIL INPUT
================================= */
$api_key = trim($_POST['api_key'] ?? '');
$pin     = trim($_POST['pin'] ?? '');
$amount  = (int)($_POST['amount'] ?? 0);
$method  = strtoupper(trim($_POST['method'] ?? ''));

/* ===============================
   VALIDASI INPUT
================================= */
if (!$api_key || !$pin || !$amount || !$method) {
    echo json_encode([
        "status"=>false,
        "message"=>"Parameter tidak lengkap"
    ]);
    exit;
}

if ($amount < 1000) {
    echo json_encode([
        "status"=>false,
        "message"=>"Minimal deposit 1000"
    ]);
    exit;
}

if ($amount > 1000000) {
    echo json_encode([
        "status"=>false,
        "message"=>"Maksimal deposit 1.000.000"
    ]);
    exit;
}

if (!in_array($method,['QRIS'])) {
    echo json_encode([
        "status"=>false,
        "message"=>"Metode tidak valid"
    ]);
    exit;
}

try {

    /* ===============================
       VALIDASI USER API
    ================================= */
    $stmt = $pdo->prepare("
        SELECT id, pin
        FROM users
        WHERE api_key = ?
        AND is_active = 1
        LIMIT 1
    ");

    $stmt->execute([$api_key]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("API Key tidak ditemukan");
    }

    if ($pin !== $user['pin']) {
        throw new Exception("PIN salah");
    }

    $user_id = $user['id'];

    /* ===============================
       GENERATE ID
    ================================= */
    $external_id = "API-TOPUP-" . bin2hex(random_bytes(6));

    /* ===============================
       UNIQUE CODE
    ================================= */
    $unique_code = random_int(1,999);
    $amount_original = $amount;
    $total_amount    = $amount + $unique_code;

    $qr_string   = null;
    $payment_info = [];
    $qr_file = null;
    
     /* =====================================================
       ✅ AMBIL QRIS MASTER
    ===================================================== */
    $stmtQris = $pdo->query("SELECT qris_code FROM website_settings LIMIT 1");
    $qrisData = $stmtQris->fetch(PDO::FETCH_ASSOC);

    if (!$qrisData || empty($qrisData['qris_code'])) {
        throw new Exception("QRIS belum disetting admin");
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    $qrisMaster = trim($qrisData['qris_code']);

    /* ===============================
       QRIS PAYMENT (LOCAL GENERATE)
    ================================= */
    if ($method === 'QRIS') {

        // generate QRIS string dari fungsi kamu
        $qr_string = generateDynamicQRIS($qrisMaster, $total_amount);

        if (!$qr_string) {
            throw new Exception("QRIS string gagal dibuat");
        }

        // lokasi file
        $qr_file = "/uploads/qris/".$external_id.".png";
        $qr_path = __DIR__."/../../".$qr_file;

        // buat folder jika belum ada
        if (!is_dir(dirname($qr_path))) {
            if (!mkdir(dirname($qr_path), 0755, true)) {
                throw new Exception("Gagal membuat folder QRIS");
            }
        }

        // generate QR IMAGE LOCAL (TANPA INTERNET)
        QRcode::png(
            $qr_string,
            $qr_path,
            QR_ECLEVEL_H,
            6,
            2
        );

        if (!file_exists($qr_path)) {
            throw new Exception("QR image gagal dibuat");
        }

        $payment_info = [
            "qr_image" => BASE_URL.$qr_file
        ];
    }

    /* ===============================
       INSERT TOPUP
    ================================= */
    $insert = $pdo->prepare("
        INSERT INTO topup_history
        (user_id, external_id, amount_original, amount, qr_string, status, payment_method)
        VALUES (?,?,?,?,?,'pending',?)
    ");

    $insert->execute([
        $user_id,
        $external_id,
        $amount_original,
        $total_amount,
        $qr_file,
        'QRIS'
    ]);

    /* ===============================
       RESPONSE SUCCESS
    ================================= */
    echo json_encode([
        "status"=>true,
        "message"=>"Deposit berhasil dibuat",
        "data"=>[
            "external_id"=>$external_id,
            "amount_original"=>$amount_original,
            "unique_code"=>$unique_code,
            "total_transfer"=>$total_amount,
            "payment_method"=>'QRIS',
            "status"=>"pending",
            "payment"=>$payment_info
        ]
    ]);

} catch (Exception $e) {

    error_log($e->getMessage());

    echo json_encode([
        "status"=>false,
        "error"=>$e->getMessage()
    ]);
}