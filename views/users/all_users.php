<?php

/**
 * views/users/all_users.php
 * Wired to UserController::index()
 *
 * Variables injected by the controller:
 *   $users        – array of DB rows
 *   $totalPages   – int
 *   $currentPage  – int
 *   $pageTitle    – string
 *   $activeNav    – string
 */

$users       = $users       ?? [];
$totalPages  = $totalPages  ?? 1;
$currentPage = $currentPage ?? 1;
$pageTitle   = $pageTitle   ?? 'All Users';
$activeNav   = $activeNav   ?? 'users';

$filterSearch = htmlspecialchars($_GET['search'] ?? '');
$filterRole   = $_GET['role'] ?? '';

function usersUrl(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

/**
 * Generates a consistent avatar background color from a user's name.
 */
function avatarColor(string $name): string
{
    $colors = ['#4f46e5','#0891b2','#059669','#d97706','#dc2626','#7c3aed','#db2777','#0284c7'];
    return $colors[crc32($name) % count($colors)];
}

/**
 * Extracts initials (up to 2 letters) from a full name.
 */
function initials(string $name): string
{
    $parts = array_filter(explode(' ', trim($name)));
    if (count($parts) >= 2) {
        return strtoupper($parts[0][0] . end($parts)[0]);
    }
    return strtoupper(substr($name, 0, 2));
}

include __DIR__ . '/../layouts/header.php';
?>

<!-- ── Flash message ─────────────────────────────────────────────────────── -->
<?php if (!empty($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Page header ───────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-people me-2 text-warning"></i>All Users
        </h4>
        <p class="text-muted small mb-0">Manage employee accounts</p>
    </div>
    <a href="?page=add-user" class="btn btn-warning fw-semibold">
        <i class="bi bi-person-plus me-1"></i>Add User
    </a>
</div>

<!-- ── Search & filter bar ───────────────────────────────────────────────── -->
<div class="page-card mb-3">
    <div class="page-card-header">
        <form method="GET" action="" id="filterForm" class="d-flex gap-3 flex-wrap align-items-end">
            <input type="hidden" name="page" value="users">
            <input type="hidden" name="p"    value="1">

            <div style="flex:1;min-width:220px;">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control"
                           name="search"
                           placeholder="Name or email…"
                           value="<?= $filterSearch ?>"
                           oninput="debounceSubmit()" />
                </div>
            </div>

            <div>
                <label class="form-label small fw-semibold mb-1">Role</label>
                <select class="form-select form-select-sm" name="role"
                        onchange="this.form.submit()" style="min-width:130px;">
                    <option value=""      <?= $filterRole === ''      ? 'selected' : '' ?>>All roles</option>
                    <option value="user"  <?= $filterRole === 'user'  ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div class="align-self-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="?page=users" class="btn btn-sm btn-outline-secondary ms-1">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ── Users table ───────────────────────────────────────────────────────── -->
<div class="page-card">
    <div class="table-responsive-stack">
        <table class="table table-hover align-middle mb-0" id="usersTable">
            <thead class="table-dark d-none d-sm-table-header-group">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Location</th>
                    <th class="text-center">Role</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-people fs-3 d-block mb-2"></i>
                            No users found.
                            <?php if ($filterSearch || $filterRole): ?>
                                <a href="?page=users" class="d-block mt-1 small">Clear filters</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u):
                        $rowNum  = ($currentPage - 1) * 10 + $i + 1;
                        $color   = avatarColor($u['name']);
                        $inits   = initials($u['name']);
                        $isActive = (bool) $u['isActive'];
                    ?>
                    <tr id="user-row-<?= (int)$u['id'] ?>">
                        <td data-label="#" class="text-muted small"><?= $rowNum ?></td>

                        <!-- Avatar + name -->
                        <td data-label="Name">
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($u['profile_pic'])): ?>
                                    <img src="../public/<?= htmlspecialchars($u['profile_pic']) ?>"
                                         class="rounded-circle border object-fit-cover flex-shrink-0"
                                         style="width:36px;height:36px;" />
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center
                                                rounded-circle text-white fw-bold flex-shrink-0"
                                         style="width:36px;height:36px;background:<?= $color ?>;font-size:.7rem;">
                                        <?= $inits ?>
                                    </div>
                                <?php endif; ?>
                                <span class="fw-semibold"><?= htmlspecialchars($u['name']) ?></span>
                            </div>
                        </td>

                        <td data-label="Email" class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>

                        <!-- Location -->
                        <td data-label="Location">
                            <?php if (!empty($u['location'])): ?>
                                <span class="text-muted small">
                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($u['location']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small fst-italic">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Role badge -->
                        <td data-label="Role" class="text-center">
                            <span class="badge <?= $u['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-light text-dark border' ?>">
                                <i class="bi bi-<?= $u['role'] === 'admin' ? 'shield-fill' : 'person' ?> me-1"></i>
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>

                        <!-- Active toggle -->
                        <td data-label="Status" class="text-center">
                            <button class="btn btn-sm border-0 fw-semibold px-2 py-1
                                           <?= $isActive ? 'btn-success' : 'btn-secondary' ?>"
                                    data-id="<?= (int)$u['id'] ?>"
                                    onclick="toggleActive(this)"
                                    style="min-width:88px;font-size:.72rem;">
                                <i class="bi <?= $isActive ? 'bi-check-circle' : 'bi-slash-circle' ?> me-1"></i>
                                <?= $isActive ? 'Active' : 'Inactive' ?>
                            </button>
                        </td>

                        <!-- Actions -->
                        <td data-label="Actions" class="text-center">
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <a href="?page=edit-user&id=<?= (int)$u['id'] ?>"
                                   class="btn btn-sm btn-outline-primary px-2">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </a>
                                <button class="btn btn-sm btn-outline-danger px-2"
                                        onclick="confirmDelete(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Pagination ────────────────────────────────────────────────────── -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center py-3 border-top">
        <nav aria-label="Users pagination">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= usersUrl(['p' => 1]) ?>">«</a>
                </li>
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= usersUrl(['p' => $currentPage - 1]) ?>">‹</a>
                </li>

                <?php
                $win   = 2;
                $start = max(1, $currentPage - $win);
                $end   = min($totalPages, $currentPage + $win);
                if ($start > 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif;
                for ($pg = $start; $pg <= $end; $pg++): ?>
                    <li class="page-item <?= $pg === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= usersUrl(['p' => $pg]) ?>"><?= $pg ?></a>
                    </li>
                <?php endfor;
                if ($end < $totalPages): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>

                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= usersUrl(['p' => $currentPage + 1]) ?>">›</a>
                </li>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= usersUrl(['p' => $totalPages]) ?>">»</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Hidden delete form -->
<form id="deleteForm" method="POST" action="" style="display:none;"></form>

<!-- Toast container -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
// ── Search debounce ──────────────────────────────────────────────────────────
let _dt;
function debounceSubmit() {
    clearTimeout(_dt);
    _dt = setTimeout(() => document.getElementById('filterForm').submit(), 450);
}

// ── Toggle active status via AJAX ────────────────────────────────────────────
async function toggleActive(btn) {
    const id = btn.dataset.id;
    btn.disabled = true;

    try {
        const res  = await fetch(`?page=toggle-user&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) {
            const err = await res.json();
            showToast(err.error ?? 'Failed to update status.', 'danger');
            return;
        }
        const data     = await res.json();
        const isActive = data.isActive === 1;

        btn.className = `btn btn-sm border-0 fw-semibold px-2 py-1 ${isActive ? 'btn-success' : 'btn-secondary'}`;
        btn.style.cssText = 'min-width:88px;font-size:.72rem;';
        btn.innerHTML = `<i class="bi ${isActive ? 'bi-check-circle' : 'bi-slash-circle'} me-1"></i>${isActive ? 'Active' : 'Inactive'}`;

        showToast(`Status updated to <strong>${isActive ? 'Active' : 'Inactive'}</strong>.`, isActive ? 'success' : 'warning');
    } catch {
        showToast('Network error. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
    }
}

// ── Delete confirmation ───────────────────────────────────────────────────────
function confirmDelete(id, name) {
    if (!confirm(`Delete user "${name}"?\nThis action cannot be undone.`)) return;
    const form = document.getElementById('deleteForm');
    form.action = `?page=delete-user&id=${id}`;
    form.submit();
}

// ── Toast helper ──────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const wrap = document.getElementById('toastWrap');
    const id   = 'toast_' + Date.now();
    const bg   = { success: 'bg-success', warning: 'bg-warning text-dark', danger: 'bg-danger' };
    wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast text-white ${bg[type] ?? bg.success} border-0 shadow-sm" role="alert">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
            </div>
        </div>`);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 3000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>