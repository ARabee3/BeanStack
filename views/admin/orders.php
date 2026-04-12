<?php
/**
 * admin/orders.php — Wireframe p.10
 * Each order = header row (Order Date | Name | Room | Ext. | Action:deliver)
 *            + detail block (product cards + Total)
 */
include '../layouts/header.php';
?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-lightning-charge me-2 text-warning"></i>Orders</h4>
        <p class="text-muted small mb-0">Live order management dashboard</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
            <i class="bi bi-hourglass-split me-1"></i>
            <?= count(array_filter($orders, fn($o)=>$o['status']==='processing')) ?> Processing
        </span>
        <span class="badge bg-info text-dark fs-6 px-3 py-2">
            <i class="bi bi-bicycle me-1"></i>
            <?= count(array_filter($orders, fn($o)=>$o['status']==='delivery')) ?> Delivery
        </span>
    </div>
</div>

<!-- Order cards (wireframe p.10 layout: header row + product cards beneath) -->
<div class="d-flex flex-column gap-3" id="ordersContainer">

<?php foreach ($orders as $oi => $order): ?>
<?php $total = array_sum(array_map(fn($x)=>$x['price']*$x['qty'], $order['items'])); ?>

<div class="page-card" id="orderCard_<?= $oi ?>">
    <!-- Header row (wireframe columns: Order Date | Name | Room | Ext. | Action) -->
    <div class="table-responsive">
    <table class="table table-bordered mb-0" style="min-width:560px;">
        <thead class="table-secondary">
            <tr>
                <th class="fw-semibold small">Order Date</th>
                <th class="text-center fw-semibold small">Name</th>
                <th class="text-center fw-semibold small">Room</th>
                <th class="text-center fw-semibold small">Ext.</th>
                <th class="text-center fw-semibold small">Status</th>
                <th class="text-center fw-semibold small">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="small fw-semibold"><?= htmlspecialchars($order['date']) ?></td>
                <td class="text-center">
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-person-circle text-muted"></i>
                        <?= htmlspecialchars($order['name']) ?>
                    </div>
                </td>
                <td class="text-center"><?= htmlspecialchars($order['room']) ?></td>
                <td class="text-center"><?= htmlspecialchars($order['ext']) ?></td>
                <td class="text-center">
                    <span class="badge rounded-pill <?= $statusClass[$order['status']] ?>"
                          id="statusBadge_<?= $oi ?>">
                        <?= $statusLabel[$order['status']] ?>
                    </span>
                </td>
                <td class="text-center">
                    <?php if ($order['status'] === 'processing'): ?>
                    <button class="btn btn-sm btn-success fw-semibold"
                            onclick="deliverOrder(<?= $oi ?>)" id="deliverBtn_<?= $oi ?>">
                        <i class="bi bi-truck me-1"></i>deliver
                    </button>
                    <?php elseif ($order['status'] === 'delivery'): ?>
                    <button class="btn btn-sm btn-primary fw-semibold"
                            onclick="doneOrder(<?= $oi ?>)" id="deliverBtn_<?= $oi ?>">
                        <i class="bi bi-check-circle me-1"></i>done
                    </button>
                    <?php else: ?>
                    <span class="text-success fw-semibold small"><i class="bi bi-check-all me-1"></i>Delivered</span>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>
    </div>

    <!-- Product cards (wireframe p.10 inner box) -->
    <div class="p-3 bg-light border-top">
        <div class="d-flex gap-3 flex-wrap">
            <?php foreach ($order['items'] as $item): ?>
            <div class="text-center p-2 bg-white rounded border shadow-sm" style="min-width:78px;">
                <div style="font-size:2rem;"><?= $item['emoji'] ?></div>
                <div class="small fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                <span class="badge bg-dark" style="font-size:.62rem;"><?= $item['price'] ?> LE</span>
                <div class="fw-bold mt-1"><?= $item['qty'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-end mt-2 fw-bold">
            Total: EGP <?= $total ?>
        </div>
    </div>
</div>

<?php endforeach; ?>
</div><!-- /ordersContainer -->

<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
function deliverOrder(idx) {
    const badge = document.getElementById('statusBadge_' + idx);
    const btn   = document.getElementById('deliverBtn_'  + idx);
    badge.className = 'badge rounded-pill badge-delivery';
    badge.textContent = 'Out for Delivery';
    btn.className = 'btn btn-sm btn-primary fw-semibold';
    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>done';
    btn.setAttribute('onclick', 'doneOrder(' + idx + ')');
    showToast('<i class="bi bi-truck me-1"></i>Order marked as out for delivery!');
}

function doneOrder(idx) {
    const badge = document.getElementById('statusBadge_' + idx);
    const btn   = document.getElementById('deliverBtn_'  + idx);
    badge.className = 'badge rounded-pill badge-done';
    badge.textContent = 'Done';
    btn.closest('td').innerHTML = '<span class="text-success fw-semibold small"><i class="bi bi-check-all me-1"></i>Delivered</span>';
    showToast('<i class="bi bi-check-circle me-1"></i>Order completed!', 'success');
}

function showToast(msg, type='success') {
    const wrap = document.getElementById('toastWrap');
    const id = 'toast_' + Date.now();
    const bg = { success:'bg-success', warning:'bg-warning text-dark' };
    wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast text-white ${bg[type]??bg.success} border-0 shadow-sm" role="alert">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, {delay:3000}).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}
</script>

<?php include '../layouts/footer.php'; ?>
