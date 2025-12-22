<?php
/**
 * Admin API - Delete Review
 * POST /api/admin/reviews/delete.php
 * Body: { "review_id": 123 }
 */

require_once '../auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $data = getJsonBody();
    validateRequired($data, ['review_id']);
    
    $review_id = intval($data['review_id']);
    
    if ($review_id <= 0) {
        sendError('Invalid review ID', 400);
    }
    
    // Check if review exists
    $stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE review_id = :id");
    $stmt->execute([':id' => $review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        sendError('Review not found', 404);
    }
    
    // Delete review
    $success = $adminController->deleteReview($review_id);
    
    if ($success) {
        sendSuccess(['review_id' => $review_id], 'Review deleted successfully');
    } else {
        sendError('Failed to delete review', 500);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

