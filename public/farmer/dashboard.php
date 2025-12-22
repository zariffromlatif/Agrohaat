<?php
require_once '../../config/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

// Simple stats for dashboard
$farmerId = $_SESSION['user_id'];

// Total products
$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM products WHERE farmer_id = :fid AND is_deleted = 0");
$stmt->execute([':fid' => $farmerId]);
$productStats = $stmt->fetch();

// Total orders
$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM orders WHERE farmer_id = :fid");
$stmt->execute([':fid' => $farmerId]);
$orderStats = $stmt->fetch();

// Recent orders (last 5)
$stmt = $pdo->prepare("
    SELECT o.order_id, o.total_amount, o.status, o.created_at, u.full_name AS buyer_name
    FROM orders o
    JOIN users u ON u.user_id = o.buyer_id
    WHERE o.farmer_id = :fid
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([':fid' => $farmerId]);
$recentOrders = $stmt->fetchAll();

$site_title = "Farmer Dashboard | AgroHaat";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Farmer') ?></h2>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card p-4 shadow-sm">
                    <h5>Total Products</h5>
                    <p class="display-6 mb-0"><?= (int)($productStats['c'] ?? 0) ?></p>
                    <a href="<?= $BASE_URL ?>farmer/products.php" class="mt-2 d-inline-block">Manage products →</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 shadow-sm">
                    <h5>Total Orders</h5>
                    <p class="display-6 mb-0"><?= (int)($orderStats['c'] ?? 0) ?></p>
                    <a href="<?= $BASE_URL ?>farmer/orders.php" class="mt-2 d-inline-block">View orders →</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 shadow-sm">
                    <h5>Quick Actions</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?= $BASE_URL ?>farmer/product_add.php">+ Add new product</a></li>
                        <li><a href="<?= $BASE_URL ?>farmer/products.php">My products</a></li>
                        <li><a href="<?= $BASE_URL ?>farmer/orders.php">My orders</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Recent Orders</h4>
                    <a href="<?= $BASE_URL ?>farmer/orders.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
                </div>
                <?php if (empty($recentOrders)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No orders yet. Once buyers purchase your products, they will appear here.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Buyer</th>
                                <th>Total (BDT)</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td><strong>#<?= (int)$o['order_id'] ?></strong></td>
                                    <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                                    <td>৳<?= number_format((float)$o['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $o['status'] === 'DELIVERED' ? 'success' : ($o['status'] === 'PENDING' ? 'warning' : 'info') ?>">
                                            <?= htmlspecialchars($o['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                                    <td>
                                        <a href="<?= $BASE_URL ?>farmer/chat.php?order_id=<?= (int)$o['order_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Chat with buyer">
                                            <i class="fas fa-comments"></i> Chat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- API Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-code"></i> Product API Available</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">All product operations are available via RESTful API endpoints:</p>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> <code>GET /api/farmer/products.php</code> - List all products</li>
                            <li><i class="fas fa-check text-success"></i> <code>POST /api/farmer/products.php</code> - Create product</li>
                            <li><i class="fas fa-check text-success"></i> <code>PUT /api/farmer/products.php</code> - Update product</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> <code>DELETE /api/farmer/products.php</code> - Delete product</li>
                            <li><i class="fas fa-check text-success"></i> Supports JSON and multipart/form-data</li>
                            <li><i class="fas fa-check text-success"></i> Auto QR code generation</li>
                        </ul>
                    </div>
                </div>
                <a href="<?= $BASE_URL ?>api/farmer/README.md" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="fas fa-book"></i> View API Documentation
                </a>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
