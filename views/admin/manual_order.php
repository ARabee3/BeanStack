<?php

/**
 * views/admin/manual_order.php
 * Admin places an order on behalf of any employee.
 * Submits to ?page=store-order via fetch() (JSON body).
 *
 * Variables injected by index.php router:
 *   $users     – [id, name]  all non-admin users
 *   $rooms     – [id, details]  all locations
 *   $products  – all available products (with category_id, image, price)
 *   $categories – [id, name]
 */

$users      = $users      ?? [];
$rooms      = $rooms      ?? [];
$products   = $products   ?? [];
$categories = $categories ?? [];

$pageTitle = $pageTitle ?? 'Manual Order';
$activeNav = $activeNav ?? 'manual-order';

// ── Flash ────────────────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include __DIR__ . '/../layouts/header.php';
?>

<!-- ── Flash ─────────────────────────────────────────────────────────────── -->
<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Page heading ───────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-2 mb-4">
    <div class="rounded-3 bg-warning bg-opacity-10 p-2">
        <i class="bi bi-clipboard-plus text-warning fs-5"></i>
    </div>
    <div>
        <h5 class="fw-bold mb-0">Manual Order</h5>
        <p class="text-muted small mb-0">Place an order on behalf of any employee</p>
    </div>
    <span class="badge bg-warning text-dark ms-2">
        <i class="bi bi-shield-fill me-1"></i>Admin
    </span>
    <a href="?page=orders" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-arrow-left me-1"></i>Back to Orders
    </a>
</div>

<div class="row g-4">

    <!-- ══════════════════════════════════════════════
         LEFT — Product grid
    ══════════════════════════════════════════════ -->
    <div class="col-12 col-lg-8 order-2 order-lg-1">

        <!-- Search + category filters -->
        <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
            <h6 class="fw-bold mb-0">Menu</h6>
            <div class="input-group" style="max-width:240px;">
                <span class="input-group-text bg-light border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-start-0 ps-0"
                       placeholder="Search product…"
                       id="searchInput"
                       oninput="filterProducts()" />
            </div>
        </div>

        <!-- Category filter buttons — built from $categories -->
        <div class="d-flex gap-2 mb-3 flex-wrap" id="catButtons">
            <button class="btn btn-sm btn-warning cat-btn active" data-cat="all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="btn btn-sm btn-outline-secondary cat-btn"
                        data-cat="<?= (int)$cat['id'] ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Product grid -->
        <div class="row g-3" id="productsGrid">
            <?php if (empty($products)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
                    No available products found.
                </div>
            <?php endif; ?>

            <?php foreach ($products as $p): ?>
                <div class="col-6 col-sm-4 col-md-3 product-item"
                     data-cat="<?= (int)$p['category_id'] ?>"
                     data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">

                    <div class="card h-100 border text-center p-2 shadow-sm product-card"
                         style="cursor:pointer;"
                         onclick="addToOrder(
                             <?= (int)$p['id'] ?>,
                             '<?= htmlspecialchars(addslashes($p['name'])) ?>',
                             <?= (float)$p['price'] ?>
                         )">

                        <!-- Product image or placeholder -->
                        <div class="d-flex justify-content-center align-items-center my-2"
                             style="height:64px;">
                            <?php if (!empty($p['image'])): ?>
                                <img src="../public/<?= htmlspecialchars($p['image']) ?>"
                                     style="max-width:60px;max-height:60px;object-fit:contain;"
                                     alt="<?= htmlspecialchars($p['name']) ?>" />
                            <?php else: ?>
                                <div class="rounded bg-light border d-flex align-items-center
                                            justify-content-center text-muted"
                                     style="width:60px;height:60px;font-size:1.5rem;">
                                    <i class="bi bi-cup-hot"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="fw-semibold small mb-1"><?= htmlspecialchars($p['name']) ?></div>
                        <span class="badge bg-dark" style="font-size:.62rem;">
                            <?= number_format((float)$p['price'], 2) ?> EGP
                        </span>

                        <!-- Quick-add indicator -->
                        <div class="mt-1 text-success small d-none product-added-indicator"
                             id="added_<?= (int)$p['id'] ?>">
                            <i class="bi bi-check-circle-fill"></i> Added
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div><!-- /col-lg-8 -->


    <!-- ══════════════════════════════════════════════
         RIGHT — Order summary panel
    ══════════════════════════════════════════════ -->
    <div class="col-12 col-lg-4 order-1 order-lg-2">
        <div id="orderPanel"
             class="bg-white rounded-3 border p-3 d-flex flex-column shadow-sm"
             style="position:sticky;top:20px;">

            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-clipboard-plus text-warning"></i>Order Summary
            </h6>

            <!-- Employee selector -->
            <label class="form-label small fw-semibold mb-1">
                <i class="bi bi-person-check me-1 text-warning"></i>
                Place order for <span class="text-danger">*</span>
            </label>
            <?php if (empty($users)): ?>
                <div class="alert alert-warning small py-2 mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    No users found. Please add users first.
                </div>
            <?php else: ?>
                <select class="form-select form-select-sm mb-3" id="selectedUser">
                    <option value="">— Select employee —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>">
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <!-- Cart items -->
            <div id="orderList" class="mb-2 flex-grow-1" style="min-height:80px;max-height:280px;overflow-y:auto;">
                <div id="emptyMsg" class="text-muted small text-center py-3">
                    <i class="bi bi-cup-hot d-block mb-1" style="font-size:1.8rem;opacity:.3;"></i>
                    No items yet — click a product to add
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

            <!-- Room / Location -->
            <label class="form-label small fw-semibold mb-1">
                Location <span class="text-danger">*</span>
            </label>
            <select class="form-select form-select-sm mb-3" id="orderRoom">
                <option value="">— Select location —</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= (int)$r['id'] ?>">
                        <?= htmlspecialchars($r['details']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Confirm button -->
            <button class="btn btn-warning fw-semibold w-100" id="confirmBtn"
                    onclick="confirmOrder()">
                <i class="bi bi-check-circle me-1"></i>Confirm Order
            </button>

        </div>
    </div><!-- /col-lg-4 -->

</div><!-- /row -->

<!-- Toast container -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ── Cart state ───────────────────────────────────────────────────────────────
// { productId: { name, price, qty } }
let cart = {};

// ── addToOrder ───────────────────────────────────────────────────────────────
function addToOrder(id, name, price) {
    if (cart[id]) {
        cart[id].qty++;
    } else {
        cart[id] = { name, price, qty: 1 };
    }

    // Flash "Added" indicator on the card
    const ind = document.getElementById(`added_${id}`);
    if (ind) {
        ind.classList.remove('d-none');
        clearTimeout(ind._timer);
        ind._timer = setTimeout(() => ind.classList.add('d-none'), 1500);
    }

    renderCart();
}

// ── changeQty ────────────────────────────────────────────────────────────────
function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}

