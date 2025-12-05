<?php
// footer.php
// closing <main>, footer area, and JS includes
?>

        </main>

        <!--====== Start Footer Area ======-->
        <footer class="footer-area">
            <div class="container">
                <div class="footer-widget pt-80 pb-40">
                    <div class="row">
                        <div class="col-lg-4 col-md-6">
                            <div class="widget about-widget mb-40">
                                <a href="<?= $BASE_URL ?>index.php" class="footer-logo">
                                    <img src="<?= $BASE_URL ?>assets/images/home-one/logo/logo-main.png?v=<?= time() ?>" alt="AgroHaat">
                                </a>
                                <p>AgroHaat connects farmers directly with buyers and logistics – a farmer-first marketplace.</p>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="widget nav-widget mb-40">
                                <h4 class="widget-title">Quick Links</h4>
                                <ul class="widget-nav">
                                    <li><a href="<?= $BASE_URL ?>index.php">Home</a></li>
                                    <li><a href="<?= $BASE_URL ?>about.php">About</a></li>
                                    <li><a href="<?= $BASE_URL ?>shop.php">Marketplace</a></li>
                                    <li><a href="<?= $BASE_URL ?>contact.php">Contact</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="widget contact-widget mb-40">
                                <h4 class="widget-title">Contact</h4>
                                <ul class="contact-info">
                                    <li>BRAC University, Dhaka</li>
                                    <li>Email: info@agrohaat.local</li>
                                    <li>Phone: +880 1XXXXXXXXX</li>
                                </ul>
                                <ul class="social-link">
                                    <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                                    <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                                    <li><a href="#"><i class="fab fa-linkedin-in"></i></a></li>
                                    <li><a href="#"><i class="fab fa-youtube"></i></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="footer-bottom d-flex justify-content-between align-items-center">
                    <p class="mb-0">© <?= date('Y') ?> AgroHaat. All rights reserved.</p>
                    <p class="mb-0">CSE470 Group Project</p>
                </div>
            </div>
        </footer>
        <!--====== End Footer Area ======-->

        <!--====== JS Files (adjust names to match /public/assets/js) ======-->
        <script src="<?= $BASE_URL ?>assets/js/plugins/jquery-3.7.1.min.js"></script>
        <script src="<?= $BASE_URL ?>assets/js/plugins/bootstrap.min.js"></script>
        <script src="<?= $BASE_URL ?>assets/js/plugins/slick.min.js"></script>
        <script src="<?= $BASE_URL ?>assets/js/plugins/jquery.magnific-popup.min.js"></script>
        <script src="<?= $BASE_URL ?>assets/js/plugins/jquery.nice-select.min.js"></script>
        <script src="<?= $BASE_URL ?>assets/js/plugins/aos.js"></script>
        <script src="<?= $BASE_URL ?>assets/js/common.js"></script>

    </body>
</html>
