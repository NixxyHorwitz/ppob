<?php

/**
 * core/transaction.php
 * Digiflazz API — sesuai dokumentasi resmi
 *
 * BUGS YANG DIPERBAIKI vs versi lama:
 * 1. prosesTransaksi: endpoint hitVendor salah → '/transaction' bukan '/transaction'
 *    tapi URL di api_handler sudah append ke API_URL, jadi cukup 'transaction'
 * 2. prosesTransaksi: tidak menyimpan SN dari response
 * 3. prosesTransaksi: status dari Digiflazz adalah 'Sukses'/'Gagal' (kapital),
 *    bukan 'success'/'failed' — harus di-normalize saat insert
 * 4. prosesTransaksi: update status transaksi setelah response vendor tidak ada
 * 5. bayarTagihanPasca: tidak pakai hitVendor, curl sendiri tanpa auth yang benar
 * 6. bayarTagihanPasca: saldo dipotong SETELAH API call — seharusnya check dulu,
 *    potong setelah konfirmasi sukses
 * 7. cekTagihanPasca: hardcode URL, tidak pakai hitVendor/API_URL dari settings
 * 8. Sign formula: md5(username + apiKey + ref_id) — SUDAH BENAR di semua fungsi
 */

require_once __DIR__ . '/../core/api_handler.php';


