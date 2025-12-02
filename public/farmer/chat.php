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

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    // For now, default receiver as buyer; later can support transporter
    $receiverId = $order['buyer_id'];
    $controller->sendMessage($orderId, $farmerId, $receiverId, $content);
    redirect('farmer/chat.php?order_id=' . $orderId);
}

$messages   = $controller->getMessagesForOrder($orderId);
$site_title = "Chat for Order #" . $orderId . " | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-3">Chat - Order #<?= (int)$order['order_id'] ?></h2>
        <p class="mb-4">
            <strong>Buyer:</strong> <?= htmlspecialchars($order['buyer_name']) ?> |
            <strong>Status:</strong> <?= htmlspecialchars($order['status']) ?>
        </p>

        <div class="card mb-4">
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($messages)): ?>
                    <p class="text-muted">No messages yet. Start the conversation.</p>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="mb-3">
                            <small class="text-muted">
                                <?= htmlspecialchars($m['sender_name']) ?> •
                                <?= htmlspecialchars($m['created_at']) ?>
                            </small>
                            <div><?= nl2br(htmlspecialchars($m['content'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" class="row g-3">
            <div class="col-12">
                <label class="form-label">Your message</label>
                <textarea name="content" class="form-control" rows="3" required></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="theme-btn style-one">Send</button>
                <a href="<?= $BASE_URL ?>farmer/orders.php" class="theme-btn style-two ms-2">Back to Orders</a>
            </div>
        </form>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>


