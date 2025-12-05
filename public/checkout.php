<?php
require_once '../config/config.php';
require_once '../controllers/BuyerController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'BUYER') {
    redirect('buyer/login.php');
}

$controller = new BuyerController($pdo);
$message = '';
$error = '';

// Get cart items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirect('cart.php');
}

$cart_items = [];
$total = 0;
foreach ($_SESSION['cart'] as $cart_item) {
    $product = $controller->getProduct($cart_item['product_id']);
    if ($product) {
        $subtotal = $product['price_per_unit'] * $cart_item['quantity'];
        $total += $subtotal;
        $cart_items[] = [
            'product' => $product,
            'quantity' => $cart_item['quantity'],
            'subtotal' => $subtotal
        ];
    }
}

// Handle order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $shipping_address = trim($_POST['shipping_address']);
    
    if (empty($shipping_address)) {
        $error = 'Please provide shipping address.';
    } else {
        $result = $controller->createOrder($_SESSION['user_id'], $_SESSION['cart'], $shipping_address);
        
        if ($result['success']) {
            // Clear cart
            $_SESSION['cart'] = [];
            redirect('checkout.php?order_id=' . $result['order_id']);
        } else {
            $error = $result['message'];
        }
    }
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $order_id = intval($_POST['order_id']);
    $payment_method = trim($_POST['payment_method']);
    $transaction_id = trim($_POST['transaction_id']);
    
    if (empty($transaction_id)) {
        $error = 'Please provide transaction ID.';
    } else {
        $result = $controller->processPayment($order_id, $_SESSION['user_id'], $payment_method, $transaction_id);
        
        if ($result['success']) {
            $message = 'Payment processed successfully! Your order has been confirmed.';
            redirect('buyer/orders.php?id=' . $order_id);
        } else {
            $error = $result['message'];
        }
    }
}

// Get order if order_id is provided
$order = null;
if (isset($_GET['order_id'])) {
    $order = $controller->getOrderDetails($_GET['order_id'], $_SESSION['user_id']);
}

$site_title  = "Checkout | AgroHaat";
$special_css = "innerpage";
include '../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Checkout</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($order): ?>
            <!-- Payment Form -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Complete Payment</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Order ID:</strong> #<?= $order['order_id'] ?></p>
                            <p><strong>Total Amount:</strong> ৳<?= number_format($order['total_amount'], 2) ?></p>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Payment Method *</label>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="">Select payment method</option>
                                        <option value="BKASH">bKash</option>
                                        <option value="NAGAD">Nagad</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Transaction ID *</label>
                                    <input type="text" name="transaction_id" class="form-control" placeholder="Enter transaction ID" required>
                                    <small class="form-text text-muted">Enter the transaction ID from your payment confirmation SMS.</small>
                                </div>
                                
                                <button type="submit" name="process_payment" class="btn btn-primary">Confirm Payment</button>
                                <a href="<?= $BASE_URL ?>buyer/orders.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Order Creation Form -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Order Summary</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['product']['title']) ?></td>
                                                <td><?= number_format($item['quantity'], 2) ?> <?= htmlspecialchars($item['product']['unit']) ?></td>
                                                <td>৳<?= number_format($item['product']['price_per_unit'], 2) ?></td>
                                                <td>৳<?= number_format($item['subtotal'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Total</th>
                                            <th>৳<?= number_format($total, 2) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4>Shipping Information</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Shipping Address *</label>
                                    <textarea name="shipping_address" class="form-control" rows="4" required placeholder="Enter your complete shipping address"></textarea>
                                </div>
                                
                                <button type="submit" name="create_order" class="btn btn-primary">Create Order</button>
                                <a href="<?= $BASE_URL ?>cart.php" class="btn btn-secondary">Back to Cart</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
