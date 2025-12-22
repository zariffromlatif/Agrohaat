<?php
/**
 * Transporter API - Bid Management
 * GET /api/transporter/bids.php - List my bids
 * GET /api/transporter/bids.php?id={id} - Get specific bid
 */

require_once 'auth.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $user_id = $_SESSION['user_id'];
            
            if (isset($_GET['id'])) {
                // Get specific bid
                $bid_id = intval($_GET['id']);
                $stmt = $pdo->prepare("
                    SELECT 
                        db.*,
                        dj.job_id,
                        dj.order_id,
                        dj.pickup_location,
                        dj.dropoff_location,
                        dj.status as job_status,
                        o.total_amount,
                        buyer.full_name as buyer_name,
                        buyer.phone_number as buyer_phone
                    FROM deliverybids db
                    INNER JOIN deliveryjobs dj ON db.job_id = dj.job_id
                    INNER JOIN orders o ON dj.order_id = o.order_id
                    INNER JOIN users buyer ON o.buyer_id = buyer.user_id
                    WHERE db.bid_id = :bid_id AND db.transporter_id = :transporter_id
                ");
                $stmt->execute([
                    ':bid_id' => $bid_id,
                    ':transporter_id' => $user_id
                ]);
                $bid = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$bid) {
                    sendError('Bid not found', 404);
                }
                
                sendSuccess($bid, 'Bid retrieved successfully');
            } else {
                // List all bids for this transporter
                $status = isset($_GET['status']) ? $_GET['status'] : null;
                
                $sql = "
                    SELECT 
                        db.*,
                        dj.job_id,
                        dj.order_id,
                        dj.pickup_location,
                        dj.dropoff_location,
                        dj.status as job_status,
                        dj.created_at as job_created_at,
                        o.total_amount,
                        buyer.full_name as buyer_name,
                        buyer.district as buyer_district,
                        COUNT(DISTINCT oi.product_id) as total_products,
                        SUM(oi.quantity) as total_weight
                    FROM deliverybids db
                    INNER JOIN deliveryjobs dj ON db.job_id = dj.job_id
                    INNER JOIN orders o ON dj.order_id = o.order_id
                    INNER JOIN users buyer ON o.buyer_id = buyer.user_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    WHERE db.transporter_id = :transporter_id
                ";
                
                $params = [':transporter_id' => $user_id];
                
                if ($status) {
                    $sql .= " AND db.status = :status";
                    $params[':status'] = $status;
                }
                
                $sql .= " GROUP BY db.bid_id ORDER BY db.created_at DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendSuccess([
                    'bids' => $bids,
                    'total' => count($bids)
                ], 'Bids retrieved successfully');
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

