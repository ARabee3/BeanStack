<?php

function create_products_table($conn) {

    $sql = "
    CREATE TABLE IF NOT EXISTS products (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `price` DECIMAL(10, 2) NOT NULL,
        `category_id` INT,
        `image` VARCHAR(255),
        `is_available` BOOLEAN DEFAULT true,
        `is_deleted` BOOLEAN DEFAULT false,
        CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    );
    ";

    $conn->exec($sql);
}