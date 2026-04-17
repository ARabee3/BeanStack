<?php

function create_locations_table($conn) {

    $sql = "
    CREATE TABLE IF NOT EXISTS `locations` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `details` TEXT NOT NULL
    );
    ";

    $conn->exec($sql);
}