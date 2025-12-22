<?php
/**
 * Transporter API - Update Delivery Status
 * POST /api/transporter/deliveries/update.php
 * Body: { "job_id": 123, "status": "PICKED_UP" }
 * Status options: "ASSIGNED", "PICKED_UP", "IN_TRANSIT", "DELIVERED"
 */

require_once '../auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $data = getJsonBody();
    validateRequired($data, ['job_id', 'status']);
    
    $user_id = $_SESSION['user_id'];
    $job_id = intval($data['job_id']);
    $new_status = strtoupper(trim($data['status']));
    $notes = isset($data['notes']) ? trim($data['notes']) : null;
    
    if ($job_id <= 0) {
        sendError('Invalid job ID', 400);
    }
    
    // Validate status
    $valid_statuses = ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'];
    if (!in_array($new_status, $valid_statuses)) {
        sendError('Invalid status. Must be one of: ' . implode(', ', $valid_statuses), 400);
    }
    
    // Get job details and verify transporter has accepted bid
    $stmt = $pdo->prepare("
        SELECT 
            dj.job_id,
            dj.status as current_status,
            dj.order_id,
            o.buyer_id,
            db.bid_id,
            db.status as bid_status
        FROM deliveryjobs dj
        INNER JOIN orders o ON dj.order_id = o.order_id
        LEFT JOIN deliverybids db ON dj.job_id = db.job_id AND db.transporter_id = :transporter_id
        WHERE dj.job_id = :job_id
    ");
    $stmt->execute([
        ':job_id' => $job_id,
        ':transporter_id' => $user_id
    ]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        sendError('Job not found', 404);
    }
    
    if ($job['bid_status'] !== 'ACCEPTED') {
        sendError('You are not assigned to this delivery job. Your bid must be accepted first.', 403);
    }
    
    // Validate status progression
    $status_order = ['ASSIGNED' => 1, 'PICKED_UP' => 2, 'IN_TRANSIT' => 3, 'DELIVERED' => 4];
    $current_order = $status_order[$job['current_status']] ?? 0;
    $new_order = $status_order[$new_status] ?? 0;
    
    if ($new_order <= $current_order) {
        sendError('Cannot move to previous or same status', 400);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update delivery job status
        $stmt = $pdo->prepare("
            UPDATE deliveryjobs 
            SET status = :status, updated_at = NOW() 
            WHERE job_id = :job_id
        ");
        $stmt->execute([
            ':status' => $new_status,
            ':job_id' => $job_id
        ]);
        
        // Update delivery record if exists
        $stmt = $pdo->prepare("
            UPDATE deliveries 
            SET status = :status, updated_at = NOW()
        ");
        
        if ($new_status === 'PICKED_UP') {
            $stmt = $pdo->prepare("
                UPDATE deliveries 
                SET status = 'PICKED_UP', pickup_time = NOW(), updated_at = NOW()
                WHERE job_id = :job_id
            ");
        } elseif ($new_status === 'IN_TRANSIT') {
            $stmt = $pdo->prepare("
                UPDATE deliveries 
                SET status = 'IN_TRANSIT', updated_at = NOW()
                WHERE job_id = :job_id
            ");
        } elseif ($new_status === 'DELIVERED') {
            $stmt = $pdo->prepare("
                UPDATE deliveries 
                SET status = 'DELIVERED', delivery_time = NOW(), updated_at = NOW()
                WHERE job_id = :job_id
            ");
        } else {
            $stmt = $pdo->prepare("
                UPDATE deliveries 
                SET status = :status, updated_at = NOW()
                WHERE job_id = :job_id
            ");
        }
        
        $stmt->execute([':job_id' => $job_id]);
        
        // Update order status to keep in sync
        $order_status_map = [
            'ASSIGNED' => 'PROCESSING',
            'PICKED_UP' => 'PROCESSING',
            'IN_TRANSIT' => 'SHIPPED',
            'DELIVERED' => 'DELIVERED'
        ];
        
        $order_status = $order_status_map[$new_status] ?? null;
        if ($order_status) {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = :status, updated_at = NOW() 
                WHERE order_id = :order_id
            ");
            $stmt->execute([
                ':status' => $order_status,
                ':order_id' => $job['order_id']
            ]);
        }
        
        // Add notes if provided
        if ($notes && $new_status === 'DELIVERED') {
            $stmt = $pdo->prepare("
                UPDATE deliveries 
                SET notes = :notes 
                WHERE job_id = :job_id
            ");
            $stmt->execute([
                ':notes' => $notes,
                ':job_id' => $job_id
            ]);
        }
        
        // Notify buyer (if notifications table exists)
        try {
            $pdo->query("SELECT 1 FROM notifications LIMIT 1");
            $status_messages = [
                'PICKED_UP' => 'Your order #' . $job['order_id'] . ' has been picked up by the transporter',
                'IN_TRANSIT' => 'Your order #' . $job['order_id'] . ' is now in transit',
                'DELIVERED' => 'Your order #' . $job['order_id'] . ' has been delivered successfully'
            ];
            
            if (isset($status_messages[$new_status])) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message) 
                    VALUES (:user_id, :title, :message)
                ");
                $stmt->execute([
                    ':user_id' => $job['buyer_id'],
                    ':title' => 'Delivery Update',
                    ':message' => $status_messages[$new_status]
                ]);
            }
            
            // Notify farmer when delivered
            if ($new_status === 'DELIVERED') {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT p.farmer_id 
                    FROM order_items oi
                    INNER JOIN products p ON oi.product_id = p.product_id
                    WHERE oi.order_id = :order_id
                ");
                $stmt->execute([':order_id' => $job['order_id']]);
                $farmers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($farmers as $farmer_id) {
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message) 
                        VALUES (:user_id, :title, :message)
                    ");
                    $stmt->execute([
                        ':user_id' => $farmer_id,
                        ':title' => 'Order Delivered',
                        ':message' => 'Order #' . $job['order_id'] . ' has been successfully delivered to the buyer'
                    ]);
                }
            }
        } catch (PDOException $e) {
            // Notifications table might not exist, ignore
        }
        
        $pdo->commit();
        
        sendSuccess([
            'job_id' => $job_id,
            'status' => $new_status,
            'order_id' => $job['order_id']
        ], 'Delivery status updated successfully');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

