<?php

/**
 * digiflazz_test.php
 * Simulasi manual request ke Digiflazz — standalone, tidak butuh framework
 * Taruh di root project, akses lewat browser atau CLI
 * HAPUS setelah selesai testing!
 */

// ── Kredensial langsung dari website_settings ─────────────────────
$API_USERNAME = 'yiyuteD282qg';
$API_KEY      = 'dev-7c368ea0-0e22-11f1-9ff0-bf9c50cdde71';
$API_URL      = 'https://api.digiflazz.com/v1/transaction';

// ── Ubah ini untuk testing ────────────────────────────────────────
$TEST_SKU     = 'danacek';          // ganti dengan sku yang mau ditest
$TEST_TARGET  = '085714705875';   // ganti dengan nomor tujuan
$TESTING_MODE = true;             // true = sandbox, false = production

// ─────────────────────────────────────────────────────────────────

$ref_id  = 'TEST-' . time() . '-' . rand(100, 999);
$sign    = md5($API_USERNAME . $API_KEY . $ref_id);

$payload = [
    'username'       => $API_USERNAME,
    'buyer_sku_code' => $TEST_SKU,
    'customer_no'    => $TEST_TARGET,
    'ref_id'         => $ref_id,
    'sign'           => $sign,
];

if ($TESTING_MODE) {
    $payload['testing'] = true;
}

// ── Hit API ───────────────────────────────────────────────────────
$ch = curl_init($API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 30,
]);

