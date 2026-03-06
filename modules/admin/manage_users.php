<?php
require_once __DIR__ . '/../../includes/auth.php';
if ($_SESSION['role'] !== 'admin') { header("Location: ../dashboard"); exit; }
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>
<style>
    .main-content {
        min-height: 85vh;
        margin-left: 260px; 
        transition: all 0.3s ease;
       
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0 !important;
            padding: 10px;
        }
        
        .hero-mini h4 {
            font-size: 1.2rem;
        }

        .table thead {
            font-size: 11px;
        }
        
        .table tbody {
            font-size: 13px;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    }
</style>
<div class="main-content">
    <div class="container-fluid flex-grow-1">
          <div class="hero-mini text-center">
           <i class="fas fa-shopping-cart fs-3 text-info p-3 bg-white border border-info border-opacity-25 rounded-4 shadow-sm"></i>
            <h4 class="fw-bold mt-1">Manage Users</h4>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-2">
            <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                <i class="fas fa-plus me-1"></i> Tambah User
            </button>
        </div>

      <div class="card bg-dark border-0 shadow-sm overflow-hidden" style="border-radius: 10px;">
    <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 ">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email / No.HP</th>
                            <th>Saldo</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr class="align-middle">
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <div class="small"><?= htmlspecialchars($u['email']) ?></div>
                                <div class=" small"><?= htmlspecialchars($u['phone']) ?></div>
                            </td>
                            <td class="text-success">Rp <?= number_format($u['saldo'], 0, ',', '.') ?></td>
                            <td><span class="badge bg-info"><?= $u['role'] ?></span></td>
                            <td>
                                 <a href="detail_user?id=<?= $u['id'] ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <button class="btn btn-warning btn-sm" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="proses_users?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus user ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <form action="../../core/proses_users.php" method="POST">
                <input type="hidden" name="id" id="user_id">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="modalTitle">Tambah User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" id="username" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" id="email" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Password (Kosongkan jika tidak ganti)</label>
                        <input type="password" name="password" class="form-control bg-dark text-white border-secondary">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Saldo</label>
                            <input type="number" name="saldo" id="saldo" class="form-control bg-dark text-white border-secondary" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Role</label>
                            <select name="role" id="role" class="form-select bg-dark text-white border-secondary">
                                <option value="user">User</option>
                                <option value="reseller">Reseller</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="submit" name="save_user" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function resetForm() {
    document.getElementById('user_id').value = '';
    document.getElementById('modalTitle').innerText = 'Tambah User';
    document.querySelector('form').reset();
}

function editUser(data) {
    document.getElementById('user_id').value = data.id;
    document.getElementById('username').value = data.username;
    document.getElementById('email').value = data.email;
    document.getElementById('phone').value = data.phone;
    document.getElementById('saldo').value = data.saldo;
    document.getElementById('role').value = data.role;
    document.getElementById('modalTitle').innerText = 'Edit User';
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>