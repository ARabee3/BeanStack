<?php

/**
 * views/admin/orders.php
 * Wired to OrderController::index()
 *
 * Variables injected:
 *   $orders         – array of order rows (joined with user + location)
 *   $countByStatus  – ['processing' => N, 'out_for_delivery' => N, ...]
 *   $totalPages     – int
 *   $currentPage    – int
 *   $pageTitle      – string
 *   $activeNav      – string
 */

$orders        = $orders        ?? [];
$countByStatus = $countByStatus ?? [];
$totalPages    = $totalPages    ?? 1;
$currentPage   = $currentPage   ?? 1;
$pageTitle     = $pageTitle     ?? 'Orders';
$activeNav     = $activeNav     ?? 'orders';

$filterStatus = $_GET['status'] ?? '';
$filterSearch = htmlspecialchars($_GET['search'] ?? '');

// Status config — DB value → [label, badge CSS class, icon]
$statusConfig = [
    'processing'       => ['label' => 'Processing',       'bg' => 'bg-warning text-dark', 'icon' => 'bi-hourglass-split'],
    'out_for_delivery' => ['label' => 'Out for Delivery',  'bg' => 'bg-info text-dark',    'icon' => 'bi-bicycle'],
    'done'             => ['label' => 'Done',              'bg' => 'bg-success',            'icon' => 'bi-check-circle-fill'],
    'canceled'         => ['label' => 'Canceled',          'bg' => 'bg-secondary',          'icon' => 'bi-x-circle'],
];

// Next status transitions allowed per current status
$nextStatus = [
    'processing'       => ['out_for_delivery' => ['label' => 'Out for Delivery', 'btn' => 'btn-info text-dark',    'icon' => 'bi-bicycle'],
                           'canceled'         => ['label' => 'Cancel',           'btn' => 'btn-outline-danger',    'icon' => 'bi-x-circle']],
    'out_for_delivery' => ['done'             => ['label' => 'Mark Done',        'btn' => 'btn-success',           'icon' => 'bi-check-circle'],
                           'canceled'         => ['label' => 'Cancel',           'btn' => 'btn-outline-danger',    'icon' => 'bi-x-circle']],
    'done'             => [],
    'canceled'         => [],
];

