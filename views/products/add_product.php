<?php

/**
 * views/products/add_product.php
 *
 * Handles both ADD (?page=add-product) and EDIT (?page=edit-product&id=X).
 *
 * Variables injected by index.php:
 *   $product    – array|null   full DB row when editing, null when adding
 *   $categories – array        all category rows [id, name]  (set below if not passed)
 *   $pageTitle  – string
 *   $activeNav  – string
 *
 * Session keys consumed here (set by the controller on validation failure):
 *   $_SESSION['form_errors']  – string[]
 *   $_SESSION['form_old']     – $_POST clone (field values to re-fill)
 */

// ── Resolve edit / add mode ───────────────────────────────────────────────
$product   = $product ?? null;
$isEdit    = $product !== null;
$editId    = $isEdit ? (int) $product['id'] : 0;

$pageTitle = $pageTitle ?? ($isEdit ? 'Edit Product' : 'Add Product');
$activeNav = $activeNav ?? 'products';

// ── Categories (controller should pass these, but fall back to a DB query) ─
if (empty($categories)) {
    require_once __DIR__ . '/../../config/Database.php';
    $categories = Database::getInstance()
        ->getConnection()
        ->query("SELECT id, name FROM categories ORDER BY name")
        ->fetchAll(PDO::FETCH_ASSOC);
}

// ── Recover validation state from session ────────────────────────────────
$formErrors = $_SESSION['form_errors'] ?? [];
$old        = $_SESSION['form_old']    ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

// ── Flash message (set by controller after redirect) ─────────────────────
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── Helper: resolve a field value (old POST > product DB row > default) ──
function fieldVal(string $key, $default = ''): string {
    global $old, $product;
    if (isset($old[$key]))     return htmlspecialchars((string) $old[$key]);
    if (isset($product[$key])) return htmlspecialchars((string) $product[$key]);
    return htmlspecialchars((string) $default);
}

include __DIR__ . '/../layouts/header.php';
?>

<!-- ── Breadcrumb ────────────────────────────────────────────────────────── -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item">
            <a href="?page=products" class="text-warning text-decoration-none">Products</a>
        </li>
        <li class="breadcrumb-item active"><?= $isEdit ? 'Edit Product' : 'Add Product' ?></li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-4 gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-square' ?> me-2 text-warning"></i>
        <?= $isEdit ? 'Edit Product' : 'Add Product' ?>
    </h4>
</div>

