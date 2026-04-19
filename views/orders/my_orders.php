<?php

/**
 * views/orders/my_orders.php
 * Regular user — view their own order history, filter by date, cancel processing orders.
 *
 * Two modes:
 *   1. Normal page load → renders the shell (stats cards + table + filters)
 *   2. AJAX request (X-Requested-With: XMLHttpRequest + GET ?page=my-orders&ajax=1)
 *      → returns JSON { table, pagination, stats }
 *
 * Auth + data fetching handled by OrderController::myOrders() which calls
 * this file. AJAX sub-requests hit ?page=my-orders&ajax=1 through index.php.
 *
 * Variables injected on normal load (from OrderController::myOrders()):
 *   $orders    – array of order rows with nested $order['items']
 *   $pageTitle – string
 *   $activeNav – string
 */

// ── AJAX branch ───────────────────────────────────────────────────────────────
// Handles ?page=my-orders&ajax=1  (table refresh + cancel action)
if (isset($_GET['ajax'])) {
    requireLogin();

    header('Content-Type: application/json');

    $db     = Database::connect();
    $userId = (int) $_SESSION['user_id'];

    // ── Cancel action ─────────────────────────────────────────────────────
    if (($_GET['action'] ?? '') === 'cancel') {
        $orderId = (int) ($_GET['order_id'] ?? 0);

        $upd = $db->prepare(
            "UPDATE orders
             SET    status = 'canceled'
             WHERE  id      = ?
               AND  user_id = ?
               AND  status  = 'processing'"
        );
        $upd->execute([$orderId, $userId]);

        echo json_encode(['success' => $upd->rowCount() === 1]);
        exit;
    }

    // ── Fetch orders (paginated + filtered) ───────────────────────────────
    $limit  = 6;
    $page   = max(1, (int) ($_GET['p'] ?? 1));
    $offset = ($page - 1) * $limit;

    $where  = ['o.user_id = ?'];
    $params = [$userId];

    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo   = $_GET['date_to']   ?? '';

    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[]  = 'DATE(o.order_date) >= ?';
        $params[] = $dateFrom;
    }
    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[]  = 'DATE(o.order_date) <= ?';
        $params[] = $dateTo;
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    // Stats (across full filtered set, not just current page)
    $statStmt = $db->prepare(
        "SELECT COUNT(*)                      AS total,
                COALESCE(SUM(o.total_price),0) AS spent,
                SUM(o.status = 'processing')   AS active
         FROM orders o
         $whereSQL"
    );
    $statStmt->execute($params);
    $stats = $statStmt->fetch(PDO::FETCH_ASSOC);

    $totalOrders = (int)   $stats['total'];
    $totalSpent  = (float) $stats['spent'];
    $activeCount = (int)   $stats['active'];
    $avgOrder    = $totalOrders > 0 ? $totalSpent / $totalOrders : 0;

    // Orders for this page
    $stmt = $db->prepare(
        "SELECT o.id, o.order_date, o.status, o.total_price, o.notes,
                o.location_snapshot, l.details AS location
         FROM orders o
         LEFT JOIN locations l ON l.id = o.location_id
         $whereSQL
         ORDER BY o.order_date DESC
         LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Items per order
    $itemStmt = $db->prepare(
        "SELECT oi.quantity AS qty, oi.price_at_purchase AS price,
                p.name, p.image
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?"
    );

    // Status → badge colour
    $badgeMap = [
        'processing'       => 'warning text-dark',
        'out_for_delivery' => 'info    text-dark',
        'done'             => 'success',
        'canceled'         => 'secondary',
    ];
    $labelMap = [
        'processing'       => 'Processing',
        'out_for_delivery' => 'Out for Delivery',
        'done'             => 'Done',
        'canceled'         => 'Canceled',
    ];
    $iconMap = [
        'processing'       => 'bi-hourglass-split',
        'out_for_delivery' => 'bi-bicycle',
        'done'             => 'bi-check-circle-fill',
        'canceled'         => 'bi-x-circle',
    ];

    // ── Build table HTML ──────────────────────────────────────────────────
    ob_start();

    if (empty($orders)) {
        echo '<tr>
                <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>
                    No orders found.
                </td>
              </tr>';
    } else {
        foreach ($orders as $o) {
            $id     = (int) $o['id'];
            $date   = $o['order_date']
                        ? date('d M Y, h:i A', strtotime($o['order_date']))
                        : '—';
            $status = $o['status'];
            $badge  = $badgeMap[$status]  ?? 'secondary';
            $label  = $labelMap[$status]  ?? ucfirst($status);
            $icon   = $iconMap[$status]   ?? 'bi-circle';
            $amount = number_format((float)$o['total_price'], 2);
            $loc    = htmlspecialchars($o['location_snapshot'] ?? $o['location'] ?? '—');
            $notes  = htmlspecialchars($o['notes'] ?? '');

            // Cancel button only on processing orders
            $actionBtn = $status === 'processing'
                ? "<button class='btn btn-sm btn-outline-danger fw-semibold'
                           onclick='cancelOrder($id)'>
                       <i class='bi bi-x-circle me-1'></i>Cancel
                   </button>"
                : '<span class="text-muted small">—</span>';

            // Items
            $itemStmt->execute([$id]);
            $items    = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            $itemsHtml = '';

            foreach ($items as $it) {
                $iName  = htmlspecialchars($it['name']);
                $iPrice = number_format((float)$it['price'], 2);
                $iQty   = (int) $it['qty'];
                $imgHtml = !empty($it['image'])
                    ? "<img src='../public/{$it['image']}' 
                            style='width:44px;height:44px;object-fit:cover;'
                            class='rounded mb-1'
                            alt='{$iName}' />"
                    : "<div class='rounded bg-light border d-flex align-items-center
                                  justify-content-center mb-1 text-muted mx-auto'
                             style='width:44px;height:44px;font-size:1.1rem;'>
                           <i class='bi bi-cup-hot'></i>
                       </div>";

                $itemsHtml .= "
                    <div class='text-center p-2 bg-white rounded border shadow-sm'
                         style='min-width:82px;'>
                        {$imgHtml}
                        <div class='small fw-semibold'>{$iName}</div>
                        <span class='badge bg-dark mt-1' style='font-size:.6rem;'>
                            {$iPrice} EGP
                        </span>
                        <div class='fw-bold small mt-1'>× {$iQty}</div>
                    </div>";
            }

            // Notes block (only if not empty)
            $notesHtml = $notes
                ? "<div class='mt-2 small text-muted border-top pt-2'>
                       <i class='bi bi-chat-left-text me-1'></i>
                       <em>{$notes}</em>
                   </div>"
                : '';

            echo "
            <tr>
                <td>
                    <button class='btn btn-sm btn-light border p-0 px-1 expand-row-btn'
                            data-target='detail_{$id}'
                            style='width:24px;line-height:1.4;'>
                        <i class='bi bi-chevron-right' style='font-size:.75rem;'></i>
                    </button>
                </td>
                <td>
                    <div class='fw-semibold small'>{$date}</div>
                    <div class='text-muted' style='font-size:.72rem;'>
                        #$id · {$loc}
                    </div>
                </td>
                <td>
                    <span class='badge rounded-pill bg-{$badge}'>
                        <i class='bi {$icon} me-1' style='font-size:.65rem;'></i>
                        {$label}
                    </span>
                </td>
                <td class='text-end fw-bold text-warning'>{$amount} EGP</td>
                <td class='text-center'>{$actionBtn}</td>
            </tr>
            <tr id='detail_{$id}' class='d-none bg-light'>
                <td colspan='5'>
                    <div class='p-3'>
                        <div class='d-flex gap-3 flex-wrap'>{$itemsHtml}</div>
                        {$notesHtml}
                        <div class='text-end fw-bold small border-top pt-2 mt-2'>
                            Order Total: <span class='text-warning'>{$amount} EGP</span>
                        </div>
                    </div>
                </td>
            </tr>";
        }
    }

    $tableHtml = ob_get_clean();

    // ── Pagination HTML ───────────────────────────────────────────────────
    $totalPages = max(1, (int) ceil($totalOrders / $limit));
    ob_start();

    echo "<li class='page-item " . ($page <= 1 ? 'disabled' : '') . "'>
              <button class='page-link' onclick='loadOrders(" . ($page - 1) . ")'>‹</button>
          </li>";

    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
        $active = $i === $page ? 'active' : '';
        echo "<li class='page-item $active'>
                  <button class='page-link' onclick='loadOrders($i)'>$i</button>
              </li>";
    }

    echo "<li class='page-item " . ($page >= $totalPages ? 'disabled' : '') . "'>
              <button class='page-link' onclick='loadOrders(" . ($page + 1) . ")'>›</button>
          </li>";

    $paginationHtml = ob_get_clean();

    echo json_encode([
        'table'      => $tableHtml,
        'pagination' => $paginationHtml,
        'stats'      => [
            'total'  => $totalOrders,
            'spent'  => number_format($totalSpent, 2),
            'active' => $activeCount,
            'avg'    => number_format($avgOrder, 2),
        ],
    ]);
    exit;
}

