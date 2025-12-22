<?php
/**
 * Admin API - Product Management
 * GET /api/admin/products.php - List all products
 * GET /api/admin/products.php?id={id} - Get specific product
 */

require_once 'auth.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific product
                $product_id = intval($_GET['id']);
                $stmt = $pdo->prepare("SELECT p.*, u.full_name AS farmer_name 
                                      FROM products p 
                                      JOIN users u ON u.user_id = p.farmer_id 
                                      WHERE p.product_id = :id");
                $stmt->execute([':id' => $product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    sendError('Product not found', 404);
                }
                
                sendSuccess($product, 'Product retrieved successfully');
            } else {
                // List all products with pagination
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
                $status = isset($_GET['status']) ? $_GET['status'] : null;
                
                // Validate limit
                if ($limit < 1 || $limit > 100) {
                    $limit = 50;
                }
                if ($offset < 0) {
                    $offset = 0;
                }
                
                $products = $adminController->getAllProducts($limit, $offset);
                
                // Filter by status if provided
                if ($status) {
                    $products = array_filter($products, function($p) use ($status) {
                        return $p['status'] === $status;
                    });
                    $products = array_values($products);
                }
                
                // Get total count
                $countSql = "SELECT COUNT(*) as total FROM products WHERE is_deleted = 0";
                if ($status) {
                    $countSql .= " AND status = :status";
                }
                $countStmt = $pdo->prepare($countSql);
                if ($status) {
                    $countStmt->execute([':status' => $status]);
                } else {
                    $countStmt->execute();
                }
                $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                sendSuccess([
                    'products' => $products,
                    'pagination' => [
                        'total' => (int)$total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total
                    ]
                ], 'Products retrieved successfully');
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

