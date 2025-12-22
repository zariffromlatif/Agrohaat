<?php
require_once '../../config/config.php';
require_once '../../models/Payment.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    redirect('admin/login.php');
}

$paymentModel = new Payment($pdo);
$message = '';
$error = '';

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_payment'])) {
        $payment_id = intval($_POST['payment_id']);
        $notes = trim($_POST['notes']) ?: null;
        
        // Verify payment exists and is in pending/processing status
        $payment = $paymentModel->getPaymentById($payment_id);
        if (!$payment) {
            $error = 'Payment not found.';
        } elseif (!in_array($payment['status'], ['PENDING', 'PROCESSING'])) {
            $error = 'Payment is already processed. Current status: ' . $payment['status'];
        } elseif ($paymentModel->completePayment($payment_id, $notes)) {
            // Update order status
            $payment = $paymentModel->getPaymentById($payment_id);
            if ($payment) {
                $order_id = $payment['order_id'];
                $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'PAID', status = 'CONFIRMED' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                
                // Create delivery job if it doesn't exist
                try {
                    // Check if deliveryjobs table exists
                    $pdo->query("SELECT 1 FROM deliveryjobs LIMIT 1");
                    
                    // Check if job already exists for this order
                    $checkStmt = $pdo->prepare("SELECT job_id FROM deliveryjobs WHERE order_id = ?");
                    $checkStmt->execute([$order_id]);
                    $existingJob = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existingJob) {
                        // Get order details with farmer and buyer locations
                        $orderStmt = $pdo->prepare("
                            SELECT o.order_id, o.shipping_address,
                                   farmer.district as farmer_district, 
                                   farmer.upazila as farmer_upazila,
                                   farmer.address_details as farmer_address
                            FROM orders o
                            INNER JOIN users farmer ON o.farmer_id = farmer.user_id
                            WHERE o.order_id = ?
                        ");
                        $orderStmt->execute([$order_id]);
                        $orderDetails = $orderStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($orderDetails) {
                            // Build pickup location from farmer's address
                            $pickup_location = trim(
                                ($orderDetails['farmer_district'] ?? '') . ', ' .
                                ($orderDetails['farmer_upazila'] ?? '') . ', ' .
                                ($orderDetails['farmer_address'] ?? '')
                            );
                            $pickup_location = rtrim($pickup_location, ', ');
                            
                            // Use order shipping address as dropoff location
                            $dropoff_location = $orderDetails['shipping_address'];
                            
                            // Create delivery job
                            $jobStmt = $pdo->prepare("
                                INSERT INTO deliveryjobs (order_id, pickup_location, dropoff_location, status)
                                VALUES (?, ?, ?, 'OPEN')
                            ");
                            $jobStmt->execute([$order_id, $pickup_location, $dropoff_location]);
                        }
                    }
                } catch (PDOException $e) {
                    // If deliveryjobs table doesn't exist, just log and continue
                    error_log("Could not create delivery job: " . $e->getMessage());
                }
            }
            header("Location: payments.php?approved=1");
            exit;
        } else {
            $error = 'Failed to approve payment.';
        }
    } elseif (isset($_POST['reject_payment'])) {
        $payment_id = intval($_POST['payment_id']);
        $notes = trim($_POST['notes']);
        
        if ($paymentModel->failPayment($payment_id, $notes)) {
            header("Location: payments.php?rejected=1");
            exit;
        } else {
            $error = 'Failed to reject payment.';
        }
    }
}

