<?php
require_once '../config/config.php';
require_once '../controllers/BuyerController.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    redirect('shop.php');
}

$controller = new BuyerController($pdo);
$product = $controller->getProduct($product_id);

if (!$product) {
    redirect('shop.php');
}

// Get all product images
require_once '../models/ProductImage.php';
$imageModel = new ProductImage($pdo);
$productImages = $imageModel->getByProductId($product_id);

// If no images in product_images table, use the old image_url field for backward compatibility
if (empty($productImages) && !empty($product['image_url'])) {
    $productImages = [['image_url' => $product['image_url'], 'is_primary' => 1]];
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $quantity = floatval($_POST['quantity']);
    if ($quantity > 0) {
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'quantity' => $quantity
            ];
        }
        header("Location: cart.php?added=1");
        exit;
    }
}

$site_title  = htmlspecialchars($product['title']) . " | AgroHaat";
$special_css = "innerpage";
include '../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <?php if (!empty($productImages)): ?>
                    <!-- Image Gallery -->
                    <div class="product-image-gallery">
                        <!-- Main Image Display -->
                        <div class="main-image mb-3" style="border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
                            <img id="mainProductImage" src="<?= $BASE_URL . htmlspecialchars($productImages[0]['image_url']) ?>" 
                                 class="img-fluid" 
                                 alt="<?= htmlspecialchars($product['title']) ?>"
                                 style="width: 100%; height: 400px; object-fit: cover; cursor: pointer;"
                                 onclick="openImageModal(this.src)">
                        </div>
                        
                        <!-- Thumbnail Gallery -->
                        <?php if (count($productImages) > 1): ?>
                            <div class="thumbnail-gallery d-flex gap-2" style="flex-wrap: wrap;">
                                <?php foreach ($productImages as $index => $img): ?>
                                    <div class="thumbnail-item" style="width: 80px; height: 80px; border: 2px solid <?= $index === 0 ? '#007bff' : '#dee2e6' ?>; border-radius: 4px; overflow: hidden; cursor: pointer; transition: border-color 0.3s;">
                                        <img src="<?= $BASE_URL . htmlspecialchars($img['image_url']) ?>" 
                                             class="img-fluid" 
                                             alt="Thumbnail <?= $index + 1 ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;"
                                             onclick="changeMainImage('<?= $BASE_URL . htmlspecialchars($img['image_url']) ?>', this)"
                                             onmouseover="this.parentElement.style.borderColor='#007bff'"
                                             onmouseout="this.parentElement.style.borderColor='<?= $index === 0 ? '#007bff' : '#dee2e6' ?>'">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Image Modal -->
                    <div class="modal fade" id="imageModal" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?= htmlspecialchars($product['title']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img id="modalImage" src="" class="img-fluid" alt="<?= htmlspecialchars($product['title']) ?>" style="max-height: 70vh;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        function changeMainImage(imageUrl, thumbnail) {
                            document.getElementById('mainProductImage').src = imageUrl;
                            // Update thumbnail borders
                            document.querySelectorAll('.thumbnail-item').forEach(item => {
                                item.style.borderColor = '#dee2e6';
                            });
                            thumbnail.parentElement.style.borderColor = '#007bff';
                        }
                        
                        function openImageModal(imageUrl) {
                            document.getElementById('modalImage').src = imageUrl;
                            var modal = new bootstrap.Modal(document.getElementById('imageModal'));
                            modal.show();
                        }
                    </script>
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                        <i class="fas fa-image fa-5x text-muted"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <h2><?= htmlspecialchars($product['title']) ?></h2>
                <p class="text-muted">By: <?= htmlspecialchars($product['farmer_name']) ?></p>
                
                <div class="mb-3">
                    <span class="badge bg-info"><?= htmlspecialchars($product['quality_grade']) ?></span>
                    <span class="text-muted"><?= htmlspecialchars($product['district']) ?>, <?= htmlspecialchars($product['upazila'] ?? '') ?></span>
                </div>
                
                <h3 class="text-primary">৳<?= number_format($product['price_per_unit'], 2) ?> per <?= htmlspecialchars($product['unit']) ?></h3>
                
                <div class="mb-3">
                    <p><strong>Available:</strong> <?= number_format($product['quantity_available'], 2) ?> <?= htmlspecialchars($product['unit']) ?></p>
                    <p><strong>Harvest Date:</strong> <?= date('F j, Y', strtotime($product['harvest_date'])) ?></p>
                    <?php if (!empty($product['batch_number'])): ?>
                        <p><strong>Batch Number:</strong> <?= htmlspecialchars($product['batch_number']) ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($product['description'])): ?>
                    <div class="mb-3">
                        <h5>Description</h5>
                        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'BUYER'): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle"></i> You can add items to cart and browse. <a href="<?= $BASE_URL ?>buyer/login.php">Login</a> required for checkout.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Quantity (<?= htmlspecialchars($product['unit']) ?>)</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="0.01" step="0.01" max="<?= $product['quantity_available'] ?>" required>
                    </div>
                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
