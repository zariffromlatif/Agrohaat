<?php
require_once '../../config/config.php';

// Only transporters can place bids
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

// We need a job ID to place a bid on
if (!isset($_GET['job_id'])) {
    redirect('transporter/jobs.php');
}

$user_id = $_SESSION['user_id'];
$job_id = intval($_GET['job_id']);

// Get this transporter's profile to check vehicle capacity
$stmt = $pdo->prepare("SELECT * FROM transporter_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$transporter_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transporter_profile) {
    redirect('transporter/profile.php');
}

// Check if deliveryjobs table exists
$table_exists = false;
try {
    $pdo->query("SELECT 1 FROM deliveryjobs LIMIT 1");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

// Fetch all the details about this delivery job
if ($table_exists) {
    // Use deliveryjobs table
    $stmt = $pdo->prepare("
        SELECT 
            dj.*,
            o.total_amount,
            o.buyer_id,
            u.full_name as buyer_name,
            u.phone_number as buyer_phone,
            u.district as buyer_district,
            COUNT(DISTINCT oi.product_id) as total_products,
            SUM(oi.quantity) as total_weight
        FROM deliveryjobs dj
        INNER JOIN orders o ON dj.order_id = o.order_id
        INNER JOIN users u ON o.buyer_id = u.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE dj.job_id = ? AND dj.status = 'OPEN'
        GROUP BY dj.job_id
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if this transporter has already placed a bid
    $stmt = $pdo->prepare("SELECT * FROM deliverybids WHERE job_id = ? AND transporter_id = ?");
    $stmt->execute([$job_id, $user_id]);
    $existing_bid = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get other bids to show competitive pricing
    $stmt = $pdo->prepare("
        SELECT 
            db.bid_amount,
            db.created_at,
            u.full_name as transporter_name,
            tp.vehicle_type
        FROM deliverybids db
        INNER JOIN users u ON db.transporter_id = u.user_id
        INNER JOIN transporter_profiles tp ON u.user_id = tp.user_id
        WHERE db.job_id = ?
        ORDER BY db.bid_amount ASC
    ");
    $stmt->execute([$job_id]);
    $other_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fallback: treat job_id as order_id and fetch from orders directly
    $stmt = $pdo->prepare("
        SELECT 
            o.order_id as job_id,
            o.order_id,
            CONCAT(farmer.district, ', ', COALESCE(farmer.upazila, ''), ', ', COALESCE(farmer.address_details, '')) as pickup_location,
            o.shipping_address as dropoff_location,
            'OPEN' as status,
            o.total_amount,
            o.buyer_id,
            buyer.full_name as buyer_name,
            buyer.phone_number as buyer_phone,
            buyer.district as buyer_district,
            COUNT(DISTINCT oi.product_id) as total_products,
            SUM(oi.quantity) as total_weight
        FROM orders o
        INNER JOIN users buyer ON o.buyer_id = buyer.user_id
        INNER JOIN users farmer ON o.farmer_id = farmer.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.order_id = ? AND o.payment_status = 'PAID'
        GROUP BY o.order_id
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $existing_bid = null; // Can't check bids if deliverybids table doesn't exist
    $other_bids = [];
}

if (!$job) {
    $_SESSION['error'] = "This job is no longer available or doesn't exist";
    redirect('transporter/jobs.php');
}

$success_message = '';
$error_message = '';

// Handle the bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid_amount = floatval($_POST['bid_amount']);
    $message = trim($_POST['message']);
    
    if ($bid_amount <= 0) {
        $error_message = "Please enter a valid bid amount";
    } elseif ($existing_bid) {
        $error_message = "You have already placed a bid on this job";
    } elseif (!$table_exists) {
        $error_message = "Delivery bidding system requires database tables. Please import database/transporter_delivery_tables.sql";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO deliverybids (job_id, transporter_id, bid_amount, message) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$job_id, $user_id, $bid_amount, $message]);
            
            // Let the buyer know about the new bid (if notifications table exists)
            if ($pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0) {
                $notification_message = "New delivery bid received for Job #" . $job_id . " - ৳" . number_format($bid_amount, 2);
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$job['buyer_id'], "New Bid Received", $notification_message]);
            }
            
            $success_message = "Your bid has been placed successfully!";
            
            // Redirect back to marketplace after a short delay
            header("refresh:2;url=jobs.php");
        } catch(PDOException $e) {
            $error_message = "Error placing bid: " . $e->getMessage();
        }
    }
}

