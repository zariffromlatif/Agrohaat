<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$BASE_URL = 'http://localhost/Agrohaat/public/';

// Allow database override for tests via env vars or constants
$db_driver = getenv('DB_DRIVER') ?: 'mysql';
$db_host   = getenv('DB_HOST') ?: 'localhost';
$db_name   = getenv('DB_NAME') ?: (defined('TEST_DB_NAME') ? TEST_DB_NAME : 'agrohaat_db');
$db_user   = getenv('DB_USER') ?: 'root';
$db_pass   = getenv('DB_PASS') ?: '';

try {
    if ($db_driver === 'sqlite') {
        $sqlitePath = getenv('DB_SQLITE_PATH') ?: ':memory:';
        $pdo = new PDO("sqlite:$sqlitePath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } else {
        $pdo = new PDO(
            "$db_driver:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

if (!function_exists('redirect')) {
    function redirect($path) {
        global $BASE_URL;
        header("Location: " . $BASE_URL . $path);
        exit;
    }
}
