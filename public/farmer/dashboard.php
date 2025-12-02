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
                <h4 class="mb-3">Recent Orders</h4>
                <?php if (empty($recentOrders)): ?>
                    <p>No orders yet. Once buyers purchase your products, they will appear here.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Buyer</th>
                                <th>Total (BDT)</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td><?= (int)$o['order_id'] ?></td>
                                    <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                                    <td><?= number_format((float)$o['total_amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($o['status']) ?></td>
                                    <td><?= htmlspecialchars($o['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
