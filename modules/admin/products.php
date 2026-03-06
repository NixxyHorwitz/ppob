<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
if(!$isAdmin){
    die("Access denied");
}

$status = null;
$message = null;

/* ===============================
   UPDATE HARGA
================================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    try{

        $id = (int)$_POST['product_id'];
        $price_sell = str_replace(['.',','],'',$_POST['price_sell']);

        if($price_sell <= 0){
            throw new Exception("Harga jual tidak valid.");
        }

        $update = $pdo->prepare("
            UPDATE products SET price_sell=? WHERE id=?
        ");
        $update->execute([$price_sell,$id]);

        $status="success";
        $message="Harga jual berhasil diperbarui.";

    }catch(Exception $e){
        $status="error";
        $message=$e->getMessage();
    }
}

/* ===============================
   SEARCH + PAGINATION
================================= */

$limit = 10;
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset = ($page-1)*$limit;

$search = $_GET['search'] ?? '';
$params = [];

$where = "";
if(!empty($search)){
    $where = "WHERE product_name LIKE ? 
              OR sku_code LIKE ?
              OR brand LIKE ?";
    $keyword = "%$search%";
    $params = [$keyword,$keyword,$keyword];
}

/* total data */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $where");
$countStmt->execute($params);
$totalData = $countStmt->fetchColumn();
$totalPage = ceil($totalData/$limit);

/* ambil data */
$sql = "
SELECT *
FROM products
$where
ORDER BY brand ASC, product_name ASC
LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$no = $offset + 1;
?>

<style>
.main-content{
    min-height:100vh;
    margin-left:260px;
}
@media(max-width:768px){
    .main-content{margin-left:0;padding:10px;}
}

.card-dark{
    background:#0f1115;
    border:1px solid #2b2f36;
    border-radius:18px;
}

.table-dark-custom{
    background:#151922;
    color:#fff;
}

.table-dark-custom th{
    background:#0e1117;
    border-color:#2b2f36;
    font-size:13px;
}

.table-dark-custom td{
    border-color:#2b2f36;
}

.price-input{
    background:#0c0f14;
    border:1px solid #2b2f36;
    color:#00d4ff;
    font-weight:bold;
    text-align:right;
}

.search-box{
    background:#0c0f14;
    border:1px solid #2b2f36;
    color:#fff;
}

.pagination .page-link{
    background:#11141a;
    border:1px solid #2b2f36;
    color:#fff;
}
.pagination .active .page-link{
    background:#0dcaf0;
    border-color:#0dcaf0;
    color:#000;
}
</style>

<div class="main-content">
<div class="container-fluid">

<div class="text-center mb-4">
    <h4 class="fw-bold text-white">Kelola Produk</h4>
    <small class="text-secondary">Manajemen harga jual produk</small>
</div>

<?php if($status): ?>
<div class="alert alert-<?= $status=='success'?'success':'danger' ?>">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="card card-dark shadow-sm p-4">

<!-- SEARCH -->
<form method="GET" class="mb-3">
<div class="input-group">
    <span class="input-group-text bg-dark text-info border-0">🔎</span>
    <input type="text" name="search"
        value="<?= htmlspecialchars($search) ?>"
        class="form-control search-box"
        placeholder="Cari product_name / sku_code / brand">
    <button class="btn btn-info fw-bold">Search</button>
</div>
</form>

<div class="table-responsive">

<table class="table table-dark-custom table-hover align-middle">
<thead>
<tr>
<th width="50">No</th>
<th>SKU</th>
<th>Produk</th>
<th>Brand</th>
<th>Vendor</th>
<th>Harga Jual</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php foreach($products as $p): ?>
<tr>

<td><?= $no++ ?></td>

<td><?= htmlspecialchars($p['sku_code']) ?></td>

<td>
<div class="fw-bold"><?= htmlspecialchars($p['product_name']) ?></div>
<small class="text-secondary"><?= $p['category'] ?></small>
</td>

<td><?= htmlspecialchars($p['brand']) ?></td>

<td class="text-warning fw-bold">
Rp <?= number_format($p['price_vendor'],0,',','.') ?>
</td>

<td style="width:180px">
<form method="POST" class="d-flex gap-2">
<input type="hidden" name="product_id" value="<?= $p['id'] ?>">
<input type="text"
name="price_sell"
class="form-control form-control-sm price-input"
value="<?= number_format($p['price_sell'],0,',','.') ?>"
required>
</td>

<td>
<span class="badge bg-<?= $p['status']=='active'?'success':'secondary' ?>">
<?= strtoupper($p['status']) ?>
</span>
</td>

<td>
<button class="btn btn-sm btn-info fw-bold">💾</button>
</form>
</td>

</tr>
<?php endforeach; ?>

<?php if(empty($products)): ?>
<tr>
<td colspan="8" class="text-center text-secondary py-4">
Tidak ada produk ditemukan
</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>

<!-- PAGINATION -->
<nav class="mt-3">
<ul class="pagination justify-content-center">

<?php for($i=1;$i<=$totalPage;$i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link"
href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
<?= $i ?>
</a>
</li>
<?php endfor; ?>

</ul>
</nav>

</div>
</div>
</div>

<script>
setTimeout(()=>{
    const alert=document.querySelector('.alert');
    if(alert){
        alert.style.opacity="0";
        setTimeout(()=>alert.remove(),500);
    }
},4000);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>