<?php
require_once '../../config/config.php';
require_once '../../controllers/ProductController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

if (empty($_GET['id'])) {
    redirect('farmer/products.php');
}

$productId   = (int) $_GET['id'];
$controller  = new ProductController($pdo);
$product     = $controller->getProductForFarmer($_SESSION['user_id'], $productId);

if (!$product) {
    redirect('farmer/products.php');
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->handleUpdate($_SESSION['user_id'], $productId);
}

$site_title  = "Edit Product | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Edit Product</h2>

        <form action="" method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Category ID</label>
                <input type="number" name="category_id" class="form-control" required
                       value="<?= htmlspecialchars($product['category_id']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required
                       value="<?= htmlspecialchars($product['title']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label">Quantity Available</label>
                <input type="number" step="0.01" name="quantity_available" class="form-control" required
                       value="<?= htmlspecialchars($product['quantity_available']) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" class="form-control" required
                       value="<?= htmlspecialchars($product['unit']) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Price per Unit (BDT)</label>
                <input type="number" step="0.01" name="price_per_unit" class="form-control" required
                       value="<?= htmlspecialchars($product['price_per_unit']) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Quality Grade</label>
                <select name="quality_grade" class="form-select" required>
                    <?php foreach (['A','B','C'] as $grade): ?>
                        <option value="<?= $grade ?>" <?= $product['quality_grade'] === $grade ? 'selected' : '' ?>>
                            <?= $grade ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Harvest Date</label>
                <input type="date" name="harvest_date" class="form-control" required
                       value="<?= htmlspecialchars($product['harvest_date']) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Batch Number</label>
                <input type="text" name="batch_number" class="form-control" required
                       value="<?= htmlspecialchars($product['batch_number']) ?>">
            </div>

            <div class="col-12">
                <button type="submit" class="theme-btn style-one">Update Product</button>
                <a href="<?= $BASE_URL ?>farmer/products.php" class="theme-btn style-two ms-2">Back</a>
            </div>
        </form>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
