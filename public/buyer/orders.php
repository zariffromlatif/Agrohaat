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
                    <!-- Order Timeline -->
                    <div class="mb-4">
                        <h5>Order Timeline</h5>
                        <div class="timeline">
                            <?php
                            $statuses = [
                                'PENDING' => ['icon' => '⏳', 'label' => 'Order Placed', 'color' => 'warning'],
                                'CONFIRMED' => ['icon' => '✓', 'label' => 'Order Confirmed', 'color' => 'info'],
                                'PROCESSING' => ['icon' => '⚙️', 'label' => 'Processing', 'color' => 'primary'],
                                'SHIPPED' => ['icon' => '🚚', 'label' => 'Shipped', 'color' => 'info'],
                                'DELIVERED' => ['icon' => '✅', 'label' => 'Delivered', 'color' => 'success'],
                                'CANCELLED' => ['icon' => '❌', 'label' => 'Cancelled', 'color' => 'danger']
                            ];
                            
                            $current_status = $order_detail['status'];
                            $status_order = ['PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED'];
                            $current_index = array_search($current_status, $status_order);
                            
                            foreach ($status_order as $index => $status):
                                $is_completed = $index <= $current_index;
                                $is_current = $index === $current_index;
                                $status_info = $statuses[$status] ?? ['icon' => '○', 'label' => $status, 'color' => 'secondary'];
                            ?>
                                <div class="timeline-item d-flex align-items-center mb-3 <?= $is_completed ? 'text-' . $status_info['color'] : 'text-muted' ?>">
                                    <div class="timeline-marker me-3">
                                        <span class="badge bg-<?= $is_completed ? $status_info['color'] : 'secondary' ?> rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 1.2em;">
                                            <?= $status_info['icon'] ?>
                                        </span>
                                    </div>
                                    <div class="timeline-content flex-grow-1">
                                        <h6 class="mb-0"><?= $status_info['label'] ?></h6>
                                        <?php if ($is_current): ?>
                                            <small class="text-<?= $status_info['color'] ?>"><strong>Current Status</strong></small>
                                        <?php elseif ($is_completed): ?>
                                            <small class="text-muted">Completed</small>
                                        <?php else: ?>
                                            <small class="text-muted">Pending</small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($index < count($status_order) - 1): ?>
                                        <div class="timeline-line ms-3" style="width: 2px; height: 40px; background: <?= $is_completed ? 'var(--bs-' . $status_info['color'] . ')' : '#dee2e6' ?>;"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Order Items -->
                    <?php
                    $order_items = $controller->getOrderItems($order_detail['order_id']);
                    if (!empty($order_items)):
                    ?>
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
                            <p><strong>Farmer:</strong> <?= htmlspecialchars($order_detail['farmer_name']) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= $statuses[$current_status]['color'] ?? 'secondary' ?>">
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
                    
                    <?php
                    // Get estimated delivery date (7 days from order creation if processing)
                    if (in_array($current_status, ['PROCESSING', 'SHIPPED'])) {
                        $estimated_delivery = date('F j, Y', strtotime($order_detail['created_at'] . ' +7 days'));
                        echo '<p><strong>Estimated Delivery:</strong> ' . $estimated_delivery . '</p>';
                    }
                    ?>
                    
                    <?php
                    // Get payment history for this order
                    $payment_history = $controller->getOrderPayments($order_detail['order_id']);
                    if (!empty($payment_history)):
                    ?>
                        <hr>
                        <h6>Payment History</h6>
                        <?php foreach ($payment_history as $payment): ?>
                            <div class="payment-info border p-2 mb-2 rounded">
                                <small>
                                    <strong><?= $payment['method_name'] ?></strong> - 
                                    ৳<?= number_format($payment['amount'], 2) ?> 
                                    <span class="badge bg-<?= $payment['status'] === 'COMPLETED' ? 'success' : ($payment['status'] === 'FAILED' ? 'danger' : 'warning') ?>">
                                        <?= $payment['status'] ?>
                                    </span><br>
                                    <?php if ($payment['transaction_id']): ?>
                                        Transaction ID: <?= htmlspecialchars($payment['transaction_id']) ?><br>
                                    <?php endif; ?>
                                    Date: <?= date('M j, Y g:i A', strtotime($payment['created_at'])) ?>
                                    <?php if ($payment['notes']): ?>
                                        <br>Notes: <?= htmlspecialchars($payment['notes']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <?php if ($order_detail['status'] === 'PENDING' && ($order_detail['payment_status'] ?? 'UNPAID') === 'UNPAID'): ?>
                            <a href="<?= $BASE_URL ?>checkout.php?order_id=<?= $order_detail['order_id'] ?>" class="btn btn-primary">Complete Payment</a>
                        <?php endif; ?>
                        
                        <a href="<?= $BASE_URL ?>buyer/chat.php?order_id=<?= $order_detail['order_id'] ?>" class="btn btn-info">
                            <i class="fas fa-comments"></i> Chat with Farmer
                        </a>
                        
                        <a href="<?= $BASE_URL ?>buyer/orders.php" class="btn btn-secondary">Back to Orders</a>
                    </div>
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
                                            <a href="<?= $BASE_URL ?>buyer/chat.php?order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-info" title="Chat with Farmer">
                                                <i class="fas fa-comments"></i> Chat
                                            </a>
                                            <?php 
                                            $payment_status = $order['payment_status'] ?? 'UNPAID';
                                            $order_status = $order['status'] ?? 'PENDING';
                                            
                                            if ($payment_status === 'UNPAID' && $order_status === 'PENDING'): ?>
                                                <a href="<?= $BASE_URL ?>checkout.php?order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-success">Pay Now</a>
                                            <?php elseif ($payment_status === 'PENDING' || ($payment_status === 'UNPAID' && $order_status === 'PROCESSING')): ?>
                                                <span class="badge bg-warning">Payment Under Review</span>
                                            <?php elseif ($payment_status === 'PAID'): ?>
                                                <span class="badge bg-success">Payment Verified</span>
                                            <?php elseif ($payment_status === 'REFUNDED'): ?>
                                                <span class="badge bg-danger">Refunded</span>
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

