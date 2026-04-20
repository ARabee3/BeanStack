<?php

/**
 * layouts/header.php
 * Include at the top of every authenticated page.
 * Expects: $pageTitle (string), $activeNav (string key)
 */
$role = $_SESSION['role'] ?? 'user';
$userName = $_SESSION['name'] ?? 'Guest';
$nameParts = array_filter(explode(' ', trim($userName)));
$userInitials = implode('', array_map(static fn($word) => strtoupper($word[0]), $nameParts));
if ($userInitials === '') {
    $userInitials = 'G';
}

$isAdmin = ($role === 'admin');
$pageTitle = $pageTitle ?? 'Cafeteria';
$activeNav = $activeNav ?? '';

$sidebarItems = $isAdmin
    ? [
        ['key' => 'products', 'label' => 'Products', 'href' => '?page=products', 'icon' => 'bi-box-seam'],
        ['key' => 'users', 'label' => 'Users', 'href' => '?page=users', 'icon' => 'bi-people'],
        ['key' => 'orders', 'label' => 'Orders', 'href' => '?page=orders', 'icon' => 'bi-lightning-charge'],
        ['key' => 'checks', 'label' => 'Checks', 'href' => '?page=checks', 'icon' => 'bi-bar-chart-line'],
        ['key' => 'manual', 'label' => 'Manual Order', 'href' => '?page=manual-order', 'icon' => 'bi-clipboard-plus'],
        ['key' => 'profile', 'label' => 'My Profile', 'href' => '?page=profile', 'icon' => 'bi-person-gear'],
    ]
    : [
        ['key' => 'home', 'label' => 'Home', 'href' => '?page=home', 'icon' => 'bi-house'],
        ['key' => 'myorders', 'label' => 'My Orders', 'href' => '?page=my-orders', 'icon' => 'bi-receipt'],
        ['key' => 'profile', 'label' => 'My Profile', 'href' => '?page=profile', 'icon' => 'bi-person-gear'],
    ];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - Cafeteria</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #212529;
        }

        /* ── Sidebar ─────────────────────────────────────────────────── */
        .layout-sidebar {
            width: 240px;
            min-height: calc(100vh - 56px);
            background: #1a1d23;
            transition: all .2s ease-in-out;
            flex-shrink: 0;
            z-index: 1020;
        }

        .layout-sidebar .nav-link {
            color: #adb5bd;
            border-radius: .375rem;
            margin: 2px 8px;
            font-size: .875rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            transition: all .15s;
        }

        .layout-sidebar .nav-link:hover,
        .layout-sidebar .nav-link.active {
            background: rgba(255, 255, 255, .08);
            color: #fff;
        }

        .layout-sidebar .nav-link.active {
            font-weight: 600;
            background: rgba(217, 119, 6, .15);
            color: #fbbf24;
        }

        .layout-sidebar .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* ── Topbar ─────────────────────────────────────────────────── */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            height: 56px;
        }

        .topbar .brand {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -.02em;
            color: #1a1d23;
        }

        .topbar .brand span {
            color: #d97706;
        }

        .avatar-circle {
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
            box-shadow: 0 2px 4px rgba(217, 119, 6, .2);
        }

        /* ── Main Layout ─────────────────────────────────────────────── */
        #content {
            flex: 1;
            overflow-x: hidden;
            background-color: #f8f9fa;
        }

        .page-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: .75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
            overflow: hidden;
        }

        .page-card-header {
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
            background-color: #fff;
        }

        /* ── Components ─────────────────────────────────────────────── */
        .product-card {
            cursor: pointer;
            transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0.75rem !important;
        }

        .product-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-4px);
            border-color: #fbbf24 !important;
        }

        #orderPanel {
            width: 100%;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            position: sticky;
            top: 80px;
        }

        /* Ensure the panel doesn't look too stretched on very wide screens */
        @media (min-width: 1400px) {
            #orderPanel {
                max-width: 360px;
                margin-left: auto;
            }
        }

        @media (max-width: 991.98px) {
            #orderPanel {
                position: relative !important;
                top: 0 !important;
                max-height: none;
                margin-bottom: 2rem;
            }
        }

        .table > :not(caption) > * > * {
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }

        .price-badge {
            font-size: .75rem;
            font-weight: 700;
            padding: .35em .65em;
        }

        /* ── Custom Scrollbar ────────────────────────────────────────── */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ── Responsive Overrides ─────────────────────────────────────── */
        @media (max-width: 767.98px) {
            .topbar .brand {
                font-size: 1.1rem;
            }
            
            .page-card {
                border-radius: 0.5rem;
            }

            .main-content-padding {
                padding: 1rem !important;
            }
            
            #orderPanel {
                position: relative !important;
                top: 0 !important;
                margin-bottom: 1.5rem;
                max-height: none;
            }
        }

        /* Utility for responsive tables on very small screens */
        @media (max-width: 575.98px) {
            .table-responsive-stack .table,
            .table-responsive-stack tbody,
            .table-responsive-stack tr,
            .table-responsive-stack td {
                display: block;
                width: 100%;
            }
            .table-responsive-stack thead {
                display: none;
            }
            .table-responsive-stack tr {
                margin-bottom: 1rem;
                border: 1px solid #e9ecef;
                border-radius: 0.5rem;
                padding: 0.5rem;
                background: #fff;
            }
            .table-responsive-stack td {
                text-align: left !important;
                border: none;
                padding: 0.25rem 0.5rem;
            }
            .table-responsive-stack td::before {
                content: attr(data-label);
                font-weight: 700;
                display: block;
                font-size: 0.75rem;
                color: #64748b;
                text-transform: uppercase;
                margin-bottom: 0.1rem;
            }
        }
    </style>
</head>

<body>
    <nav class="topbar d-flex align-items-center px-3 px-lg-4 sticky-top shadow-sm" style="z-index:1030;">
        <a href="<?= $isAdmin ? '?page=manual-order' : '?page=home' ?>" class="brand text-decoration-none">
            <i class="bi bi-cup-hot-fill text-warning me-1"></i>Cafe<span>ria</span>
        </a>

        <div class="d-flex align-items-center gap-2 ms-auto">
            <div class="avatar-circle"><?= htmlspecialchars($userInitials) ?></div>
            <span class="d-none d-sm-inline fw-semibold small"><?= htmlspecialchars($userName) ?></span>
            <a class="btn btn-sm btn-outline-danger" href="?page=logout">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
            <button class="btn btn-sm btn-light d-md-none border" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start" style="width:260px;background:#1a1d23;" tabindex="-1" id="mobileNav">
        <div class="offcanvas-header border-bottom border-secondary">
            <span class="text-white fw-bold"><i class="bi bi-cup-hot-fill text-warning me-1"></i>Cafeteria</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <?php
            $sidebarClass = 'layout-sidebar d-flex flex-column';
            include __DIR__ . '/sidebar.php';
            ?>
        </div>
    </div>

    <div class="d-flex" style="min-height:calc(100vh - 56px);">
        <?php
        $sidebarClass = 'layout-sidebar d-none d-md-flex flex-column';
        include __DIR__ . '/sidebar.php';
        ?>

        <main id="content" class="p-3 p-lg-4 flex-grow-1">
            <?php include __DIR__ . '/flash.php'; ?>