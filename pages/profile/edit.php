<?php

/**
 * pages/profile/edit.php
 * Halaman edit profil user
 * Path: /htdocs/pages/profile/edit.php
 */

$pageTitle = 'Edit Profil';
require_once __DIR__ . '/../../includes/header.php';

// ── Fetch user ─────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die('User tidak ditemukan');

$success = '';
$error   = '';

// ── Handle: Simpan Profil ──────────────────────────────────
if (isset($_POST['save_profile'])) {
    $phone   = trim($_POST['phone']   ?? '');
    $address = trim($_POST['address'] ?? '');
    $nik     = trim($_POST['nik']     ?? '');

    $newImageName = $user['image'];

    // Upload foto
    if (!empty($_FILES['profile_image']['name'])) {
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
        $fileName = $_FILES['profile_image']['name'];
        $tmp      = $_FILES['profile_image']['tmp_name'];
        $size     = $_FILES['profile_image']['size'];
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = 'Format gambar harus JPG, PNG, atau WEBP.';
        } elseif ($size > 2 * 1024 * 1024) {
            $error = 'Ukuran gambar maksimal 2MB.';
        } elseif (getimagesize($tmp) === false) {
            $error = 'File bukan gambar yang valid.';
        } else {
            $uploadDir    = __DIR__ . '/../../uploads/profile/';
            $newImageName = 'profile_' . $userId . '_' . time() . '.' . $ext;
            $target       = $uploadDir . $newImageName;

            if (move_uploaded_file($tmp, $target)) {
                // Hapus foto lama
                if (!empty($user['image']) && $user['image'] !== 'default.png') {
                    $old = $uploadDir . $user['image'];
                    if (file_exists($old)) unlink($old);
                }
            } else {
                $error = 'Gagal mengupload gambar.';
            }
        }
    }

    // Validasi NIK
    if (empty($error) && !empty($nik)) {
        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            $error = 'Format NIK harus 16 digit angka.';
        } else {
            $cekNik = $pdo->prepare("SELECT id FROM users WHERE nik = ? AND id != ? LIMIT 1");
            $cekNik->execute([$nik, $userId]);
            if ($cekNik->fetch()) $error = 'NIK sudah digunakan akun lain.';
        }
    }

    // Update DB
    if (empty($error)) {
        $pdo->prepare("UPDATE users SET phone=?, address=?, nik=?, image=? WHERE id=?")
            ->execute([$phone, $address, $nik, $newImageName, $userId]);

        header('Location: /pages/profile');
        exit;
    }
}

// ── Handle: Kirim OTP PIN ──────────────────────────────────
if (isset($_POST['send_pin'])) {
    $_SESSION['temp_user_id']  = $userId;
    $_SESSION['temp_email']    = $user['email'];
    $_SESSION['temp_username'] = $user['username'];
    require __DIR__ . '/../../auth/send_otp_pin.php';
    exit;
}

// ── Avatar ─────────────────────────────────────────────────
$avatar = !empty($user['image']) && $user['image'] !== 'default.png'
    ? '/uploads/profile/' . $user['image']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['fullname'] ?: $user['username']) . '&background=01d298&color=fff&size=200&bold=true';

$displayName = $user['fullname'] ?: $user['username'];
?>

