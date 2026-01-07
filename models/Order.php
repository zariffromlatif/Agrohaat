<?php
// Order model
class Order {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all orders for a specific farmer, newest first.
     * Uses existing `orders` table and joins `users` to get buyer name.
     */
    public function getForFarmer($farmer_id) {
        $sql = "SELECT o.*, u.full_name AS buyer_name
                FROM orders o
                JOIN users u ON u.user_id = o.buyer_id
                WHERE o.farmer_id = :fid
                ORDER BY o.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':fid' => $farmer_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get all orders for a specific buyer, newest first.
     */
    public function getForBuyer($buyer_id) {
        $sql = "SELECT o.*, u.full_name AS farmer_name, u.district AS farmer_district
                FROM orders o
                LEFT JOIN users u ON u.user_id = o.farmer_id
                WHERE o.buyer_id = :bid
                ORDER BY o.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':bid' => $buyer_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get order details by ID (for buyer)
     */
    public function getByIdForBuyer($order_id, $buyer_id) {
        $sql = "SELECT o.*, u.full_name AS farmer_name, u.phone_number AS farmer_phone, u.district AS farmer_district, u.upazila AS farmer_upazila
                FROM orders o
                LEFT JOIN users u ON u.user_id = o.farmer_id
                WHERE o.order_id = :oid AND o.buyer_id = :bid";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':oid' => $order_id,
            ':bid' => $buyer_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new order
     */
    public function create($buyer_id, $farmer_id, $total_amount, $shipping_address) {
        $sql = "INSERT INTO orders (buyer_id, farmer_id, total_amount, status, payment_status, shipping_address, created_at)
                VALUES (:buyer_id, :farmer_id, :total_amount, 'PENDING', 'UNPAID', :shipping_address, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':buyer_id' => $buyer_id,
            ':farmer_id' => $farmer_id,
            ':total_amount' => $total_amount,
            ':shipping_address' => $shipping_address
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Create order items for an order
     */
    public function createOrderItems($order_id, $cart_items) {
        // Check if order_items table uses 'subtotal' or 'total_price' column
        $checkColumn = $this->pdo->query("SHOW COLUMNS FROM order_items LIKE 'subtotal'");
        $useSubtotal = $checkColumn->rowCount() > 0;
        $priceColumn = $useSubtotal ? 'subtotal' : 'total_price';
        
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, $priceColumn, created_at)
                VALUES (:order_id, :product_id, :quantity, :unit_price, :subtotal, NOW())";

        $stmt = $this->pdo->prepare($sql);
        
        foreach ($cart_items as $item) {
            $unit_price = $item['unit_price'] ?? $item['price_per_unit'] ?? 0;
            $quantity = $item['quantity'] ?? 0;
            $subtotal = $unit_price * $quantity;
            
            $stmt->execute([
                ':order_id' => $order_id,
                ':product_id' => $item['product_id'],
                ':quantity' => $quantity,
                ':unit_price' => $unit_price,
                ':subtotal' => $subtotal
            ]);
        }
        
        return true;
    }

    /**
     * Get order items for an order
     */
    public function getOrderItems($order_id) {
        // Check if order_items table uses 'subtotal' or 'total_price' column
        $checkColumn = $this->pdo->query("SHOW COLUMNS FROM order_items LIKE 'subtotal'");
        $useSubtotal = $checkColumn->rowCount() > 0;
        $priceColumn = $useSubtotal ? 'subtotal' : 'total_price';
        
        $sql = "SELECT oi.*, p.title, p.image_url, p.unit
                FROM order_items oi
                JOIN products p ON p.product_id = oi.product_id
                WHERE oi.order_id = :order_id
                ORDER BY oi.created_at ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':order_id' => $order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update order payment status
     */
    public function updatePaymentStatus($order_id, $buyer_id, $payment_status) {
        // Keep order.status aligned with payment_status
        $newStatus = 'PROCESSING';
        if ($payment_status === 'PAID') {
            $newStatus = 'PAID';
        } elseif ($payment_status === 'REFUNDED') {
            $newStatus = 'CANCELLED';
        }

        $sql = "UPDATE orders SET payment_status = :payment_status, status = :status, updated_at = NOW()
                WHERE order_id = :oid AND buyer_id = :bid";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':oid' => $order_id,
            ':bid' => $buyer_id,
            ':payment_status' => $payment_status,
            ':status' => $newStatus
        ]);
    }
    
    /**
     * Update order when payment is submitted for review
     */
    public function updateOrderForPaymentReview($order_id, $buyer_id) {
        $sql = "UPDATE orders 
                SET payment_status = 'PENDING', 
                    status = 'PROCESSING',
                    updated_at = NOW()
                WHERE order_id = :oid AND buyer_id = :bid";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':oid' => $order_id,
            ':bid' => $buyer_id
        ]);
    }
}

