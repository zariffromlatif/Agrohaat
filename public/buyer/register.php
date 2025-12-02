<?php
require_once '../../config/config.php';
require_once '../../controllers/BuyerAuthController.php';

// If already logged in as buyer, go to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'BUYER') {
    redirect('buyer/dashboard.php');
}

$auth = new BuyerAuthController($pdo);
$auth->handleRegister();

$site_title  = "Buyer Registration | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="mb-4 text-center">Buyer Registration</h2>

                <?php if (!empty($auth->error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($auth->error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="theme-btn style-one w-100">Register</button>
                </form>

                <p class="mt-3 text-center">
                    Already have an account? <a href="<?= $BASE_URL ?>buyer/login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>


