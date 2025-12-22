<?php
require_once '../../config/config.php';

// Security check - transporters only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

// Need a job ID to track anything
if (!isset($_GET['job_id'])) {
    redirect('transporter/jobs.php');
}

$user_id = $_SESSION['user_id'];
$job_id = intval($_GET['job_id']);

// Check if deliveryjobs table exists
$table_exists = false;
try {
    $pdo->query("SELECT 1 FROM deliveryjobs LIMIT 1");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

if (!$table_exists) {
    $_SESSION['error'] = "Delivery tracking requires deliveryjobs table. Please import database/transporter_delivery_tables.sql";
    redirect('transporter/jobs.php');
}

// Grab all the details about this delivery job
$stmt = $pdo->prepare("
    SELECT 
        dj.*,
        o.order_id,
        o.total_amount,
        o.status as order_status,
        o.buyer_id,
        buyer.full_name as buyer_name,
        buyer.phone_number as buyer_phone,
        buyer.district as buyer_district,
        buyer.address_details as buyer_address,
        db.bid_amount,
        db.status as bid_status,
        COUNT(DISTINCT oi.product_id) as total_products,
        SUM(oi.quantity) as total_weight
    FROM deliveryjobs dj
    INNER JOIN orders o ON dj.order_id = o.order_id
    INNER JOIN users buyer ON o.buyer_id = buyer.user_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN deliverybids db ON dj.job_id = db.job_id AND db.transporter_id = ? AND db.status = 'ACCEPTED'
    WHERE dj.job_id = ?
    GROUP BY dj.job_id
");
$stmt->execute([$user_id, $job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    $_SESSION['error'] = "Job not found";
    redirect('transporter/jobs.php');
}

// Verify this transporter has an accepted bid for this job
if ($job['bid_status'] !== 'ACCEPTED') {
    $_SESSION['error'] = "You are not assigned to this delivery job. Your bid must be accepted first.";
    redirect('transporter/jobs.php');
}

$success_message = '';
$error_message = '';

// Handle status updates when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];
    $valid_statuses = ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'];
    
    if (!in_array($new_status, $valid_statuses)) {
        $error_message = "Invalid status";
    } else {
        try {
            // Using a transaction to ensure all updates happen together
            $pdo->beginTransaction();
            
            // Update the delivery job status
            $stmt = $pdo->prepare("UPDATE deliveryjobs SET status = ? WHERE job_id = ?");
            $stmt->execute([$new_status, $job_id]);
            
            // Update delivery record if exists
            if ($new_status === 'PICKED_UP') {
                $stmt = $pdo->prepare("UPDATE deliveries SET status = 'PICKED_UP', pickup_time = NOW(), updated_at = NOW() WHERE job_id = ?");
                $stmt->execute([$job_id]);
            } elseif ($new_status === 'IN_TRANSIT') {
                $stmt = $pdo->prepare("UPDATE deliveries SET status = 'IN_TRANSIT', updated_at = NOW() WHERE job_id = ?");
                $stmt->execute([$job_id]);
            } elseif ($new_status === 'DELIVERED') {
                $stmt = $pdo->prepare("UPDATE deliveries SET status = 'DELIVERED', delivery_time = NOW(), updated_at = NOW() WHERE job_id = ?");
                $stmt->execute([$job_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE deliveries SET status = ?, updated_at = NOW() WHERE job_id = ?");
                $stmt->execute([$new_status, $job_id]);
            }
            
            // Also update the main order status to keep everything in sync
            $order_status = '';
            switch ($new_status) {
                case 'ASSIGNED':
                    $order_status = 'PROCESSING';
                    break;
                case 'PICKED_UP':
                    $order_status = 'PROCESSING';
                    break;
                case 'IN_TRANSIT':
                    $order_status = 'SHIPPED';
                    break;
                case 'DELIVERED':
                    $order_status = 'DELIVERED';
                    break;
            }
            
            if ($order_status) {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                $stmt->execute([$order_status, $job['order_id']]);
            }
            
            // Notify the buyer about the status change (if notifications table exists)
            if ($pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0) {
                $status_messages = [
                    'ASSIGNED' => 'Your delivery job #' . $job_id . ' has been accepted by the transporter',
                    'PICKED_UP' => 'Your order #' . $job['order_id'] . ' has been picked up by the transporter',
                    'IN_TRANSIT' => 'Your order #' . $job['order_id'] . ' is now in transit',
                    'DELIVERED' => 'Your order #' . $job['order_id'] . ' has been delivered successfully'
                ];
                
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $job['buyer_id'], 
                    "Delivery Update", 
                    $status_messages[$new_status] ?? "Delivery status updated"
                ]);
                
                // When delivery is complete, notify the farmers too
                if ($new_status === 'DELIVERED') {
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT p.farmer_id 
                        FROM order_items oi
                        INNER JOIN products p ON oi.product_id = p.product_id
                        WHERE oi.order_id = ?
                    ");
                    $stmt->execute([$job['order_id']]);
                    $farmers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($farmers as $farmer_id) {
                        $stmt = $pdo->prepare("
                            INSERT INTO notifications (user_id, title, message) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([
                            $farmer_id,
                            "Order Delivered",
                            "Order #" . $job['order_id'] . " has been successfully delivered to the buyer"
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            
            $success_message = "Status updated successfully!";
            
            // Refresh the job data to show the updated status
            $stmt = $pdo->prepare("
                SELECT 
                    dj.*,
                    o.order_id,
                    o.total_amount,
                    o.status as order_status,
                    o.buyer_id,
                    buyer.full_name as buyer_name,
                    buyer.phone_number as buyer_phone,
                    buyer.district as buyer_district,
                    buyer.address_details as buyer_address,
                    db.bid_amount,
                    db.status as bid_status,
                    COUNT(DISTINCT oi.product_id) as total_products,
                    SUM(oi.quantity) as total_weight
                FROM deliveryjobs dj
                INNER JOIN orders o ON dj.order_id = o.order_id
                INNER JOIN users buyer ON o.buyer_id = buyer.user_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                LEFT JOIN deliverybids db ON dj.job_id = db.job_id AND db.transporter_id = ? AND db.status = 'ACCEPTED'
                WHERE dj.job_id = ?
                GROUP BY dj.job_id
            ");
            $stmt->execute([$user_id, $job_id]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error updating status: " . $e->getMessage();
        }
    }
}

// Get the list of products being delivered
$stmt = $pdo->prepare("
    SELECT 
        p.title,
        p.quantity_available,
        p.unit,
        oi.quantity,
        oi.unit_price,
        c.name as category_name,
        farmer.full_name as farmer_name
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    INNER JOIN users farmer ON p.farmer_id = farmer.user_id
    WHERE oi.order_id = ?
");
$stmt->execute([$job['order_id']]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$site_title = "Delivery Tracking - Job #$job_id | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <a href="jobs.php" class="btn btn-secondary mb-4">
            ← Back to Marketplace
        </a>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <strong>✓ Success!</strong> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <strong>✗ Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Delivery Information Card -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">📋 Delivery Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Buyer Name:</strong><br>
                                <?php echo htmlspecialchars($job['buyer_name']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Contact Number:</strong><br>
                                <?php echo htmlspecialchars($job['buyer_phone']); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Total Products:</strong><br>
                                <?php echo $job['total_products']; ?> items
                            </div>
                            <div class="col-md-6">
                                <strong>Total Weight:</strong><br>
                                <?php echo number_format($job['total_weight'], 2); ?> KG
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Order Value:</strong><br>
                                ৳<?php echo number_format($job['total_amount'], 2); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Order Status:</strong><br>
                                <span class="badge bg-info"><?php echo htmlspecialchars($job['order_status']); ?></span>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded">
                            <h5 class="mb-3">📍 Delivery Route</h5>
                            <div class="d-flex align-items-center flex-wrap gap-3">
                                <div class="flex-fill p-3 bg-white rounded border">
                                    <strong>🚚 Pickup Point</strong><br>
                                    <?php echo htmlspecialchars($job['pickup_location']); ?>
                                </div>
                                <div class="text-success" style="font-size: 24px; font-weight: bold;">→</div>
                                <div class="flex-fill p-3 bg-white rounded border">
                                    <strong>🏠 Delivery Point</strong><br>
                                    <?php echo htmlspecialchars($job['dropoff_location']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Card -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">📦 Products to Deliver</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Farmer</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['title']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($product['farmer_name']); ?></td>
                                            <td><?php echo number_format($product['quantity'], 2); ?> <?php echo htmlspecialchars($product['unit']); ?></td>
                                            <td>৳<?php echo number_format($product['unit_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Status Tracker Card -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white text-center">
                        <h4 class="mb-0">Current Status</h4>
                        <div class="mt-2">
                            <?php 
                            $status_display = [
                                'OPEN' => '⏳ Pending',
                                'BIDDING' => '💰 Bidding',
                                'ASSIGNED' => '✓ Assigned',
                                'PICKED_UP' => '📦 Picked Up',
                                'IN_TRANSIT' => '🚚 In Transit',
                                'DELIVERED' => '✅ Delivered',
                                'CANCELLED' => '❌ Cancelled'
                            ];
                            echo '<span class="badge bg-warning text-dark fs-6">' . ($status_display[$job['status']] ?? $job['status']) . '</span>';
                            ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Status Steps -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3 <?php echo in_array($job['status'], ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED']) ? 'text-success' : ''; ?>">
                                <span class="me-2"><?php echo in_array($job['status'], ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED']) ? '✓' : '○'; ?></span>
                                <div>
                                    <strong>Bid Accepted</strong><br>
                                    <small class="text-muted">Job assigned to you</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3 <?php echo in_array($job['status'], ['PICKED_UP', 'IN_TRANSIT', 'DELIVERED']) ? 'text-success' : ''; ?>">
                                <span class="me-2"><?php echo in_array($job['status'], ['PICKED_UP', 'IN_TRANSIT', 'DELIVERED']) ? '✓' : '○'; ?></span>
                                <div>
                                    <strong>Picked Up</strong><br>
                                    <small class="text-muted">Products collected from farmer</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3 <?php echo in_array($job['status'], ['IN_TRANSIT', 'DELIVERED']) ? 'text-success' : ($job['status'] == 'IN_TRANSIT' ? 'text-primary' : ''); ?>">
                                <span class="me-2"><?php echo in_array($job['status'], ['IN_TRANSIT', 'DELIVERED']) ? ($job['status'] == 'IN_TRANSIT' ? '●' : '✓') : '○'; ?></span>
                                <div>
                                    <strong>In Transit</strong><br>
                                    <small class="text-muted">On the way to buyer</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center <?php echo $job['status'] == 'DELIVERED' ? 'text-success' : ''; ?>">
                                <span class="me-2"><?php echo $job['status'] == 'DELIVERED' ? '✓' : '○'; ?></span>
                                <div>
                                    <strong>Delivered</strong><br>
                                    <small class="text-muted">Order complete</small>
                                </div>
                            </div>
                        </div>

                        <?php if ($job['status'] !== 'DELIVERED' && $job['status'] !== 'CANCELLED'): ?>
                            <div class="border-top pt-3">
                                <h5 class="mb-3">Update Delivery Status</h5>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <?php if ($job['status'] == 'ASSIGNED'): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="status" value="PICKED_UP" id="picked_up" required>
                                                <label class="form-check-label" for="picked_up">
                                                    📦 Mark as Picked Up
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($job['status'] == 'PICKED_UP'): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="status" value="IN_TRANSIT" id="in_transit" required>
                                                <label class="form-check-label" for="in_transit">
                                                    🚚 Mark as In Transit
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($job['status'] == 'IN_TRANSIT'): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="status" value="DELIVERED" id="delivered" required>
                                                <label class="form-check-label" for="delivered">
                                                    ✅ Mark as Delivered
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Update Status</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($job['bid_amount']): ?>
                    <div class="card bg-warning">
                        <div class="card-body text-center">
                            <div class="text-muted mb-2"><small>Your Earning</small></div>
                            <div class="h2 mb-0">৳<?php echo number_format($job['bid_amount'], 2); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

