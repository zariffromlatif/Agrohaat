<?php
/**
 * Admin API - Review Management
 * GET /api/admin/reviews.php - List all reviews
 * GET /api/admin/reviews.php?id={id} - Get specific review
 */

require_once 'auth.php';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific review
                $review_id = intval($_GET['id']);
                $stmt = $pdo->prepare("SELECT r.review_id, r.rating, r.comment, r.created_at,
                                              u1.full_name AS reviewer,
                                              u2.full_name AS reviewee,
                                              o.order_id
                                       FROM reviews r
                                       JOIN users u1 ON r.reviewer_id = u1.user_id
                                       JOIN users u2 ON r.reviewee_id = u2.user_id
                                       LEFT JOIN orders o ON o.order_id = r.order_id
                                       WHERE r.review_id = :id");
                $stmt->execute([':id' => $review_id]);
                $review = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$review) {
                    sendError('Review not found', 404);
                }
                
                sendSuccess($review, 'Review retrieved successfully');
            } else {
                // List all reviews
                $rating = isset($_GET['rating']) ? intval($_GET['rating']) : null;
                
                $reviews = $adminController->listReviews();
                
                // Filter by rating if provided
                if ($rating !== null && $rating >= 1 && $rating <= 5) {
                    $reviews = array_filter($reviews, function($r) use ($rating) {
                        return $r['rating'] == $rating;
                    });
                    $reviews = array_values($reviews);
                }
                
                sendSuccess([
                    'reviews' => $reviews,
                    'total' => count($reviews)
                ], 'Reviews retrieved successfully');
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

