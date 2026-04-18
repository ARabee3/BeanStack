<?php

/**
 * layouts/header.php
 * Include at the top of every page.
 * Expects: $pageTitle (string), $activeNav (string key)
 * Session: $_SESSION['role'] => 'admin' | 'user'
 *          $_SESSION['name'] => display name
 */
$role      = $_SESSION['role']      ?? 'user';
$userName  = $_SESSION['name']      ?? 'Guest';
$userInitials = implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', trim($userName))));
$isAdmin   = ($role === 'admin');
$pageTitle = $pageTitle ?? 'Cafeteria';
$activeNav = $activeNav ?? '';

/* Resolve relative path to layouts/ for assets */
$pathDepth = substr_count(ltrim($_SERVER['PHP_SELF'] ?? '', '/'), '/');
$base = str_repeat('../', max(0, $pathDepth - 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> — Cafeteria</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        /* ── Minimal overrides — all layout done with Bootstrap utilities ── */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        /* Sidebar */
        #sidebar {
            width: 240px;
            min-height: calc(100vh - 56px);
            background: #1a1d23;
            transition: width .2s;
            flex-shrink: 0;
        }

        #sidebar .nav-link {
            color: #adb5bd;
            border-radius: .375rem;
            margin: 1px 0;
            font-size: .875rem;
        }

        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background: rgba(255, 255, 255, .08);
            color: #fff;
        }

        #sidebar .nav-link.active {
            font-weight: 600;
        }

        #sidebar .sidebar-section {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #6c757d;
            padding: .75rem 1rem .25rem;
        }

        #sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 6px;
        }

        /* Topbar */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            height: 56px;
        }

        .topbar .brand {
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: -.01em;
            color: #212529;
        }

        .topbar .brand span {
            color: #d97706;
        }

        /* Avatar chip */
        .avatar-chip .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #d97706;
            color: #fff;
            font-size: .75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Content wrapper */
        #content {
            flex: 1;
            overflow-x: hidden;
        }

        /* Page card */
        .page-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: .75rem;
        }

        .page-card-header {
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
        }

        /* Order item card (home / manual order) */
        .product-card {
            cursor: pointer;
            transition: box-shadow .15s, transform .15s;
        }

        .product-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
            transform: translateY(-2px);
        }

        .product-emoji {
            font-size: 2.5rem;
            line-height: 1;
        }

        .price-badge {
            font-size: .7rem;
            font-weight: 700;
        }

        /* Latest order sidebar */
        #orderPanel {
            min-width: 280px;
            max-width: 280px;
        }

        /* Table tweaks */
        .table> :not(caption)>*>* {
            vertical-align: middle;
        }

        /* Expand row */
        .detail-row td {
            background: #f8f9fa !important;
        }

        /* Pagination */
        .page-link {
            color: #d97706;
        }

        .page-item.active .page-link {
            background: #d97706;
            border-color: #d97706;
            color: #fff;
        }

        /* Status badges */
        .badge-processing {
            background: #fff3cd;
            color: #664d03;
        }

        .badge-delivery {
            background: #cff4fc;
            color: #055160;
        }

        .badge-done {
            background: #d1e7dd;
            color: #0a3622;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #58151c;
        }

        .badge-available {
            background: #d1e7dd;
            color: #0a3622;
        }

        .badge-unavailable {
            background: #f8d7da;
            color: #58151c;
        }

        /* Form validation helpers */
        .form-control:focus,
        .form-select:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 .2rem rgba(217, 119, 6, .2);
        }

        /* Responsive: collapse sidebar on small screens */
        @media (max-width: 767.98px) {
            #sidebar {
                display: none !important;
            }

            #orderPanel {
                min-width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- ══════════════════════════════════════════════════
     TOPBAR