<style>
    /* ── EDIT HERO ──────────────────────────────── */
    .eh {
        background: linear-gradient(145deg, var(--cpdd) 0%, var(--cpd) 45%, var(--cp) 100%);
        padding: 36px 18px 65px;
        position: relative;
        overflow: hidden;
        text-align: center;
    }

    .eh::before {
        content: '';
        position: absolute;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, .07);
        border-radius: 50%;
        top: -70px;
        right: -50px;
    }

    .eh::after {
        content: '';
        position: absolute;
        width: 110px;
        height: 110px;
        background: rgba(255, 255, 255, .05);
        border-radius: 50%;
        bottom: 10px;
        left: -30px;
    }

    .eh-in {
        position: relative;
        z-index: 2;
    }

    .eh-back {
        position: absolute;
        top: 16px;
        left: 16px;
        z-index: 10;
        width: 34px;
        height: 34px;
        background: rgba(255, 255, 255, .20);
        border: 1px solid rgba(255, 255, 255, .28);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        text-decoration: none;
        transition: background .2s;
    }

    .eh-back:active {
        background: rgba(255, 255, 255, .35);
        color: #fff;
    }

    .eh-avatar-wrap {
        position: relative;
        display: inline-block;
        margin-bottom: 10px;
        cursor: pointer;
    }

    .eh-avatar {
        width: 82px;
        height: 82px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 255, 255, .40);
        box-shadow: 0 6px 24px rgba(0, 0, 0, .20);
        transition: opacity .2s;
    }

    .eh-avatar-wrap:hover .eh-avatar {
        opacity: .85;
    }

    .eh-cam {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 26px;
        height: 26px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        color: var(--cpd);
        box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
    }

    .eh-name {
        color: #fff;
        font-size: 16px;
        font-weight: 900;
        margin-bottom: 2px;
    }

    .eh-username {
        color: rgba(255, 255, 255, .72);
        font-size: 11.5px;
        font-weight: 600;
    }

    /* ── FORM CARD ──────────────────────────────── */
    .form-wrap {
        padding: 0 14px;
        margin-top: -42px;
        position: relative;
        z-index: 10;
    }

    .form-card {
        background: var(--cc);
        border-radius: 20px;
        padding: 20px 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, .11);
        border: 1px solid rgba(0, 0, 0, .05);
        margin-bottom: 12px;
    }

    .form-sec-title {
        font-size: 10.5px;
        font-weight: 800;
        color: var(--cm);
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .form-sec-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(0, 0, 0, .07);
    }

    /* Input */
    .fi-wrap {
        margin-bottom: 13px;
    }

    .fi-lbl {
        font-size: 10.5px;
        font-weight: 800;
        color: var(--cm);
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .fi-lbl i {
        font-size: 10px;
    }

    .fi {
        width: 100%;
        background: var(--cbg);
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 11px 13px;
        font-family: var(--f);
        font-size: 13px;
        font-weight: 600;
        color: var(--ct);
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .fi:focus {
        border-color: var(--cp);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--cp) 15%, transparent);
    }

    .fi:disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    .fi::placeholder {
        color: #94a3b8;
        font-weight: 500;
    }

    textarea.fi {
        resize: vertical;
        min-height: 80px;
    }

    /* Alert */
    .alert-box {
        border-radius: 12px;
        padding: 11px 13px;
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 14px;
    }

    .alert-box.error {
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #be123c;
    }

    .alert-box.success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #15803d;
    }

    .alert-box i {
        font-size: 15px;
        flex-shrink: 0;
    }

    /* Submit button */
    .btn-save {
        width: 100%;
        background: linear-gradient(135deg, var(--cpd), var(--cp));
        color: #fff;
        border: none;
        border-radius: 14px;
        padding: 14px;
        font-family: var(--f);
        font-size: 13.5px;
        font-weight: 900;
        letter-spacing: -.2px;
        cursor: pointer;
        box-shadow: 0 6px 20px color-mix(in srgb, var(--cp) 35%, transparent);
        transition: transform .15s, box-shadow .15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-save:active {
        transform: scale(.97);
    }

    .btn-otp {
        width: 100%;
        background: var(--cbg);
        color: var(--cpd);
        border: 1.5px solid var(--cp);
        border-radius: 14px;
        padding: 13px;
        font-family: var(--f);
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        transition: background .15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-otp:active {
        background: var(--cpl);
    }

    /* Photo preview */
    .photo-preview {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--cbg);
        border-radius: 13px;
        padding: 12px 13px;
        margin-bottom: 10px;
        border: 1.5px dashed #e2e8f0;
        cursor: pointer;
        transition: border-color .2s;
    }

    .photo-preview:hover {
        border-color: var(--cp);
    }

    .photo-preview img {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--cpl);
        flex-shrink: 0;
    }

    .photo-preview-txt {
        flex: 1;
    }

    .photo-preview-txt strong {
        font-size: 12px;
        font-weight: 800;
        color: var(--ct);
        display: block;
        margin-bottom: 2px;
    }

    .photo-preview-txt span {
        font-size: 10.5px;
        color: var(--cm);
        font-weight: 500;
    }

    .photo-preview-ico {
        color: var(--cp);
        font-size: 18px;
    }
