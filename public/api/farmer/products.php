<?php
/**
 * Farmer Product CRUD API
 * Complete RESTful API for product management
 */

require_once '../../../config/config.php';
require_once '../../../models/Product.php';

header('Content-Type: application/json');

$__API_TEST_MODE = defined('UNIT_TESTING') && UNIT_TESTING === true;

if (!function_exists('sendSuccess')) {
    /**
     * Send JSON success response
     */
    function sendSuccess($data = null, $message = 'Success', $code = 200) {
        $response = [
            'success' => true,
            'message' => $message
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($GLOBALS['__API_TEST_MODE'])) {
            return ['status' => $code, 'body' => $response];
        }

        http_response_code($code);
        echo json_encode($response);
        exit;
    }
}

if (!function_exists('sendError')) {
    /**
     * Send JSON error response
     */
    function sendError($message = 'Error', $code = 400, $errors = null) {
        $response = [
            'success' => false,
            'error' => $message
        ];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if (!empty($GLOBALS['__API_TEST_MODE'])) {
            return ['status' => $code, 'body' => $response];
        }

        http_response_code($code);
        echo json_encode($response);
        exit;
    }
}

if (!function_exists('getJsonBody')) {
    /**
     * Get JSON request body
     */
    function getJsonBody() {
        $body = $GLOBALS['__API_RAW_INPUT'] ?? file_get_contents('php://input');
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return sendError('Invalid JSON', 400);
        }
        return $data;
    }
}

if (!function_exists('validateRequired')) {
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
            return sendError('Missing required fields: ' . implode(', ', $missing), 400);
        }
        return true;
    }
}

if (!function_exists('handleImageUpload')) {
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
            return sendError('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.', 400);
        }
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($_FILES['image']['size'] > $maxSize) {
            return sendError('File size exceeds 5MB limit.', 400);
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
        
        return sendError('Image upload failed', 500);
    }
}

