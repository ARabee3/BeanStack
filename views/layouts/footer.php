<?php
/**
 * layouts/footer.php
 * Include at the bottom of every page (closes tags opened by header.php)
 */
?>
</main><!-- /#content -->
</div><!-- /.d-flex page wrapper -->

<!-- Bootstrap 5 JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ── Shared page JS ────────────────────────────────── -->
<script>
/* Bootstrap form validation */
(function () {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

/* Toast helper */
function showToast(msg, type = 'success') {
    const wrap = document.getElementById('toastWrap');
    if (!wrap) return;
    const id = 'toast_' + Date.now();
    const icons = { success: 'bi-check-circle-fill text-success', danger: 'bi-x-circle-fill text-danger', warning: 'bi-exclamation-triangle-fill text-warning' };
    wrap.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast align-items-center border-0 shadow-sm" role="alert">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${icons[type] ?? icons.success}"></i> ${msg}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 3000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

/* Expandable table rows */
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

<!-- Toast container (fixed bottom-end) -->
<div id="toastWrap" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

</body>
</html>
