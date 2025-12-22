<?php
/**
 * Admin API Authentication Helper
 * Include this file at the top of all admin API endpoints
 */

require_once '../../../config/config.php';
require_once '../../../controllers/AdminController.php';

header('Content-Type: application/json');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check if user is authenticated and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'Admin authentication required'
    ]);
    exit;
}

// Initialize admin controller
$adminController = new AdminController($pdo);

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
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing), 400);
    }
    return true;
}

