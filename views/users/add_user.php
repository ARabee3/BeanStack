<?php

/**
 * views/users/add_user.php
 * Handles both ADD (?page=add-user) and EDIT (?page=edit-user&id=X).
 *
 * Variables injected by index.php:
 *   $user      – array|null   full DB row when editing, null when adding
 *   $pageTitle – string
 *   $activeNav – string
 *
 * Session keys consumed:
 *   $_SESSION['form_errors']  – string[]
 *   $_SESSION['form_old']     – repopulate fields after failed validation
 */

$user      = $user      ?? null;
$isEdit    = $user      !== null;
$editId    = $isEdit    ? (int) $user['id'] : 0;
$pageTitle = $pageTitle ?? ($isEdit ? 'Edit User' : 'Add User');
$activeNav = $activeNav ?? 'users';

// ── Recover validation state ─────────────────────────────────────────────
$formErrors = $_SESSION['form_errors'] ?? [];
$old        = $_SESSION['form_old']    ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

// ── Flash ────────────────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/**
 * Resolve field value: old POST → DB row → default
 */
function uFieldVal(string $key, $default = ''): string {
    global $old, $user;
    if (isset($old[$key]))  return htmlspecialchars((string) $old[$key]);
    if (isset($user[$key])) return htmlspecialchars((string) $user[$key]);
    return htmlspecialchars((string) $default);
}

include __DIR__ . '/../layouts/header.php';
?>

<!-- ── Breadcrumb ────────────────────────────────────────────────────────── -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item">
            <a href="?page=users" class="text-warning text-decoration-none">Users</a>
        </li>
        <li class="breadcrumb-item active"><?= $isEdit ? 'Edit User' : 'Add User' ?></li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-4 gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-person-<?= $isEdit ? 'gear' : 'plus' ?> me-2 text-warning"></i>
        <?= $isEdit ? 'Edit User' : 'Add User' ?>
    </h4>
</div>

