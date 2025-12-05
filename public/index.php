<?php
require_once '../config/config.php';

$site_title = "AgroHaat - Direct Farmer-to-Market Linkage Platform";
$special_css = "home-one";
include '../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section style-one">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1>Welcome to AgroHaat</h1>
                    <p>Direct Farmer-to-Market Linkage Platform</p>
                    <p>Connect farmers directly with buyers, ensuring fair prices and quality products.</p>
                    <div class="hero-btn">
                        <a href="<?= $BASE_URL ?>shop.php" class="theme-btn style-one">Browse Marketplace</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section pt-80 pb-80">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="feature-item text-center">
                    <h3>For Farmers</h3>
                    <p>List your products, manage orders, and connect directly with buyers.</p>
                    <a href="<?= $BASE_URL ?>farmer/login.php" class="btn btn-primary">Farmer Login</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item text-center">
                    <h3>For Buyers</h3>
                    <p>Browse quality products, place orders, and track your purchases.</p>
                    <a href="<?= $BASE_URL ?>buyer/login.php" class="btn btn-primary">Buyer Login</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item text-center">
                    <h3>Marketplace</h3>
                    <p>Explore fresh products directly from farmers with QR traceability.</p>
                    <a href="<?= $BASE_URL ?>shop.php" class="btn btn-primary">View Products</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
