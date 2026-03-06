<?php

// ===== CONFIG =====
$botToken = "8300472698:AAFhoPsePTRPSOHRbGE92LZEW1qP1gtr4D0";
$chatId   = "-1003706044521";

// ===== RUN GIT PULL =====
$output = [];
$returnVar = 0;

exec("git pull origin main 2>&1", $output, $returnVar);

$gitResult = implode("\n", $output);

// ===== MESSAGE FORMAT =====
$message = "🚀 *DEPLOYMENT UPDATE*\n\n";
$message .= "Server: " . gethostname() . "\n";
$message .= "Time: " . date("Y-m-d H:i:s") . "\n\n";
$message .= "*Git Output:*\n";
$message .= "```\n$gitResult\n```";

// ===== SEND TO TELEGRAM =====
$url = "https://api.telegram.org/bot$botToken/sendMessage";

$data = [
    "chat_id" => $chatId,
    "text" => $message,
    "parse_mode" => "Markdown"
];

$options = [
    "http" => [
        "header"  => "Content-Type: application/x-www-form-urlencoded",
        "method"  => "POST",
        "content" => http_build_query($data),
    ]
];

$context = stream_context_create($options);
file_get_contents($url, false, $context);

// ===== OUTPUT TO BROWSER =====
echo "<pre>";
echo $gitResult;
echo "</pre>";
