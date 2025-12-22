<?php
/**
 * Admin API - Dispute Management
 * GET /api/admin/disputes.php - List all disputes
 * GET /api/admin/disputes.php?id={id} - Get specific dispute
 */

require_once 'auth.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific dispute
                $dispute_id = intval($_GET['id']);
                $stmt = $pdo->prepare("SELECT d.*, 
                                              o.order_id, o.total_amount,
                                              u.full_name AS complainant_name
                                       FROM disputes d
                                       JOIN orders o ON o.order_id = d.order_id
                                       JOIN users u ON u.user_id = d.complainant_id
                                       WHERE d.dispute_id = :id");
                $stmt->execute([':id' => $dispute_id]);
                $dispute = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$dispute) {
                    sendError('Dispute not found', 404);
                }
                
                sendSuccess($dispute, 'Dispute retrieved successfully');
            } else {
                // List all disputes
                $status = isset($_GET['status']) ? $_GET['status'] : null;
                
                $disputes = $adminController->listDisputes();
                
                // Filter by status if provided
                if ($status) {
                    $disputes = array_filter($disputes, function($d) use ($status) {
                        return $d['status'] === $status;
                    });
                    $disputes = array_values($disputes);
                }
                
                sendSuccess([
                    'disputes' => $disputes,
                    'total' => count($disputes)
                ], 'Disputes retrieved successfully');
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

