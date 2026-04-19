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

    // Products Operations : 
        case 'products':
        requireLogin();                 // or requireAdmin() if only admins see it
        ProductController::index();     // fetches data + includes the view itself
        break;
 
    // ── Add product form (GET) ────────────────────────────────────────────
    case 'add-product':
        requireAdmin();
        // Pass categories so the dropdown is populated
        $categories = Database::connect()
            ->query("SELECT id, name FROM categories ORDER BY name")
            ->fetchAll(PDO::FETCH_ASSOC);
        $product = null;                // null = "add" mode in the view
        include __DIR__ . '/../views/products/add_product.php';
        break;
 
    // ── Store new product (POST) ──────────────────────────────────────────
    case 'store-product':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ProductController::store(); // validates, inserts, redirects
        } else {
            header('Location: ?page=add-product');
            exit;
        }
        break;
 
    // ── Edit product form (GET) ───────────────────────────────────────────
    case 'edit-product':
        requireAdmin();
        $id      = (int) ($_GET['id'] ?? 0);
        $product = ProductController::show($id);
        if (!$product) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Product not found.'];
            header('Location: ?page=products'); exit;
        }
        $categories = Database::connect()
            ->query("SELECT id, name FROM categories ORDER BY name")
            ->fetchAll(PDO::FETCH_ASSOC);
        include __DIR__ . '/../views/products/add_product.php';
        break;
 
    // ── Update product (POST) ─────────────────────────────────────────────
    case 'update-product':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ProductController::update((int) ($_GET['id'] ?? 0));
        } else {
            header('Location: ?page=products'); exit;
        }
        break;
 
    // ── Soft-delete product ───────────────────────────────────────────────
    case 'delete-product':
        requireAdmin();
        ProductController::delete((int) ($_GET['id'] ?? 0));
        break;
 
    // ── Toggle availability (supports AJAX) ───────────────────────────────
    case 'toggle-product':
        requireAdmin();
        ProductController::toggle((int) ($_GET['id'] ?? 0));
        break;
 
    // ── Add category (AJAX only, called from the modal in add_product.php) ─
    case 'add-category':
        requireAdmin();
        header('Content-Type: application/json');
        $catName = trim($_POST['name'] ?? '');
        if ($catName === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Category name is required.']);
            exit;
        }
        try {
            $db   = Database::connect();
            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (:name)");
            $stmt->execute([':name' => $catName]);
            $newId = (int) $db->lastInsertId();
            echo json_encode(['id' => $newId, 'name' => $catName]);
        } catch (PDOException $e) {
            // Duplicate name → categories.name has a UNIQUE constraint
            http_response_code(422);
            echo json_encode(['error' => "Category \"$catName\" already exists."]);
        }
        exit;

       // ── All users ─────────────────────────────────────────────────────────
    case 'users':
        requireAdmin();
        UserController::index();        // fetches data + includes the view
        break;
 
    // ── Add user form (GET) ───────────────────────────────────────────────
    case 'add-user':
        requireAdmin();
        $user = null;                   // null = add mode
        include __DIR__ . '/../views/users/add_user.php';
        break;
 
    // ── Store new user (POST) ─────────────────────────────────────────────
    case 'store-user':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            UserController::store();
        } else {
            header('Location: ?page=add-user'); exit;
        }
        break;
 
    // ── Edit user form (GET) ──────────────────────────────────────────────
    case 'edit-user':
        requireAdmin();
        $id   = (int) ($_GET['id'] ?? 0);
        $user = UserController::show($id);
        if (!$user) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'User not found.'];
            header('Location: ?page=users'); exit;
        }
        include __DIR__ . '/../views/users/add_user.php';
        break;
 
    // ── Update user (POST) ────────────────────────────────────────────────
    case 'update-user':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            UserController::update((int) ($_GET['id'] ?? 0));
        } else {
            header('Location: ?page=users'); exit;
        }
        break;
 
    // ── Delete user ───────────────────────────────────────────────────────
    case 'delete-user':
        requireAdmin();
        UserController::delete((int) ($_GET['id'] ?? 0));
        break;
 
    // ── Toggle active status (AJAX-aware) ─────────────────────────────────
    case 'toggle-user':
        requireAdmin();
        UserController::toggleActive((int) ($_GET['id'] ?? 0));
        break;

// ── Admin order dashboard ─────────────────────────────────────────────
    case 'orders':
        requireAdmin();
        OrderController::index();           // fetches data + includes view
        break;
 
    // ── Order items (AJAX — returns JSON) ─────────────────────────────────
    case 'order-items':
        requireAdmin();
        OrderController::items();           // always JSON response
        break;
 
    // ── Store new order (POST + JSON body) ────────────────────────────────
    case 'store-order':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            OrderController::store();       // always JSON response
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }
        break;
 
    // ── Update order status (AJAX-aware) ─────────────────────────────────
    case 'update-order-status':
        requireAdmin();
        OrderController::updateStatus();    // JSON if AJAX, redirect otherwise
        break;
 
    // ── Cancel order ──────────────────────────────────────────────────────
    case 'cancel-order':
        requireAdmin();
        OrderController::cancel();
        break;
 
    // ── Manual order form ─────────────────────────────────────────────────
    case 'manual-order':
        requireAdmin();
        $db = Database::connect();
 
        // Users (non-admin only — admin places order for employees)
        $usersStmt = $db->query(
            "SELECT id, name FROM users WHERE role = 'user' AND isActive = 1 ORDER BY name"
        );
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
 
        // Locations
        $rooms = $db->query("SELECT id, details FROM locations ORDER BY details")
                    ->fetchAll(PDO::FETCH_ASSOC);
 
        // Available products
        $products = $db->query(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_available = 1 AND p.is_deleted = 0
             ORDER BY c.name, p.name"
        )->fetchAll(PDO::FETCH_ASSOC);
 
        // Categories for filter buttons
        $categories = $db->query("SELECT id, name FROM categories ORDER BY name")
                         ->fetchAll(PDO::FETCH_ASSOC);
 
        $pageTitle = 'Manual Order';
        $activeNav = 'manual-order';
        include __DIR__ . '/../views/admin/manual_order.php';
        break;

    case 'checks':
        requireAdmin();
        include __DIR__ . '/../views/admin/checks.php';
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
