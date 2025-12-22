<?php
/**
 * Transporter Controller
 * Handles business logic for transporter operations
 */

require_once __DIR__ . '/../models/TransporterProfile.php';

class TransporterController {
    private $pdo;
    private $profileModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->profileModel = new TransporterProfile($pdo);
    }

    /**
     * Get transporter profile
     */
    public function getProfile($user_id) {
        return $this->profileModel->getByUserId($user_id);
    }

    /**
     * Save or update transporter profile
     */
    public function saveProfile($user_id, $vehicle_type, $license_plate, $max_capacity_kg, $service_area_districts) {
        // Validate inputs
        if (empty($vehicle_type) || empty($license_plate) || $max_capacity_kg <= 0) {
            return ['success' => false, 'message' => 'Invalid profile data'];
        }

        // Validate vehicle type
        $valid_vehicle_types = ['TRUCK', 'PICKUP', 'VAN', 'CNG', 'BOAT'];
        if (!in_array($vehicle_type, $valid_vehicle_types)) {
            return ['success' => false, 'message' => 'Invalid vehicle type'];
        }

        try {
            $result = $this->profileModel->save($user_id, $vehicle_type, $license_plate, $max_capacity_kg, $service_area_districts);
            
            if ($result) {
                return ['success' => true, 'message' => 'Profile saved successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to save profile'];
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'license_plate') !== false) {
                return ['success' => false, 'message' => 'This license plate is already registered'];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get available delivery jobs
     */
    public function getAvailableJobs($user_id, $filters = []) {
        $pickup_district = $filters['pickup_district'] ?? null;
        $dropoff_district = $filters['dropoff_district'] ?? null;
        $status = $filters['status'] ?? 'OPEN';
        $search = $filters['search'] ?? null;

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
                buyer.phone_number as buyer_phone,
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

        $sql .= " GROUP BY dj.job_id ORDER BY dj.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get job details
     */
    public function getJobDetails($job_id, $user_id = null) {
        $sql = "
            SELECT 
                dj.*,
                o.total_amount,
                o.buyer_id,
                buyer.full_name as buyer_name,
                buyer.phone_number as buyer_phone,
                buyer.district as buyer_district,
                COUNT(DISTINCT oi.product_id) as total_products,
                SUM(oi.quantity) as total_weight
            FROM deliveryjobs dj
            INNER JOIN orders o ON dj.order_id = o.order_id
            INNER JOIN users buyer ON o.buyer_id = buyer.user_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            WHERE dj.job_id = :job_id
            GROUP BY dj.job_id
        ";

        $params = [':job_id' => $job_id];

        if ($user_id) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM deliverybids db 
                WHERE db.job_id = dj.job_id 
                AND db.transporter_id = :transporter_id
            )";
            $params[':transporter_id'] = $user_id;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a bid on a job
     */
    public function createBid($job_id, $transporter_id, $bid_amount, $message = null) {
        // Validate bid amount
        if ($bid_amount <= 0) {
            return ['success' => false, 'message' => 'Bid amount must be greater than 0'];
        }

        // Check if job exists and is open
        $job = $this->getJobDetails($job_id);
        if (!$job) {
            return ['success' => false, 'message' => 'Job not found'];
        }

        if ($job['status'] !== 'OPEN' && $job['status'] !== 'BIDDING') {
            return ['success' => false, 'message' => 'Job is not available for bidding'];
        }

        // Check vehicle capacity
        $profile = $this->profileModel->getByUserId($transporter_id);
        if (!$profile) {
            return ['success' => false, 'message' => 'Profile not found'];
        }

        if ($job['total_weight'] > $profile['max_capacity_kg']) {
            return ['success' => false, 'message' => 'Job weight exceeds your vehicle capacity'];
        }

        // Check for existing bid
        $stmt = $this->pdo->prepare("
            SELECT bid_id FROM deliverybids 
            WHERE job_id = :job_id AND transporter_id = :transporter_id
        ");
        $stmt->execute([
            ':job_id' => $job_id,
            ':transporter_id' => $transporter_id
        ]);

        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'You have already placed a bid on this job'];
        }

        try {
            // Create bid
            $stmt = $this->pdo->prepare("
                INSERT INTO deliverybids (job_id, transporter_id, bid_amount, message, status) 
                VALUES (:job_id, :transporter_id, :bid_amount, :message, 'PENDING')
            ");
            $stmt->execute([
                ':job_id' => $job_id,
                ':transporter_id' => $transporter_id,
                ':bid_amount' => $bid_amount,
                ':message' => $message
            ]);

            $bid_id = $this->pdo->lastInsertId();

            // Update job status to BIDDING if first bid
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM deliverybids WHERE job_id = :job_id");
            $stmt->execute([':job_id' => $job_id]);
            $bid_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($bid_count == 1) {
                $stmt = $this->pdo->prepare("UPDATE deliveryjobs SET status = 'BIDDING' WHERE job_id = :job_id");
                $stmt->execute([':job_id' => $job_id]);
            }

            // Notify buyer
            $this->notifyBuyer($job['buyer_id'], $job_id, $bid_amount);

            return ['success' => true, 'message' => 'Bid created successfully', 'bid_id' => $bid_id];

        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique_job_transporter') !== false) {
                return ['success' => false, 'message' => 'You have already placed a bid on this job'];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get all bids for a transporter
     */
    public function getMyBids($transporter_id, $status = null) {
        $sql = "
            SELECT 
                db.*,
                dj.job_id,
                dj.order_id,
                dj.pickup_location,
                dj.dropoff_location,
                dj.status as job_status,
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

        $params = [':transporter_id' => $transporter_id];

        if ($status) {
            $sql .= " AND db.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " GROUP BY db.bid_id ORDER BY db.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Withdraw a bid
     */
    public function withdrawBid($bid_id, $transporter_id) {
        // Verify bid belongs to transporter
        $stmt = $this->pdo->prepare("
            SELECT bid_id, status FROM deliverybids 
            WHERE bid_id = :bid_id AND transporter_id = :transporter_id
        ");
        $stmt->execute([
            ':bid_id' => $bid_id,
            ':transporter_id' => $transporter_id
        ]);
        $bid = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bid) {
            return ['success' => false, 'message' => 'Bid not found'];
        }

        if ($bid['status'] !== 'PENDING') {
            return ['success' => false, 'message' => 'Only pending bids can be withdrawn'];
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE deliverybids SET status = 'WITHDRAWN' 
                WHERE bid_id = :bid_id AND transporter_id = :transporter_id
            ");
            $stmt->execute([
                ':bid_id' => $bid_id,
                ':transporter_id' => $transporter_id
            ]);

            return ['success' => true, 'message' => 'Bid withdrawn successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get deliveries for transporter
     */
    public function getMyDeliveries($transporter_id, $status = null) {
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
                COUNT(DISTINCT oi.product_id) as total_products,
                SUM(oi.quantity) as total_weight
            FROM deliveryjobs dj
            INNER JOIN orders o ON dj.order_id = o.order_id
            INNER JOIN users buyer ON o.buyer_id = buyer.user_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            INNER JOIN deliverybids db ON dj.job_id = db.job_id AND db.transporter_id = :transporter_id AND db.status = 'ACCEPTED'
            WHERE dj.status IN ('ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED')
        ";

        $params = [':transporter_id' => $transporter_id];

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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update delivery status
     */
    public function updateDeliveryStatus($job_id, $transporter_id, $new_status, $notes = null) {
        // Validate status
        $valid_statuses = ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'];
        if (!in_array($new_status, $valid_statuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        // Verify transporter has accepted bid
        $stmt = $this->pdo->prepare("
            SELECT dj.job_id, dj.status, dj.order_id, o.buyer_id, db.status as bid_status
            FROM deliveryjobs dj
            INNER JOIN orders o ON dj.order_id = o.order_id
            LEFT JOIN deliverybids db ON dj.job_id = db.job_id AND db.transporter_id = :transporter_id
            WHERE dj.job_id = :job_id
        ");
        $stmt->execute([
            ':job_id' => $job_id,
            ':transporter_id' => $transporter_id
        ]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            return ['success' => false, 'message' => 'Job not found'];
        }

        if ($job['bid_status'] !== 'ACCEPTED') {
            return ['success' => false, 'message' => 'You are not assigned to this delivery job'];
        }

        // Validate status progression
        $status_order = ['ASSIGNED' => 1, 'PICKED_UP' => 2, 'IN_TRANSIT' => 3, 'DELIVERED' => 4];
        $current_order = $status_order[$job['status']] ?? 0;
        $new_order = $status_order[$new_status] ?? 0;

        if ($new_order <= $current_order) {
            return ['success' => false, 'message' => 'Cannot move to previous or same status'];
        }

        try {
            $this->pdo->beginTransaction();

            // Update delivery job status
            $stmt = $this->pdo->prepare("
                UPDATE deliveryjobs SET status = :status, updated_at = NOW() 
                WHERE job_id = :job_id
            ");
            $stmt->execute([
                ':status' => $new_status,
                ':job_id' => $job_id
            ]);

            // Update delivery record
            if ($new_status === 'PICKED_UP') {
                $stmt = $this->pdo->prepare("
                    UPDATE deliveries SET status = 'PICKED_UP', pickup_time = NOW(), updated_at = NOW()
                    WHERE job_id = :job_id
                ");
            } elseif ($new_status === 'IN_TRANSIT') {
                $stmt = $this->pdo->prepare("
                    UPDATE deliveries SET status = 'IN_TRANSIT', updated_at = NOW()
                    WHERE job_id = :job_id
                ");
            } elseif ($new_status === 'DELIVERED') {
                $stmt = $this->pdo->prepare("
                    UPDATE deliveries SET status = 'DELIVERED', delivery_time = NOW(), notes = :notes, updated_at = NOW()
                    WHERE job_id = :job_id
                ");
                $stmt->execute([
                    ':job_id' => $job_id,
                    ':notes' => $notes
                ]);
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE deliveries SET status = :status, updated_at = NOW()
                    WHERE job_id = :job_id
                ");
                $stmt->execute([
                    ':status' => $new_status,
                    ':job_id' => $job_id
                ]);
            }

            // Update order status
            $order_status_map = [
                'ASSIGNED' => 'PROCESSING',
                'PICKED_UP' => 'PROCESSING',
                'IN_TRANSIT' => 'SHIPPED',
                'DELIVERED' => 'DELIVERED'
            ];

            $order_status = $order_status_map[$new_status] ?? null;
            if ($order_status) {
                $stmt = $this->pdo->prepare("
                    UPDATE orders SET status = :status, updated_at = NOW() 
                    WHERE order_id = :order_id
                ");
                $stmt->execute([
                    ':status' => $order_status,
                    ':order_id' => $job['order_id']
                ]);
            }

            // Notify buyer
            $this->notifyDeliveryStatus($job['buyer_id'], $job['order_id'], $new_status);

            // Notify farmer when delivered
            if ($new_status === 'DELIVERED') {
                $this->notifyFarmerDelivery($job['order_id']);
            }

            $this->pdo->commit();

            return ['success' => true, 'message' => 'Delivery status updated successfully'];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Notify buyer of new bid
     */
    private function notifyBuyer($buyer_id, $job_id, $bid_amount) {
        try {
            $pdo->query("SELECT 1 FROM notifications LIMIT 1");
            $message = "New delivery bid received for Job #" . $job_id . " - ৳" . number_format($bid_amount, 2);
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, title, message) 
                VALUES (:user_id, :title, :message)
            ");
            $stmt->execute([
                ':user_id' => $buyer_id,
                ':title' => 'New Bid Received',
                ':message' => $message
            ]);
        } catch (PDOException $e) {
            // Notifications table might not exist, ignore
        }
    }

    /**
     * Notify buyer of delivery status update
     */
    private function notifyDeliveryStatus($buyer_id, $order_id, $status) {
        try {
            $pdo->query("SELECT 1 FROM notifications LIMIT 1");
            $status_messages = [
                'PICKED_UP' => 'Your order #' . $order_id . ' has been picked up by the transporter',
                'IN_TRANSIT' => 'Your order #' . $order_id . ' is now in transit',
                'DELIVERED' => 'Your order #' . $order_id . ' has been delivered successfully'
            ];

            if (isset($status_messages[$status])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO notifications (user_id, title, message) 
                    VALUES (:user_id, :title, :message)
                ");
                $stmt->execute([
                    ':user_id' => $buyer_id,
                    ':title' => 'Delivery Update',
                    ':message' => $status_messages[$status]
                ]);
            }
        } catch (PDOException $e) {
            // Notifications table might not exist, ignore
        }
    }

    /**
     * Notify farmer when order is delivered
     */
    private function notifyFarmerDelivery($order_id) {
        try {
            $pdo->query("SELECT 1 FROM notifications LIMIT 1");
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT p.farmer_id 
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = :order_id
            ");
            $stmt->execute([':order_id' => $order_id]);
            $farmers = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($farmers as $farmer_id) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO notifications (user_id, title, message) 
                    VALUES (:user_id, :title, :message)
                ");
                $stmt->execute([
                    ':user_id' => $farmer_id,
                    ':title' => 'Order Delivered',
                    ':message' => 'Order #' . $order_id . ' has been successfully delivered to the buyer'
                ]);
            }
        } catch (PDOException $e) {
            // Notifications table might not exist, ignore
        }
    }
}