$raw      = curl_exec($ch);
$curlErr  = curl_errno($ch) ? curl_error($ch) : null;
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$response = json_decode($raw, true);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Digiflazz Test</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: ui-monospace, monospace;
            background: #0d1117;
            color: #e6edf3;
            padding: 24px;
        }

        h1 {
            font-size: 18px;
            font-weight: 700;
            color: #58a6ff;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 13px;
            font-weight: 700;
            color: #8b949e;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 20px 0 8px;
        }

        .card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .row {
            display: flex;
            gap: 12px;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .key {
            color: #79c0ff;
            min-width: 160px;
            flex-shrink: 0;
        }

        .val {
            color: #e6edf3;
            word-break: break-all;
        }

        .val.ok {
            color: #3fb950;
            font-weight: 700;
        }

        .val.err {
            color: #f85149;
            font-weight: 700;
        }

        .val.warn {
            color: #e3b341;
            font-weight: 700;
        }

        pre {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 14px;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge.ok {
            background: #1a4731;
            color: #3fb950;
        }

        .badge.err {
            background: #3d1f1f;
            color: #f85149;
        }

        .badge.warn {
            background: #3d2f0e;
            color: #e3b341;
        }

        hr {
            border: none;
            border-top: 1px solid #30363d;
            margin: 16px 0;
        }
    </style>
</head>

<body>

    <h1>🔌 Digiflazz API Test</h1>

    <!-- CREDENTIALS -->
    <h2>Kredensial</h2>
    <div class="card">
        <div class="row"><span class="key">API Username</span><span class="val"><?= htmlspecialchars($API_USERNAME) ?></span></div>
        <div class="row"><span class="key">API Key</span><span class="val"><?= htmlspecialchars(substr($API_KEY, 0, 8) . '****') ?></span></div>
        <div class="row"><span class="key">API URL</span><span class="val"><?= htmlspecialchars($API_URL) ?></span></div>
        <div class="row"><span class="key">Mode</span><span class="val <?= $TESTING_MODE ? 'warn' : 'ok' ?>"><?= $TESTING_MODE ? '⚠ SANDBOX / TESTING' : '🟢 PRODUCTION' ?></span></div>
    </div>

    <!-- REQUEST -->
    <h2>Request yang Dikirim</h2>
    <div class="card">
        <div class="row"><span class="key">ref_id</span><span class="val"><?= htmlspecialchars($ref_id) ?></span></div>
        <div class="row"><span class="key">buyer_sku_code</span><span class="val"><?= htmlspecialchars($TEST_SKU) ?></span></div>
        <div class="row"><span class="key">customer_no</span><span class="val"><?= htmlspecialchars($TEST_TARGET) ?></span></div>
        <div class="row"><span class="key">sign (md5)</span><span class="val"><?= htmlspecialchars($sign) ?></span></div>
        <div class="row"><span class="key">sign formula</span><span class="val">md5("<?= htmlspecialchars($API_USERNAME) ?>" + "<?= htmlspecialchars(substr($API_KEY, 0, 8)) ?>****" + "<?= htmlspecialchars($ref_id) ?>")</span></div>
        <hr>
        <h2 style="margin-top:0">JSON Payload</h2>
        <pre><?= htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    </div>

    <!-- HTTP INFO -->
    <h2>HTTP Info</h2>
    <div class="card">
        <div class="row">
            <span class="key">HTTP Status Code</span>
            <span class="val <?= $httpCode === 200 ? 'ok' : 'err' ?>"><?= $httpCode ?></span>
        </div>
        <?php if ($curlErr): ?>
            <div class="row"><span class="key">cURL Error</span><span class="val err"><?= htmlspecialchars($curlErr) ?></span></div>
        <?php endif; ?>
    </div>

    <!-- RAW RESPONSE -->
    <h2>Raw Response</h2>
    <div class="card">
        <pre><?= htmlspecialchars($raw ?: '(kosong / null)') ?></pre>
    </div>

    <!-- PARSED RESPONSE -->
    <h2>Parsed Response</h2>
    <?php if (!$response): ?>
        <div class="card">
            <div class="row"><span class="key">Status</span><span class="val err">❌ Gagal parse JSON</span></div>
        </div>
    <?php else:
        $data   = $response['data'] ?? null;
        $rc     = $data['rc']      ?? ($response['rc'] ?? '-');
        $status = $data['status']  ?? ($response['status'] ?? '-');
        $msg    = $data['message'] ?? ($response['message'] ?? '-');
        $sn     = $data['sn']      ?? '-';

        $statusClass = match (strtolower($status)) {
            'sukses', 'success' => 'ok',
            'gagal',  'failed'  => 'err',
            default             => 'warn',
        };
    ?>
        <div class="card">
            <div class="row">
                <span class="key">Status</span>
                <span class="val"><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span></span>
            </div>
            <div class="row"><span class="key">RC</span><span class="val <?= $rc === '00' ? 'ok' : 'err' ?>"><?= htmlspecialchars($rc) ?> <?= $rc === '00' ? '✅' : '❌' ?></span></div>
            <div class="row"><span class="key">Message</span><span class="val"><?= htmlspecialchars($msg) ?></span></div>
            <?php if ($sn && $sn !== '-'): ?>
                <div class="row"><span class="key">SN</span><span class="val ok"><?= htmlspecialchars($sn) ?></span></div>
            <?php endif; ?>
            <?php if (isset($data['customer_no'])): ?>
                <div class="row"><span class="key">customer_no (response)</span><span class="val"><?= htmlspecialchars($data['customer_no']) ?></span></div>
            <?php endif; ?>
            <?php if (isset($data['price'])): ?>
                <div class="row"><span class="key">Price (harga modal)</span><span class="val">Rp <?= number_format($data['price'], 0, ',', '.') ?></span></div>
            <?php endif; ?>
            <?php if (isset($data['buyer_last_saldo'])): ?>
                <div class="row"><span class="key">Saldo Digiflazz</span><span class="val">Rp <?= number_format($data['buyer_last_saldo'], 0, ',', '.') ?></span></div>
            <?php endif; ?>
            <hr>
            <h2 style="margin-top:0">data{} object</h2>
            <pre><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
    <?php endif; ?>

    <!-- DIAGNOSIS -->
    <h2>Diagnosis</h2>
    <div class="card">
        <?php
        $issues = [];

        if ($curlErr)
            $issues[] = ['err', "cURL error: $curlErr — periksa koneksi server ke internet"];

        if ($httpCode !== 200)
            $issues[] = ['err', "HTTP $httpCode — bukan 200 OK"];

        if (!$data)
            $issues[] = ['err', "Response tidak punya key 'data' — mungkin API_USERNAME/API_KEY salah"];

        if ($rc === '14' || str_contains(strtolower($msg), 'nomor'))
            $issues[] = ['err', "RC 14 / 'Nomor tujuan salah' — customer_no '$TEST_TARGET' ditolak vendor. Coba nomor lain atau cek format SKU '$TEST_SKU'"];

        if ($rc === '00')
            $issues[] = ['ok', "Transaksi diterima Digiflazz! Flow API berjalan normal."];

        if (strtolower($status) === 'pending')
            $issues[] = ['warn', "Status Pending — normal, Digiflazz sedang proses. Cek lagi dengan ref_id yang sama."];

        if (empty($issues))
            $issues[] = ['warn', "Tidak ada diagnosis otomatis — lihat parsed response di atas."];

        foreach ($issues as [$type, $text]): ?>
            <div class="row">
                <span class="key"><?= $type === 'ok' ? '✅' : ($type === 'err' ? '❌' : '⚠️') ?></span>
                <span class="val <?= $type ?>"><?= htmlspecialchars($text) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <p style="margin-top:20px;font-size:11px;color:#484f58">⚠️ Hapus file ini setelah selesai testing — mengandung kredensial API!</p>
</body>

</html>