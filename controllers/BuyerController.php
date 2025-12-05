<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';

class BuyerController {
    private $productModel;
    private $orderModel;

    public function __construct($pdo) {
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
    }

    /**
     * Search products with filters
     */
    public function searchProducts($search_term = '', $category_id = null, $district = null, $min_price = null, $max_price = null, $quality_grade = null, $limit = 12, $offset = 0) {
        return $this->productModel->search($search_term, $category_id, $district, $min_price, $max_price, $quality_grade, $limit, $offset);
    }

    /**
     * Get product count for pagination
     */
    public function getProductCount($search_term = '', $category_id = null, $district = null, $min_price = null, $max_price = null, $quality_grade = null) {
        return $this->productModel->getSearchCount($search_term, $category_id, $district, $min_price, $max_price, $quality_grade);
    }

    /**
     * Get product by ID
     */
    public function getProduct($product_id) {
        return $this->productModel->getById($product_id);
    }

    /**
     * Get buyer orders
     */
    public function getBuyerOrders($buyer_id) {
        return $this->orderModel->getForBuyer($buyer_id);
    }

    /**
     * Get order details
     */
    public function getOrderDetails($order_id, $buyer_id) {
        return $this->orderModel->getByIdForBuyer($order_id, $buyer_id);
    }

    /**
     * Create order from cart
     */
    public function createOrder($buyer_id, $cart_items, $shipping_address) {
        // Calculate total and get farmer_id from first item
        $total = 0;
        $farmer_id = null;
        
        foreach ($cart_items as $item) {
            $product = $this->productModel->getById($item['product_id']);
            if ($product) {
                $total += $product['price_per_unit'] * $item['quantity'];
                if (!$farmer_id) {
                    $farmer_id = $product['farmer_id'];
                }
            }
        }

        if (!$farmer_id) {
            return ['success' => false, 'message' => 'Invalid cart items'];
        }

        $order_id = $this->orderModel->create($buyer_id, $farmer_id, $total, $shipping_address);
        return ['success' => true, 'order_id' => $order_id, 'total' => $total];
    }

    /**
     * Process payment
     */
    public function processPayment($order_id, $buyer_id, $payment_method, $transaction_id) {
        // Update order payment status
        $success = $this->orderModel->updatePaymentStatus($order_id, $buyer_id, $payment_method);
        
        if ($success) {
            // Here you would also create a payment record in payments table
            // For now, just return success
            return ['success' => true, 'message' => 'Payment processed successfully'];
        }
        
        return ['success' => false, 'message' => 'Payment processing failed'];
    }
}

