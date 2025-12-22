<?php
/**
 * Admin API - Delete Product
 * POST /api/admin/products/delete.php
 * Body: { "product_id": 123 }
 */

require_once '../auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $data = getJsonBody();
    validateRequired($data, ['product_id']);
    
    $product_id = intval($data['product_id']);
    
    if ($product_id <= 0) {
        sendError('Invalid product ID', 400);
    }
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT product_id, is_deleted FROM products WHERE product_id = :id");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        sendError('Product not found', 404);
    }
    
    if ($product['is_deleted']) {
        sendError('Product is already deleted', 400);
    }
    
    // Delete product (soft delete)
    $success = $adminController->deleteProduct($product_id);
    
    if ($success) {
        sendSuccess(['product_id' => $product_id], 'Product deleted successfully');
    } else {
        sendError('Failed to delete product', 500);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

