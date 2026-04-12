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

// 4. Basic Router
$page = $_GET['page'] ?? 'login'; // Default to login page

// Very basic routing logic:
switch ($page) {
    case 'login':
        include __DIR__ . '/../views/login.php';
        break;
    case 'dashboard':
        include __DIR__ . '/../views/dashboard.php';
        break;
    case 'products':
        include __DIR__ . '/../views/products.php';
        break;
    default:
        echo "404 - Page Not Found";
        break;
}