// Get pending payments
$pendingPayments = [];
try {
    // First check if the new schema columns exist
    $checkColumns = $pdo->query("SHOW COLUMNS FROM payments LIKE 'method_id'");
    $hasNewSchema = $checkColumns->rowCount() > 0;
    
    if ($hasNewSchema) {
        // New schema with method_id, user_id, etc.
        $sql = "SELECT p.*, 
                       pm.name as method_name, pm.type as method_type, pm.provider,
                       o.order_id, o.total_amount as order_amount,
                       u.full_name as buyer_name, u.phone_number as buyer_phone, u.email as buyer_email
                FROM payments p
                LEFT JOIN payment_methods pm ON p.method_id = pm.method_id
                LEFT JOIN orders o ON p.order_id = o.order_id
                LEFT JOIN users u ON p.user_id = u.user_id
                WHERE p.status IN ('PENDING', 'PROCESSING')
                ORDER BY p.created_at ASC";
    } else {
        // Old schema - simpler payments table (only show PENDING)
        $sql = "SELECT p.*, 
                       p.payment_method as method_name,
                       o.order_id, o.total_amount as order_amount,
                       u.full_name as buyer_name, u.phone_number as buyer_phone, u.email as buyer_email
                FROM payments p
                LEFT JOIN orders o ON p.order_id = o.order_id
                LEFT JOIN users u ON o.buyer_id = u.user_id
                WHERE p.status = 'PENDING'
                ORDER BY p.paid_at ASC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log how many payments were found
    error_log("Found " . count($pendingPayments) . " pending payments");
} catch (PDOException $e) {
    $error = 'Error loading payments: ' . $e->getMessage();
    error_log("Payment query error: " . $e->getMessage() . " | SQL: " . $sql);
}

$site_title = "Payment Management | AgroHaat Admin";
include '../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Payment Management</h2>
            
            <?php if (isset($_GET['approved'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Payment approved successfully! Order status updated and delivery job created.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['rejected'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-times-circle"></i> Payment rejected successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Pending Payment Verifications</h4>
                    <?php 
                    // Debug: Show total payments count
                    try {
                        $debugStmt = $pdo->query("SELECT COUNT(*) as total, status FROM payments GROUP BY status");
                        $debugCounts = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                        if (!empty($debugCounts)): ?>
                            <small class="text-muted">
                                Total Payments: 
                                <?php foreach ($debugCounts as $count): ?>
                                    <?= $count['status'] ?>: <?= $count['total'] ?> | 
                                <?php endforeach; ?>
                            </small>
                        <?php endif;
                    } catch (Exception $e) {
                        // Ignore debug errors
                    }
                    ?>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingPayments)): ?>
                        <div class="alert alert-info">
                            <p><strong>No pending payments to verify.</strong></p>
                            <p class="mb-0 small">If you just submitted a payment, please check:</p>
                            <ul class="small">
                                <li>Payment was created successfully (check error logs)</li>
                                <li>Payment status is 'PENDING' or 'PROCESSING'</li>
                                <li>Database tables exist and have correct structure</li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Order</th>
                                        <th>Buyer</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Transaction ID</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingPayments as $payment): ?>
                                        <tr>
                                            <td>#<?= $payment['payment_id'] ?></td>
                                            <td>
                                                <strong>Order #<?= $payment['order_id'] ?></strong><br>
                                                <small>৳<?= number_format($payment['order_amount'], 2) ?></small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($payment['buyer_name']) ?></strong><br>
                                                <small><?= htmlspecialchars($payment['buyer_phone']) ?></small><br>
                                                <small><?= htmlspecialchars($payment['buyer_email']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($payment['method_name'] ?? 'N/A') ?></span><br>
                                                <?php if (isset($payment['method_type']) && $payment['method_type']): ?>
                                                    <small><?= htmlspecialchars($payment['method_type']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>৳<?= number_format($payment['amount'], 2) ?></td>
                                            <td>
                                                <?php if (!empty($payment['transaction_id'])): ?>
                                                    <code><?= htmlspecialchars($payment['transaction_id']) ?></code>
                                                <?php else: ?>
                                                    <small class="text-muted">Not provided</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M j, Y g:i A', strtotime($payment['created_at'] ?? $payment['paid_at'] ?? 'now')) ?></td>
                                            <td>
                                                <span class="badge bg-warning"><?= $payment['status'] ?></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                                        data-bs-target="#approveModal<?= $payment['payment_id'] ?>">
                                                    Approve
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal<?= $payment['payment_id'] ?>">
                                                    Reject
                                                </button>
                                                
                                                <!-- Approve Modal -->
                                                <div class="modal fade" id="approveModal<?= $payment['payment_id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Approve Payment</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="payment_id" value="<?= $payment['payment_id'] ?>">
                                                                    <p><strong>Payment Details:</strong></p>
                                                                    <ul>
                                                                        <li>Order: #<?= $payment['order_id'] ?></li>
                                                                        <li>Amount: ৳<?= number_format($payment['amount'], 2) ?></li>
                                                                        <li>Method: <?= $payment['method_name'] ?></li>
                                                                        <li>Transaction ID: <?= htmlspecialchars($payment['transaction_id'] ?? 'N/A') ?></li>
                                                                    </ul>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Approval Notes (Optional)</label>
                                                                        <textarea name="notes" class="form-control" rows="3" 
                                                                                  placeholder="Add any notes about this payment approval..."></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="approve_payment" class="btn btn-success">Approve Payment</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Reject Modal -->
                                                <div class="modal fade" id="rejectModal<?= $payment['payment_id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Reject Payment</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="payment_id" value="<?= $payment['payment_id'] ?>">
                                                                    <div class="alert alert-warning">
                                                                        <strong>Warning:</strong> This will mark the payment as failed and the order will remain unpaid.
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Rejection Reason *</label>
                                                                        <textarea name="notes" class="form-control" rows="3" required
                                                                                  placeholder="Please provide a reason for rejecting this payment..."></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="reject_payment" class="btn btn-danger">Reject Payment</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
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
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
