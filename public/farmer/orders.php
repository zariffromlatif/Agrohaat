<?php
require_once '../../config/config.php';
require_once '../../controllers/OrderController.php';
require_once '../../models/Order.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

$controller = new OrderController($pdo);
$orders     = $controller->getFarmerOrders($_SESSION['user_id']);

// Get order detail if ID is provided
$order_detail = null;
$order_items = [];
if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    $orderModel = new Order($pdo);
    
    // Verify order belongs to this farmer
    $stmt = $pdo->prepare("SELECT o.*, u.full_name AS buyer_name 
                           FROM orders o 
                           JOIN users u ON u.user_id = o.buyer_id 
                           WHERE o.order_id = ? AND o.farmer_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order_detail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order_detail) {
        $order_items = $orderModel->getOrderItems($order_id);
    }
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Order #<?= $order_detail['order_id'] ?></h4>
                    <a href="<?= $BASE_URL ?>farmer/orders.php" class="btn btn-sm btn-secondary">Back to Orders</a>
                </div>
                <div class="card-body">
                    <!-- Order Items -->
                    <?php if (!empty($order_items)): ?>
                        <div class="mb-4">
                            <h5>Order Items</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($item['image_url'])): ?>
                                                            <img src="<?= $BASE_URL . htmlspecialchars($item['image_url']) ?>" 
                                                                 width="50" height="50" 
                                                                 class="rounded me-2" 
                                                                 style="object-fit: cover;"
                                                                 alt="<?= htmlspecialchars($item['title']) ?>">
                                                        <?php endif; ?>
                                                        <div>
                                                            <strong><?= htmlspecialchars($item['title']) ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= number_format((float)$item['quantity'], 2) ?> 
                                                    <?= htmlspecialchars($item['unit'] ?? 'kg') ?>
                                                </td>
                                                <td>৳<?= number_format((float)$item['unit_price'], 2) ?></td>
                                                <td>
                                                    <strong>৳<?= number_format((float)($item['subtotal'] ?? $item['total_price'] ?? ($item['unit_price'] * $item['quantity'])), 2) ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="3" class="text-end">Total:</th>
                                            <th>৳<?= number_format($order_detail['total_amount'], 2) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <hr>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Buyer:</strong> <?= htmlspecialchars($order_detail['buyer_name']) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= $order_detail['status'] === 'DELIVERED' ? 'success' : ($order_detail['status'] === 'PENDING' ? 'warning' : 'info') ?>">
                                    <?= htmlspecialchars($order_detail['status']) ?>
                                </span>
                            </p>
                            <p><strong>Payment Status:</strong> 
                                <span class="badge bg-<?= ($order_detail['payment_status'] ?? 'UNPAID') === 'PAID' ? 'success' : 'warning' ?>">
                                    <?= htmlspecialchars($order_detail['payment_status'] ?? 'UNPAID') ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Amount:</strong> <strong class="text-success">৳<?= number_format($order_detail['total_amount'], 2) ?></strong></p>
                            <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order_detail['shipping_address']) ?></p>
                            <p><strong>Created:</strong> <?= date('F j, Y g:i A', strtotime($order_detail['created_at'])) ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?= $BASE_URL ?>farmer/chat.php?order_id=<?= (int)$order_detail['order_id'] ?>" class="btn btn-primary">
                            <i class="fas fa-comments"></i> Chat with Buyer
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Orders Yet</h4>
                    <p class="text-muted">Once buyers purchase your products, orders will appear here.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Buyer</th>
                        <th>Total Amount (BDT)</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong>#<?= (int) $o['order_id'] ?></strong></td>
                            <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                            <td>৳<?= number_format((float) $o['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $o['status'] === 'DELIVERED' ? 'success' : ($o['status'] === 'PENDING' ? 'warning' : 'info') ?>">
                                    <?= htmlspecialchars($o['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= ($o['payment_status'] ?? 'UNPAID') === 'PAID' ? 'success' : 'warning' ?>">
                                    <?= htmlspecialchars($o['payment_status'] ?? 'UNPAID') ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="<?= $BASE_URL ?>farmer/orders.php?id=<?= (int)$o['order_id'] ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="<?= $BASE_URL ?>farmer/chat.php?order_id=<?= (int)$o['order_id'] ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Chat with Buyer">
                                        <i class="fas fa-comments"></i> Chat
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
