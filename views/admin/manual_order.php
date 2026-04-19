<?php


session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../config/Database.php';

// ── Fetch data from database ──────────────────────────────
try {
    $conn = Database::connect();

    // All non-deleted users with role = 'user'
    // We show their name in the dropdown + send their id to the backend
$userStmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'user' ORDER BY name ASC");
    $userStmt->execute();
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

    // Rooms / locations (same query as home.php)
    $roomStmt = $conn->query("SELECT id, details FROM locations");
    $rooms    = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

    // Available products (same query as home.php)
    $prodStmt = $conn->query("SELECT * FROM products WHERE is_available = 1");
    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// ── Page meta (used by header.php) ───────────────────────
$pageTitle = 'Manual Order';
$activeNav = 'manual_order';

include '../layouts/header.php';
?>

<div class="container-fluid py-3">

    <!-- ── Page heading ───────────────────────────────── -->
    <div class="d-flex align-items-center gap-2 mb-4">
        <div class="rounded-3 bg-warning bg-opacity-10 p-2">
            <i class="bi bi-clipboard-plus text-warning fs-5"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-0">Manual Order</h5>
            <p class="text-muted small mb-0">Place an order on behalf of any employee</p>
        </div>
        <!-- Admin badge so it's clear this is an admin-only view -->
        <span class="badge bg-warning text-dark ms-2">
            <i class="bi bi-shield-fill me-1"></i>Admin
        </span>
    </div>

    <div class="row g-4">

        <!-- ════════════════════════════════════════════
             LEFT COLUMN — Product grid (col-lg-9)
             Identical structure to home.php
        ════════════════════════════════════════════ -->
        <div class="col-12 col-lg-9 order-2 order-lg-1">

            <!-- Search bar -->
            <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
                <h5 class="fw-bold mb-0">Menu</h5>
                <div class="input-group" style="max-width:240px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text"
                           class="form-control border-start-0 ps-0"
                           placeholder="Search…"
                           id="searchInput"
                           oninput="filterProducts()"/>
                </div>
            </div>

            <!-- Category filter buttons -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <button class="btn btn-sm btn-warning  cat-btn active" data-cat="all">All</button>
                <button class="btn btn-sm btn-outline-secondary cat-btn" data-cat="1">☕ Hot</button>
                <button class="btn btn-sm btn-outline-primary   cat-btn" data-cat="2">🧊 Cold</button>
                <button class="btn btn-sm btn-outline-success   cat-btn" data-cat="3">🍪 Snacks</button>
            </div>

            <!-- Featured row -->
            <div class="page-card mb-3 shadow-sm border rounded">
                <div class="page-card-header d-flex align-items-center gap-2 p-2 bg-light border-bottom">
                    <i class="bi bi-stars text-warning"></i>
                    <span class="fw-semibold small">Featured</span>
                </div>
                <div class="p-3 d-flex gap-3 flex-wrap justify-content-start">
                    <?php foreach (array_slice($products, 0, 4) as $p): ?>
                        <div class="text-center" style="min-width:70px; cursor:pointer;"
                             onclick="addToOrder(
                                 <?= $p['id'] ?>,
                                 '<?= htmlspecialchars(addslashes($p['name'])) ?>',
                                 <?= $p['price'] ?>
                             )">
                            <div class="product-img mb-1">
                                <img src="../../public/assets/images/products/<?= htmlspecialchars($p['image']) ?>"
                                     style="width:40px; height:40px; object-fit:contain;"
                                     alt="<?= htmlspecialchars($p['name']) ?>">
                            </div>
                            <div class="small fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Full product grid -->
            <div class="row g-3" id="productsGrid">
                <?php foreach ($products as $p): ?>
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2 product-item"
                         data-cat="<?= $p['category_id'] ?>"
                         data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">

                        <div class="card h-100 border product-card text-center p-2 shadow-sm"
                             style="cursor:pointer;"
                             onclick="addToOrder(
                                 <?= $p['id'] ?>,
                                 '<?= htmlspecialchars(addslashes($p['name'])) ?>',
                                 <?= $p['price'] ?>
                             )">
                            <div class="product-img my-2">
                                <img src="../../public/assets/images/products/<?= htmlspecialchars($p['image']) ?>"
                                     style="width:60px; height:60px; object-fit:contain;"
                                     alt="<?= htmlspecialchars($p['name']) ?>">
                            </div>
                            <div class="fw-semibold small mb-1"><?= htmlspecialchars($p['name']) ?></div>
                            <span class="badge bg-dark price-badge"><?= $p['price'] ?> LE</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div><!-- /col-lg-9 -->


          <div class="col-12 col-lg-3 order-1 order-lg-2">
            <div id="orderPanel"
                 class="bg-white rounded-3 border p-3 d-flex flex-column shadow-sm"
                 style="position:sticky; top:20px; height:fit-content;">

                <!-- Panel title -->
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard-plus text-warning"></i>Order Summary
                </h6>

                <!-- ── USER SELECTOR (#41) ──────────────────
                     This is the only new UI element vs home.php.
                     The selected value is read by saveOrderToDB().
                ──────────────────────────────────────────── -->
                <label class="form-label small fw-semibold mb-1">
                    <i class="bi bi-person-check me-1 text-warning"></i>
                    Place order for <span class="text-danger">*</span>
                </label>

                <?php if (empty($users)): ?>
                    <!-- Edge case: no users in the system yet -->
                    <div class="alert alert-warning small py-2 mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        No active users found. Please add users first.
                    </div>
                <?php else: ?>
                    <select class="form-select form-select-sm mb-3" id="selectedUser">
                        <option value="">— Select employee —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int) $u['id'] ?>">
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <!-- ── CART ITEMS (same as home.php) ──────── -->
                <div id="orderList" class="mb-3 flex-grow-1" style="min-height:80px;">
                    <div id="emptyMsg" class="text-muted small text-center py-3">
                        <i class="bi bi-cup-hot d-block mb-1" style="font-size:1.8rem; opacity:.3;"></i>
                        No items yet
                    </div>
                </div>

                <!-- Order total -->
                <div id="orderTotalRow" class="d-none border-top pt-2 mb-3">
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span>
                        <span id="orderTotalAmt">0 EGP</span>
                    </div>
                </div>

                <hr class="my-2"/>

                <!-- Notes (same as home.php) -->
                <label class="form-label small fw-semibold mb-1">Notes</label>
                <textarea class="form-control form-control-sm mb-3"
                          id="orderNotes" rows="3"
                          placeholder="e.g. 1 Tea Extra Sugar"></textarea>

                <!-- Room (same as home.php) -->
                <label class="form-label small fw-semibold mb-1">Room</label>
                <select class="form-select form-select-sm mb-3" id="orderRoom">
                    <option value="">— Select room —</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= (int) $r['id'] ?>">
                            <?= htmlspecialchars($r['details']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Confirm button -->
                <button class="btn btn-warning fw-semibold w-100" onclick="confirmOrder()">
                    <i class="bi bi-check-circle me-1"></i>Confirm Order
                </button>

            </div>
        </div ><!-- /col-lg-3 -->

    </div><!-- /row -->
</div><!-- /container-fluid -->

<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>


<script>


// Same structure as home.php: { productId: {name, price, qty} }
let order = {};


// ── addToOrder() — identical to home.php ─────────────────
function addToOrder(id, name, price) {
    if (order[id]) {
        order[id].qty++;
    } else {
        order[id] = { name, price, qty: 1 };
    }
    renderOrder();
}


// ── changeQty() — identical to home.php ──────────────────
function changeQty(id, delta) {
    if (!order[id]) return;
    order[id].qty += delta;
    if (order[id].qty <= 0) delete order[id];
    renderOrder();
}


// ── renderOrder() — identical to home.php ────────────────
function renderOrder() {
    const list     = document.getElementById('orderList');
    const totalRow = document.getElementById('orderTotalRow');
    const totalAmt = document.getElementById('orderTotalAmt');
    const keys     = Object.keys(order);

    list.innerHTML = '';

    if (keys.length === 0) {
        list.innerHTML = `
            <div id="emptyMsg" class="text-muted small text-center py-3">
                <i class="bi bi-cup-hot d-block mb-1" style="font-size:1.8rem;opacity:.3;"></i>
                No items yet
            </div>`;
        totalRow.classList.add('d-none');
        return;
    }

    totalRow.classList.remove('d-none');
    let grandTotal = 0;

    keys.forEach(id => {
        const item  = order[id];
        grandTotal += item.price * item.qty;

        list.insertAdjacentHTML('beforeend', `
            <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                <span class="small fw-bold">${item.name}</span>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-light" onclick="changeQty(${id}, -1)">-</button>
                    <span>${item.qty}</span>
                    <button class="btn btn-sm btn-light" onclick="changeQty(${id},  1)">+</button>
                </div>
                <span class="small">${item.price * item.qty} LE</span>
            </div>`);
    });

    totalAmt.innerText = grandTotal + ' LE';
}


// ── confirmOrder() — extended for admin (#42) ─────────────
// New validation step: check that an employee is selected.
async function confirmOrder() {

    // 1. Cart must not be empty
    if (Object.keys(order).length === 0) {
        Swal.fire('Empty Order', 'Please add at least one item.', 'warning');
        return;
    }

    // 2. A room must be selected
    const roomSelect = document.getElementById('orderRoom');
    if (!roomSelect.value) {
        Swal.fire('Room Required', 'Please choose a room / location.', 'info');
        return;
    }

    // 3. NEW ── An employee must be selected (#41 / #42)
    const userSelect = document.getElementById('selectedUser');
    if (!userSelect || !userSelect.value) {
        Swal.fire(
            'Employee Required',
            'Please select the employee you are placing this order for.',
            'info'
        );
        return;
    }

    // 4. Confirmation dialog — shows the employee name for clarity
    const employeeName = userSelect.options[userSelect.selectedIndex].text;

    const { isConfirmed } = await Swal.fire({
        title           : 'Confirm Manual Order?',
        html            : `Place this order on behalf of <b>${employeeName}</b>?`,
        icon            : 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor : '#6c757d',
        confirmButtonText : 'Yes, Place Order!',
        cancelButtonText  : 'Cancel',
    });

    if (isConfirmed) {
        saveOrderToDB();
    }
}


// ── saveOrderToDB() — extended for admin (#42) ────────────
// The only difference vs home.php: we add `target_user_id`
// to the payload so the backend knows whose order this is.
async function saveOrderToDB() {

    const userSelect = document.getElementById('selectedUser');

    const payload = {
        // NEW field — the employee this order belongs to
        target_user_id : parseInt(userSelect.value, 10),

        // Same fields as home.php
        location_id    : document.getElementById('orderRoom').value,
        notes          : document.getElementById('orderNotes').value,
        products       : order,
    };

    // Loading spinner
    Swal.fire({
        title           : 'Processing...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen         : () => Swal.showLoading(),
    });

    try {
        // Same endpoint as home.php — OrderController.php handles both modes
        const response = await fetch('../../app/Controllers/OrderController.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify(payload),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const result = await response.json();

        if (result.success) {

            const employeeName = userSelect.options[userSelect.selectedIndex].text;

            Swal.fire({
                icon             : 'success',
                title            : 'Order Placed!',
                html             : `Order <b>#${result.order_id}</b> was placed for <b>${employeeName}</b>.`,
                confirmButtonColor: '#ffc107',
            });

            // Reset everything
            order = {};
            renderOrder();
            document.getElementById('orderNotes').value  = '';
            document.getElementById('orderRoom').value   = '';
            document.getElementById('selectedUser').value = '';

        } else {
            Swal.fire('Failed', result.message || 'Could not place order.', 'error');
        }

    } catch (err) {
        console.error('[manual_order] saveOrderToDB error:', err);
        Swal.fire('Connection Error', 'Could not reach the server. Please try again.', 'error');
    }
}


// ── filterProducts() — identical to home.php ─────────────
function filterProducts() {
    const searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
    const activeBtn   = document.querySelector('.cat-btn.active');
    const activeCat   = activeBtn ? activeBtn.dataset.cat : 'all';

    document.querySelectorAll('.product-item').forEach(el => {
        const nameMatch = !searchQuery || el.dataset.name.includes(searchQuery);
        const catMatch  = activeCat === 'all' || el.dataset.cat === activeCat;
        el.style.display = (nameMatch && catMatch) ? '' : 'none';
    });
}


// ── Category button click — identical to home.php ─────────
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php include '../layouts/footer.php'; ?>