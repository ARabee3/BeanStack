<?php
/**
 * orders/my_orders.php — Wireframe p.4
 * Table: Order Date | Status | Amount | Action
 * Expandable rows show product cards with price + qty
 * Pagination at bottom
 */
session_start();
// if (empty($_SESSION['role'])) { header('Location: ../login.php'); exit; }
$_SESSION['role'] = 'user';
$_SESSION['name'] = 'Islam Askar';

$pageTitle = 'My Orders';
$activeNav = 'myorders';

/* Demo data */
$orders = [
    [
        'id'=>'#1042','date'=>'2024/02/02 10:30 AM','status'=>'processing','amount'=>55,
        'items'=>[['name'=>'Tea','emoji'=>'🍵','price'=>5,'qty'=>2],
                  ['name'=>'Coffee','emoji'=>'☕','price'=>6,'qty'=>1],
                  ['name'=>'Nescafe','emoji'=>'🫘','price'=>8,'qty'=>1],
                  ['name'=>'Cola','emoji'=>'🥤','price'=>10,'qty'=>3]],
    ],
    [
        'id'=>'#1038','date'=>'2024/02/01 11:30 AM','status'=>'delivery','amount'=>20,
        'items'=>[['name'=>'Tea','emoji'=>'🍵','price'=>5,'qty'=>2],
                  ['name'=>'Coffee','emoji'=>'☕','price'=>6,'qty'=>1]],
    ],
    [
        'id'=>'#1031','date'=>'2024/01/01 11:35 AM','status'=>'done','amount'=>29,
        'items'=>[['name'=>'Tea','emoji'=>'🍵','price'=>5,'qty'=>1],
                  ['name'=>'Coffee','emoji'=>'☕','price'=>6,'qty'=>1],
                  ['name'=>'Nescafe','emoji'=>'🫘','price'=>8,'qty'=>1],
                  ['name'=>'Cola','emoji'=>'🥤','price'=>10,'qty'=>1]],
    ],
];

$statusClass = [
    'processing' => 'badge-processing',
    'delivery'   => 'badge-delivery',
    'done'       => 'badge-done',
    'cancelled'  => 'badge-cancelled',
];
$statusLabel = [
    'processing' => 'Processing',
    'delivery'   => 'Out for delivery',
    'done'       => 'Done',
    'cancelled'  => 'Cancelled',
];

include '../layouts/header.php';
?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-warning"></i>My Orders</h4>
        <p class="text-muted small mb-0">Track your order history and current status</p>
    </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <?php
    $total = count($orders);
    $spent = array_sum(array_column($orders, 'amount'));
    $active= count(array_filter($orders, fn($o)=>$o['status']==='processing'));
    $avg   = $total ? round($spent/$total,1) : 0;
    ?>
    <?php foreach([
        ['Total Orders','bi-receipt',      $total,       'primary'],
        ['Total Spent', 'bi-cash-coin',    $spent.' EGP','success'],
        ['Active',      'bi-hourglass-split',$active,    'warning'],
        ['Avg. Order',  'bi-graph-up',     $avg.' EGP',  'info'],
    ] as [$lbl,$icon,$val,$color]): ?>
    <div class="col-6 col-md-3">
        <div class="page-card p-3 h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi <?= $icon ?> text-<?= $color ?>"></i>
                <span class="small text-muted"><?= $lbl ?></span>
            </div>
            <div class="fw-bold fs-5"><?= $val ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="page-card mb-3">
    <div class="page-card-header">
        <div class="d-flex align-items-end gap-3 flex-wrap">
            <div>
                <label class="form-label small fw-semibold mb-1">Date from</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="date" class="form-control" id="dateFrom" value="2024-01-01"/>
                </div>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Date to</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="date" class="form-control" id="dateTo" value="2024-12-31"/>
                </div>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select class="form-select form-select-sm" id="statusFilter" style="min-width:140px;">
                    <option value="">All Statuses</option>
                    <option value="processing">Processing</option>
                    <option value="delivery">Out for Delivery</option>
                    <option value="done">Done</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <button class="btn btn-warning btn-sm fw-semibold px-3">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <button class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise me-1"></i>Reset
            </button>
        </div>
    </div>
</div>

