<?php
require_once '../../config/config.php';
require_once '../../controllers/FarmerAuthController.php';

// If already logged in as farmer, go to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'FARMER') {
    redirect('farmer/dashboard.php');
}

$auth = new FarmerAuthController($pdo);
$auth->handleLogin();

$site_title  = "Farmer Login | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="mb-4 text-center">Farmer Login</h2>

                <?php if (!empty($auth->error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($auth->error) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">
                        Registration successful. Please log in.
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

                <p class="mt-3 text-center">
                    New farmer? <a href="<?= $BASE_URL ?>farmer/register.php">Create an account</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>