<!-- ── Flash banner (success / danger from controller) ───────────────────── -->
<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Validation error summary ──────────────────────────────────────────── -->
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
<div class="page-card" style="max-width:620px;">
    <div class="page-card-header">
        <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.06em;">
            Product Information
        </span>
    </div>
    <div class="p-4">

        <!--
            ADD  → POST to ?page=store-product
            EDIT → POST to ?page=update-product&id=X
        -->
        <form method="POST"
              enctype="multipart/form-data"
              action="?page=<?= $isEdit ? "update-product&id=$editId" : 'store-product' ?>"
              class="needs-validation"
              novalidate
              id="productForm">

            <!-- ── Product name ─────────────────────────────────────────── -->
            <div class="mb-4">
                <label class="form-label fw-semibold" for="name">
                    Product name <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control <?= in_array('Product name is required.', $formErrors) ? 'is-invalid' : '' ?>"
                       name="name"
                       id="name"
                       placeholder="e.g. Cappuccino"
                       value="<?= fieldVal('name') ?>"
                       required />
                <div class="invalid-feedback">Product name is required.</div>
            </div>

            <!-- ── Price ────────────────────────────────────────────────── -->
            <div class="mb-4">
                <label class="form-label fw-semibold" for="price">
                    Price <span class="text-danger">*</span>
                </label>
                <div class="input-group" style="max-width:220px;">
                    <input type="number"
                           class="form-control <?= in_array('A valid price is required.', $formErrors) ? 'is-invalid' : '' ?>"
                           name="price"
                           id="price"
                           placeholder="0.00"
                           step="0.50"
                           min="0"
                           value="<?= fieldVal('price') ?>"
                           required />
                    <span class="input-group-text fw-semibold">EGP</span>
                    <div class="invalid-feedback">A valid price is required.</div>
                </div>
                <div class="form-text">Step: 0.50 EGP</div>
            </div>

            <!-- ── Category ─────────────────────────────────────────────── -->
            <div class="mb-4">
                <label class="form-label fw-semibold" for="category_id">
                    Category <span class="text-danger">*</span>
                </label>
                <div class="d-flex align-items-center gap-3 flex-wrap">

                    <?php
                    // Resolve which category is currently selected
                    $selectedCatId = (int) ($old['category_id'] ?? $product['category_id'] ?? 0);
                    ?>

                    <select class="form-select <?= in_array('Please select a category.', $formErrors) ? 'is-invalid' : '' ?>"
                            name="category_id"
                            id="category_id"
                            style="max-width:280px;"
                            required>
                        <option value="">— Select category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"
                                <?= $selectedCatId === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <a href="#"
                       class="text-warning fw-semibold text-decoration-none small"
                       data-bs-toggle="modal"
                       data-bs-target="#addCatModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Category
                    </a>

                    <div class="invalid-feedback">Please select a category.</div>
                </div>
            </div>

            <!-- ── Availability ──────────────────────────────────────────── -->
            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Availability</label>
                <?php
                // Determine checked state:
                //   old POST (checkbox present = 1, absent = 0)  → new submit
                //   product['is_available']                       → edit prefill
                //   default true                                  → new form
                if ($old) {
                    $isAvailable = isset($old['is_available']);
                } elseif ($isEdit) {
                    $isAvailable = (bool) $product['is_available'];
                } else {
                    $isAvailable = true; // default: available
                }
                ?>
                <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           role="switch"
                           name="is_available"
                           id="is_available"
                           <?= $isAvailable ? 'checked' : '' ?> />
                    <label class="form-check-label small" for="is_available" id="availLabel">
                        <?= $isAvailable ? 'Available for order' : 'Not available' ?>
                    </label>
                </div>
            </div>

            <!-- ── Product picture ───────────────────────────────────────── -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Product Picture</label>
                <div class="d-flex align-items-center gap-3 flex-wrap">

                    <!-- Preview box: show existing image when editing -->
                    <div id="imgPreviewBox"
                         class="d-flex align-items-center justify-content-center
                                bg-light border rounded overflow-hidden"
                         style="width:80px;height:80px;flex-shrink:0;">
                        <?php if ($isEdit && !empty($product['image'])): ?>
                            <img src="../public/<?= htmlspecialchars($product['image']) ?>"
                                 id="existingImg"
                                 style="width:100%;height:100%;object-fit:cover;" />
                        <?php else: ?>
                            <span style="font-size:2rem;">🖼️</span>
                        <?php endif; ?>
                    </div>

                    <div class="flex-grow-1" style="max-width:300px;">
                        <!-- 'image' matches controller's handleImageUpload('image') -->
                        <input type="file"
                               class="form-control"
                               name="image"
                               id="image"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewImg(this)" />
                        <div class="form-text">
                            JPG, PNG, WEBP, GIF — max 2 MB
                            <?= ($isEdit && !empty($product['image'])) ? ' · Leave blank to keep current image.' : '' ?>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4" />

            <!-- ── Action buttons ────────────────────────────────────────── -->
            <div class="d-flex gap-3 align-items-center flex-wrap">
                <button type="submit" class="btn btn-warning fw-bold px-4">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Update' : 'Save' ?>
                </button>
                <button type="reset" class="btn btn-outline-secondary px-4" onclick="onReset()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
                <a href="?page=products" class="btn btn-link text-muted text-decoration-none ms-auto">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<!-- ── Add Category Modal ─────────────────────────────────────────────────── -->
