<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/Database.php';

try {
    $conn = Database::connect();

    $roomStmt = $conn->query("SELECT id, details FROM locations");
    $rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

    $prodStmt = $conn->query("SELECT * FROM products WHERE is_deleted = 0 AND is_available = 1");
    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = $pageTitle ?? 'Home';
$activeNav = $activeNav ?? 'home';

include 'layouts/header.php';
?>

<div class="container-fluid py-3">
    <div class="row g-4">

        <div class="col-12 col-lg-9 order-2 order-lg-1">

            <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
                <h5 class="fw-bold mb-0">Menu</h5>
                <div class="input-group" style="max-width:240px;">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Search…"
                        id="searchInput" oninput="filterProducts()" />
                </div>
            </div>

            <div class="d-flex gap-2 mb-3 flex-wrap">
                <button class="btn btn-sm btn-warning cat-btn active" data-cat="all">All</button>
                <button class="btn btn-sm btn-outline-secondary cat-btn" data-cat="1">☕ Hot</button>
                <button class="btn btn-sm btn-outline-primary   cat-btn" data-cat="2">🧊 Cold</button>
                <button class="btn btn-sm btn-outline-success   cat-btn" data-cat="3">🍪 Snacks</button>
            </div>

            <div class="page-card mb-3 shadow-sm border rounded">
                <div class="page-card-header d-flex align-items-center gap-2 p-2 bg-light border-bottom">
                    <i class="bi bi-stars text-warning"></i>
                    <span class="fw-semibold small">Featured</span>
                </div>
                <div class="p-3 d-flex gap-3 flex-wrap justify-content-start">
                    <?php foreach (array_slice($products, 0, 4) as $p): ?>
                        <div class="text-center" style="min-width:70px;cursor:pointer;"
                            onclick="addToOrder(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['name'])) ?>',<?= $p['price'] ?>)">
                            <div class="product-img mb-1">
                                <img src="../public/assets/images/products/<?= htmlspecialchars($p['image']) ?>"
                                    style="width:40px;height:40px;object-fit:contain;" alt="<?= htmlspecialchars($p['name']) ?>">
                            </div>
                            <div class="small fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row g-3" id="productsGrid">
                <?php foreach ($products as $p): ?>
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2 product-item"
                        data-cat="<?= $p['category_id'] ?>"
                        data-name="<?= strtolower($p['name']) ?>">
                        <div class="card h-100 border product-card text-center p-2 shadow-sm"
                            style="cursor:pointer;"
                            onclick="addToOrder(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['name'])) ?>',<?= $p['price'] ?>)">
                            <div class="product-img my-2">
                                <img src="../public/assets/images/products/<?= htmlspecialchars($p['image']) ?>"
                                    style="width:60px;height:60px;object-fit:contain;" alt="<?= htmlspecialchars($p['name']) ?>">
                            </div>
                            <div class="fw-semibold small mb-1"><?= htmlspecialchars($p['name']) ?></div>
                            <span class="badge bg-dark price-badge"><?= $p['price'] ?> LE</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-12 col-lg-3 order-1 order-lg-2">
            <div id="orderPanel" class="bg-white rounded-3 border p-3 d-flex flex-column shadow-sm"
                style="position:sticky; top:20px; height: fit-content;">

                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-bag-check text-warning"></i>Latest Order
                </h6>

                <div id="orderList" class="mb-3 flex-grow-1" style="min-height:80px;">
                    <div id="emptyMsg" class="text-muted small text-center py-3">
                        <i class="bi bi-cup-hot d-block mb-1" style="font-size:1.8rem;opacity:.3;"></i>
                        No items yet
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
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['details']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button class="btn btn-warning fw-semibold w-100" onclick="confirmOrder()">
                    <i class="bi bi-check-circle me-1"></i>Confirm
                </button>
            </div>
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
    }

    function changeQty(id, delta) {
        if (!order[id]) return;
        order[id].qty += delta;
        if (order[id].qty <= 0) delete order[id];
        renderOrder();
    }

    function renderOrder() {
        const list = document.getElementById('orderList');
        const totalRow = document.getElementById('orderTotalRow');
        const totalAmt = document.getElementById('orderTotalAmt');
        const keys = Object.keys(order);

        list.innerHTML = '';

        if (keys.length === 0) {
            list.innerHTML = '<div id="emptyMsg" class="text-muted small text-center py-3">No items yet</div>';
            totalRow.classList.add('d-none');
            return;
        }

        totalRow.classList.remove('d-none');
        let grandTotal = 0;

        keys.forEach(id => {
            const item = order[id];
            grandTotal += item.price * item.qty;
            list.insertAdjacentHTML('beforeend', `
            <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                <span class="small fw-bold">${item.name}</span>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-light" onclick="changeQty(${id}, -1)">-</button>
                    <span>${item.qty}</span>
                    <button class="btn btn-sm btn-light" onclick="changeQty(${id}, 1)">+</button>
                </div>
                <span class="small">${item.price * item.qty} LE</span>
            </div>`);
        });
        totalAmt.innerText = grandTotal + ' LE';
    }

    async function confirmOrder() {
        if (Object.keys(order).length === 0) {
            Swal.fire('Empty Order', 'Please add items first.', 'warning');
            return;
        }
        const roomSelect = document.getElementById('orderRoom');
        if (!roomSelect.value) {
            Swal.fire('Selection Required', 'Please choose a room.', 'info');
            return;
        }

        const {
            isConfirmed
        } = await Swal.fire({
            title: 'Confirm Order?',
            text: "Send this order to the kitchen?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Yes, Place Order!'
        });

        if (isConfirmed) {
            saveOrderToDB();
        }
    }

    async function saveOrderToDB() {
        const payload = {
            location_id: document.getElementById('orderRoom').value,
            notes: document.getElementById('orderNotes').value,
            products: order
        };

        Swal.fire({
            title: 'Processing...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch('../app/Controllers/OrderController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error('File not found (404)');

            const result = await response.json();

            if (result.success) {
                Swal.fire('Success!', `Order #${result.order_id} placed.`, 'success');
                order = {};
                renderOrder();
                document.getElementById('orderNotes').value = '';
                document.getElementById('orderRoom').value = '';
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (err) {
            console.error('Fetch Error:', err);
            Swal.fire('Error', 'Could not connect to OrderController.', 'error');
        }
    }

    function filterProducts() {
        const searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
        const activeBtn = document.querySelector('.cat-btn.active');
        const activeCat = activeBtn ? activeBtn.dataset.cat : 'all';

        document.querySelectorAll('.product-item').forEach(el => {
            const nameMatch = !searchQuery || el.dataset.name.includes(searchQuery);
            const catMatch = activeCat === 'all' || el.dataset.cat === activeCat;

            if (nameMatch && catMatch) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    document.querySelectorAll('.cat-btn').forEach(btn => {
        btn.addEventListener('click', function() {
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
<?php include 'layouts/footer.php'; ?>