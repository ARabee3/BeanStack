<?php

/**
 * register.php
 * Registration page — fields match wireframe p.7:
 * Name, Email, Password, Confirm Password, Room No., Ext., Profile Picture
 */

$success = $success ?? false;
$errors = $errors ?? [];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register — Cafeteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
        }

        .auth-card {
            max-width: 520px;
            border-radius: 1rem;
            border: 1px solid #e9ecef;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            background: #d97706;
            border-radius: .75rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .step-pill {
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .04em;
        }

        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dee2e6;
        }

        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #e9ecef;
            border: 3px dashed #adb5bd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #adb5bd;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 .2rem rgba(217, 119, 6, .18);
        }

        .btn-brand {
            background: #d97706;
            border-color: #d97706;
            color: #fff;
            font-weight: 600;
        }

        .btn-brand:hover {
            background: #b45309;
            border-color: #b45309;
            color: #fff;
        }

        .strength-bar {
            height: 5px;
            border-radius: 99px;
            transition: width .3s, background .3s;
        }

        .divider-or {
            position: relative;
            text-align: center;
            color: #6c757d;
            font-size: .82rem;
        }

        .divider-or::before,
        .divider-or::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #dee2e6;
        }

        .divider-or::before {
            left: 0;
        }

        .divider-or::after {
            right: 0;
        }
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
                <a href="?page=login" class="btn btn-brand px-4">
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

                <form method="POST" action="?page=register" enctype="multipart/form-data" class="needs-validation" novalidate id="regForm">

                    <!-- ── SECTION 1: Personal Info ── -->
                    <p class="text-uppercase fw-semibold text-muted mb-3" style="font-size:.7rem;letter-spacing:.1em;">
                        <i class="bi bi-person me-1"></i>Personal Information
                    </p>

                    <!-- Profile picture upload (shown at top like wireframe p.7) -->
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div id="avatarWrap">
                            <div class="avatar-placeholder" id="avatarPlaceholder"><i class="bi bi-person"></i></div>
                            <img id="avatarPreview" class="avatar-preview d-none" src="" alt="Preview" />
                        </div>
                        <div>
                            <label class="form-label fw-semibold small mb-1">Profile Picture</label>
                            <input type="file" class="form-control form-control-sm <?= isset($errors['picture']) ? 'is-invalid' : '' ?>"
                                name="picture" id="picture" accept="image/*"
                                onchange="previewAvatar(this)" />
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
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required />
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
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
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
                                oninput="checkStrength(this.value)" required minlength="8" />
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
                                name="confirm" id="confirm" placeholder="Repeat password" required />
                            <button type="button" class="input-group-text bg-light border-start-0" onclick="togglePass('confirm','eyeIcon2')">
                                <i class="bi bi-eye" id="eyeIcon2"></i>
                            </button>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm'] ?? 'Passwords must match.') ?></div>
                        </div>
                    </div>

                    <hr class="my-4" />

                    <!-- ── SECTION 2: Work Details ── -->
                    <p class="text-uppercase fw-semibold text-muted mb-3" style="font-size:.7rem;letter-spacing:.1em;">
                        <i class="bi bi-building me-1"></i>Work Details
                    </p>

                    <!-- Location -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small" for="location">Location <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-geo-alt text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0 <?= isset($errors['location']) ? 'is-invalid' : '' ?>"
                                name="location" id="location" placeholder="e.g. Room 2010, Floor 2"
                                value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required />
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['location'] ?? 'Location is required.') ?></div>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input <?= isset($errors['terms']) ? 'is-invalid' : '' ?>" type="checkbox" id="terms" name="terms" <?= isset($_POST['terms']) ? 'checked' : '' ?> required />
                            <label class="form-check-label small" for="terms">
                                I agree to the <a href="#" class="text-warning fw-semibold text-decoration-none">Terms of Service</a>
                                and <a href="#" class="text-warning fw-semibold text-decoration-none">Privacy Policy</a>
                            </label>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['terms'] ?? 'You must accept the terms to continue.') ?></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brand w-100 py-2">
                        <i class="bi bi-person-check me-1"></i>Create Account
                    </button>
                </form>

                <div class="text-center mt-4 small text-muted">
                    Already have an account?
                    <a href="?page=login" class="text-warning fw-semibold text-decoration-none">Sign in</a>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- /container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePass(inputId, iconId) {
            const field = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!field || !icon) return;

            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        function previewAvatar(input) {
            const file = input.files && input.files[0] ? input.files[0] : null;
            const preview = document.getElementById('avatarPreview');
            const placeholder = document.getElementById('avatarPlaceholder');

            if (!file || !preview || !placeholder) return;

            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
            placeholder.classList.add('d-none');
        }

        function checkStrength(value) {
            const bar = document.getElementById('strengthBar');
            const label = document.getElementById('strengthLabel');
            if (!bar || !label) return;

            let score = 0;
            if (value.length >= 8) score++;
            if (/[A-Z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;

            const map = {
                0: {
                    width: '0%',
                    text: 'Enter a password',
                    color: '#dc3545'
                },
                1: {
                    width: '25%',
                    text: 'Weak',
                    color: '#dc3545'
                },
                2: {
                    width: '50%',
                    text: 'Fair',
                    color: '#fd7e14'
                },
                3: {
                    width: '75%',
                    text: 'Good',
                    color: '#ffc107'
                },
                4: {
                    width: '100%',
                    text: 'Strong',
                    color: '#198754'
                },
            };

            const state = map[score];
            bar.style.width = state.width;
            bar.style.background = state.color;
            label.textContent = state.text;
        }

        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>

</body>

</html>