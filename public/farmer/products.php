<?php
require_once '../../config/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

require_once '../../controllers/ProductController.php';
$controller = new ProductController($pdo);
$products   = $controller->getProductsForFarmer($_SESSION['user_id']);

$site_title = "My Products | AgroHaat";
include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">My Products</h2>
        <div>
            <a href="<?= $BASE_URL ?>farmer/product_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Product
            </a>
            <button onclick="refreshProducts()" class="btn btn-outline-secondary" title="Refresh from API">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <strong>Success!</strong> Product created successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <strong>Success!</strong> Product updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <strong>Success!</strong> Product deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div id="products-container">
        <?php if (empty($products)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Products Yet</h4>
                    <p class="text-muted">Start by adding your first product!</p>
                    <a href="<?= $BASE_URL ?>farmer/product_add.php" class="btn btn-primary">Add Product</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                    <tr>
                        <th>Title</th>
                        <th>Quantity</th>
                        <th>Price / Unit</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody id="products-table-body">
                    <?php foreach ($products as $p): ?>
                        <tr data-product-id="<?= $p['product_id'] ?>">
                            <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                            <td><?= htmlspecialchars($p['quantity_available']) . ' ' . htmlspecialchars($p['unit']) ?></td>
                            <td>৳<?= number_format((float)$p['price_per_unit'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $p['quality_grade'] === 'A' ? 'success' : ($p['quality_grade'] === 'B' ? 'warning' : 'secondary') ?>">
                                    <?= htmlspecialchars($p['quality_grade']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $p['status'] === 'ACTIVE' ? 'success' : ($p['status'] === 'SOLD_OUT' ? 'danger' : 'secondary') ?>">
                                    <?= htmlspecialchars($p['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($p['image_url'])): ?>
                                    <img src="<?= $BASE_URL . htmlspecialchars($p['image_url']) ?>" 
                                         width="60" height="60" 
                                         class="rounded" 
                                         style="object-fit: cover;"
                                         alt="<?= htmlspecialchars($p['title']) ?>">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-image"></i> No image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="product_edit.php?id=<?= $p['product_id'] ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Edit Product">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button onclick="deleteProductAPI(<?= $p['product_id'] ?>, '<?= htmlspecialchars($p['title']) ?>')" 
                                            class="btn btn-sm btn-danger" 
                                            title="Delete Product">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Refresh products from API
function refreshProducts() {
    const btn = event.target.closest('button');
    const icon = btn.querySelector('i');
    icon.classList.add('fa-spin');
    
    fetch('<?= $BASE_URL ?>api/farmer/products.php')
        .then(response => response.json())
        .then(data => {
            icon.classList.remove('fa-spin');
            if (data.success && data.data.products) {
                location.reload(); // Reload page to show updated data
            } else {
                alert('Error refreshing products: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            icon.classList.remove('fa-spin');
            console.error('Error:', error);
            alert('Error refreshing products. Please try again.');
        });
}

// Delete product via API
function deleteProductAPI(productId, productTitle) {
    if (!confirm(`Are you sure you want to delete "${productTitle}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    fetch('<?= $BASE_URL ?>api/farmer/products.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove row from table
            const row = document.querySelector(`tr[data-product-id="${productId}"]`);
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    // Check if table is empty
                    const tbody = document.getElementById('products-table-body');
                    if (tbody && tbody.children.length === 0) {
                        location.reload();
                    }
                }, 300);
            } else {
                location.reload();
            }
            
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <strong>Success!</strong> Product deleted successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container').insertBefore(alert, document.querySelector('.container').firstChild);
        } else {
            alert('Error deleting product: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting product. Please try again.');
    });
}
</script>

<?php include '../../includes/footer.php'; ?>
