<?php
/**
 * Transporter API - Accept Bid (Buyer Side)
 * POST /api/transporter/bids/accept.php
 * Body: { "bid_id": 123 }
 * Note: This endpoint should be called by buyers, but placed here for API consistency
 */

require_once '../../../config/config.php';

header('Content-Type: application/json');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check if user is authenticated and is buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'BUYER') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'Buyer authentication required'
    ]);
    exit;
}

function sendSuccess($data = null, $message = 'Success', $code = 200) {
    http_response_code($code);
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

function sendError($message = 'Error', $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function getJsonBody() {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON', 400);
    }
    return $data;
}

function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field])) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing), 400);
    }
    return true;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $data = getJsonBody();
    validateRequired($data, ['bid_id']);
    
    $buyer_id = $_SESSION['user_id'];
    $bid_id = intval($data['bid_id']);
    
    if ($bid_id <= 0) {
        sendError('Invalid bid ID', 400);
    }
    
    // Get bid details and verify ownership
    $stmt = $pdo->prepare("
        SELECT 
            db.bid_id,
            db.job_id,
            db.transporter_id,
            db.bid_amount,
            db.status,
            dj.order_id,
            o.buyer_id as order_buyer_id,
            dj.status as job_status
        FROM deliverybids db
        INNER JOIN deliveryjobs dj ON db.job_id = dj.job_id
        INNER JOIN orders o ON dj.order_id = o.order_id
        WHERE db.bid_id = :bid_id
    ");
    $stmt->execute([':bid_id' => $bid_id]);
    $bid = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bid) {
        sendError('Bid not found', 404);
    }
    
    // Verify buyer owns this order
    if ($bid['order_buyer_id'] != $buyer_id) {
        sendError('You do not have permission to accept this bid', 403);
    }
    
    if ($bid['status'] !== 'PENDING') {
        sendError('Bid is not pending', 400);
    }
    
    if ($bid['job_status'] !== 'OPEN' && $bid['job_status'] !== 'BIDDING') {
        sendError('Job is not available for bid acceptance', 400);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Reject all other bids for this job
        $stmt = $pdo->prepare("
            UPDATE deliverybids 
            SET status = 'REJECTED' 
            WHERE job_id = :job_id AND bid_id != :bid_id AND status = 'PENDING'
        ");
        $stmt->execute([
            ':job_id' => $bid['job_id'],
            ':bid_id' => $bid_id
        ]);
        
        // Accept the selected bid
        $stmt = $pdo->prepare("
            UPDATE deliverybids 
            SET status = 'ACCEPTED', updated_at = NOW() 
            WHERE bid_id = :bid_id
        ");
        $stmt->execute([':bid_id' => $bid_id]);
        
        // Update job status to ASSIGNED
        $stmt = $pdo->prepare("
            UPDATE deliveryjobs 
            SET status = 'ASSIGNED', updated_at = NOW() 
            WHERE job_id = :job_id
        ");
        $stmt->execute([':job_id' => $bid['job_id']]);
        
        // Update order status to PROCESSING
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'PROCESSING', updated_at = NOW() 
            WHERE order_id = :order_id
        ");
        $stmt->execute([':order_id' => $bid['order_id']]);
        
        // Create delivery record
        $stmt = $pdo->prepare("
            INSERT INTO deliveries (job_id, order_id, transporter_id, bid_id, status) 
            VALUES (:job_id, :order_id, :transporter_id, :bid_id, 'ASSIGNED')
        ");
        $stmt->execute([
            ':job_id' => $bid['job_id'],
            ':order_id' => $bid['order_id'],
            ':transporter_id' => $bid['transporter_id'],
            ':bid_id' => $bid_id
        ]);
        
        // Notify transporter (if notifications table exists)
        try {
            $pdo->query("SELECT 1 FROM notifications LIMIT 1");
            $notification_message = "Your bid of ৳" . number_format($bid['bid_amount'], 2) . " for Job #" . $bid['job_id'] . " has been accepted!";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)");
            $stmt->execute([
                ':user_id' => $bid['transporter_id'],
                ':title' => 'Bid Accepted',
                ':message' => $notification_message
            ]);
        } catch (PDOException $e) {
            // Notifications table might not exist, ignore
        }
        
        $pdo->commit();
        
        sendSuccess([
            'bid_id' => $bid_id,
            'job_id' => $bid['job_id'],
            'transporter_id' => $bid['transporter_id'],
            'status' => 'ACCEPTED'
        ], 'Bid accepted successfully');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

