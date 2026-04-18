<?php

/**
 * products/all_products.php — Wireframe p.5
 * Columns: Product | Price | Image | Action (available/unavailable + edit + delete)
 * "Add product" link top-right, pagination bottom
 */

$products = $products ?? [];

include __DIR__ . '/../layouts/header.php';
?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-warning"></i>All Products</h4>
        <p class="text-muted small mb-0">Manage cafeteria menu items</p>
    </div>
    <a href="add_product.php" class="btn btn-warning fw-semibold">
        <i class="bi bi-plus-lg me-1"></i>Add product
    </a>
</div>

<!-- Search & filter bar -->
<div class="page-card mb-3">
    <div class="page-card-header">
        <div class="d-flex gap-3 flex-wrap align-items-end">
            <div style="flex:1;min-width:180px;">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" placeholder="Product name…"
                        id="searchProd" oninput="filterTable()" />
                </div>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Category</label>
                <select class="form-select form-select-sm" id="catFilter" onchange="filterTable()" style="min-width:140px;">
                    <option value="">All</option>
                    <option>Hot Drinks</option>
                    <option>Cold Drinks</option>
                    <option>Snacks</option>
                    <option>Food</option>
                </select>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select class="form-select form-select-sm" id="statusFilter" onchange="filterTable()" style="min-width:130px;">
                    <option value="">All</option>
                    <option value="1">Available</option>
                    <option value="0">Unavailable</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Products table -->
<div class="page-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="productsTable">
            <thead class="table-dark">
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-end">Price</th>
                    <th class="text-center">Image</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr data-name="<?= strtolower($p['name']) ?>"
                        data-cat="<?= strtolower($p['cat']) ?>"
                        data-avail="<?= $p['available'] ? '1' : '0' ?>">
                        <td>
                            <span class="fw-semibold"><?= htmlspecialchars($p['name']) ?></span>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['cat']) ?></span></td>
                        <td class="text-end fw-semibold"><?= $p['price'] ?> EGP</td>
                        <td class="text-center">
                            <!-- Placeholder image box (wireframe shows crossed-box) -->
                            <div class="d-inline-flex align-items-center justify-content-center bg-light border rounded"
                                style="width:44px;height:44px;font-size:1.4rem;"><?= $p['emoji'] ?></div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <!-- Toggle availability -->
                                <button class="btn btn-sm fw-semibold border-0 px-2 py-1
                                   <?= $p['available'] ? 'btn-success' : 'btn-danger' ?>"
                                    onclick="toggleAvail(this)"
                                    style="min-width:100px;font-size:.75rem;">
                                    <i class="bi <?= $p['available'] ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                                    <?= $p['available'] ? 'available' : 'unavailable' ?>
                                </button>
                                <a href="add_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary px-2">
                                    <i class="bi bi-pencil"></i> edit
                                </a>
                                <button class="btn btn-sm btn-outline-danger px-2"
                                    onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>', this)">
                                    <i class="bi bi-trash"></i> delete
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center py-3 border-top">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item"><a class="page-link" href="#">«</a></li>
                <li class="page-item"><a class="page-link" href="#">‹</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item disabled"><a class="page-link" href="#">…</a></li>
                <li class="page-item"><a class="page-link" href="#">›</a></li>
                <li class="page-item"><a class="page-link" href="#">»</a></li>
            </ul>
        </nav>
    </div>
</div>

<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
    function filterTable() {
        const q = document.getElementById('searchProd').value.toLowerCase();
        const cat = document.getElementById('catFilter').value.toLowerCase();
        const sta = document.getElementById('statusFilter').value;
        document.querySelectorAll('#productsTable tbody tr').forEach(row => {
            const nameOk = !q || row.dataset.name.includes(q);
            const catOk = !cat || row.dataset.cat.includes(cat);
            const statOk = sta === '' || row.dataset.avail === sta;
            row.style.display = (nameOk && catOk && statOk) ? '' : 'none';
        });
    }

    function toggleAvail(btn) {
        const row = btn.closest('tr');
        const isAvail = btn.classList.contains('btn-success');
        btn.classList.toggle('btn-success', !isAvail);
        btn.classList.toggle('btn-danger', isAvail);
        btn.innerHTML = isAvail ?
            '<i class="bi bi-x-circle me-1"></i>unavailable' :
            '<i class="bi bi-check-circle me-1"></i>available';
        row.dataset.avail = isAvail ? '0' : '1';
        showToast('Status updated.', isAvail ? 'warning' : 'success');
    }

    function deleteProduct(id, name, btn) {
        if (!confirm('Delete "' + name + '"?')) return;
        const row = btn.closest('tr');
        row.style.opacity = '0';
        row.style.transition = 'opacity .3s';
        setTimeout(() => row.remove(), 300);
        showToast('<i class="bi bi-trash me-1"></i>' + name + ' deleted.');
    }

    function showToast(msg, type = 'success') {
        const wrap = document.getElementById('toastWrap');
        const id = 'toast_' + Date.now();
        const bg = {
            success: 'bg-success',
            warning: 'bg-warning text-dark',
            danger: 'bg-danger'
        };
        wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast text-white ${bg[type]??bg.success} border-0 shadow-sm" role="alert">
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