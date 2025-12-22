<?php
require_once '../../config/config.php';
require_once '../../controllers/AdminController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    redirect('admin/login.php');
}

$admin = new AdminController($pdo);
$products = $admin->getAllProducts();

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_product'])) {
        $admin->deleteProduct($_POST['product_id']);
        header("Location: products.php?deleted=1");
        exit;
    } elseif (isset($_POST['approve_product'])) {
        $admin->approveProduct($_POST['product_id']);
        header("Location: products.php?approved=1");
        exit;
    } elseif (isset($_POST['reject_product'])) {
        $admin->rejectProduct($_POST['product_id']);
        header("Location: products.php?rejected=1");
        exit;
    }
}

$site_title  = "Manage Products | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Product Management</h2>
            <?php 
            // Count pending products
            $pendingCount = 0;
            foreach ($products as $p) {
                if ($p['status'] === 'PENDING' && !$p['is_deleted']) {
                    $pendingCount++;
                }
            }
            if ($pendingCount > 0): ?>
                <span class="badge bg-warning text-dark fs-6">
                    <i class="fas fa-exclamation-circle"></i> <?= $pendingCount ?> Product(s) Awaiting Approval
                </span>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Product deleted successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['approved'])): ?>
            <div class="alert alert-success">Product approved successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['rejected'])): ?>
            <div class="alert alert-warning">Product rejected.</div>
        <?php endif; ?>
        
        <?php if ($pendingCount > 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <strong>Note:</strong> Products with <span class="badge bg-warning">PENDING</span> status are waiting for your approval. Click <strong>Approve</strong> to make them visible in the marketplace.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Farmer</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr class="<?= $p['status'] === 'PENDING' && !$p['is_deleted'] ? 'table-warning' : '' ?>">
                            <td><?= $p['product_id'] ?></td>
                            <td><?= htmlspecialchars($p['title']) ?></td>
                            <td><?= htmlspecialchars($p['farmer_name']) ?></td>
                            <td>৳<?= number_format($p['price_per_unit'], 2) ?></td>
                            <td><?= number_format($p['quantity_available'], 2) ?> <?= htmlspecialchars($p['unit']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($p['quality_grade']) ?></span></td>
                            <td>
                                <span class="badge bg-<?= $p['status'] === 'ACTIVE' ? 'success' : ($p['status'] === 'PENDING' ? 'warning' : 'secondary') ?>">
                                    <?= htmlspecialchars($p['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                            <td>
                                <?php if (!$p['is_deleted']): ?>
                                    <?php if ($p['status'] === 'PENDING'): ?>
                                        <div class="btn-group" role="group">
                                            <form method="POST" action="" style="display:inline-block;">
                                                <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                                <button type="submit" name="approve_product" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="" style="display:inline-block;">
                                                <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                                <button type="submit" name="reject_product" class="btn btn-warning btn-sm" onclick="return confirm('Reject this product?')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                    <form method="POST" action="" style="display:inline-block; margin-top: 5px;">
                                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Deleted</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

