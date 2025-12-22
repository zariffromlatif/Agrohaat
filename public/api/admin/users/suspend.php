<?php
/**
 * Admin API - Suspend/Unsuspend User
 * POST /api/admin/users/suspend.php
 * Body: { "user_id": 123, "action": "suspend" } or { "user_id": 123, "action": "unsuspend" }
 */

require_once '../auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $data = getJsonBody();
    validateRequired($data, ['user_id', 'action']);
    
    $user_id = intval($data['user_id']);
    $action = strtolower(trim($data['action']));
    
    if ($user_id <= 0) {
        sendError('Invalid user ID', 400);
    }
    
    if (!in_array($action, ['suspend', 'unsuspend'])) {
        sendError('Invalid action. Must be "suspend" or "unsuspend"', 400);
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id, is_deleted FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    // Prevent suspending admin accounts
    $roleStmt = $pdo->prepare("SELECT role FROM users WHERE user_id = :id");
    $roleStmt->execute([':id' => $user_id]);
    $userRole = $roleStmt->fetch(PDO::FETCH_ASSOC)['role'];
    
    if ($userRole === 'ADMIN' && $action === 'suspend') {
        sendError('Cannot suspend admin accounts', 403);
    }
    
    // Perform action
    if ($action === 'suspend') {
        if ($user['is_deleted']) {
            sendError('User is already suspended', 400);
        }
        $success = $adminController->suspendUser($user_id);
        $message = 'User suspended successfully';
    } else {
        if (!$user['is_deleted']) {
            sendError('User is not suspended', 400);
        }
        $success = $adminController->unsuspendUser($user_id);
        $message = 'User unsuspended successfully';
    }
    
    if ($success) {
        sendSuccess(['user_id' => $user_id, 'action' => $action], $message);
    } else {
        sendError('Failed to ' . $action . ' user', 500);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

