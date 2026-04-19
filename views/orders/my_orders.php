<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../../config/Database.php';


function getOrderItems(PDO $db, int $orderId): array
{
    $stmt = $db->prepare("
        SELECT oi.quantity        AS qty,
               oi.price_at_purchase AS price,
               p.name,
               p.image
        FROM   order_items oi
        JOIN   products    p  ON oi.product_id = p.id
        WHERE  oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function statusBadgeColor(string $status): string
{
    return match ($status) {
        'processing'       => 'warning text-dark',
        'out_for_delivery' => 'info text-dark',
        'canceled'         => 'danger',          // FIX 3 (single-L, matches ENUM)
        'done'             => 'success',
        default            => 'secondary',
    };
}


if (isset($_GET['ajax'])) {

    header('Content-Type: application/json');

    try {
        $db     = Database::connect();
        $userId = (int) $_SESSION['user_id'];

        if (isset($_GET['action']) && $_GET['action'] === 'cancel') {

            $orderId = (int) $_GET['order_id'];          

            $upd = $db->prepare("
                UPDATE orders
                SET    status = 'canceled'
                WHERE  id      = ?
                  AND  user_id = ?
                  AND  status  = 'processing'
            ");

            $upd->execute([$orderId, $userId]);
            $affected = $upd->rowCount();      

            echo json_encode(['success' => $affected === 1]);
            exit;
        }   

        $limit  = 5;
        $page   = max(1, (int) ($_GET['page'] ?? 1));   // FIX 1: safe integer
        $offset = ($page - 1) * $limit;

        $where  = "WHERE o.user_id = ?";
        $params = [$userId];                             // shared for ALL queries

        if (!empty($_GET['date_from'])) {
            // FIX 2: value goes into $params, not into $where string
            $where   .= " AND DATE(o.order_date) >= ?";
            $params[] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $where   .= " AND DATE(o.order_date) <= ?";
            $params[] = $_GET['date_to'];
        }

        
        $statStmt = $db->prepare("
            SELECT COUNT(*)         AS total,
                   COALESCE(SUM(total_price), 0) AS spent
            FROM   orders o
            $where
        ");
        $statStmt->execute($params);                     // FIX 6: same $params
        $stats = $statStmt->fetch(PDO::FETCH_ASSOC);

        $activeStmt = $db->prepare("
            SELECT COUNT(*)
            FROM   orders o
            $where AND o.status = 'processing'
        ");
        $activeStmt->execute($params);                   // FIX 6: same $params
        $activeCount = (int) $activeStmt->fetchColumn();

       

        $stmt = $db->prepare("
            SELECT o.id,
                   o.order_date  AS date,
                   o.status,
                   o.total_price AS amount,
                   o.notes
            FROM   orders o
            $where
            ORDER  BY o.order_date DESC
            LIMIT  $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tableHtml = '';

        if (empty($orders)) {
            $tableHtml = '
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>
                        No orders found.
                    </td>
                </tr>';
        } else {
            foreach ($orders as $o) {

                $safeId     = (int) $o['id'];
                $safeDate   = htmlspecialchars(date('Y/m/d h:i A', strtotime($o['date'])));
                $safeStatus = htmlspecialchars($o['status']);
                $safeAmount = htmlspecialchars($o['amount']);
                $badgeColor = statusBadgeColor($o['status']);  // FIX 5

                $actionBtn = ($o['status'] === 'processing')
                    ? "<button class='btn btn-sm btn-outline-danger fw-semibold'
                               onclick='cancelOrder($safeId)'>
                           <i class='bi bi-x-circle me-1'></i>CANCEL
                       </button>"
                    : '<span class="text-muted small">—</span>';

                $itemsHtml = '';
                $items     = getOrderItems($db, $safeId);

                foreach ($items as $it) {
                    // FIX 4 — escape every user/DB value in HTML
                    $safeName  = htmlspecialchars($it['name']);
                    $safePrice = htmlspecialchars($it['price']);
                    $safeQty   = (int) $it['qty'];
                    $safeImg   = htmlspecialchars($it['image']);

                    $itemsHtml .= "
                        <div class='text-center p-2 bg-white rounded border shadow-sm'
                             style='min-width:100px;'>
                            <img src='../../public/assets/images/products/$safeImg'
                                 onerror=\"this.src='../../public/assets/images/default.png'\"
                                 width='40' height='40' class='mb-1 rounded'>
                            <div class='small fw-semibold'>$safeName</div>
                            <span class='badge bg-dark' style='font-size:.65rem;'>
                                $safePrice LE
                            </span>
                            <div class='fw-bold mt-1'>x$safeQty</div>
                        </div>";
                }

                $tableHtml .= "
                <tr>
                    <td>
                        <button class='btn btn-sm btn-light border p-0 px-1'
                                data-bs-toggle='collapse'
                                data-bs-target='#detail_$safeId'>
                            <i class='bi bi-chevron-right'></i>
                        </button>
                    </td>
                    <td>
                        <span class='fw-semibold small'>$safeDate</span>
                        <div class='text-muted small'>#$safeId</div>
                    </td>
                    <td>
                        <span class='badge rounded-pill bg-$badgeColor'>
                            <i class='bi bi-circle-fill me-1' style='font-size:.5rem;'></i>
                            $safeStatus
                        </span>
                    </td>
                    <td class='text-end fw-bold text-primary'>$safeAmount EGP</td>
                    <td class='text-center'>$actionBtn</td>
                </tr>
                <tr id='detail_$safeId' class='collapse bg-light'>
                    <td colspan='5'>
                        <div class='p-3'>
                            <div class='d-flex gap-3 flex-wrap'>$itemsHtml</div>
                        </div>
                    </td>
                </tr>";
            }
        }

        $totalPages     = (int) ceil($stats['total'] / $limit);
        $paginationHtml = '';

        for ($i = 1; $i <= $totalPages; $i++) {
            $activeClass     = ($i === $page) ? 'active' : '';
            $paginationHtml .= "
                <li class='page-item $activeClass'>
                    <button class='page-link' onclick='loadOrders($i)'>$i</button>
                </li>";
        }

        echo json_encode([
            'table'      => $tableHtml,
            'pagination' => $paginationHtml,
            'stats'      => [
                'total'  => (int)   $stats['total'],
                'spent'  => number_format((float) $stats['spent'], 2),
                'active' => $activeCount,
                'avg'    => $stats['total'] > 0
                            ? number_format($stats['spent'] / $stats['total'], 1)
                            : '0.0',
            ],
        ]);

    } catch (Exception $e) {
        error_log('[my_orders] ' . $e->getMessage());   // log, don't expose
        echo json_encode(['error' => 'Server error. Please try again.']);
    }

    exit;
}

include '../layouts/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container py-4">

    <!-- Stats row -->
    <div class="row g-3 mb-4">
        <?php
        $statCards = [
            'Total Orders' => 'stat_total',
            'Total Spent'  => 'stat_spent',
            'Active'       => 'stat_active',
            'Avg. Order'   => 'stat_avg',
        ];
        foreach ($statCards as $label => $id):
        ?>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-warning">
                <span class="small text-muted d-block mb-1"><?= $label ?></span>
                <div class="fw-bold fs-5" id="<?= $id ?>">—</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filter bar -->
    <div class="bg-white rounded shadow-sm p-3 mb-3">
        <div class="d-flex align-items-end gap-3 flex-wrap">
            <div>
                <label class="small text-muted mb-1">From</label>
                <input type="date" id="date_from" class="form-control form-control-sm"/>
            </div>
            <div>
                <label class="small text-muted mb-1">To</label>
                <input type="date" id="date_to" class="form-control form-control-sm"/>
            </div>
            <button onclick="loadOrders(1)" class="btn btn-warning btn-sm px-4 fw-bold">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <button onclick="resetFilter()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise me-1"></i>Reset
            </button>
        </div>
    </div>

    <!-- Orders table -->
    <div class="bg-white rounded shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark text-uppercase small">
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <!-- filled by loadOrders() -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center" id="paginationNav"></ul>
    </nav>

</div>


<script>
// ── loadOrders() ─────────────────────────────────────────
function loadOrders(page = 1) {
    const from  = document.getElementById('date_from').value;
    const to    = document.getElementById('date_to').value;
    const tbody = document.getElementById('ordersTableBody');

    // Show a spinner while loading
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-5">
                <div class="spinner-border spinner-border-sm text-warning me-2"></div>
                Loading…
            </td>
        </tr>`;

    fetch(`my_orders.php?ajax=1&page=${page}&date_from=${from}&date_to=${to}`)
        .then(res => res.json())
        .then(data => {

            if (data.error) {
                throw new Error(data.error);
            }

            tbody.innerHTML = data.table;
            document.getElementById('paginationNav').innerHTML = data.pagination;

            // Update stat cards
            document.getElementById('stat_total').innerText  = data.stats.total;
            document.getElementById('stat_spent').innerText  = data.stats.spent  + ' EGP';
            document.getElementById('stat_active').innerText = data.stats.active;
            document.getElementById('stat_avg').innerText    = data.stats.avg    + ' EGP';
        })
        .catch(err => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Error loading data. Please try again.
                    </td>
                </tr>`;
            console.error('[my_orders] loadOrders error:', err);
        });
}


// ── cancelOrder() ────────────────────────────────────────
function cancelOrder(id) {
    Swal.fire({
        title            : 'Cancel this order?',
        text             : `Order #${id} will be cancelled.`,
        icon             : 'warning',
        showCancelButton : true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor : '#6c757d',
        confirmButtonText : 'Yes, cancel it',
        cancelButtonText  : 'No, keep it',
    }).then(result => {

        if (!result.isConfirmed) return;

        fetch(`my_orders.php?ajax=1&action=cancel&order_id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon             : 'success',
                        title            : 'Order cancelled',
                        text             : `Order #${id} has been cancelled.`,
                        timer            : 2000,
                        showConfirmButton: false,
                    });
                    loadOrders();   // refresh the table
                } else {
                    Swal.fire(
                        'Cannot cancel',
                        'This order may have already been processed.',
                        'error'
                    );
                }
            })
            .catch(() => {
                Swal.fire('Connection error', 'Please try again.', 'error');
            });
    });
}


// ── resetFilter() ────────────────────────────────────────
function resetFilter() {
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value   = '';
    loadOrders(1);
}


// Load on first visit
window.addEventListener('DOMContentLoaded', () => loadOrders(1));
</script>

<?php include '../layouts/footer.php'; ?>