<?php

/**
 * users/add_user.php — Wireframe p.7
 * Fields: Name | Email | Password | Confirm Password | Room No. | Ext. | Profile picture
 * Buttons: Save | Reset
 */

$pageTitle = $pageTitle ?? 'Add User';
$activeNav = $activeNav ?? 'users';
$editId = isset($editId) ? (int) $editId : (int) ($_GET['id'] ?? 0);
$success = $success ?? false;
$errors = $errors ?? [];
$prefill = $prefill ?? [];

include __DIR__ . '/../layouts/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item">
            <a href="?page=users" class="text-warning text-decoration-none">Users</a>
        </li>
        <li class="breadcrumb-item active"><?= $editId > 0 ? 'Edit User' : 'Add User' ?></li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-4 gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-person-<?= $editId > 0 ? 'gear' : 'plus' ?> me-2 text-warning"></i>
        <?= $editId > 0 ? 'Edit User' : 'Add User' ?>
    </h4>
</div>

<?php if ($success): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i>
        User <?= $editId > 0 ? 'updated' : 'added' ?> successfully!
        <a href="?page=users" class="ms-auto btn btn-sm btn-success">← Back to Users</a>
    </div>
<?php endif; ?>

<div class="page-card" style="max-width:560px;">
    <div class="page-card-header">
        <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.06em;">
            User Details
        </span>
    </div>
    <div class="p-4">
        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>

            <!-- Name -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">
                    Name <span class="text-danger">*</span>
                </label>
                <div class="col-sm-8">
                    <input type="text"
                        class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                        name="name" placeholder="Full name"
                        value="<?= htmlspecialchars($_POST['name'] ?? $prefill['name'] ?? '') ?>"
                        required />
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($errors['name'] ?? 'Name is required.') ?>
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">
                    Email <span class="text-danger">*</span>
                </label>
                <div class="col-sm-8">
                    <input type="email"
                        class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                        name="email" placeholder="user@company.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? $prefill['email'] ?? '') ?>"
                        required />
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($errors['email'] ?? 'Valid email required.') ?>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">
                    Password <?= !$editId ? '<span class="text-danger">*</span>' : '' ?>
                </label>
                <div class="col-sm-8">
                    <div class="input-group">
                        <input type="password"
                            class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                            name="password" id="addPass"
                            placeholder="<?= $editId ? 'Leave blank to keep current' : 'Min. 8 characters' ?>"
                            <?= !$editId ? 'required minlength="8"' : '' ?> />
                        <button type="button" class="input-group-text bg-light" onclick="togglePass('addPass','eye1')">
                            <i class="bi bi-eye" id="eye1"></i>
                        </button>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['password'] ?? 'Min. 8 characters required.') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">
                    Confirm Password <?= !$editId ? '<span class="text-danger">*</span>' : '' ?>
                </label>
                <div class="col-sm-8">
                    <div class="input-group">
                        <input type="password"
                            class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                            name="confirm" id="addConfirm"
                            placeholder="Repeat password"
                            <?= !$editId ? 'required' : '' ?> />
                        <button type="button" class="input-group-text bg-light" onclick="togglePass('addConfirm','eye2')">
                            <i class="bi bi-eye" id="eye2"></i>
                        </button>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['confirm'] ?? 'Passwords must match.') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room No. -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">
                    Room No. <span class="text-danger">*</span>
                </label>
                <div class="col-sm-8">
                    <input type="text"
                        class="form-control <?= isset($errors['room']) ? 'is-invalid' : '' ?>"
                        name="room" placeholder="e.g. 2010"
                        value="<?= htmlspecialchars($_POST['room'] ?? $prefill['room'] ?? '') ?>"
                        required />
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($errors['room'] ?? 'Room number required.') ?>
                    </div>
                </div>
            </div>

            <!-- Ext. -->
            <div class="row mb-3 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">Ext.</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="ext"
                        placeholder="e.g. 5605"
                        value="<?= htmlspecialchars($_POST['ext'] ?? $prefill['ext'] ?? '') ?>" />
                </div>
            </div>

            <!-- Profile picture -->
            <div class="row mb-4 align-items-start">
                <label class="col-sm-4 col-form-label fw-semibold">Profile picture</label>
                <div class="col-sm-8">
                    <div class="d-flex align-items-center gap-3">
                        <!-- Avatar preview circle -->
                        <div id="avatarBox"
                            class="d-flex align-items-center justify-content-center rounded-circle bg-light border overflow-hidden flex-shrink-0"
                            style="width:56px;height:56px;font-size:1.5rem;color:#adb5bd;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="flex-grow-1">
                            <input type="file"
                                class="form-control <?= isset($errors['picture']) ? 'is-invalid' : '' ?>"
                                name="picture" id="profilePic"
                                accept="image/*"
                                onchange="previewPic(this)" />
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['picture'] ?? '') ?>
                            </div>
                            <div class="form-text">JPG, PNG, WEBP — max 2 MB. Leave empty to keep current.</div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4" />

            <!-- Buttons: Save | Reset (wireframe p.7) -->
            <div class="d-flex gap-3 align-items-center">
                <button type="submit" class="btn btn-warning fw-bold px-4">
                    <i class="bi bi-save me-1"></i>Save
                </button>
                <button type="reset" class="btn btn-outline-secondary px-4" onclick="resetAvatar()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
                <a href="?page=users" class="btn btn-link text-muted text-decoration-none ms-auto">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<script>
    function togglePass(inputId, iconId) {
        const f = document.getElementById(inputId);
        const i = document.getElementById(iconId);
        if (f.type === 'password') {
            f.type = 'text';
            i.className = 'bi bi-eye-slash';
        } else {
            f.type = 'password';
            i.className = 'bi bi-eye';
        }
    }

    function previewPic(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const box = document.getElementById('avatarBox');
            box.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;"/>`;
        };
        reader.readAsDataURL(file);
    }

    function resetAvatar() {
        document.getElementById('avatarBox').innerHTML = '<i class="bi bi-person-fill"></i>';
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>