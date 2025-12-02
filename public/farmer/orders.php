<?php
require_once '../../config/config.php';
require_once '../../controllers/OrderController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

$controller = new OrderController($pdo);
$orders     = $controller->getFarmerOrders($_SESSION['user_id']);

$site_title  = "My Orders | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">My Orders</h2>

        <?php if (empty($orders)): ?>
            <p>You have no orders yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Buyer</th>
                        <th>Total Amount (BDT)</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Created At</th>
                        <th>Chat</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= (int) $o['order_id'] ?></td>
                            <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                            <td><?= number_format((float) $o['total_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($o['status']) ?></td>
                            <td><?= htmlspecialchars($o['payment_status'] ?? 'UNPAID') ?></td>
                            <td><?= htmlspecialchars($o['created_at']) ?></td>
                            <td>
                                <a href="<?= $BASE_URL ?>farmer/chat.php?order_id=<?= (int)$o['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                    Open
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
