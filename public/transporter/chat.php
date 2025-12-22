<?php
require_once '../../config/config.php';
require_once '../../controllers/MessageController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

if (empty($_GET['order_id'])) {
    redirect('transporter/my-deliveries.php');
}

$orderId = (int) $_GET['order_id'];
$transporterId = $_SESSION['user_id'];
$controller = new MessageController($pdo);

// Ensure this transporter is assigned to this order (has accepted bid)
$stmt = $pdo->prepare("
    SELECT o.*, 
           buyer.full_name AS buyer_name,
           farmer.full_name AS farmer_name,
           dj.job_id,
           db.status as bid_status
    FROM orders o
    INNER JOIN users buyer ON o.buyer_id = buyer.user_id
    INNER JOIN users farmer ON o.farmer_id = farmer.user_id
    INNER JOIN deliveryjobs dj ON dj.order_id = o.order_id
    INNER JOIN deliverybids db ON db.job_id = dj.job_id AND db.transporter_id = :tid AND db.status = 'ACCEPTED'
    WHERE o.order_id = :oid
");
$stmt->execute([':oid' => $orderId, ':tid' => $transporterId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('transporter/my-deliveries.php');
}

// Handle new message - determine receiver based on chat_with parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    $chat_with = $_POST['chat_with'] ?? 'buyer'; // 'buyer' or 'farmer'
    
    if ($chat_with === 'buyer') {
        $receiverId = $order['buyer_id'];
    } else {
        $receiverId = $order['farmer_id'];
    }
    
    $controller->sendMessage($orderId, $transporterId, $receiverId, $content);
    redirect('transporter/chat.php?order_id=' . $orderId . '&chat_with=' . $chat_with);
}

$chat_with = $_GET['chat_with'] ?? 'buyer'; // Default to buyer
$messages = $controller->getMessagesForOrder($orderId);

$site_title = "Chat - Order #" . $orderId . " | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-3">Chat - Order #<?= (int)$order['order_id'] ?></h2>
        
        <!-- Chat Selection Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $chat_with === 'buyer' ? 'active' : '' ?>" 
                        onclick="window.location.href='?order_id=<?= $orderId ?>&chat_with=buyer'">
                    Chat with Buyer
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $chat_with === 'farmer' ? 'active' : '' ?>" 
                        onclick="window.location.href='?order_id=<?= $orderId ?>&chat_with=farmer'">
                    Chat with Farmer
                </button>
            </li>
        </ul>
        
        <p class="mb-4">
            <strong><?= $chat_with === 'buyer' ? 'Buyer' : 'Farmer' ?>:</strong> 
            <?= htmlspecialchars($chat_with === 'buyer' ? $order['buyer_name'] : $order['farmer_name']) ?> |
            <strong>Status:</strong> <?= htmlspecialchars($order['status']) ?>
        </p>

        <div class="card mb-4">
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php 
                // Filter messages to show only relevant ones (between transporter and selected party)
                $relevantMessages = array_filter($messages, function($m) use ($transporterId, $order, $chat_with) {
                    if ($chat_with === 'buyer') {
                        // Show messages between transporter and buyer
                        return ($m['sender_id'] == $transporterId && $m['receiver_id'] == $order['buyer_id']) ||
                               ($m['sender_id'] == $order['buyer_id'] && $m['receiver_id'] == $transporterId);
                    } else {
                        // Show messages between transporter and farmer
                        return ($m['sender_id'] == $transporterId && $m['receiver_id'] == $order['farmer_id']) ||
                               ($m['sender_id'] == $order['farmer_id'] && $m['receiver_id'] == $transporterId);
                    }
                });
                ?>
                
                <?php if (empty($relevantMessages)): ?>
                    <p class="text-muted">No messages yet. Start the conversation.</p>
                <?php else: ?>
                    <?php foreach ($relevantMessages as $m): ?>
                        <div class="mb-3 p-3 <?= $m['sender_id'] == $transporterId ? 'bg-light border-start border-primary border-3' : 'bg-white border-start border-success border-3' ?>">
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
                <a href="<?= $BASE_URL ?>transporter/my-deliveries.php" class="theme-btn style-two ms-2">Back to Deliveries</a>
            </div>
        </form>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

