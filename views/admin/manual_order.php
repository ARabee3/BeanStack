<?php

/**
 * admin/manual_order.php — Wireframe p.3
 * Same as home.php but adds "Add to user" ComboBox above the product grid
 */

$pageTitle = $pageTitle ?? 'Manual Order';
$activeNav = $activeNav ?? 'manual';
$rooms = $rooms ?? [];
$users = $users ?? [];
$products = $products ?? [];

include __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex gap-0 gap-lg-3 flex-wrap flex-lg-nowrap">

    <!-- ─── ORDER PANEL ─── -->
    <div id="orderPanel" class="bg-white rounded-3 border p-3 d-flex flex-column"
        style="min-width:270px;max-width:280px;position:sticky;top:72px;align-self:flex-start;">

        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-clipboard-plus text-warning"></i>Order Panel
        </h6>

        <div id="orderList" class="mb-3 flex-grow-1" style="min-height:80px;">
            <div id="emptyMsg" class="text-muted small text-center py-3">
                <i class="bi bi-cup-hot d-block mb-1" style="font-size:1.8rem;opacity:.3;"></i>No items yet
            </div>
        </div>

        <div id="orderTotalRow" class="d-none border-top pt-2 mb-3">
            <div class="d-flex justify-content-between fw-bold">
                <span>Total</span>
                <span id="orderTotalAmt">0 EGP</span>
            </div>
        </div>

        <hr class="my-2" />

        <label class="form-label small fw-semibold mb-1">Notes</label>
        <textarea class="form-control form-control-sm mb-3" id="orderNotes" rows="3"
            placeholder="e.g. 1 Tea Extra Sugar"></textarea>

        <label class="form-label small fw-semibold mb-1">Room</label>
        <select class="form-select form-select-sm mb-3" id="orderRoom">
            <option value="">— Select room —</option>
            <?php foreach ($rooms as $r): ?>
                <option><?= htmlspecialchars($r) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn-warning fw-semibold w-100" onclick="confirmOrder()">
            <i class="bi bi-check-circle me-1"></i>Confirm
        </button>
    </div>

    <!-- ─── PRODUCTS AREA ─── -->
    <div class="flex-grow-1 min-width-0">

        <!-- Search -->
        <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
            <h5 class="fw-bold mb-0">Manual Order</h5>
            <div class="input-group" style="max-width:220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" placeholder="Search…"
                    id="searchInput" oninput="filterProducts()" />
            </div>
        </div>

        <!-- "Add to user" section (wireframe p.3 — key difference from user home) -->
        <div class="page-card mb-3">
            <div class="page-card-header d-flex align-items-center gap-2">
                <i class="bi bi-person-check text-warning"></i>
                <span class="fw-semibold small">Add to user</span>
                <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">Admin only</span>
            </div>
            <div class="p-3">
                <select class="form-select" id="assignedUser" style="max-width:320px;" required>
                    <option value="">— Select employee —</option>
                    <?php foreach ($users as $u): ?>
                        <option><?= htmlspecialchars($u) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">This order will be placed on behalf of the selected employee.</div>
            </div>
        </div>

        <!-- Category filter -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <button class="btn btn-sm btn-warning cat-btn active" data-cat="all">All</button>
            <button class="btn btn-sm btn-outline-secondary cat-btn" data-cat="hot">☕ Hot</button>
            <button class="btn btn-sm btn-outline-primary cat-btn" data-cat="cold">🧊 Cold</button>
            <button class="btn btn-sm btn-outline-success cat-btn" data-cat="food">🍪 Snacks</button>
        </div>

        <!-- Product grid -->
        <div class="row g-3" id="productsGrid">
            <?php foreach ($products as $p): ?>
                <div class="col-6 col-sm-4 col-md-3 col-xl-2 product-item"
                    data-cat="<?= $p['cat'] ?>"
                    data-name="<?= strtolower($p['name']) ?>">
                    <div class="card h-100 border product-card text-center p-2
                        <?= !$p['available'] ? 'opacity-50' : '' ?>"
                        onclick="<?= $p['available'] ? "addToOrder({$p['id']},'{$p['name']}',{$p['price']})" : '' ?>">
                        <div class="product-emoji my-2"><?= $p['emoji'] ?></div>
                        <div class="fw-semibold small mb-1"><?= htmlspecialchars($p['name']) ?></div>
                        <span class="badge bg-dark price-badge"><?= $p['price'] ?> LE</span>
                        <?php if (!$p['available']): ?>
                            <div class="badge bg-danger mt-1" style="font-size:.65rem;">Out of Stock</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
    let order = {};

    function addToOrder(id, name, price) {
        if (order[id]) order[id].qty++;
        else order[id] = {
            name,
            price,
            qty: 1
        };
        renderOrder();
        showToast(`<i class="bi bi-plus-circle me-1"></i>${name} added`);
    }

    function changeQty(id, delta) {
        if (!order[id]) return;
        order[id].qty += delta;
        if (order[id].qty <= 0) delete order[id];
        renderOrder();
    }

    function renderOrder() {
        const list = document.getElementById('orderList');
        const empty = document.getElementById('emptyMsg');
        const totRow = document.getElementById('orderTotalRow');
        const totAmt = document.getElementById('orderTotalAmt');
        const keys = Object.keys(order);
        if (keys.length === 0) {
            list.innerHTML = '';
            list.appendChild(empty);
            empty.classList.remove('d-none');
            totRow.classList.add('d-none');
            return;
        }
        empty.classList.add('d-none');
        let total = 0;
        list.innerHTML = keys.map(id => {
            const item = order[id];
            total += item.price * item.qty;
            return `<div class="d-flex align-items-center gap-2 py-1 border-bottom">
            <span class="flex-grow-1 small fw-semibold">${item.name}</span>
            <div class="d-flex align-items-center gap-1">
                <button class="btn btn-sm btn-outline-secondary py-0 px-1 lh-1" onclick="changeQty(${id},-1)">−</button>
                <span class="small fw-bold" style="min-width:20px;text-align:center;">${item.qty}</span>
                <button class="btn btn-sm btn-outline-secondary py-0 px-1 lh-1" onclick="changeQty(${id},1)">+</button>
            </div>
            <span class="small text-muted" style="min-width:52px;text-align:right;">EGP ${item.price*item.qty}</span>
            <button class="btn btn-sm btn-link text-danger p-0 ms-1" onclick="changeQty(${id},-999)">
                <i class="bi bi-x-lg" style="font-size:.8rem;"></i>
            </button>
        </div>`;
        }).join('');
        totRow.classList.remove('d-none');
        totAmt.textContent = 'EGP ' + total;
    }

    function confirmOrder() {
        const user = document.getElementById('assignedUser').value;
        if (!user) {
            showToast('<i class="bi bi-person-x me-1"></i>Select an employee first!', 'warning');
            return;
        }
        if (Object.keys(order).length === 0) {
            showToast('<i class="bi bi-exclamation-triangle me-1"></i>Order is empty!', 'warning');
            return;
        }
        if (!document.getElementById('orderRoom').value) {
            showToast('<i class="bi bi-door-closed me-1"></i>Select a room!', 'warning');
            return;
        }
        showToast(`<i class="bi bi-check-circle me-1"></i>Order placed for ${user}!`);
        order = {};
        renderOrder();
        document.getElementById('orderRoom').value = '';
        document.getElementById('orderNotes').value = '';
    }
    let activeCat = 'all';
    document.querySelectorAll('.cat-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.cat-btn').forEach(b => {
                b.classList.remove('btn-warning', 'active');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.add('btn-warning', 'active');
            btn.classList.remove('btn-outline-secondary', 'btn-outline-primary', 'btn-outline-success');
            activeCat = btn.dataset.cat;
            filterProducts();
        });
    });

    function filterProducts() {
        const q = document.getElementById('searchInput').value.toLowerCase().trim();
        document.querySelectorAll('.product-item').forEach(el => {
            el.style.display = ((activeCat === 'all' || el.dataset.cat === activeCat) && (!q || el.dataset.name.includes(q))) ? '' : 'none';
        });
    }

    function showToast(msg, type = 'success') {
        const wrap = document.getElementById('toastWrap');
        const id = 'toast_' + Date.now();
        const bg = {
            success: 'bg-success',
            warning: 'bg-warning text-dark',
            danger: 'bg-danger'
        };
        wrap.insertAdjacentHTML('beforeend', `<div id="${id}" class="toast text-white ${bg[type]??bg.success} border-0 shadow-sm" role="alert"><div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`);
        const el = document.getElementById(id);
        new bootstrap.Toast(el, {
            delay: 2500
        }).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>