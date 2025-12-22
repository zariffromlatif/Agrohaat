<?php
/**
 * Admin API - User Management
 * GET /api/admin/users.php - List all users
 * GET /api/admin/users.php?id={id} - Get specific user
 */

require_once 'auth.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific user
                $user_id = intval($_GET['id']);
                $stmt = $pdo->prepare("SELECT user_id, full_name, email, phone_number, role, district, upazila, is_verified, is_deleted, created_at 
                                      FROM users 
                                      WHERE user_id = :id");
                $stmt->execute([':id' => $user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    sendError('User not found', 404);
                }
                
                sendSuccess($user, 'User retrieved successfully');
            } else {
                // List all users with pagination
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
                $role = isset($_GET['role']) ? $_GET['role'] : null;
                
                // Validate limit
                if ($limit < 1 || $limit > 100) {
                    $limit = 50;
                }
                if ($offset < 0) {
                    $offset = 0;
                }
                
                $users = $adminController->getAllUsers($limit, $offset);
                
                // Get total count
                $countSql = "SELECT COUNT(*) as total FROM users";
                if ($role) {
                    $countSql .= " WHERE role = :role";
                }
                $countStmt = $pdo->prepare($countSql);
                if ($role) {
                    $countStmt->execute([':role' => $role]);
                } else {
                    $countStmt->execute();
                }
                $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                sendSuccess([
                    'users' => $users,
                    'pagination' => [
                        'total' => (int)$total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total
                    ]
                ], 'Users retrieved successfully');
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