// ── Normal page load ──────────────────────────────────────────────────────────
$pageTitle = $pageTitle ?? 'My Orders';
$activeNav = $activeNav ?? 'my-orders';

include __DIR__ . '/../layouts/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ── Page header ───────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-receipt me-2 text-warning"></i>My Orders
        </h4>
        <p class="text-muted small mb-0">Track all your orders and their status</p>
    </div>
    <a href="?page=home" class="btn btn-warning fw-semibold ms-auto">
        <i class="bi bi-plus-lg me-1"></i>New Order
    </a>
</div>

<!-- ── Stats row ─────────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['id' => 'stat_total',  'label' => 'Total Orders', 'icon' => 'bi-receipt',      'color' => 'warning'],
        ['id' => 'stat_spent',  'label' => 'Total Spent',  'icon' => 'bi-cash-stack',   'color' => 'success'],
        ['id' => 'stat_active', 'label' => 'Active',       'icon' => 'bi-hourglass-split','color' => 'info'],
        ['id' => 'stat_avg',    'label' => 'Avg. Order',   'icon' => 'bi-graph-up',     'color' => 'danger'],
    ];
    foreach ($statCards as $card):
    ?>
    <div class="col-6 col-md-3">
        <div class="page-card p-3">
            <div class="text-muted small mb-1">
                <i class="bi <?= $card['icon'] ?> me-1 text-<?= $card['color'] ?>"></i>
                <?= $card['label'] ?>
            </div>
            <div class="fw-bold fs-5" id="<?= $card['id'] ?>">
                <span class="spinner-border spinner-border-sm text-warning"></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Filter bar ────────────────────────────────────────────────────────────── -->
