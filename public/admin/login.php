<?php
require_once '../../config/config.php';
require_once '../../controllers/AdminAuthController.php';

// If already logged in as admin, go to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'ADMIN') {
    redirect('admin/dashboard.php');
}

$auth = new AdminAuthController($pdo);
$auth->handleLogin();

$site_title  = "Admin Login | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="mb-4 text-center">Admin Login</h2>

                <?php if (!empty($auth->error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($auth->error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="theme-btn style-one w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

