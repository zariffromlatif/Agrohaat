<?php
require_once '../../config/config.php';
require_once '../../controllers/BuyerController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'BUYER') {
    redirect('buyer/login.php');
}

$controller = new BuyerController($pdo);
$orders = $controller->getBuyerOrders($_SESSION['user_id']);

// Calculate stats
$total_orders = count($orders);
$active_orders = count(array_filter($orders, function($o) { 
    return in_array($o['status'], ['PENDING', 'PAID', 'PROCESSING']); 
}));
$completed_orders = count(array_filter($orders, function($o) { 
    return $o['status'] === 'DELIVERED'; 
}));

$site_title  = "Buyer Dashboard | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Buyer') ?>!</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✅ Success!</strong> <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>❌ Error!</strong> <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <h3><?= $total_orders ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Active Orders</h5>
                        <h3><?= $active_orders ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Completed</h5>
                        <h3><?= $completed_orders ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Recent Orders</h4>
                <a href="<?= $BASE_URL ?>buyer/orders.php" class="btn btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <p>You have no orders yet. <a href="<?= $BASE_URL ?>shop.php">Browse products</a> to get started.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Farmer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                                    <tr>
                                        <td>#<?= $order['order_id'] ?></td>
                                        <td><?= htmlspecialchars($order['farmer_name'] ?? 'N/A') ?></td>
                                        <td>৳<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] === 'DELIVERED' ? 'success' : ($order['status'] === 'PENDING' ? 'warning' : 'info') ?>">
                                                <?= htmlspecialchars($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($order['payment_status'] ?? 'UNPAID') ?></td>
                                        <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= $BASE_URL ?>buyer/orders.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-4">
            <h4>Quick Actions</h4>
            <div class="row">
                <div class="col-md-3">
                    <a href="<?= $BASE_URL ?>shop.php" class="btn btn-outline-primary w-100">Browse Products</a>
                </div>
                <div class="col-md-3">
                    <a href="<?= $BASE_URL ?>buyer/orders.php" class="btn btn-outline-primary w-100">My Orders</a>
                </div>
                <div class="col-md-3">
                    <a href="<?= $BASE_URL ?>buyer/profile.php" class="btn btn-outline-primary w-100">My Profile</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
