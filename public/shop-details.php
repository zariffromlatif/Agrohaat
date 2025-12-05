<?php
$site_title = 'Shop Details | Agricko - Agriculture Farming PHP Template';
$special_css = 'innerpage';
$special_js = 'innerpage';
$page_banner_title = "Shop Details";
$breadcrumbs = [
    [
        'title' => 'Home',
        'url' => 'index.php',
    ],
    [
        'title' => 'Shop Details',
        'url' => null,
    ],
];
require_once 'layout/header.php';
include 'parts/common/page-title-banner.php';
?>

<!--====== Start Shop Details Page ======-->
<section class="shop-details-page pt-130 pb-95">
    <div class="container">
        <!-- Shop Details Wrapper -->
        <div class="shop-details-wrapper mb-30">
            <div class="row align-items-center ">
                <div class="col-xl-6">
                    <div class="product-gallery-slider mb-50" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="10">
                        <div class="product-thumb-slider">
                            <div class="product-img">
                                <img src="assets/images/innerpage/shop/product-sm1.jpg" alt="Product">
                            </div>
                            <div class="product-img">
                                <img src="assets/images/innerpage/shop/product-sm2.jpg" alt="Product">
                            </div>
                            <div class="product-img">
                                <img src="assets/images/innerpage/shop/product-sm3.jpg" alt="Product">
                            </div>
                            <div class="product-img">
                                <img src="assets/images/innerpage/shop/product-sm4.jpg" alt="Product">
                            </div>
                        </div>
                        <div class="product-big-slider">
                            <div class="product-img">
                                <a href="assets/images/innerpage/shop/product-big1.jpg" class="img-popup"><img src="assets/images/innerpage/shop/product-big1.jpg" alt="Product"></a>
                            </div>
                            <div class="product-img">
                                <a href="assets/images/innerpage/shop/product-big2.jpg" class="img-popup"><img src="assets/images/innerpage/shop/product-big2.jpg" alt="Product"></a>
                            </div>
                            <div class="product-img">
                                <a href="assets/images/innerpage/shop/product-big3.jpg" class="img-popup"><img src="assets/images/innerpage/shop/product-big3.jpg" alt="Product"></a>
                            </div>
                            <div class="product-img">
                                <a href="assets/images/innerpage/shop/product-big4.jpg" class="img-popup"><img src="assets/images/innerpage/shop/product-big4.jpg" alt="Product"></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="product-info mb-50" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="10">
                        <span class="sm-title">Organic Food <span class="review"><i class="fas fa-star"></i>(9 Review)</span></span>
                        <h4 class="title">Fresh Green Lemon</h4>
                        <p class="price"><span class="prev-price">$20.00</span>$10.99</p>
                        <p>Involves the creation and upkeep of outdoor spaces, to  the blending natural and man-made elements. It includes tasks like planting.</p>
                        <div class="product-cart-variation">
                            <ul>
                                <li>
                                    <div class="quantity-input">
                                        <button class="quantity-down"><i class="far fa-angle-left"></i></button>
                                        <input class="quantity" type="text" value="1" name="quantity">
                                        <button class="quantity-up"><i class="far fa-angle-right"></i></button>
                                    </div>
                                </li>
                                <li>
                                    <a href="cart.html" class="cart-btn">Add To cart</a>
                                </li>
                            </ul>
                        </div>
                        <div class="product-meta mb-25">
                            <ul>
                                <li class="category"><span>Category :</span><a href="#">T-Shirt</a>, <a href="#">Shoe</a>, <a href="#">Watch</a></li>
                                <li class="tag"><span>Tags :</span><a href="#">Shop</a>, <a href="#">Men</a>, <a href="#">Women</a></li>
                            </ul>
                        </div>
                        <div class="product-delivery">
                            <span class="delivery"><i class="flaticon-package"></i>2-day Delivery</span>
                            <span>- Speedy and reliable parcel delivery!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="shop-additional-info mb-30" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="10">
            <div class="row">
                <div class="col-lg-12">
                    <div class="sasly-tabs mb-30">
                        <ul class="nav nav-tabs">
                            <li>
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#description">Descriptions</button>
                            </li>
                            <li>
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#additional">Additional Information</button>
                            </li>
                            <li>
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews">Reviews</button>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="description">
                            <p>A fresh green lemon is a captivating sight with its bright, glossy skin, a symbol of its tangy freshness. The fruit feels firm, indicating its juiciness, and as you slice into it, the air fills with a sharp, citrusy fragrance that invigorates the senses. The pale green flesh inside is packed with zesty juice, offering a tart, refreshing flavor that’s perfect for cooking, baking, or adding a lively twist to beverages. It's a versatile ingredient that elevates any dish with its vibrant taste.</p>
                            <p>As you slice into it, the air fills with a sharp, invigorating citrus scent. The juicy, pale green flesh offers a tart, vibrant flavor that adds a crisp, refreshing edge to drinks, salads, or marinades, making it a versatile and essential ingredient in the kitchen.</p>
                            <ul class="check-list style-one">
                                <li><i class="flaticon-check"></i>Quique postures confidently while Hendrie subtly traces the hidden path.</li>
                                <li><i class="flaticon-check"></i>Clamorer echoes through the era as Morbi tempers each step.</li>
                                <li><i class="flaticon-check"></i>Condiment enhances the dish, while pharetra directs the path smoothly.</li>
                                <li><i class="flaticon-check"></i>Preussen critiques sharply as the bandit moves with vivacious energy.</li>
                            </ul>
                        </div>
                        <div class="tab-pane fade" id="additional">
                            <p>A fresh green lemon is a captivating sight with its bright, glossy skin, a symbol of its tangy freshness. The fruit feels firm, indicating its juiciness, and as you slice into it, the air fills with a sharp, citrusy fragrance that invigorates the senses. The pale green flesh inside is packed with zesty juice, offering a tart, refreshing flavor that’s perfect for cooking, baking, or adding a lively twist to beverages. It's a versatile ingredient that elevates any dish with its vibrant taste.</p>
                            <p>As you slice into it, the air fills with a sharp, invigorating citrus scent. The juicy, pale green flesh offers a tart, vibrant flavor that adds a crisp, refreshing edge to drinks, salads, or marinades, making it a versatile and essential ingredient in the kitchen.</p>
                            <ul class="check-list style-one">
                                <li><i class="flaticon-check"></i>Quique postures confidently while Hendrie subtly traces the hidden path.</li>
                                <li><i class="flaticon-check"></i>Clamorer echoes through the era as Morbi tempers each step.</li>
                                <li><i class="flaticon-check"></i>Condiment enhances the dish, while pharetra directs the path smoothly.</li>
                                <li><i class="flaticon-check"></i>Preussen critiques sharply as the bandit moves with vivacious energy.</li>
                            </ul>
                        </div>
                        <div class="tab-pane fade" id="reviews">
                            <p>A fresh green lemon is a captivating sight with its bright, glossy skin, a symbol of its tangy freshness. The fruit feels firm, indicating its juiciness, and as you slice into it, the air fills with a sharp, citrusy fragrance that invigorates the senses. The pale green flesh inside is packed with zesty juice, offering a tart, refreshing flavor that’s perfect for cooking, baking, or adding a lively twist to beverages. It's a versatile ingredient that elevates any dish with its vibrant taste.</p>
                            <p>As you slice into it, the air fills with a sharp, invigorating citrus scent. The juicy, pale green flesh offers a tart, vibrant flavor that adds a crisp, refreshing edge to drinks, salads, or marinades, making it a versatile and essential ingredient in the kitchen.</p>
                            <ul class="check-list style-one">
                                <li><i class="flaticon-check"></i>Quique postures confidently while Hendrie subtly traces the hidden path.</li>
                                <li><i class="flaticon-check"></i>Clamorer echoes through the era as Morbi tempers each step.</li>
                                <li><i class="flaticon-check"></i>Condiment enhances the dish, while pharetra directs the path smoothly.</li>
                                <li><i class="flaticon-check"></i>Preussen critiques sharply as the bandit moves with vivacious energy.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section><!--====== End Shop Details Page ======-->
