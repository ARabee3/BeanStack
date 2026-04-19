<?php

/**
 * views/admin/checks.php
 * Spending summary per client — only counts 'done' orders.
 *
 * All data is fetched directly here (no separate controller needed —
 * this is a read-only report page).
 *
 * Filter GET params: date_from, date_to, user_id, p (page)
 * Level-3 items (product cards per order) are lazy-loaded via AJAX
 * hitting ?page=order-items&id=X (already built in OrderController).
 */

require_once __DIR__ . '/../../config/Database.php';

$pageTitle = $pageTitle ?? 'Checks';
$activeNav = $activeNav ?? 'checks';

// ── Date defaults: current month ─────────────────────────────────────────
$today       = date('Y-m-d');
$monthStart  = date('Y-m-01');

$dateFrom   = $_GET['date_from'] ?? $monthStart;
$dateTo     = $_GET['date_to']   ?? $today;
$userFilter = (int) ($_GET['user_id'] ?? 0);

// ── Sanitise dates ───────────────────────────────────────────────────────
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = $monthStart;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = $today;
if ($dateFrom > $dateTo) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];

// ── Pagination ────────────────────────────────────────────────────────────
$perPage     = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

// ── DB ────────────────────────────────────────────────────────────────────
$db = Database::connect();

// All users for the filter dropdown
$allUsers = $db->query(
    "SELECT id, name FROM users WHERE role = 'user' ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Build WHERE clause ────────────────────────────────────────────────────
// Only 'done' orders within the date window
$where  = ["o.status = 'done'",
           "DATE(o.order_date) >= :dfrom",
           "DATE(o.order_date) <= :dto"];
$params = [':dfrom' => $dateFrom, ':dto' => $dateTo];

if ($userFilter > 0) {
    $where[]           = 'u.id = :uid';
    $params[':uid']    = $userFilter;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// ── Count distinct users who have qualifying orders ───────────────────────
$countStmt = $db->prepare(
    "SELECT COUNT(DISTINCT u.id)
     FROM orders o
     JOIN users u ON u.id = o.user_id
     $whereSQL"
);
$countStmt->execute($params);
$totalUsers  = (int) $countStmt->fetchColumn();
$totalPages  = max(1, (int) ceil($totalUsers / $perPage));
$currentPage = min($currentPage, $totalPages);

// ── Fetch aggregated data (one row per user) ──────────────────────────────
$stmt = $db->prepare(
    "SELECT u.id AS user_id,
            u.name,
            u.email,
            u.profile_pic,
            COUNT(o.id)      AS order_count,
            SUM(o.total_price) AS total_amount
     FROM orders o
     JOIN users u ON u.id = o.user_id
     $whereSQL
     GROUP BY u.id, u.name, u.email, u.profile_pic
     ORDER BY total_amount DESC
     LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
$stmt->execute();
$checksData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── For each user fetch their individual orders (header only) ─────────────
// Items are lazy-loaded per order via AJAX (?page=order-items&id=X)
$orderStmt = $db->prepare(
    "SELECT o.id, o.order_date, o.total_price, o.location_snapshot,
            l.details AS location
     FROM orders o
     LEFT JOIN locations l ON l.id = o.location_id
     WHERE o.user_id = :uid
       AND o.status  = 'done'
       AND DATE(o.order_date) >= :dfrom
       AND DATE(o.order_date) <= :dto
     ORDER BY o.order_date DESC"
);

foreach ($checksData as &$row) {
    $orderStmt->execute([
        ':uid'   => $row['user_id'],
        ':dfrom' => $dateFrom,
        ':dto'   => $dateTo,
    ]);
    $row['orders'] = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($row);

// ── Summary stats (across the full result-set, not just current page) ─────
$summaryStmt = $db->prepare(
    "SELECT COUNT(DISTINCT u.id)    AS client_count,
            COUNT(o.id)             AS order_count,
            COALESCE(SUM(o.total_price), 0) AS grand_total
     FROM orders o
     JOIN users u ON u.id = o.user_id
     $whereSQL"
);
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

$grandTotal     = (float) $summary['grand_total'];
$totalOrders    = (int)   $summary['order_count'];
$clientCount  = (int)   $summary['client_count'];
$avgPerclient = $clientCount > 0 ? round($grandTotal / $clientCount, 2) : 0;

// ── Avatar helpers ────────────────────────────────────────────────────────
function checksAvatarColor(string $name): string
{
    $colors = ['#4f46e5','#0891b2','#059669','#d97706','#dc2626','#7c3aed','#db2777','#0284c7'];
    return $colors[abs(crc32($name)) % count($colors)];
}

function checksInitials(string $name): string
{
    $parts = array_filter(explode(' ', trim($name)));
    return count($parts) >= 2
        ? strtoupper($parts[0][0] . end($parts)[0])
        : strtoupper(substr($name, 0, 2));
}

function checksUrl(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

// ── Flash ─────────────────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include __DIR__ . '/../layouts/header.php';
?>

<!-- ── Flash ─────────────────────────────────────────────────────────────── -->
<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Page header ───────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-bar-chart-line me-2 text-warning"></i>Checks
        </h4>
        <p class="text-muted small mb-0">Spending summary per client — completed orders only</p>
    </div>
</div>

<!-- ── Summary cards ─────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="page-card p-3">
            <div class="text-muted small mb-1">
                <i class="bi bi-cash-stack me-1 text-warning"></i>Grand Total
            </div>
            <div class="fw-bold fs-5"><?= number_format($grandTotal, 2) ?> EGP</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="page-card p-3">
            <div class="text-muted small mb-1">
                <i class="bi bi-receipt me-1 text-info"></i>Total Orders
            </div>
            <div class="fw-bold fs-5"><?= $totalOrders ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="page-card p-3">
            <div class="text-muted small mb-1">
                <i class="bi bi-people me-1 text-success"></i>Clients
            </div>
            <div class="fw-bold fs-5"><?= $clientCount ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="page-card p-3">
            <div class="text-muted small mb-1">
                <i class="bi bi-graph-up me-1 text-danger"></i>Avg / Client
            </div>
            <div class="fw-bold fs-5"><?= number_format($avgPerclient, 2) ?> EGP</div>
        </div>
    </div>
</div>

<!-- ── Filter bar ────────────────────────────────────────────────────────── -->
<div class="page-card mb-3">
    <div class="page-card-header">
        <form method="GET" action="" id="filterForm"
              class="d-flex align-items-end gap-3 flex-wrap">
            <input type="hidden" name="page" value="checks">
            <input type="hidden" name="p"    value="1">

            <div>
                <label class="form-label small fw-semibold mb-1">Date from</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="date" class="form-control"
                           name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" />
                </div>
            </div>

            <div>
                <label class="form-label small fw-semibold mb-1">Date to</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="date" class="form-control"
                           name="date_to" value="<?= htmlspecialchars($dateTo) ?>" />
                </div>
            </div>

            <div>
                <label class="form-label small fw-semibold mb-1">Client</label>
                <select class="form-select form-select-sm" name="user_id"
                        style="min-width:180px;">
                    <option value="">All clients</option>
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"
                            <?= $userFilter === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="align-self-end d-flex gap-2">
                <button type="submit" class="btn btn-warning btn-sm fw-semibold px-3">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?page=checks" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="exportCSV()">
                    <i class="bi bi-download me-1"></i>Export
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Main table ────────────────────────────────────────────────────────── -->
<div class="page-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="checksTable">
            <thead class="table-dark">
                <tr>
                    <th style="width:36px;"></th>
                    <th>Client</th>
                    <th class="text-center">Orders</th>
                    <th class="text-end">Total Amount</th>
                </tr>
            </thead>
            <tbody>

                <?php if (empty($checksData)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No completed orders found for the selected period.
                            <?php if ($dateFrom !== $monthStart || $dateTo !== $today || $userFilter): ?>
                                <a href="?page=checks" class="d-block mt-1 small">Clear filters</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($checksData as $ui => $u):
                    $color = checksAvatarColor($u['name']);
                    $inits = checksInitials($u['name']);
                    $userId = (int) $u['user_id'];
                ?>

                    <!-- ── Level 1: User row ─────────────────────────────── -->
                    <tr class="fw-semibold" id="userRow_<?= $userId ?>">
                        <td>
                            <button class="btn btn-sm btn-light border p-0 px-1 expand-btn"
                                    data-target="userDetail_<?= $userId ?>"
                                    style="line-height:1.4;width:24px;">
                                <i class="bi bi-chevron-right" style="font-size:.75rem;"></i>
                            </button>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($u['profile_pic'])): ?>
                                    <img src="/<?= htmlspecialchars($u['profile_pic']) ?>"
                                         class="rounded-circle border flex-shrink-0"
                                         style="width:32px;height:32px;object-fit:cover;" />
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center
                                                rounded-circle text-white fw-bold flex-shrink-0"
                                         style="width:32px;height:32px;background:<?= $color ?>;font-size:.68rem;">
                                        <?= $inits ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div><?= htmlspecialchars($u['name']) ?></div>
                                    <div class="text-muted fw-normal" style="font-size:.72rem;">
                                        <?= htmlspecialchars($u['email']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">
                                <?= (int)$u['order_count'] ?>
                            </span>
                        </td>
                        <td class="text-end text-warning fw-bold">
                            <?= number_format((float)$u['total_amount'], 2) ?> EGP
                        </td>
                    </tr>

                    <!-- ── Level 2: Orders for this user ─────────────────── -->
                    <tr id="userDetail_<?= $userId ?>" class="d-none">
                        <td colspan="4" class="p-0 ps-4 bg-light">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th style="width:36px;"></th>
                                        <th>Order Date</th>
                                        <th>Location</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($u['orders'])): ?>
                                        <tr>
                                            <td colspan="4" class="text-muted small text-center py-2">
                                                No orders in this period.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($u['orders'] as $oi => $order):
                                        $orderId = (int)$order['id'];
                                        $loc = $order['location_snapshot'] ?? $order['location'] ?? '—';
                                    ?>
                                        <!-- Order header row -->
                                        <tr id="orderRow_<?= $orderId ?>">
                                            <td>
                                                <button class="btn btn-sm btn-light border p-0 px-1 expand-btn"
                                                        data-target="orderDetail_<?= $orderId ?>"
                                                        data-order-id="<?= $orderId ?>"
                                                        style="line-height:1.4;width:24px;">
                                                    <i class="bi bi-chevron-right" style="font-size:.75rem;"></i>
                                                </button>
                                            </td>
                                            <td class="small fw-semibold">
                                                <i class="bi bi-receipt me-1 text-muted"></i>
                                                #<?= $orderId ?>
                                                &nbsp;·&nbsp;
                                                <?= $order['order_date']
                                                    ? date('d M Y, h:i A', strtotime($order['order_date']))
                                                    : '—' ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?= htmlspecialchars($loc) ?>
                                            </td>
                                            <td class="text-end small fw-semibold">
                                                <?= number_format((float)$order['total_price'], 2) ?> EGP
                                            </td>
                                        </tr>

                                        <!-- Level 3: Product cards (lazy loaded) -->
                                        <tr id="orderDetail_<?= $orderId ?>" class="d-none">
                                            <td colspan="4" class="bg-white p-3">

                                                <!-- Spinner shown until items load -->
                                                <div id="itemsLoader_<?= $orderId ?>"
                                                     class="text-center text-muted small py-2">
                                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                                    Loading items…
                                                </div>

                                                <!-- Items injected here -->
                                                <div id="itemsContent_<?= $orderId ?>"
                                                     style="display:none;">
                                                    <div class="d-flex gap-3 flex-wrap"
                                                         id="itemCards_<?= $orderId ?>"></div>
                                                    <div class="d-flex justify-content-end
                                                                fw-bold small border-top pt-2 mt-2">
                                                        Order Total:
                                                        <span class="ms-2 text-warning">
                                                            <?= number_format((float)$order['total_price'], 2) ?> EGP
                                                        </span>
                                                    </div>
                                                </div>

                                            </td>
                                        </tr>

                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <!-- ── Pagination ────────────────────────────────────────────────────── -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center py-3 border-top">
        <nav aria-label="Checks pagination">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= checksUrl(['p' => 1]) ?>">«</a>
                </li>
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= checksUrl(['p' => $currentPage - 1]) ?>">‹</a>
                </li>
                <?php for ($pg = max(1, $currentPage - 2); $pg <= min($totalPages, $currentPage + 2); $pg++): ?>
                    <li class="page-item <?= $pg === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= checksUrl(['p' => $pg]) ?>"><?= $pg ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= checksUrl(['p' => $currentPage + 1]) ?>">›</a>
                </li>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= checksUrl(['p' => $totalPages]) ?>">»</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

</div><!-- /page-card -->

<!-- ── Scripts ────────────────────────────────────────────────────────────── -->
<script>
// ── Generic expand/collapse ───────────────────────────────────────────────────
// Handles both Level-1 (user) and Level-2 (order) expand buttons.
// Level-2 buttons also carry data-order-id which triggers item lazy-load.
document.querySelectorAll('.expand-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
        const targetId = this.dataset.target;
        const target   = document.getElementById(targetId);
        if (!target) return;

        const isOpen = !target.classList.contains('d-none');

        // Toggle row
        target.classList.toggle('d-none', isOpen);
        const icon = this.querySelector('i');
        if (icon) {
            icon.className = isOpen
                ? 'bi bi-chevron-right'
                : 'bi bi-chevron-down';
            icon.style.fontSize = '.75rem';
        }

        // If this is an order-level expand and we're opening it → load items
        const orderId = this.dataset.orderId;
        if (!isOpen && orderId) {
            await loadOrderItems(parseInt(orderId, 10));
        }
    });
});

// ── Lazy-load order items via AJAX ────────────────────────────────────────────
const loadedOrders = new Set();

async function loadOrderItems(orderId) {
    if (loadedOrders.has(orderId)) return; // already loaded

    const loader  = document.getElementById(`itemsLoader_${orderId}`);
    const content = document.getElementById(`itemsContent_${orderId}`);
    const cards   = document.getElementById(`itemCards_${orderId}`);

    try {
        const res  = await fetch(`?page=order-items&id=${orderId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        loader.style.display = 'none';
        loadedOrders.add(orderId);

        if (!data.success || !data.items || !data.items.length) {
            content.style.display = '';
            cards.innerHTML = '<p class="text-muted small">No items found for this order.</p>';
            return;
        }

        cards.innerHTML = data.items.map(item => {
            const imgHtml = item.image
                ? `<img src="/${item.image}"
                        style="width:44px;height:44px;object-fit:cover;"
                        class="rounded mb-1"
                        alt="${escHtml(item.name)}" />`
                : `<div class="rounded bg-light border d-flex align-items-center
                             justify-content-center mb-1 text-muted"
                        style="width:44px;height:44px;font-size:1.2rem;">
                       <i class="bi bi-cup-hot"></i>
                   </div>`;

            return `
                <div class="text-center p-2 bg-white rounded border shadow-sm"
                     style="min-width:80px;">
                    ${imgHtml}
                    <div class="small fw-semibold">${escHtml(item.name)}</div>
                    <span class="badge bg-dark mt-1" style="font-size:.6rem;">
                        ${parseFloat(item.price).toFixed(2)} EGP
                    </span>
                    <div class="fw-bold small mt-1">× ${item.quantity}</div>
                </div>`;
        }).join('');

        content.style.display = '';

    } catch (err) {
        if (loader) loader.innerHTML =
            '<span class="text-danger small">Failed to load items. Please try again.</span>';
    }
}

// ── CSV export ────────────────────────────────────────────────────────────────
// Reads the visible table rows and downloads as a CSV file.
function exportCSV() {
    const rows = [['Client', 'Email', 'Orders', 'Total (EGP)']];

    document.querySelectorAll('tbody tr[id^="userRow_"]').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 4) return;

        const name   = cells[1].querySelector('div > div')?.firstChild?.textContent?.trim() ?? '';
        const email  = cells[1].querySelector('[style*="font-size"]')?.textContent?.trim() ?? '';
        const orders = cells[2].textContent.trim();
        const total  = cells[3].textContent.trim().replace(' EGP', '').trim();
        rows.push([name, email, orders, total]);
    });

    const csv     = rows.map(r => r.map(c => `"${c.replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob    = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url     = URL.createObjectURL(blob);
    const a       = document.createElement('a');
    a.href        = url;
    a.download    = `checks_<?= $dateFrom ?>_to_<?= $dateTo ?>.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

// ── HTML escape helper ────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>