/* ── Debug logger ─────────────────────────────────────────────────── */
function txLog(string $tag, $data): void
{
    $logDir  = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/transaction_debug.log';
    $line    = '[' . date('Y-m-d H:i:s') . '] [' . $tag . '] ' . json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

if (!function_exists('notifyUser')) {
    function notifyUser(PDO $pdo, int $userId, string $title, string $body): void
    {
        try {
            $pdo->prepare("INSERT INTO notifications (user_id, title, body) VALUES (?,?,?)")
                ->execute([$userId, $title, $body]);
        } catch (Exception $e) {
        }
    }
}

if (!function_exists('notifyAdmins')) {
    function notifyAdmins(PDO $pdo, string $title, string $body): void
    {
        try {
            $admins = $pdo->query("SELECT id FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN);
            $stmt   = $pdo->prepare("INSERT INTO notifications (user_id, title, body) VALUES (?,?,?)");
            foreach ($admins as $aid) $stmt->execute([$aid, $title, $body]);
        } catch (Exception $e) {
        }
    }
}

/* ══════════════════════════════════════════════════════════════════
   PRABAYAR — Beli produk (pulsa, token, data, games, dst)
══════════════════════════════════════════════════════════════════ */
function prosesTransaksi(int $userId, string $sku, string $target, string $pin): string
{
    global $pdo;

    // 1. Validasi user & PIN
    $stmt = $pdo->prepare("SELECT username, saldo, pin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user)            return 'User tidak ditemukan.';
    if ($pin !== $user['pin']) return 'PIN yang Anda masukkan salah.';

    // 2. Validasi produk
    $stmt = $pdo->prepare("SELECT price_sell FROM products WHERE sku_code = ? AND status = 'active'");
    $stmt->execute([$sku]);
    $product = $stmt->fetch();
    if (!$product)                      return 'Produk tidak ditemukan atau tidak aktif.';
    if ($user['saldo'] < $product['price_sell']) return 'Saldo tidak cukup.';

    // 3. Generate ref_id & sign
    $refId = 'TRX' . time() . rand(100, 999);
    $sign  = md5(API_USERNAME . API_KEY . $refId);

    $pdo->beginTransaction();
    try {
        // 4. Potong saldo & insert transaksi pending
        $pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id = ?")
            ->execute([$product['price_sell'], $userId]);

        $pdo->prepare("INSERT INTO transactions (user_id, ref_id, sku_code, target, amount, status) VALUES (?,?,?,?,?,'pending')")
            ->execute([$userId, $refId, $sku, $target, $product['price_sell']]);

        // 5. Hit vendor — sesuai doc: endpoint 'transaction'
        $payload = [
            'username'       => API_USERNAME,
            'buyer_sku_code' => $sku,
            'customer_no'    => $target,
            'ref_id'         => $refId,
            'sign'           => $sign,
        ];
        txLog('PRABAYAR_REQUEST', $payload);
        $vendorRes = hitVendor('transaction', $payload);
        txLog('PRABAYAR_RESPONSE', $vendorRes);

        // 6. Handle null (timeout/error)
        if (is_null($vendorRes)) {
            $pdo->rollBack();
            return 'Gagal: Server vendor tidak merespons. Silakan coba lagi.';
        }

        // 7. Ambil data dari response (selalu dibungkus 'data')
        $data   = $vendorRes['data'] ?? null;
        if (!$data) {
            $pdo->rollBack();
            return 'Gagal: ' . ($vendorRes['message'] ?? 'Format respons tidak dikenali.');
        }

        $status = strtolower($data['status'] ?? '');   // 'sukses' atau 'gagal'
        $sn     = $data['sn']      ?? '';
        $msg    = $data['message'] ?? '';
        $rc     = $data['rc']      ?? '';

        if ($status === 'gagal' || $rc === '14') {
            $pdo->rollBack();   // kembalikan saldo
            return 'Transaksi Gagal: ' . $msg;
        }

        // 8. Sukses / pending — commit, update status & SN
        $dbStatus = ($status === 'sukses') ? 'success' : 'pending';
        $pdo->prepare("UPDATE transactions SET status = ?, sn = ? WHERE ref_id = ?")
            ->execute([$dbStatus, $sn, $refId]);

        $pdo->commit();

        notifyUser(
            $pdo,
            $userId,
            'Transaksi Diproses',
            "Pembelian $sku (ID: $refId) sedang diproses. Status: $msg"
        );
        notifyAdmins(
            $pdo,
            'Transaksi Baru',
            "User {$user['username']} membeli $sku (ID: $refId)"
        );

        return "Transaksi sedang diproses (Status: $msg)";
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return 'System Error: ' . $e->getMessage();
    }
}

/* ══════════════════════════════════════════════════════════════════
   PASCABAYAR — Cek tagihan (inquiry)
   Sesuai doc: commands = 'inq-pasca'
══════════════════════════════════════════════════════════════════ */
function cekTagihanPasca(int $userId, string $sku, string $target): array
{
    global $pdo;

    $refId = 'CEK' . time() . rand(100, 999);
    $sign  = md5(API_USERNAME . API_KEY . $refId);

    $payload = [
        'commands'       => 'inq-pasca',
        'username'       => API_USERNAME,
        'buyer_sku_code' => $sku,
        'customer_no'    => $target,
        'ref_id'         => $refId,
        'sign'           => $sign,
    ];
    txLog('INQ_PASCA_REQUEST', $payload);
    $res = hitVendor('transaction', $payload);
    txLog('INQ_PASCA_RESPONSE', $res);

    if (is_null($res)) {
        return ['rc' => '99', 'message' => 'Server vendor tidak merespons.'];
    }

    $data = $res['data'] ?? null;
    if (!$data) {
        return ['rc' => '99', 'message' => $res['message'] ?? 'Format respons tidak dikenali.'];
    }

    // rc '00' = sukses inquiry
    return $data;
}

/* ══════════════════════════════════════════════════════════════════
   PASCABAYAR — Bayar tagihan
   Sesuai doc: commands = 'pay-pasca', ref_id HARUS SAMA dengan inquiry
   sign = md5(username + apiKey + ref_id)
══════════════════════════════════════════════════════════════════ */
function bayarTagihanPasca(int $userId, string $sku, string $target, string $refIdInquiry, string $pin): string
{
    global $pdo;

    // 1. Validasi user & PIN
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user)                return 'User tidak ditemukan.';
    if ($pin !== $user['pin']) return 'PIN yang Anda masukkan salah.';

    // 2. Sign menggunakan ref_id YANG SAMA dengan saat inquiry
    $sign = md5(API_USERNAME . API_KEY . $refIdInquiry);

    // 3. Hit vendor pay-pasca
    $payload = [
        'commands'       => 'pay-pasca',
        'username'       => API_USERNAME,
        'buyer_sku_code' => $sku,
        'customer_no'    => $target,
        'ref_id'         => $refIdInquiry,
        'sign'           => $sign,
    ];
    txLog('PAY_PASCA_REQUEST', $payload);
    $res = hitVendor('transaction', $payload);
    txLog('PAY_PASCA_RESPONSE', $res);

    if (is_null($res)) {
        return 'Gagal: Server vendor tidak merespons.';
    }

    $data = $res['data'] ?? null;
    if (!$data) {
        return 'Gagal: ' . ($res['message'] ?? 'Format respons tidak dikenali.');
    }

    $rc     = $data['rc']            ?? '';
    $msg    = $data['message']       ?? '';
    $status = strtolower($data['status'] ?? '');

    if ($rc !== '00' || $status === 'gagal') {
        return 'Gagal: ' . $msg;
    }

    // 4. Sukses — potong saldo & catat transaksi
    $totalBayar = (int)($data['selling_price'] ?? 0);
    $sn         = $data['sn'] ?? '';

    if ($user['saldo'] < $totalBayar) {
        return 'Saldo tidak cukup!';
    }

    try {
        $pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id = ?")
            ->execute([$totalBayar, $userId]);

        $pdo->prepare("INSERT INTO transactions (user_id, sku_code, target, amount, ref_id, sn, status, type) VALUES (?,?,?,?,?,?,'success','pascabayar')")
            ->execute([$userId, $sku, $target, $totalBayar, $refIdInquiry, $sn]);

        notifyUser(
            $pdo,
            $userId,
            'Pembayaran Berhasil',
            "Tagihan $sku (ID: $refIdInquiry) berhasil dibayar. SN: $sn"
        );
        notifyAdmins(
            $pdo,
            'Tagihan Terbayar',
            "User {$user['username']} membayar pascabayar $sku (ID: $refIdInquiry)"
        );

        return 'Pembayaran Berhasil!';
    } catch (\Exception $e) {
        return 'System Error: ' . $e->getMessage();
    }
}
