<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

$userId = $_SESSION['user_id'];
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if ($isAdmin) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? OR (SELECT role FROM users WHERE id = notifications.user_id) = 'user'")->execute([$userId]);
} else {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
}

$query = "SELECT n.*, u_sender.username AS sender_name
          FROM notifications n 
          LEFT JOIN users u_sender ON n.message LIKE CONCAT('%User ID: ', u_sender.id, '%') ";

    if ($isAdmin) {
        $stmt = $pdo->prepare($query . " WHERE n.user_id = ? OR (SELECT role FROM users WHERE id = n.user_id) = 'user' ORDER BY n.created_at DESC");
    } else {
        $stmt = $pdo->prepare($query . " WHERE n.user_id = ? ORDER BY n.created_at DESC");
    }
    
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<style>
.main-content { margin-left: 260px; min-height: 85vh; transition: 0.3s; }
.unread { border-left: 4px solid #0dcaf0 !important; background: #2b3035 !important; }
@media (max-width: 768px) { .main-content { margin-left: 0 !important; } }
</style>

<div class="main-content">
    <div class="container-fluid py-4">
        <div class="hero-mini text-center mb-5">
            <i class="fas fa-envelope fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
            <h4 class="fw-bold mt-1">Kotak Masuk</h4>
        </div>

        <div class="row justify-content-center mt-5">
            <div class="col-lg-10">
                <?php if (empty($notifications)): ?>
                    <div class="alert alert-dark text-center border-secondary">Tidak ada pesan.</div>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <div class="card bg-dark border-secondary mb-2 <?= !$n['is_read'] ? 'unread' : '' ?>">
                            <div class="card-body p-3 text-start">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                            <i class="fas <?= (strpos($n['title'], 'Topup') !== false) ? 'fa-wallet text-warning' : 'fa-info-circle text-info' ?> fs-5"></i>
                                        </div>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="text-info fw-bold mb-0"><?= htmlspecialchars($n['title']) ?></h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                <i class="far fa-clock me-1"></i><?= date('H:i', strtotime($n['created_at'])) ?>
                                            </small>
                                        </div>

                                        <p class="text-light small mb-2 opacity-75">
                                            <?php 
                                            $msg = $n['message'];
                                            $pelaku = (!empty($n['sender_name'])) ? $n['sender_name'] : 'User';
                                            $msg = preg_replace('/User ID: \d+/', 'User: ' . $pelaku, $msg);
                                            echo htmlspecialchars($msg); 
                                            ?>
                                        </p>

                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-dark border border-secondary text-secondary fw-normal py-1 px-2" style="font-size: 0.65rem;">
                                                <i class="fas fa-calendar-alt me-1"></i>Tgl Transaksi: <?= date('d M Y, H:i:s', strtotime($n['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>