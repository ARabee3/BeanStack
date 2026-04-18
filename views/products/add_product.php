<?php

/**
 * products/add_product.php — Wireframe p.8
 * Fields: Product name | Price (spinner) | Category (dropdown + Add Category link) | Product picture
 * Buttons: Save | Reset
 */

$pageTitle = $pageTitle ?? 'Add Product';
$activeNav = $activeNav ?? 'products';
$editId = isset($editId) ? (int) $editId : (int) ($_GET['id'] ?? 0);
$success = $success ?? false;
$errors = $errors ?? [];
$prefill = $prefill ?? [];

include __DIR__ . '/../layouts/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="?page=products" class="text-warning text-decoration-none">Products</a></li>
        <li class="breadcrumb-item active"><?= $editId > 0 ? 'Edit Product' : 'Add Product' ?></li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-4 gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-<?= $editId > 0 ? 'pencil-square' : 'plus-square' ?> me-2 text-warning"></i>
        <?= $editId > 0 ? 'Edit Product' : 'Add Product' ?>
    </h4>
</div>

<?php if ($success): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i>
        Product <?= $editId > 0 ? 'updated' : 'added' ?> successfully!
        <a href="?page=products" class="ms-auto btn btn-sm btn-success">← Back to Products</a>
    </div>
<?php endif; ?>

<div class="page-card" style="max-width:600px;">
    <div class="page-card-header">
        <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.06em;">
            Product Information
        </span>
    </div>
    <div class="p-4">
        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate id="productForm">

            <!-- Product name -->
            <div class="mb-4">
                <label class="form-label fw-semibold" for="product">
                    Product <span class="text-danger">*</span>
                </label>
                <input type="text"
                    class="form-control <?= isset($errors['product']) ? 'is-invalid' : '' ?>"
                    name="product" id="product"
                    placeholder="e.g. Tea"
                    value="<?= htmlspecialchars($_POST['product'] ?? $prefill['name'] ?? '') ?>"
                    required />
                <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['product'] ?? 'Product name is required.') ?>
                </div>
            </div>

            <!-- Price -->
            <div class="mb-4">
                <label class="form-label fw-semibold" for="price">
                    Price <span class="text-danger">*</span>
                </label>
                <div class="input-group" style="max-width:200px;">
                    <input type="number"
                        class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                        name="price" id="price"
                        placeholder="0.00" step="0.50" min="0.50"
                        value="<?= htmlspecialchars($_POST['price'] ?? $prefill['price'] ?? '') ?>"
                        required />
                    <span class="input-group-text fw-semibold">EGP</span>
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($errors['price'] ?? 'Valid price required.') ?>
                    </div>
                </div>
                <div class="form-text">Use the up/down arrows or type a value (step: 0.50 EGP)</div>
            </div>

            <!-- Category + Add Category link (wireframe p.8) -->
            <div class="mb-4">
                <label class="form-label fw-semibold" for="category">
                    Category <span class="text-danger">*</span>
                </label>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <select class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>"
                        name="category" id="category"
                        style="max-width:280px;" required>
                        <option value="">— Select category —</option>
                        <option <?= ($_POST['category'] ?? $prefill['category'] ?? '') === 'Hot Drinks' ? 'selected' : '' ?>>Hot Drinks</option>
                        <option <?= ($_POST['category'] ?? $prefill['category'] ?? '') === 'Cold Drinks' ? 'selected' : '' ?>>Cold Drinks</option>
                        <option <?= ($_POST['category'] ?? $prefill['category'] ?? '') === 'Snacks' ? 'selected' : '' ?>>Snacks</option>
                        <option <?= ($_POST['category'] ?? $prefill['category'] ?? '') === 'Food' ? 'selected' : '' ?>>Food</option>
                    </select>
                    <a href="#" class="text-warning fw-semibold text-decoration-none small"
                        data-bs-toggle="modal" data-bs-target="#addCatModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Category
                    </a>
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($errors['category'] ?? 'Please select a category.') ?>
                    </div>
                </div>
            </div>

            <!-- Availability toggle -->
            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Availability</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                        name="available" id="available" checked />
                    <label class="form-check-label small" for="available" id="availLabel">Available for order</label>
                </div>
            </div>

            <!-- Product picture -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Product Picture</label>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <!-- Preview box -->
                    <div id="imgPreviewBox"
                        class="d-flex align-items-center justify-content-center bg-light border rounded"
                        style="width:80px;height:80px;font-size:2.5rem;overflow:hidden;flex-shrink:0;">
                        🖼️
                    </div>
                    <div class="flex-grow-1" style="max-width:300px;">
                        <input type="file"
                            class="form-control <?= isset($errors['picture']) ? 'is-invalid' : '' ?>"
                            name="picture" id="picture"
                            accept="image/jpeg,image/png,image/webp"
                            onchange="previewImg(this)" />
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['picture'] ?? '') ?>
                        </div>
                        <div class="form-text">JPG, PNG, WEBP — max 5 MB</div>
                    </div>
                </div>
            </div>

            <hr class="my-4" />

            <!-- Buttons: Save | Reset (wireframe p.8) -->
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-warning fw-bold px-4">
                    <i class="bi bi-save me-1"></i>Save
                </button>
                <button type="reset" class="btn btn-outline-secondary px-4" onclick="resetForm()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
                <a href="?page=products" class="btn btn-link text-muted text-decoration-none ms-auto">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Add New Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <input type="text" class="form-control" id="newCatName" placeholder="Category name" />
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-warning fw-semibold btn-sm px-3" onclick="addCategory()">
                    <i class="bi bi-plus me-1"></i>Add
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
    document.getElementById('available').addEventListener('change', function() {
        document.getElementById('availLabel').textContent =
            this.checked ? 'Available for order' : 'Not available';
    });

    function previewImg(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const box = document.getElementById('imgPreviewBox');
            box.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;"/>`;
        };
        reader.readAsDataURL(file);
    }

    function resetForm() {
        document.getElementById('imgPreviewBox').innerHTML = '🖼️';
        document.getElementById('availLabel').textContent = 'Available for order';
    }

    function addCategory() {
        const name = document.getElementById('newCatName').value.trim();
        if (!name) return;
        const sel = document.getElementById('category');
        const opt = new Option(name, name, true, true);
        sel.add(opt);
        document.getElementById('newCatName').value = '';
        bootstrap.Modal.getInstance(document.getElementById('addCatModal')).hide();
        showToast('<i class="bi bi-check me-1"></i>Category "' + name + '" added.');
    }

    function showToast(msg, type = 'success') {
        const wrap = document.getElementById('toastWrap');
        const id = 'toast_' + Date.now();
        wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast text-white bg-success border-0 shadow-sm" role="alert">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`);
        const el = document.getElementById(id);
        new bootstrap.Toast(el, {
            delay: 3000
        }).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>