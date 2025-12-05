<?php
// Load config (DB, BASE_URL, session, etc.)
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="zxx">
    <head>
        <!--====== Required meta tags ======-->
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="description" content="Foods, Restaurant, Coffee">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!--====== Title ======-->
        <title>
            <?php echo isset($site_title) ? $site_title : "Agricko - Agriculture Farming PHP Template"; ?>
        </title>

        <!--====== Favicon Icon ======-->
        <link rel="shortcut icon" href="<?= $BASE_URL ?>assets/images/favicon.png" type="image/png">

        <!--====== Google Fonts ======-->
        <link href="https://fonts.googleapis.com/css2?family=Handlee&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

        <!--====== Flaticon css ======-->
        <link rel="stylesheet" href="<?= $BASE_URL ?>assets/fonts/flaticon/flaticon_agricko.css">

        <!--====== FontAwesome css ======-->
        <link rel="stylesheet" href="<?= $BASE_URL ?>assets/fonts/fontawesome/css/all.min.css">

        <!--====== Bootstrap css ======-->
        <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/plugins/bootstrap.min.css">

        <!--====== Slick css ======-->
        <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/plugins/slick.css">

        <!--====== Magnific-popup css ======-->
        <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/plugins/magnific-popup.css">

        <!--====== Nice Select CSS ======-->
        <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/plugins/nice-select.css">

        <!--====== AOS Animation ======-->
        <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/plugins/aos.css">

        <!--====== Spacing CSS  ======-->
        <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/spacings.css">

        <!--====== Common Style css ======-->
        <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/common-style.css">

        <?php if (isset($special_css)) : ?>
            <!--====== Page-specific Style css ======-->
            <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/pages/<?= htmlspecialchars($special_css) ?>.css">
        <?php endif; ?>
    </head>
    <body>
        <!--====== Start Loader Area ======-->
        <div class="preloader">
            <div class="loader"></div>
        </div>
        <!--====== End Loader Area ======-->

        <!--====== Search Form ======-->
        <div class="modal fade search-modal" id="search-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form>
                        <div class="form-group">
                            <input type="search" class="form_control" placeholder="Search here" name="search">
                            <label><i class="fa fa-search"></i></label>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--====== Search Form ======-->

        <!--====== Start Overlay ======-->
        <div class="offcanvas__overlay"></div>

        <!--======  Start Header Area  ======-->
        <header class="header-area header-one">
            <div class="container-fluid">
                <div class="header-navigation">
                    <div class="nav-inner-menu">
                        <div class="primary-menu">
                            <!--====  Site Branding  ===-->
                            <div class="site-branding">
                                <a href="<?= $BASE_URL ?>index.php" class="brand-logo">
                                    <img src="<?= $BASE_URL ?>assets/images/home-one/logo/logo-main.png?v=<?= time() ?>" alt="Brand Logo">
                                </a>
                            </div>

                            <!--=== Theme Main Menu ===-->
                            <div class="theme-nav-menu">
                                <!-- Theme Menu Top Mobile -->
                                <div class="theme-menu-top d-flex justify-content-between d-block d-xl-none mb-4">
                                    <div class="site-branding text-center">
                                        <a href="<?= $BASE_URL ?>index.php" class="brand-logo">
                                            <img src="<?= $BASE_URL ?>assets/images/home-one/logo/logo-main.png?v=<?= time() ?>" alt="Brand Logo">
                                        </a>
                                    </div>
                                </div>

                                <!--=== Main Menu ===-->
                                <?php include_once __DIR__ . '/main-menu.php'; ?>

                                <!--=== Theme Nav Button (mobile) ===-->
                                <div class="theme-nav-button mt-3 d-block d-lg-none">
                                    <a href="<?= $BASE_URL ?>farmer/register.php" class="theme-btn style-one">Join as Farmer</a>
                                </div>

                                <!--=== Theme Menu Bottom (mobile) ===-->
                                <div class="theme-menu-bottom mt-5 d-block d-xl-none">
                                    <h5>Follow Us</h5>
                                    <ul class="social-link">
                                        <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                                        <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                                        <li><a href="#"><i class="fab fa-linkedin-in"></i></a></li>
                                        <li><a href="#"><i class="fab fa-youtube"></i></a></li>
                                    </ul>
                                </div>
                            </div>

                            <!--=== Header Nav Right ===-->
                            <div class="nav-right-item">
                                <div class="nav-action">
                                    <div class="search-btn action-btn" data-bs-toggle="modal" data-bs-target="#search-modal">
                                        <i class="far fa-search"></i>
                                    </div>
                                    <a href="<?= $BASE_URL ?>cart.php" class="shopping-btn action-btn">
                                        <i class="far fa-shopping-bag"></i>
                                    </a>
                                </div>
                                <div class="nav-button d-none d-md-block">
                                    <a href="<?= $BASE_URL ?>farmer/register.php" class="theme-btn style-one">Join as Farmer</a>
                                </div>
                                <div class="navbar-toggler">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!--======  End Header Area  ======-->

        <main>
