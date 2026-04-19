<?php

/**
 * HomeController.php
 * Place in: app/Controllers/HomeController.php
 */

require_once __DIR__ . '/../../config/Database.php';

class HomeController
{
    public static function index(): void
    {
        requireLogin();

        $db = Database::connect();

        // Available, non-deleted products joined with their category
        $products = $db->query(
            "SELECT p.id, p.name, p.price, p.image, p.category_id,
                    c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_available = 1 AND p.is_deleted = 0
             ORDER BY c.name, p.name"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Only the location assigned to this user (from their profile)
        $roomStmt = $db->prepare(
            "SELECT l.id, l.details
             FROM locations l
             JOIN users u ON u.location_id = l.id
             WHERE u.id = ?"
        );
        $roomStmt->execute([$_SESSION['user_id']]);
        $rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

        // Categories for the filter buttons
        $categories = $db->query(
            "SELECT id, name FROM categories ORDER BY name"
        )->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Home';
        $activeNav = 'home';

        include __DIR__ . '/../../views/home.php';
    }
}