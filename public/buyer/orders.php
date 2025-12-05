<?php
require_once '../../config/config.php';
require_once '../../controllers/BuyerController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'BUYER') {
    redirect('buyer/login.php');
}

$controller = new BuyerController($pdo);
$orders = $controller->getBuyerOrders($_SESSION['user_id']);

// If order ID is specified, show details
$order_detail = null;
if (isset($_GET['id'])) {
    $order_detail = $controller->getOrderDetails($_GET['id'], $_SESSION['user_id']);
}

$site_title  = "My Orders | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">My Orders</h2>

        <?php if ($order_detail): ?>
            <!-- Order Detail View -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Order #<?= $order_detail['order_id'] ?></h4>
                </div>
                <div class="card-body">
                    <p><strong>Farmer:</strong> <?= htmlspecialchars($order_detail['farmer_name']) ?></p>
                    <p><strong>Amount:</strong> ৳<?= number_format($order_detail['total_amount'], 2) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($order_detail['status']) ?></p>
                    <p><strong>Payment Status:</strong> <?= htmlspecialchars($order_detail['payment_status'] ?? 'UNPAID') ?></p>
                    <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order_detail['shipping_address']) ?></p>
                    <p><strong>Created:</strong> <?= date('F j, Y g:i A', strtotime($order_detail['created_at'])) ?></p>
                    
                    <?php if ($order_detail['status'] === 'PENDING' && ($order_detail['payment_status'] ?? 'UNPAID') === 'UNPAID'): ?>
                        <a href="<?= $BASE_URL ?>checkout.php?order_id=<?= $order_detail['order_id'] ?>" class="btn btn-primary">Complete Payment</a>
                    <?php endif; ?>
                    
                    <a href="<?= $BASE_URL ?>buyer/orders.php" class="btn btn-secondary">Back to Orders</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Orders List -->
        <div class="card">
            <div class="card-header">
                <h4>Order History</h4>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <p>You have no orders yet. <a href="<?= $BASE_URL ?>shop.php">Browse products</a> to get started.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
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
                                <?php foreach ($orders as $order): ?>
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
                                            <?php if ($order['status'] === 'PENDING' && ($order['payment_status'] ?? 'UNPAID') === 'UNPAID'): ?>
                                                <a href="<?= $BASE_URL ?>checkout.php?order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-success">Pay</a>
                                            <?php endif; ?>
                                        </td>
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

