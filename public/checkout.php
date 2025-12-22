<?php
/**
 * Checkout Page - Rebuilt for reliability
 * Handles order creation and payment processing
 */
ob_start();
require_once '../config/config.php';
require_once '../controllers/BuyerController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'BUYER') {
    redirect('buyer/login.php');
}

$controller = new BuyerController($pdo);
$message = '';
$error = '';

// ============================================
// STEP 1: Handle Order Creation
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    
    if (empty($shipping_address)) {
        $error = 'Please provide shipping address.';
    } elseif (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        $error = 'Your cart is empty.';
        redirect('cart.php');
    } else {
        $result = $controller->createOrder($_SESSION['user_id'], $_SESSION['cart'], $shipping_address);
        
        if ($result['success']) {
            $_SESSION['cart'] = [];
            redirect('checkout.php?order_id=' . $result['order_id']);
        } else {
            $error = $result['message'];
        }
    }
}

// ============================================
// STEP 2: Handle Payment Processing
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    error_log("Payment submission received. POST data: " . print_r($_POST, true));
    
    $order_id = intval($_POST['order_id'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? '');
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    
    error_log("Parsed: order_id=$order_id, payment_method=$payment_method, transaction_id=$transaction_id");
    
    if (!$order_id) {
        $error = 'Invalid order ID.';
        error_log("Payment error: Invalid order ID");
    } elseif (empty($payment_method)) {
        $error = 'Please select a payment method.';
        error_log("Payment error: Payment method not selected");
    } elseif (empty($transaction_id)) {
        $error = 'Please provide transaction ID.';
        error_log("Payment error: Transaction ID not provided");
    } else {
        error_log("Calling processPayment: order_id=$order_id, user_id={$_SESSION['user_id']}, method=$payment_method");
        $result = $controller->processPayment($order_id, $_SESSION['user_id'], $payment_method, $transaction_id);
        error_log("processPayment result: " . print_r($result, true));
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            error_log("Payment successful, redirecting to dashboard");
            
            // Clear output buffers for redirect
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            redirect('buyer/dashboard.php');
        } else {
            $error = $result['message'];
            error_log("Payment failed: " . $result['message']);
        }
    }
}

// ============================================
// STEP 3: Get Order or Cart Data
// ============================================
$order = null;
if (isset($_GET['order_id'])) {
    $order = $controller->getOrderDetails(intval($_GET['order_id']), $_SESSION['user_id']);
    if (!$order) {
        $error = 'Order not found.';
    }
}

// If no order, get cart items
$cart_items = [];
$total = 0;
if (!$order) {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        redirect('cart.php');
    }
    
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
}

// Check for success message
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$site_title = "Checkout | AgroHaat";
$special_css = "innerpage";
include '../includes/header.php';
?>

<style>
.payment-option {
    cursor: pointer;
    margin-bottom: 10px;
}
.payment-option input[type="radio"] {
    display: none;
}
.payment-card {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    transition: all 0.3s ease;
    background: white;
}
.payment-option:hover .payment-card {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
}
.payment-option input[type="radio"]:checked + .payment-card {
    border-color: #007bff;
    background-color: #f8f9ff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
}
.payment-form {
    border: 1px solid #007bff;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
    background: #f8f9ff;
    display: none;
}
#pay-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

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
                            
                            <form method="POST" id="payment-form" action="<?= $_SERVER['PHP_SELF'] ?>">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                
                                <div class="mb-4">
                                    <label class="form-label"><strong>Select Payment Method *</strong></label>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="payment-option">
                                                <input type="radio" name="payment_method" value="BKASH" required>
                                                <div class="payment-card"><span>bKash</span></div>
                                            </label>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="payment-option">
                                                <input type="radio" name="payment_method" value="NAGAD" required>
                                                <div class="payment-card"><span>Nagad</span></div>
                                            </label>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="payment-option">
                                                <input type="radio" name="payment_method" value="ROCKET" required>
                                                <div class="payment-card"><span>Rocket</span></div>
                                            </label>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="payment-option">
                                                <input type="radio" name="payment_method" value="UPAY" required>
                                                <div class="payment-card"><span>Upay</span></div>
                                            </label>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="payment-option">
                                                <input type="radio" name="payment_method" value="BANK_TRANSFER" required>
                                                <div class="payment-card"><span>Bank Transfer</span></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Transaction ID Form -->
                                <div id="transaction-form" class="payment-form">
                                    <div class="alert alert-info">
                                        <strong>Instructions:</strong><br>
                                        Complete your payment using the selected method, then enter the Transaction ID you received.
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Transaction ID *</label>
                                        <input type="text" name="transaction_id" class="form-control" 
                                               placeholder="Enter transaction ID" required>
                                        <small class="form-text text-muted">Enter the transaction ID from your payment confirmation.</small>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" name="process_payment" class="btn btn-primary btn-lg" id="pay-btn" disabled>
                                        Submit Payment
                                    </button>
                                    <a href="<?= $BASE_URL ?>buyer/orders.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Order ID:</strong> #<?= $order['order_id'] ?></p>
                            <p><strong>Amount:</strong> ৳<?= number_format($order['total_amount'], 2) ?></p>
                            <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total to Pay:</strong>
                                <strong>৳<?= number_format($order['total_amount'], 2) ?></strong>
                            </div>
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
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Shipping Address *</label>
                                    <textarea name="shipping_address" class="form-control" rows="4" required 
                                              placeholder="Enter your complete shipping address"></textarea>
                                </div>
                                
                                <button type="submit" name="create_order" class="btn btn-primary">Create Order & Continue to Payment</button>
                                <a href="<?= $BASE_URL ?>cart.php" class="btn btn-secondary">Back to Cart</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const transactionForm = document.getElementById('transaction-form');
    const payBtn = document.getElementById('pay-btn');
    
    if (paymentMethods.length > 0 && transactionForm && payBtn) {
        paymentMethods.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    transactionForm.style.display = 'block';
                    payBtn.disabled = false;
                    payBtn.textContent = 'Submit Payment';
                }
            });
        });
        
        // Form validation
        const paymentForm = document.getElementById('payment-form');
        if (paymentForm) {
            paymentForm.addEventListener('submit', function(e) {
                const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                const transactionId = document.querySelector('input[name="transaction_id"]').value.trim();
                
                if (!selectedPayment) {
                    alert('Please select a payment method.');
                    e.preventDefault();
                    return;
                }
                
                if (!transactionId) {
                    alert('Please enter transaction ID.');
                    e.preventDefault();
                    return;
                }
                
                // Show loading state
                payBtn.disabled = true;
                payBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            });
        }
    }
});
</script>

<?php 
include '../includes/footer.php'; 
ob_end_flush();
?>
