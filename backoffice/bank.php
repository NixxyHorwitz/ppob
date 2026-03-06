<?php
// backoffice/bank.php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/../config/database.php';

$page_title  = 'Manage Bank & Pembayaran';
$active_menu = 'bank';

// ══ ACTIONS ══════════════════════════════════════════════════
$toast   = '';
$toast_e = '';
$action  = $_POST['action'] ?? '';

// ── Tambah ───────────────────────────────────────────────────
if ($action === 'add') {
    $method_type    = in_array($_POST['method_type'] ?? '', ['MANUAL', 'QRIS']) ? $_POST['method_type'] : 'MANUAL';
    $bank_name      = trim($_POST['bank_name']      ?? '');
    $account_name   = trim($_POST['account_name']   ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $is_active      = isset($_POST['is_active']) ? 1 : 0;

    if ($method_type === 'MANUAL' && !$bank_name) {
        $toast_e = 'Nama bank wajib diisi.';
    } elseif ($method_type === 'MANUAL' && !$account_number) {
        $toast_e = 'Nomor rekening wajib diisi.';
    } elseif ($method_type === 'MANUAL' && !$account_name) {
        $toast_e = 'Nama pemilik rekening wajib diisi.';
    } else {
        $pdo->prepare("INSERT INTO payment_method (method_type, bank_name, account_name, account_number, is_active) VALUES (?,?,?,?,?)")
            ->execute([$method_type, $bank_name ?: null, $account_name ?: null, $account_number ?: null, $is_active]);
        $toast = 'Metode pembayaran berhasil ditambahkan.';
    }
}

// ── Edit ─────────────────────────────────────────────────────
if ($action === 'edit' && !empty($_POST['id'])) {
    $id             = (int)$_POST['id'];
    $method_type    = in_array($_POST['method_type'] ?? '', ['MANUAL', 'QRIS']) ? $_POST['method_type'] : 'MANUAL';
    $bank_name      = trim($_POST['bank_name']      ?? '');
    $account_name   = trim($_POST['account_name']   ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $is_active      = isset($_POST['is_active']) ? 1 : 0;

    if ($method_type === 'MANUAL' && !$bank_name) {
        $toast_e = 'Nama bank wajib diisi.';
    } elseif ($method_type === 'MANUAL' && !$account_number) {
        $toast_e = 'Nomor rekening wajib diisi.';
    } else {
        $pdo->prepare("UPDATE payment_method SET method_type=?, bank_name=?, account_name=?, account_number=?, is_active=? WHERE id=?")
            ->execute([$method_type, $bank_name ?: null, $account_name ?: null, $account_number ?: null, $is_active, $id]);
        $toast = 'Metode pembayaran berhasil diupdate.';
    }
}

// ── Toggle aktif ─────────────────────────────────────────────
if ($action === 'toggle' && !empty($_POST['id'])) {
    $pdo->prepare("UPDATE payment_method SET is_active = NOT is_active WHERE id = ?")
        ->execute([(int)$_POST['id']]);
    $toast = 'Status berhasil diubah.';
}

// ── Hapus ─────────────────────────────────────────────────────
if ($action === 'delete' && !empty($_POST['id'])) {
    $pdo->prepare("DELETE FROM payment_method WHERE id = ?")
        ->execute([(int)$_POST['id']]);
    $toast = 'Metode pembayaran berhasil dihapus.';
}

// ══ FETCH ════════════════════════════════════════════════════
$stats = $pdo->query("
    SELECT COUNT(*) total,
           SUM(is_active = 1) aktif,
           SUM(method_type = 'MANUAL') manual_count,
           SUM(method_type = 'QRIS')   qris_count
    FROM payment_method
")->fetch();

$q        = trim($_GET['q']    ?? '');
$f_type   = trim($_GET['type'] ?? '');
$f_status = $_GET['status']    ?? '';

$where = [];
$params = [];
if ($q) {
    $where[] = "(bank_name LIKE ? OR account_name LIKE ? OR account_number LIKE ?)";
    $s = "%$q%";
    array_push($params, $s, $s, $s);
}
if ($f_type) {
    $where[] = "method_type = ?";
    $params[] = $f_type;
}
if ($f_status !== '') {
    $where[] = "is_active = ?";
    $params[] = (int)$f_status;
}
$wsql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM payment_method $wsql ORDER BY is_active DESC, id ASC");
$stmt->execute($params);
$banks = $stmt->fetchAll();

// Buka modal edit jika ada ?edit=
$edit_data = null;
if (!empty($_GET['edit'])) {
    $eu = $pdo->prepare("SELECT * FROM payment_method WHERE id = ?");
    $eu->execute([(int)$_GET['edit']]);
    $edit_data = $eu->fetch();
}

$qs = http_build_query(array_filter(['q' => $q, 'type' => $f_type, 'status' => $f_status]));

// Helper icon per bank
function bank_icon(string $name): string
{
    $map = [
        'bca'     => '🏦',
        'bri' => '🏦',
        'bni' => '🏦',
        'mandiri' => '🏦',
        'cimb'    => '🏦',
        'btn' => '🏦',
        'permata' => '🏦',
        'gopay'   => '💚',
        'dana' => '💙',
        'ovo' => '💜',
        'shopeepay' => '🧡',
        'qris'    => '📱',
    ];
    $key = strtolower($name);
    foreach ($map as $k => $v) {
        if (str_contains($key, $k)) return $v;
    }
    return '🏦';
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ══ TOAST ══ -->
<div class="toast-wrap">
    <?php if ($toast):   ?><div class="toast-item toast-ok"><i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>
    <?php if ($toast_e): ?><div class="toast-item toast-err"><i class="ph ph-warning-circle" style="font-size:18px;flex-shrink:0"></i><?= htmlspecialchars($toast_e) ?></div><?php endif; ?>
</div>

<!-- ══ PAGE HEADER ══ -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1>Bank & Pembayaran</h1>
        <nav>
            <ol class="breadcrumb bc">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Bank & Pembayaran</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" style="border-radius:8px"
        data-bs-toggle="modal" data-bs-target="#mAdd">
        <i class="ph ph-plus me-1"></i> Tambah Metode
    </button>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="sc blue">
            <div class="si blue"><i class="ph-fill ph-bank"></i></div>
            <div class="sv"><?= $stats['total'] ?></div>
            <div class="sl">Total Metode</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="sc green">
            <div class="si green"><i class="ph-fill ph-check-circle"></i></div>
            <div class="sv"><?= $stats['aktif'] ?></div>
            <div class="sl">Aktif</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="sc orange">
            <div class="si orange"><i class="ph-fill ph-buildings"></i></div>
            <div class="sv"><?= $stats['manual_count'] ?></div>
            <div class="sl">Transfer Bank</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="sc purple">
            <div class="si purple"><i class="ph-fill ph-qr-code"></i></div>
            <div class="sv"><?= $stats['qris_count'] ?></div>
            <div class="sl">QRIS</div>
        </div>
    </div>
</div>

<!-- ══ TABLE CARD ══ -->
<div class="card-c">
    <div class="ch">
        <div>
            <p class="ct">Daftar Metode Pembayaran</p>
            <p class="cs"><?= count($banks) ?> metode<?= $q ? " · cari: <strong style='color:var(--accent)'>" . htmlspecialchars($q) . "</strong>" : '' ?></p>
        </div>
    </div>

    <!-- Filter bar -->
    <div class="cb pb-0">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <div style="position:relative;flex:1;min-width:180px">
                <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--mut);font-size:16px;pointer-events:none"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="fi"
                    placeholder="Cari nama bank, rekening…" style="width:100%" />
            </div>
            <select name="type" class="fs">
                <option value="">Semua Tipe</option>
                <option value="MANUAL" <?= $f_type === 'MANUAL' ? 'selected' : '' ?>>Transfer Bank</option>
                <option value="QRIS" <?= $f_type === 'QRIS'   ? 'selected' : '' ?>>QRIS</option>
            </select>
            <select name="status" class="fs">
                <option value="">Semua Status</option>
                <option value="1" <?= $f_status === '1' ? 'selected' : '' ?>>Aktif</option>
                <option value="0" <?= $f_status === '0' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm" style="border-radius:7px;padding:8px 16px">
                <i class="ph ph-funnel me-1"></i>Filter
            </button>
            <?php if ($q || $f_type || $f_status !== ''): ?>
                <a href="bank.php" class="btn btn-sm" style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub);padding:8px 14px">
                    <i class="ph ph-x me-1"></i>Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Grid Cards (bukan tabel biasa, lebih visual untuk bank) -->
    <div class="cb">
        <?php if (empty($banks)): ?>
            <div class="text-center py-5" style="color:var(--mut)">
                <i class="ph ph-bank" style="font-size:48px;display:block;margin-bottom:10px;opacity:.4"></i>
                <div style="font-size:14px;font-weight:600;margin-bottom:4px">Belum ada metode pembayaran</div>
                <div style="font-size:12px">Klik <strong>Tambah Metode</strong> untuk memulai</div>
            </div>
        <?php else: ?>

            <!-- Desktop: Table -->
            <div class="table-responsive d-none d-md-block">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipe</th>
                            <th>Bank / Provider</th>
                            <th>No. Rekening / Kode QRIS</th>
                            <th>Nama Pemilik</th>
                            <th>Status</th>
                            <th style="text-align:center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banks as $b): ?>
                            <tr>
                                <!-- ID -->
                                <td><span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--mut)">#<?= $b['id'] ?></span></td>

                                <!-- Tipe -->
                                <td>
                                    <?php if ($b['method_type'] === 'QRIS'): ?>
                                        <span class="bd bd-pur"><i class="ph ph-qr-code"></i> QRIS</span>
                                    <?php else: ?>
                                        <span class="bd bd-acc"><i class="ph ph-bank"></i> Transfer</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Bank -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Ikon lingkaran -->
                                        <div style="width:36px;height:36px;border-radius:10px;
                              background:<?= $b['method_type'] === 'QRIS' ? 'var(--oks)' : 'var(--as)' ?>;
                              display:flex;align-items:center;justify-content:center;
                              font-size:18px;flex-shrink:0">
                                            <i class="ph <?= $b['method_type'] === 'QRIS' ? 'ph-qr-code' : 'ph-bank' ?>"
                                                style="color:<?= $b['method_type'] === 'QRIS' ? 'var(--ok)' : 'var(--accent)' ?>"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:13.5px">
                                                <?= $b['method_type'] === 'QRIS' ? 'QRIS' : htmlspecialchars($b['bank_name'] ?? '—') ?>
                                            </div>
                                            <div style="font-size:11px;color:var(--mut)">
                                                Dibuat <?= date('d M Y', strtotime($b['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Nomor rekening / QRIS snippet -->
                                <td>
                                    <?php if ($b['method_type'] === 'QRIS'): ?>
                                        <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--mut)">
                                            —
                                        </span>
                                    <?php else: ?>
                                        <div style="display:flex;align-items:center;gap:6px">
                                            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--text)">
                                                <?= htmlspecialchars($b['account_number'] ?? '—') ?>
                                            </span>
                                            <?php if ($b['account_number']): ?>
                                                <button type="button" class="ab" style="width:24px;height:24px;font-size:13px"
                                                    title="Copy" onclick="copyText('<?= htmlspecialchars($b['account_number']) ?>')">
                                                    <i class="ph ph-copy"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Nama pemilik -->
                                <td style="font-size:13px;color:var(--sub)">
                                    <?= $b['account_name'] ? htmlspecialchars($b['account_name']) : '<span style="color:var(--mut)">—</span>' ?>
                                </td>

                                <!-- Status toggle -->
                                <td>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="toggle" />
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>" />
                                        <?php if ($qs): ?><input type="hidden" name="_qs" value="<?= htmlspecialchars($qs) ?>"><?php endif; ?>
                                        <button type="submit" class="bd <?= $b['is_active'] ? 'bd-ok' : 'bd-err' ?>"
                                            style="border:none;cursor:pointer">
                                            <i class="ph <?= $b['is_active'] ? 'ph-check-circle' : 'ph-x-circle' ?>"></i>
                                            <?= $b['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                        </button>
                                    </form>
                                </td>

                                <!-- Aksi -->
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="?edit=<?= $b['id'] ?><?= $qs ? '&' . $qs : '' ?>"
                                            class="ab" title="Edit">
                                            <i class="ph ph-pencil-simple"></i>
                                        </a>
                                        <form method="POST" style="display:inline"
                                            onsubmit="return confirm('Hapus metode pembayaran <?= addslashes(htmlspecialchars($b['bank_name'] ?? 'QRIS')) ?>?')">
                                            <input type="hidden" name="action" value="delete" />
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>" />
                                            <button type="submit" class="ab red" title="Hapus">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile: Cards -->
            <div class="d-md-none">
                <?php foreach ($banks as $b): ?>
                    <div style="border:1px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:12px;
                      background:<?= $b['is_active'] ? 'transparent' : 'rgba(0,0,0,.15)' ?>">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:40px;height:40px;border-radius:10px;
                            background:<?= $b['method_type'] === 'QRIS' ? 'var(--oks)' : 'var(--as)' ?>;
                            display:flex;align-items:center;justify-content:center;font-size:20px">
                                    <i class="ph <?= $b['method_type'] === 'QRIS' ? 'ph-qr-code' : 'ph-bank' ?>"
                                        style="color:<?= $b['method_type'] === 'QRIS' ? 'var(--ok)' : 'var(--accent)' ?>"></i>
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:14px">
                                        <?= $b['method_type'] === 'QRIS' ? 'QRIS' : htmlspecialchars($b['bank_name'] ?? '—') ?>
                                    </div>
                                    <div style="font-size:11px;color:var(--mut)"><?= $b['method_type'] ?></div>
                                </div>
                            </div>
                            <span class="bd <?= $b['is_active'] ? 'bd-ok' : 'bd-err' ?>">
                                <i class="ph <?= $b['is_active'] ? 'ph-check-circle' : 'ph-x-circle' ?>"></i>
                                <?= $b['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </div>
                        <?php if ($b['method_type'] === 'MANUAL'): ?>
                            <div style="background:var(--hover);border-radius:var(--rs);padding:10px 12px;margin-bottom:12px">
                                <div style="font-size:11px;color:var(--mut);margin-bottom:2px">Nomor Rekening</div>
                                <div style="font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700;letter-spacing:1px">
                                    <?= htmlspecialchars($b['account_number'] ?? '—') ?>
                                </div>
                                <div style="font-size:12px;color:var(--sub);margin-top:2px">a/n <?= htmlspecialchars($b['account_name'] ?? '—') ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            <a href="?edit=<?= $b['id'] ?><?= $qs ? '&' . $qs : '' ?>" class="btn btn-sm flex-fill"
                                style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub);font-size:12px">
                                <i class="ph ph-pencil-simple me-1"></i>Edit
                            </a>
                            <form method="POST" style="flex:1"
                                onsubmit="return confirm('Hapus metode ini?')">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?= $b['id'] ?>" />
                                <button type="submit" class="btn btn-sm w-100"
                                    style="border-radius:7px;background:var(--es);border:1px solid rgba(239,68,68,.2);color:var(--err);font-size:12px">
                                    <i class="ph ph-trash me-1"></i>Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<!-- ══ MODAL: TAMBAH ══ -->
<div class="modal fade" id="mAdd" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content mc">
            <div class="modal-header mh">
                <h5 class="modal-title"><i class="ph ph-plus-circle me-2" style="color:var(--accent)"></i>Tambah Metode Pembayaran</h5>
                <button type="button" class="btn-close" style="filter:invert(1)" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add" />
                <div class="modal-body">

                    <!-- Tipe -->
                    <div class="mb-3">
                        <label class="ml">Tipe Pembayaran</label>
                        <div class="d-flex gap-2">
                            <label class="type-card active" id="tcManual" for="typeManual">
                                <input type="radio" name="method_type" value="MANUAL" id="typeManual" checked class="d-none" />
                                <i class="ph ph-bank" style="font-size:22px;color:var(--accent)"></i>
                                <div style="font-size:12px;font-weight:600;margin-top:4px">Transfer Bank</div>
                            </label>
                            <label class="type-card" id="tcQris" for="typeQris">
                                <input type="radio" name="method_type" value="QRIS" id="typeQris" class="d-none" />
                                <i class="ph ph-qr-code" style="font-size:22px;color:var(--ok)"></i>
                                <div style="font-size:12px;font-weight:600;margin-top:4px">QRIS</div>
                            </label>
                        </div>
                    </div>

                    <!-- Field bank (tampil/sembunyi sesuai tipe) -->
                    <div id="bankFields">
                        <div class="mb-3">
                            <label class="ml">Nama Bank *</label>
                            <input type="text" name="bank_name" class="fi w-100" placeholder="BCA, BRI, BNI, Mandiri…" />
                        </div>
                        <div class="mb-3">
                            <label class="ml">Nomor Rekening *</label>
                            <input type="text" name="account_number" class="fi w-100" placeholder="0123456789"
                                style="font-family:'JetBrains Mono',monospace;letter-spacing:1px" />
                        </div>
                        <div class="mb-3">
                            <label class="ml">Nama Pemilik Rekening *</label>
                            <input type="text" name="account_name" class="fi w-100" placeholder="PT Nama Perusahaan / Nama Pemilik" />
                        </div>
                    </div>

                    <!-- Info QRIS -->
                    <div id="qrisInfo" style="display:none" class="p-3 mb-3"
                        style="background:var(--oks);border:1px solid rgba(16,185,129,.2);border-radius:var(--rs)">
                        <div style="background:var(--oks);border:1px solid rgba(16,185,129,.15);border-radius:var(--rs);padding:12px">
                            <div style="font-size:12px;color:var(--ok);font-weight:600;margin-bottom:4px">
                                <i class="ph ph-info me-1"></i>Info QRIS
                            </div>
                            <div style="font-size:11px;color:var(--mut)">
                                Kode QRIS dikelola di menu <a href="settings.php" style="color:var(--accent)">Settings → Integrasi</a>.
                                Di sini hanya mencatat bahwa metode QRIS tersedia sebagai opsi pembayaran.
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="d-flex align-items-center justify-content-between p-3"
                        style="background:var(--hover);border:1px solid var(--border);border-radius:var(--rs)">
                        <div>
                            <div style="font-size:13px;font-weight:600">Aktifkan Sekarang</div>
                            <div style="font-size:11px;color:var(--mut)">Metode ini langsung tersedia untuk user</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="is_active" checked
                                style="background-color:var(--accent);border-color:var(--accent)" />
                        </div>
                    </div>

                </div>
                <div class="modal-footer mf">
                    <button type="button" class="btn btn-sm"
                        style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px">
                        <i class="ph ph-plus me-1"></i>Tambahkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL: EDIT ══ -->
<div class="modal fade" id="mEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content mc">
            <div class="modal-header mh">
                <h5 class="modal-title"><i class="ph ph-pencil-simple me-2" style="color:var(--accent)"></i>Edit Metode Pembayaran</h5>
                <button type="button" class="btn-close" style="filter:invert(1)" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit" />
                <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>" />
                <div class="modal-body">

                    <!-- Tipe (readonly saat edit) -->
                    <div class="mb-3">
                        <label class="ml">Tipe Pembayaran</label>
                        <div class="fi d-flex align-items-center gap-2" style="opacity:.7;cursor:default">
                            <i class="ph <?= ($edit_data['method_type'] ?? '') === 'QRIS' ? 'ph-qr-code' : 'ph-bank' ?>"
                                style="color:var(--accent)"></i>
                            <?= ($edit_data['method_type'] ?? '') === 'QRIS' ? 'QRIS' : 'Transfer Bank (MANUAL)' ?>
                        </div>
                        <input type="hidden" name="method_type" value="<?= htmlspecialchars($edit_data['method_type'] ?? 'MANUAL') ?>" />
                    </div>

                    <?php if (($edit_data['method_type'] ?? '') === 'MANUAL'): ?>
                        <div class="mb-3">
                            <label class="ml">Nama Bank *</label>
                            <input type="text" name="bank_name" class="fi w-100"
                                value="<?= htmlspecialchars($edit_data['bank_name'] ?? '') ?>"
                                placeholder="BCA, BRI, Mandiri…" />
                        </div>
                        <div class="mb-3">
                            <label class="ml">Nomor Rekening *</label>
                            <input type="text" name="account_number" class="fi w-100"
                                value="<?= htmlspecialchars($edit_data['account_number'] ?? '') ?>"
                                style="font-family:'JetBrains Mono',monospace;letter-spacing:1px" />
                        </div>
                        <div class="mb-3">
                            <label class="ml">Nama Pemilik Rekening</label>
                            <input type="text" name="account_name" class="fi w-100"
                                value="<?= htmlspecialchars($edit_data['account_name'] ?? '') ?>" />
                        </div>
                    <?php else: ?>
                        <div class="mb-3 p-3" style="background:var(--oks);border:1px solid rgba(16,185,129,.15);border-radius:var(--rs)">
                            <div style="font-size:12px;color:var(--ok);font-weight:600;margin-bottom:4px"><i class="ph ph-info me-1"></i>QRIS</div>
                            <div style="font-size:11px;color:var(--mut)">Kode QRIS dikelola di <a href="settings.php" style="color:var(--accent)">Settings → Integrasi</a>.</div>
                        </div>
                    <?php endif; ?>

                    <!-- Status -->
                    <div class="d-flex align-items-center justify-content-between p-3"
                        style="background:var(--hover);border:1px solid var(--border);border-radius:var(--rs)">
                        <div>
                            <div style="font-size:13px;font-weight:600">Status Aktif</div>
                            <div style="font-size:11px;color:var(--mut)">Tampilkan ke user sebagai opsi pembayaran</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                <?= !empty($edit_data['is_active']) ? 'checked' : '' ?>
                                style="background-color:var(--accent);border-color:var(--accent)" />
                        </div>
                    </div>

                </div>
                <div class="modal-footer mf">
                    <a href="bank.php<?= $qs ? '?' . $qs : '' ?>" class="btn btn-sm"
                        style="border-radius:7px;background:var(--hover);border:1px solid var(--border);color:var(--sub)">Batal</a>
                    <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px">
                        <i class="ph ph-floppy-disk me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .type-card {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 14px 10px;
        border: 2px solid var(--border);
        border-radius: var(--r);
        cursor: pointer;
        transition: all .2s;
        text-align: center;
        background: var(--hover);
    }

    .type-card.active {
        border-color: var(--accent);
        background: var(--as);
    }

    .type-card:hover {
        border-color: var(--accent);
    }

    .ml {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--sub);
        display: block;
        margin-bottom: 6px;
    }

    .bd-pur {
        background: rgba(168, 85, 247, .12);
        color: #a855f7;
        border: 1px solid rgba(168, 85, 247, .25);
    }
</style>

<?php
$open_edit = $edit_data ? 'new bootstrap.Modal(document.getElementById("mEdit")).show();' : '';

$page_scripts = <<<SCRIPT
<script>
// ── Open edit modal if ?edit= ─────────────────────────────────
{$open_edit}

// ── Type card toggle (add modal) ──────────────────────────────
document.querySelectorAll('input[name="method_type"]').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
    this.closest('.type-card').classList.add('active');

    const isQris  = this.value === 'QRIS';
    document.getElementById('bankFields').style.display = isQris ? 'none' : 'block';
    document.getElementById('qrisInfo').style.display   = isQris ? 'block' : 'none';

    // required toggle
    document.querySelectorAll('#bankFields input').forEach(inp => {
      inp.required = !isQris;
    });
  });
});

// ── Copy rekening ─────────────────────────────────────────────
function copyText(txt) {
  navigator.clipboard.writeText(txt).then(() => {
    const wrap = document.querySelector('.toast-wrap');
    if (!wrap) return;
    const t = document.createElement('div');
    t.className = 'toast-item toast-ok';
    t.innerHTML = '<i class="ph ph-check-circle" style="font-size:18px;flex-shrink:0"></i>Nomor rekening disalin!';
    wrap.appendChild(t);
    setTimeout(() => t.remove(), 3000);
  });
}

// ── Auto dismiss toast ────────────────────────────────────────
document.querySelectorAll('.toast-item').forEach(t => {
  setTimeout(() => t.style.opacity = '0', 3500);
  setTimeout(() => t.remove(), 4000);
});
</script>
SCRIPT;

require_once __DIR__ . '/includes/footer.php';
?>