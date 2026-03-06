<?php
session_start();

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| VALIDASI SESSION LOGIN SEMENTARA
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['temp_user_id']) || empty($_SESSION['temp_email'])) {
    die("Session login tidak valid.");
}

$tempUserId = $_SESSION['temp_user_id'];
$tempEmail  = $_SESSION['temp_email'];


/*
|--------------------------------------------------------------------------
| LOAD PHPMAILER
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {

    /*
    |--------------------------------------------------------------------------
    | AMBIL WEBSITE SETTINGS
    |--------------------------------------------------------------------------
    */
    $set = $pdo->query("
        SELECT site_name, phone
        FROM website_settings
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if (!$set) {
        throw new Exception("Website settings tidak ditemukan.");
    }

    $site_name = $set['site_name'];
    $phone = $set['phone'];

    /*
    |--------------------------------------------------------------------------
    | AMBIL DATA USER
    |--------------------------------------------------------------------------
    */
    $stmtUser = $pdo->prepare("
        SELECT id, username, email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmtUser->execute([$tempUserId]);

    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['email'])) {
        throw new Exception("Data user tidak valid.");
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE OTP
    |--------------------------------------------------------------------------
    */
    $pin = random_int(100000, 999999);

    /*
    | Simpan OTP ke database (expired 5 menit)
    */
    $updateOtp = $pdo->prepare("
        UPDATE users
        SET pin = ?
        WHERE id = ?
        LIMIT 1
    ");

    if (!$updateOtp->execute([$pin, $user['id']])) {
        throw new Exception("Gagal menyimpan PIN.");
    }


    /*
    |--------------------------------------------------------------------------
    | KONFIGURASI MAILER (GMAIL SMTP)
    |--------------------------------------------------------------------------
    */
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'pt.linggastorejaya@gmail.com';
    $mail->Password   = 'vjzxbtcgpinntrdn'; // Gmail App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ];


    /*
    |--------------------------------------------------------------------------
    | EMAIL HEADER
    |--------------------------------------------------------------------------
    */
    $mail->setFrom('pt.linggastorejaya@gmail.com', $site_name . ' Security');
    $mail->addAddress($user['email']);

    $mail->isHTML(true);
    $mail->Subject = "Kode OTP Login - {$site_name}";


    /*
    |--------------------------------------------------------------------------
    | EMAIL TEMPLATE
    |--------------------------------------------------------------------------
    */
    $username = htmlspecialchars($user['username']);

    $mail->Body = "
<div style='font-family:Arial,sans-serif;max-width:600px;margin:20px auto;
border:3px solid #0056b3;padding:30px;border-radius:12px;color:#333;'>

    <h1 style='color:#0056b3;margin:0;font-size:30px;'>
        <span style='color:#f37021;'>{$site_name}</span>
    </h1>

    <hr style='margin:25px 0'>

    <p>Halo <strong>{$username}</strong>,</p>

    <p>
        Kami menerima permintaan untuk Riset PIN di akun anda.
    </p>
     <p style='font-size:18px; text-align:center; margin:25px 0;'>
        <strong style='font-size:28px;color:#000;letter-spacing:3px;'>{$pin}</strong>
    </p>
    <p>
        Jika Anda tidak merasa melakukan permintaan Riset PIN ini, segera abaikan email
        ini dan disarankan untuk mengganti kata sandi akun Anda guna mencegah akses
        yang tidak sah.
    </p>

    <p style='margin-top:25px'>
        Apabila Anda membutuhkan bantuan lebih lanjut, silakan hubungi layanan
        pelanggan kami melalui WhatsApp:
        <strong>{$phone}</strong>.
    </p>

    <hr style='margin:30px 0'>

    <p style='margin:0'>
        Hormat kami,<br>
        <strong>Tim Keamanan {$site_name}</strong>
    </p>

    <br>

    <small style='color:#777'>
        Email ini dikirim secara otomatis oleh sistem dan tidak dapat menerima balasan.
        Mohon tidak membagikan informasi keamanan akun Anda kepada pihak manapun.
    </small>

</div>
";


    /*
    |--------------------------------------------------------------------------
    | KIRIM EMAIL OTP
    |--------------------------------------------------------------------------
    */
    $mail->send();

    header("Location: ../user/profil");
    exit;

} catch (Exception $e) {

    die("Gagal mengirim OTP: " . $e->getMessage());
}