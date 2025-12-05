<?php
require_once '../../config/config.php';
require_once '../../controllers/AdminController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    redirect('admin/login.php');
}

$admin = new AdminController($pdo);
$reviews = $admin->listReviews();

// Handle review deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $admin->deleteReview($_POST['review_id']);
    header("Location: reviews.php?deleted=1");
    exit;
}

$site_title  = "Manage Reviews | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Review Management</h2>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Review deleted successfully!</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reviewer</th>
                        <th>Reviewee</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Order ID</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $r): ?>
                        <tr>
                            <td><?= $r['review_id'] ?></td>
                            <td><?= htmlspecialchars($r['reviewer']) ?></td>
                            <td><?= htmlspecialchars($r['reviewee']) ?></td>
                            <td>
                                <span class="badge bg-warning">
                                    <?= $r['rating'] ?>/5
                                </span>
                            </td>
                            <td><?= htmlspecialchars(substr($r['comment'] ?? '', 0, 50)) ?>...</td>
                            <td>#<?= $r['order_id'] ?? 'N/A' ?></td>
                            <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                            <td>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                                    <button type="submit" name="delete_review" class="btn btn-danger btn-sm" onclick="return confirm('Delete this review?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