// ── renderCart ───────────────────────────────────────────────────────────────
function renderCart() {
    const list      = document.getElementById('orderList');
    const totalRow  = document.getElementById('orderTotalRow');
    const totalAmt  = document.getElementById('orderTotalAmt');
    const keys      = Object.keys(cart);

    list.innerHTML = '';

    if (keys.length === 0) {
        list.innerHTML = `
            <div id="emptyMsg" class="text-muted small text-center py-3">
                <i class="bi bi-cup-hot d-block mb-1" style="font-size:1.8rem;opacity:.3;"></i>
                No items yet — click a product to add
            </div>`;
        totalRow.classList.add('d-none');
        return;
    }

    totalRow.classList.remove('d-none');
    let grand = 0;

    keys.forEach(id => {
        const item  = cart[id];
        const line  = item.price * item.qty;
        grand      += line;

        list.insertAdjacentHTML('beforeend', `
            <div class="d-flex align-items-center justify-content-between border-bottom py-2 gap-2">
                <div class="flex-grow-1 min-w-0">
                    <div class="small fw-semibold text-truncate" title="${item.name}">${item.name}</div>
                    <div class="text-muted" style="font-size: .7rem;">${item.price.toFixed(2)} EGP</div>
                </div>
                <div class="d-flex align-items-center gap-1 flex-shrink-0 mx-2">
                    <button class="btn btn-sm btn-light border px-2 py-0 lh-1"
                            onclick="changeQty(${id}, -1)">−</button>
                    <span class="fw-bold small" style="min-width: 20px; text-align: center;">${item.qty}</span>
                    <button class="btn btn-sm btn-light border px-2 py-0 lh-1"
                            onclick="changeQty(${id},  1)">+</button>
                </div>
                <div class="small fw-bold text-end flex-shrink-0" style="min-width: 65px;">
                    ${line.toFixed(2)}
                </div>
            </div>`);
    });

    totalAmt.textContent = grand.toFixed(2) + ' EGP';
}

// ── confirmOrder ─────────────────────────────────────────────────────────────
async function confirmOrder() {
    if (Object.keys(cart).length === 0) {
        Swal.fire('Empty Cart', 'Please add at least one product.', 'warning');
        return;
    }

    const roomEl = document.getElementById('orderRoom');
    if (!roomEl.value) {
        Swal.fire('Location Required', 'Please choose a delivery location.', 'info');
        return;
    }

    const userEl = document.getElementById('selectedUser');
    if (!userEl || !userEl.value) {
        Swal.fire('Employee Required', 'Please select the employee for this order.', 'info');
        return;
    }

    const employeeName = userEl.options[userEl.selectedIndex].text;

    const { isConfirmed } = await Swal.fire({
        title: 'Confirm Manual Order?',
        html:  `Place order on behalf of <b>${employeeName}</b>?`,
        icon:  'question',
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

    // Build base path dynamically so it works regardless of subdirectory
    const base = window.location.pathname.replace(/\/index\.php.*$/, '');

    const payload = {
        target_user_id: parseInt(document.getElementById('selectedUser').value, 10),
        location_id:    document.getElementById('orderRoom').value,
        notes:          document.getElementById('orderNotes').value,
        products:       cart,
    };

    try {
        const res  = await fetch(`${base}/index.php?page=store-order`, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (data.success) {
            const employeeName = document.getElementById('selectedUser')
                .options[document.getElementById('selectedUser').selectedIndex].text;

            await Swal.fire({
                icon:  'success',
                title: 'Order Placed!',
                html:  `Order <b>#${data.order_id}</b> placed for <b>${employeeName}</b>.`,
                confirmButtonColor: '#ffc107',
            });

            // Reset
            cart = {};
            renderCart();
            document.getElementById('orderNotes').value    = '';
            document.getElementById('orderRoom').value     = '';
            document.getElementById('selectedUser').value  = '';

        } else {
            Swal.fire('Failed', data.message || 'Could not place order.', 'error');
        }

    } catch (err) {
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

// ── Category button click ─────────────────────────────────────────────────────
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

<?php include __DIR__ . '/../layouts/footer.php'; ?>