<!--
    Submits via fetch() to ?page=add-category (a lightweight JSON endpoint
    you can add to index.php).  On success it injects the new <option> into
    the category dropdown and selects it — no page reload needed.
-->
<div class="modal fade" id="addCatModal" tabindex="-1" aria-labelledby="addCatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold" id="addCatModalLabel">Add New Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <input type="text"
                       class="form-control"
                       id="newCatName"
                       placeholder="Category name"
                       maxlength="100" />
                <div class="text-danger small mt-1 d-none" id="catModalErr"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button"
                        class="btn btn-warning fw-semibold btn-sm px-3"
                        id="saveCatBtn"
                        onclick="saveCategory()">
                    <i class="bi bi-plus me-1"></i>Add
                </button>
                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<!-- ── Scripts ────────────────────────────────────────────────────────────── -->
<script>
// ── Availability label sync ─────────────────────────────────────────────────
document.getElementById('is_available').addEventListener('change', function () {
    document.getElementById('availLabel').textContent =
        this.checked ? 'Available for order' : 'Not available';
});

// ── Image preview ───────────────────────────────────────────────────────────
function previewImg(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('imgPreviewBox').innerHTML =
            `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;" />`;
    };
    reader.readAsDataURL(file);
}

// ── Reset — restore original preview ────────────────────────────────────────
function onReset() {
    <?php if ($isEdit && !empty($product['image'])): ?>
    document.getElementById('imgPreviewBox').innerHTML =
        `<img src="/<?= htmlspecialchars($product['image']) ?>"
              style="width:100%;height:100%;object-fit:cover;" />`;
    <?php else: ?>
    document.getElementById('imgPreviewBox').innerHTML = '<span style="font-size:2rem;">🖼️</span>';
    <?php endif; ?>
    document.getElementById('availLabel').textContent =
        document.getElementById('is_available').checked ? 'Available for order' : 'Not available';
}

// ── Save new category via AJAX ───────────────────────────────────────────────
async function saveCategory() {
    const nameInput = document.getElementById('newCatName');
    const errDiv    = document.getElementById('catModalErr');
    const btn       = document.getElementById('saveCatBtn');
    const name      = nameInput.value.trim();

    errDiv.classList.add('d-none');
    errDiv.textContent = '';

    if (!name) {
        errDiv.textContent = 'Category name is required.';
        errDiv.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

    try {
        const fd = new FormData();
        fd.append('name', name);

        const res  = await fetch('?page=add-category', { method: 'POST', body: fd });
        const data = await res.json();

        if (!res.ok || data.error) {
            errDiv.textContent = data.error ?? 'Failed to save category.';
            errDiv.classList.remove('d-none');
            return;
        }

        // Inject new <option> into the dropdown and select it
        const sel = document.getElementById('category_id');
        const opt = new Option(data.name, data.id, true, true);
        sel.add(opt);

        nameInput.value = '';
        bootstrap.Modal.getInstance(document.getElementById('addCatModal')).hide();
        showToast(`<i class="bi bi-check me-1"></i>Category "${data.name}" added.`);

    } catch (e) {
        errDiv.textContent = 'Network error. Please try again.';
        errDiv.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus me-1"></i>Add';
    }
}

// Allow Enter key inside the modal input
document.getElementById('newCatName').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); saveCategory(); }
});

// ── Bootstrap client-side validation ────────────────────────────────────────
document.getElementById('productForm').addEventListener('submit', function (e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    }
    this.classList.add('was-validated');
});

// ── Toast helper ────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const wrap = document.getElementById('toastWrap');
    const id   = 'toast_' + Date.now();
    const bg   = { success: 'bg-success', warning: 'bg-warning text-dark', danger: 'bg-danger' };
    wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast text-white ${bg[type] ?? bg.success} border-0 shadow-sm" role="alert">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
            </div>
        </div>`);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 3000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>