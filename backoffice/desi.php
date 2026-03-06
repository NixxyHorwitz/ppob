<?php
// ═══════════════════════════════════════════════
// DEBUG SEMENTARA — hapus setelah masalah solved
// Tempel ini di baris PERTAMA users.php
// Buka: users.php?debug=1
// ═══════════════════════════════════════════════
if (isset($_GET['debug'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    echo '<pre style="background:#111;color:#0f0;padding:16px;font-size:12px">';
    echo "session_id     : " . session_id() . "\n";
    echo "session_name   : " . session_name() . "\n";
    echo "session status : " . session_status() . "\n";
    echo "SESSION data   : "; print_r($_SESSION);
    echo "\nCOOKIE data    : "; print_r($_COOKIE);
    echo "\nPHP session dir: " . ini_get('session.save_path') . "\n";
    echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "s\n";
    echo '</pre>';
    exit;
}
// ═══════════════════════════════════════════════