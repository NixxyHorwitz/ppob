<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
if(!$isAdmin){
    die("Access denied");
}

$status  = null;
$message = null;

/* ===============================
   AMBIL DATA SETTINGS
================================= */
$stmt = $pdo->query("SELECT * FROM website_settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===============================
   FUNCTION UPLOAD
================================= */
function uploadFile($file, $old = null)
{
    if(empty($file['name'])) return $old;

    $dir = __DIR__ . '/../../uploads/settings/';
    if(!is_dir($dir)){
        mkdir($dir,0777,true);
    }

    if(!is_uploaded_file($file['tmp_name'])){
        return $old;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['png','jpg','jpeg','webp','ico'];

    if(!in_array($ext,$allowed)){
        throw new Exception("Format file tidak diizinkan.");
    }

    $name = 'setting_' . time().'_'.rand(100,999).'.'.$ext;
    $target = $dir.$name;

    if(!move_uploaded_file($file['tmp_name'], $target)){
        throw new Exception("Upload file gagal.");
    }

    return 'uploads/settings/'.$name;
}

/* ===============================
   UPDATE SETTINGS
================================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    try{

        /* WEBSITE */
        $site_name        = trim($_POST['site_name']);
        $site_tagline     = trim($_POST['site_tagline']);
        $site_description = trim($_POST['site_description']);
        $site_keywords    = trim($_POST['site_keywords']);
        $site_author      = trim($_POST['site_author']);
        $phone            = $_POST['phone'];

        /* API DIGIFLAZZ */
        $api_username = trim($_POST['api_username']);
        $api_key      = trim($_POST['api_key']);
        $api_url      = trim($_POST['api_url']);

        /* QRIS */
        $qris_code = trim($_POST['qris_code']);

        if(empty($site_name)){
            throw new Exception("Nama website wajib diisi.");
        }

        if(empty($api_username) || empty($api_key) || empty($api_url)){
            throw new Exception("Pengaturan API wajib diisi.");
        }

        /* UPLOAD FILE */
        $logo       = uploadFile($_FILES['site_logo'], $settings['site_logo']);
        $favicon    = uploadFile($_FILES['site_favicon'], $settings['site_favicon']);
        $ogimage    = uploadFile($_FILES['seo_og_image'], $settings['seo_og_image']);

        /* UPDATE DB */
        $update = $pdo->prepare("
            UPDATE website_settings SET
            site_name=?,
            site_tagline=?,
            site_description=?,
            site_keywords=?,
            site_author=?,
            phone=?,
            site_logo=?,
            site_favicon=?,
            seo_og_image=?,
            api_username=?,
            api_key=?,
            api_url=?,
            qris_code=?
            WHERE id=?
        ");

        $success = $update->execute([
            $site_name,
            $site_tagline,
            $site_description,
            $site_keywords,
            $site_author,
            $phone,
            $logo,
            $favicon,
            $ogimage,
            $api_username,
            $api_key,
            $api_url,
            $qris_code,
            $settings['id']
        ]);

        if(!$success){
            throw new Exception("Gagal menyimpan perubahan.");
        }

        $status  = "success";
        $message = "Settings berhasil diperbarui.";

        $stmt = $pdo->query("SELECT * FROM website_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    }catch(Exception $e){
        $status  = "error";
        $message = $e->getMessage();
    }
}
?>

<style>
.main-content{
    min-height:100vh;
    margin-left:260px;
}
@media(max-width:768px){
    .main-content{
        margin-left:0;
        padding:10px;
    }
}
.settings-card{
    background:#0f1115;
    border-radius:16px;
    border:1px solid #2b2f36;
}
.form-control,.form-control:focus{
    background:#151922;
    border:1px solid #2b2f36;
    color:#fff;
}
.preview-img{
    height:60px;
    object-fit:contain;
    background:#111;
    padding:5px;
    border-radius:8px;
}
</style>

<div class="main-content">
<div class="container-fluid">

<div class="text-center mb-4">
    <h4 class="fw-bold text-white">⚙️ Website Settings</h4>
    <small class="text-secondary">Pengaturan utama website & SEO</small>
</div>

<?php if($status): ?>
<div class="alert alert-<?= $status=='success'?'success':'danger' ?> alert-dismissible fade show shadow-sm">
    <?= $status=='success' ? '✅ ' : '❌ ' ?>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<div class="row g-4">

<!-- LEFT -->
<div class="col-lg-8">
<div class="card settings-card p-4 shadow-sm">

<h6 class="text-info mb-4">Informasi Website</h6>

<div class="mb-3">
<label class="text-light">Nama Website</label>
<input type="text" name="site_name" class="form-control"
value="<?= htmlspecialchars($settings['site_name']) ?>">
</div>

<div class="mb-3">
<label class="text-light">Tagline</label>
<input type="text" name="site_tagline" class="form-control"
value="<?= htmlspecialchars($settings['site_tagline']) ?>">
</div>

<div class="mb-3">
<label class="text-light">Deskripsi SEO</label>
<textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settings['site_description']) ?></textarea>
</div>

<div class="mb-3">
<label class="text-light">Keywords SEO</label>
<textarea name="site_keywords" class="form-control" rows="2"><?= htmlspecialchars($settings['site_keywords']) ?></textarea>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="text-light">Author</label>
<input type="text" name="site_author" class="form-control"
value="<?= htmlspecialchars($settings['site_author']) ?>">
</div>

<div class="col-md-6 mb-3">
<label class="text-light">Phone</label>
<input type="text" name="phone" class="form-control"
value="<?= htmlspecialchars($settings['phone']) ?>">
</div>
</div>

</div>
</div>

<!-- RIGHT -->
<div class="col-lg-4">
<div class="card settings-card p-4 shadow-sm">

<h6 class="text-info mb-3">Logo & Media</h6>

<div class="mb-3 text-center">
<img src="/<?= $settings['site_logo'] ?>" class="preview-img mb-2">
<input type="file" name="site_logo" class="form-control">
<small class="text-secondary">Logo Website</small>
</div>

<div class="mb-3 text-center">
<img src="/<?= $settings['site_favicon'] ?>" class="preview-img mb-2">
<input type="file" name="site_favicon" class="form-control">
<small class="text-secondary">Favicon</small>
</div>

<div class="mb-3 text-center">
<img src="/<?= $settings['seo_og_image'] ?>" class="preview-img mb-2">
<input type="file" name="seo_og_image" class="form-control">
<small class="text-secondary">OG Image (Share Facebook/WA)</small>
</div>

<hr class="my-4">

<h6 class="text-info mb-3">🔑 API Digiflazz</h6>

<div class="mb-3">
<label class="text-light">API Username</label>
<input type="text" name="api_username" class="form-control"
value="<?= htmlspecialchars($settings['api_username'] ?? '') ?>">
</div>

<div class="mb-3">
<label class="text-light">API Key</label>
<input type="text" name="api_key" class="form-control"
value="<?= htmlspecialchars($settings['api_key'] ?? '') ?>">
</div>

<div class="mb-3">
<label class="text-light">API URL</label>
<input type="text" name="api_url" class="form-control"
value="<?= htmlspecialchars($settings['api_url'] ?? 'https://api.digiflazz.com/v1/') ?>">
</div>

<hr class="my-4">

<h6 class="text-info mb-3">💳 Pengaturan QRIS</h6>

<div class="mb-3">
<label class="text-light">Kode QRIS</label>
<textarea name="qris_code" rows="3" class="form-control"
placeholder="Paste kode QRIS EMV"><?= htmlspecialchars($settings['qris_code'] ?? '') ?></textarea>
</div>

<button class="btn btn-info w-100 mt-3 fw-bold">
💾 Simpan Pengaturan
</button>

</div>
</div>

</div>
</form>

</div>
</div>

<script>
/* auto hide alert */
setTimeout(()=>{
    const alert=document.querySelector('.alert');
    if(alert){
        alert.style.transition="0.5s";
        alert.style.opacity="0";
        setTimeout(()=>alert.remove(),500);
    }
},4000);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>