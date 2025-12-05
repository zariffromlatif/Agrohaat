<?php
require_once '../config/config.php';
require_once '../controllers/BuyerController.php';

$controller = new BuyerController($pdo);

// Get search parameters
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$district = isset($_GET['district']) ? trim($_GET['district']) : null;
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : null;
$quality_grade = isset($_GET['quality']) && $_GET['quality'] !== '' ? trim($_GET['quality']) : null;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get products and total count
$products = $controller->searchProducts($search_term, $category_id, $district, $min_price, $max_price, $quality_grade, $limit, $offset);
$total_products = $controller->getProductCount($search_term, $category_id, $district, $min_price, $max_price, $quality_grade);
$total_pages = ceil($total_products / $limit);

// Get unique districts for filter
$all_products = $controller->searchProducts('', null, null, null, null, null, 1000, 0);
$districts = array_unique(array_column($all_products, 'district'));
sort($districts);

$site_title  = "Marketplace | AgroHaat";
$special_css = "innerpage";
include '../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Fresh Products from Farmers</h2>

        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <!-- Search -->
                            <div class="mb-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search_term) ?>" placeholder="Product name...">
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control">
                                    <option value="">All Categories</option>
                                    <!-- Categories would come from categories table -->
                                </select>
                            </div>

                            <!-- District -->
                            <div class="mb-3">
                                <label class="form-label">District</label>
                                <select name="district" class="form-control">
                                    <option value="">All Districts</option>
                                    <?php foreach ($districts as $d): ?>
                                        <option value="<?= htmlspecialchars($d) ?>" <?= $district === $d ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($d) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Quality Grade -->
                            <div class="mb-3">
                                <label class="form-label">Quality Grade</label>
                                <select name="quality" class="form-control">
                                    <option value="">All Grades</option>
                                    <option value="EXPORT_QUALITY" <?= $quality_grade === 'EXPORT_QUALITY' ? 'selected' : '' ?>>Export Quality</option>
                                    <option value="A" <?= $quality_grade === 'A' ? 'selected' : '' ?>>Grade A</option>
                                    <option value="B" <?= $quality_grade === 'B' ? 'selected' : '' ?>>Grade B</option>
                                    <option value="C" <?= $quality_grade === 'C' ? 'selected' : '' ?>>Grade C</option>
                                </select>
                            </div>

                            <!-- Price Range -->
                            <div class="mb-3">
                                <label class="form-label">Price Range (৳)</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?= $min_price ?>" step="0.01">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?= $max_price ?>" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                            <a href="<?= $BASE_URL ?>shop.php" class="btn btn-secondary w-100 mt-2">Clear All</a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-md-9">
                <?php if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'BUYER'): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle"></i> Browse freely! <a href="<?= $BASE_URL ?>buyer/login.php">Login</a> or <a href="<?= $BASE_URL ?>buyer/register.php">Register</a> only required for checkout.
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <p>Showing <?= min($offset + 1, $total_products) ?>-<?= min($offset + $limit, $total_products) ?> of <?= $total_products ?> products</p>
                </div>

                <?php if (empty($products)): ?>
                    <div class="alert alert-info">
                        <h4>No products found</h4>
                        <p>Try adjusting your search criteria or <a href="<?= $BASE_URL ?>shop.php">view all products</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?= $BASE_URL . htmlspecialchars($product['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['title']) ?>" style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($product['title']) ?></h5>
                                        <p class="card-text text-muted small">By: <?= htmlspecialchars($product['farmer_name']) ?></p>
                                        <p class="card-text">
                                            <strong>৳<?= number_format($product['price_per_unit'], 2) ?></strong> per <?= htmlspecialchars($product['unit']) ?>
                                        </p>
                                        <p class="card-text small">
                                            <span class="badge bg-info"><?= htmlspecialchars($product['quality_grade']) ?></span>
                                            <span class="text-muted"><?= htmlspecialchars($product['district']) ?></span>
                                        </p>
                                        <p class="card-text small">
                                            Available: <?= number_format($product['quantity_available'], 2) ?> <?= htmlspecialchars($product['unit']) ?>
                                        </p>
                                    </div>
                                    <div class="card-footer">
                                        <a href="<?= $BASE_URL ?>product-details.php?id=<?= $product['product_id'] ?>" class="btn btn-primary btn-sm w-100">View Details</a>
                                        <a href="<?= $BASE_URL ?>trace.php?tid=<?= htmlspecialchars($product['trace_id']) ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2" target="_blank">
                                            <i class="fas fa-qrcode"></i> QR Trace
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
