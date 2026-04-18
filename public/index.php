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

// 4. Run migrations 
require_once __DIR__ . '/../db/run_migrations.php';

// 5. Basic Router
$page = $_GET['page'] ?? 'login'; // Default to login page

// Very basic routing logic:
switch ($page) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../app/Controllers/Auth/LoginController.php';
            LoginController::handlePost();
        }
        include __DIR__ . '/../views/login.php';
        break;
    case 'home':
        include __DIR__ . '/../views/home.php';
        break;
    case 'products':
        include __DIR__ . '/../views/products/all_products.php';
        break;
    default:
        http_response_code(404);
        echo "404 - Page Not Found";
        break;
}
