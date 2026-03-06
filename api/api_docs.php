<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
/* ===============================
   API DOCUMENTATION PAGE
================================ */
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>API Documentation</title>

<style>

body{
    margin:0;
    font-family: Arial, Helvetica, sans-serif;
    background:#0f172a;
    color:#e2e8f0;
}

/* HEADER */
.header{
    background:linear-gradient(135deg,#2563eb,#1e40af);
    padding:30px;
    text-align:center;
    box-shadow:0 5px 20px rgba(0,0,0,.4);
}

.header h1{
    margin:0;
    font-size:28px;
}

.header p{
    opacity:.8;
}

/* CONTAINER */
.container{
    max-width:1100px;
    margin:auto;
    padding:30px 20px;
}

/* CARD */
.card{
    background:#1e293b;
    border-radius:12px;
    padding:20px;
    margin-bottom:25px;
    box-shadow:0 10px 25px rgba(0,0,0,.35);
}

.card h2{
    margin-top:0;
    color:#38bdf8;
}

/* ENDPOINT BADGE */
.endpoint{
    background:#020617;
    padding:10px;
    border-radius:8px;
    font-family:monospace;
    margin:10px 0;
    border-left:4px solid #22c55e;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}

table th{
    background:#0f172a;
    padding:10px;
    text-align:left;
}

table td{
    padding:10px;
    border-top:1px solid #334155;
}

/* CODE BLOCK */
.code{
    background:#020617;
    padding:15px;
    border-radius:8px;
    overflow:auto;
    font-family:monospace;
    color:#22c55e;
    margin-top:10px;
}

/* FOOTER */
.footer{
    text-align:center;
    padding:20px;
    opacity:.6;
    font-size:13px;
}

.badge{
    background:#22c55e;
    color:#000;
    padding:3px 8px;
    border-radius:6px;
    font-size:12px;
    font-weight:bold;
}

</style>
</head>

<body>

<div class="header">
    <h1>🚀 Provider API Documentation</h1>
    <p>Integrasi API untuk Cek Saldo, Deposit, Produk & Order</p>
</div>

<div class="container">

<!-- AUTH -->
<div class="card">
<h2>🔐 Authentication</h2>

<p>Semua endpoint menggunakan metode <span class="badge">POST</span></p>
<p>Endpoint : https://ppob.bersamakita.my.id/api/v1/</p>

<table>
<tr>
<th>Parameter</th>
<th>Type</th>
<th>Wajib</th>
<th>Keterangan</th>
</tr>
<tr>
<td>api_key</td>
<td>string</td>
<td>Ya</td>
<td>API Key user</td>
</tr>
<tr>
<td>pin</td>
<td>string</td>
<td>Ya</td>
<td>PIN akun</td>
</tr>
</table>
</div>


<!-- CEK SALDO -->
<div class="card">
<h2>💰 Cek Saldo</h2>

<div class="endpoint">
POST /api/v1/cek_saldo
</div>

<p>Endpoint ini digunakan untuk mengambil informasi akun dan saldo user.</p>

<table>
<tr>
<th>Parameter</th>
<th>Type</th>
<th>Wajib</th>
<th>Keterangan</th>
</tr>
<tr>
<td>api_key</td>
<td>string</td>
<td>Ya</td>
<td>API Key user</td>
</tr>
<tr>
<td>pin</td>
<td>string</td>
<td>Ya</td>
<td>PIN akun</td>
</tr>
</table>

<h3>✅ Response Berhasil</h3>

<div class="code">
{
  "status": true,
  "data": {
    "fullname": "Budi Santoso",
    "email": "budi@email.com",
    "saldo": 150000
  }
}
</div>

<h3>❌ Response Gagal</h3>

<div class="code">
{
  "status": false,
  "message": "API Key atau PIN salah"
}
</div>

</div>

<!-- DEPOSIT -->
<div class="card">
<h2>🏦 Deposit (Topup Saldo)</h2>

<div class="endpoint">
POST /api/v1/deposit
</div>

<p>Endpoint untuk membuat permintaan deposit saldo menggunakan metode pembayaran QRIS atau BANK Transfer.</p>

<table>
<tr>
<th>Parameter</th>
<th>Type</th>
<th>Wajib</th>
<th>Keterangan</th>
</tr>
<tr>
<td>api_key</td>
<td>string</td>
<td>Ya</td>
<td>API Key user</td>
</tr>
<tr>
<td>pin</td>
<td>string</td>
<td>Ya</td>
<td>PIN akun</td>
</tr>
<tr>
<td>amount</td>
<td>number</td>
<td>Ya</td>
<td>Nominal deposit (minimal 1000)</td>
</tr>
<tr>
<td>method</td>
<td>string</td>
<td>Ya</td>
<td>QRIS</td>
</tr>
</table>

<h3>✅ Response Berhasil</h3>

<div class="code">
{
  "status": true,
  "message": "Deposit berhasil dibuat",
  "data": {
    "external_id": "API-TOPUP-1700000000-12",
    "amount_original": 50000,
    "unique_code": 123,
    "total_transfer": 50123,
    "payment_method": "QRIS",
    "status": "pending",
    "payment": {
      "qr_image": "https://domain.com/uploads/qris/API-TOPUP.png"
    }
  }
}
</div>

<h3>❌ Response Gagal</h3>

<div class="code">
{
  "status": false,
  "message": "Parameter tidak lengkap"
}
</div>

</div>


<!-- PRODUK -->
<div class="card">
<h2>📦 Daftar Produk</h2>

<div class="endpoint">
POST /api/v1/price-list
</div>

<p>Endpoint untuk mengambil daftar produk aktif yang tersedia pada sistem.</p>

<table>
<tr>
<th>Parameter</th>
<th>Type</th>
<th>Wajib</th>
<th>Keterangan</th>
</tr>
<tr>
<td>api_key</td>
<td>string</td>
<td>Ya</td>
<td>API Key user</td>
</tr>
<tr>
<td>pin</td>
<td>string</td>
<td>Ya</td>
<td>PIN akun</td>
</tr>
</table>

<h3>✅ Response Berhasil</h3>

<div class="code">
{
  "status": true,
  "message": "Berhasil mengambil data produk",
  "client": {
    "fullname": "Budi Santoso",
    "email": "budi@email.com"
  },
  "total_products": 2,
  "data": [
    {
      "sku_code": "PLN20",
      "product_name": "PLN Token 20K",
      "category": "PLN",
      "type": "PREPAID",
      "brand": "PLN",
      "price_sell": 21500,
      "status": "active"
    }
  ]
}
</div>

<h3>❌ Response Gagal</h3>

<div class="code">
{
  "status": false,
  "message": "API Key atau PIN salah"
}
</div>

</div>


<!-- ORDER -->
<div class="card">
<h2>⚡ Orders (Transaksi)</h2>

<div class="endpoint">
POST /api/v1/orders
</div>

<p>Endpoint untuk membuat transaksi pembelian produk menggunakan saldo akun.</p>

<table>
<tr>
<th>Parameter</th>
<th>Type</th>
<th>Wajib</th>
<th>Keterangan</th>
</tr>

<tr>
<td>api_key</td>
<td>string</td>
<td>Ya</td>
<td>API Key user</td>
</tr>

<tr>
<td>pin</td>
<td>string</td>
<td>Ya</td>
<td>PIN akun (hash verification)</td>
</tr>

<tr>
<td>sku_code</td>
<td>string</td>
<td>Ya</td>
<td>Kode produk</td>
</tr>

<tr>
<td>target</td>
<td>string</td>
<td>Ya</td>
<td>Nomor tujuan / ID pelanggan</td>
</tr>

<tr>
<td>ref_id</td>
<td>string</td>
<td>Ya (unik)</td>
<td>ID referensi transaksi dari client (tidak boleh sama)</td>
</tr>

</table>

<h3>✅ Response Berhasil</h3>

<div class="code">
{
  "status": true,
  "message": "Order berhasil dibuat",
  "data": {
    "trx_id": 1021,
    "ref_id": "INV-88921",
    "target": "081234567890",
    "sku_code": "PLN20",
    "price": 21500,
    "status": "pending"
  }
}
</div>

<h3>❌ Response Gagal</h3>

<div class="code">
{
  "status": false,
  "message": "Saldo tidak cukup",
  "data": null
}
</div>

</div>
</div>

<div class="footer">
Provider API © <?php echo date("Y"); ?> — Documentation System
</div>

</body>
</html>