<?php
// transaction.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$stmt = $pdo->query("SELECT api_username, api_key, api_url FROM website_settings LIMIT 1");
$api = $stmt->fetch(PDO::FETCH_ASSOC);

define('API_USERNAME', $api['api_username']);
define('API_KEY', $api['api_key']);
define('API_URL', $api['api_url']);

function prosesTransaksi($userId, $sku, $target, $pin)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT username, saldo, pin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) return "User tidak ditemukan.";

    if ($pin !== $user['pin']) {
        return "PIN yang Anda masukkan salah.";
    }

    $stmt = $pdo->prepare("SELECT price_sell FROM products WHERE sku_code = ? AND status = 'active'");
    $stmt->execute([$sku]);
    $product = $stmt->fetch();

    if (!$product) return "Produk tidak ditemukan/tidak aktif.";
    if ($user['saldo'] < $product['price_sell']) return "Saldo tidak cukup.";

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id = ?");
        $stmt->execute([$product['price_sell'], $userId]);
        $trxIdInternal = 'TRX' . time() . rand(100, 999);
        $harga = $product['price_sell'];
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, ref_id, sku_code, target, amount, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$userId, $trxIdInternal, $sku, $target, $harga]);

        $sign = md5(API_USERNAME . API_KEY . $trxIdInternal);


        $vendorRes = hitVendor('/transaction', [
            'username' => API_USERNAME,
            'ref_id' => $trxIdInternal,
            'buyer_sku_code' => $sku,
            'customer_no' => $target,
            'sign' => $sign
            //'testing' => true 
        ]);

        if (is_null($vendorRes) || !is_array($vendorRes)) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return "Gagal: Server vendor tidak memberikan respon (Timeout/Error).";
        }


        if (isset($vendorRes['data'])) {
            $statusResponse = $vendorRes['data']['status'] ?? '';

            if ($statusResponse == 'Gagal') {
                $pdo->rollBack();
                return "Transaksi Gagal: " . ($vendorRes['data']['message'] ?? 'Ditolak vendor');
            }

            $pdo->commit();
            $username = $user['username'];

            notifyUser($pdo, $userId, "Transaksi Diproses", "Pesanan produk $sku dengan ID: [$trxIdInternal] sedang diproses.");
            notifyAdmins($pdo, "Transaksi Baru", "User $username membeli $sku dengan ID: [$trxIdInternal]");

            return "Transaksi sedang diproses (Status: $statusResponse)";
        } else {
            $pdo->rollBack();
            $errorMsg = $vendorRes['message'] ?? 'Format respon tidak dikenali.';
            return "Gagal: " . $errorMsg;
        }
    } catch (\Exception $e) {
        $pdo->rollBack();
        return "System Error: " . $e->getMessage();
    }
}


function cekTagihanPasca($user_id, $sku, $target)
{
    global $pdo;

    $user = $pdo->prepare("SELECT saldo FROM users WHERE id = ?");
    $user->execute([$user_id]);
    $userData = $user->fetch();

    $username = API_USERNAME;
    $apiKey = API_KEY;
    $refId = "CEK" . time() . rand(100, 999);
    $sign = md5($username . $apiKey . $refId);

    $payload = [
        'commands' => 'inq-pasca',
        'username' => $username,
        'buyer_sku_code' => $sku,
        'customer_no' => $target,
        'ref_id' => $refId,
        'sign' => $sign
    ];

    $ch = curl_init("https://api.digiflazz.com/v1/transaction");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);


    $res = json_decode($response, true);

    if (isset($res['data'])) {
        return $res['data'];
    }


    $msg = $res['data']['message'] ?? $response;

    return [
        'rc' => '99',
        'message' => 'Digiflazz Error: ' . $msg
    ];
}


function bayarTagihanPasca($user_id, $sku, $target, $refIdInquiry, $pin)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($pin !== $user['pin']) {
        return "PIN yang Anda masukkan salah.";
    }

    $username = API_USERNAME;
    $apiKey = API_KEY;
    $sign = md5($username . $apiKey . $refIdInquiry);

    $payload = [
        'commands' => 'pay-pasca',
        'username' => $username,
        'buyer_sku_code' => $sku,
        'customer_no' => $target,
        'ref_id' => $refIdInquiry,
        'sign' => $sign
    ];

    $ch = curl_init("https://api.digiflazz.com/v1/transaction");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    curl_close($ch);

    $res = json_decode($response, true);
    $data = $res['data'];

    if ($data['rc'] == '00') {
        $totalBayar = $data['selling_price'];
        $refIdInquiry = $data['ref_id'];
        $sn = $data['sn'];

        if ($user['saldo'] < $totalBayar) {
            return "Saldo tidak cukup!";
        }

        $pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id = ?")->execute([$totalBayar, $user_id]);

        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, sku_code, target, amount, ref_id, sn, status, type) VALUES (?, ?, ?, ?, ?, ?, 'success', 'pascabayar')");
        $stmt->execute([$user_id, $sku, $target, $totalBayar, $refIdInquiry, $sn]);
        $username = $user['username'];
        notifyUser($pdo, $user_id, "Pembayaran Berhasil", "Tagihan $sku dengan ID: [$refIdInquiry] sukses dibayar.");
        notifyAdmins($pdo, "Tagihan Terbayar", "User $username membayar pascabayar $sku dengan ID: [$refIdInquiry]");

        return "Pembayaran Berhasil!";
    }

    return "Gagal: " . $data['message'];
}
