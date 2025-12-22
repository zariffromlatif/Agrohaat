<?php
/**
 * Admin API - Approve User
 * POST /api/admin/users/approve.php
 * Body: { "user_id": 123 }
 */

require_once '../auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $data = getJsonBody();
    validateRequired($data, ['user_id']);
    
    $user_id = intval($data['user_id']);
    
    if ($user_id <= 0) {
        sendError('Invalid user ID', 400);
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id, is_verified FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    if ($user['is_verified']) {
        sendError('User is already verified', 400);
    }
    
    // Approve user
    $success = $adminController->approveUser($user_id);
    
    if ($success) {
        sendSuccess(['user_id' => $user_id], 'User approved successfully');
    } else {
        sendError('Failed to approve user', 500);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

