<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== CONFIG =====
$botToken = "8300472698:AAFhoPsePTRPSOHRbGE92LZEW1qP1gtr4D0";
$chatId   = "-1003706044521";

// ===== READ GITHUB WEBHOOK =====
$payload = file_get_contents("php://input");
$data = json_decode($payload, true);

// kalau webhook kosong (manual access)
if (!$data) {
    echo "Webhook payload empty";
}

// ===== RUN GIT PULL =====
$output = [];
$returnVar = 0;

exec("git pull origin main 2>&1", $output, $returnVar);

$gitResult = implode("\n", $output);

// ===== INFO DARI GITHUB =====
$repo   = $data['repository']['name'] ?? "unknown";
$branch = $data['ref'] ?? "unknown";
$author = $data['pusher']['name'] ?? "unknown";
$commit = $data['head_commit']['message'] ?? "no message";

// ===== FORMAT MESSAGE =====
$message  = "🚀 DEPLOY SUCCESS\n\n";
$message .= "Repo   : $repo\n";
$message .= "Branch : $branch\n";
$message .= "Author : $author\n";
$message .= "Commit : $commit\n\n";
$message .= "Server : " . gethostname() . "\n";
$message .= "Time   : " . date("Y-m-d H:i:s") . "\n\n";
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

$response = curl_exec($ch);

curl_close($ch);

// ===== RESPONSE KE GITHUB =====
echo "DEPLOY OK";
