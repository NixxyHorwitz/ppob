<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== CONFIG =====
$botToken = "8300472698:AAFhoPsePTRPSOHRbGE92LZEW1qP1gtr4D0";
$chatId   = "-1003706044521";

// ===== PASTIKAN JALAN DI FOLDER REPO =====
chdir(__DIR__);

// ===== RUN GIT PULL =====
$output = [];
$returnVar = 0;

$cmd = "/usr/bin/git pull origin main 2>&1";
exec($cmd, $output, $returnVar);

$gitResult = implode("\n", $output);

// ===== INFO WEBHOOK (optional) =====
$payload = file_get_contents("php://input");
$data = json_decode($payload, true);

$repo   = $data['repository']['name'] ?? "unknown";
$branch = $data['ref'] ?? "unknown";
$author = $data['pusher']['name'] ?? "unknown";
$commit = $data['head_commit']['message'] ?? "no message";

// ===== FORMAT MESSAGE =====
$message  = "🚀 DEPLOYMENT UPDATE\n\n";
$message .= "Repo   : $repo\n";
$message .= "Branch : $branch\n";
$message .= "Author : $author\n";
$message .= "Commit : $commit\n\n";
$message .= "Server : " . gethostname() . "\n";
$message .= "Time   : " . date("Y-m-d H:i:s") . "\n";
$message .= "Status : " . ($returnVar === 0 ? "SUCCESS ✅" : "FAILED ❌") . "\n\n";
$message .= "Git Output:\n" . $gitResult;


// ===== SEND TELEGRAM =====
$url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";

$dataPost = [
    "chat_id" => $chatId,
    "text" => $message
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dataPost));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
curl_close($ch);


// ===== RESPONSE KE GITHUB =====
echo "DEPLOY OK\n";
echo "<pre>" . $gitResult . "</pre>";
