<?php

/**
 * views/home.php
 * Regular user — browse products and place orders.
 *
 * Variables injected by index.php (via HomeController or direct include):
 *   $products   – available, non-deleted products joined with category
 *   $rooms      – all locations
 *   $categories – all categories (for filter buttons)
 *   $pageTitle  – string
 *   $activeNav  – string
 */

$products   = $products   ?? [];
$rooms      = $rooms      ?? [];
$categories = $categories ?? [];
$pageTitle  = $pageTitle  ?? 'Home';
$activeNav  = $activeNav  ?? 'home';

// ── Flash ─────────────────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include __DIR__ . '/layouts/header.php';
?>

<!-- ── Flash ─────────────────────────────────────────────────────────────── -->
<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="container-fluid py-3">
    <div class="row g-4">

        <!-- ══════════════════════════════════════════
             LEFT — Product grid (col-lg-9)
        ══════════════════════════════════════════ -->
        <div class="col-12 col-lg-9 order-2 order-lg-1">

            <!-- Search bar -->
            <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-cup-hot me-2 text-warning"></i>Menu
                </h5>
                <div class="input-group" style="max-width:240px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text"
                           class="form-control border-start-0 ps-0"
                           placeholder="Search products…"
                           id="searchInput"
                           oninput="filterProducts()" />
                </div>
            </div>

            <!-- Category filter buttons — built from DB, not hardcoded -->
            <div class="d-flex gap-2 mb-3 flex-wrap" id="catButtons">
                <button class="btn btn-sm btn-warning cat-btn active" data-cat="all">
                    All
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button class="btn btn-sm btn-outline-secondary cat-btn"
                            data-cat="<?= (int)$cat['id'] ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Featured strip (first 4 available products) -->
            <?php if (!empty($products)): ?>
            <div class="page-card mb-3 shadow-sm border rounded">
                <div class="page-card-header d-flex align-items-center gap-2 p-2 bg-light border-bottom">
                    <i class="bi bi-stars text-warning"></i>
                    <span class="fw-semibold small">Featured</span>
                </div>
                <div class="p-3 d-flex gap-3 flex-wrap">
                    <?php foreach (array_slice($products, 0, 4) as $p): ?>
                        <div class="text-center" style="min-width:70px;cursor:pointer;"
                             onclick="addToOrder(
                                 <?= (int)$p['id'] ?>,
                                 '<?= htmlspecialchars(addslashes($p['name'])) ?>',
                                 <?= (float)$p['price'] ?>
                             )">
                            <?php if (!empty($p['image'])): ?>
                                <img src="../public/<?= htmlspecialchars($p['image']) ?>"
                                     style="width:40px;height:40px;object-fit:contain;"
                                     class="mb-1"
                                     alt="<?= htmlspecialchars($p['name']) ?>" />
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center
                                            bg-light border rounded mb-1 mx-auto text-muted"
                                     style="width:40px;height:40px;font-size:1.1rem;">
                                    <i class="bi bi-cup-hot"></i>
                                </div>
                            <?php endif; ?>
                            <div class="small fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                            <span class="badge bg-dark" style="font-size:.6rem;">
                                <?= number_format((float)$p['price'], 2) ?> EGP
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Full product grid -->
            <div class="row g-3" id="productsGrid">

                <?php if (empty($products)): ?>
                    <div class="col-12 text-center text-muted py-5">
                        <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
                        No products available right now.
                    </div>
                <?php endif; ?>

                <?php foreach ($products as $p): ?>
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2 product-item"
                         data-cat="<?= (int)$p['category_id'] ?>"
                         data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">

                        <div class="card h-100 border product-card text-center p-2 shadow-sm"
                             style="cursor:pointer;transition:transform .15s,box-shadow .15s;"
                             onclick="addToOrder(
                                 <?= (int)$p['id'] ?>,
                                 '<?= htmlspecialchars(addslashes($p['name'])) ?>',
                                 <?= (float)$p['price'] ?>
                             )"
                             onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,.12)'"
                             onmouseleave="this.style.transform='';this.style.boxShadow=''">

                            <!-- Image or placeholder -->
                            <div class="d-flex justify-content-center align-items-center my-2"
                                 style="height:64px;">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="../public/<?= htmlspecialchars($p['image']) ?>"
                                         style="max-width:60px;max-height:60px;object-fit:contain;"
                                         alt="<?= htmlspecialchars($p['name']) ?>" />
                                <?php else: ?>
                                    <div class="rounded bg-light border d-flex align-items-center
                                                justify-content-center text-muted"
                                         style="width:60px;height:60px;font-size:1.4rem;">
                                        <i class="bi bi-cup-hot"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="fw-semibold small mb-1"><?= htmlspecialchars($p['name']) ?></div>
                            <span class="badge bg-dark" style="font-size:.62rem;">
                                <?= number_format((float)$p['price'], 2) ?> EGP
                            </span>

                            <!-- "Added" flash indicator -->
                            <div class="text-success small mt-1 d-none"
                                 id="added_<?= (int)$p['id'] ?>">
                                <i class="bi bi-check-circle-fill"></i> Added
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div><!-- /productsGrid -->
        </div><!-- /col-lg-9 -->


        <!-- ══════════════════════════════════════════
             RIGHT — Order summary panel (col-lg-3)
        ══════════════════════════════════════════ -->
        <div class="col-12 col-lg-3 order-1 order-lg-2">
            <div id="orderPanel"
                 class="bg-white rounded-3 border p-3 d-flex flex-column shadow-sm"
                 style="position:sticky;top:20px;">

                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-bag-check text-warning"></i>My Order
                </h6>

                <!-- Cart items -->
                <div id="orderList" class="mb-2 flex-grow-1"
                     style="min-height:80px;max-height:300px;overflow-y:auto;">
                    <div id="emptyMsg" class="text-muted small text-center py-3">
                        <i class="bi bi-cup-hot d-block mb-1"
                           style="font-size:1.8rem;opacity:.3;"></i>
                        No items yet — tap a product to add
                    </div>
                </div>

                <!-- Total -->
                <div id="orderTotalRow" class="d-none border-top pt-2 mb-2">
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span>
                        <span id="orderTotalAmt" class="text-warning">0 EGP</span>
                    </div>
                </div>

                <hr class="my-2" />

                <!-- Notes -->
                <label class="form-label small fw-semibold mb-1">Notes</label>
                <textarea class="form-control form-control-sm mb-3"
                          id="orderNotes" rows="2"
                          placeholder="e.g. Extra sugar, no ice…"></textarea>

                <!-- Room / Location — shows only the user's assigned location -->
                <label class="form-label small fw-semibold mb-1">
                    Delivery location
                </label>
                <?php if (empty($rooms)): ?>
                    <div class="alert alert-warning small py-2 mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        No location assigned to your account.
                        Please <a href="?page=profile" class="alert-link">set your location</a> in your profile.
                    </div>
                    <!-- Hidden so confirmOrder() still reads it — value stays empty → blocked -->
                    <select class="d-none" id="orderRoom">
                        <option value="">— No location —</option>
                    </select>
                <?php else: ?>
                    <select class="form-select form-select-sm mb-3" id="orderRoom">
                        <?php foreach ($rooms as $r): ?>
                            <!-- Auto-select the user's location — only one option -->
                            <option value="<?= (int)$r['id'] ?>" selected>
                                <i class="bi bi-geo-alt"></i>
                                <?= htmlspecialchars($r['details']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <!-- Confirm -->
                <button class="btn btn-warning fw-semibold w-100"
                        id="confirmBtn"
                        onclick="confirmOrder()">
                    <i class="bi bi-check-circle me-1"></i>Confirm Order
                </button>

            </div>
        </div><!-- /col-lg-3 -->

    </div><!-- /row -->
</div><!-- /container-fluid -->

<!-- Toast container -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3"
     style="z-index:9999;"></div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ── Cart state ────────────────────────────────────────────────────────────────
let cart = {};

// ── addToOrder ────────────────────────────────────────────────────────────────
function addToOrder(id, name, price) {
    if (cart[id]) {
        cart[id].qty++;
    } else {
        cart[id] = { name, price, qty: 1 };
    }

    // Flash "Added ✓" indicator on the product card
    const ind = document.getElementById(`added_${id}`);
    if (ind) {
        ind.classList.remove('d-none');
        clearTimeout(ind._timer);
        ind._timer = setTimeout(() => ind.classList.add('d-none'), 1500);
    }

    renderCart();
}

// ── changeQty ─────────────────────────────────────────────────────────────────
function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}

