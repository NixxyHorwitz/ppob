<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== CONFIG =====
$botToken = "8300472698:AAFhoPsePTRPSOHRbGE92LZEW1qP1gtr4D0";
$chatId   = "-1003706044521";

// ===== RUN GIT PULL =====
$output = [];
$returnVar = 0;

exec("git pull origin main 2>&1", $output, $returnVar);

$gitResult = implode("\n", $output);

// ===== MESSAGE FORMAT =====
$message  = "🚀 DEPLOYMENT UPDATE\n\n";
$message .= "Server : " . gethostname() . "\n";
$message .= "Time   : " . date("Y-m-d H:i:s") . "\n";
$message .= "Status : " . ($returnVar === 0 ? "SUCCESS ✅" : "FAILED ❌") . "\n\n";
$message .= "Git Output:\n";
$message .= $gitResult;

// ===== SEND TELEGRAM VIA CURL =====
$url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";

$data = [
    "chat_id" => $chatId,
    "text" => $message
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $telegramError = curl_error($ch);
}

curl_close($ch);

// ===== OUTPUT TO BROWSER =====
echo "<pre>";
echo "===== GIT OUTPUT =====\n\n";
echo $gitResult;

echo "\n\n===== TELEGRAM RESPONSE =====\n";
echo $response ?? "No response";

if (isset($telegramError)) {
    echo "\n\nCURL ERROR:\n" . $telegramError;
}

echo "</pre>";
