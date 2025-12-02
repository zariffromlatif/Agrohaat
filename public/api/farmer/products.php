<?php
// Simple JSON API for farmer product CRUD
require_once '../../../config/config.php';
require_once '../../../models/Product.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$farmerId = $_SESSION['user_id'];
$productModel = new Product($pdo);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // /api/farmer/products.php or ?id=XX
        if (isset($_GET['id'])) {
            $product = $productModel->find((int)$_GET['id'], $farmerId);
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
            } else {
                echo json_encode($product);
            }
        } else {
            $products = $productModel->getByFarmer($farmerId);
            echo json_encode($products);
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $body);
        if (empty($body['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            break;
        }
        $ok = $productModel->delete((int)$body['id'], $farmerId);
        echo json_encode(['success' => (bool)$ok]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}