<!-- Orders table -->
<div class="page-card">
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th style="width:36px;"></th>
                <th>Order Date</th>
                <th>Status</th>
                <th class="text-end">Amount</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $i => $o): ?>
            <!-- Main row -->
            <tr>
                <td>
                    <button class="btn btn-sm btn-light border p-0 px-1" style="line-height:1.2;"
                            data-expand-target="detail_<?= $i ?>">
                        <i class="bi bi-chevron-right" style="font-size:.75rem;"></i>
                    </button>
                </td>
                <td>
                    <span class="fw-semibold small"><?= htmlspecialchars($o['date']) ?></span>
                    <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($o['id']) ?></div>
                </td>
                <td>
                    <span class="badge rounded-pill <?= $statusClass[$o['status']] ?>">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>
                        <?= $statusLabel[$o['status']] ?>
                    </span>
                </td>
                <td class="text-end fw-bold"><?= $o['amount'] ?> EGP</td>
                <td class="text-center">
                    <?php if ($o['status'] === 'processing'): ?>
                    <button class="btn btn-sm btn-outline-danger fw-semibold"
                            onclick="cancelOrder('<?= $o['id'] ?>',this)">
                        <i class="bi bi-x-circle me-1"></i>CANCEL
                    </button>
                    <?php else: ?>
                    <span class="text-muted small">—</span>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Expandable detail row -->
            <tr id="detail_<?= $i ?>" class="detail-row d-none">
                <td colspan="5">
                    <div class="p-3">
                        <p class="text-uppercase fw-semibold text-muted mb-3"
                           style="font-size:.7rem;letter-spacing:.1em;">Order Details</p>
                        <!-- Product cards (wireframe p.4 bottom) -->
                        <div class="d-flex gap-3 flex-wrap mb-3">
                            <?php foreach ($o['items'] as $item): ?>
                            <div class="text-center p-2 bg-white rounded border" style="min-width:80px;">
                                <div style="font-size:2rem;"><?= $item['emoji'] ?></div>
                                <div class="small fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                <span class="badge bg-dark" style="font-size:.65rem;"><?= $item['price'] ?> LE</span>
                                <div class="fw-bold mt-1"><?= $item['qty'] ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php $tot = array_sum(array_map(fn($x)=>$x['price']*$x['qty'],$o['items'])); ?>
                        <div class="d-flex justify-content-end fw-bold border-top pt-2">
                            <span>Total: EGP <?= $tot ?></span>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>

        <tfoot class="table-light">
            <tr>
                <td colspan="3" class="fw-semibold">Total (this page)</td>
                <td class="text-end fw-bold"><?= array_sum(array_column($orders,'amount')) ?> EGP</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center py-3 border-top">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#">«</a></li>
                <li class="page-item disabled"><a class="page-link" href="#">‹</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item disabled"><a class="page-link" href="#">…</a></li>
                <li class="page-item"><a class="page-link" href="#">›</a></li>
                <li class="page-item"><a class="page-link" href="#">»</a></li>
            </ul>
        </nav>
    </div>
</div>

<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
function cancelOrder(id, btn) {
    if (!confirm('Cancel order ' + id + '?')) return;
    const row = btn.closest('tr');
    row.querySelector('.badge').className = 'badge rounded-pill badge-cancelled';
    row.querySelector('.badge').innerHTML = '<i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Cancelled';
    btn.closest('td').innerHTML = '<span class="text-muted small">—</span>';
    showToast('<i class="bi bi-check-circle me-1"></i>Order ' + id + ' cancelled.');
}

document.querySelectorAll('[data-expand-target]').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.expandTarget);
        if (!target) return;
        const isOpen = !target.classList.contains('d-none');
        target.classList.toggle('d-none', isOpen);
        const icon = btn.querySelector('i');
        if (icon) icon.className = isOpen ? 'bi bi-chevron-right' : 'bi bi-chevron-down';
    });
});

function showToast(msg, type = 'success') {
    const wrap = document.getElementById('toastWrap');
    const id = 'toast_' + Date.now();
    wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast align-items-center border-0 shadow-sm bg-success text-white" role="alert">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 3000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}
</script>

<?php include '../layouts/footer.php'; ?>
