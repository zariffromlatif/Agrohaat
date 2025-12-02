<?php
require_once '../../config/config.php';
require_once '../../controllers/AdminController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    redirect('admin/login.php');
}

$admin = new AdminController($pdo);
$stats = $admin->viewDashboard();

$site_title  = "Admin Dashboard | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Admin Dashboard</h2>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <h3><?= $stats['users'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <h3><?= $stats['orders'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Open Disputes</h5>
                        <h3><?= $stats['disputes'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Products</h5>
                        <h3><?= $stats['products'] ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>admin/users.php" class="btn btn-primary w-100 mb-2">Manage Users</a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>admin/products.php" class="btn btn-primary w-100 mb-2">Manage Products</a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>admin/disputes.php" class="btn btn-primary w-100 mb-2">Manage Disputes</a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>admin/reviews.php" class="btn btn-primary w-100 mb-2">Manage Reviews</a>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

