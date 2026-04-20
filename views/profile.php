<?php
/**
 * views/profile.php
 * Profile management for regular users.
 */

$user = $user ?? [];
$pageTitle = $pageTitle ?? 'My Profile';
$activeNav = $activeNav ?? 'profile';

include __DIR__ . '/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="page-card shadow-sm">
                <div class="page-card-header bg-white d-flex align-items-center gap-2">
                    <i class="bi bi-person-circle fs-4 text-warning"></i>
                    <h5 class="fw-bold mb-0">Manage Profile</h5>
                </div>
                <div class="p-4">
                    <!-- Display flash message here -->
                    <?php if (isset($_SESSION['flash'])): ?>
                        <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?> alert-dismissible fade show" role="alert">
                            <?= $_SESSION['flash']['msg'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash']); ?>
                    <?php endif; ?>

                    <form action="?page=update-profile" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <?php if (!empty($user['profile_pic'])): ?>
                                    <img src="<?= htmlspecialchars($user['profile_pic']) ?>" 
                                         id="profilePreview"
                                         class="rounded-circle border shadow-sm mb-3" 
                                         style="width:120px;height:120px;object-fit:cover;" />
                                <?php else: ?>
                                    <div id="profilePlaceholder" 
                                         class="bg-light rounded-circle border d-flex align-items-center justify-content-center text-muted mx-auto mb-3" 
                                         style="width:120px;height:120px;font-size:3rem;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <img id="profilePreview" 
                                         class="rounded-circle border shadow-sm mb-3 d-none" 
                                         style="width:120px;height:120px;object-fit:cover;" />
                                <?php endif; ?>
                                <label for="picture" class="btn btn-sm btn-dark rounded-circle position-absolute bottom-0 end-0 mb-3 me-1 shadow" title="Change Picture">
                                    <i class="bi bi-camera-fill"></i>
                                </label>
                                <input type="file" name="picture" id="picture" class="d-none" accept="image/*" onchange="previewProfilePic(this)">
                            </div>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($user['name'] ?? 'User') ?></h5>
                            <p class="text-muted small"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                        </div>

                        <hr class="my-4 opacity-50">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small" for="name">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" 
                                       name="name" id="name" 
                                       placeholder="Your Full Name"
                                       value="<?= htmlspecialchars($user['name'] ?? '') ?>" required />
                                <div class="invalid-feedback">Please enter your full name.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small" for="location">My Location <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-geo-alt text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" 
                                       name="location" id="location" 
                                       placeholder="e.g. Room 2010, Floor 2"
                                       value="<?= htmlspecialchars($user['location'] ?? '') ?>" required />
                                <div class="invalid-feedback">Please enter your location.</div>
                            </div>
                            <div class="form-text small text-muted">
                                This is where your orders will be delivered.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning fw-bold py-2 shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewProfilePic(input) {
    const file = input.files && input.files[0] ? input.files[0] : null;
    const preview = document.getElementById('profilePreview');
    const placeholder = document.getElementById('profilePlaceholder');

    if (!file || !preview) return;

    preview.src = URL.createObjectURL(file);
    preview.classList.remove('d-none');
    if (placeholder) placeholder.classList.add('d-none');
}

(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
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

<?php include __DIR__ . '/layouts/footer.php'; ?>
