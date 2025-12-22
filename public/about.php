<?php
require_once '../config/config.php';

$site_title = "About Us - AgroHaat | Direct Farmer-to-Market Linkage Platform";
$special_css = "innerpage";
include '../includes/header.php';
?>

<!-- Page Header -->
<section class="page-header pt-80 pb-80" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <h1 class="text-white mb-3">About AgroHaat</h1>
                <p class="text-white-50 mb-0">Connecting Farmers Directly with Buyers</p>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="pt-80 pb-80">
    <div class="container">
        <div class="row align-items-center mb-80">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="about-image">
                    <img src="<?= $BASE_URL ?>assets/images/home-one/about/aboutimg.png?v=<?= time() ?>" alt="AgroHaat Logo" class="img-fluid rounded shadow">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-title mb-4">
                    <span class="sub-title text-success"><i class="flaticon-leaves"></i> Our Story</span>
                    <h2>Direct Farmer-to-Market Linkage Platform</h2>
                </div>
                <p class="mb-4">
                    AgroHaat is a revolutionary digital marketplace designed to bridge the gap between farmers and buyers, 
                    eliminating middlemen and ensuring fair prices for quality agricultural products. Our platform empowers 
                    farmers to showcase their produce directly to buyers while providing buyers with access to fresh, 
                    traceable products straight from the source.
                </p>
                <p class="mb-4">
                    Founded with the vision of creating a farmer-first marketplace, AgroHaat leverages technology to 
                    transform agricultural trade, making it more transparent, efficient, and profitable for all stakeholders.
                </p>
            </div>
        </div>

        <!-- Mission & Vision -->
        <div class="row mb-80">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                <i class="flaticon-target fs-2"></i>
                            </div>
                            <h3 class="mb-0">Our Mission</h3>
                        </div>
                        <p class="mb-0">
                            To empower farmers by providing direct market access, ensuring fair prices, and eliminating 
                            intermediaries. We strive to create a sustainable agricultural ecosystem where farmers thrive 
                            and buyers receive quality products with complete transparency.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                <i class="flaticon-eye fs-2"></i>
                            </div>
                            <h3 class="mb-0">Our Vision</h3>
                        </div>
                        <p class="mb-0">
                            To become the leading digital platform for agricultural trade in Bangladesh, transforming 
                            how farmers connect with buyers. We envision a future where every farmer has direct access 
                            to markets and every buyer can trace their food from farm to table.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Features -->
        <div class="row mb-80">
            <div class="col-lg-12 text-center mb-5">
                <div class="section-title">
                    <span class="sub-title text-success"><i class="flaticon-leaves"></i> Why Choose AgroHaat</span>
                    <h2>Key Features & Benefits</h2>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-box text-center p-4 h-100">
                    <div class="icon-box bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="flaticon-farmer text-success fs-1"></i>
                    </div>
                    <h4>For Farmers</h4>
                    <p class="mb-0">
                        List your products, set your prices, manage orders, and connect directly with buyers. 
                        No middlemen, no commission cuts - just fair trade.
                    </p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-box text-center p-4 h-100">
                    <div class="icon-box bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="flaticon-shopping-cart text-success fs-1"></i>
                    </div>
                    <h4>For Buyers</h4>
                    <p class="mb-0">
                        Browse quality products directly from farmers, place orders with ease, and track your 
                        purchases with complete transparency.
                    </p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-box text-center p-4 h-100">
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-box text-center p-4 h-100">
                    <div class="icon-box bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="flaticon-truck text-success fs-1"></i>
                    </div>
                    <h4>Logistics Support</h4>
                    <p class="mb-0">
                        Integrated transporter marketplace connects farmers and buyers with reliable delivery 
                        services for seamless product transportation.
                    </p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-box text-center p-4 h-100">
                    <div class="icon-box bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="flaticon-shield text-success fs-1"></i>
                    </div>
                    <h4>Secure Transactions</h4>
                    <p class="mb-0">
                        Safe and secure payment processing with multiple payment options. Your transactions 
                        are protected with industry-standard security measures.
                    </p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-box text-center p-4 h-100">
                    <div class="icon-box bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="flaticon-chat text-success fs-1"></i>
                    </div>
                    <h4>Direct Communication</h4>
                    <p class="mb-0">
                        Built-in messaging system allows farmers and buyers to communicate directly, 
                        negotiate terms, and build lasting business relationships.
                    </p>
                </div>
            </div>
        </div>

        <!-- Values -->
        <div class="row mb-80">
            <div class="col-lg-12 text-center mb-5">
                <div class="section-title">
                    <span class="sub-title text-success"><i class="flaticon-leaves"></i> Our Values</span>
                    <h2>What We Stand For</h2>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="value-item text-center">
                    <div class="value-icon mb-3">
                        <i class="flaticon-handshake text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Transparency</h5>
                    <p class="mb-0">Complete visibility in every transaction</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="value-item text-center">
                    <div class="value-icon mb-3">
                        <i class="flaticon-medal text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Quality</h5>
                    <p class="mb-0">Ensuring premium products and services</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="value-item text-center">
                    <div class="value-icon mb-3">
                        <i class="flaticon-heart text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Fairness</h5>
                    <p class="mb-0">Fair prices for farmers and buyers</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="value-item text-center">
                    <div class="value-icon mb-3">
                        <i class="flaticon-growth text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Innovation</h5>
                    <p class="mb-0">Leveraging technology for better outcomes</p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="row">
            <div class="col-lg-12">
                <div class="cta-box bg-light rounded p-5 text-center">
                    <h3 class="mb-3">Join the AgroHaat Community</h3>
                    <p class="mb-4">Whether you're a farmer looking to expand your market reach or a buyer seeking quality products, AgroHaat is here for you.</p>
                    <div class="cta-buttons">
                        <a href="<?= $BASE_URL ?>farmer/register.php" class="theme-btn style-one me-3 mb-2">Join as Farmer</a>
                        <a href="<?= $BASE_URL ?>buyer/register.php" class="theme-btn style-two me-3 mb-2">Join as Buyer</a>
                        <a href="<?= $BASE_URL ?>shop.php" class="theme-btn style-one mb-2">Browse Marketplace</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