<div class="page-card mb-3">
    <div class="page-card-header">
        <div class="d-flex align-items-end gap-3 flex-wrap">
            <div>
                <label class="form-label small fw-semibold mb-1">From</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="date" id="date_from" class="form-control" />
                </div>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">To</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="date" id="date_to" class="form-control" />
                </div>
            </div>
            <div class="align-self-end d-flex gap-2">
                <button onclick="loadOrders(1)" class="btn btn-warning btn-sm fw-semibold px-3">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <button onclick="resetFilter()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i> Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Orders table ───────────────────────────────────────────────────────────── -->
<div class="page-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:36px;"></th>
                    <th>Order</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody">
                <!-- filled by loadOrders() on DOMContentLoaded -->
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="spinner-border spinner-border-sm text-warning me-2"></div>
                        Loading your orders…
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center py-3 border-top">
        <nav>
            <ul class="pagination pagination-sm mb-0" id="paginationNav"></ul>
        </nav>
    </div>
</div>

<!-- Toast container -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3"
     style="z-index:9999;"></div>

<script>
// Detect base URL so fetch works in any subdirectory
const BASE_URL = window.location.pathname.replace(/\/index\.php.*$/, '') + '/index.php';

// ── loadOrders ────────────────────────────────────────────────────────────────
function loadOrders(page = 1) {
    const from  = document.getElementById('date_from').value;
    const to    = document.getElementById('date_to').value;
    const tbody = document.getElementById('ordersTableBody');

    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-5">
                <div class="spinner-border spinner-border-sm text-warning me-2"></div>
                Loading…
            </td>
        </tr>`;

    const url = `${BASE_URL}?page=my-orders&ajax=1&p=${page}`
              + (from ? `&date_from=${encodeURIComponent(from)}` : '')
              + (to   ? `&date_to=${encodeURIComponent(to)}`     : '');

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);

            tbody.innerHTML = data.table;
            document.getElementById('paginationNav').innerHTML = data.pagination;

            // Stats
            document.getElementById('stat_total').textContent  = data.stats.total;
            document.getElementById('stat_spent').textContent  = data.stats.spent  + ' EGP';
            document.getElementById('stat_active').textContent = data.stats.active;
            document.getElementById('stat_avg').textContent    = data.stats.avg    + ' EGP';

            // Bind expand/collapse buttons on newly injected rows
            bindExpandButtons();
        })
        .catch(err => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Failed to load orders. Please try again.
                    </td>
                </tr>`;
            console.error('[my_orders] loadOrders error:', err);
        });
}

// ── bindExpandButtons ─────────────────────────────────────────────────────────
// Must be called after each AJAX inject because the rows are new DOM nodes.
function bindExpandButtons() {
    document.querySelectorAll('.expand-row-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const target   = document.getElementById(targetId);
            if (!target) return;

            const isOpen = !target.classList.contains('d-none');
            target.classList.toggle('d-none', isOpen);

            const icon = this.querySelector('i');
            if (icon) {
                icon.className = isOpen
                    ? 'bi bi-chevron-right'
                    : 'bi bi-chevron-down';
                icon.style.fontSize = '.75rem';
            }
        });
    });
}

// ── cancelOrder ───────────────────────────────────────────────────────────────
function cancelOrder(id) {
    Swal.fire({
        title:             'Cancel this order?',
        text:              `Order #${id} will be canceled.`,
        icon:              'warning',
        showCancelButton:  true,
        confirmButtonColor:'#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel it',
        cancelButtonText:  'Keep it',
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch(`${BASE_URL}?page=my-orders&ajax=1&action=cancel&order_id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon:              'success',
                    title:             'Order Canceled',
                    text:              `Order #${id} has been canceled.`,
                    timer:             2000,
                    showConfirmButton: false,
                });
                loadOrders(); // refresh table
            } else {
                Swal.fire(
                    'Cannot Cancel',
                    'This order may have already been processed or delivered.',
                    'error'
                );
            }
        })
        .catch(() => Swal.fire('Connection Error', 'Please try again.', 'error'));
    });
}

// ── resetFilter ───────────────────────────────────────────────────────────────
function resetFilter() {
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value   = '';
    loadOrders(1);
}

// ── Boot ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => loadOrders(1));
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>