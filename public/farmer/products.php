<?php
require_once '../../config/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

require_once '../../controllers/ProductController.php';
$controller = new ProductController($pdo);
$products   = $controller->getProductsForFarmer($_SESSION['user_id']);

$site_title = "My Products | AgroHaat";
include '../../includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">My Products</h2>
    <a href="<?= $BASE_URL ?>farmer/product_add.php" class="theme-btn style-one mb-3">+ Add New Product</a>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Title</th>
                 <th>Trace ID</th>
                <th>Quantity</th>
                <th>Price / Unit</th>
                <th>Grade</th>
                <th>Status</th>
                <th>Image</th>
                 <th>QR</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['title']) ?></td>
                     <td><?= htmlspecialchars($p['trace_id']) ?></td>
                    <td><?= htmlspecialchars($p['quantity_available']) . ' ' . htmlspecialchars($p['unit']) ?></td>
                    <td><?= htmlspecialchars($p['price_per_unit']) ?></td>
                    <td><?= htmlspecialchars($p['quality_grade']) ?></td>
                    <td><?= htmlspecialchars($p['status']) ?></td>
                    <td>
                        <?php if (!empty($p['image_url'])): ?>
                            <img src="<?= $BASE_URL . htmlspecialchars($p['image_url']) ?>" width="60">
                        <?php endif; ?>
                    </td>
                     <td>
                         <?php if (!empty($p['qr_code_url'])): ?>
                             <a href="<?= $BASE_URL ?>trace.php?tid=<?= urlencode($p['trace_id']) ?>" target="_blank">
                                 View
                             </a>
                         <?php else: ?>
                             N/A
                         <?php endif; ?>
                     </td>
                    <td>
                        <a href="product_edit.php?id=<?= $p['product_id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="product_delete.php?id=<?= $p['product_id'] ?>"
                           onclick="return confirm('Delete this product?');"
                           class="btn btn-sm btn-danger">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
