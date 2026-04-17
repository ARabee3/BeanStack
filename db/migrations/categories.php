<?php

function create_categories_table($conn) {

    $sql = "
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL
    );
    ";

    $conn->exec($sql);
}