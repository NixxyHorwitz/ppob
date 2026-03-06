<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

$userId = $_SESSION['user_id'];

/* ===============================
   AMBIL DATA USER
================================= */
$stmt = $pdo->prepare("
    SELECT
        username,
        fullname,
        email,
        phone,
        nik,
        address,
        image,
        is_active,
        created_at
    FROM users
    WHERE id=?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    die("User tidak ditemukan");
}

$success="";
$error="";

/* ===============================
   SIMPAN PROFIL
================================= */
if(isset($_POST['save_profile'])){

    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $nik     = trim($_POST['nik']);
    
    /* ===============================
   UPLOAD FOTO PROFILE
================================= */
$newImageName = $user['image']; // default lama

if(!empty($_FILES['profile_image']['name'])){

    $allowed = ['jpg','jpeg','png','webp'];
    $fileName = $_FILES['profile_image']['name'];
    $tmp      = $_FILES['profile_image']['tmp_name'];
    $size     = $_FILES['profile_image']['size'];

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    /* VALIDASI EXTENSION */
    if(!in_array($ext,$allowed)){
        $error="Format gambar harus JPG, PNG, atau WEBP";
    }

    /* MAX 2MB */
    elseif($size > 2*1024*1024){
        $error="Ukuran gambar maksimal 2MB";
    }

    else{

        /* VALIDASI BENAR BENAR GAMBAR */
        $check = getimagesize($tmp);
        if($check === false){
            $error="File bukan gambar valid";
        }else{

            $uploadDir = __DIR__."/../../uploads/profile/";

            /* NAMA FILE AMAN */
            $newImageName = "profile_".$userId."_".time().".".$ext;
            $target = $uploadDir.$newImageName;

            if(move_uploaded_file($tmp,$target)){

                /* HAPUS FOTO LAMA */
                if(!empty($user['image'])){
                    $old = $uploadDir.$user['image'];
                    if(file_exists($old)){
                        unlink($old);
                    }
                }

            }else{
                $error="Gagal upload gambar";
            }
        }
    }
}

    /* ========= VALIDASI NIK =========
       API BELUM AKTIF → hanya cek format
    ===================================*/
    if(!empty($nik)){

        if(!preg_match('/^[0-9]{16}$/',$nik)){
            $error="Format NIK harus 16 digit angka";
        } else {

            // hanya cek duplicate
            $cekNik = $pdo->prepare("
                SELECT id FROM users
                WHERE nik=? AND id!=?
                LIMIT 1
            ");
            $cekNik->execute([$nik,$userId]);

            if($cekNik->fetch()){
                $error="NIK sudah digunakan akun lain";
            }
        }
    }

    /* ========= UPDATE ========= */
    if(empty($error)){

        $update=$pdo->prepare("
            UPDATE users
            SET phone=?,address=?,nik=?,image=?
            WHERE id=?
        ");

        $update->execute([
        $phone,
        $address,
        $nik,
        $newImageName,
        $userId
    ]);

        $success="Profil berhasil diperbarui";

        // reload data
        header("Location: ".$_SERVER['REQUEST_URI']);
        exit;
    }
}

/* ===============================
   KIRIM OTP PIN
================================= */
if(isset($_POST['send_pin'])){
    $_SESSION['temp_user_id']=$userId;
    $_SESSION['temp_email']=$user['email'];
    $_SESSION['temp_username']=$user['username'];

    require __DIR__."/../../auth/send_otp_pin.php";
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

/* FOTO DEFAULT */
$profileImage = !empty($user['image'])
    ? "/uploads/profile/".$user['image']
    : "https://ui-avatars.com/api/?name=".urlencode($user['username'])."&background=0D8ABC&color=fff&size=200";
?>

<style>

.profile-wrapper{
    max-width:850px;
    margin:auto;
}

.profile-card{
    border-radius:20px;
    border:none;
    box-shadow:0 15px 40px rgba(0,0,0,.08);
}

.profile-header{
    text-align:center;
    padding:30px;
    background:linear-gradient(135deg,#108ee9,#0b6ed0);
    border-radius:20px 20px 0 0;
    color:white;
}

.profile-img{
    width:110px;
    height:110px;
    border-radius:50%;
    object-fit:cover;
    border:5px solid white;
    margin-bottom:10px;
}

.info-badge{
    font-size:12px;
    padding:5px 10px;
    border-radius:30px;
}

.input-modern{
    border-radius:12px;
    padding:12px;
}

.btn-modern{
    background:#108ee9;
    border:none;
    border-radius:12px;
    padding:12px;
    font-weight:600;
}

</style>

<div class="container mt-4 profile-wrapper">

<div class="card profile-card">

<!-- HEADER PROFILE -->
<div class="profile-header">

<img src="<?= $profileImage ?>" class="profile-img">

<h5 class="mb-1"><?= htmlspecialchars($user['fullname'] ?: $user['username']) ?></h5>
<div>@<?= htmlspecialchars($user['username']) ?></div>

<div class="mt-2">

<?php if($user['is_active']): ?>
<span class="badge bg-success info-badge">AKUN AKTIF</span>
<?php else: ?>
<span class="badge bg-danger info-badge">BELUM AKTIF</span>
<?php endif; ?>

</div>

<small>
Bergabung sejak:
<?= date('d M Y', strtotime($user['created_at'])) ?>
</small>

</div>

<div class="card-body p-4">

<?php if($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<!-- INFO ACCOUNT -->
<div class="mb-4">
<label class="small text-muted">Email</label>
<input class="form-control input-modern mb-3"
       value="<?= htmlspecialchars($user['email']) ?>"
       disabled>

<label class="small text-muted">Username</label>
<input class="form-control input-modern"
       value="<?= htmlspecialchars($user['username']) ?>"
       disabled>
</div>

<hr>

<form method="POST" enctype="multipart/form-data">

<hr>

<label class="small text-muted mb-2">Foto Profile</label>

<div class="mb-3 text-center">

    <img src="<?= $profileImage ?>"
         id="previewImage"
         style="width:120px;height:120px;
                object-fit:cover;
                border-radius:50%;
                border:3px solid #eee;
                margin-bottom:10px;">

    <input type="file"
           name="profile_image"
           accept="image/*"
           class="form-control input-modern">
</div>
<label class="small text-muted">NIK (boleh diisi sementara)</label>
<input type="text"
       name="nik"
       maxlength="16"
       class="form-control input-modern mb-3"
       placeholder="16 digit NIK"
       value="<?= htmlspecialchars($user['nik']) ?>">

<label class="small text-muted">No HP</label>
<input type="text"
       name="phone"
       class="form-control input-modern mb-3"
       value="<?= htmlspecialchars($user['phone']) ?>">

<label class="small text-muted">Alamat</label>
<textarea name="address"
          class="form-control input-modern mb-3"
          rows="3"><?= htmlspecialchars($user['address']) ?></textarea>

<button name="save_profile" class="btn btn-modern w-100 mb-3">
💾 Simpan Profil
</button>

<hr>

<button name="send_pin" class="btn btn-outline-primary w-100">
📧 Kirim OTP PIN ke Email
</button>

</form>

<script>
document.querySelector('input[name="profile_image"]').addEventListener('change',function(e){

    const file = e.target.files[0];
    if(!file) return;

    const reader = new FileReader();

    reader.onload = function(ev){
        document.getElementById('previewImage').src = ev.target.result;
    };

    reader.readAsDataURL(file);
});
</script>

</div>
</div>
</div>