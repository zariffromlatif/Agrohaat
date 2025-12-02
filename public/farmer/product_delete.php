<?php
require_once '../../config/config.php';
require_once '../../controllers/ProductController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

if (empty($_GET['id'])) {
    redirect('farmer/products.php');
}

$productId  = (int) $_GET['id'];
$controller = new ProductController($pdo);
$controller->handleDelete($_SESSION['user_id'], $productId);
