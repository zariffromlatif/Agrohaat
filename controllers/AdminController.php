<?php
class AdminController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Dashboard stats
    public function viewDashboard() {
        $users = $this->pdo->query("SELECT COUNT(*) as total FROM users WHERE is_deleted = 0")->fetch();
        $orders = $this->pdo->query("SELECT COUNT(*) as total FROM orders")->fetch();
        $disputes = $this->pdo->query("SELECT COUNT(*) as total FROM disputes WHERE status = 'OPEN'")->fetch();
        $products = $this->pdo->query("SELECT COUNT(*) as total FROM products WHERE is_deleted = 0")->fetch();
        
        return [
            "users" => $users['total'],
            "orders" => $orders['total'],
            "disputes" => $disputes['total'],
            "products" => $products['total']
        ];
    }

    // Get all users with pagination
    public function getAllUsers($limit = 50, $offset = 0) {
        $sql = "SELECT user_id, full_name, email, phone_number, role, district, upazila, is_verified, is_deleted, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // User moderation
    public function approveUser($userID) {
        $stmt = $this->pdo->prepare("UPDATE users SET is_verified = 1 WHERE user_id = :uid");
        return $stmt->execute([':uid' => $userID]);
    }

    public function suspendUser($userID) {
        $stmt = $this->pdo->prepare("UPDATE users SET is_deleted = 1 WHERE user_id = :uid");
        return $stmt->execute([':uid' => $userID]);
    }

    public function unsuspendUser($userID) {
        $stmt = $this->pdo->prepare("UPDATE users SET is_deleted = 0 WHERE user_id = :uid");
        return $stmt->execute([':uid' => $userID]);
    }

    // Get all products
    public function getAllProducts($limit = 50, $offset = 0) {
        $sql = "SELECT p.*, u.full_name AS farmer_name 
                FROM products p 
                JOIN users u ON u.user_id = p.farmer_id 
                ORDER BY p.created_at DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteProduct($productID) {
        $stmt = $this->pdo->prepare("UPDATE products SET is_deleted = 1 WHERE product_id = :pid");
        return $stmt->execute([':pid' => $productID]);
    }

    // Dispute ticketing system
    public function listDisputes() {
        $sql = "SELECT d.*, 
                       o.order_id, o.total_amount,
                       u.full_name AS complainant_name
                FROM disputes d
                JOIN orders o ON o.order_id = d.order_id
                JOIN users u ON u.user_id = d.complainant_id
                ORDER BY d.created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resolveDispute($disputeID, $resolution = "RESOLVED") {
        $stmt = $this->pdo->prepare("UPDATE disputes SET status = :status WHERE dispute_id = :did");
        return $stmt->execute([
            ':status' => $resolution,
            ':did' => $disputeID
        ]);
    }

    // Ratings system
    public function listReviews() {
        $sql = "SELECT r.review_id, r.rating, r.comment, r.created_at,
                       u1.full_name AS reviewer,
                       u2.full_name AS reviewee,
                       o.order_id
                FROM reviews r
                JOIN users u1 ON r.reviewer_id = u1.user_id
                JOIN users u2 ON r.reviewee_id = u2.user_id
                LEFT JOIN orders o ON o.order_id = r.order_id
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteReview($reviewID) {
        $stmt = $this->pdo->prepare("DELETE FROM reviews WHERE review_id = :rid");
        return $stmt->execute([':rid' => $reviewID]);
    }

    // Reports
    public function generateReports($type = "sales") {
        if ($type === "sales") {
            $stmt = $this->pdo->query("SELECT SUM(total_amount) as revenue, COUNT(*) as total_orders FROM orders WHERE status = 'DELIVERED'");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if ($type === "users") {
            $stmt = $this->pdo->query("SELECT role, COUNT(*) as count FROM users WHERE is_deleted = 0 GROUP BY role");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($type === "ratings") {
            $stmt = $this->pdo->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return [];
    }
}