<!-- ── Flash banner ──────────────────────────────────────────────────────── -->
<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Validation errors ──────────────────────────────────────────────────── -->
<?php if ($formErrors): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Please fix the following:</strong>
        <ul class="mb-0 mt-1 ps-3">
            <?php foreach ($formErrors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Form card ─────────────────────────────────────────────────────────── -->
<div class="page-card" style="max-width:580px;">
    <div class="page-card-header">
        <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.06em;">
            User Details
        </span>
    </div>
    <div class="p-4">

        <form method="POST"
              enctype="multipart/form-data"
              action="?page=<?= $isEdit ? "update-user&id=$editId" : 'store-user' ?>"
              class="needs-validation"
              novalidate
              id="userForm">

            <!-- ── Full name ─────────────────────────────────────────────── -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold" for="name">
                    Full Name <span class="text-danger">*</span>
                </label>
                <div class="col-sm-8">
                    <input type="text"
                           class="form-control <?= in_array('Full name is required.', $formErrors) ? 'is-invalid' : '' ?>"
                           name="name"
                           id="name"
                           placeholder="e.g. Ahmed Mostafa"
                           value="<?= uFieldVal('name') ?>"
                           required />
                    <div class="invalid-feedback">Full name is required.</div>
                </div>
            </div>

            <!-- ── Email ─────────────────────────────────────────────────── -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold" for="email">
                    Email <span class="text-danger">*</span>
                </label>
                <div class="col-sm-8">
                    <input type="email"
                           class="form-control <?= in_array('A valid email is required.', $formErrors) || in_array('That email address is already in use.', $formErrors) ? 'is-invalid' : '' ?>"
                           name="email"
                           id="email"
                           placeholder="user@company.com"
                           value="<?= uFieldVal('email') ?>"
                           required />
                    <div class="invalid-feedback">Enter a valid, unique email address.</div>
                </div>
            </div>

            <!-- ── Password ──────────────────────────────────────────────── -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold" for="password">
                    Password
                    <?php if (!$isEdit): ?><span class="text-danger">*</span><?php endif; ?>
                </label>
                <div class="col-sm-8">
                    <div class="input-group">
                        <input type="password"
                               class="form-control <?= in_array('Password must be at least 8 characters.', $formErrors) ? 'is-invalid' : '' ?>"
                               name="password"
                               id="password"
                               placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'Min. 8 characters' ?>"
                               <?= !$isEdit ? 'required minlength="8"' : 'minlength="8"' ?> />
                        <button type="button" class="input-group-text bg-light border"
                                onclick="togglePass('password','eye1')" tabindex="-1">
                            <i class="bi bi-eye" id="eye1"></i>
                        </button>
                        <div class="invalid-feedback">Minimum 8 characters required.</div>
                    </div>
                    <?php if ($isEdit): ?>
                        <div class="form-text">Leave blank to keep the current password.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Confirm password ───────────────────────────────────────── -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold" for="confirm">
                    Confirm Password
                    <?php if (!$isEdit): ?><span class="text-danger">*</span><?php endif; ?>
                </label>
                <div class="col-sm-8">
                    <div class="input-group">
                        <input type="password"
                               class="form-control <?= in_array('Passwords do not match.', $formErrors) ? 'is-invalid' : '' ?>"
                               name="confirm"
                               id="confirm"
                               placeholder="Repeat password"
                               <?= !$isEdit ? 'required' : '' ?> />
                        <button type="button" class="input-group-text bg-light border"
                                onclick="togglePass('confirm','eye2')" tabindex="-1">
                            <i class="bi bi-eye" id="eye2"></i>
                        </button>
                        <div class="invalid-feedback">Passwords must match.</div>
                    </div>
                </div>
            </div>

            <!-- ── Location ──────────────────────────────────────────────── -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold" for="location">
                    Location <span class="text-danger">*</span>
                </label>
                <div class="col-sm-8">
                    <input type="text"
                           class="form-control <?= in_array('Location is required.', $formErrors) ? 'is-invalid' : '' ?>"
                           name="location"
                           id="location"
                           placeholder="e.g. Building A, Floor 3"
                           value="<?= uFieldVal('location') ?>"
                           required />
                    <div class="invalid-feedback">Location is required.</div>
                    <div class="form-text">Building, floor, room or any address details.</div>
                </div>
            </div>

            <!-- ── Role ──────────────────────────────────────────────────── -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">Role</label>
                <div class="col-sm-8 pt-2">
                    <?php
                    $selectedRole = $old['role'] ?? $user['role'] ?? 'user';
                    ?>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role"
                                   id="roleUser" value="user"
                                   <?= $selectedRole === 'user' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="roleUser">
                                <i class="bi bi-person me-1"></i>User
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role"
                                   id="roleAdmin" value="admin"
                                   <?= $selectedRole === 'admin' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="roleAdmin">
                                <i class="bi bi-shield-fill me-1 text-warning"></i>Admin
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Active status (edit only) ─────────────────────────────── -->
            <?php if ($isEdit): ?>
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">Status</label>
                <div class="col-sm-8 pt-2">
                    <?php $activeChecked = isset($old['isActive']) ? (bool)$old['isActive'] : (bool)($user['isActive'] ?? true); ?>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="isActive" id="isActive"
                               <?= $activeChecked ? 'checked' : '' ?> />
                        <label class="form-check-label small" for="isActive" id="activeLabel">
                            <?= $activeChecked ? 'Active' : 'Inactive' ?>
                        </label>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Profile picture ───────────────────────────────────────── -->
            <div class="row mb-4 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">Profile Picture</label>
                <div class="col-sm-8">
                    <div class="d-flex align-items-center gap-3">

                        <!-- Avatar preview -->
                        <div id="avatarBox"
                             class="d-flex align-items-center justify-content-center
                                    rounded-circle bg-light border overflow-hidden flex-shrink-0"
                             style="width:58px;height:58px;">
                            <?php if ($isEdit && !empty($user['profile_pic'])): ?>
                                <img src="../public/<?= htmlspecialchars($user['profile_pic']) ?>"
                                     id="existingAvatar"
                                     style="width:100%;height:100%;object-fit:cover;" />
                            <?php else: ?>
                                <i class="bi bi-person-fill" style="font-size:1.6rem;color:#adb5bd;"></i>
                            <?php endif; ?>
                        </div>

                        <div class="flex-grow-1">
                            <!-- Field name matches controller: handleImageUpload('profile_pic') -->
                            <input type="file"
                                   class="form-control"
                                   name="profile_pic"
                                   id="profile_pic"
                                   accept="image/jpeg,image/png,image/webp,image/gif"
                                   onchange="previewPic(this)" />
                            <div class="form-text">
                                JPG, PNG, WEBP, GIF — max 2 MB.
                                <?= $isEdit ? 'Leave blank to keep current.' : '' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4" />

            <!-- ── Buttons ───────────────────────────────────────────────── -->
            <div class="d-flex gap-3 align-items-center flex-wrap">
                <button type="submit" class="btn btn-warning fw-bold px-4">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Update' : 'Save' ?>
                </button>
                <button type="reset" class="btn btn-outline-secondary px-4" onclick="onReset()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
                <a href="?page=users" class="btn btn-link text-muted text-decoration-none ms-auto">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<!-- Toast container -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
// ── Show / hide password ─────────────────────────────────────────────────────
function togglePass(inputId, iconId) {
    const f = document.getElementById(inputId);
    const i = document.getElementById(iconId);
    f.type  = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

// ── Profile picture preview ──────────────────────────────────────────────────
function previewPic(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('avatarBox').innerHTML =
            `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;" />`;
    };
    reader.readAsDataURL(file);
}

// ── Reset — restore original avatar ─────────────────────────────────────────
function onReset() {
    <?php if ($isEdit && !empty($user['profile_pic'])): ?>
    document.getElementById('avatarBox').innerHTML =
        `<img src="/<?= htmlspecialchars($user['profile_pic']) ?>"
              style="width:100%;height:100%;object-fit:cover;" />`;
    <?php else: ?>
    document.getElementById('avatarBox').innerHTML =
        '<i class="bi bi-person-fill" style="font-size:1.6rem;color:#adb5bd;"></i>';
    <?php endif; ?>
}

// ── Active label sync (edit mode only) ──────────────────────────────────────
const activeToggle = document.getElementById('isActive');
if (activeToggle) {
    activeToggle.addEventListener('change', function () {
        document.getElementById('activeLabel').textContent =
            this.checked ? 'Active' : 'Inactive';
    });
}

// ── Client-side: confirm passwords match before submit ───────────────────────
document.getElementById('userForm').addEventListener('submit', function (e) {
    const pass    = document.getElementById('password').value;
    const confirm = document.getElementById('confirm').value;

    // Only check if a password was entered (edit mode allows blank)
    if (pass && pass !== confirm) {
        e.preventDefault();
        document.getElementById('confirm').classList.add('is-invalid');
        this.classList.add('was-validated');
        return;
    }

    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    }
    this.classList.add('was-validated');
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>