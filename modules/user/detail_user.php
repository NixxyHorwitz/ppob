<?php
require_once __DIR__ . '/../../includes/auth.php';
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard"); exit; }
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) { echo "User tidak ditemukan"; exit; }
?>

<div class="main-content">
    <div class="container-fluid mt-4">
        <div class="d-flex align-items-center mb-4">
            <a href="users" class="btn btn-secondary btn-sm me-3"><i class="fas fa-arrow-left"></i> Kembali</a>
            <h4 class="mb-0">Detail Profil User</h4>
        </div>

        <div class="row">
            <div class="col-md-4 text-center">
                <div class="card bg-dark border-secondary p-4">
                    <img src="../../assets/img/profiles/<?= $user['image'] ?: 'default.png' ?>" class="rounded-circle img-thumbnail mb-3 mx-auto" style="width: 150px; height: 150px; object-fit: cover;">
                    <h5 class="text-white"><?= htmlspecialchars($user['username']) ?></h5>
                    <span class="badge bg-info"><?= strtoupper($user['role']) ?></span>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card bg-dark text-white border-secondary">
                    <div class="card-body">
                        <table class="table table-dark table-borderless">
                            <tr>
                                <td width="30%">NIK</td>
                                <td>: <?= htmlspecialchars($user['nik'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td>Nama Lengkap</td>
                                <td>: <?= htmlspecialchars($user['username'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td>Email</td>
                                <td>: <?= htmlspecialchars($user['email']) ?></td>
                            </tr>
                            <tr>
                                <td>No. HP</td>
                                <td>: <?= htmlspecialchars($user['phone']) ?></td>
                            </tr>
                            <tr>
                                <td>Alamat</td>
                                <td>: <?= htmlspecialchars($user['address'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td>Saldo</td>
                                <td class="text-success">: Rp <?= number_format($user['saldo'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td>: <?= $user['is_active'] ? '<span class="text-success">Aktif</span>' : '<span class="text-danger">Non-Aktif</span>' ?></td>
                            </tr>
                            <tr>
                                <td>Bergabung Sejak</td>
                                <td>: <?= date('d M Y', strtotime($user['created_at'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .main-content { margin-left: 260px; min-height: 85vh; transition: all 0.3s ease; }
    @media (max-width: 768px) { .main-content { margin-left: 0 !important; padding: 10px; } }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>