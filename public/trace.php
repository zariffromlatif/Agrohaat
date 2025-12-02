<?php
require_once '../config/config.php';
require_once '../models/Product.php';

$traceId = isset($_GET['tid']) ? $_GET['tid'] : null;

if (!$traceId) {
    http_response_code(400);
    echo "Trace ID missing.";
    exit;
}

$productModel = new Product($pdo);
$product      = $productModel->findByTraceId($traceId);

if (!$product) {
    http_response_code(404);
    echo "No product found for this trace ID.";
    exit;
}

// Fetch limited farmer info
$stmt = $pdo->prepare("SELECT full_name, district, upazila FROM users WHERE user_id = :uid");
$stmt->execute([':uid' => $product['farmer_id']]);
$farmer = $stmt->fetch();

$site_title  = "Trace Product | AgroHaat";
$special_css = "innerpage";
include '../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Product Traceability Report</h2>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card p-4">
                    <h4>Product Details</h4>
                    <p><strong>Name:</strong> <?= htmlspecialchars($product['title']) ?></p>
                    <p><strong>Category ID:</strong> <?= htmlspecialchars($product['category_id']) ?></p>
                    <p><strong>Quality Grade:</strong> <?= htmlspecialchars($product['quality_grade']) ?></p>
                    <p><strong>Harvest Date:</strong> <?= htmlspecialchars($product['harvest_date']) ?></p>
                    <p><strong>Batch Number:</strong> <?= htmlspecialchars($product['batch_number']) ?></p>
                    <p><strong>Trace ID:</strong> <?= htmlspecialchars($product['trace_id']) ?></p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4">
                    <h4>Origin & Farmer</h4>
                    <?php if ($farmer): ?>
                        <p><strong>Farmer:</strong> <?= htmlspecialchars($farmer['full_name']) ?></p>
                        <p><strong>Origin:</strong>
                            <?= htmlspecialchars($farmer['district'] ?? '') ?>,
                            <?= htmlspecialchars($farmer['upazila'] ?? '') ?>
                        </p>
                    <?php else: ?>
                        <p>Farmer information unavailable.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4 text-center">
                    <h4>QR Code</h4>
                    <?php if (!empty($product['qr_code_url'])): ?>
                        <img src="<?= htmlspecialchars($product['qr_code_url']) ?>" alt="QR Code">
                    <?php else: ?>
                        <p>QR code not generated.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>


