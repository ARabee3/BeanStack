<?php
/**
 * login.php  — Wireframe p.1
 */

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Login — Cafeteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        body { font-family:'Inter',sans-serif; background:#f3f4f6; }
        .auth-wrap { min-height:100vh; }
        .auth-card { max-width:440px; border-radius:1rem; border:1px solid #e9ecef; }
        .brand-icon { width:52px;height:52px;background:#d97706;border-radius:.75rem;font-size:1.5rem;display:flex;align-items:center;justify-content:center; }
        .role-tab { cursor:pointer;border-radius:.5rem;padding:.5rem 1rem;font-size:.85rem;font-weight:500;color:#6c757d;border:1.5px solid transparent;transition:all .15s; }
        .role-tab.active { border-color:#d97706;color:#d97706;background:#fff8ec; }
        .form-control:focus,.form-select:focus { border-color:#d97706;box-shadow:0 0 0 .2rem rgba(217,119,6,.18); }
        .btn-brand { background:#d97706;border-color:#d97706;color:#fff;font-weight:600; }
        .btn-brand:hover { background:#b45309;border-color:#b45309;color:#fff; }
    </style>
</head>
<body>
<div class="auth-wrap d-flex align-items-center justify-content-center p-3">
<div class="w-100" style="max-width:440px;">

    <!-- Brand -->
    <div class="text-center mb-4">
        <div class="brand-icon mx-auto mb-3 text-white"><i class="bi bi-cup-hot-fill"></i></div>
        <h1 class="fs-3 fw-bold mb-1">Cafeteria</h1>
        <p class="text-muted small">Sign in to your account</p>
    </div>

    <div class="auth-card bg-white shadow-sm p-4 p-md-5 mx-auto">

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Role selector -->
        <div class="d-flex gap-2 bg-light rounded-3 p-1 mb-4" id="roleSelector">
            <div class="role-tab active flex-fill text-center" id="tabUser" onclick="setRole('user')">
                <i class="bi bi-person me-1"></i>Employee
            </div>
            <div class="role-tab flex-fill text-center" id="tabAdmin" onclick="setRole('admin')">
                <i class="bi bi-shield-check me-1"></i>Admin
            </div>
        </div>
        <input type="hidden" name="role" id="roleInput" value="user"/>

        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="role" id="roleField" value="user"/>

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label fw-semibold small" for="email">Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control border-start-0 ps-0" name="email" id="email"
                           placeholder="you@company.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
                    <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-semibold small mb-0" for="password">Password</label>
                    <a href="forgot_password.php" class="small text-warning text-decoration-none">Forget Password?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0 ps-0"
                           name="password" id="password" placeholder="••••••••" required/>
                    <button type="button" class="input-group-text bg-light border-start-0"
                            onclick="togglePass('password','eyeI')">
                        <i class="bi bi-eye" id="eyeI"></i>
                    </button>
                    <div class="invalid-feedback">Password is required.</div>
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember"/>
                    <label class="form-check-label small text-muted" for="remember">Remember me</label>
                </div>
            </div>

            <button type="submit" class="btn btn-brand w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </button>
        </form>

        <div class="text-center mt-4 small text-muted">
            Don't have an account?
            <a href="register.php" class="text-warning fw-semibold text-decoration-none">Register</a>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setRole(r) {
    document.getElementById('roleField').value = r;
    document.getElementById('tabUser').classList.toggle('active',  r === 'user');
    document.getElementById('tabAdmin').classList.toggle('active', r === 'admin');
}
function togglePass(inputId, iconId) {
    const f = document.getElementById(inputId);
    const i = document.getElementById(iconId);
    if (f.type === 'password') { f.type = 'text';     i.className = 'bi bi-eye-slash'; }
    else                       { f.type = 'password'; i.className = 'bi bi-eye'; }
}
(function() {
    'use strict';
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
            form.classList.add('was-validated');
        });
    });
})();
</script>
</body>
</html>
