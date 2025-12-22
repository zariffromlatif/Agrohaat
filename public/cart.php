<?php
require_once '../config/config.php';
require_once '../controllers/BuyerController.php';

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$controller = new BuyerController($pdo);
$cart_items = [];
$total = 0;

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = floatval($_POST['quantity']);
    
    $product = $controller->getProduct($product_id);
    if ($product && $quantity > 0) {
        // Check if already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'quantity' => $quantity
            ];
        }
        header("Location: cart.php?added=1");
        exit;
    }
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($remove_id) {
        return $item['product_id'] !== $remove_id;
    });
    $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index
    header("Location: cart.php");
    exit;
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = floatval($quantity);
        
        if ($quantity <= 0) {
            // Remove item
            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
                return $item['product_id'] !== $product_id;
            });
        } else {
            // Update quantity
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] === $product_id) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart.php");
    exit;
}

// Build cart items with product details
foreach ($_SESSION['cart'] as $cart_item) {
    $product = $controller->getProduct($cart_item['product_id']);
    if ($product) {
        $subtotal = $product['price_per_unit'] * $cart_item['quantity'];
        $total += $subtotal;
        $cart_items[] = [
            'product' => $product,
            'quantity' => $cart_item['quantity'],
            'subtotal' => $subtotal
        ];
    }
}

$site_title  = "Shopping Cart | AgroHaat";
$special_css = "innerpage";
include '../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Shopping Cart</h2>

        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">Product added to cart!</div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                <h4>Your cart is empty</h4>
                <p><a href="<?= $BASE_URL ?>shop.php">Browse products</a> to add items to your cart.</p>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['product']['title']) ?></strong><br>
                                        <small class="text-muted">By: <?= htmlspecialchars($item['product']['farmer_name']) ?></small>
                                    </td>
                                    <td>৳<span class="unit-price" data-price="<?= $item['product']['price_per_unit'] ?>"><?= number_format($item['product']['price_per_unit'], 2) ?></span> / <?= htmlspecialchars($item['product']['unit']) ?></td>
                                    <td>
                                        <input type="number" 
                                               name="quantities[<?= $item['product']['product_id'] ?>]" 
                                               value="<?= $item['quantity'] ?>" 
                                               min="0.01" 
                                               step="0.01" 
                                               class="form-control quantity-input" 
                                               data-product-id="<?= $item['product']['product_id'] ?>"
                                               data-unit-price="<?= $item['product']['price_per_unit'] ?>"
                                               style="width: 100px;">
                                    </td>
                                    <td>৳<span class="subtotal-display" data-product-id="<?= $item['product']['product_id'] ?>"><?= number_format($item['subtotal'], 2) ?></span></td>
                                    <td>
                                        <a href="?remove=<?= $item['product']['product_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove from cart?')">Remove</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Total</th>
                                <th>৳<span id="cart-total"><?= number_format($total, 2) ?></span></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3">
                    <button type="submit" name="update_cart" class="btn btn-secondary">Update Cart</button>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'BUYER'): ?>
                        <a href="<?= $BASE_URL ?>checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                    <?php else: ?>
                        <a href="<?= $BASE_URL ?>buyer/login.php" class="btn btn-primary">Login to Checkout</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    
    function updateSubtotal(input) {
        const productId = input.getAttribute('data-product-id');
        const unitPrice = parseFloat(input.getAttribute('data-unit-price'));
        const quantity = parseFloat(input.value) || 0;
        
        const subtotal = unitPrice * quantity;
        const subtotalDisplay = document.querySelector(`.subtotal-display[data-product-id="${productId}"]`);
        
        if (subtotalDisplay) {
            subtotalDisplay.textContent = subtotal.toFixed(2);
        }
        
        updateTotal();
    }
    
    function updateTotal() {
        const subtotals = document.querySelectorAll('.subtotal-display');
        let total = 0;
        
        subtotals.forEach(function(subtotalEl) {
            total += parseFloat(subtotalEl.textContent) || 0;
        });
        
        const totalDisplay = document.getElementById('cart-total');
        if (totalDisplay) {
            totalDisplay.textContent = total.toFixed(2);
        }
    }
    
    // Add event listeners to all quantity inputs
    quantityInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            updateSubtotal(this);
        });
        
        input.addEventListener('change', function() {
            updateSubtotal(this);
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