══════════════════════════════════════════════════ -->
    <nav class="topbar d-flex align-items-center px-3 px-lg-4 gap-3 sticky-top shadow-sm" style="z-index:1030;">

        <!-- Brand -->
        <a href="<?= $base ?><?= $isAdmin ? 'admin/manual_order.php' : 'home.php' ?>"
            class="brand text-decoration-none me-auto me-lg-4">
            <i class="bi bi-cup-hot-fill text-warning me-1"></i>Cafe<span>ria</span>
        </a>

        <?php if ($isAdmin): ?>
            <!-- Admin nav links -->
            <div class="d-none d-lg-flex align-items-center gap-1">
                <a href="<?= $base ?>admin/manual_order.php"
                    class="nav-link px-3 py-1 rounded <?= $activeNav === 'home' ? 'fw-semibold text-dark' : '' ?>">
                    <i class="bi bi-house me-1"></i>Home
                </a>
                <a href="<?= $base ?>products/all_products.php"
                    class="nav-link px-3 py-1 rounded <?= $activeNav === 'products' ? 'fw-semibold text-dark' : '' ?>">
                    <i class="bi bi-box-seam me-1"></i>Products
                </a>
                <a href="<?= $base ?>users/all_users.php"
                    class="nav-link px-3 py-1 rounded <?= $activeNav === 'users' ? 'fw-semibold text-dark' : '' ?>">
                    <i class="bi bi-people me-1"></i>Users
                </a>
                <a href="<?= $base ?>admin/manual_order.php"
                    class="nav-link px-3 py-1 rounded <?= $activeNav === 'manual' ? 'fw-semibold text-dark' : '' ?>">
                    <i class="bi bi-clipboard-plus me-1"></i>Manual Order
                </a>
                <a href="<?= $base ?>admin/checks.php"
                    class="nav-link px-3 py-1 rounded <?= $activeNav === 'checks' ? 'fw-semibold text-dark' : '' ?>">
                    <i class="bi bi-bar-chart-line me-1"></i>Checks
                </a>
                <a href="<?= $base ?>admin/orders.php"
                    class="nav-link px-3 py-1 rounded <?= $activeNav === 'orders' ? 'fw-semibold text-dark' : '' ?>">
                    <i class="bi bi-lightning-charge me-1"></i>Orders
                    <span class="badge bg-danger ms-1" id="liveCount">3</span>
                </a>
            </div>
        <?php else: ?>
            <!-- User nav links -->
            <div class="d-none d-lg-flex align-items-center gap-1">
                <a href="<?= $base ?>home.php"
                    class="nav-link px-3 py-1 rounded <?= $activeNav === 'home' ? 'fw-semibold text-dark' : '' ?>">
                    <i class="bi bi-house me-1"></i>Home
                </a>
                <a href="<?= $base ?>orders/my_orders.php"
                    class="nav-link px-3 py-1 rounded <?= $activeNav === 'myorders' ? 'fw-semibold text-dark' : '' ?>">
                    <i class="bi bi-receipt me-1"></i>My Orders
                </a>
            </div>
        <?php endif; ?>

        <!-- User chip -->
        <div class="avatar-chip d-flex align-items-center gap-2 ms-auto ms-lg-0">
            <div class="avatar-circle"><?= htmlspecialchars($userInitials) ?></div>
            <span class="d-none d-sm-inline fw-semibold small"><?= htmlspecialchars($userName) ?></span>

            <div class="dropdown">
                <button class="btn btn-sm btn-light border-0 p-1" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li>
                        <h6 class="dropdown-header"><?= htmlspecialchars($userName) ?></h6>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item text-danger" href="?page=logout">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                </ul>
            </div>
        </div>

        <!-- Mobile toggle -->
        <button class="btn btn-sm btn-light d-lg-none border" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
            <i class="bi bi-list"></i>
        </button>
    </nav>

    <!-- Mobile Offcanvas Nav -->
    <div class="offcanvas offcanvas-start" style="width:260px;background:#1a1d23;" tabindex="-1" id="mobileNav">
        <div class="offcanvas-header border-bottom border-secondary">
            <span class="text-white fw-bold"><i class="bi bi-cup-hot-fill text-warning me-1"></i>Caferia</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-2">
            <?php
            $sidebarNavFile = __DIR__ . '/sidebar_nav.php';
            if (file_exists($sidebarNavFile)) {
                include $sidebarNavFile;
            }
            ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════
     PAGE WRAPPER  (sidebar + content)
══════════════════════════════════════════════════ -->
    <div class="d-flex" style="min-height:calc(100vh - 56px);">

        <?php if ($isAdmin): ?>
            <!-- ADMIN SIDEBAR -->
            <aside id="sidebar" class="d-none d-md-flex flex-column py-3 px-2">
                <div class="sidebar-section mt-0">Navigation</div>
                <a href="<?= $base ?>admin/manual_order.php"
                    class="nav-link px-3 py-2 <?= $activeNav === 'home' ? 'active' : '' ?>">
                    <i class="bi bi-house"></i>Home
                </a>

                <div class="sidebar-section">Catalogue</div>
                <a href="<?= $base ?>products/all_products.php"
                    class="nav-link px-3 py-2 <?= $activeNav === 'products' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam"></i>All Products
                </a>
                <a href="<?= $base ?>products/add_product.php"
                    class="nav-link px-3 py-2 <?= $activeNav === 'addproduct' ? 'active' : '' ?>">
                    <i class="bi bi-plus-square"></i>Add Product
                </a>

                <div class="sidebar-section">People</div>
                <a href="<?= $base ?>users/all_users.php"
                    class="nav-link px-3 py-2 <?= $activeNav === 'users' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>All Users
                </a>
                <a href="<?= $base ?>users/add_user.php"
                    class="nav-link px-3 py-2 <?= $activeNav === 'adduser' ? 'active' : '' ?>">
                    <i class="bi bi-person-plus"></i>Add User
                </a>

                <div class="sidebar-section">Orders</div>
                <a href="<?= $base ?>admin/manual_order.php"
                    class="nav-link px-3 py-2 <?= $activeNav === 'manual' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-plus"></i>Manual Order
                </a>
                <a href="<?= $base ?>admin/orders.php"
                    class="nav-link px-3 py-2 <?= $activeNav === 'orders' ? 'active' : '' ?>">
                    <i class="bi bi-lightning-charge"></i>Live Orders
                    <span class="badge bg-danger ms-auto">3</span>
                </a>

                <div class="sidebar-section">Reports</div>
                <a href="<?= $base ?>admin/checks.php"
                    class="nav-link px-3 py-2 <?= $activeNav === 'checks' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-line"></i>Checks
                </a>

                <div class="mt-auto px-3 pb-2">
                    <a href="?page=logout" class="nav-link text-danger px-0 py-2">
                        <i class="bi bi-box-arrow-left"></i>Logout
                    </a>
                </div>
            </aside>
        <?php endif; ?>

        <!-- MAIN CONTENT -->
        <main id="content" class="p-3 p-lg-4 flex-grow-1">