// ── renderCart ────────────────────────────────────────────────────────────────
function renderCart() {
    const list     = document.getElementById('orderList');
    const totalRow = document.getElementById('orderTotalRow');
    const totalAmt = document.getElementById('orderTotalAmt');
    const keys     = Object.keys(cart);

    list.innerHTML = '';

    if (keys.length === 0) {
        list.innerHTML = `
            <div id="emptyMsg" class="text-muted small text-center py-3">
                <i class="bi bi-cup-hot d-block mb-1"
                   style="font-size:1.8rem;opacity:.3;"></i>
                No items yet — tap a product to add
            </div>`;
        totalRow.classList.add('d-none');
        return;
    }

    totalRow.classList.remove('d-none');
    let grand = 0;

    keys.forEach(id => {
        const item = cart[id];
        const line = item.price * item.qty;
        grand += line;

        list.insertAdjacentHTML('beforeend', `
            <div class="d-flex align-items-center justify-content-between
                        border-bottom py-2 gap-2">
                <span class="small fw-semibold text-truncate"
                      style="max-width:110px;"
                      title="${item.name}">${item.name}</span>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button class="btn btn-sm btn-light px-2 py-0 lh-1"
                            onclick="changeQty(${id}, -1)">−</button>
                    <span class="fw-bold small">${item.qty}</span>
                    <button class="btn btn-sm btn-light px-2 py-0 lh-1"
                            onclick="changeQty(${id},  1)">+</button>
                </div>
                <span class="small text-muted flex-shrink-0">
                    ${line.toFixed(2)} EGP
                </span>
            </div>`);
    });

    totalAmt.textContent = grand.toFixed(2) + ' EGP';
}

