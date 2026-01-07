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
.payment-instructions {
    border: 1px solid #007bff;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
    background: #f8f9ff;
    display: none;
}
.payment-instructions.active {
    display: block;
}
.instruction-step {
    background: white;
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 5px;
    border-left: 4px solid #007bff;
}
.instruction-step .step-number {
    background: #007bff;
    color: white;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 10px;
}
.farmer-details {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 5px;
    padding: 15px;
    margin: 15px 0;
}
.transaction-form-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
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
                                                <input type="radio" name="payment_method" value="BKASH" required data-method="bkash">
                                                <div class="payment-card"><span>bKash</span></div>
                                            </label>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="payment-option">
                                                <input type="radio" name="payment_method" value="NAGAD" required data-method="nagad">
                                                <div class="payment-card"><span>Nagad</span></div>
                                            </label>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="payment-option">
                                                <input type="radio" name="payment_method" value="ROCKET" required data-method="rocket">
                                                <div class="payment-card"><span>Rocket</span></div>
                                            </label>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="payment-option">
                                                <input type="radio" name="payment_method" value="UPAY" required data-method="upay">
                                                <div class="payment-card"><span>Upay</span></div>
                                            </label>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="payment-option">
                                                <input type="radio" name="payment_method" value="BANK_TRANSFER" required data-method="bank">
                                                <div class="payment-card"><span>Bank Transfer</span></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Payment Instructions -->
                                <!-- bKash Instructions -->
                                <div id="instructions-bkash" class="payment-instructions">
                                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> bKash Payment Instructions</h5>
                                    
                                    <div class="farmer-details">
                                        <h6><i class="fas fa-user"></i> Farmer Payment Details</h6>
                                        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($order['farmer_name']) ?></p>
                                        <p class="mb-1"><strong>bKash Number:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($order['farmer_phone']) ?></span></p>
                                        <p class="mb-0"><strong>Amount to Send:</strong> <span class="text-danger fw-bold">৳<?= number_format($order['total_amount'], 2) ?></span></p>
                                    </div>
                                    
                                    <div class="instruction-step">
                                        <span class="step-number">1</span>
                                        <strong>Open bKash App or Dial *247#</strong> on your mobile phone
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">2</span>
                                        Select <strong>"Send Money"</strong> option
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">3</span>
                                        Enter farmer's bKash number: <strong><?= htmlspecialchars($order['farmer_phone']) ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">4</span>
                                        Enter amount: <strong>৳<?= number_format($order['total_amount'], 2) ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">5</span>
                                        Add reference: <strong>Order #<?= $order['order_id'] ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">6</span>
                                        Enter your bKash PIN and <strong>confirm the transaction</strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">7</span>
                                        You will receive a <strong>Transaction ID</strong> via SMS - Enter it below
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Please keep the transaction ID safe. Admin will verify your payment before order confirmation.
                                    </div>
                                </div>
                                
                                <!-- Nagad Instructions -->
                                <div id="instructions-nagad" class="payment-instructions">
                                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Nagad Payment Instructions</h5>
                                    
                                    <div class="farmer-details">
                                        <h6><i class="fas fa-user"></i> Farmer Payment Details</h6>
                                        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($order['farmer_name']) ?></p>
                                        <p class="mb-1"><strong>Nagad Number:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($order['farmer_phone']) ?></span></p>
                                        <p class="mb-0"><strong>Amount to Send:</strong> <span class="text-danger fw-bold">৳<?= number_format($order['total_amount'], 2) ?></span></p>
                                    </div>
                                    
                                    <div class="instruction-step">
                                        <span class="step-number">1</span>
                                        <strong>Open Nagad App or Dial *167#</strong> on your mobile phone
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">2</span>
                                        Select <strong>"Send Money"</strong> option
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">3</span>
                                        Enter farmer's Nagad number: <strong><?= htmlspecialchars($order['farmer_phone']) ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">4</span>
                                        Enter amount: <strong>৳<?= number_format($order['total_amount'], 2) ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">5</span>
                                        Add reference: <strong>Order #<?= $order['order_id'] ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">6</span>
                                        Enter your Nagad PIN and <strong>confirm the transaction</strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">7</span>
                                        You will receive a <strong>Transaction ID</strong> via SMS - Enter it below
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Please keep the transaction ID safe. Admin will verify your payment before order confirmation.
                                    </div>
                                </div>
                                
                                <!-- Rocket Instructions -->
                                <div id="instructions-rocket" class="payment-instructions">
                                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Rocket Payment Instructions</h5>
                                    
                                    <div class="farmer-details">
                                        <h6><i class="fas fa-user"></i> Farmer Payment Details</h6>
                                        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($order['farmer_name']) ?></p>
                                        <p class="mb-1"><strong>Rocket Number:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($order['farmer_phone']) ?></span></p>
                                        <p class="mb-0"><strong>Amount to Send:</strong> <span class="text-danger fw-bold">৳<?= number_format($order['total_amount'], 2) ?></span></p>
                                    </div>
                                    
                                    <div class="instruction-step">
                                        <span class="step-number">1</span>
                                        <strong>Dial *322#</strong> on your mobile phone
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">2</span>
                                        Select <strong>"Send Money"</strong> option
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">3</span>
                                        Enter farmer's Rocket number: <strong><?= htmlspecialchars($order['farmer_phone']) ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">4</span>
                                        Enter amount: <strong>৳<?= number_format($order['total_amount'], 2) ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">5</span>
                                        Add reference: <strong>Order #<?= $order['order_id'] ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">6</span>
                                        Enter your Rocket PIN and <strong>confirm the transaction</strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">7</span>
                                        You will receive a <strong>Transaction ID</strong> via SMS - Enter it below
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Please keep the transaction ID safe. Admin will verify your payment before order confirmation.
                                    </div>
                                </div>
                                
                                <!-- Upay Instructions -->
                                <div id="instructions-upay" class="payment-instructions">
                                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Upay Payment Instructions</h5>
                                    
                                    <div class="farmer-details">
                                        <h6><i class="fas fa-user"></i> Farmer Payment Details</h6>
                                        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($order['farmer_name']) ?></p>
                                        <p class="mb-1"><strong>Upay Number:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($order['farmer_phone']) ?></span></p>
                                        <p class="mb-0"><strong>Amount to Send:</strong> <span class="text-danger fw-bold">৳<?= number_format($order['total_amount'], 2) ?></span></p>
                                    </div>
                                    
                                    <div class="instruction-step">
                                        <span class="step-number">1</span>
                                        <strong>Open Upay App</strong> on your mobile phone
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">2</span>
                                        Select <strong>"Send Money"</strong> option
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">3</span>
                                        Enter farmer's Upay number: <strong><?= htmlspecialchars($order['farmer_phone']) ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">4</span>
                                        Enter amount: <strong>৳<?= number_format($order['total_amount'], 2) ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">5</span>
                                        Add reference: <strong>Order #<?= $order['order_id'] ?></strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">6</span>
                                        Enter your Upay PIN and <strong>confirm the transaction</strong>
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">7</span>
                                        You will receive a <strong>Transaction ID</strong> - Enter it below
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Please keep the transaction ID safe. Admin will verify your payment before order confirmation.
                                    </div>
                                </div>
                                
                                <!-- Bank Transfer Instructions -->
                                <div id="instructions-bank" class="payment-instructions">
                                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Bank Transfer Instructions</h5>
                                    
                                    <div class="farmer-details">
                                        <h6><i class="fas fa-building"></i> Farmer Bank Account Details</h6>
                                        <p class="mb-1"><strong>Account Holder:</strong> <?= htmlspecialchars($order['farmer_name']) ?></p>
                                        <p class="mb-1"><strong>Bank:</strong> <span class="text-primary">Dutch-Bangla Bank Limited (DBBL)</span></p>
                                        <p class="mb-1"><strong>Branch:</strong> <span class="text-primary">Gulshan Branch, Dhaka</span></p>
                                        <p class="mb-1"><strong>Account Number:</strong> <span class="text-primary fw-bold">123-456-789012</span></p>
                                        <p class="mb-1"><strong>Routing Number:</strong> <span class="text-primary fw-bold">090270704</span></p>
                                        <p class="mb-0"><strong>Amount to Transfer:</strong> <span class="text-danger fw-bold">৳<?= number_format($order['total_amount'], 2) ?></span></p>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3">
                                        <strong>Note:</strong> You can transfer money through your bank's mobile app, online banking, ATM, or by visiting a bank branch.
                                    </div>
                                    
                                    <h6 class="mt-3">Transfer Methods:</h6>
                                    
                                    <div class="instruction-step">
                                        <span class="step-number">1</span>
                                        <strong>Internet Banking:</strong> Log in to your bank's online portal, select "Fund Transfer" → "Other Bank Transfer" → Enter farmer's account details and amount
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">2</span>
                                        <strong>Mobile Banking App:</strong> Open your banking app → Select "Transfer" → "BEFTN/RTGS" → Enter farmer's account details
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">3</span>
                                        <strong>Bank Branch:</strong> Visit your bank with cash/cheque → Fill deposit slip with farmer's account number → Get transaction receipt
                                    </div>
                                    <div class="instruction-step">
                                        <span class="step-number">4</span>
                                        <strong>ATM Deposit:</strong> Insert card → Select "Deposit" → "Other Account" → Enter farmer's account number → Insert cash → Get receipt
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> After completing the transfer, enter the <strong>Transaction ID / Reference Number</strong> from your receipt below. Add reference "Order #<?= $order['order_id'] ?>" during transfer if possible.
                                    </div>
                                </div>
                                
                                <!-- Transaction ID Form -->
                                <div id="transaction-form" class="transaction-form-section" style="display: none;">
                                    <h6 class="mb-3"><i class="fas fa-receipt"></i> Submit Transaction Details</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Transaction ID / Reference Number *</label>
                                        <input type="text" name="transaction_id" class="form-control" 
                                               placeholder="Enter your transaction ID" required>
                                        <small class="form-text text-muted">
                                            Enter the transaction ID you received after completing the payment. 
                                            This will be verified by our admin team.
                                        </small>
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
                // Hide all instruction sections
                const allInstructions = document.querySelectorAll('.payment-instructions');
                allInstructions.forEach(function(inst) {
                    inst.classList.remove('active');
                });
                
                // Show selected payment instructions
                const method = this.getAttribute('data-method');
                const instructionsDiv = document.getElementById('instructions-' + method);
                if (instructionsDiv) {
                    instructionsDiv.classList.add('active');
                }
                
                // Show transaction form and enable button
                transactionForm.style.display = 'block';
                payBtn.disabled = false;
                payBtn.textContent = 'Submit Payment';
                
                // Scroll to instructions
                instructionsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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
                
                // Confirm submission
                if (!confirm('Have you completed the payment and received the transaction ID?\n\nTransaction ID: ' + transactionId + '\n\nClick OK to submit for admin verification.')) {
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
