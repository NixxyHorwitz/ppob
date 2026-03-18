<?php

/**
 * debug_tx.php — taruh di root project
 * Akses: https://ppob.bersamakita.my.id/debug_tx.php
 * HAPUS setelah selesai!
 */

// Basic auth agar tidak sembarangan diakses
$PASS = 'debug123';
if (($_GET['key'] ?? '') !== $PASS) {
    die('<h3>403 — Tambahkan ?key=debug123 ke URL</h3>');
}

$logFile = dirname(__FILE__) . '/logs/transaction_debug.log';

// Kalau ada ?clear=1 → kosongkan log
if (isset($_GET['clear'])) {
    file_put_contents($logFile, '');
    echo '<p>Log dikosongkan. <a href="?key=' . $PASS . '">Refresh</a></p>';
    exit;
}

// Kalau ada ?simulate=1 → simulasi langsung dari sini
if (isset($_GET['simulate'])) {
    require_once dirname(__FILE__) . '/config/database.php';
    require_once dirname(__FILE__) . '/core/api_handler.php';

    $sku    = $_GET['sku']    ?? 'xld25';
    $target = $_GET['target'] ?? '08123456789';
    $refId  = 'DBG-' . time() . '-' . rand(100, 999);
    $sign   = md5(API_USERNAME . API_KEY . $refId);

    $payload = [
        'username'       => API_USERNAME,
        'buyer_sku_code' => $sku,
        'customer_no'    => $target,
        'ref_id'         => $refId,
        'sign'           => $sign,
    ];

    $res = hitVendor($payload);

    echo '<pre style="background:#111;color:#0f0;padding:20px;font-size:13px">';
    echo "=== SIMULATE REQUEST ===\n";
    echo "API_USERNAME : " . API_USERNAME . "\n";
    echo "API_URL      : " . API_URL . "\n";
    echo "SKU          : $sku\n";
    echo "TARGET       : $target\n";
    echo "REF_ID       : $refId\n";
    echo "SIGN         : $sign\n";
    echo "SIGN CHECK   : md5(" . API_USERNAME . " + " . substr(API_KEY, 0, 8) . "**** + $refId)\n\n";
    echo "=== PAYLOAD SENT ===\n";
    echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";
    echo "=== RESPONSE ===\n";
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo '</pre>';
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Transaction Debug</title>
    <style>
        body {
            font-family: monospace;
            background: #0d1117;
            color: #e6edf3;
            padding: 20px;
            margin: 0;
        }

        h2 {
            color: #58a6ff;
            font-size: 15px;
            margin: 16px 0 8px;
        }

        pre {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 14px;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 600px;
            overflow-y: auto;
        }

        .entry {
            border-bottom: 1px solid #21262d;
            padding: 8px 0;
        }

        .tag-SERVICES_POST {
            color: #79c0ff;
            font-weight: bold;
        }

        .tag-PRABAYAR_REQUEST {
            color: #e3b341;
            font-weight: bold;
        }

        .tag-PRABAYAR_RESPONSE {
            color: #3fb950;
            font-weight: bold;
        }

        .tag-INQ_PASCA_REQUEST {
            color: #d2a8ff;
            font-weight: bold;
        }

        .tag-INQ_PASCA_RESPONSE {
            color: #3fb950;
            font-weight: bold;
        }

        .tag-PAY_PASCA_REQUEST {
            color: #ffa657;
            font-weight: bold;
        }

        .tag-PAY_PASCA_RESPONSE {
            color: #3fb950;
            font-weight: bold;
        }

        .err {
            color: #f85149;
        }

        .ok {
            color: #3fb950;
        }

        .btn {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            text-decoration: none;
            background: #21262d;
            color: #e6edf3;
            font-size: 12px;
            margin-right: 8px;
            border: 1px solid #30363d;
        }

        .btn:hover {
            background: #30363d;
        }

        .btn.red {
            background: #3d1f1f;
            color: #f85149;
            border-color: #f85149;
        }

        form {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            margin-top: 12px;
        }

        input {
            background: #161b22;
            border: 1px solid #30363d;
            color: #e6edf3;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <h2>🔍 Transaction Debug Log</h2>

    <a class="btn" href="?key=<?= $PASS ?>&simulate=1&sku=xld25&target=085714705875">▶ Quick Simulate</a>
    <a class="btn red" href="?key=<?= $PASS ?>&clear=1" onclick="return confirm('Kosongkan log?')">🗑 Clear Log</a>

    <!-- Custom simulate form -->
    <form action="" method="get">
        <input type="hidden" name="key" value="<?= $PASS ?>">
        <input type="hidden" name="simulate" value="1">
        <input type="text" name="sku" placeholder="SKU (e.g. xld25)" value="xld25" style="width:140px">
        <input type="text" name="target" placeholder="Nomor tujuan" value="085714705875" style="width:150px">
        <button type="submit" class="btn" style="cursor:pointer">▶ Simulate</button>
    </form>

    <h2>📄 Log File: /logs/transaction_debug.log</h2>

    <?php
    if (!file_exists($logFile) || filesize($logFile) === 0): ?>
        <pre class="err">Log kosong atau belum ada.
Artinya: transaction.php yang baru BELUM di-deploy ke server,
atau belum ada transaksi yang dilakukan setelah deploy.

Coba klik "Quick Simulate" di atas untuk test langsung dari sini.</pre>
    <?php else:
        $lines = array_reverse(array_filter(explode("\n", file_get_contents($logFile))));
        echo '<pre>';
        foreach (array_slice($lines, 0, 200) as $line) {
            if (!trim($line)) continue;
            // Highlight tag
            preg_match('/\[([A-Z_]+)\]/', $line, $m);
            $tag = $m[1] ?? '';

            // Parse JSON bagian akhir
            $jsonStart = strpos($line, '] {');
            if ($jsonStart !== false) {
                $prefix  = substr($line, 0, $jsonStart + 2);
                $jsonStr = substr($line, $jsonStart + 2);
                $pretty  = json_encode(json_decode($jsonStr), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $line    = htmlspecialchars($prefix) . "\n" . htmlspecialchars($pretty ?: $jsonStr);
            } else {
                $line = htmlspecialchars($line);
            }

            // Color tag
            if ($tag) {
                $line = str_replace(
                    "[{$tag}]",
                    "<span class='tag-{$tag}'>[{$tag}]</span>",
                    $line
                );
            }

            // Highlight error keywords
            $line = preg_replace('/(Gagal|Error|salah|failed|error|rc":"(?!00))/i', '<span class="err">$1</span>', $line);
            $line = preg_replace('/(Sukses|sukses|success|rc":"00)/i', '<span class="ok">$1</span>', $line);

            echo '<div class="entry">' . $line . '</div>';
        }
        echo '</pre>';
        echo '<p style="color:#8b949e;font-size:11px">Menampilkan ' . min(count($lines), 200) . ' baris terakhir (urutan terbaru di atas)</p>';
    endif;
    ?>

    <p style="margin-top:24px;color:#484f58;font-size:11px">⚠️ Hapus file ini setelah selesai debugging!</p>
</body>

</html>