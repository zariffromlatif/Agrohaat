<?php
/**
 * Transporter API - Job Management
 * GET /api/transporter/jobs.php - List available delivery jobs
 * GET /api/transporter/jobs.php?id={id} - Get specific job
 */

require_once 'auth.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific job
                $job_id = intval($_GET['id']);
                $stmt = $pdo->prepare("
                    SELECT 
                        dj.job_id,
                        dj.order_id,
                        dj.pickup_location,
                        dj.dropoff_location,
                        dj.status,
                        dj.created_at,
                        o.total_amount,
                        o.buyer_id,
                        buyer.full_name as buyer_name,
                        buyer.phone_number as buyer_phone,
                        buyer.district as buyer_district,
                        farmer.full_name as farmer_name,
                        farmer.district as farmer_district,
                        COUNT(DISTINCT oi.product_id) as total_products,
                        SUM(oi.quantity) as total_weight,
                        COUNT(DISTINCT db.bid_id) as bid_count,
                        MIN(db.bid_amount) as lowest_bid
                    FROM deliveryjobs dj
                    INNER JOIN orders o ON dj.order_id = o.order_id
                    INNER JOIN users buyer ON o.buyer_id = buyer.user_id
                    INNER JOIN users farmer ON o.farmer_id = farmer.user_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    LEFT JOIN deliverybids db ON dj.job_id = db.job_id AND db.status = 'PENDING'
                    WHERE dj.job_id = :id AND dj.status = 'OPEN' AND o.payment_status = 'PAID'
                    GROUP BY dj.job_id
                ");
                $stmt->execute([':id' => $job_id]);
                $job = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$job) {
                    sendError('Job not found', 404);
                }
                
                sendSuccess($job, 'Job retrieved successfully');
            } else {
                // List available jobs with filters
                $pickup_district = isset($_GET['pickup_district']) ? $_GET['pickup_district'] : null;
                $dropoff_district = isset($_GET['dropoff_district']) ? $_GET['dropoff_district'] : null;
                $status = isset($_GET['status']) ? $_GET['status'] : 'OPEN';
                $search = isset($_GET['search']) ? $_GET['search'] : null;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
                
                // Validate limit
                if ($limit < 1 || $limit > 100) {
                    $limit = 50;
                }
                if ($offset < 0) {
                    $offset = 0;
                }
                
                $sql = "
                    SELECT 
                        dj.job_id,
                        dj.order_id,
                        dj.pickup_location,
                        dj.dropoff_location,
                        dj.status,
                        dj.created_at,
                        o.total_amount,
                        buyer.full_name as buyer_name,
                        buyer.district as buyer_district,
                        farmer.full_name as farmer_name,
                        farmer.district as farmer_district,
                        COUNT(DISTINCT oi.product_id) as total_products,
                        SUM(oi.quantity) as total_weight,
                        COUNT(DISTINCT db.bid_id) as bid_count,
                        MIN(db.bid_amount) as lowest_bid
                    FROM deliveryjobs dj
                    INNER JOIN orders o ON dj.order_id = o.order_id
                    INNER JOIN users buyer ON o.buyer_id = buyer.user_id
                    INNER JOIN users farmer ON o.farmer_id = farmer.user_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    LEFT JOIN deliverybids db ON dj.job_id = db.job_id AND db.status = 'PENDING'
                    WHERE dj.status = :status 
                    AND o.payment_status = 'PAID'
                    AND o.status IN ('PAID', 'CONFIRMED', 'PROCESSING')
                ";
                
                $params = [':status' => $status];
                
                if ($pickup_district) {
                    $sql .= " AND farmer.district LIKE :pickup_district";
                    $params[':pickup_district'] = "%$pickup_district%";
                }
                
                if ($dropoff_district) {
                    $sql .= " AND buyer.district LIKE :dropoff_district";
                    $params[':dropoff_district'] = "%$dropoff_district%";
                }
                
                if ($search) {
                    $sql .= " AND (dj.pickup_location LIKE :search OR dj.dropoff_location LIKE :search OR buyer.full_name LIKE :search OR farmer.full_name LIKE :search)";
                    $params[':search'] = "%$search%";
                }
                
                $sql .= " GROUP BY dj.job_id ORDER BY dj.created_at DESC LIMIT :limit OFFSET :offset";
                
                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get total count
                $countSql = "
                    SELECT COUNT(DISTINCT dj.job_id) as total
                    FROM deliveryjobs dj
                    INNER JOIN orders o ON dj.order_id = o.order_id
                    INNER JOIN users buyer ON o.buyer_id = buyer.user_id
                    INNER JOIN users farmer ON o.farmer_id = farmer.user_id
                    WHERE dj.status = :status 
                    AND o.payment_status = 'PAID'
                    AND o.status IN ('PAID', 'CONFIRMED', 'PROCESSING')
                ";
                
                $countParams = [':status' => $status];
                
                if ($pickup_district) {
                    $countSql .= " AND farmer.district LIKE :pickup_district";
                    $countParams[':pickup_district'] = "%$pickup_district%";
                }
                
                if ($dropoff_district) {
                    $countSql .= " AND buyer.district LIKE :dropoff_district";
                    $countParams[':dropoff_district'] = "%$dropoff_district%";
                }
                
                if ($search) {
                    $countSql .= " AND (dj.pickup_location LIKE :search OR dj.dropoff_location LIKE :search OR buyer.full_name LIKE :search OR farmer.full_name LIKE :search)";
                    $countParams[':search'] = "%$search%";
                }
                
                $countStmt = $pdo->prepare($countSql);
                $countStmt->execute($countParams);
                $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                sendSuccess([
                    'jobs' => $jobs,
                    'pagination' => [
                        'total' => (int)$total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total
                    ]
                ], 'Jobs retrieved successfully');
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

