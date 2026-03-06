<?php

/**
 * /modules/cms.php
 * Public file server — accessible via:
 *   https://ppob.bersamakita.my.id/cms/{id}.{ext}
 * Rewrites handled by .htaccess
 *
 * Usage: cms.php?id=12&ext=jpg  (set by RewriteRule)
 */

require_once __DIR__ . '/../config/database.php';

$id  = isset($_GET['id'])  ? (int)$_GET['id']  : 0;
$ext = isset($_GET['ext']) ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_GET['ext'])) : '';

if (!$id) {
    http_response_code(400);
    exit('Bad Request');
}

/* ── Fetch file record ──────────────────────────────────────── */
$stmt = $pdo->prepare("SELECT * FROM cms_files WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    exit('File Not Found');
}

/* ── MIME map ───────────────────────────────────────────────── */
$mime_map = [
    // Images
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    // Video
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'mov'  => 'video/quicktime',
    // Audio
    'mp3'  => 'audio/mpeg',
    'ogg'  => 'audio/ogg',
    'wav'  => 'audio/wav',
    // Documents
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    // Text / Data
    'txt'  => 'text/plain',
    'csv'  => 'text/csv',
    'json' => 'application/json',
    'xml'  => 'application/xml',
    'html' => 'text/html',
    // Archives
    'zip'  => 'application/zip',
    'rar'  => 'application/x-rar-compressed',
    'gz'   => 'application/gzip',
];

$file_ext  = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
$mime      = $mime_map[$file_ext] ?? ($mime_map[$ext] ?? 'application/octet-stream');
$disk_path = __DIR__ . '/../uploads/cms/' . $file['stored_name'];

if (!file_exists($disk_path)) {
    http_response_code(404);
    exit('File Not Found on Disk');
}

/* ── Increment view count ───────────────────────────────────── */
$pdo->prepare("UPDATE cms_files SET views = views + 1 WHERE id = ?")->execute([$id]);

/* ── Cache headers ──────────────────────────────────────────── */
$etag     = '"' . md5_file($disk_path) . '"';
$modified = filemtime($disk_path);

header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');
header('Cache-Control: public, max-age=2592000'); // 30 days

if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH'])     && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $modified)
) {
    http_response_code(304);
    exit;
}

/* ── Serve file ─────────────────────────────────────────────── */
$is_inline = str_starts_with($mime, 'image/')
    || str_starts_with($mime, 'video/')
    || str_starts_with($mime, 'audio/')
    || in_array($mime, ['application/pdf', 'text/plain', 'text/html', 'application/json']);

$disposition = $is_inline ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($file['original_name']) . '"');
header('Content-Length: ' . filesize($disk_path));
header('X-Content-Type-Options: nosniff');

readfile($disk_path);
exit;