</style>

<!-- ════════════ HERO ════════════ -->
<div class="eh">
    <a href="/pages/profile" class="eh-back"><i class="fas fa-chevron-left"></i></a>
    <div class="eh-in">
        <label for="fileInput" class="eh-avatar-wrap" title="Klik untuk ganti foto">
            <img src="<?= $avatar ?>" id="previewImg" class="eh-avatar" alt="Avatar">
            <div class="eh-cam"><i class="fas fa-camera"></i></div>
        </label>
        <div class="eh-name"><?= htmlspecialchars($displayName) ?></div>
        <div class="eh-username">@<?= htmlspecialchars($user['username']) ?></div>
    </div>
</div>

<!-- ════════════ FORM ════════════ -->
<div class="form-wrap">

    <?php if ($error): ?>
        <div class="alert-box error"><i class="fas fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <!-- FOTO PROFIL -->
        <div class="form-card">
            <div class="form-sec-title"><i class="fas fa-image"></i> Foto Profil</div>

            <label for="fileInput" class="photo-preview">
                <img src="<?= $avatar ?>" id="previewImgCard" alt="">
                <div class="photo-preview-txt">
                    <strong>Ganti Foto Profil</strong>
                    <span>JPG, PNG, WEBP — maks. 2MB</span>
                </div>
                <i class="fas fa-upload photo-preview-ico"></i>
            </label>

            <input type="file" name="profile_image" id="fileInput"
                accept="image/*" style="display:none">
        </div>

        <!-- DATA AKUN (read only) -->
        <div class="form-card">
            <div class="form-sec-title"><i class="fas fa-lock"></i> Data Akun</div>

            <div class="fi-wrap">
                <div class="fi-lbl"><i class="fas fa-envelope"></i> Email</div>
                <input class="fi" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <div class="fi-wrap" style="margin-bottom:0">
                <div class="fi-lbl"><i class="fas fa-at"></i> Username</div>
                <input class="fi" value="<?= htmlspecialchars($user['username']) ?>" disabled>
            </div>
        </div>

        <!-- DATA PROFIL -->
        <div class="form-card">
            <div class="form-sec-title"><i class="fas fa-user"></i> Data Profil</div>

            <div class="fi-wrap">
                <div class="fi-lbl"><i class="fas fa-id-card"></i> NIK <span style="font-weight:500;color:#94a3b8">(16 digit)</span></div>
                <input type="text" name="nik" class="fi"
                    maxlength="16"
                    placeholder="Masukkan NIK KTP"
                    value="<?= htmlspecialchars($user['nik'] ?? '') ?>"
                    inputmode="numeric" pattern="[0-9]{16}">
            </div>

            <div class="fi-wrap">
                <div class="fi-lbl"><i class="fas fa-phone"></i> No. HP</div>
                <input type="tel" name="phone" class="fi"
                    placeholder="Contoh: 0812xxxxxxxx"
                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>

            <div class="fi-wrap" style="margin-bottom:0">
                <div class="fi-lbl"><i class="fas fa-location-dot"></i> Alamat</div>
                <textarea name="address" class="fi" placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- SAVE -->
        <button type="submit" name="save_profile" class="btn-save">
            <i class="fas fa-floppy-disk"></i> Simpan Perubahan
        </button>

    </form>

    <!-- OTP PIN -->
    <div class="g12"></div>
    <form method="POST">
        <button type="submit" name="send_pin" class="btn-otp">
            <i class="fas fa-envelope-open-text"></i> Kirim OTP PIN ke Email
        </button>
    </form>

</div>

<div class="g20"></div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    const fileInput = document.getElementById('fileInput');
    const previewHero = document.getElementById('previewImg');
    const previewCard = document.getElementById('previewImgCard');

    fileInput && fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            previewHero.src = e.target.result;
            previewCard.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
</script>
</body>

</html>