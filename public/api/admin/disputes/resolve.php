<?php
/**
 * Admin API - Resolve Dispute
 * POST /api/admin/disputes/resolve.php
 * Body: { "dispute_id": 123, "resolution": "RESOLVED" }
 * Resolution options: "RESOLVED", "REFUNDED", "REJECTED"
 */

require_once '../auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    $data = getJsonBody();
    validateRequired($data, ['dispute_id', 'resolution']);
    
    $dispute_id = intval($data['dispute_id']);
    $resolution = strtoupper(trim($data['resolution']));
    
    if ($dispute_id <= 0) {
        sendError('Invalid dispute ID', 400);
    }
    
    // Validate resolution status
    $validResolutions = ['RESOLVED', 'REFUNDED', 'REJECTED'];
    if (!in_array($resolution, $validResolutions)) {
        sendError('Invalid resolution. Must be one of: ' . implode(', ', $validResolutions), 400);
    }
    
    // Check if dispute exists
    $stmt = $pdo->prepare("SELECT dispute_id, status FROM disputes WHERE dispute_id = :id");
    $stmt->execute([':id' => $dispute_id]);
    $dispute = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dispute) {
        sendError('Dispute not found', 404);
    }
    
    if ($dispute['status'] !== 'OPEN') {
        sendError('Dispute is already resolved', 400);
    }
    
    // Resolve dispute
    $success = $adminController->resolveDispute($dispute_id, $resolution);
    
    if ($success) {
        sendSuccess([
            'dispute_id' => $dispute_id,
            'resolution' => $resolution
        ], 'Dispute resolved successfully');
    } else {
        sendError('Failed to resolve dispute', 500);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

