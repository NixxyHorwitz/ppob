<?php

/**
 * ==========================================
 * PPOB UNIVERSAL API REQUEST (ONE FILE)
 * ==========================================
 * Cara pakai:
 * ?action=saldo
 * ?action=products
 * ?action=deposit& amount=50000&method=QRIS
 * ?action=order&sku=PLN20&target=08123456789&ref=INV001
 */

header("Content-Type: application/json");

/* ==========================
   CONFIG
========================== */
$API_KEY = "083018b886638ab0bc9762e2a706a3d2015ac7681758e814";
$PIN     = "268808";

$BASE_URL = "https://ppob.bersamakita.my.id/api/v1/";


/* ==========================
   CURL REQUEST FUNCTION
========================== */
function send_request($endpoint, $params = [])
{
    global $API_KEY, $PIN, $BASE_URL;

    $params['api_key'] = $API_KEY;
    $params['pin']     = $PIN;

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $BASE_URL . $endpoint,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return [
            "status" => false,
            "message" => curl_error($ch)
        ];
    }

    curl_close($ch);

    return json_decode($response, true);
}


/* ==========================
   ROUTER ACTION
========================== */

$action = $_GET['action'] ?? '';

switch ($action) {

    /* ======================
       CEK SALDO
    ====================== */
    case "saldo":
        echo json_encode(
            send_request("cek_saldo.php"),
            JSON_PRETTY_PRINT
        );
        break;


    /* ======================
       DAFTAR PRODUK
    ====================== */
    case "price-list":
        echo json_encode(
            send_request("price-list.php"),
            JSON_PRETTY_PRINT
        );
        break;


    /* ======================
       DEPOSIT
       ?action=deposit&amount=50000&method=QRIS
    ====================== */
    case "deposit":

        $amount = $_GET['amount'] ?? 0;
        $method = $_GET['method'] ?? 'QRIS';

        if ($amount < 1000) {
            echo json_encode([
                "status" => false,
                "message" => "Minimal deposit 1000"
            ]);
            exit;
        }

        echo json_encode(
            send_request("deposit.php", [
                "amount" => $amount,
                "method" => strtoupper($method)
            ]),
            JSON_PRETTY_PRINT
        );
        break;


    /* ======================
       ORDER TRANSAKSI
       ?action=order&sku=PLN20&target=08123&ref=INV001
    ====================== */
    case "orders":

        $sku    = $_GET['sku'] ?? '';
        $target = $_GET['target'] ?? '';
        $ref    = $_GET['ref'] ?? '';

        if (!$sku || !$target || !$ref) {
            echo json_encode([
                "status" => false,
                "message" => "Parameter tidak lengkap"
            ]);
            exit;
        }

        echo json_encode(
            send_request("orders.php", [
                "sku_code" => $sku,
                "target"   => $target,
                "ref_id"   => $ref
            ]),
            JSON_PRETTY_PRINT
        );
        break;


    /* ======================
       DEFAULT HELP
    ====================== */
    default:
        echo json_encode([
            "status" => true,
            "message" => "PPOB API CLIENT READY",
            "usage" => [
                "?action=saldo",
                "?action=price-list",
                "?action=deposit&amount=50000&method=QRIS",
                "?action=orders&sku=PLN20&target=081236565&ref=INV001"
            ]
        ], JSON_PRETTY_PRINT);
}
