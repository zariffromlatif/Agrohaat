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
}

