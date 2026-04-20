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
        }

        .layout-sidebar {
            width: 240px;
            min-height: calc(100vh - 56px);
            background: #1a1d23;
            transition: width .2s;
            flex-shrink: 0;
        }

        .layout-sidebar .nav-link {
            color: #adb5bd;
            border-radius: .375rem;
            margin: 1px 0;
            font-size: .875rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .layout-sidebar .nav-link:hover,
        .layout-sidebar .nav-link.active {
            background: rgba(255, 255, 255, .08);
            color: #fff;
        }

        .layout-sidebar .nav-link.active {
            font-weight: 600;
        }

        .layout-sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }

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
        }

        #content {
            flex: 1;
            overflow-x: hidden;
        }

        .page-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: .75rem;
        }

        .page-card-header {
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
        }

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

        #orderPanel {
            min-width: 280px;
            max-width: 280px;
        }

        .table> :not(caption)>*>* {
            vertical-align: middle;
        }

        .detail-row td {
            background: #f8f9fa !important;
        }

        .page-link {
            color: #d97706;
        }

        .page-item.active .page-link {
            background: #d97706;
            border-color: #d97706;
            color: #fff;
        }

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

        .form-control:focus,
        .form-select:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 .2rem rgba(217, 119, 6, .2);
        }

        @media (max-width: 767.98px) {
            #orderPanel {
                min-width: 100%;
                max-width: 100%;
            }

            .layout-sidebar {
                min-height: auto;
                width: 100%;
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