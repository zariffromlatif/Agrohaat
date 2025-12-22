<?php
/**
 * Admin API - Approve/Reject Payment
 * POST /api/admin/payments/approve.php
 * Body: { "payment_id": 123, "action": "approve", "notes": "Optional notes" }
 * or { "payment_id": 123, "action": "reject", "notes": "Required rejection reason" }
 */

require_once '../auth.php';
require_once '../../../models/Payment.php';

$paymentModel = new Payment($pdo);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $data = getJsonBody();
    validateRequired($data, ['payment_id', 'action']);
    
    $payment_id = intval($data['payment_id']);
    $action = strtolower(trim($data['action']));
    $notes = isset($data['notes']) ? trim($data['notes']) : null;
    
    if ($payment_id <= 0) {
        sendError('Invalid payment ID', 400);
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        sendError('Invalid action. Must be "approve" or "reject"', 400);
    }
    
    // Check if payment exists
    $payment = $paymentModel->getPaymentById($payment_id);
    
    if (!$payment) {
        sendError('Payment not found', 404);
    }
    
    // Check if payment is in pending/processing status
    if (!in_array($payment['status'], ['PENDING', 'PROCESSING'])) {
        sendError('Payment is already processed', 400);
    }
    
    // Require notes for rejection
    if ($action === 'reject' && empty($notes)) {
        sendError('Rejection reason (notes) is required', 400);
    }
    
    // Perform action
    if ($action === 'approve') {
        $success = $paymentModel->completePayment($payment_id, $notes);
        
        if ($success) {
            // Update order status
            $order_id = $payment['order_id'];
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'PAID', status = 'CONFIRMED' WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            // Create delivery job if it doesn't exist
            try {
                // Check if deliveryjobs table exists
                $pdo->query("SELECT 1 FROM deliveryjobs LIMIT 1");
                
                // Check if job already exists for this order
                $checkStmt = $pdo->prepare("SELECT job_id FROM deliveryjobs WHERE order_id = ?");
                $checkStmt->execute([$order_id]);
                $existingJob = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existingJob) {
                    // Get order details with farmer and buyer locations
                    $orderStmt = $pdo->prepare("
                        SELECT o.order_id, o.shipping_address,
                               farmer.district as farmer_district, 
                               farmer.upazila as farmer_upazila,
                               farmer.address_details as farmer_address
                        FROM orders o
                        INNER JOIN users farmer ON o.farmer_id = farmer.user_id
                        WHERE o.order_id = ?
                    ");
                    $orderStmt->execute([$order_id]);
                    $orderDetails = $orderStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($orderDetails) {
                        // Build pickup location from farmer's address
                        $pickup_location = trim(
                            ($orderDetails['farmer_district'] ?? '') . ', ' .
                            ($orderDetails['farmer_upazila'] ?? '') . ', ' .
                            ($orderDetails['farmer_address'] ?? '')
                        );
                        $pickup_location = rtrim($pickup_location, ', ');
                        
                        // Use order shipping address as dropoff location
                        $dropoff_location = $orderDetails['shipping_address'];
                        
                        // Create delivery job
                        $jobStmt = $pdo->prepare("
                            INSERT INTO deliveryjobs (order_id, pickup_location, dropoff_location, status)
                            VALUES (?, ?, ?, 'OPEN')
                        ");
                        $jobStmt->execute([$order_id, $pickup_location, $dropoff_location]);
                    }
                }
            } catch (PDOException $e) {
                // If deliveryjobs table doesn't exist, just log and continue
                error_log("Could not create delivery job: " . $e->getMessage());
            }
            
            sendSuccess([
                'payment_id' => $payment_id,
                'action' => 'approved'
            ], 'Payment approved successfully');
        } else {
            sendError('Failed to approve payment', 500);
        }
    } else {
        $success = $paymentModel->failPayment($payment_id, $notes);
        
        if ($success) {
            sendSuccess([
                'payment_id' => $payment_id,
                'action' => 'rejected'
            ], 'Payment rejected successfully');
        } else {
            sendError('Failed to reject payment', 500);
        }
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

