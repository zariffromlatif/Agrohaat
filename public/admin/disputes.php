<?php
require_once '../../config/config.php';
require_once '../../controllers/AdminController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    redirect('admin/login.php');
}

$admin = new AdminController($pdo);
$disputes = $admin->listDisputes();

// Handle dispute resolution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_dispute'])) {
    $admin->resolveDispute($_POST['dispute_id'], $_POST['resolution']);
    header("Location: disputes.php?updated=1");
    exit;
}

$site_title  = "Manage Disputes | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Dispute Management</h2>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Dispute updated successfully!</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order ID</th>
                        <th>Complainant</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($disputes as $d): ?>
                        <tr>
                            <td><?= $d['dispute_id'] ?></td>
                            <td>#<?= $d['order_id'] ?></td>
                            <td><?= htmlspecialchars($d['complainant_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars(substr($d['description'] ?? '', 0, 100)) ?>...</td>
                            <td>
                                <span class="badge bg-<?= $d['status'] === 'OPEN' ? 'warning' : ($d['status'] === 'RESOLVED' ? 'success' : 'secondary') ?>">
                                    <?= htmlspecialchars($d['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($d['created_at'])) ?></td>
                            <td>
                                <?php if ($d['status'] === 'OPEN'): ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="dispute_id" value="<?= $d['dispute_id'] ?>">
                                        <select name="resolution" class="form-select form-select-sm d-inline-block" style="width: auto;">
                                            <option value="RESOLVED">Resolved</option>
                                            <option value="REFUNDED">Refunded</option>
                                            <option value="REJECTED">Rejected</option>
                                        </select>
                                        <button type="submit" name="resolve_dispute" class="btn btn-primary btn-sm">Update</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Resolved</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

