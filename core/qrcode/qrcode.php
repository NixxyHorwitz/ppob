<?php

function generateQRImage($text, $filepath)
{
    include_once __DIR__.'/qrcode/qrlib.php';

    QRcode::png(
        $text,
        $filepath,
        QR_ECLEVEL_H,
        6,
        2
    );

    return file_exists($filepath);
}