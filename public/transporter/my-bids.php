<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

$user_id = $_SESSION['user_id'];

// Check if deliverybids table exists
$table_exists = false;
try {
    $pdo->query("SELECT 1 FROM deliverybids LIMIT 1");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

$bids = [];
$message = '';
$error = '';

// Handle bid withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_bid'])) {
    $bid_id = intval($_POST['bid_id']);
    
    if ($table_exists) {
        try {
            // Verify bid belongs to this transporter
            $stmt = $pdo->prepare("SELECT bid_id, status FROM deliverybids WHERE bid_id = ? AND transporter_id = ?");
            $stmt->execute([$bid_id, $user_id]);
            $bid = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$bid) {
                $error = 'Bid not found';
            } elseif ($bid['status'] !== 'PENDING') {
                $error = 'Only pending bids can be withdrawn';
            } else {
                $stmt = $pdo->prepare("UPDATE deliverybids SET status = 'WITHDRAWN' WHERE bid_id = ? AND transporter_id = ?");
                if ($stmt->execute([$bid_id, $user_id])) {
                    $message = 'Bid withdrawn successfully';
                } else {
                    $error = 'Failed to withdraw bid';
                }
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get all bids for this transporter
if ($table_exists) {
    $status_filter = isset($_GET['status']) ? $_GET['status'] : null;
    
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
            o.status as order_status,
            buyer.full_name as buyer_name,
            buyer.phone_number as buyer_phone,
            buyer.district as buyer_district,
            COUNT(DISTINCT oi.product_id) as total_products,
            SUM(oi.quantity) as total_weight,
            (SELECT COUNT(*) FROM deliverybids WHERE job_id = dj.job_id AND status = 'PENDING') as total_bids,
            (SELECT MIN(bid_amount) FROM deliverybids WHERE job_id = dj.job_id AND status = 'PENDING') as lowest_bid
        FROM deliverybids db
        INNER JOIN deliveryjobs dj ON db.job_id = dj.job_id
        INNER JOIN orders o ON dj.order_id = o.order_id
        INNER JOIN users buyer ON o.buyer_id = buyer.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE db.transporter_id = ?
    ";
    
    $params = [$user_id];
    
    if ($status_filter) {
        $sql .= " AND db.status = ?";
        $params[] = $status_filter;
    }
    
    $sql .= " GROUP BY db.bid_id ORDER BY db.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$site_title = "My Bids | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">My Bids</h2>
            <a href="<?= $BASE_URL ?>transporter/jobs.php" class="btn btn-success">Browse New Jobs</a>
        </div>

        <?php if (!$table_exists): ?>
            <div class="alert alert-warning">
                <strong>Note:</strong> Bidding system requires database tables. Please import <code>database/transporter_delivery_tables.sql</code>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="PENDING" <?= (isset($_GET['status']) && $_GET['status'] === 'PENDING') ? 'selected' : '' ?>>Pending</option>
                            <option value="ACCEPTED" <?= (isset($_GET['status']) && $_GET['status'] === 'ACCEPTED') ? 'selected' : '' ?>>Accepted</option>
                            <option value="REJECTED" <?= (isset($_GET['status']) && $_GET['status'] === 'REJECTED') ? 'selected' : '' ?>>Rejected</option>
                            <option value="WITHDRAWN" <?= (isset($_GET['status']) && $_GET['status'] === 'WITHDRAWN') ? 'selected' : '' ?>>Withdrawn</option>
                        </select>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <a href="my-bids.php" class="btn btn-outline-secondary">Clear Filter</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($bids)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="display-1 mb-3">💰</div>
                    <h4 class="text-muted mb-3">No Bids Found</h4>
                    <p class="text-muted mb-4">
                        <?php if ($status_filter): ?>
                            No bids found with status: <?= htmlspecialchars($status_filter) ?>
                        <?php else: ?>
                            You haven't placed any bids yet. Browse available jobs and submit your competitive bids.
                        <?php endif; ?>
                    </p>
                    <a href="<?= $BASE_URL ?>transporter/jobs.php" class="btn btn-success">Browse Jobs</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($bids as $bid): 
                    $status_badge = [
                        'PENDING' => ['class' => 'warning', 'icon' => '⏳'],
                        'ACCEPTED' => ['class' => 'success', 'icon' => '✓'],
                        'REJECTED' => ['class' => 'danger', 'icon' => '✗'],
                        'WITHDRAWN' => ['class' => 'secondary', 'icon' => '↩']
                    ];
                    $badge = $status_badge[$bid['status']] ?? ['class' => 'secondary', 'icon' => '○'];
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-<?= $badge['class'] ?> text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?= $badge['icon'] ?> Bid #<?= $bid['bid_id'] ?></h5>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($bid['status']) ?></span>
                                </div>
                                <small class="opacity-75">Job #<?= $bid['job_id'] ?> • Order #<?= $bid['order_id'] ?></small>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Bid Amount</small>
                                        <strong class="text-success fs-5">৳<?= number_format($bid['bid_amount'], 2) ?></strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block">Buyer</small>
                                    <strong><?= htmlspecialchars($bid['buyer_name']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($bid['buyer_district']) ?></small>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Products</small>
                                        <strong><?= $bid['total_products'] ?> items</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Weight</small>
                                        <strong><?= number_format($bid['total_weight'], 1) ?> KG</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Order Value</small>
                                        <strong class="text-success">৳<?= number_format($bid['total_amount'], 0) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Total Bids</small>
                                        <strong><?= $bid['total_bids'] ?> bids</strong>
                                    </div>
                                </div>

                                <?php if ($bid['lowest_bid'] && $bid['lowest_bid'] < $bid['bid_amount']): ?>
                                    <div class="alert alert-warning py-2 mb-3">
                                        <small><strong>Lowest Bid:</strong> ৳<?= number_format($bid['lowest_bid'], 2) ?></small>
                                    </div>
                                <?php endif; ?>

                                <?php if ($bid['message']): ?>
                                    <div class="bg-light p-2 rounded mb-3">
                                        <small class="text-muted d-block">Your Message:</small>
                                        <small><?= htmlspecialchars($bid['message']) ?></small>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-2">
                                    <small class="text-muted">Submitted: <?= date('M j, Y g:i A', strtotime($bid['created_at'])) ?></small>
                                </div>

                                <?php if ($bid['status'] === 'ACCEPTED'): ?>
                                    <div class="alert alert-success py-2 mb-3">
                                        <small><strong>✓ Congratulations!</strong> Your bid has been accepted. <a href="track_delivery.php?job_id=<?= $bid['job_id'] ?>">Start delivery →</a></small>
                                    </div>
                                <?php elseif ($bid['status'] === 'PENDING'): ?>
                                    <div class="alert alert-info py-2 mb-3">
                                        <small><strong>⏳ Waiting:</strong> Your bid is under review by the buyer.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light">
                                <?php if ($bid['status'] === 'PENDING'): ?>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to withdraw this bid?');">
                                        <input type="hidden" name="bid_id" value="<?= $bid['bid_id'] ?>">
                                        <button type="submit" name="withdraw_bid" class="btn btn-warning btn-sm w-100">Withdraw Bid</button>
                                    </form>
                                <?php elseif ($bid['status'] === 'ACCEPTED'): ?>
                                    <a href="track_delivery.php?job_id=<?= $bid['job_id'] ?>" class="btn btn-success btn-sm w-100">Start Delivery</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm w-100" disabled>
                                        <?= $bid['status'] === 'REJECTED' ? 'Bid Rejected' : 'Bid Withdrawn' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4">
                <p class="text-muted text-center">Showing <?= count($bids) ?> bid(s)</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

