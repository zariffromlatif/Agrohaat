<?php
require_once __DIR__ . '/../models/Order.php';

class ReviewController {
    private $pdo;
    private $orderModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->orderModel = new Order($pdo);
    }

    /**
     * Submit a review/rating
     */
    public function submitReview($order_id, $reviewer_id, $reviewee_id, $rating, $comment = '') {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
        }

        // Verify order exists and belongs to reviewer
        $order = $this->orderModel->getByIdForBuyer($order_id, $reviewer_id);
        if (!$order) {
            // Try as farmer
            $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE order_id = :oid AND farmer_id = :fid");
            $stmt->execute([':oid' => $order_id, ':fid' => $reviewer_id]);
            $order = $stmt->fetch();
        }

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found or access denied'];
        }

        // Check if order is delivered
        if ($order['status'] !== 'DELIVERED') {
            return ['success' => false, 'message' => 'You can only rate after order is delivered'];
        }

        // Check if review already exists
        $stmt = $this->pdo->prepare("SELECT review_id FROM reviews 
                                      WHERE order_id = :oid AND reviewer_id = :rid");
        $stmt->execute([':oid' => $order_id, ':rid' => $reviewer_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'You have already submitted a review for this order'];
        }

        // Insert review
        $sql = "INSERT INTO reviews (order_id, reviewer_id, reviewee_id, rating, comment) 
                VALUES (:order_id, :reviewer_id, :reviewee_id, :rating, :comment)";
        $stmt = $this->pdo->prepare($sql);
        
        $success = $stmt->execute([
            ':order_id' => $order_id,
            ':reviewer_id' => $reviewer_id,
            ':reviewee_id' => $reviewee_id,
            ':rating' => $rating,
            ':comment' => $comment
        ]);

        if ($success) {
            return ['success' => true, 'message' => 'Review submitted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to submit review'];
        }
    }

    /**
     * Get review for an order by reviewer
     */
    public function getReviewForOrder($order_id, $reviewer_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM reviews 
                                      WHERE order_id = :oid AND reviewer_id = :rid");
        $stmt->execute([':oid' => $order_id, ':rid' => $reviewer_id]);
        return $stmt->fetch();
    }

    /**
     * Get all reviews for a user (as reviewee)
     */
    public function getReviewsForUser($user_id) {
        $stmt = $this->pdo->prepare("SELECT r.*, u.full_name AS reviewer_name, o.order_id
                                      FROM reviews r
                                      JOIN users u ON r.reviewer_id = u.user_id
                                      JOIN orders o ON r.order_id = o.order_id
                                      WHERE r.reviewee_id = :uid
                                      ORDER BY r.created_at DESC");
        $stmt->execute([':uid' => $user_id]);
        return $stmt->fetchAll();
    }
}

