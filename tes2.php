<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$botToken = "8300472698:AAFhoPsePTRPSOHRbGE92LZEW1qP1gtr4D0";
$chatId   = "-1003706044521";

$message = "✅ TEST TELEGRAM\nServer: " . gethostname() . "\nTime: " . date("Y-m-d H:i:s");
//WILLLLWOTK?s
$url = "https://api.telegram.org/bot$botToken/sendMessage";

$data = [
    "chat_id" => $chatId,
    "text" => $message
];

$options = [
    "http" => [
        "header"  => "Content-type: application/x-www-form-urlencoded\r\n",
        "method"  => "POST",
        "content" => http_build_query($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo $result;