<section class="related-product-sec pb-195">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title text-center mb-55" data-aos="fade-up" data-aos-duration="1000">
                    <span class="sub-title"><i class="flaticon-leaves"></i>Product</span>
                    <h2>Related Product</h2>
                </div>
            </div>
        </div>
        <div class="related-product-slider">
            <!--=== Agricko Product Item ===-->
            <div class="agricko-product-item mb-30" data-aos="fade-up" data-aos-duration="1400">
                <div class="product-thumbnail">
                    <img src="assets/images/home-two/products/product-img2.jpg" alt="Product Image">
                    <div class="new">New</div>
                    <div class="action-button">
                        <a href="#" class="icon-btn"><i class="far fa-shopping-basket"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-heart"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-eye"></i></a>
                    </div>
                </div>
                <div class="product-info">
                    <h4><a href="shop-details.html">Green Broccoli</a></h4>
                    <p class="price"><span class="prev-price">$28.00</span>$17.99</p>
                </div>
            </div>
            <!--=== Agricko Product Item ===-->
            <div class="agricko-product-item mb-30" data-aos="fade-up" data-aos-duration="1600">
                <div class="product-thumbnail">
                    <img src="assets/images/home-two/products/product-img3.jpg" alt="Product Image">
                    <div class="action-button">
                        <a href="#" class="icon-btn"><i class="far fa-shopping-basket"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-heart"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-eye"></i></a>
                    </div>
                </div>
                <div class="product-info">
                    <h4><a href="shop-details.html">Sou Red Cherry</a></h4>
                    <p class="price"><span class="prev-price">$30.00</span>$12.00</p>
                </div>
            </div>
            <!--=== Agricko Product Item ===-->
            <div class="agricko-product-item mb-30" data-aos="fade-up" data-aos-duration="1800">
                <div class="product-thumbnail">
                    <img src="assets/images/home-two/products/product-img4.jpg" alt="Product Image">
                    <div class="new">New</div>
                    <div class="action-button">
                        <a href="#" class="icon-btn"><i class="far fa-shopping-basket"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-heart"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-eye"></i></a>
                    </div>
                </div>
                <div class="product-info">
                    <h4><a href="shop-details.html">Fresh Orange</a></h4>
                    <p class="price"><span class="prev-price">$35.00</span>$20.99</p>
                </div>
            </div>
            <!--=== Agricko Product Item ===-->
            <div class="agricko-product-item mb-30" data-aos="fade-up" data-aos-duration="2000">
                <div class="product-thumbnail">
                    <img src="assets/images/home-two/products/product-img5.jpg" alt="Product Image">
                    <div class="action-button">
                        <a href="#" class="icon-btn"><i class="far fa-shopping-basket"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-heart"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-eye"></i></a>
                    </div>
                </div>
                <div class="product-info">
                    <h4><a href="shop-details.html">Green Apple</a></h4>
                    <p class="price"><span class="prev-price">$40.00</span>$14.00</p>
                </div>
            </div>
            <!--=== Agricko Product Item ===-->
            <div class="agricko-product-item mb-30" data-aos="fade-up" data-aos-duration="2200">
                <div class="product-thumbnail">
                    <img src="assets/images/home-two/products/product-img6.jpg" alt="Product Image">
                    <div class="new">New</div>
                    <div class="action-button">
                        <a href="#" class="icon-btn"><i class="far fa-shopping-basket"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-heart"></i></a>
                        <a href="#" class="icon-btn"><i class="far fa-eye"></i></a>
                    </div>
                </div>
                <div class="product-info">
                    <h4><a href="shop-details.html">Green Apple</a></h4>
                    <p class="price"><span class="prev-price">$32.00</span>$12.99</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
require_once 'layout/footer.php';