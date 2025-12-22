<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Payment.php';

class BuyerController {
    private $pdo;
    private $productModel;
    private $orderModel;
    private $paymentModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->paymentModel = new Payment($pdo);
    }

    /**
     * Get product by ID
     */
    public function getProduct($product_id) {
        return $this->productModel->getById($product_id);
    }

    /**
     * Search products
     */
    public function searchProducts($query, $category_id = null, $district = null, $min_price = null, $max_price = null, $quality = null, $limit = 12, $offset = 0) {
        return $this->productModel->search($query, $category_id, $district, $min_price, $max_price, $quality, $limit, $offset);
    }

    /**
     * Get product count for search/filter
     */
    public function getProductCount($query = '', $category_id = null, $district = null, $min_price = null, $max_price = null, $quality = null) {
        return $this->productModel->getSearchCount($query, $category_id, $district, $min_price, $max_price, $quality);
    }

    /**
     * Get all orders for buyer
     */
    public function getBuyerOrders($buyer_id) {
        return $this->orderModel->getForBuyer($buyer_id);
    }

    /**
     * Get order details for buyer
     */
    public function getOrderDetails($order_id, $buyer_id) {
        return $this->orderModel->getByIdForBuyer($order_id, $buyer_id);
    }

    /**
     * Create order from cart
     */
    public function createOrder($buyer_id, $cart_items, $shipping_address) {
        try {
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Calculate total and get farmer_id from first item
            $total = 0;
            $farmer_id = null;
            $order_items_data = [];
            
            foreach ($cart_items as $item) {
                $product = $this->productModel->getById($item['product_id']);
                if (!$product) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Product not found: ' . $item['product_id']];
                }
                
                // Check if product is available
                if ($product['status'] !== 'ACTIVE' || $product['is_deleted'] == 1) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Product is not available: ' . $product['title']];
                }
                
                // Check if quantity is available
                if ($item['quantity'] > $product['quantity_available']) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Insufficient quantity for: ' . $product['title']];
                }
                
                $unit_price = $product['price_per_unit'];
                $quantity = $item['quantity'];
                $subtotal = $unit_price * $quantity;
                $total += $subtotal;
                
                if (!$farmer_id) {
                    $farmer_id = $product['farmer_id'];
                }
                
                // Prepare order items data - FIX: Include product_id
                $order_items_data[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'subtotal' => $subtotal
                ];
            }

            if (!$farmer_id || empty($order_items_data)) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Invalid cart items'];
            }

            // Create order
            $order_id = $this->orderModel->create($buyer_id, $farmer_id, $total, $shipping_address);
            
            if (!$order_id) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Failed to create order'];
            }
            
            // Create order items
            $items_created = $this->orderModel->createOrderItems($order_id, $order_items_data);
            
            if (!$items_created) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Failed to create order items'];
            }
            
            // Commit transaction
            $this->pdo->commit();
            
            return ['success' => true, 'order_id' => $order_id, 'total' => $total];
            
        } catch (Exception $e) {
            // Rollback on error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Order creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Order creation error: ' . $e->getMessage()];
        }
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods($type = null) {
        return $this->paymentModel->getPaymentMethods($type);
    }

    /**
     * Get bank accounts
     */
    public function getBankAccounts() {
        return $this->paymentModel->getBankAccounts();
    }

    /**
     * Get order payments
     */
    public function getOrderPayments($order_id) {
        return $this->paymentModel->getOrderPayments($order_id);
    }

    /**
     * Get order items for an order
     */
    public function getOrderItems($order_id) {
        return $this->orderModel->getOrderItems($order_id);
    }

    /**
     * Process payment - REBUILT VERSION
     */
    public function processPayment($order_id, $buyer_id, $payment_method, $transaction_id) {
        try {
            // Get order details
            $order = $this->orderModel->getByIdForBuyer($order_id, $buyer_id);
            if (!$order) {
                error_log("Order not found: order_id=$order_id, buyer_id=$buyer_id");
                return ['success' => false, 'message' => 'Order not found'];
            }

            // Check if payment already exists and is pending/processing
            $existing_payments = $this->paymentModel->getOrderPayments($order_id);
            foreach ($existing_payments as $existing) {
                if (in_array($existing['status'], ['PENDING', 'PROCESSING', 'COMPLETED'])) {
                    return ['success' => true, 'message' => 'Payment already submitted and under review', 'payment_id' => $existing['payment_id']];
                }
            }

            // Get payment method ID from database
            $payment_method_id = $this->getPaymentMethodId($payment_method);
            if (!$payment_method_id) {
                error_log("Payment method not found: payment_method=$payment_method");
                return ['success' => false, 'message' => 'Invalid payment method: ' . $payment_method . '. Please contact support.'];
            }

            // Prepare payment details
            $payment_details = [
                'transaction_id' => $transaction_id,
                'payment_method' => $payment_method,
                'submitted_at' => date('Y-m-d H:i:s'),
                'notes' => 'Payment submitted by buyer - awaiting admin verification'
            ];

            // Create payment record
            $payment_id = $this->paymentModel->createPayment(
                $order_id,
                $buyer_id,
                $payment_method_id,
                $order['total_amount'],
                $payment_details
            );

            if (!$payment_id) {
                error_log("Payment creation failed: order_id=$order_id, buyer_id=$buyer_id, method_id=$payment_method_id");
                return ['success' => false, 'message' => 'Failed to create payment record. Please try again or contact support.'];
            }

            // Update payment with transaction ID (this also sets status to PROCESSING)
            $update_success = $this->paymentModel->updatePaymentTransaction($payment_id, $transaction_id);
            if (!$update_success) {
                error_log("Failed to update payment transaction: payment_id=$payment_id");
                // Don't fail here, payment was created
            }
            
            // Update order status to show payment is under review
            $order_success = $this->orderModel->updateOrderForPaymentReview($order_id, $buyer_id);
            if (!$order_success) {
                error_log("Failed to update order status: order_id=$order_id");
                // Payment was created, so return success anyway
            }

            return ['success' => true, 'message' => 'Payment submitted successfully. Your payment is under review.', 'payment_id' => $payment_id];
            
        } catch (Exception $e) {
            error_log("Payment processing exception: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Payment processing error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get payment method ID from provider name
     */
    private function getPaymentMethodId($payment_method) {
        // Map payment method codes to provider names in database
        // Database uses: BKASH, NAGAD, ROCKET, UPAY, VISA, MASTERCARD, AMEX, BANK
        $method_mapping = [
            'BKASH' => 'BKASH',  // Database has 'BKASH' not 'bKash'
            'NAGAD' => 'NAGAD',
            'ROCKET' => 'ROCKET',
            'UPAY' => 'UPAY',
            'VISA' => 'VISA',
            'MASTERCARD' => 'MASTERCARD',
            'AMEX' => 'AMEX',
            'BANK_TRANSFER' => 'BANK'  // Database has 'BANK' not 'DBBL'
        ];
        
        $provider = $method_mapping[$payment_method] ?? $payment_method;
        
        try {
            // First try exact match on provider
            $stmt = $this->pdo->prepare("SELECT method_id FROM payment_methods WHERE provider = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$provider]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['method_id'];
            }
            
            // Try case-insensitive match
            $stmt = $this->pdo->prepare("SELECT method_id FROM payment_methods WHERE UPPER(provider) = UPPER(?) AND is_active = 1 LIMIT 1");
            $stmt->execute([$provider]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['method_id'];
            }
            
            // Try to find by name (e.g., 'bKash' name matches 'BKASH' provider)
            $stmt = $this->pdo->prepare("SELECT method_id FROM payment_methods WHERE name LIKE ? AND is_active = 1 LIMIT 1");
            $stmt->execute(["%$payment_method%"]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                error_log("Payment method found by name: payment_method=$payment_method, found_method_id=" . $result['method_id']);
                return $result['method_id'];
            }
            
            error_log("Payment method not found in database: provider=$provider, payment_method=$payment_method");
            return null;
        } catch (Exception $e) {
            error_log("Error getting payment method ID: " . $e->getMessage());
            return null;
        }
    }
}