$site_title = "Place Bid - Job #$job_id | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <a href="jobs.php" class="btn btn-secondary mb-4">
            ← Back to Marketplace
        </a>
        
        <div class="card shadow-lg">
            <div class="card-header bg-success text-white">
                <h1 class="mb-2">Submit Your Bid</h1>
                <p class="mb-0">Job #<?php echo $job_id; ?> • Order #<?php echo $job['order_id']; ?></p>
            </div>
            
            <div class="card-body p-4">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <strong>✓ Success!</strong> <?php echo htmlspecialchars($success_message); ?>
                        <br><small>Redirecting you back to the marketplace...</small>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <strong>✗ Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($existing_bid): ?>
                    <div class="alert alert-warning">
                        You already placed a bid of ৳<?php echo number_format($existing_bid['bid_amount'], 2); ?> on this job.
                    </div>
                <?php endif; ?>
                
                <?php if ($job['total_weight'] > $transporter_profile['max_capacity_kg']): ?>
                    <div class="alert alert-danger">
                        <strong>⚠️ Weight Warning:</strong> This delivery is <?php echo number_format($job['total_weight'], 2); ?> KG, 
                        which exceeds your vehicle's capacity of <?php echo number_format($transporter_profile['max_capacity_kg'], 2); ?> KG.
                    </div>
                <?php endif; ?>
                
                <div class="bg-light p-4 rounded mb-4">
                    <h3 class="mb-3">📋 Job Details</h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Buyer Name:</strong><br>
                            <?php echo htmlspecialchars($job['buyer_name']); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Phone Number:</strong><br>
                            <?php echo htmlspecialchars($job['buyer_phone']); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Total Products:</strong><br>
                            <?php echo $job['total_products']; ?> items
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Total Weight:</strong><br>
                            <?php echo number_format($job['total_weight'], 2); ?> KG
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Order Value:</strong><br>
                            ৳<?php echo number_format($job['total_amount'], 2); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>District:</strong><br>
                            <?php echo htmlspecialchars($job['buyer_district']); ?>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-white rounded border">
                        <h4 class="mb-3">📍 Delivery Route</h4>
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <div class="flex-fill p-3 bg-light rounded border">
                                <strong>🚚 Pickup Point</strong><br>
                                <?php echo htmlspecialchars($job['pickup_location']); ?>
                            </div>
                            <div class="text-success" style="font-size: 24px; font-weight: bold;">→</div>
                            <div class="flex-fill p-3 bg-light rounded border">
                                <strong>🏠 Delivery Point</strong><br>
                                <?php echo htmlspecialchars($job['dropoff_location']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($other_bids)): ?>
                    <div class="alert alert-warning">
                        <h4 class="mb-3">💡 Competitive Bids (<?php echo count($other_bids); ?> total)</h4>
                        <?php 
                        $display_count = min(3, count($other_bids));
                        for ($i = 0; $i < $display_count; $i++): 
                            $bid = $other_bids[$i];
                        ?>
                            <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded mb-2">
                                <span>
                                    <?php echo htmlspecialchars($bid['vehicle_type']); ?>
                                    <small class="text-muted ms-2">
                                        (<?php echo date('M d, h:i A', strtotime($bid['created_at'])); ?>)
                                    </small>
                                </span>
                                <span class="text-success fw-bold fs-5">৳<?php echo number_format($bid['bid_amount'], 2); ?></span>
                            </div>
                        <?php endfor; ?>
                        <?php if (count($other_bids) > 3): ?>
                            <p class="text-center mt-2 mb-0 text-muted">
                                + <?php echo count($other_bids) - 3; ?> more bids submitted
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$existing_bid): ?>
                    <div class="mt-4">
                        <h3 class="mb-3">💰 Your Bid Amount</h3>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="bid_amount" class="form-label fw-bold">Delivery Charge (৳) *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white fw-bold">৳</span>
                                    <input 
                                        type="number" 
                                        class="form-control form-control-lg" 
                                        id="bid_amount"
                                        name="bid_amount" 
                                        step="0.01"
                                        min="1"
                                        placeholder="0.00"
                                        required>
                                </div>
                                <small class="form-text text-muted">
                                    Enter your competitive delivery charge for this job
                                    <?php if (!empty($other_bids)): ?>
                                        | Current lowest bid: ৳<?php echo number_format($other_bids[0]['bid_amount'], 2); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="message" class="form-label fw-bold">Additional Notes (Optional)</label>
                                <textarea 
                                    class="form-control" 
                                    id="message"
                                    name="message" 
                                    rows="4"
                                    placeholder="Add any special conditions or notes for the buyer (e.g., delivery timeframe, special requirements, etc.)"
                                ></textarea>
                                <small class="form-text text-muted">
                                    Example: "Can deliver within 24 hours" or "Experienced in handling fragile agricultural products"
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Submit Bid →
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