if (!function_exists('runFarmerProductsApi')) {
    /**
     * Execute the Farmer Products API using current superglobals.
     * Returns an array when UNIT_TESTING is enabled, otherwise echoes JSON and exits.
     */
    function runFarmerProductsApi(PDO $pdo) {
        // Handle CORS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (!empty($GLOBALS['__API_TEST_MODE'])) {
                return ['status' => 204, 'body' => null];
            }
            http_response_code(204);
            exit;
        }

        // Authentication check
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
            if (!empty($GLOBALS['__API_TEST_MODE'])) {
                return ['status' => 401, 'body' => [
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Farmer authentication required'
                ]];
            }

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

        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    if (isset($_GET['id'])) {
                        $product = $productModel->find((int)$_GET['id'], $farmerId);
                        if (!$product) {
                            return sendError('Product not found', 404);
                        }
                        return sendSuccess($product, 'Product retrieved successfully');
                    }

                    $products = $productModel->getByFarmer($farmerId);
                    return sendSuccess([
                        'products' => $products,
                        'total' => count($products)
                    ], 'Products retrieved successfully');

                case 'POST':
                    if (!empty($_FILES['image']['name'])) {
                        $data = $_POST;
                        $imagePath = handleImageUpload();
                    } else {
                        $data = getJsonBody();
                        $imagePath = null;
                    }
                    
                    $requiredFields = ['category_id', 'title', 'description', 'quantity_available', 'unit', 'price_per_unit', 'quality_grade', 'harvest_date', 'batch_number'];
                    $validationResult = validateRequired($data, $requiredFields);
                    if (is_array($validationResult)) {
                        return $validationResult;
                    }
                    
                    $validGrades = ['A', 'B', 'C', 'EXPORT_QUALITY'];
                    if (!in_array($data['quality_grade'], $validGrades)) {
                        return sendError('Invalid quality grade. Must be one of: ' . implode(', ', $validGrades), 400);
                    }
                    
                    if (!is_numeric($data['quantity_available']) || floatval($data['quantity_available']) <= 0) {
                        return sendError('Quantity must be a positive number', 400);
                    }
                    if (!is_numeric($data['price_per_unit']) || floatval($data['price_per_unit']) <= 0) {
                        return sendError('Price must be a positive number', 400);
                    }
                    
                    $trace_id = 'TRACE_' . time() . '_' . uniqid();
                    
                    global $BASE_URL;
                    $trace_url = $BASE_URL . 'trace.php?trace_id=' . urlencode($trace_id);
                    $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($trace_url);
                    
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
                        ':trace_id' => $trace_id,
                        ':qr_code_url' => $qr_code_url,
                    ];
                    
                    $result = $productModel->create($productData);
                    
                    if ($result) {
                        $productId = $pdo->lastInsertId();
                        $createdProduct = $productModel->find($productId, $farmerId);
                        return sendSuccess($createdProduct, 'Product created successfully', 201);
                    }

                    return sendError('Failed to create product', 500);

                case 'PUT':
                case 'PATCH':
                    $data = getJsonBody();
                    
                    if (empty($data['id'])) {
                        return sendError('Product ID is required', 400);
                    }
                    
                    $productId = intval($data['id']);
                    
                    $existingProduct = $productModel->find($productId, $farmerId);
                    if (!$existingProduct) {
                        return sendError('Product not found', 404);
                    }
                    
                    $imagePath = null;
                    if (!empty($_FILES['image']['name'])) {
                        $imagePath = handleImageUpload();
                    } elseif (isset($data['image_url'])) {
                        $imagePath = $data['image_url'];
                    }
                    
                    $updateData = [];
                    $allowedFields = ['category_id', 'title', 'description', 'quantity_available', 'unit', 'price_per_unit', 'quality_grade', 'harvest_date', 'batch_number'];
                    
                    foreach ($allowedFields as $field) {
                        if (isset($data[$field])) {
                            $updateData[':' . $field] = $field === 'category_id' ? intval($data[$field]) : 
                                                        (in_array($field, ['quantity_available', 'price_per_unit']) ? floatval($data[$field]) : trim($data[$field]));
                        }
                    }
                    
                    if (isset($data['quality_grade'])) {
                        $validGrades = ['A', 'B', 'C', 'EXPORT_QUALITY'];
                        if (!in_array($data['quality_grade'], $validGrades)) {
                            return sendError('Invalid quality grade', 400);
                        }
                    }
                    
                    if (isset($data['quantity_available']) && (!is_numeric($data['quantity_available']) || floatval($data['quantity_available']) <= 0)) {
                        return sendError('Quantity must be a positive number', 400);
                    }
                    if (isset($data['price_per_unit']) && (!is_numeric($data['price_per_unit']) || floatval($data['price_per_unit']) <= 0)) {
                        return sendError('Price must be a positive number', 400);
                    }
                    
                    if (empty($updateData) && !$imagePath) {
                        return sendError('No fields to update', 400);
                    }
                    
                    if (!empty($updateData)) {
                        $result = $productModel->update($productId, $farmerId, $updateData);
                    } else {
                        $result = true;
                    }
                    
                    if ($imagePath) {
                        $stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE product_id = ? AND farmer_id = ?");
                        $result = $stmt->execute([$imagePath, $productId, $farmerId]);
                    }
                    
                    if ($result) {
                        $updatedProduct = $productModel->find($productId, $farmerId);
                        return sendSuccess($updatedProduct, 'Product updated successfully');
                    }

                    return sendError('Failed to update product', 500);

                case 'DELETE':
                    $body = getJsonBody();
                    
                    if (empty($body['id'])) {
                        return sendError('Product ID is required', 400);
                    }
                    
                    $productId = intval($body['id']);
                    
                    $product = $productModel->find($productId, $farmerId);
                    if (!$product) {
                        return sendError('Product not found', 404);
                    }
                    
                    $result = $productModel->delete($productId, $farmerId);
                    
                    if ($result) {
                        return sendSuccess(null, 'Product deleted successfully');
                    }

                    return sendError('Failed to delete product', 500);

                default:
                    return sendError('Method not allowed', 405);
            }
        } catch (PDOException $e) {
            return sendError('Database error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            return sendError('Server error: ' . $e->getMessage(), 500);
        }
    }
}

if (!isset($GLOBALS['__API_TEST_MODE']) || $GLOBALS['__API_TEST_MODE'] === false) {
    runFarmerProductsApi($pdo);
}


