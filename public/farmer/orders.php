<?php
require_once '../../config/config.php';
require_once '../../controllers/OrderController.php';
require_once '../../models/Order.php';
require_once '../../controllers/ReviewController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

$controller = new OrderController($pdo);
$reviewController = new ReviewController($pdo);
$orders = $controller->getFarmerOrders($_SESSION['user_id']);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $order_id = intval($_POST['order_id']);
    $buyer_id = intval($_POST['buyer_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment'] ?? '');
    
    $result = $reviewController->submitReview($order_id, $_SESSION['user_id'], $buyer_id, $rating, $comment);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    redirect('farmer/orders.php?id=' . $order_id);
}

// Get order detail if ID is provided
$order_detail = null;
$order_items = [];
$existing_review = null;
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
        $existing_review = $reviewController->getReviewForOrder($order_id, $_SESSION['user_id']);
    }
}

$site_title  = "My Orders | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">My Orders</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

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
                    
                    <?php
                    // Show rating form if order is delivered and not yet rated
                    if ($order_detail['status'] === 'DELIVERED' && !$existing_review):
                    ?>
                        <hr>
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">⭐ Rate Your Experience</h5>
                                <p class="text-muted">Rate your experience with this buyer.</p>
                                
                                <form method="POST" action="">
                                    <input type="hidden" name="order_id" value="<?= $order_detail['order_id'] ?>">
                                    <input type="hidden" name="buyer_id" value="<?= $order_detail['buyer_id'] ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Rating (1-5 stars)</label>
                                        <div class="rating-input">
                                            <input type="radio" name="rating" value="5" id="rating5_f" required>
                                            <label for="rating5_f" class="star">★</label>
                                            <input type="radio" name="rating" value="4" id="rating4_f">
                                            <label for="rating4_f" class="star">★</label>
                                            <input type="radio" name="rating" value="3" id="rating3_f">
                                            <label for="rating3_f" class="star">★</label>
                                            <input type="radio" name="rating" value="2" id="rating2_f">
                                            <label for="rating2_f" class="star">★</label>
                                            <input type="radio" name="rating" value="1" id="rating1_f">
                                            <label for="rating1_f" class="star">★</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="comment_f" class="form-label">Comment (Optional)</label>
                                        <textarea name="comment" id="comment_f" class="form-control" rows="3" 
                                                  placeholder="Share your experience..."></textarea>
                                    </div>
                                    
                                    <button type="submit" name="submit_review" class="btn btn-primary">
                                        Submit Review
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php elseif ($existing_review): ?>
                        <hr>
                        <div class="alert alert-success">
                            <h6>✅ You've already rated this order</h6>
                            <p class="mb-0">
                                <strong>Your Rating:</strong> 
                                <?php for ($i = 0; $i < $existing_review['rating']; $i++): ?>★<?php endfor; ?>
                                (<?= $existing_review['rating'] ?>/5)
                            </p>
                            <?php if ($existing_review['comment']): ?>
                                <p class="mb-0 mt-2"><strong>Your Comment:</strong> <?= htmlspecialchars($existing_review['comment']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
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

<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input label.star {
    font-size: 2em;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.rating-input input[type="radio"]:checked ~ label.star,
.rating-input label.star:hover,
.rating-input label.star:hover ~ label.star {
    color: #ffc107;
}

.rating-input input[type="radio"]:checked ~ label.star {
    color: #ffc107;
}
</style>

<?php include '../../includes/footer.php'; ?>
