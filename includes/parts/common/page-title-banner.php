<!--====== Start Page Banner ======-->
<section class="page-banner bg_cover" style="background-image: url(assets/images/innerpage/bg/page-banner.jpg);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Page Content -->
                <div class="page-content text-center">
                    <h1><?php echo isset($page_banner_title) ? $page_banner_title : ""; ?></h1>
					<?php if (!empty($breadcrumbs)): ?>
                    <ul>
                       <?php foreach ($breadcrumbs as $crumb): ?>
                            <li>
                                <?php if (!empty($crumb['url'])): ?>
                                    <a href="<?php echo htmlspecialchars($crumb['url']); ?>">
                                        <?php echo htmlspecialchars($crumb['title']); ?>
                                    </a>
                                <?php else: ?>
                                        <?php echo htmlspecialchars($crumb['title']); ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
					<?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section><!--====== End Page Banner ======-->