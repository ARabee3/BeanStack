<?php

/**
 * views/products/all_products.php
 * Fully wired to ProductController::index()
 *
 * Variables injected by the controller:
 *   $products     – array of rows from DB
 *   $categories   – array for the category filter dropdown
 *   $totalPages   – int
 *   $currentPage  – int
 *   $pageTitle    – string
 *   $activeNav    – string
 *
 * Filter GET params: search, category, status, p (page number)
 */

$products   = $products   ?? [];
$categories = $categories ?? [];
$totalPages  = $totalPages  ?? 1;
$currentPage = $currentPage ?? 1;
$pageTitle   = $pageTitle   ?? 'All Products';
$activeNav   = $activeNav   ?? 'products';

// Keep current filter values for sticky inputs
$filterSearch = htmlspecialchars($_GET['search']   ?? '');
$filterCat    = htmlspecialchars($_GET['category'] ?? '');
$filterStatus = $_GET['status'] ?? '';

/**
 * Builds a URL that keeps all current GET params, but overrides
 * specific keys (used for pagination links).
 */
function buildUrl(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

include __DIR__ . '/../layouts/header.php';
?>

<!-- ── Flash message ─────────────────────────────────────────────────── -->
<?php if (!empty($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Page header ───────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-box-seam me-2 text-warning"></i>All Products
        </h4>
        <p class="text-muted small mb-0">Manage cafeteria menu items</p>
    </div>
    <a href="?page=add-product" class="btn btn-warning fw-semibold">
        <i class="bi bi-plus-lg me-1"></i>Add product
    </a>
</div>

<!-- ── Search & filter bar ───────────────────────────────────────────── -->
<div class="page-card mb-3">
    <div class="page-card-header">
        <form method="GET" action="" id="filterForm" class="d-flex gap-3 flex-wrap align-items-end">
            <input type="hidden" name="page" value="products">
            <input type="hidden" name="p"    value="1"><!-- reset to page 1 on filter change -->

            <div style="flex:1;min-width:180px;">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search"
                           placeholder="Product name…"
                           value="<?= $filterSearch ?>"
                           oninput="debounceSubmit()" />
                </div>
            </div>

            <div>
                <label class="form-label small fw-semibold mb-1">Category</label>
                <select class="form-select form-select-sm" name="category"
                        onchange="this.form.submit()" style="min-width:140px;">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>"
                            <?= $filterCat === $cat['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select class="form-select form-select-sm" name="status"
                        onchange="this.form.submit()" style="min-width:130px;">
                    <option value=""  <?= $filterStatus === ''  ? 'selected' : '' ?>>All</option>
                    <option value="1" <?= $filterStatus === '1' ? 'selected' : '' ?>>Available</option>
                    <option value="0" <?= $filterStatus === '0' ? 'selected' : '' ?>>Unavailable</option>
                </select>
            </div>

            <div class="align-self-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="?page=products" class="btn btn-sm btn-outline-secondary ms-1">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ── Products table ────────────────────────────────────────────────── -->
<div class="page-card">
    <div class="table-responsive-stack">
        <table class="table table-hover align-middle mb-0" id="productsTable">
            <thead class="table-dark d-none d-sm-table-header-group">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-end">Price</th>
                    <th class="text-center">Image</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No products found.
                            <?php if ($filterSearch || $filterCat || $filterStatus !== ''): ?>
                                <a href="?page=products" class="d-block mt-1 small">Clear filters</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $i => $p):
                        $rowNum = ($currentPage - 1) * 10 + $i + 1;
                    ?>
                    <tr id="row-<?= (int)$p['id'] ?>">
                        <td data-label="#" class="text-muted small"><?= $rowNum ?></td>
                        <td data-label="Product"><span class="fw-semibold"><?= htmlspecialchars($p['name']) ?></span></td>
                        <td data-label="Category">
                            <span class="badge bg-light text-dark border">
                                <?= htmlspecialchars($p['category'] ?? '—') ?>
                            </span>
                        </td>
                        <td data-label="Price" class="text-end fw-semibold">
                            <?= number_format((float)$p['price'], 2) ?> EGP
                        </td>
                        <td data-label="Image" class="text-center">
                            <?php if (!empty($p['image'])): ?>
                                <img src="../public/<?= htmlspecialchars($p['image']) ?>"
                                     alt="<?= htmlspecialchars($p['name']) ?>"
                                     class="rounded border object-fit-cover"
                                     style="width:44px;height:44px;" />
                            <?php else: ?>
                                <div class="d-inline-flex align-items-center justify-content-center
                                            bg-light border rounded text-muted"
                                     style="width:44px;height:44px;font-size:1.2rem;">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Actions" class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">

                                <!-- Toggle availability (AJAX) -->
                                <button class="btn btn-sm fw-semibold border-0 px-2 py-1
                                               <?= $p['is_available'] ? 'btn-success' : 'btn-danger' ?>"
                                        data-id="<?= (int)$p['id'] ?>"
                                        data-avail="<?= (int)$p['is_available'] ?>"
                                        onclick="toggleAvail(this)"
                                        style="min-width:108px;font-size:.75rem;">
                                    <i class="bi <?= $p['is_available'] ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                                    <?= $p['is_available'] ? 'Available' : 'Unavailable' ?>
                                </button>

                                <!-- Edit -->
                                <a href="?page=edit-product&id=<?= (int)$p['id'] ?>"
                                   class="btn btn-sm btn-outline-primary px-2">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>

                                <!-- Delete -->
                                <button class="btn btn-sm btn-outline-danger px-2"
                                        onclick="confirmDelete(<?= (int)$p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', this)">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Pagination ────────────────────────────────────────────────── -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center py-3 border-top">
        <nav aria-label="Products pagination">
            <ul class="pagination pagination-sm mb-0">

                <!-- First / Prev -->
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= buildUrl(['p' => 1]) ?>"
                       aria-label="First">«</a>
                </li>
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= buildUrl(['p' => $currentPage - 1]) ?>"
                       aria-label="Previous">‹</a>
                </li>

                <!-- Page numbers (window of 5) -->
                <?php
                $window = 2;
                $start  = max(1, $currentPage - $window);
                $end    = min($totalPages, $currentPage + $window);
                if ($start > 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif;
                for ($pg = $start; $pg <= $end; $pg++): ?>
                    <li class="page-item <?= $pg === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= buildUrl(['p' => $pg]) ?>">
                            <?= $pg ?>
                        </a>
                    </li>
                <?php endfor;
                if ($end < $totalPages): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>

                <!-- Next / Last -->
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= buildUrl(['p' => $currentPage + 1]) ?>"
                       aria-label="Next">›</a>
                </li>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= buildUrl(['p' => $totalPages]) ?>"
                       aria-label="Last">»</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Hidden delete form (POST) -->
<form id="deleteForm" method="POST" action="" style="display:none;">
    <input type="hidden" name="page"  value="delete-product">
</form>

<!-- Toast container -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<!-- ── Scripts ────────────────────────────────────────────────────────── -->
<script>
// ── Search debounce ──────────────────────────────────────────────────────
let _debTimer;
function debounceSubmit() {
    clearTimeout(_debTimer);
    _debTimer = setTimeout(() => document.getElementById('filterForm').submit(), 450);
}

// ── Toggle availability via AJAX ─────────────────────────────────────────
async function toggleAvail(btn) {
    const id = btn.dataset.id;
    btn.disabled = true;

    try {
        const res  = await fetch(`?page=toggle-product&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('Server error');
        const data = await res.json();

        const isAvail = data.is_available === 1;
        btn.dataset.avail = isAvail ? '1' : '0';
        btn.className = `btn btn-sm fw-semibold border-0 px-2 py-1 ${isAvail ? 'btn-success' : 'btn-danger'}`;
        btn.innerHTML = `<i class="bi ${isAvail ? 'bi-check-circle' : 'bi-x-circle'} me-1"></i>${isAvail ? 'Available' : 'Unavailable'}`;
        btn.style.minWidth = '108px';
        btn.style.fontSize = '.75rem';

        showToast(
            `<i class="bi bi-arrow-repeat me-1"></i>Availability updated.`,
            isAvail ? 'success' : 'warning'
        );
    } catch (e) {
        showToast('<i class="bi bi-exclamation-circle me-1"></i>Failed to update.', 'danger');
    } finally {
        btn.disabled = false;
    }
}

// ── Delete confirmation ───────────────────────────────────────────────────
function confirmDelete(id, name, btn) {
    if (!confirm(`Delete "${name}"?\nThis action cannot be undone.`)) return;

    // Submit a POST form to the delete route
    const form = document.getElementById('deleteForm');
    form.action = `?page=delete-product&id=${id}`;
    form.submit();
}

// ── Toast helper ──────────────────────────────────────────────────────────
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