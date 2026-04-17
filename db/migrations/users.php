<?php

function create_users_table($conn) {

    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `location_id` INT,
        `profile_pic` VARCHAR(255),
        -- Added DEFAULT 'user'
        `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
        `isActive` BOOLEAN DEFAULT true,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_user_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
    );
    ";

    $conn->exec($sql);
}