function ordersUrl(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

include __DIR__ . '/../layouts/header.php';
?>

<!-- ── Flash ─────────────────────────────────────────────────────────────── -->
<?php if (!empty($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Page header ───────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-lightning-charge me-2 text-warning"></i>Orders
        </h4>
        <p class="text-muted small mb-0">Live order management dashboard</p>
    </div>
    <!-- Status summary badges -->
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
            <i class="bi bi-hourglass-split me-1"></i>
            <?= (int)($countByStatus['processing'] ?? 0) ?> Processing
        </span>
        <span class="badge bg-info text-dark fs-6 px-3 py-2">
            <i class="bi bi-bicycle me-1"></i>
            <?= (int)($countByStatus['out_for_delivery'] ?? 0) ?> Delivery
        </span>
        <span class="badge bg-success fs-6 px-3 py-2">
            <i class="bi bi-check-circle me-1"></i>
            <?= (int)($countByStatus['done'] ?? 0) ?> Done
        </span>
        <a href="?page=manual-order" class="btn btn-warning fw-semibold">
            <i class="bi bi-clipboard-plus me-1"></i>Manual Order
        </a>
    </div>
</div>

<!-- ── Filter bar ────────────────────────────────────────────────────────── -->
<div class="page-card mb-3">
    <div class="page-card-header">
        <form method="GET" action="" id="filterForm" class="d-flex gap-3 flex-wrap align-items-end">
            <input type="hidden" name="page" value="orders">
            <input type="hidden" name="p"    value="1">

            <div style="flex:1;min-width:200px;">
                <label class="form-label small fw-semibold mb-1">Search customer</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search"
                           value="<?= $filterSearch ?>"
                           placeholder="Customer name…"
                           oninput="debounceSubmit()" />
                </div>
            </div>

            <div>
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select class="form-select form-select-sm" name="status"
                        onchange="this.form.submit()" style="min-width:160px;">
                    <option value=""               <?= $filterStatus === ''               ? 'selected' : '' ?>>All statuses</option>
                    <option value="processing"     <?= $filterStatus === 'processing'     ? 'selected' : '' ?>>Processing</option>
                    <option value="out_for_delivery" <?= $filterStatus === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                    <option value="done"           <?= $filterStatus === 'done'           ? 'selected' : '' ?>>Done</option>
                    <option value="canceled"       <?= $filterStatus === 'canceled'       ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>

            <div class="align-self-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="?page=orders" class="btn btn-sm btn-outline-secondary ms-1">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ── Order cards ───────────────────────────────────────────────────────── -->
<div class="d-flex flex-column gap-3" id="ordersContainer">

    <?php if (empty($orders)): ?>
        <div class="page-card text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            No orders found.
            <?php if ($filterStatus || $filterSearch): ?>
                <a href="?page=orders" class="d-block mt-1 small">Clear filters</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($orders as $order):
        $status = $order['status'];
        $cfg    = $statusConfig[$status] ?? $statusConfig['processing'];
        $transitions = $nextStatus[$status] ?? [];
    ?>
        <div class="page-card" id="orderCard_<?= (int)$order['id'] ?>">

            <!-- ── Order header row ──────────────────────────────────────── -->
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="min-width:580px;">
                    <thead class="table-secondary">
                        <tr>
                            <th class="fw-semibold small">#ID</th>
                            <th class="fw-semibold small">Date</th>
                            <th class="fw-semibold small">Customer</th>
                            <th class="fw-semibold small">Location</th>
                            <th class="text-center fw-semibold small">Status</th>
                            <th class="text-center fw-semibold small">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="small fw-bold text-muted">#<?= (int)$order['id'] ?></td>
                            <td class="small">
                                <?= $order['order_date']
                                    ? date('d M Y, h:i A', strtotime($order['order_date']))
                                    : '—' ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-person-circle text-muted"></i>
                                    <span class="fw-semibold"><?= htmlspecialchars($order['user_name']) ?></span>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">
                                    <?= htmlspecialchars($order['user_email']) ?>
                                </div>
                            </td>
                            <td class="small text-muted">
                                <?php
                                $loc = $order['location_snapshot'] ?? $order['location'] ?? null;
                                echo $loc ? htmlspecialchars($loc) : '<span class="fst-italic">—</span>';
                                ?>
                            </td>

                            <!-- Status badge — updates via JS -->
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $cfg['bg'] ?>"
                                      id="statusBadge_<?= (int)$order['id'] ?>">
                                    <i class="bi <?= $cfg['icon'] ?> me-1"></i>
                                    <?= $cfg['label'] ?>
                                </span>
                            </td>

                            <!-- Action buttons -->
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap"
                                     id="actionCell_<?= (int)$order['id'] ?>">
                                    <?php if ($transitions): ?>
                                        <?php foreach ($transitions as $toStatus => $meta): ?>
                                            <button class="btn btn-sm <?= $meta['btn'] ?> fw-semibold"
                                                    onclick="changeStatus(<?= (int)$order['id'] ?>, '<?= $toStatus ?>')">
                                                <i class="bi <?= $meta['icon'] ?> me-1"></i><?= $meta['label'] ?>
                                            </button>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">No actions</span>
                                    <?php endif; ?>

                                    <!-- View items toggle -->
                                    <button class="btn btn-sm btn-outline-secondary"
                                            onclick="toggleItems(<?= (int)$order['id'] ?>)"
                                            id="toggleBtn_<?= (int)$order['id'] ?>">
                                        <i class="bi bi-eye me-1"></i>Items
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── Items panel (collapsed by default, loaded via AJAX) ───── -->
            <div id="itemsPanel_<?= (int)$order['id'] ?>"
                 class="d-none border-top"
                 data-loaded="0">

                <!-- Loading placeholder -->
                <div class="p-3 text-center text-muted small" id="itemsLoader_<?= (int)$order['id'] ?>">
                    <span class="spinner-border spinner-border-sm me-1"></span> Loading items…
                </div>

                <!-- Items will be injected here -->
                <div class="p-3 bg-light" id="itemsContent_<?= (int)$order['id'] ?>" style="display:none;">
                    <div class="d-flex gap-3 flex-wrap" id="itemCards_<?= (int)$order['id'] ?>"></div>
                    <div class="d-flex justify-content-end mt-3 fw-bold border-top pt-2">
                        Total:
                        <span class="ms-2 text-warning">
                            <?= number_format((float)$order['total_price'], 2) ?> EGP
                        </span>
                    </div>
                    <?php if (!empty($order['notes'])): ?>
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-chat-left-text me-1"></i>
                            Notes: <?= htmlspecialchars($order['notes']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /orderCard -->
    <?php endforeach; ?>

</div><!-- /ordersContainer -->

<!-- ── Pagination ────────────────────────────────────────────────────────── -->
<?php if ($totalPages > 1): ?>
<div class="d-flex justify-content-center py-3 mt-2">
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= ordersUrl(['p' => 1]) ?>">«</a>
            </li>
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= ordersUrl(['p' => $currentPage - 1]) ?>">‹</a>
            </li>
            <?php for ($pg = max(1, $currentPage - 2); $pg <= min($totalPages, $currentPage + 2); $pg++): ?>
                <li class="page-item <?= $pg === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= ordersUrl(['p' => $pg]) ?>"><?= $pg ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= ordersUrl(['p' => $currentPage + 1]) ?>">›</a>
            </li>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= ordersUrl(['p' => $totalPages]) ?>">»</a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<!-- Toast container -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<!-- ── Status config passed to JS ────────────────────────────────────────── -->
<script>
const STATUS_CONFIG = <?= json_encode($statusConfig) ?>;
const NEXT_STATUS   = <?= json_encode($nextStatus)   ?>;

// ── Search debounce ──────────────────────────────────────────────────────────
let _dt;
function debounceSubmit() {
    clearTimeout(_dt);
    _dt = setTimeout(() => document.getElementById('filterForm').submit(), 450);
}

// ── Toggle items panel (lazy-loads via AJAX on first open) ───────────────────
async function toggleItems(orderId) {
    const panel   = document.getElementById(`itemsPanel_${orderId}`);
    const loader  = document.getElementById(`itemsLoader_${orderId}`);
    const content = document.getElementById(`itemsContent_${orderId}`);
    const btn     = document.getElementById(`toggleBtn_${orderId}`);

    const isOpen = !panel.classList.contains('d-none');

    if (isOpen) {
        panel.classList.add('d-none');
        btn.innerHTML = '<i class="bi bi-eye me-1"></i>Items';
        return;
    }

    panel.classList.remove('d-none');
    btn.innerHTML = '<i class="bi bi-eye-slash me-1"></i>Hide';

    // Only fetch once
    if (panel.dataset.loaded === '1') {
        loader.style.display  = 'none';
        content.style.display = '';
        return;
    }

    try {
        const res  = await fetch(`?page=order-items&id=${orderId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        loader.style.display = 'none';
        panel.dataset.loaded = '1';

        if (!data.success || !data.items.length) {
            content.style.display = '';
            document.getElementById(`itemCards_${orderId}`).innerHTML =
                '<p class="text-muted small">No items found.</p>';
            return;
        }

        const cards = data.items.map(item => {
            const imgSrc = item.image
                ? `/<?= trim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') ?>/public/${item.image}`
                : '';
            const imgHtml = imgSrc
                ? `<img src="${imgSrc}" style="width:44px;height:44px;object-fit:cover;" class="rounded mb-1" />`
                : `<div class="rounded bg-light border d-flex align-items-center justify-content-center mb-1"
                        style="width:44px;height:44px;font-size:1.2rem;">🛒</div>`;

            return `
                <div class="text-center p-2 bg-white rounded border shadow-sm" style="min-width:82px;">
                    ${imgHtml}
                    <div class="small fw-semibold">${item.name}</div>
                    <span class="badge bg-dark" style="font-size:.62rem;">${item.price} EGP</span>
                    <div class="fw-bold mt-1">× ${item.quantity}</div>
                </div>`;
        }).join('');

        document.getElementById(`itemCards_${orderId}`).innerHTML = cards;
        content.style.display = '';

    } catch (err) {
        loader.innerHTML = '<span class="text-danger small">Failed to load items.</span>';
    }
}

// ── Change order status via AJAX ─────────────────────────────────────────────
async function changeStatus(orderId, newStatus) {
    const label = STATUS_CONFIG[newStatus]?.label ?? newStatus;
    if (!confirm(`Change order #${orderId} to "${label}"?`)) return;

    try {
        const res  = await fetch(
            `?page=update-order-status&id=${orderId}&status=${newStatus}`,
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        );
        const data = await res.json();

        if (!data.success) {
            showToast(data.message ?? 'Update failed.', 'danger');
            return;
        }

        // Update badge
        const cfg   = STATUS_CONFIG[newStatus];
        const badge = document.getElementById(`statusBadge_${orderId}`);
        badge.className   = `badge rounded-pill ${cfg.bg}`;
        badge.innerHTML   = `<i class="bi ${cfg.icon} me-1"></i>${cfg.label}`;

        // Rebuild action buttons
        const cell        = document.getElementById(`actionCell_${orderId}`);
        const transitions = NEXT_STATUS[newStatus] ?? {};
        const toggleBtn   = `<button class="btn btn-sm btn-outline-secondary"
                                     onclick="toggleItems(${orderId})"
                                     id="toggleBtn_${orderId}">
                                <i class="bi bi-eye me-1"></i>Items
                            </button>`;

        const actionBtns = Object.entries(transitions).map(([toStatus, meta]) =>
            `<button class="btn btn-sm ${meta.btn} fw-semibold"
                     onclick="changeStatus(${orderId}, '${toStatus}')">
                 <i class="bi ${meta.icon} me-1"></i>${meta.label}
             </button>`
        ).join('');

        cell.innerHTML = (actionBtns || '<span class="text-muted small fst-italic">No actions</span>') + toggleBtn;

        showToast(`Order #${orderId} → <strong>${cfg.label}</strong>`, 'success');

    } catch (err) {
        showToast('Network error. Please try again.', 'danger');
    }
}

// ── Toast helper ──────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const wrap = document.getElementById('toastWrap');
    const id   = 'toast_' + Date.now();
    const bg   = { success: 'bg-success', warning: 'bg-warning text-dark', danger: 'bg-danger', info: 'bg-info text-dark' };
    wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast text-white ${bg[type] ?? bg.success} border-0 shadow-sm" role="alert">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
            </div>
        </div>`);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 3500 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>