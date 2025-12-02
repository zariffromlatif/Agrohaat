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
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= $BASE_URL . htmlspecialchars($product['image_url']) ?>" class="img-fluid" alt="<?= htmlspecialchars($product['title']) ?>">
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                        <i class="fas fa-image fa-5x text-muted"></i>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($product['qr_code_url'])): ?>
                    <div class="mt-3 text-center">
                        <a href="<?= $BASE_URL ?>trace.php?tid=<?= htmlspecialchars($product['trace_id']) ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-qrcode"></i> View QR Traceability
                        </a>
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
