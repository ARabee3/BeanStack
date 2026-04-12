<?php

class Database {
    private static $host = 'localhost';
    private static $db_name = 'cafeteria_db'; // Change this to your database name
    private static $username = 'root';        // Change this to your DB user
    private static $password = '';            // Change this to your DB password
    private static $conn;

    public static function connect() {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name,
                    self::$username,
                    self::$password
                );
                // Set the PDO error mode to exception
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Default fetch mode to associative array
                self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$conn;
    }
}
