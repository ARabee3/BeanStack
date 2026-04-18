<?php

require_once __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

class Database {
    private static $conn = null;

    public static function connect() {

        if (self::$conn === null) {

            $host = $_ENV["DB_HOST"];
            $db_name = $_ENV["DB_NAME"];
            $username = $_ENV["DB_USER"];
            $password = $_ENV["DB_PASS"];

            try {
                self::$conn = new PDO(
                    "mysql:host=$host;dbname=$db_name",
                    $username,
                    $password
                );

                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }

        return self::$conn;
    }
}
