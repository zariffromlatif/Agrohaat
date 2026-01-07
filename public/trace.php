<?php
require_once '../config/config.php';
require_once '../models/Product.php';

$productModel = new Product($pdo);
$product = null;
$trace_id = isset($_GET['trace_id']) ? trim($_GET['trace_id']) : '';

if (!empty($trace_id)) {
    $product = $productModel->findByTraceId($trace_id);
}

$site_title = "Product Traceability | AgroHaat";
$special_css = "innerpage";
include '../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="mb-4">Product Traceability</h2>
                
                <?php if (empty($trace_id)): ?>
                    <!-- QR Code Scanner Interface -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Scan QR Code</h5>
                            <p class="text-muted">Enter trace ID manually or scan QR code</p>
                            
                            <form method="GET" action="trace.php" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="trace_id" class="form-control" 
                                           placeholder="Enter Trace ID (e.g., TRACE_1234567890_abc123)" 
                                           required>
                                    <button class="btn btn-primary" type="submit">View Trace</button>
                                </div>
                            </form>
                            
                            <div class="alert alert-info">
                                <strong>How to use:</strong>
                                <ul class="mb-0">
                                    <li>Scan the QR code on the product packaging</li>
                                    <li>Or enter the Trace ID manually</li>
                                    <li>View complete product origin and harvest information</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($product): ?>
                    <!-- Product Traceability Information -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">✅ Verified Product Traceability</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?= htmlspecialchars($BASE_URL . $product['image_url']) ?>" 
                                             alt="<?= htmlspecialchars($product['title']) ?>" 
                                             class="img-fluid rounded">
                                    <?php else: ?>
                                        <div class="bg-light p-5 text-center rounded">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                            <p class="text-muted mt-2">No image available</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h4><?= htmlspecialchars($product['title']) ?></h4>
                                    <p class="text-muted"><?= htmlspecialchars($product['description'] ?? 'No description') ?></p>
                                    
                                    <div class="mb-3">
                                        <strong>Quality Grade:</strong>
                                        <span class="badge bg-primary"><?= htmlspecialchars($product['quality_grade']) ?></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Price:</strong> 
                                        ৳<?= number_format($product['price_per_unit'], 2) ?> per <?= htmlspecialchars($product['unit']) ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Available Quantity:</strong> 
                                        <?= number_format($product['quantity_available'], 2) ?> <?= htmlspecialchars($product['unit']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">📋 Traceability Information</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Trace ID:</strong><br>
                                    <code><?= htmlspecialchars($product['trace_id']) ?></code>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong>Batch Number:</strong><br>
                                    <?= htmlspecialchars($product['batch_number'] ?? 'N/A') ?>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong>Harvest Date:</strong><br>
                                    <?= date('F d, Y', strtotime($product['harvest_date'])) ?>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong>Product Created:</strong><br>
                                    <?= date('F d, Y', strtotime($product['created_at'])) ?>
                                </div>
                            </div>
                            
                            <?php
                            // Get farmer information
                            $stmt = $pdo->prepare("SELECT full_name, district, upazila, phone_number, email 
                                                   FROM users WHERE user_id = :fid");
                            $stmt->execute([':fid' => $product['farmer_id']]);
                            $farmer = $stmt->fetch();
                            ?>
                            
                            <?php if ($farmer): ?>
                                <hr>
                                <h5 class="mb-3">👨‍🌾 Farmer Information</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <strong>Farmer Name:</strong><br>
                                        <?= htmlspecialchars($farmer['full_name']) ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <strong>Location:</strong><br>
                                        <?= htmlspecialchars($farmer['district'] ?? 'N/A') ?><?= $farmer['upazila'] ? ', ' . htmlspecialchars($farmer['upazila']) : '' ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($product['qr_code_url']): ?>
                                <hr>
                                <div class="text-center">
                                    <h5 class="mb-3">QR Code</h5>
                                    <img src="<?= htmlspecialchars($product['qr_code_url']) ?>" 
                                         alt="QR Code" 
                                         class="img-thumbnail" 
                                         style="max-width: 250px;">
                                    <p class="text-muted mt-2">Scan this QR code to verify product authenticity</p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <a href="shop.php" class="btn btn-secondary">Back to Shop</a>
                                <a href="product-details.php?id=<?= $product['product_id'] ?>" class="btn btn-primary">View Product Details</a>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($trace_id)): ?>
                    <!-- Trace ID not found -->
                    <div class="alert alert-warning">
                        <h5>⚠️ Trace ID Not Found</h5>
                        <p>The trace ID <code><?= htmlspecialchars($trace_id) ?></code> could not be found in our system.</p>
                        <p>Please verify the trace ID and try again.</p>
                        <a href="trace.php" class="btn btn-primary">Try Another Trace ID</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
