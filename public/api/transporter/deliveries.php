<?php
/**
 * Transporter API - Delivery Management
 * GET /api/transporter/deliveries.php - List my deliveries
 * GET /api/transporter/deliveries.php?id={id} - Get specific delivery
 */

require_once 'auth.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $user_id = $_SESSION['user_id'];
            
            if (isset($_GET['id'])) {
                // Get specific delivery
                $job_id = intval($_GET['id']);
                $stmt = $pdo->prepare("
                    SELECT 
                        dj.*,
                        o.order_id,
                        o.total_amount,
                        o.status as order_status,
                        buyer.full_name as buyer_name,
                        buyer.phone_number as buyer_phone,
                        buyer.district as buyer_district,
                        db.bid_amount,
                        d.delivery_id,
                        d.tracking_number,
                        d.pickup_time,
                        d.delivery_time,
                        d.notes,
                        COUNT(DISTINCT oi.product_id) as total_products,
                        SUM(oi.quantity) as total_weight
                    FROM deliveryjobs dj
                    INNER JOIN orders o ON dj.order_id = o.order_id
                    INNER JOIN users buyer ON o.buyer_id = buyer.user_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    LEFT JOIN deliverybids db ON dj.job_id = db.job_id AND db.transporter_id = :transporter_id AND db.status = 'ACCEPTED'
                    LEFT JOIN deliveries d ON dj.job_id = d.job_id
                    WHERE dj.job_id = :job_id AND db.transporter_id = :transporter_id
                    GROUP BY dj.job_id
                ");
                $stmt->execute([
                    ':job_id' => $job_id,
                    ':transporter_id' => $user_id
                ]);
                $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$delivery) {
                    sendError('Delivery not found', 404);
                }
                
                sendSuccess($delivery, 'Delivery retrieved successfully');
            } else {
                // List all deliveries for this transporter
                $status = isset($_GET['status']) ? $_GET['status'] : null;
                
                $sql = "
                    SELECT 
                        dj.job_id,
                        dj.order_id,
                        dj.pickup_location,
                        dj.dropoff_location,
                        dj.status,
                        dj.created_at,
                        dj.updated_at,
                        o.total_amount,
                        o.status as order_status,
                        buyer.full_name as buyer_name,
                        buyer.phone_number as buyer_phone,
                        buyer.district as buyer_district,
                        db.bid_amount,
                        d.delivery_id,
                        d.tracking_number,
                        d.pickup_time,
                        d.delivery_time,
                        COUNT(DISTINCT oi.product_id) as total_products,
                        SUM(oi.quantity) as total_weight
                    FROM deliveryjobs dj
                    INNER JOIN orders o ON dj.order_id = o.order_id
                    INNER JOIN users buyer ON o.buyer_id = buyer.user_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    INNER JOIN deliverybids db ON dj.job_id = db.job_id AND db.transporter_id = :transporter_id AND db.status = 'ACCEPTED'
                    LEFT JOIN deliveries d ON dj.job_id = d.job_id
                    WHERE dj.status IN ('ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED')
                ";
                
                $params = [':transporter_id' => $user_id];
                
                if ($status) {
                    $sql .= " AND dj.status = :status";
                    $params[':status'] = $status;
                }
                
                $sql .= " GROUP BY dj.job_id ORDER BY 
                    CASE dj.status
                        WHEN 'IN_TRANSIT' THEN 1
                        WHEN 'PICKED_UP' THEN 2
                        WHEN 'ASSIGNED' THEN 3
                        WHEN 'DELIVERED' THEN 4
                        ELSE 5
                    END,
                    dj.created_at DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendSuccess([
                    'deliveries' => $deliveries,
                    'total' => count($deliveries)
                ], 'Deliveries retrieved successfully');
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

