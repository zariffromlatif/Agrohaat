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
                JOIN users u ON u.user_id = o.farmer_id
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
        $sql = "SELECT o.*, u.full_name AS farmer_name, u.district AS farmer_district, u.upazila AS farmer_upazila
                FROM orders o
                JOIN users u ON u.user_id = o.farmer_id
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
     * Update order payment status
     */
    public function updatePaymentStatus($order_id, $buyer_id, $payment_status) {
        $sql = "UPDATE orders SET payment_status = :payment_status, status = 'PAID' 
                WHERE order_id = :oid AND buyer_id = :bid";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':oid' => $order_id,
            ':bid' => $buyer_id,
            ':payment_status' => $payment_status
        ]);
    }
}

