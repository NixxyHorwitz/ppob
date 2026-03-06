
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px; background: #0ea5e9; color: white;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <div class="text-center mb-4">
                    <h3 class="fw-bold" style="font-family: 'Comfortaa'; color: #ffffff;">UsahaPPOB</h3>
                    <p class="small" style="color: #e0f2fe;">Silahkan masuk ke akun Anda</p>
                </div>
                <form action="auth/login_process.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" style="color: #e0f2fe;">Username</label>
                        <input type="text" name="username" class="form-control border-0 py-2" required 
                               style="border-radius: 10px; background-color: #bae6fd; color: #0c4a6e;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold" style="color: #e0f2fe;">Password</label>
                        <input type="password" name="password" class="form-control border-0 py-2" required 
                               style="border-radius: 10px; background-color: #bae6fd; color: #0c4a6e;">
                    </div>
                    <button type="submit" name="login" class="btn w-50 d-block mx-auto py-2 fw-bold login-card-box" 
                            style="border-radius: 10px; background-color: #38bdf8; color: white;">Masuk Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px; background: #0ea5e9; color: white;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <div class="text-center mb-4">
                    <h3 class="fw-bold" style="font-family: 'Comfortaa'; color: #ffffff;">UsahaPPOB</h3>
                    <p class="small" style="color: #8B0000;">Pendaftaran sementara dinonaktifkan.</p>
                </div>
                <form>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" style="color: #e0f2fe;">Nama Lengkap</label>
                        <input type="text" class="form-control border-0 py-2" style="border-radius: 10px; background-color: #bae6fd; color: #0c4a6e;" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" style="color: #e0f2fe;">Username</label>
                        <input type="text" class="form-control border-0 py-2" style="border-radius: 10px; background-color: #bae6fd; color: #0c4a6e;" disabled>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold" style="color: #e0f2fe;">Password</label>
                        <input type="password" class="form-control border-0 py-2" style="border-radius: 10px; background-color: #bae6fd; color: #0c4a6e;" disabled>
                    </div>
                    <button type="button" class="btn w-50 d-block mx-auto py-2 fw-bold" style="border-radius: 10px; background-color: #94a3b8; color: white;" disabled>Daftar Akun</button>
                </form>
            </div>
        </div>
    </div>
</div>
