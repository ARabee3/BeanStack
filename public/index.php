<?php

/**
 * Front Controller (The single entry point)
 * All requests should go through this file.
 */

// 1. Error Reporting (For Development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Start Session (Global)
session_start();

require_once __DIR__ . '/../app/Helpers/Auth.php';

// 3. Simple Autoloader (To load Models/Controllers automatically)
spl_autoload_register(function ($class) {
    $paths = ['app/Controllers/', 'app/Models/', 'app/Helpers/', 'config/'];
    foreach ($paths as $path) {
        $file = __DIR__ . '/../' . $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/../app/Controllers/Auth/LoginController.php';
require_once __DIR__ . '/../app/Controllers/Auth/RegisterController.php';

// 4. Run migrations 
require_once __DIR__ . '/../db/run_migrations.php';

// 5. Basic Router
$page = $_GET['page'] ?? 'login'; // Default to login page

// Very basic routing logic:
switch ($page) {
    case 'login':
        redirectAuthenticatedUser();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../app/Controllers/Auth/LoginController.php';
            $loginController = 'LoginController';
            $loginController::handlePost();
        }
        include __DIR__ . '/../views/login.php';
        break;
    case 'register':
        redirectAuthenticatedUser();
        $registerController = 'RegisterController';
        $registerState = $registerController::handleRequest();
        $success = $registerState['success'];
        $errors = $registerState['errors'];
        include __DIR__ . '/../views/register.php';
        break;
    case 'forgot-password':
        include __DIR__ . '/../views/forgot_password.php';
        break;
    case 'dashboard':
        HomeController::index();
        break;
    case 'home':
        HomeController::index();
        break;
    case 'products':
        ProductController::index();
        break;
    case 'add-product':
        requireAdmin();
        include __DIR__ . '/../views/products/add_product.php';
        break;
    case 'users':
        requireAdmin();
        include __DIR__ . '/../views/users/all_users.php';
        break;
    case 'add-user':
        requireAdmin();
        include __DIR__ . '/../views/users/add_user.php';
        break;
    case 'manual-order':
        requireAdmin();
        include __DIR__ . '/../views/admin/manual_order.php';
        break;
    case 'checks':
        requireAdmin();
        include __DIR__ . '/../views/admin/checks.php';
        break;
    case 'orders':
        requireAdmin();
        include __DIR__ . '/../views/admin/orders.php';
        break;
    case 'my-orders':
        requireLogin();
        include __DIR__ . '/../views/orders/my_orders.php';
        break;
    case 'logout':
        logout();
        break;
    default:
        http_response_code(404);
        echo "404 - Page Not Found";
        break;
}
