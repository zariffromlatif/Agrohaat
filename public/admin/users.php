<?php
require_once '../../config/config.php';
require_once '../../controllers/AdminController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    redirect('admin/login.php');
}

$admin = new AdminController($pdo);
$users = $admin->getAllUsers();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_user'])) {
        $admin->approveUser($_POST['user_id']);
        header("Location: users.php?approved=1");
        exit;
    }
    if (isset($_POST['suspend_user'])) {
        $admin->suspendUser($_POST['user_id']);
        header("Location: users.php?suspended=1");
        exit;
    }
    if (isset($_POST['unsuspend_user'])) {
        $admin->unsuspendUser($_POST['user_id']);
        header("Location: users.php?unsuspended=1");
        exit;
    }
}

$site_title  = "Manage Users | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">User Management</h2>

        <?php if (isset($_GET['approved'])): ?>
            <div class="alert alert-success">User approved successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['suspended'])): ?>
            <div class="alert alert-warning">User suspended successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['unsuspended'])): ?>
            <div class="alert alert-success">User unsuspended successfully!</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Verified</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['user_id'] ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($user['phone_number']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($user['role']) ?></span></td>
                            <td><?= htmlspecialchars($user['district'] ?? '') ?>, <?= htmlspecialchars($user['upazila'] ?? '') ?></td>
                            <td>
                                <?php if ($user['is_verified']): ?>
                                    <span class="badge bg-success">Verified</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_deleted']): ?>
                                    <span class="badge bg-danger">Suspended</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$user['is_verified']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <button type="submit" name="approve_user" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if (!$user['is_deleted']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <button type="submit" name="suspend_user" class="btn btn-sm btn-danger" onclick="return confirm('Suspend this user?')">Suspend</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <button type="submit" name="unsuspend_user" class="btn btn-sm btn-warning">Unsuspend</button>
                                    </form>
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

