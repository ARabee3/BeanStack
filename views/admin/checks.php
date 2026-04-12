<?php
/**
 * admin/checks.php — Wireframe p.9
 * Date from/to + User dropdown filter
 * Table: Name | Total amount  (expandable → Order Date | Amount → expandable → product cards)
 */
include '../layouts/header.php';
?>

<div class="d-flex align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2 text-warning"></i>Checks</h4>
        <p class="text-muted small mb-0">Spending summary per employee</p>
    </div>
</div>

<!-- Summary stats -->
<div class="row g-3 mb-4">
    <?php
    $grandTotal = array_sum(array_column($checksData, 'total'));
    $totalOrders = array_sum(array_map(fn($u)=>count($u['orders']), $checksData));
    ?>
    <div class="col-6 col-md-3">
        <div class="page-card p-3"><div class="text-muted small mb-1"><i class="bi bi-cash-stack me-1 text-warning"></i>Grand Total</div>
        <div class="fw-bold fs-5"><?= $grandTotal ?> EGP</div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="page-card p-3"><div class="text-muted small mb-1"><i class="bi bi-receipt me-1 text-info"></i>Total Orders</div>
        <div class="fw-bold fs-5"><?= $totalOrders ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="page-card p-3"><div class="text-muted small mb-1"><i class="bi bi-people me-1 text-success"></i>Employees</div>
        <div class="fw-bold fs-5"><?= count($checksData) ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="page-card p-3"><div class="text-muted small mb-1"><i class="bi bi-graph-up me-1 text-danger"></i>Avg / Employee</div>
        <div class="fw-bold fs-5"><?= round($grandTotal / count($checksData)) ?> EGP</div></div>
    </div>
</div>

<!-- Filter bar (wireframe p.9: Date from | Date to | User dropdown) -->
<div class="page-card mb-3">
    <div class="page-card-header">
        <div class="d-flex align-items-end gap-3 flex-wrap">
            <div>
                <label class="form-label small fw-semibold mb-1">Date from</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="date" class="form-control" value="2015-01-01"/>
                </div>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Date to</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="date" class="form-control" value="2015-12-31"/>
                </div>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">User</label>
                <select class="form-select form-select-sm" style="min-width:180px;">
                    <option value="">All Users</option>
                    <?php foreach ($checksData as $u): ?>
                    <option><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-warning btn-sm fw-semibold px-3">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
        </div>
    </div>
</div>

<!-- Main table — Level 1: Users -->
<div class="page-card">
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th style="width:36px;"></th>
                <th>Name</th>
                <th class="text-end">Total amount</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($checksData as $ui => $u): ?>

            <!-- User row -->
            <tr class="fw-semibold">
                <td>
                    <button class="btn btn-sm btn-light border p-0 px-1" style="line-height:1.2;"
                            data-expand-target="user_<?= $ui ?>">
                        <i class="bi bi-chevron-right" style="font-size:.75rem;"></i>
                    </button>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
                             style="width:30px;height:30px;background:<?= $u['color'] ?>;font-size:.7rem;flex-shrink:0;">
                            <?= $u['initials'] ?>
                        </div>
                        <?= htmlspecialchars($u['name']) ?>
                    </div>
                </td>
                <td class="text-end"><?= $u['total'] ?></td>
            </tr>

            <!-- Level 2: Orders for this user -->
            <tr id="user_<?= $ui ?>" class="detail-row d-none">
                <td colspan="3" class="p-0 ps-4 pe-2">
                    <table class="table table-sm table-bordered mb-0 mt-1 mb-2">
                        <thead class="table-secondary">
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Order Date</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($u['orders'] as $oi => $order): ?>
                            <!-- Order row -->
                            <tr>
                                <td>
                                    <button class="btn btn-sm btn-light border p-0 px-1" style="line-height:1.2;"
                                            data-expand-target="order_<?= $ui ?>_<?= $oi ?>">
                                        <i class="bi bi-chevron-right" style="font-size:.75rem;"></i>
                                    </button>
                                </td>
                                <td class="small"><?= htmlspecialchars($order['date']) ?></td>
                                <td class="text-end small fw-semibold"><?= $order['amount'] ?> EGP</td>
                            </tr>
                            <!-- Level 3: Product cards -->
                            <tr id="order_<?= $ui ?>_<?= $oi ?>" class="detail-row d-none">
                                <td colspan="3">
                                    <div class="d-flex gap-3 flex-wrap p-2">
                                        <?php foreach ($order['items'] as $item): ?>
                                        <div class="text-center p-2 bg-white rounded border" style="min-width:72px;">
                                            <div style="font-size:1.8rem;"><?= $item['emoji'] ?></div>
                                            <div class="small fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                            <span class="badge bg-dark" style="font-size:.6rem;"><?= $item['price'] ?> LE</span>
                                            <div class="fw-bold small"><?= $item['qty'] ?></div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php $tot = array_sum(array_map(fn($x)=>$x['price']*$x['qty'],$order['items'])); ?>
                                    <div class="d-flex justify-content-end pe-2 fw-bold small border-top py-1">
                                        Total: EGP <?= $tot ?>
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

    <!-- Pagination -->
    <div class="d-flex justify-content-center py-3 border-top">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item"><a class="page-link" href="#">«</a></li>
                <li class="page-item"><a class="page-link" href="#">‹</a></li>
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

<script>
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
</script>

<?php include '../layouts/footer.php'; ?>
