<?php
require_once '../../config/config.php';
require_once '../../controllers/AdminController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    redirect('admin/login.php');
}

$admin = new AdminController($pdo);
$products = $admin->getAllProducts();

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $admin->deleteProduct($_POST['product_id']);
    header("Location: products.php?deleted=1");
    exit;
}

$site_title  = "Manage Products | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Product Management</h2>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Product deleted successfully!</div>
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
                        <tr>
                            <td><?= $p['product_id'] ?></td>
                            <td><?= htmlspecialchars($p['title']) ?></td>
                            <td><?= htmlspecialchars($p['farmer_name']) ?></td>
                            <td>৳<?= number_format($p['price_per_unit'], 2) ?></td>
                            <td><?= number_format($p['quantity_available'], 2) ?> <?= htmlspecialchars($p['unit']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($p['quality_grade']) ?></span></td>
                            <td>
                                <span class="badge bg-<?= $p['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>">
                                    <?= htmlspecialchars($p['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                            <td>
                                <?php if (!$p['is_deleted']): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</button>
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

