<?php

/**
 * users/all_users.php — Wireframe p.6
 * Columns: Name | Room | Image | Ext. | Action (edit delete)
 * "add user" link top-right, pagination
 */

$pageTitle = $pageTitle ?? 'All Users';
$activeNav = $activeNav ?? 'users';
$users = $users ?? [];

include __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-people me-2 text-warning"></i>All Users</h4>
        <p class="text-muted small mb-0">Manage employee accounts</p>
    </div>
    <a href="?page=add-user" class="btn btn-warning fw-semibold">
        <i class="bi bi-person-plus me-1"></i>add user
    </a>
</div>

<!-- Search bar -->
<div class="page-card mb-3">
    <div class="page-card-header">
        <div class="d-flex gap-3 align-items-end flex-wrap">
            <div style="flex:1;min-width:200px;">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" placeholder="Name or room…"
                        id="userSearch" oninput="filterUsers()" />
                </div>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Room</label>
                <input type="text" class="form-control form-control-sm" placeholder="Room no."
                    id="roomFilter" oninput="filterUsers()" style="width:110px;" />
            </div>
        </div>
    </div>
</div>

<!-- Users table -->
<div class="page-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="usersTable">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th class="text-center">Room</th>
                    <th class="text-center">Image</th>
                    <th class="text-center">Ext.</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr data-name="<?= strtolower($u['name']) ?>" data-room="<?= $u['room'] ?>">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
                                    style="width:34px;height:34px;background:<?= $u['color'] ?>;font-size:.75rem;flex-shrink:0;">
                                    <?= $u['initials'] ?>
                                </div>
                                <span class="fw-semibold"><?= htmlspecialchars($u['name']) ?></span>
                            </div>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($u['room']) ?></td>
                        <td class="text-center">
                            <!-- Photo placeholder (wireframe crossed box) -->
                            <div class="d-inline-flex align-items-center justify-content-center bg-light border rounded-circle"
                                style="width:38px;height:38px;font-size:.75rem;color:#adb5bd;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($u['ext']) ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="?page=add-user&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary px-2">
                                    <i class="bi bi-pencil me-1"></i>edit
                                </a>
                                <button class="btn btn-sm btn-outline-danger px-2"
                                    onclick="deleteUser(<?= $u['id'] ?>,'<?= htmlspecialchars($u['name']) ?>',this)">
                                    <i class="bi bi-trash me-1"></i>delete
                                </button>
                            </div>
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

<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
    function filterUsers() {
        const q = document.getElementById('userSearch').value.toLowerCase();
        const room = document.getElementById('roomFilter').value.toLowerCase();
        document.querySelectorAll('#usersTable tbody tr').forEach(row => {
            const nm = row.dataset.name;
            const rm = row.dataset.room.toLowerCase();
            row.style.display = ((!q || nm.includes(q)) && (!room || rm.includes(room))) ? '' : 'none';
        });
    }

    function deleteUser(id, name, btn) {
        if (!confirm('Delete user "' + name + '"?')) return;
        const row = btn.closest('tr');
        row.style.opacity = '0';
        row.style.transition = 'opacity .3s';
        setTimeout(() => row.remove(), 300);
        showToast('<i class="bi bi-trash me-1"></i>' + name + ' removed.');
    }

    function showToast(msg) {
        const wrap = document.getElementById('toastWrap');
        const id = 'toast_' + Date.now();
        wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast text-white bg-success border-0 shadow-sm" role="alert">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`);
        const el = document.getElementById(id);
        new bootstrap.Toast(el, {
            delay: 3000
        }).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>