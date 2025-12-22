<?php
/**
 * Farmer Product CRUD API
 * Complete RESTful API for product management
 */

require_once '../../../config/config.php';
require_once '../../../models/Product.php';

header('Content-Type: application/json');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'Farmer authentication required'
    ]);
    exit;
}

$farmerId = $_SESSION['user_id'];
$productModel = new Product($pdo);

/**
 * Send JSON success response
 */
function sendSuccess($data = null, $message = 'Success', $code = 200) {
    http_response_code($code);
    $response = [
        'success' => true,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

/**
 * Send JSON error response
 */
function sendError($message = 'Error', $code = 400, $errors = null) {
    http_response_code($code);
    $response = [
        'success' => false,
        'error' => $message
    ];
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    echo json_encode($response);
    exit;
}

/**
 * Get JSON request body
 */
function getJsonBody() {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON', 400);
    }
    return $data;
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing), 400);
    }
    return true;
}

/**
 * Handle image upload
 */
function handleImageUpload() {
    if (empty($_FILES['image']['name'])) {
        return null;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $_FILES['image']['type'];
    if (!in_array($fileType, $allowedTypes)) {
        sendError('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.', 400);
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($_FILES['image']['size'] > $maxSize) {
        sendError('File size exceeds 5MB limit.', 400);
    }
    
    $uploadDir = __DIR__ . '/../../../public/uploads/product_images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($_FILES['image']['name']);
    $fullPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
        return 'uploads/product_images/' . $fileName;
    }
    
    sendError('Image upload failed', 500);
}


try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // GET /api/farmer/products.php - List all products
            // GET /api/farmer/products.php?id={id} - Get single product
            if (isset($_GET['id'])) {
                $product = $productModel->find((int)$_GET['id'], $farmerId);
                if (!$product) {
                    sendError('Product not found', 404);
                }
                sendSuccess($product, 'Product retrieved successfully');
            } else {
                $products = $productModel->getByFarmer($farmerId);
                sendSuccess([
                    'products' => $products,
                    'total' => count($products)
                ], 'Products retrieved successfully');
            }
            break;

        case 'POST':
            // POST /api/farmer/products.php - Create new product
            // Supports both JSON and multipart/form-data
            
            if (!empty($_FILES['image']['name'])) {
                // Multipart form data (with file upload)
                $data = $_POST;
                $imagePath = handleImageUpload();
            } else {
                // JSON body
                $data = getJsonBody();
                $imagePath = null;
            }
            
            // Validate required fields
            $requiredFields = ['category_id', 'title', 'description', 'quantity_available', 'unit', 'price_per_unit', 'quality_grade', 'harvest_date', 'batch_number'];
            validateRequired($data, $requiredFields);
            
            // Validate quality grade
            $validGrades = ['A', 'B', 'C', 'EXPORT_QUALITY'];
            if (!in_array($data['quality_grade'], $validGrades)) {
                sendError('Invalid quality grade. Must be one of: ' . implode(', ', $validGrades), 400);
            }
            
            // Validate numeric fields
            if (!is_numeric($data['quantity_available']) || floatval($data['quantity_available']) <= 0) {
                sendError('Quantity must be a positive number', 400);
            }
            if (!is_numeric($data['price_per_unit']) || floatval($data['price_per_unit']) <= 0) {
                sendError('Price must be a positive number', 400);
            }
            
            // Prepare data for insertion
            $productData = [
                ':farmer_id' => $farmerId,
                ':category_id' => intval($data['category_id']),
                ':title' => trim($data['title']),
                ':description' => trim($data['description']),
                ':image_url' => $imagePath,
                ':quantity_available' => floatval($data['quantity_available']),
                ':unit' => trim($data['unit']),
                ':price_per_unit' => floatval($data['price_per_unit']),
                ':quality_grade' => $data['quality_grade'],
                ':harvest_date' => $data['harvest_date'],
                ':batch_number' => trim($data['batch_number']),
                ':trace_id' => null,
                ':qr_code_url' => null,
            ];
            
            // Create product
            $result = $productModel->create($productData);
            
            if ($result) {
                // Get created product by product_id (last insert id)
                $productId = $pdo->lastInsertId();
                $createdProduct = $productModel->find($productId, $farmerId);
                
                sendSuccess($createdProduct, 'Product created successfully', 201);
            } else {
                sendError('Failed to create product', 500);
            }
            break;

        case 'PUT':
        case 'PATCH':
            // PUT/PATCH /api/farmer/products.php - Update product
            $data = getJsonBody();
            
            if (empty($data['id'])) {
                sendError('Product ID is required', 400);
            }
            
            $productId = intval($data['id']);
            
            // Verify product belongs to farmer
            $existingProduct = $productModel->find($productId, $farmerId);
            if (!$existingProduct) {
                sendError('Product not found', 404);
            }
            
            // Handle image upload if provided
            $imagePath = null;
            if (!empty($_FILES['image']['name'])) {
                $imagePath = handleImageUpload();
            } elseif (isset($data['image_url'])) {
                $imagePath = $data['image_url'];
            }
            
            // Prepare update data
            $updateData = [];
            $allowedFields = ['category_id', 'title', 'description', 'quantity_available', 'unit', 'price_per_unit', 'quality_grade', 'harvest_date', 'batch_number'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[':' . $field] = $field === 'category_id' ? intval($data[$field]) : 
                                                (in_array($field, ['quantity_available', 'price_per_unit']) ? floatval($data[$field]) : trim($data[$field]));
                }
            }
            
            // Validate quality grade if provided
            if (isset($data['quality_grade'])) {
                $validGrades = ['A', 'B', 'C', 'EXPORT_QUALITY'];
                if (!in_array($data['quality_grade'], $validGrades)) {
                    sendError('Invalid quality grade', 400);
                }
            }
            
            // Validate numeric fields if provided
            if (isset($data['quantity_available']) && (!is_numeric($data['quantity_available']) || floatval($data['quantity_available']) <= 0)) {
                sendError('Quantity must be a positive number', 400);
            }
            if (isset($data['price_per_unit']) && (!is_numeric($data['price_per_unit']) || floatval($data['price_per_unit']) <= 0)) {
                sendError('Price must be a positive number', 400);
            }
            
            if (empty($updateData) && !$imagePath) {
                sendError('No fields to update', 400);
            }
            
            // Update product
            if (!empty($updateData)) {
                $result = $productModel->update($productId, $farmerId, $updateData);
            } else {
                $result = true; // Only image update
            }
            
            // Update image if provided
            if ($imagePath) {
                $stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE product_id = ? AND farmer_id = ?");
                $result = $stmt->execute([$imagePath, $productId, $farmerId]);
            }
            
            if ($result) {
                // Get updated product
                $updatedProduct = $productModel->find($productId, $farmerId);
                sendSuccess($updatedProduct, 'Product updated successfully');
            } else {
                sendError('Failed to update product', 500);
            }
            break;

        case 'DELETE':
            // DELETE /api/farmer/products.php - Delete product
            $body = getJsonBody();
            
            if (empty($body['id'])) {
                sendError('Product ID is required', 400);
            }
            
            $productId = intval($body['id']);
            
            // Verify product belongs to farmer
            $product = $productModel->find($productId, $farmerId);
            if (!$product) {
                sendError('Product not found', 404);
            }
            
            $result = $productModel->delete($productId, $farmerId);
            
            if ($result) {
                sendSuccess(null, 'Product deleted successfully');
            } else {
                sendError('Failed to delete product', 500);
            }
            break;

        default:
            sendError('Method not allowed', 405);
    }
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}


