<?php

/**
 * /backoffice/media.php
 * CMS File Manager — Upload, browse & manage media files
 * Files are served publicly via /cms/{id}.{ext}  (see .htaccess)
 *
 * AJAX endpoint: POST ?ajax=upload  → JSON response (one file at a time)
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'CMS Media';
$active_menu = 'media';
/* ── Ensure table exists ────────────────────────────────────── */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS cms_files (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        original_name VARCHAR(255)  NOT NULL,
        stored_name   VARCHAR(255)  NOT NULL UNIQUE,
        mime_type     VARCHAR(120)  NOT NULL,
        file_size     BIGINT        NOT NULL DEFAULT 0,
        file_type     ENUM('image','video','audio','document','other') NOT NULL DEFAULT 'other',
        title         VARCHAR(255)  DEFAULT NULL,
        description   TEXT          DEFAULT NULL,
        folder        VARCHAR(100)  DEFAULT 'uncategorized',
        views         INT UNSIGNED  NOT NULL DEFAULT 0,
        is_active     TINYINT(1)    NOT NULL DEFAULT 1,
        uploaded_by   VARCHAR(100)  DEFAULT NULL,
        created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_folder  (folder),
        INDEX idx_type    (file_type),
        INDEX idx_active  (is_active),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

/* ── Upload directory ───────────────────────────────────────── */
$upload_dir = __DIR__ . '/../uploads/cms/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

/* ── Helpers ────────────────────────────────────────────────── */
$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$cms_base = $base_url . '/cms/';

/* Mime detection — no mime_content_type() dependency */
function detect_mime_by_ext(string $filename): string
{
    $map = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'mov'  => 'video/quicktime',
        'mp3'  => 'audio/mpeg',
        'ogg'  => 'audio/ogg',
        'wav'  => 'audio/wav',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt'  => 'text/plain',
        'csv'  => 'text/csv',
        'json' => 'application/json',
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
    ];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $map[$ext] ?? 'application/octet-stream';
}

/* Try finfo first, fall back to extension map */
function get_mime(string $tmp_path, string $orig_name): string
{
    if (function_exists('finfo_open')) {
        $fi   = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fi, $tmp_path);
        finfo_close($fi);
        if ($mime && $mime !== 'application/octet-stream') return $mime;
    }
    return detect_mime_by_ext($orig_name);
}

function detect_type(string $mime): string
{
    if (str_starts_with($mime, 'image/')) return 'image';
    if (str_starts_with($mime, 'video/')) return 'video';
    if (str_starts_with($mime, 'audio/')) return 'audio';
    foreach (['application/pdf', 'application/msword', 'application/vnd.', 'text/'] as $d)
        if (str_starts_with($mime, $d)) return 'document';
    return 'other';
}

function fmt_size(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return number_format($bytes / 1024,    1) . ' KB';
    return $bytes . ' B';
}

/* ════════════════════════════════════════════════════════════
   AJAX UPLOAD ENDPOINT  (?ajax=upload)
   Accepts ONE file per request. JS sends them one-by-one.
   Returns JSON: { ok, name, id, url, size, type, error? }
════════════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'upload') {
    header('Content-Type: application/json');

    $allowed_ext = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
        'ico',
        'mp4',
        'webm',
        'mov',
        'mp3',
        'ogg',
        'wav',
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'txt',
        'csv',
        'json',
        'zip',
        'rar',
    ];
    $max_size = 20 * 1024 * 1024; // 20 MB

    $folder = trim($_POST['folder'] ?? 'uncategorized') ?: 'uncategorized';
    $folder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $folder) ?: 'uncategorized';

    if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['ok' => false, 'error' => 'Tidak ada file diterima.']);
        exit;
    }

    $f         = $_FILES['file'];
    $err_code  = $f['error'];
    $orig_name = basename($f['name']);

    // Apply custom name if provided (keep original extension)
    $custom_raw = trim($_POST['custom_name'] ?? '');
    if ($custom_raw !== '') {
        $orig_ext  = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        $clean = preg_replace('/[\/\\\\:*?"<>|]/', '', $custom_raw);
        $clean = trim(pathinfo($clean, PATHINFO_FILENAME)) ?: 'file';
        $orig_name = $clean . '.' . $orig_ext;
    }
    $tmp_path  = $f['tmp_name'];
    $size      = (int)$f['size'];

    // PHP upload error
    if ($err_code !== UPLOAD_ERR_OK) {
        $err_msg = match ($err_code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (maks 20 MB).',
            UPLOAD_ERR_PARTIAL  => 'Upload tidak lengkap.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder tmp tidak ditemukan.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis ke disk.',
            default => "Upload error (kode {$err_code}).",
        };
        echo json_encode(['ok' => false, 'name' => $orig_name, 'error' => $err_msg]);
        exit;
    }

    // Size check
    if ($size > $max_size) {
        echo json_encode(['ok' => false, 'name' => $orig_name, 'error' => 'File terlalu besar (maks 20 MB).']);
        exit;
    }

    // Extension check
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        echo json_encode(['ok' => false, 'name' => $orig_name, 'error' => "Ekstensi .{$ext} tidak diizinkan."]);
        exit;
    }

    // Mime detection
    $mime = get_mime($tmp_path, $orig_name);

    // Store file
    $stored = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $dest   = $upload_dir . $stored;

    if (!move_uploaded_file($tmp_path, $dest)) {
        echo json_encode(['ok' => false, 'name' => $orig_name, 'error' => 'Gagal memindahkan file ke server.']);
        exit;
    }

    $type = detect_type($mime);

    try {
        $pdo->prepare("
            INSERT INTO cms_files (original_name, stored_name, mime_type, file_size, file_type, folder, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$orig_name, $stored, $mime, $size, $type, $folder, $_SESSION['username'] ?? 'admin']);

        $new_id  = (int)$pdo->lastInsertId();
        $pub_url = $cms_base . $new_id . '.' . $ext;

        echo json_encode([
            'ok'    => true,
            'id'    => $new_id,
            'name'  => $orig_name,
            'url'   => $pub_url,
            'size'  => fmt_size($size),
            'type'  => $type,
            'mime'  => $mime,
        ]);
    } catch (Exception $e) {
        @unlink($dest);
        echo json_encode(['ok' => false, 'name' => $orig_name, 'error' => 'Gagal menyimpan ke database.']);
    }
    exit;
}

/* ════════════════════════════════════════════════════════════
   REGULAR POST ACTIONS (edit / toggle / delete)
════════════════════════════════════════════════════════════ */
$page_title  = 'CMS File Manager';
$active_menu = 'media';

