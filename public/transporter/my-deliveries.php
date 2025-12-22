<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

$user_id = $_SESSION['user_id'];

// Check if deliveryjobs table exists
$table_exists = false;
try {
    $pdo->query("SELECT 1 FROM deliveryjobs LIMIT 1");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

$deliveries = [];

if ($table_exists) {
    // Get all deliveries assigned to this transporter (where they have an accepted bid)
    $stmt = $pdo->prepare("
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
        INNER JOIN deliverybids db ON dj.job_id = db.job_id AND db.transporter_id = ? AND db.status = 'ACCEPTED'
        WHERE dj.status IN ('ASSIGNED', 'IN_PROGRESS', 'COMPLETED')
        GROUP BY dj.job_id
        ORDER BY 
            CASE dj.status
                WHEN 'IN_PROGRESS' THEN 1
                WHEN 'ASSIGNED' THEN 2
                WHEN 'COMPLETED' THEN 3
                ELSE 4
            END,
            dj.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$site_title = "My Deliveries | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">My Deliveries</h2>
            <a href="jobs.php" class="btn btn-success">View Available Jobs</a>
        </div>

        <?php if (!$table_exists): ?>
            <div class="alert alert-warning">
                <strong>Note:</strong> Delivery tracking requires database tables. Please import <code>database/transporter_delivery_tables.sql</code>
            </div>
        <?php elseif (empty($deliveries)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <h4 class="text-muted mb-3">No Deliveries Assigned</h4>
                    <p class="text-muted mb-4">You don't have any assigned deliveries yet. Browse available jobs and place bids to get started.</p>
                    <a href="jobs.php" class="btn btn-success">Browse Jobs</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($deliveries as $delivery): 
                    $status_badge = [
                        'ASSIGNED' => 'warning',
                        'IN_PROGRESS' => 'primary',
                        'COMPLETED' => 'success',
                        'CANCELLED' => 'danger'
                    ];
                    $badge_class = $status_badge[$delivery['status']] ?? 'secondary';
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-<?= $badge_class ?> text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Job #<?= $delivery['job_id'] ?></h5>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($delivery['status']) ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Order #</small>
                                    <strong><?= $delivery['order_id'] ?></strong>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Buyer</small>
                                    <strong><?= htmlspecialchars($delivery['buyer_name']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($delivery['buyer_district']) ?></small>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Route</small>
                                    <div class="small">
                                        <div class="text-truncate" title="<?= htmlspecialchars($delivery['pickup_location']) ?>">
                                            🚚 <?= htmlspecialchars(substr($delivery['pickup_location'], 0, 30)) ?>...
                                        </div>
                                        <div class="text-center my-1">↓</div>
                                        <div class="text-truncate" title="<?= htmlspecialchars($delivery['dropoff_location']) ?>">
                                            🏠 <?= htmlspecialchars(substr($delivery['dropoff_location'], 0, 30)) ?>...
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Products</small>
                                        <strong><?= $delivery['total_products'] ?> items</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Weight</small>
                                        <strong><?= number_format($delivery['total_weight'], 1) ?> KG</strong>
                                    </div>
                                </div>
                                
                                <?php if ($delivery['bid_amount']): ?>
                                    <div class="alert alert-info py-2 mb-3">
                                        <small class="d-block text-muted">Your Earning</small>
                                        <strong class="text-success">৳<?= number_format($delivery['bid_amount'], 2) ?></strong>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-2">
                                    <small class="text-muted">Assigned: <?= date('M j, Y', strtotime($delivery['created_at'])) ?></small>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-grid gap-2">
                                    <?php if ($delivery['status'] !== 'COMPLETED'): ?>
                                        <a href="track_delivery.php?job_id=<?= $delivery['job_id'] ?>" class="btn btn-success">
                                            Track & Update Status
                                        </a>
                                    <?php else: ?>
                                        <a href="track_delivery.php?job_id=<?= $delivery['job_id'] ?>" class="btn btn-outline-secondary">
                                            View Details
                                        </a>
                                    <?php endif; ?>
                                    <a href="chat.php?order_id=<?= $delivery['order_id'] ?>" class="btn btn-info">
                                        <i class="fas fa-comments"></i> Chat
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4">
                <p class="text-muted text-center">Showing <?= count($deliveries) ?> delivery job(s)</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
