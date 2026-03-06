<?php
/* ===============================
   HITUNG CRC16 QRIS
================================= */
function crc16($str) {
    $crc = 0xFFFF;

    for ($c = 0; $c < strlen($str); $c++) {
        $crc ^= ord($str[$c]) << 8;
        for ($i = 0; $i < 8; $i++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc = $crc << 1;
            }
        }
    }

    $crc &= 0xFFFF;
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

/* ===============================
   INSERT NOMINAL KE QRIS (FIX BI STANDARD)
================================= */
function generateDynamicQRIS($qrisString, $amount)
{
    // pastikan string bersih
    $qris = trim($qrisString);

    // ===============================
    // HAPUS CRC LAMA
    // ===============================
    $posCRC = strpos($qris, "6304");
    if ($posCRC === false) {
        return false;
    }

    $qris = substr($qris, 0, $posCRC);

    // ===============================
    // HAPUS TAG 54 JIKA SUDAH ADA
    // ===============================
    $result = '';
    $i = 0;

    while ($i < strlen($qris)) {
        $tag = substr($qris, $i, 2);
        $len = intval(substr($qris, $i + 2, 2));
        $value = substr($qris, $i + 4, $len);

        // skip tag 54 (amount lama)
        if ($tag !== "54") {
            $result .= $tag . str_pad($len, 2, '0', STR_PAD_LEFT) . $value;
        }

        $i += 4 + $len;
    }

    $qris = $result;

    // ===============================
    // FORMAT NOMINAL
    // ===============================
    $amount = preg_replace('/[^0-9]/', '', $amount);

    $tag54 =
        "54" .
        str_pad(strlen($amount), 2, "0", STR_PAD_LEFT) .
        $amount;

    // ===============================
    // TAMBAHKAN TAG 54 + CRC HEADER
    // ===============================
    $qris .= $tag54 . "6304";

    // ===============================
    // HITUNG CRC BARU
    // ===============================
    $crc = crc16($qris);

    return $qris . $crc;
}

function notifyAdmins($pdo, $title, $message) {
    $stmtAdmins = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
    $admins = $stmtAdmins->fetchAll();
    
    $stmtNotify = $pdo->prepare("INSERT INTO notifications (user_id, title, message, is_read) VALUES (?, ?, ?, 0)");
    
    foreach ($admins as $adm) {
        $stmtNotify->execute([$adm['id'], "[SISTEM] " . $title, $message]);
    }
}

function notifyUser($pdo, $user_id, $title, $message) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message]);
}