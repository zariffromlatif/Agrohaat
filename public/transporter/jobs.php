<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

$user_id = $_SESSION['user_id'];

// Check if transporter has a profile
$stmt = $pdo->prepare("SELECT * FROM transporter_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$transporter_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transporter_profile) {
    $_SESSION['error'] = "Please complete your transporter profile first.";
    redirect('transporter/profile.php');
}

// Check if deliveryjobs table exists, if not, we'll work with orders directly
$table_exists = false;
try {
    $pdo->query("SELECT 1 FROM deliveryjobs LIMIT 1");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

// Filter parameters
$filter_pickup = $_GET['pickup_district'] ?? '';
$filter_dropoff = $_GET['dropoff_district'] ?? '';
$filter_status = $_GET['status'] ?? 'OPEN';
$search = $_GET['search'] ?? '';

// Build query based on whether deliveryjobs table exists
if ($table_exists) {
    // Use deliveryjobs table
    $sql = "
        SELECT 
            dj.job_id,
            dj.order_id,
            dj.pickup_location,
            dj.dropoff_location,
            dj.status,
            dj.created_at,
            o.total_amount,
            o.buyer_id,
            o.farmer_id,
            buyer.full_name as buyer_name,
            buyer.district as buyer_district,
            buyer.phone_number as buyer_phone,
            farmer.full_name as farmer_name,
            farmer.district as farmer_district,
            farmer.upazila as farmer_upazila,
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
        WHERE dj.status = ?
    ";
    
    $params = [$filter_status];
    
    if ($filter_pickup) {
        $sql .= " AND farmer.district LIKE ?";
        $params[] = "%$filter_pickup%";
    }
    
    if ($filter_dropoff) {
        $sql .= " AND buyer.district LIKE ?";
        $params[] = "%$filter_dropoff%";
    }
    
    if ($search) {
        $sql .= " AND (dj.pickup_location LIKE ? OR dj.dropoff_location LIKE ? OR buyer.full_name LIKE ? OR farmer.full_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $sql .= " GROUP BY dj.job_id ORDER BY dj.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} else {
    // Create jobs on-the-fly from paid orders that need delivery
    // This is a fallback if deliveryjobs table doesn't exist yet
    $sql = "
        SELECT 
            o.order_id as job_id,
            o.order_id,
            CONCAT(farmer.district, ', ', COALESCE(farmer.upazila, ''), ', ', COALESCE(farmer.address_details, '')) as pickup_location,
            o.shipping_address as dropoff_location,
            'OPEN' as status,
            o.created_at,
            o.total_amount,
            o.buyer_id,
            o.farmer_id,
            buyer.full_name as buyer_name,
            buyer.district as buyer_district,
            buyer.phone_number as buyer_phone,
            farmer.full_name as farmer_name,
            farmer.district as farmer_district,
            farmer.upazila as farmer_upazila,
            COUNT(DISTINCT oi.product_id) as total_products,
            SUM(oi.quantity) as total_weight,
            0 as bid_count,
            NULL as lowest_bid
        FROM orders o
        INNER JOIN users buyer ON o.buyer_id = buyer.user_id
        INNER JOIN users farmer ON o.farmer_id = farmer.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.payment_status = 'PAID' 
        AND o.status IN ('PAID', 'CONFIRMED', 'PROCESSING')
    ";
    
    $params = [];
    
    if ($filter_pickup) {
        $sql .= " AND farmer.district LIKE ?";
        $params[] = "%$filter_pickup%";
    }
    
    if ($filter_dropoff) {
        $sql .= " AND buyer.district LIKE ?";
        $params[] = "%$filter_dropoff%";
    }
    
    if ($search) {
        $sql .= " AND (o.shipping_address LIKE ? OR buyer.full_name LIKE ? OR farmer.full_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $sql .= " GROUP BY o.order_id ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get unique districts for filter dropdowns
$stmt = $pdo->query("SELECT DISTINCT district FROM users WHERE district IS NOT NULL AND district != '' ORDER BY district");
$districts = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Check which jobs the transporter has already bid on
$bid_job_ids = [];
if (!empty($jobs) && $table_exists) {
    $job_ids = array_column($jobs, 'job_id');
    $placeholders = implode(',', array_fill(0, count($job_ids), '?'));
    $stmt = $pdo->prepare("SELECT job_id FROM deliverybids WHERE transporter_id = ? AND job_id IN ($placeholders)");
    $stmt->execute(array_merge([$user_id], $job_ids));
    $bid_job_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$site_title = "Available Jobs | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Available Delivery Jobs</h2>
            <a href="<?= $BASE_URL ?>transporter/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search locations, names..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pickup District</label>
                        <select name="pickup_district" class="form-select">
                            <option value="">All Districts</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= htmlspecialchars($district) ?>" <?= $filter_pickup === $district ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($district) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Delivery District</label>
                        <select name="dropoff_district" class="form-select">
                            <option value="">All Districts</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= htmlspecialchars($district) ?>" <?= $filter_dropoff === $district ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($district) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($table_exists): ?>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="OPEN" <?= $filter_status === 'OPEN' ? 'selected' : '' ?>>Open</option>
                            <option value="BIDDING" <?= $filter_status === 'BIDDING' ? 'selected' : '' ?>>Bidding</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-12">
                        <a href="jobs.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!$table_exists): ?>
            <div class="alert alert-info">
                <strong>Note:</strong> Delivery jobs table not found. Showing paid orders directly. 
                Please import <code>database/transporter_delivery_tables.sql</code> for full functionality.
            </div>
        <?php endif; ?>

        <!-- Jobs List -->
        <?php if (empty($jobs)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <h4 class="text-muted mb-3">No Jobs Available</h4>
                    <p class="text-muted">There are currently no delivery jobs matching your filters.</p>
                    <a href="jobs.php" class="btn btn-primary">Clear Filters</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($jobs as $job): 
                    $has_bid = in_array($job['job_id'], $bid_job_ids);
                    $can_bid = !$has_bid && ($job['status'] === 'OPEN' || !$table_exists);
                    $weight_ok = $job['total_weight'] <= $transporter_profile['max_capacity_kg'];
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm <?= !$weight_ok ? 'border-warning' : '' ?>">
                            <div class="card-header bg-success text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Job #<?= $job['job_id'] ?></h5>
                                    <?php if ($job['bid_count'] > 0): ?>
                                        <span class="badge bg-light text-dark"><?= $job['bid_count'] ?> bids</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Route -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-info me-2">🚚</span>
                                        <small class="text-muted">Pickup</small>
                                    </div>
                                    <p class="mb-0 small"><?= htmlspecialchars($job['pickup_location']) ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($job['farmer_name']) ?> • <?= htmlspecialchars($job['farmer_district']) ?></small>
                                </div>
                                
                                <div class="text-center my-2">
                                    <span class="text-success fw-bold">↓</span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-primary me-2">🏠</span>
                                        <small class="text-muted">Delivery</small>
                                    </div>
                                    <p class="mb-0 small"><?= htmlspecialchars($job['dropoff_location']) ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($job['buyer_name']) ?> • <?= htmlspecialchars($job['buyer_district']) ?></small>
                                </div>

                                <hr>

                                <!-- Job Details -->
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Order Value</small>
                                        <strong class="text-success">৳<?= number_format($job['total_amount'], 2) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Weight</small>
                                        <strong><?= number_format($job['total_weight'], 2) ?> KG</strong>
                                        <?php if (!$weight_ok): ?>
                                            <span class="badge bg-warning text-dark ms-1">⚠ Over capacity</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Products</small>
                                        <strong><?= $job['total_products'] ?> items</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Posted</small>
                                        <strong><?= date('M j, Y', strtotime($job['created_at'])) ?></strong>
                                    </div>
                                </div>

                                <?php if ($job['lowest_bid']): ?>
                                    <div class="alert alert-info py-2 mb-3">
                                        <small><strong>Lowest Bid:</strong> ৳<?= number_format($job['lowest_bid'], 2) ?></small>
                                    </div>
                                <?php endif; ?>

                                <?php if ($has_bid): ?>
                                    <div class="alert alert-warning py-2 mb-3">
                                        <small>✓ You've already placed a bid</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light">
                                <?php if ($can_bid): ?>
                                    <a href="placebid.php?job_id=<?= $job['job_id'] ?>" class="btn btn-success w-100">
                                        Place Bid →
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <?= $has_bid ? 'Already Bid' : 'Not Available' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination could be added here if needed -->
            <div class="mt-4 text-center">
                <p class="text-muted">Showing <?= count($jobs) ?> job(s)</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
