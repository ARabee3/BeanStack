<?php
header('Content-Type: application/json');
session_start();

// Path to your Database class
require_once __DIR__ . '/../../config/Database.php'; 
$method = $_SERVER['REQUEST_METHOD'];
// For GET requests, we return the user's order history
if ($method === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    try {
        $db = Database::connect();
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT o.id, o.order_date as date, o.status, o.total_price as amount 
                FROM orders o 
                WHERE o.user_id = ? 
                ORDER BY o.order_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['products'])) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty. Please add products.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'You are not a registered user. Please log in to place an order.'
    ]);
    exit;
}


try {
    $db = Database::connect(); 
    $db->beginTransaction();

    // 1. Calculate Total Price
    $total = 0;
    foreach ($data['products'] as $item) {
        $total += ($item['qty'] * $item['price']);
    }

    // 2. Insert Main Order
    // We use session user_id or default to 1 (the user we just created in MySQL)
    $userId = $_SESSION['user_id'] ; 

    $sqlOrder = "INSERT INTO orders (user_id, total_price, notes, location_id, status) VALUES (?, ?, ?, ?, 'processing')";
    $stmt = $db->prepare($sqlOrder);
    $stmt->execute([
        $userId, 
        $total, 
        $data['notes'], 
        $data['location_id']
    ]);
    
    $orderId = $db->lastInsertId();

    // 3. Insert Order Items (Products)
    $sqlItems = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
    $stmtItem = $db->prepare($sqlItems);

    foreach ($data['products'] as $productId => $details) {
        $stmtItem->execute([
            $orderId, 
            $productId, 
            $details['qty'], 
            $details['price']
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}