<?php
require_once __DIR__ . '/../config/Database.php';

require_once __DIR__ . '/migrations/categories.php';
require_once __DIR__ . '/migrations/products.php';
require_once __DIR__ . '/migrations/locations.php';
require_once __DIR__ . '/migrations/users.php';
require_once __DIR__ . '/migrations/orders.php';
require_once __DIR__ . '/migrations/order_items.php';

$conn = Database::connect();

create_categories_table($conn);
create_locations_table($conn);
create_users_table($conn);
create_products_table($conn);
create_orders_table($conn);
create_order_items_table($conn);
