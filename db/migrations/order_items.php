<?php

function create_order_items_table($conn) {

    $sql = "
    CREATE TABLE IF NOT EXISTS order_items (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `order_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `quantity` INT NOT NULL,
        `price_at_purchase` DECIMAL(10, 2) NOT NULL,
        CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
        CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    );
    ";

    $conn->exec($sql);
}