<?php
require_once '../../config/config.php';
require_once '../../controllers/ProductController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

$controller = new ProductController($pdo);

// Handle form submission
$controller->handleCreate($_SESSION['user_id']);

$site_title  = "Add Product | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Add New Product</h2>

        <form action="" method="post" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Category ID</label>
                <input type="number" name="category_id" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" required></textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label">Quantity Available</label>
                <input type="number" step="0.01" name="quantity_available" class="form-control" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" class="form-control" placeholder="kg, ton" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Price per Unit (BDT)</label>
                <input type="number" step="0.01" name="price_per_unit" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Quality Grade</label>
                <select name="quality_grade" class="form-select" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Harvest Date</label>
                <input type="date" name="harvest_date" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Batch Number</label>
                <input type="text" name="batch_number" class="form-control" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Product Images (Multiple photos allowed)</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                <small class="form-text text-muted">You can select multiple images at once. First image will be set as primary.</small>
            </div>

            <div class="col-12">
                <button type="submit" class="theme-btn style-one">Save Product</button>
                <a href="<?= $BASE_URL ?>farmer/products.php" class="theme-btn style-two ms-2">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
