<?php

require_once __DIR__ . '/../config/Database.php';

$conn = Database::connect();

try {
    $conn->beginTransaction();

    // =========================
    // 1. Categories
    // =========================
    $conn->exec("
        INSERT INTO categories (name) VALUES
        ('Drinks'),
        ('Snacks'),
        ('Meals');
    ");

    // =========================
    // 2. Locations
    // =========================
    $conn->exec("
        INSERT INTO locations (details) VALUES
        ('Room A - First Floor'),
        ('Room B - Second Floor'),
        ('Office 101');
    ");

    // =========================
    // 3. Users
    // =========================
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, location_id, role)
        VALUES (:name, :email, :password, :location_id, :role)
    ");

    $stmt->execute([
        "name" => "Admin User",
        "email" => "admin@mail.com",
        "password" => password_hash("password", PASSWORD_DEFAULT),
        "location_id" => 1,
        "role" => "admin"
    ]);

    $stmt->execute([
        "name" => "Normal User",
        "email" => "user@mail.com",
        "password" => password_hash("password", PASSWORD_DEFAULT),
        "location_id" => 2,
        "role" => "user"
    ]);

    // =========================
    // 4. Products
    // =========================
    $stmt = $conn->prepare("
        INSERT INTO products (name, price, category_id)
        VALUES (:name, :price, :category_id)
    ");

    $products = [
        ["Coffee", 20.00, 1],
        ["Tea", 15.00, 1],
        ["Chips", 10.00, 2],
        ["Burger", 50.00, 3]
    ];

    foreach ($products as $p) {
        $stmt->execute([
            "name" => $p[0],
            "price" => $p[1],
            "category_id" => $p[2]
        ]);
    }

    // =========================
    // 5. Orders
    // =========================
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, status, total_price, location_id, location_snapshot)
        VALUES (:user_id, :status, :total_price, :location_id, :location_snapshot)
    ");

    $stmt->execute([
        "user_id" => 2,
        "status" => "processing",
        "total_price" => 70.00,
        "location_id" => 2,
        "location_snapshot" => "Room B - Second Floor"
    ]);

    $orderId = $conn->lastInsertId();

    // =========================
    // 6. Order Items
    // =========================
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
        VALUES (:order_id, :product_id, :quantity, :price)
    ");

    // Coffee (20) x1 + Burger (50) x1 = 70
    $stmt->execute([
        "order_id" => $orderId,
        "product_id" => 1,
        "quantity" => 1,
        "price" => 20.00
    ]);

    $stmt->execute([
        "order_id" => $orderId,
        "product_id" => 4,
        "quantity" => 1,
        "price" => 50.00
    ]);

    // =========================
    $conn->commit();

} catch (Exception $e) {
    $conn->rollBack();
}
