<?php
require_once '../../config/config.php';

// Make sure only transporters can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

$user_id = $_SESSION['user_id'];

// First, verify that the transporter has completed their profile setup
$stmt = $pdo->prepare("SELECT * FROM transporter_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$transporter_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// If no profile exists, redirect them to create one first
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

$available_jobs = [];
$my_bids = [];
$bid_map = [];

if ($table_exists) {
    // Fetching all available delivery jobs from the database
    $stmt = $pdo->query("
        SELECT 
            dj.job_id,
            dj.order_id,
            dj.pickup_location,
            dj.dropoff_location,
            dj.status,
            dj.created_at,
            o.total_amount,
            o.shipping_address,
            buyer.full_name as buyer_name,
            buyer.phone_number as buyer_phone,
            buyer.district as buyer_district,
            COUNT(DISTINCT oi.product_id) as total_products,
            SUM(oi.quantity) as total_weight
        FROM deliveryjobs dj
        INNER JOIN orders o ON dj.order_id = o.order_id
        INNER JOIN users buyer ON o.buyer_id = buyer.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE dj.status = 'OPEN' 
        AND o.payment_status = 'PAID'
        AND o.status IN ('PAID', 'CONFIRMED', 'PROCESSING')
        GROUP BY dj.job_id
        ORDER BY dj.created_at DESC
    ");
    
    $available_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get the bids that this transporter has already placed
    $stmt = $pdo->prepare("
        SELECT job_id, bid_amount, status, created_at 
        FROM deliverybids 
        WHERE transporter_id = ?
    ");
    $stmt->execute([$user_id]);
    $my_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Creating a quick lookup map for bids
    foreach ($my_bids as $bid) {
        $bid_map[$bid['job_id']] = $bid;
    }
}

$site_title = "Delivery Job Marketplace | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-2">🚛 Delivery Job Marketplace</h2>
                <p class="text-muted mb-0">Browse delivery opportunities and submit your competitive bids</p>
            </div>
            <div class="text-end">
                <small class="text-muted d-block">Vehicle: <?php echo htmlspecialchars($transporter_profile['vehicle_type']); ?></small>
                <small class="text-muted">License: <?php echo htmlspecialchars($transporter_profile['license_plate']); ?></small>
            </div>
        </div>

        <?php if (!$table_exists): ?>
            <div class="alert alert-warning">
                <strong>Note:</strong> Delivery marketplace requires database tables. Please import <code>database/transporter_delivery_tables.sql</code>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo count($available_jobs); ?></h3>
                        <small>Open Jobs</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo count($my_bids); ?></h3>
                        <small>My Active Bids</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo number_format($transporter_profile['max_capacity_kg']); ?> KG</h3>
                        <small>Vehicle Capacity</small>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($available_jobs)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="display-1 mb-3">📦</div>
                    <h3 class="mb-3">No Jobs Available Right Now</h3>
                    <p class="text-muted">New delivery opportunities will appear here when farmers and buyers place orders</p>
                    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($available_jobs as $job): 
                    $has_bid = isset($bid_map[$job['job_id']]);
                    $bid_accepted = $has_bid && $bid_map[$job['job_id']]['status'] === 'ACCEPTED';
                    $bid_pending = $has_bid && $bid_map[$job['job_id']]['status'] === 'PENDING';
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-success text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Job #<?php echo $job['job_id']; ?></h5>
                                    <?php if ($bid_accepted): ?>
                                        <span class="badge bg-warning text-dark">✓ Accepted</span>
                                    <?php elseif ($bid_pending): ?>
                                        <span class="badge bg-info">⏳ Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Available</span>
                                    <?php endif; ?>
                                </div>
                                <small class="opacity-75">Order #<?php echo $job['order_id']; ?></small>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Buyer</small>
                                    <strong><?php echo htmlspecialchars($job['buyer_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($job['buyer_phone']); ?></small>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Products</small>
                                        <strong><?php echo $job['total_products']; ?> items</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Weight</small>
                                        <strong><?php echo number_format($job['total_weight'], 1); ?> KG</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Value</small>
                                        <strong class="text-success">৳<?php echo number_format($job['total_amount'], 0); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">District</small>
                                        <strong><?php echo htmlspecialchars($job['buyer_district']); ?></strong>
                                    </div>
                                </div>

                                <div class="bg-light p-3 rounded mb-3">
                                    <small class="text-muted d-block mb-2"><strong>📍 Route</strong></small>
                                    <div class="small">
                                        <div class="mb-2">
                                            <strong>🚚 Pickup:</strong><br>
                                            <span class="text-truncate d-inline-block" style="max-width: 100%;" title="<?php echo htmlspecialchars($job['pickup_location']); ?>">
                                                <?php echo htmlspecialchars(substr($job['pickup_location'], 0, 40)); ?>
                                                <?php echo strlen($job['pickup_location']) > 40 ? '...' : ''; ?>
                                            </span>
                                        </div>
                                        <div class="text-center my-1">↓</div>
                                        <div>
                                            <strong>🏠 Delivery:</strong><br>
                                            <span class="text-truncate d-inline-block" style="max-width: 100%;" title="<?php echo htmlspecialchars($job['dropoff_location']); ?>">
                                                <?php echo htmlspecialchars(substr($job['dropoff_location'], 0, 40)); ?>
                                                <?php echo strlen($job['dropoff_location']) > 40 ? '...' : ''; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($has_bid): ?>
                                    <div class="alert alert-<?php echo $bid_accepted ? 'success' : 'warning'; ?> py-2 mb-3">
                                        <small>
                                            <?php if ($bid_accepted): ?>
                                                <strong>✓ Congratulations!</strong> Your bid of ৳<?php echo number_format($bid_map[$job['job_id']]['bid_amount'], 2); ?> has been accepted
                                            <?php else: ?>
                                                <strong>⏳ Your bid:</strong> ৳<?php echo number_format($bid_map[$job['job_id']]['bid_amount'], 2); ?> - Waiting for response
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light">
                                <?php if ($bid_accepted): ?>
                                    <a href="track_delivery.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-success w-100">
                                        Start Delivery Process →
                                    </a>
                                <?php elseif ($bid_pending): ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        Bid Submitted - Waiting
                                    </button>
                                <?php else: ?>
                                    <a href="placebid.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-success w-100">
                                        Submit Your Bid
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 text-center">
                <p class="text-muted">Showing <?php echo count($available_jobs); ?> available job(s)</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

