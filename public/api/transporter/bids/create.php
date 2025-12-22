<?php
/**
 * Transporter API - Create Bid
 * POST /api/transporter/bids/create.php
 * Body: { "job_id": 123, "bid_amount": 500.00, "message": "Optional message" }
 */

require_once '../auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $data = getJsonBody();
    validateRequired($data, ['job_id', 'bid_amount']);
    
    $user_id = $_SESSION['user_id'];
    $job_id = intval($data['job_id']);
    $bid_amount = floatval($data['bid_amount']);
    $message = isset($data['message']) ? trim($data['message']) : null;
    
    if ($job_id <= 0) {
        sendError('Invalid job ID', 400);
    }
    
    if ($bid_amount <= 0) {
        sendError('Bid amount must be greater than 0', 400);
    }
    
    // Check if job exists and is open
    $stmt = $pdo->prepare("
        SELECT dj.job_id, dj.status, o.buyer_id, o.payment_status,
               SUM(oi.quantity) as total_weight
        FROM deliveryjobs dj
        INNER JOIN orders o ON dj.order_id = o.order_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE dj.job_id = :id
        GROUP BY dj.job_id
    ");
    $stmt->execute([':id' => $job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        sendError('Job not found', 404);
    }
    
    if ($job['status'] !== 'OPEN') {
        sendError('Job is not available for bidding', 400);
    }
    
    if ($job['payment_status'] !== 'PAID') {
        sendError('Order payment is not confirmed', 400);
    }
    
    // Check vehicle capacity
    $stmt = $pdo->prepare("SELECT max_capacity_kg FROM transporter_profiles WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($job['total_weight'] > $profile['max_capacity_kg']) {
        sendError('Job weight exceeds your vehicle capacity', 400);
    }
    
    // Check if transporter already placed a bid
    $stmt = $pdo->prepare("SELECT bid_id FROM deliverybids WHERE job_id = :job_id AND transporter_id = :transporter_id");
    $stmt->execute([
        ':job_id' => $job_id,
        ':transporter_id' => $user_id
    ]);
    $existing_bid = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_bid) {
        sendError('You have already placed a bid on this job', 400);
    }
    
    // Create bid
    $stmt = $pdo->prepare("
        INSERT INTO deliverybids (job_id, transporter_id, bid_amount, message, status) 
        VALUES (:job_id, :transporter_id, :bid_amount, :message, 'PENDING')
    ");
    $stmt->execute([
        ':job_id' => $job_id,
        ':transporter_id' => $user_id,
        ':bid_amount' => $bid_amount,
        ':message' => $message
    ]);
    
    $bid_id = $pdo->lastInsertId();
    
    // Update job status to BIDDING if first bid
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM deliverybids WHERE job_id = :job_id");
    $stmt->execute([':job_id' => $job_id]);
    $bid_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($bid_count == 1) {
        $stmt = $pdo->prepare("UPDATE deliveryjobs SET status = 'BIDDING' WHERE job_id = :job_id");
        $stmt->execute([':job_id' => $job_id]);
    }
    
    // Notify buyer (if notifications table exists)
    try {
        $pdo->query("SELECT 1 FROM notifications LIMIT 1");
        $notification_message = "New delivery bid received for Job #" . $job_id . " - ৳" . number_format($bid_amount, 2);
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)");
        $stmt->execute([
            ':user_id' => $job['buyer_id'],
            ':title' => 'New Bid Received',
            ':message' => $notification_message
        ]);
    } catch (PDOException $e) {
        // Notifications table might not exist, ignore
    }
    
    sendSuccess([
        'bid_id' => $bid_id,
        'job_id' => $job_id,
        'bid_amount' => $bid_amount,
        'status' => 'PENDING'
    ], 'Bid created successfully', 201);
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'unique_job_transporter') !== false) {
        sendError('You have already placed a bid on this job', 400);
    } else {
        sendError('Database error: ' . $e->getMessage(), 500);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

