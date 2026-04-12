<?php
/**
 * register.php
 * Registration page — fields match wireframe p.7:
 * Name, Email, Password, Confirm Password, Room No., Ext., Profile Picture
 */

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Register — Cafeteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; min-height: 100vh; }
        .auth-card { max-width: 520px; border-radius: 1rem; border: 1px solid #e9ecef; }
        .brand-icon { width: 52px; height: 52px; background: #d97706; border-radius: .75rem; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; }
        .step-pill { font-size: .72rem; font-weight: 600; letter-spacing: .04em; }
        .avatar-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #dee2e6; }
        .avatar-placeholder { width: 80px; height: 80px; border-radius: 50%; background: #e9ecef; border: 3px dashed #adb5bd; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; color: #adb5bd; }
        .form-control:focus, .form-select:focus { border-color: #d97706; box-shadow: 0 0 0 .2rem rgba(217,119,6,.18); }
        .btn-brand { background: #d97706; border-color: #d97706; color: #fff; font-weight: 600; }
        .btn-brand:hover { background: #b45309; border-color: #b45309; color: #fff; }
        .strength-bar { height: 5px; border-radius: 99px; transition: width .3s, background .3s; }
        .divider-or { position: relative; text-align: center; color: #6c757d; font-size: .82rem; }
        .divider-or::before, .divider-or::after { content:''; position:absolute; top:50%; width:42%; height:1px; background:#dee2e6; }
        .divider-or::before { left:0; } .divider-or::after { right:0; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">

<div class="w-100 px-3" style="max-width:540px;">

    <!-- Brand -->
    <div class="text-center mb-4">
        <div class="brand-icon mx-auto mb-3 text-white"><i class="bi bi-cup-hot-fill"></i></div>
        <h1 class="fs-4 fw-bold mb-1">Create your account</h1>
        <p class="text-muted small mb-0">Join the cafeteria ordering system</p>
    </div>

    <?php if ($success): ?>
    <!-- ── SUCCESS STATE ── -->
    <div class="auth-card bg-white shadow-sm p-5 text-center mx-auto">
        <div class="text-success mb-3" style="font-size:3rem;"><i class="bi bi-check-circle-fill"></i></div>
        <h2 class="fs-5 fw-bold mb-2">Account created!</h2>
        <p class="text-muted small mb-4">Your account is pending admin approval. You'll receive an email once activated.</p>
        <a href="login.php" class="btn btn-brand px-4">
            <i class="bi bi-arrow-right-circle me-1"></i>Go to Login
        </a>
    </div>

    <?php else: ?>
    <!-- ── FORM ── -->
    <div class="auth-card bg-white shadow-sm p-4 p-md-5 mx-auto">

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2 py-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
            <div>
                <?php foreach ($errors as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate id="regForm">

            <!-- ── SECTION 1: Personal Info ── -->
            <p class="text-uppercase fw-semibold text-muted mb-3" style="font-size:.7rem;letter-spacing:.1em;">
                <i class="bi bi-person me-1"></i>Personal Information
            </p>

            <!-- Profile picture upload (shown at top like wireframe p.7) -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <div id="avatarWrap">
                    <div class="avatar-placeholder" id="avatarPlaceholder"><i class="bi bi-person"></i></div>
                    <img id="avatarPreview" class="avatar-preview d-none" src="" alt="Preview"/>
                </div>
                <div>
                    <label class="form-label fw-semibold small mb-1">Profile Picture</label>
                    <input type="file" class="form-control form-control-sm <?= isset($errors['picture']) ? 'is-invalid' : '' ?>"
                           name="picture" id="picture" accept="image/*"
                           onchange="previewAvatar(this)"/>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['picture'] ?? '') ?></div>
                    <div class="text-muted" style="font-size:.72rem;">JPG, PNG, WEBP — max 2 MB</div>
                </div>
            </div>

            <!-- Name -->
            <div class="mb-3">
                <label class="form-label fw-semibold small" for="name">Full Name <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0 <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                           name="name" id="name" placeholder="e.g. Islam Askar"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required/>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['name'] ?? 'Full name is required.') ?></div>
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label fw-semibold small" for="email">Email Address <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control border-start-0 ps-0 <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           name="email" id="email" placeholder="you@company.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email'] ?? 'Enter a valid email.') ?></div>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-1">
                <label class="form-label fw-semibold small" for="password">Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0 ps-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           name="password" id="password" placeholder="Min. 8 characters"
                           oninput="checkStrength(this.value)" required minlength="8"/>
                    <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePass('password','eyeIcon1')">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['password'] ?? 'Min. 8 characters required.') ?></div>
                </div>
            </div>
            <!-- Strength meter -->
            <div class="mb-3 px-1">
                <div class="bg-light rounded-pill overflow-hidden" style="height:5px;">
                    <div class="strength-bar" id="strengthBar" style="width:0%;background:#dc3545;"></div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted" id="strengthLabel">Enter a password</small>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label class="form-label fw-semibold small" for="confirm">Confirm Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0 ps-0 <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                           name="confirm" id="confirm" placeholder="Repeat password" required/>
                    <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePass('confirm','eyeIcon2')">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm'] ?? 'Passwords must match.') ?></div>
                </div>
            </div>

            <hr class="my-4"/>

            <!-- ── SECTION 2: Work Details ── -->
            <p class="text-uppercase fw-semibold text-muted mb-3" style="font-size:.7rem;letter-spacing:.1em;">
                <i class="bi bi-building me-1"></i>Work Details
            </p>

            <!-- Room No. + Ext. side by side -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label class="form-label fw-semibold small" for="room">Room No. <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-door-closed text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0 <?= isset($errors['room']) ? 'is-invalid' : '' ?>"
                               name="room" id="room" placeholder="e.g. 2010"
                               value="<?= htmlspecialchars($_POST['room'] ?? '') ?>" required/>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['room'] ?? 'Required.') ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold small" for="ext">Extension (Ext.)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               name="ext" id="ext" placeholder="e.g. 5605"
                               value="<?= htmlspecialchars($_POST['ext'] ?? '') ?>"/>
                    </div>
                </div>
            </div>

            <!-- Terms -->
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="terms" required/>
                    <label class="form-check-label small" for="terms">
                        I agree to the <a href="#" class="text-warning fw-semibold text-decoration-none">Terms of Service</a>
                        and <a href="#" class="text-warning fw-semibold text-decoration-none">Privacy Policy</a>
                    </label>
                    <div class="invalid-feedback">You must accept the terms to continue.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-brand w-100 py-2">
                <i class="bi bi-person-check me-1"></i>Create Account
            </button>
        </form>

        <div class="text-center mt-4 small text-muted">
            Already have an account?
            <a href="login.php" class="text-warning fw-semibold text-decoration-none">Sign in</a>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
