<?php
require_once '../../config/config.php';
require_once '../../controllers/MessageController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

if (empty($_GET['order_id'])) {
    redirect('farmer/orders.php');
}

$orderId   = (int) $_GET['order_id'];
$farmerId  = $_SESSION['user_id'];
$controller = new MessageController($pdo);

// Ensure this order belongs to the logged-in farmer
$stmt  = $pdo->prepare("SELECT o.*, u.full_name AS buyer_name FROM orders o JOIN users u ON u.user_id = o.buyer_id WHERE o.order_id = :oid AND o.farmer_id = :fid");
$stmt->execute([':oid' => $orderId, ':fid' => $farmerId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('farmer/orders.php');
}

// Check if transporter is assigned to this order
$transporterInfo = null;
try {
    $transporterStmt = $pdo->prepare("
        SELECT u.user_id, u.full_name, db.status as bid_status
        FROM deliveryjobs dj
        INNER JOIN deliverybids db ON db.job_id = dj.job_id AND db.status = 'ACCEPTED'
        INNER JOIN users u ON u.user_id = db.transporter_id
        WHERE dj.order_id = :oid
        LIMIT 1
    ");
    $transporterStmt->execute([':oid' => $orderId]);
    $transporterInfo = $transporterStmt->fetch();
} catch (PDOException $e) {
    // Table might not exist, ignore
}

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    $chat_with = $_POST['chat_with'] ?? 'buyer'; // 'buyer' or 'transporter'
    
    if ($chat_with === 'buyer') {
        $receiverId = $order['buyer_id'];
    } else {
        $receiverId = $transporterInfo ? $transporterInfo['user_id'] : $order['buyer_id'];
    }
    
    $controller->sendMessage($orderId, $farmerId, $receiverId, $content);
    redirect('farmer/chat.php?order_id=' . $orderId . '&chat_with=' . $chat_with);
}

$chat_with = $_GET['chat_with'] ?? 'buyer'; // Default to buyer

$messages   = $controller->getMessagesForOrder($orderId);
$site_title = "Chat for Order #" . $orderId . " | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-3">Chat - Order #<?= (int)$order['order_id'] ?></h2>
        
        <!-- Chat Selection Tabs -->
        <?php if ($transporterInfo): ?>
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $chat_with === 'buyer' ? 'active' : '' ?>" 
                        onclick="window.location.href='?order_id=<?= $orderId ?>&chat_with=buyer'">
                    Chat with Buyer
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $chat_with === 'transporter' ? 'active' : '' ?>" 
                        onclick="window.location.href='?order_id=<?= $orderId ?>&chat_with=transporter'">
                    Chat with Transporter
                </button>
            </li>
        </ul>
        <?php endif; ?>
        
        <p class="mb-4">
            <strong><?= $chat_with === 'buyer' ? 'Buyer' : 'Transporter' ?>:</strong> 
            <?= htmlspecialchars($chat_with === 'buyer' ? $order['buyer_name'] : ($transporterInfo ? $transporterInfo['full_name'] : 'N/A')) ?> |
            <strong>Status:</strong> <?= htmlspecialchars($order['status']) ?>
        </p>

        <div class="card mb-4">
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php 
                // Filter messages to show only relevant ones
                $relevantMessages = array_filter($messages, function($m) use ($farmerId, $order, $transporterInfo, $chat_with) {
                    if ($chat_with === 'buyer') {
                        // Show messages between farmer and buyer
                        return ($m['sender_id'] == $farmerId && $m['receiver_id'] == $order['buyer_id']) ||
                               ($m['sender_id'] == $order['buyer_id'] && $m['receiver_id'] == $farmerId);
                    } else {
                        // Show messages between farmer and transporter
                        if (!$transporterInfo) return false;
                        return ($m['sender_id'] == $farmerId && $m['receiver_id'] == $transporterInfo['user_id']) ||
                               ($m['sender_id'] == $transporterInfo['user_id'] && $m['receiver_id'] == $farmerId);
                    }
                });
                ?>
                
                <?php if (empty($relevantMessages)): ?>
                    <p class="text-muted">No messages yet. Start the conversation.</p>
                <?php else: ?>
                    <?php foreach ($relevantMessages as $m): ?>
                        <div class="mb-3 p-3 <?= $m['sender_id'] == $farmerId ? 'bg-light border-start border-primary border-3' : 'bg-white border-start border-success border-3' ?>">
                            <small class="text-muted d-block mb-2">
                                <strong><?= htmlspecialchars($m['sender_name']) ?></strong> • 
                                <?= date('M j, Y g:i A', strtotime($m['created_at'])) ?>
                            </small>
                            <div><?= nl2br(htmlspecialchars($m['content'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" class="row g-3">
            <input type="hidden" name="chat_with" value="<?= htmlspecialchars($chat_with) ?>">
            <div class="col-12">
                <label class="form-label">Your message</label>
                <textarea name="content" class="form-control" rows="3" required placeholder="Type your message here..."></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="theme-btn style-one">Send Message</button>
                <a href="<?= $BASE_URL ?>farmer/orders.php" class="theme-btn style-two ms-2">Back to Orders</a>
            </div>
        </form>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>