$toast = '';
$toast_e = '';
$action = $_POST['action'] ?? '';

/* ── Edit metadata ──────────────────────────────────────────── */
if ($action === 'edit' && !empty($_POST['id'])) {
    $pdo->prepare("UPDATE cms_files SET title=?, description=?, folder=? WHERE id=?")
        ->execute([
            trim($_POST['title']       ?? ''),
            trim($_POST['description'] ?? ''),
            trim($_POST['folder']      ?? 'uncategorized'),
            (int)$_POST['id']
        ]);
    $toast = 'Metadata file diperbarui.';
}

/* ── Toggle active ──────────────────────────────────────────── */
if ($action === 'toggle' && !empty($_POST['id'])) {
    $pdo->prepare("UPDATE cms_files SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'Status file diubah.';
}

/* ── Delete ─────────────────────────────────────────────────── */
if ($action === 'delete' && !empty($_POST['id'])) {
    $frow = $pdo->prepare("SELECT stored_name FROM cms_files WHERE id=?");
    $frow->execute([(int)$_POST['id']]);
    $fr = $frow->fetch();
    if ($fr) {
        $path = $upload_dir . $fr['stored_name'];
        if (file_exists($path)) unlink($path);
    }
    $pdo->prepare("DELETE FROM cms_files WHERE id=?")->execute([(int)$_POST['id']]);
    $toast = 'File dihapus.';
}

/* ════════════════════════════════════════════════════════════
   FETCH / FILTER
════════════════════════════════════════════════════════════ */
$filter_type   = $_GET['type']   ?? '';
$filter_folder = $_GET['folder'] ?? '';
$search        = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 24;
$offset        = ($page - 1) * $per_page;

$where  = ['1=1'];
$params = [];

if ($filter_type) {
    $where[]  = 'file_type = ?';
    $params[] = $filter_type;
}
if ($filter_folder) {
    $where[]  = 'folder = ?';
    $params[] = $filter_folder;
}
if ($search) {
    $where[]  = '(original_name LIKE ? OR title LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$where_sql = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM cms_files WHERE {$where_sql}");
$total->execute($params);
$total_count = (int)$total->fetchColumn();
$total_pages = max(1, ceil($total_count / $per_page));

$rows_stmt = $pdo->prepare("SELECT * FROM cms_files WHERE {$where_sql} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}");
$rows_stmt->execute($params);
$files_rows = $rows_stmt->fetchAll(PDO::FETCH_ASSOC);

/* Stats */
$stats = $pdo->query("SELECT file_type, COUNT(*) cnt, SUM(file_size) total_sz FROM cms_files GROUP BY file_type")->fetchAll(PDO::FETCH_ASSOC);
$stat_map = [];
foreach ($stats as $s) $stat_map[$s['file_type']] = $s;

$all_count   = array_sum(array_column($stats, 'cnt'));
$all_size    = array_sum(array_column($stats, 'total_sz'));
$folders_all = $pdo->query("SELECT DISTINCT folder FROM cms_files ORDER BY folder")->fetchAll(PDO::FETCH_COLUMN);

/* Edit modal data */
$edit_file = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare("SELECT * FROM cms_files WHERE id=?");
    $es->execute([(int)$_GET['edit']]);
    $edit_file = $es->fetch(PDO::FETCH_ASSOC);
}

/* ── Thumb helper: returns URL for preview ──────────────────── */
function file_thumb_url(array $f, string $cms_base): string
{
    if ($f['file_type'] === 'image') {
        return $cms_base . $f['id'] . '.' . pathinfo($f['original_name'], PATHINFO_EXTENSION);
    }
    return '';
}

function type_icon(string $type): string
{
    return match ($type) {
        'image'    => 'ph-image',
        'video'    => 'ph-video',
        'audio'    => 'ph-music-note',
        'document' => 'ph-file-text',
        default    => 'ph-file',
    };
}
function type_color(string $type): string
{
    return match ($type) {
        'image'    => '#3b82f6',
        'video'    => '#8b5cf6',
        'audio'    => '#ec4899',
        'document' => '#f97316',
        default    => '#64748b',
    };
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Toast -->
<div class="toast-wrap">
    <?php if ($toast):  ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast)  ?></div><?php endif; ?>
    <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<style>
    /* ═══════════════════════════════════════════
   CMS FILE MANAGER STYLES
═══════════════════════════════════════════ */

    /* ── Drop zone ──────────────────────────────────────────────── */
    .drop-zone {
        border: 2px dashed var(--border);
        border-radius: 14px;
        padding: 36px 20px;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        background: var(--hover);
        position: relative;
        user-select: none;
    }

    .drop-zone:hover,
    .drop-zone.drag-over {
        border-color: var(--accent);
        background: rgba(1, 210, 152, .06);
    }

    .drop-zone input[type=file] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    .drop-icon {
        font-size: 42px;
        margin-bottom: 10px;
        opacity: .45;
        pointer-events: none;
    }

    .drop-txt {
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 4px;
        pointer-events: none;
    }

    .drop-sub {
        font-size: 11px;
        color: var(--mut);
        pointer-events: none;
    }

    /* ── Upload progress ────────────────────────────────────────── */
    .upload-progress-wrap {
        display: none;
        margin-top: 14px;
    }

    .upload-progress-wrap.active {
        display: block;
    }

    .up-bar-bg {
        height: 6px;
        border-radius: 99px;
        background: var(--hover);
        border: 1px solid var(--border);
        overflow: hidden;
    }

    .up-bar-fill {
        height: 100%;
        border-radius: 99px;
        background: var(--accent);
        width: 0%;
        transition: width .3s;
    }

    .up-file-list {
        margin-top: 10px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        max-height: 160px;
        overflow-y: auto;
    }

    .up-file-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        padding: 6px 10px;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 7px;
    }

    .up-file-item .ufi-name {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .up-file-item .ufi-sz {
        color: var(--mut);
        flex-shrink: 0;
    }

    .up-file-item .ufi-st {
        flex-shrink: 0;
        font-size: 11px;
        font-weight: 700;
    }

    .ufi-ok {
        color: #10b981;
    }

    .ufi-fail {
        color: #ef4444;
    }

    .ufi-wait {
        color: var(--mut);
    }

    /* ── Gallery grid ───────────────────────────────────────────── */
    .file-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
    }

    @media(max-width:576px) {
        .file-grid {
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        }
    }

    /* ── File card ──────────────────────────────────────────────── */
    .fc {
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        background: var(--card);
        transition: box-shadow .18s, transform .18s;
        position: relative;
    }

    .fc:hover {
        box-shadow: 0 8px 28px rgba(0, 0, 0, .22);
        transform: translateY(-3px);
    }

    .fc.inactive {
        opacity: .45;
    }

    .fc-thumb {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        display: block;
        background: var(--hover);
        cursor: zoom-in;
    }

    .fc-thumb-icon {
        width: 100%;
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 42px;
    }

    .fc-body {
        padding: 8px 10px 10px;
        border-top: 1px solid var(--border);
    }

    .fc-name {
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 4px;
    }

    .fc-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 10px;
        color: var(--mut);
    }

    .fc-type-badge {
        font-size: 9px;
        font-weight: 700;
        padding: 1px 7px;
        border-radius: 99px;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    /* ── Overlay: delete only ───────────────────────────────────── */
    .fc-thumb-wrap {
        position: relative;
        overflow: hidden;
    }

    .fc-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .55);
        backdrop-filter: blur(3px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        opacity: 0;
        transition: opacity .18s;
        z-index: 1;
    }

    .fc-thumb-wrap:hover .fc-overlay {
        opacity: 1;
    }

    .fc-ov-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 7px;
        border: 1px solid rgba(255, 255, 255, .2);
        background: rgba(255, 255, 255, .12);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .12s;
        width: 120px;
        justify-content: center;
    }

    .fc-ov-btn:hover {
        background: rgba(255, 255, 255, .25);
        color: #fff;
    }

    .fc-ov-btn.red {
        background: rgba(239, 68, 68, .4);
    }

    .fc-ov-btn.red:hover {
        background: rgba(239, 68, 68, .7);
    }

    /* ── Status dot ─────────────────────────────────────────────── */
    .status-dot {
        position: absolute;
        top: 8px;
        left: 8px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        border: 1.5px solid rgba(0, 0, 0, .2);
        z-index: 2;
    }

    .dot-on {
        background: #10b981;
    }

    .dot-off {
        background: #ef4444;
    }

    /* ── Type tab filters ───────────────────────────────────────── */
    .type-tabs {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .ttab {
        padding: 5px 13px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        border: 1px solid var(--border);
        color: var(--sub);
        background: var(--hover);
        transition: all .15s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .ttab:hover,
    .ttab.active {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    /* ── Copy URL button ────────────────────────────────────────── */
    .copy-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-family: 'JetBrains Mono', monospace;
        background: rgba(1, 210, 152, .1);
        border: 1px solid rgba(1, 210, 152, .25);
        border-radius: 5px;
        padding: 2px 7px;
        cursor: pointer;
        color: var(--accent);
        transition: all .12s;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .copy-chip:hover {
        background: rgba(1, 210, 152, .2);
    }

    /* ── View toggle ────────────────────────────────────────────── */
    .view-toggle-btn {
        width: 32px;
        height: 32px;
        border-radius: 7px;
        border: 1px solid var(--border);
        background: var(--hover);
        color: var(--mut);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .15s;
    }

    .view-toggle-btn.active,
    .view-toggle-btn:hover {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    /* ── List view ──────────────────────────────────────────────── */
    .file-list-view {
        display: none;
        flex-direction: column;
        gap: 6px;
    }

    .file-list-view.show {
        display: flex;
    }

    .file-grid.hide {
        display: none;
    }

    .fl-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--card);
        transition: all .15s;
    }

    .fl-row:hover {
        background: var(--hover);
        box-shadow: 0 4px 14px rgba(0, 0, 0, .1);
    }

    .fl-row.inactive {
        opacity: .45;
    }

    .fl-thumb {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        object-fit: cover;
        flex-shrink: 0;
        background: var(--hover);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .fl-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .fl-info {
        flex: 1;
        min-width: 0;
    }

    .fl-name {
        font-size: 13px;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .fl-sub {
        font-size: 11px;
        color: var(--mut);
        margin-top: 2px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .fl-actions {
        display: flex;
        gap: 5px;
        flex-shrink: 0;
    }

    .fl-btn {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: var(--hover);
        color: var(--sub);
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all .12s;
    }

    .fl-btn:hover {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    .fl-btn.red:hover {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
    }

    /* ── Modal ──────────────────────────────────────────────────── */
    .modal-back {
        position: fixed;
        inset: 0;
        z-index: 9990;
        background: rgba(0, 0, 0, .65);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-box {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        width: 100%;
        max-width: 480px;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-head {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 700;
        position: sticky;
        top: 0;
        background: var(--card);
        z-index: 1;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-close {
        margin-left: auto;
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: var(--hover);
        border: 1px solid var(--border);
        color: var(--mut);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        text-decoration: none;
    }

    .modal-close:hover {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    /* ── Lightbox ───────────────────────────────────────────────── */
    .lightbox {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0, 0, 0, .92);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        cursor: zoom-out;
    }

    .lightbox img {
        max-width: 100%;
        max-height: 90vh;
        border-radius: 10px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
    }

    .lb-close {
        position: absolute;
        top: 18px;
        right: 22px;
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: rgba(255, 255, 255, .12);
        backdrop-filter: blur(4px);
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ── Pagination ─────────────────────────────────────────────── */
    .pgn {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .pgn-item {
        width: 32px;
        height: 32px;
        border-radius: 7px;
        border: 1px solid var(--border);
        background: var(--hover);
        color: var(--sub);
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all .12s;
    }

    .pgn-item:hover,
    .pgn-item.active {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    .pgn-item.disabled {
        opacity: .35;
        pointer-events: none;
    }

    /* ── Stat chip ──────────────────────────────────────────────── */
    .stat-chip {
        flex: 1;
        min-width: 110px;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 11px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>

<!-- Page header -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 style="display:flex;align-items:center;gap:10px">
            <span style="width:36px;height:36px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:18px">📁</span>
            CMS File Manager
        </h1>
        <nav>
            <ol class="breadcrumb bc">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">CMS File Manager</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Stats -->
<div class="d-flex gap-3 mb-4 flex-wrap">
    <?php
    $sc = [
        ['Total File', $all_count,                      '#3b82f6', 'ph-files'],
        ['Total Size',  fmt_size((int)$all_size),        '#8b5cf6', 'ph-database'],
        ['Gambar',      $stat_map['image']['cnt']  ?? 0, '#10b981', 'ph-image'],
        ['Dokumen',     $stat_map['document']['cnt'] ?? 0, '#f97316', 'ph-file-text'],
        ['Video/Audio', ($stat_map['video']['cnt'] ?? 0) + ($stat_map['audio']['cnt'] ?? 0), '#ec4899', 'ph-video'],
    ];
    foreach ($sc as [$lbl, $val, $clr, $ico]): ?>
        <div class="stat-chip">
            <div style="width:34px;height:34px;border-radius:8px;background:<?= $clr ?>22;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="ph <?= $ico ?>" style="font-size:17px;color:<?= $clr ?>"></i>
            </div>
            <div>
                <div style="font-size:18px;font-weight:800;line-height:1"><?= $val ?></div>
                <div style="font-size:10px;color:var(--mut);margin-top:2px"><?= $lbl ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">

    <!-- ══ LEFT: Upload panel ════════════════════════════════════ -->
    <div class="col-xl-4 col-lg-5">

        <!-- Drop / Upload card -->
        <div class="card-c mb-4">
            <div class="ch">
                <div>
                    <p class="ct">Upload File</p>
                    <p class="cs">Maks. 20 MB per file</p>
                </div>
            </div>
            <div class="cb">
                <!-- Folder selector -->
                <div style="margin-bottom:14px">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);display:block;margin-bottom:6px">Folder</label>
                    <input type="text" id="folder-inp" class="fi" style="width:100%"
                        placeholder="uncategorized" list="folder-list"
                        value="<?= htmlspecialchars($filter_folder ?: 'uncategorized') ?>" />
                    <datalist id="folder-list">
                        <?php foreach ($folders_all as $fo): ?>
                            <option value="<?= htmlspecialchars($fo) ?>">
                            <?php endforeach; ?>
                    </datalist>
                </div>

                <!-- Custom name -->
                <div style="margin-bottom:14px">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);display:block;margin-bottom:6px">
                        Nama File <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--mut)">(opsional, untuk 1 file)</span>
                    </label>
                    <input type="text" id="custom-name-inp" class="fi" style="width:100%"
                        placeholder="Kosongkan = pakai nama asli file"
                        autocomplete="off" />
                    <div style="font-size:10px;color:var(--mut);margin-top:4px;display:none" id="custom-name-hint"></div>
                </div>

                <!-- Drop zone -->
                <div class="drop-zone" id="drop-zone">
                    <input type="file" id="file-inp" multiple
                        accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.json,.zip" />
                    <div class="drop-icon">📂</div>
                    <div class="drop-txt">Klik atau drag &amp; drop file</div>
                    <div class="drop-sub">PNG, JPG, GIF, WEBP, PDF, DOC, MP4, dll</div>
                </div>

                <!-- Progress -->
                <div class="upload-progress-wrap" id="up-progress">
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--mut);margin-bottom:5px">
                        <span id="up-label">Mengupload…</span>
                        <span id="up-pct">0%</span>
                    </div>
                    <div class="up-bar-bg">
                        <div class="up-bar-fill" id="up-bar"></div>
                    </div>
                    <div class="up-file-list" id="up-file-list"></div>
                </div>

                <button type="button" class="btn btn-primary w-100 mt-3" style="border-radius:9px" id="btn-upload" disabled>
                    <i class="ph ph-upload-simple me-1"></i>Upload Sekarang
                </button>
            </div>
        </div>

        <!-- Folder tree card -->
        <div class="card-c">
            <div class="ch">
                <p class="ct">Folder</p>
            </div>
            <div class="cb" style="padding-top:6px">
                <a href="media.php" class="ttab <?= !$filter_folder ? 'active' : '' ?>" style="width:100%;margin-bottom:5px;display:flex">
                    <i class="ph ph-folder-open"></i> Semua Folder
                    <span style="margin-left:auto;font-size:10px;opacity:.6"><?= $all_count ?></span>
                </a>
                <?php foreach ($folders_all as $fo):
                    $fc_stmt = $pdo->prepare("SELECT COUNT(*) FROM cms_files WHERE folder=?");
                    $fc_stmt->execute([$fo]);
                    $fc_n = $fc_stmt->fetchColumn();
                ?>
                    <a href="media.php?folder=<?= urlencode($fo) ?><?= $filter_type ? "&type={$filter_type}" : '' ?>"
                        class="ttab <?= $filter_folder === $fo ? 'active' : '' ?>"
                        style="width:100%;margin-bottom:5px;display:flex">
                        <i class="ph ph-folder"></i> <?= htmlspecialchars($fo) ?>
                        <span style="margin-left:auto;font-size:10px;opacity:.6"><?= $fc_n ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- ══ RIGHT: File gallery ═══════════════════════════════════ -->
    <div class="col-xl-8 col-lg-7">
        <div class="card-c">

            <!-- Toolbar -->
            <div class="ch" style="flex-wrap:wrap;gap:10px">
                <div style="flex:1;min-width:200px">
                    <form method="GET" style="display:flex;gap:8px;align-items:center">
                        <?php if ($filter_type): ?><input type="hidden" name="type" value="<?= htmlspecialchars($filter_type) ?>" /><?php endif; ?>
                        <?php if ($filter_folder): ?><input type="hidden" name="folder" value="<?= htmlspecialchars($filter_folder) ?>" /><?php endif; ?>
                        <input type="text" name="q" class="fi" style="flex:1;padding:7px 12px;font-size:12px"
                            placeholder="Cari nama file…" value="<?= htmlspecialchars($search) ?>" />
                        <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px;padding:7px 12px">
                            <i class="ph ph-magnifying-glass"></i>
                        </button>
                        <?php if ($search): ?>
                            <a href="media.php?<?= $filter_type ? "type={$filter_type}&" : '' ?><?= $filter_folder ? "folder=" . urlencode($filter_folder) : ''; ?>"
                                class="btn btn-sm" style="border-radius:7px;padding:7px 10px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">
                                <i class="ph ph-x"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- View toggle -->
                <div style="display:flex;gap:5px">
                    <button class="view-toggle-btn active" id="btn-grid" onclick="setView('grid')" title="Grid">
                        <i class="ph ph-squares-four"></i>
                    </button>
                    <button class="view-toggle-btn" id="btn-list" onclick="setView('list')" title="List">
                        <i class="ph ph-rows"></i>
                    </button>
                </div>
            </div>

            <!-- Type tabs -->
            <div style="padding: 0 20px 14px;border-bottom:1px solid var(--border)">
                <div class="type-tabs">
                    <?php
                    $tabs = [
                        ['', 'ph-files', 'Semua',     $all_count],
                        ['image', 'ph-image', 'Gambar',    $stat_map['image']['cnt']   ?? 0],
                        ['video', 'ph-video', 'Video',     $stat_map['video']['cnt']   ?? 0],
                        ['audio', 'ph-music-note', 'Audio',     $stat_map['audio']['cnt']   ?? 0],
                        ['document', 'ph-file-text', 'Dokumen',   $stat_map['document']['cnt'] ?? 0],
                        ['other', 'ph-file', 'Lainnya',   $stat_map['other']['cnt']   ?? 0],
                    ];
                    foreach ($tabs as [$tv, $ti, $tl, $tn]):
                        $url = 'media.php?';
                        if ($tv) $url .= "type={$tv}&";
                        if ($filter_folder) $url .= 'folder=' . urlencode($filter_folder) . '&';
                        if ($search) $url .= 'q=' . urlencode($search) . '&';
                    ?>
                        <a href="<?= $url ?>" class="ttab <?= $filter_type === $tv ? 'active' : '' ?>">
                            <i class="ph <?= $ti ?>"></i><?= $tl ?>
                            <span style="font-size:9px;opacity:.7;margin-left:2px">(<?= $tn ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cb">
                <?php if (empty($files_rows)): ?>
                    <div style="text-align:center;padding:56px 20px;color:var(--mut)">
                        <div style="font-size:52px;opacity:.15;margin-bottom:14px">📁</div>
                        <div style="font-weight:700;margin-bottom:4px">Tidak ada file</div>
                        <div style="font-size:12px">Upload file atau ubah filter</div>
                    </div>
                <?php else: ?>

                    <!-- Grid view -->
                    <div class="file-grid" id="view-grid">
                        <?php foreach ($files_rows as $f):
                            $thumb     = file_thumb_url($f, $cms_base);
                            $pub_url   = $cms_base . $f['id'] . '.' . pathinfo($f['original_name'], PATHINFO_EXTENSION);
                            $ico       = type_icon($f['file_type']);
                            $clr       = type_color($f['file_type']);
                            $display   = $f['title'] ?: $f['original_name'];
                        ?>
                            <div class="fc <?= !$f['is_active'] ? 'inactive' : '' ?>">

                                <div class="fc-thumb-wrap">
                                    <div class="status-dot <?= $f['is_active'] ? 'dot-on' : 'dot-off' ?>"></div>

                                    <?php if ($f['file_type'] === 'image' && $thumb): ?>
                                        <img src="<?= htmlspecialchars($thumb) ?>" alt="" class="fc-thumb"
                                            onclick="openLightbox('<?= htmlspecialchars($thumb, ENT_QUOTES) ?>')"
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                        <div class="fc-thumb-icon" style="display:none;color:<?= $clr ?>"><i class="ph <?= $ico ?>"></i></div>
                                    <?php else: ?>
                                        <div class="fc-thumb-icon" style="color:<?= $clr ?>">
                                            <i class="ph <?= $ico ?>" style="opacity:.5"></i>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Overlay: delete only -->
                                    <div class="fc-overlay">
                                        <form method="POST" style="display:contents"
                                            onsubmit="return confirm('Hapus «<?= addslashes(htmlspecialchars($display)) ?>»?')">
                                            <input type="hidden" name="action" value="delete" />
                                            <input type="hidden" name="id" value="<?= $f['id'] ?>" />
                                            <button type="submit" class="fc-ov-btn red">
                                                <i class="ph ph-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div><!-- /fc-thumb-wrap -->

                                <!-- Body: preview/open + copy link only (no edit) -->
                                <div class="fc-body">
                                    <div class="fc-name" title="<?= htmlspecialchars($display) ?>"><?= htmlspecialchars($display) ?></div>
                                    <div class="fc-meta">
                                        <span class="fc-type-badge" style="background:<?= $clr ?>18;color:<?= $clr ?>"><?= $f['file_type'] ?></span>
                                        <span><?= fmt_size((int)$f['file_size']) ?></span>
                                    </div>
                                    <div style="display:flex;gap:5px;margin-top:8px">
                                        <?php if ($f['file_type'] === 'image'): ?>
                                            <button type="button"
                                                onclick="openLightbox('<?= htmlspecialchars($thumb, ENT_QUOTES) ?>')"
                                                title="Preview"
                                                style="flex:1;height:26px;border-radius:6px;border:1px solid var(--border);background:var(--hover);color:var(--sub);font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;transition:all .12s"
                                                onmouseover="this.style.background='var(--accent)';this.style.color='#fff';this.style.borderColor='var(--accent)'"
                                                onmouseout="this.style.background='var(--hover)';this.style.color='var(--sub)';this.style.borderColor='var(--border)'">
                                                <i class="ph ph-eye"></i> View
                                            </button>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($pub_url) ?>" target="_blank"
                                                title="Buka"
                                                style="flex:1;height:26px;border-radius:6px;border:1px solid var(--border);background:var(--hover);color:var(--sub);font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;text-decoration:none;transition:all .12s"
                                                onmouseover="this.style.background='var(--accent)';this.style.color='#fff';this.style.borderColor='var(--accent)'"
                                                onmouseout="this.style.background='var(--hover)';this.style.color='var(--sub)';this.style.borderColor='var(--border)'">
                                                <i class="ph ph-arrow-square-out"></i> Buka
                                            </a>
                                        <?php endif; ?>
                                        <button type="button"
                                            onclick="copyUrl('<?= htmlspecialchars($pub_url, ENT_QUOTES) ?>')"
                                            title="Salin link"
                                            style="flex:1;height:26px;border-radius:6px;border:1px solid rgba(1,210,152,.3);background:rgba(1,210,152,.07);color:var(--accent);font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;transition:all .12s"
                                            onmouseover="this.style.background='var(--accent)';this.style.color='#fff';this.style.borderColor='var(--accent)'"
                                            onmouseout="this.style.background='rgba(1,210,152,.07)';this.style.color='var(--accent)';this.style.borderColor='rgba(1,210,152,.3)'">
                                            <i class="ph ph-copy"></i> Salin
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- List view -->
                    <div class="file-list-view" id="view-list">
                        <?php foreach ($files_rows as $f):
                            $thumb   = file_thumb_url($f, $cms_base);
                            $pub_url = $cms_base . $f['id'] . '.' . pathinfo($f['original_name'], PATHINFO_EXTENSION);
                            $ico     = type_icon($f['file_type']);
                            $clr     = type_color($f['file_type']);
                            $display = $f['title'] ?: $f['original_name'];
                        ?>
                            <div class="fl-row <?= !$f['is_active'] ? 'inactive' : '' ?>">
                                <!-- Thumb -->
                                <div class="fl-thumb">
                                    <?php if ($f['file_type'] === 'image' && $thumb): ?>
                                        <img src="<?= htmlspecialchars($thumb) ?>" alt="" onerror="this.style.display='none'" />
                                    <?php else: ?>
                                        <i class="ph <?= $ico ?>" style="font-size:22px;color:<?= $clr ?>"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="fl-info">
                                    <div class="fl-name"><?= htmlspecialchars($display) ?></div>
                                    <div class="fl-sub">
                                        <span class="fc-type-badge" style="background:<?= $clr ?>18;color:<?= $clr ?>"><?= $f['file_type'] ?></span>
                                        <span><?= fmt_size((int)$f['file_size']) ?></span>
                                        <span><?= $f['folder'] ?></span>
                                        <span><?= date('d M Y', strtotime($f['created_at'])) ?></span>
                                        <span><?= $f['views'] ?> views</span>
                                    </div>
                                    <!-- Copy URL chip -->
                                    <div class="copy-chip mt-1" onclick="copyUrl('<?= htmlspecialchars($pub_url, ENT_QUOTES) ?>')" title="Copy URL">
                                        <i class="ph ph-copy" style="flex-shrink:0"></i>
                                        <?= htmlspecialchars('/cms/' . $f['id'] . '.' . pathinfo($f['original_name'], PATHINFO_EXTENSION)) ?>
                                    </div>
                                </div>

                                <!-- List actions: view/open + copy + delete only (no edit) -->
                                <div class="fl-actions">
                                    <?php if ($f['file_type'] === 'image' && $thumb): ?>
                                        <button type="button" class="fl-btn" title="Preview"
                                            onclick="openLightbox('<?= htmlspecialchars($thumb, ENT_QUOTES) ?>')">
                                            <i class="ph ph-eye"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= htmlspecialchars($pub_url) ?>" target="_blank" class="fl-btn" title="Buka">
                                            <i class="ph ph-arrow-square-out"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="fl-btn" title="Salin Link"
                                        onclick="copyUrl('<?= htmlspecialchars($pub_url, ENT_QUOTES) ?>')">
                                        <i class="ph ph-copy"></i>
                                    </button>
                                    <form method="POST" style="display:contents"
                                        onsubmit="return confirm('Hapus «<?= addslashes(htmlspecialchars($display)) ?>»?')">
                                        <input type="hidden" name="action" value="delete" />
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>" />
                                        <button type="submit" class="fl-btn red" title="Hapus">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;flex-wrap:wrap;gap:8px">
                            <div style="font-size:12px;color:var(--mut)">
                                <?= $total_count ?> file &bull; Hal <?= $page ?> / <?= $total_pages ?>
                            </div>
                            <div class="pgn">
                                <?php
                                $qs = [];
                                if ($filter_type)   $qs[] = 'type=' . urlencode($filter_type);
                                if ($filter_folder) $qs[] = 'folder=' . urlencode($filter_folder);
                                if ($search)        $qs[] = 'q=' . urlencode($search);
                                $qs_base = $qs ? '&' . implode('&', $qs) : '';

                                $start = max(1, $page - 2);
                                $end   = min($total_pages, $page + 2);
                                ?>
                                <a href="?page=<?= $page - 1 ?><?= $qs_base ?>" class="pgn-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <i class="ph ph-caret-left"></i>
                                </a>
                                <?php if ($start > 1): ?><a href="?page=1<?= $qs_base ?>" class="pgn-item">1</a><?php if ($start > 2) echo '<span class="pgn-item" style="pointer-events:none">…</span>';
                                                                                                            endif; ?>
                                <?php for ($p = $start; $p <= $end; $p++): ?>
                                    <a href="?page=<?= $p ?><?= $qs_base ?>" class="pgn-item <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                                <?php endfor; ?>
                                <?php if ($end < $total_pages): ?><?php if ($end < $total_pages - 1) echo '<span class="pgn-item" style="pointer-events:none">…</span>'; ?><a href="?page=<?= $total_pages ?><?= $qs_base ?>" class="pgn-item"><?= $total_pages ?></a><?php endif; ?>
                            <a href="?page=<?= $page + 1 ?><?= $qs_base ?>" class="pgn-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <i class="ph ph-caret-right"></i>
                            </a>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══ Edit Modal (still accessible via direct ?edit= URL if needed) ══ -->
<?php if ($edit_file): ?>
    <div class="modal-back" id="edit-modal">
        <div class="modal-box">
            <div class="modal-head">
                <i class="ph ph-pencil-simple" style="font-size:18px;color:var(--accent)"></i>
                Edit Metadata File
                <a href="media.php?<?= $filter_type ? "type={$filter_type}&" : '' ?><?= $filter_folder ? "folder=" . urlencode($filter_folder) . '&' : ''; ?><?= $search ? "q=" . urlencode($search) : ''; ?>"
                    class="modal-close"><i class="ph ph-x"></i></a>
            </div>
            <div class="modal-body">
                <?php
                $ef_ext  = pathinfo($edit_file['original_name'], PATHINFO_EXTENSION);
                $ef_url  = $cms_base . $edit_file['id'] . '.' . $ef_ext;
                ?>
                <div style="background:var(--hover);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:18px;display:flex;gap:12px;align-items:center">
                    <?php if ($edit_file['file_type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($ef_url) ?>" alt=""
                            style="width:60px;height:60px;border-radius:8px;object-fit:cover;flex-shrink:0"
                            onerror="this.style.display='none'" />
                    <?php else: ?>
                        <div style="width:60px;height:60px;border-radius:8px;background:<?= type_color($edit_file['file_type']) ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="ph <?= type_icon($edit_file['file_type']) ?>" style="font-size:28px;color:<?= type_color($edit_file['file_type']) ?>"></i>
                        </div>
                    <?php endif; ?>
                    <div style="min-width:0">
                        <div style="font-weight:700;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($edit_file['original_name']) ?></div>
                        <div style="font-size:11px;color:var(--mut);margin-top:2px"><?= fmt_size((int)$edit_file['file_size']) ?> &bull; <?= $edit_file['mime_type'] ?> &bull; <?= $edit_file['views'] ?> views</div>
                        <div class="copy-chip mt-2" onclick="copyUrl('<?= htmlspecialchars($ef_url, ENT_QUOTES) ?>')">
                            <i class="ph ph-copy"></i>
                            <?= htmlspecialchars('/cms/' . $edit_file['id'] . '.' . $ef_ext) ?>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="edit" />
                    <input type="hidden" name="id" value="<?= $edit_file['id'] ?>" />

                    <div style="margin-bottom:14px">
                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);display:block;margin-bottom:6px">Judul</label>
                        <input type="text" name="title" class="fi" placeholder="Nama tampilan (opsional)"
                            value="<?= htmlspecialchars($edit_file['title'] ?? '') ?>" />
                    </div>
                    <div style="margin-bottom:14px">
                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);display:block;margin-bottom:6px">Deskripsi</label>
                        <textarea name="description" class="fi" rows="3" style="resize:vertical" placeholder="Keterangan file…"><?= htmlspecialchars($edit_file['description'] ?? '') ?></textarea>
                    </div>
                    <div style="margin-bottom:18px">
                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);display:block;margin-bottom:6px">Folder</label>
                        <input type="text" name="folder" class="fi" list="folder-list-modal"
                            value="<?= htmlspecialchars($edit_file['folder'] ?? 'uncategorized') ?>" />
                        <datalist id="folder-list-modal">
                            <?php foreach ($folders_all as $fo): ?><option value="<?= htmlspecialchars($fo) ?>"><?php endforeach; ?>
                        </datalist>
                    </div>

                    <div style="display:flex;gap:8px;justify-content:flex-end">
                        <a href="media.php?<?= $filter_type ? "type={$filter_type}&" : '' ?><?= $filter_folder ? "folder=" . urlencode($filter_folder) . '&' : ''; ?><?= $search ? "q=" . urlencode($search) : ''; ?>"
                            class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px">
                            <i class="ph ph-floppy-disk me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" style="display:none" onclick="closeLightbox()">
    <button class="lb-close" onclick="event.stopPropagation();closeLightbox()"><i class="ph ph-x"></i></button>
    <img id="lb-img" src="" alt="" onclick="event.stopPropagation()" />
</div>

<!-- Copy toast -->
<div id="copy-toast" style="
  position:fixed;bottom:24px;right:24px;z-index:99999;
  background:#1e293b;border:1px solid rgba(255,255,255,.12);
  border-radius:9px;padding:9px 16px;font-size:12px;font-weight:600;
  color:#fff;display:none;align-items:center;gap:7px;
  box-shadow:0 8px 24px rgba(0,0,0,.3)
">
    <i class="ph ph-check-circle" style="color:#10b981;font-size:16px"></i>
    URL berhasil disalin!
</div>

<?php
$page_scripts = <<<'SCRIPT'
<script>
(function () {
  'use strict';

  /* ════════════════════════════════════════════════════════════
     HELPERS
  ════════════════════════════════════════════════════════════ */
  function escHtml(s) {
    return String(s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function fmtSize(b) {
    return b >= 1048576 ? (b/1048576).toFixed(1)+' MB'
         : b >= 1024   ? (b/1024).toFixed(1)+' KB'
         : b+' B';
  }

  /* ════════════════════════════════════════════════════════════
     VIEW TOGGLE (grid / list)
  ════════════════════════════════════════════════════════════ */
  window.setView = function(v) {
    localStorage.setItem('cms_view', v);
    var vGrid = document.getElementById('view-grid');
    var vList = document.getElementById('view-list');
    var btnG  = document.getElementById('btn-grid');
    var btnL  = document.getElementById('btn-list');
    if (vGrid) vGrid.classList.toggle('hide', v !== 'grid');
    if (vList) vList.classList.toggle('show', v === 'list');
    if (btnG)  btnG.classList.toggle('active', v === 'grid');
    if (btnL)  btnL.classList.toggle('active', v === 'list');
  };

  /* ════════════════════════════════════════════════════════════
     COPY URL
  ════════════════════════════════════════════════════════════ */
  window.copyUrl = function(url) {
    function showToast() {
      var t = document.getElementById('copy-toast');
      if (!t) return;
      t.style.display = 'flex';
      clearTimeout(t._tm);
      t._tm = setTimeout(function(){ t.style.display = 'none'; }, 2200);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(showToast).catch(function(){ fbCopy(url, showToast); });
    } else {
      fbCopy(url, showToast);
    }
  };
  function fbCopy(text, cb) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    try { document.execCommand('copy'); cb(); } catch(e) {}
    document.body.removeChild(ta);
  }

  /* ════════════════════════════════════════════════════════════
     LIGHTBOX
  ════════════════════════════════════════════════════════════ */
  window.openLightbox = function(src) {
    var img = document.getElementById('lb-img');
    var lb  = document.getElementById('lightbox');
    if (img) img.src = src;
    if (lb)  lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };
  window.closeLightbox = function() {
    var lb = document.getElementById('lightbox');
    if (lb) lb.style.display = 'none';
    document.body.style.overflow = '';
  };
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') window.closeLightbox();
  });

  /* ════════════════════════════════════════════════════════════
     UPLOAD ENGINE
  ════════════════════════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function () {

    /* Restore view preference */
    window.setView(localStorage.getItem('cms_view') || 'grid');

    var dz       = document.getElementById('drop-zone');
    var fInp     = document.getElementById('file-inp');
    var btnUp    = document.getElementById('btn-upload');
    var progWrap = document.getElementById('up-progress');
    var progBar  = document.getElementById('up-bar');
    var progPct  = document.getElementById('up-pct');
    var progLbl  = document.getElementById('up-label');
    var fileList = document.getElementById('up-file-list');

    var pendingFiles = [];
    var isUploading  = false;

    /* ── Stage files (build preview list) ─────────────────── */
    function stageFiles(files) {
      if (!files || !files.length) return;
      pendingFiles = Array.from(files);

      progWrap.classList.add('active');
      fileList.innerHTML = '';
      progBar.style.width = '0%';
      progBar.style.background = 'var(--accent)';
      progPct.textContent = '0%';
      progLbl.textContent = pendingFiles.length + ' file siap diupload';

      var nameInp  = document.getElementById('custom-name-inp');
      var nameHint = document.getElementById('custom-name-hint');
      if (nameInp) {
        if (pendingFiles.length > 1) {
          nameInp.disabled    = true;
          nameInp.placeholder = 'Multi-file → nama diambil dari masing-masing file';
          if (nameHint) { nameHint.textContent = ''; nameHint.style.display = 'none'; }
        } else {
          nameInp.disabled    = false;
          nameInp.placeholder = 'Kosongkan = pakai nama asli file';
          if (nameHint && pendingFiles[0]) {
            nameHint.textContent = 'Nama asli: ' + pendingFiles[0].name;
            nameHint.style.display = 'block';
          }
        }
      }

      pendingFiles.forEach(function(f, i) {
        var row = document.createElement('div');
        row.className = 'up-file-item';
        row.id = 'ufi-' + i;
        row.innerHTML =
          '<span class="ufi-name" title="' + escHtml(f.name) + '">' + escHtml(f.name) + '</span>' +
          '<span class="ufi-sz">' + fmtSize(f.size) + '</span>' +
          '<span class="ufi-st ufi-wait" id="ufi-st-' + i + '">Antri…</span>';
        fileList.appendChild(row);
      });

      if (btnUp) {
        btnUp.disabled = false;
        btnUp.innerHTML = '<i class="ph ph-upload-simple"></i> Upload ' + pendingFiles.length + ' File';
      }
    }

    /* ── Upload a single file via XHR ──────────────────────── */
    function uploadOne(file, index, folder, customName) {
      return new Promise(function(resolve) {
        var st = document.getElementById('ufi-st-' + index);
        if (st) { st.className = 'ufi-st'; st.textContent = '⏳ Mengupload…'; }

        var fd = new FormData();
        fd.append('file', file);
        fd.append('folder', folder);
        if (customName) fd.append('custom_name', customName);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'media.php?ajax=upload', true);

        xhr.upload.onprogress = function(e) {
          if (!e.lengthComputable) return;
          var pct = Math.round((e.loaded / e.total) * 100);
          if (st) st.textContent = '⏳ ' + pct + '%';
        };

        xhr.onload = function() {
          var res;
          try { res = JSON.parse(xhr.responseText); }
          catch(e) { res = { ok: false, error: 'Respons server tidak valid.' }; }

          if (res.ok) {
            if (st) { st.className = 'ufi-st ufi-ok'; st.textContent = '✓ OK'; }
            resolve({ ok: true, res: res });
          } else {
            if (st) { st.className = 'ufi-st ufi-fail'; st.textContent = '✗ Gagal'; }
            var row = document.getElementById('ufi-' + index);
            if (row) {
              var errEl = document.createElement('div');
              errEl.style.cssText = 'font-size:10px;color:#ef4444;padding:2px 2px 4px';
              errEl.textContent = res.error || 'Upload gagal.';
              row.insertAdjacentElement('afterend', errEl);
            }
            resolve({ ok: false, error: res.error });
          }
        };

        xhr.onerror = function() {
          if (st) { st.className = 'ufi-st ufi-fail'; st.textContent = '✗ Error'; }
          resolve({ ok: false, error: 'Network error.' });
        };

        xhr.send(fd);
      });
    }

    /* ── Run upload queue sequentially ────────────────────── */
    async function runUploadQueue() {
      if (isUploading || !pendingFiles.length) return;
      isUploading = true;

      var folder     = (document.getElementById('folder-inp')?.value || '').trim() || 'uncategorized';
      var total      = pendingFiles.length;
      var customName = total === 1
        ? ((document.getElementById('custom-name-inp')?.value || '').trim())
        : '';
      var done = 0, okCount = 0, failCount = 0;

      if (btnUp) { btnUp.disabled = true; btnUp.innerHTML = '<i class="ph ph-circle-notch"></i> Mengupload…'; }

      for (var i = 0; i < total; i++) {
        progLbl.textContent = 'Mengupload file ' + (i + 1) + ' / ' + total + '…';

        var result = await uploadOne(pendingFiles[i], i, folder, customName);
        done++;
        if (result.ok) okCount++; else failCount++;

        var pct = Math.round((done / total) * 100);
        progBar.style.width = pct + '%';
        progPct.textContent = pct + '%';
      }

      isUploading  = false;
      pendingFiles = [];
      if (fInp) fInp.value = '';

      progLbl.textContent = 'Selesai: ' + okCount + ' berhasil' + (failCount ? ', ' + failCount + ' gagal' : '') + '.';
      progBar.style.background = failCount ? '#ef4444' : 'var(--accent)';
      if (btnUp) { btnUp.disabled = true; btnUp.innerHTML = '<i class="ph ph-upload-simple me-1"></i>Upload Sekarang'; }

      if (okCount > 0) {
        setTimeout(function(){ window.location.reload(); }, 1200);
      }
    }

    /* ── File input change ─────────────────────────────────── */
    if (fInp) {
      fInp.addEventListener('change', function() {
        if (this.files && this.files.length) stageFiles(this.files);
      });
    }

    /* ── Drag & drop on zone ───────────────────────────────── */
    if (dz) {
      ['dragenter','dragover'].forEach(function(evt) {
        dz.addEventListener(evt, function(e) {
          e.preventDefault(); e.stopPropagation();
          dz.classList.add('drag-over');
        });
      });
      dz.addEventListener('dragleave', function(e) {
        e.preventDefault();
        if (!dz.contains(e.relatedTarget)) dz.classList.remove('drag-over');
      });
      dz.addEventListener('drop', function(e) {
        e.preventDefault(); e.stopPropagation();
        dz.classList.remove('drag-over');
        var files = e.dataTransfer && e.dataTransfer.files;
        if (files && files.length) stageFiles(files);
      });
    }

    /* ── Upload button ─────────────────────────────────────── */
    if (btnUp) {
      btnUp.addEventListener('click', function(e) {
        e.preventDefault();
        if (pendingFiles.length) runUploadQueue();
      });
    }

    /* ── Toast auto-dismiss ────────────────────────────────── */
    document.querySelectorAll('.toast-item').forEach(function(t) {
      setTimeout(function(){ t.style.opacity='0'; t.style.transform='translateX(16px)'; }, 3200);
      setTimeout(function(){ t.remove(); }, 3700);
    });

    /* ── Edit modal backdrop click ─────────────────────────── */
    var em = document.getElementById('edit-modal');
    if (em) {
      em.addEventListener('click', function(e) {
        if (e.target === this) {
          var cl = this.querySelector('.modal-close');
          if (cl) window.location = cl.href;
        }
      });
    }

  }); // DOMContentLoaded

})(); // IIFE
</script>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>