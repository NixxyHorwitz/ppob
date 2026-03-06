<?php
// backoffice/debug2.php — HAPUS SETELAH SELESAI
session_start();
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"/><title>Debug Session</title></head>
<body style="background:#111;color:#0f0;font-family:monospace;padding:20px">

<h2>Session Debug</h2>
<pre><?php
echo "session_id   : " . session_id() . "\n";
echo "session_name : " . session_name() . "\n\n";

echo "=== ISI SESSION ===\n";
if (empty($_SESSION)) {
    echo "SESSION KOSONG!\n";
} else {
    foreach ($_SESSION as $k => $v) {
        echo "$k = " . var_export($v, true) . "\n";
    }
}

echo "\n=== CEK GUARD ===\n";
echo "admin_id   : " . ($_SESSION['admin_id']   ?? '❌ MISSING') . "\n";
echo "admin_role : " . ($_SESSION['admin_role'] ?? '❌ MISSING') . "\n";
echo "role ok?   : " . (($_SESSION['admin_role'] ?? '') === 'admin' ? '✅ YES' : '❌ NO') . "\n";

echo "\n=== PHP INFO ===\n";
echo "session.save_path : " . ini_get('session.save_path') . "\n";
echo "session.save_handler : " . ini_get('session.save_handler') . "\n";
echo "session cookie path : " . ini_get('session.cookie_path') . "\n";
?></pre>

<hr style="border-color:#333;margin:20px 0"/>
<p style="color:#888">Kalau session kosong padahal baru login → masalah ada di cookie tidak tersimpan atau session save path tidak bisa ditulis.</p>

<form method="POST" style="margin-top:16px">
  <input type="hidden" name="test" value="1"/>
  <button type="submit" style="background:#3b82f6;border:none;color:#fff;padding:8px 16px;border-radius:6px;cursor:pointer">
    Tes Tulis Session
  </button>
</form>

<?php if (!empty($_POST['test'])): 
    $_SESSION['test_tulis'] = 'berhasil_' . time();
    echo '<p style="color:#0f0;margin-top:12px">✅ Session ditulis: test_tulis = ' . $_SESSION['test_tulis'] . '</p>';
    echo '<p style="color:#aaa">Refresh halaman ini untuk lihat apakah session masih ada.</p>';
endif; ?>

</body>
</html>