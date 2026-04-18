<?php

/**
 * home.php — User order page (wireframe p.2)
 * Left: order panel (Latest Order + Notes + Room + Total + Confirm)
 * Right: search bar + featured row + product grid
 */

$rooms = $rooms ?? [];
$products = $products ?? [];

include __DIR__ . '/layouts/header.php';
?>

<!-- ══════════════════════════════════════
     HOME  (flex row: order panel | content)
══════════════════════════════════════ -->
<div class="d-flex gap-0 gap-lg-3 flex-wrap flex-lg-nowrap">

    <!-- ─────────────────────────────────────
     LEFT: ORDER PANEL  (wireframe p.2 left box)
───────────────────────────────────── -->
    <div id="orderPanel" class="bg-white rounded-3 border p-3 d-flex flex-column"
        style="min-width:270px;max-width:280px;position:sticky;top:72px;align-self:flex-start;">

        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-bag-check text-warning"></i>Latest Order
        </h6>

        <!-- Order items list -->
        <div id="orderList" class="mb-3 flex-grow-1" style="min-height:80px;">
            <div id="emptyMsg" class="text-muted small text-center py-3">
                <i class="bi bi-cup-hot d-block mb-1" style="font-size:1.8rem;opacity:.3;"></i>
                No items yet
            </div>
        </div>

        <!-- Divider + total -->
        <div id="orderTotalRow" class="d-none border-top pt-2 mb-3">
            <div class="d-flex justify-content-between fw-bold">
                <span>Total</span>
                <span id="orderTotalAmt">0 EGP</span>
            </div>
        </div>

        <hr class="my-2" />

        <!-- Notes -->
        <label class="form-label small fw-semibold mb-1">Notes</label>
        <textarea class="form-control form-control-sm mb-3" id="orderNotes" rows="3"
            placeholder="e.g. 1 Tea Extra Sugar"></textarea>

        <!-- Room ComboBox -->
        <label class="form-label small fw-semibold mb-1">Room</label>
        <select class="form-select form-select-sm mb-3" id="orderRoom">
            <option value="">— Select room —</option>
            <?php foreach ($rooms as $r): ?>
                <option><?= htmlspecialchars($r) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Confirm button -->
        <button class="btn btn-warning fw-semibold w-100" onclick="confirmOrder()">
            <i class="bi bi-check-circle me-1"></i>Confirm
        </button>
    </div>
    <!-- /order panel -->

    <!-- ─────────────────────────────────────
     RIGHT: PRODUCTS AREA
───────────────────────────────────── -->
    <div class="flex-grow-1 min-width-0">

        <!-- Search + heading -->
        <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
            <h5 class="fw-bold mb-0">Menu</h5>
            <div class="input-group" style="max-width:240px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" placeholder="Search…"
                    id="searchInput" oninput="filterProducts()" />
            </div>
        </div>

        <!-- Category filter pills -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <button class="btn btn-sm btn-warning cat-btn active" data-cat="all">All</button>
            <button class="btn btn-sm btn-outline-secondary cat-btn" data-cat="hot">☕ Hot</button>
            <button class="btn btn-sm btn-outline-primary   cat-btn" data-cat="cold">🧊 Cold</button>
            <button class="btn btn-sm btn-outline-success   cat-btn" data-cat="food">🍪 Snacks</button>
        </div>

        <!-- Featured / Latest Order row (top cups in wireframe) -->
        <div class="page-card mb-3">
            <div class="page-card-header d-flex align-items-center gap-2">
                <i class="bi bi-stars text-warning"></i>
                <span class="fw-semibold small">Featured</span>
            </div>
            <div class="p-3 d-flex gap-3 flex-wrap">
                <?php foreach (array_slice($products, 0, 4) as $p): ?>
                    <?php if (!$p['available']) continue; ?>
                    <div class="text-center" style="min-width:70px;cursor:pointer;" onclick="addToOrder(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name']) ?>',<?= $p['price'] ?>)">
                        <div class="product-emoji mb-1"><?= $p['emoji'] ?></div>
                        <div class="small fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product grid -->
        <div class="row g-3" id="productsGrid">
            <?php foreach ($products as $p): ?>
                <div class="col-6 col-sm-4 col-md-3 col-xl-2 product-item"
                    data-cat="<?= $p['cat'] ?>"
                    data-name="<?= strtolower($p['name']) ?>"
                    data-available="<?= $p['available'] ? '1' : '0' ?>">
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

    </div><!-- /products area -->
</div><!-- /flex row -->

<!-- Toast container -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
    /* ── Order state ─────────────────────── */
    let order = {}; // { id: {name, price, qty} }

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
            return `
        <div class="d-flex align-items-center gap-2 py-1 border-bottom">
            <span class="flex-grow-1 small fw-semibold">${item.name}</span>
            <div class="d-flex align-items-center gap-1">
                <button class="btn btn-sm btn-outline-secondary py-0 px-1 lh-1" onclick="changeQty(${id},-1)">−</button>
                <input type="number" class="form-control form-control-sm text-center p-0"
                       style="width:36px;" value="${item.qty}" min="1"
                       onchange="setQty(${id},this.value)"/>
                <button class="btn btn-sm btn-outline-secondary py-0 px-1 lh-1" onclick="changeQty(${id},1)">+</button>
            </div>
            <span class="small text-muted" style="min-width:52px;text-align:right;">
                EGP ${item.price * item.qty}</span>
            <button class="btn btn-sm btn-link text-danger p-0 ms-1" onclick="changeQty(${id},-999)">
                <i class="bi bi-x-lg" style="font-size:.8rem;"></i>
            </button>
        </div>`;
        }).join('');

        totRow.classList.remove('d-none');
        totAmt.textContent = 'EGP ' + total;
    }

    function setQty(id, val) {
        const q = parseInt(val, 10);
        if (isNaN(q) || q <= 0) {
            delete order[id];
        } else order[id].qty = q;
        renderOrder();
    }

    function confirmOrder() {
        if (Object.keys(order).length === 0) {
            showToast('<i class="bi bi-exclamation-triangle me-1"></i>Your order is empty!', 'warning');
            return;
        }
        if (!document.getElementById('orderRoom').value) {
            showToast('<i class="bi bi-exclamation-triangle me-1"></i>Please select a room.', 'warning');
            return;
        }
        /* TODO: POST to order_submit.php via fetch */
        showToast('<i class="bi bi-check-circle me-1"></i>Order placed successfully!');
        order = {};
        renderOrder();
        document.getElementById('orderRoom').value = '';
        document.getElementById('orderNotes').value = '';
    }

    /* ── Category filter ─────────────────── */
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
            const catOk = activeCat === 'all' || el.dataset.cat === activeCat;
            const nameOk = !q || el.dataset.name.includes(q);
            el.style.display = (catOk && nameOk) ? '' : 'none';
        });
    }

    /* ── Toast ───────────────────────────── */
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
            delay: 2500
        }).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>