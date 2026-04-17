<?php

function create_orders_table($conn) {

    $sql = "
    CREATE TABLE IF NOT EXISTS orders (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `status` ENUM('processing', 'out_for_delivery', 'canceled', 'done') NOT NULL DEFAULT 'processing',
        `total_price` DECIMAL(10, 2) NOT NULL,
        `notes` TEXT DEFAULT NULL,
        `location_id` INT,
        `location_snapshot` TEXT,
        `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
        CONSTRAINT `fk_order_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
    );
    ";

    $conn->exec($sql);
}