// ── confirmOrder ──────────────────────────────────────────────────────────────
async function confirmOrder() {
    if (Object.keys(cart).length === 0) {
        Swal.fire('Empty Cart', 'Please add at least one item.', 'warning');
        return;
    }

    const roomEl = document.getElementById('orderRoom');
    if (!roomEl.value) {
        Swal.fire('Location Required', 'Please select a delivery location.', 'info');
        return;
    }

    const { isConfirmed } = await Swal.fire({
        title:              'Confirm Order?',
        text:               'Send this order to the cafeteria?',
        icon:               'question',
        showCancelButton:   true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor:  '#6c757d',
        confirmButtonText:  'Yes, Place Order!',
    });

    if (isConfirmed) await submitOrder();
}

// ── submitOrder ───────────────────────────────────────────────────────────────
async function submitOrder() {
    const btn = document.getElementById('confirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Placing…';

    // Use the router, not the controller file directly
    const base = window.location.pathname.replace(/\/index\.php.*$/, '');

    const payload = {
        location_id: document.getElementById('orderRoom').value,
        notes:       document.getElementById('orderNotes').value,
        products:    cart,
    };

    try {
        const res = await fetch(`${base}/index.php?page=store-order`, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();

        if (data.success) {
            await Swal.fire({
                icon:               'success',
                title:              'Order Placed!',
                html:               `Your order <b>#${data.order_id}</b> has been received.<br>We'll deliver it shortly ☕`,
                confirmButtonColor: '#ffc107',
            });

            // Reset cart
            cart = {};
            renderCart();
            document.getElementById('orderNotes').value = '';
            document.getElementById('orderRoom').value  = '';

        } else {
            Swal.fire('Failed', data.message || 'Could not place order.', 'error');
        }

    } catch (err) {
        console.error('[home] submitOrder error:', err);
        Swal.fire('Connection Error', 'Could not reach the server. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirm Order';
    }
}

// ── filterProducts ────────────────────────────────────────────────────────────
function filterProducts() {
    const q      = document.getElementById('searchInput').value.toLowerCase().trim();
    const active = document.querySelector('.cat-btn.active');
    const cat    = active ? active.dataset.cat : 'all';

    document.querySelectorAll('.product-item').forEach(el => {
        const nameOk = !q   || el.dataset.name.includes(q);
        const catOk  = cat === 'all' || el.dataset.cat === cat;
        el.style.display = (nameOk && catOk) ? '' : 'none';
    });
}

// ── Category buttons ──────────────────────────────────────────────────────────
document.querySelectorAll('.cat-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.cat-btn').forEach(b => {
            b.classList.remove('active', 'btn-warning');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.add('active', 'btn-warning');
        this.classList.remove('btn-outline-secondary');
        filterProducts();
    });
});
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>