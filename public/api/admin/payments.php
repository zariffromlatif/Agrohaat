<?php
/**
 * Admin API - Payment Management
 * GET /api/admin/payments.php - List pending payments
 * GET /api/admin/payments.php?id={id} - Get specific payment
 */

require_once 'auth.php';
require_once '../../../models/Payment.php';

$paymentModel = new Payment($pdo);

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific payment
                $payment_id = intval($_GET['id']);
                $payment = $paymentModel->getPaymentById($payment_id);
                
                if (!$payment) {
                    sendError('Payment not found', 404);
                }
                
                sendSuccess($payment, 'Payment retrieved successfully');
            } else {
                // List pending payments
                $status = isset($_GET['status']) ? $_GET['status'] : null;
                
                // Build query
                $sql = "SELECT p.*, pm.name as method_name, pm.type as method_type, pm.provider,
                               o.order_id, o.total_amount as order_amount,
                               u.full_name as buyer_name, u.phone_number as buyer_phone, u.email as buyer_email
                        FROM payments p
                        JOIN payment_methods pm ON p.method_id = pm.method_id
                        JOIN orders o ON p.order_id = o.order_id
                        JOIN users u ON p.user_id = u.user_id";
                
                $params = [];
                
                if ($status) {
                    $sql .= " WHERE p.status = :status";
                    $params[':status'] = $status;
                } else {
                    $sql .= " WHERE p.status IN ('PENDING', 'PROCESSING')";
                }
                
                $sql .= " ORDER BY p.created_at ASC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendSuccess([
                    'payments' => $payments,
                    'total' => count($payments)
                ], 'Payments retrieved successfully');
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

