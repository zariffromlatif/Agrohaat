<?php
require_once '../../config/config.php';
require_once '../../controllers/AdminController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    redirect('admin/login.php');
}

$admin = new AdminController($pdo);
$stats = $admin->viewDashboard();

$site_title  = "Admin Dashboard | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Admin Dashboard</h2>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <h3><?= $stats['users'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <h3><?= $stats['orders'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Open Disputes</h5>
                        <h3><?= $stats['disputes'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Products</h5>
                        <h3><?= $stats['products'] ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Items Alerts -->
        <?php 
        // Count pending products
        $pendingProductsStmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'PENDING' AND is_deleted = 0");
        $pendingProducts = $pendingProductsStmt->fetch()['count'] ?? 0;
        ?>
        
        <?php if ($pendingProducts > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
            <strong><i class="fas fa-box"></i> Product Approval Needed!</strong> 
            You have <strong><?= $pendingProducts ?></strong> product(s) awaiting approval.
            <a href="<?= $BASE_URL ?>admin/products.php" class="alert-link">Click here to review and approve products</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($stats['pending_payments']) && $stats['pending_payments'] > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
            <strong><i class="fas fa-exclamation-triangle"></i> Payment Verification Needed!</strong> 
            You have <strong><?= $stats['pending_payments'] ?></strong> payment(s) awaiting verification.
            <a href="<?= $BASE_URL ?>admin/payments.php" class="alert-link">Click here to verify payments</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>admin/users.php" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>admin/products.php" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-box"></i> Manage Products
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>admin/payments.php" class="btn btn-warning w-100 mb-2">
                    <i class="fas fa-money-bill-wave"></i> Verify Payments
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>admin/disputes.php" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-gavel"></i> Manage Disputes
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>admin/reviews.php" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-star"></i> Manage Reviews
                </a>
            </div>
        </div>

        <!-- API Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-code"></i> API Endpoints Available</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">All moderation actions are available via RESTful API endpoints:</p>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> <code>GET /api/admin/users.php</code> - List all users</li>
                            <li><i class="fas fa-check text-success"></i> <code>POST /api/admin/users/approve.php</code> - Approve user</li>
                            <li><i class="fas fa-check text-success"></i> <code>POST /api/admin/users/suspend.php</code> - Suspend/Unsuspend user</li>
                            <li><i class="fas fa-check text-success"></i> <code>GET /api/admin/products.php</code> - List all products</li>
                            <li><i class="fas fa-check text-success"></i> <code>POST /api/admin/products/delete.php</code> - Delete product</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> <code>GET /api/admin/disputes.php</code> - List all disputes</li>
                            <li><i class="fas fa-check text-success"></i> <code>POST /api/admin/disputes/resolve.php</code> - Resolve dispute</li>
                            <li><i class="fas fa-check text-success"></i> <code>GET /api/admin/payments.php</code> - List pending payments</li>
                            <li><i class="fas fa-check text-success"></i> <code>POST /api/admin/payments/approve.php</code> - Approve/Reject payment</li>
                            <li><i class="fas fa-check text-success"></i> <code>GET /api/admin/reviews.php</code> - List all reviews</li>
                        </ul>
                    </div>
                </div>
                <a href="<?= $BASE_URL ?>api/admin/README.md" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="fas fa-book"></i> View API Documentation
                </a>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

