<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$BASE_URL = 'http://localhost/Agrohaat/public/';

$db_host = "localhost";
$db_name = "agrohaat_db";
$db_user = "root";
$db_pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

function redirect($path) {
    global $BASE_URL;
    header("Location: " . $BASE_URL . $path);
    exit;
}
