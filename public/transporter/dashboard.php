<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

$transporterId = $_SESSION['user_id'];

// Check if profile exists
$stmt = $pdo->prepare("SELECT * FROM transporter_profiles WHERE user_id = :uid");
$stmt->execute([':uid' => $transporterId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$site_title = "Transporter Dashboard | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Transporter') ?>!</h2>

        <?php if (!$profile): ?>
            <div class="alert alert-warning">
                <strong>Profile Setup Required:</strong> Please complete your transporter profile to start accepting delivery jobs.
                <a href="<?= $BASE_URL ?>transporter/profile.php" class="btn btn-primary btn-sm ms-2">Setup Profile</a>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>Profile Active:</strong> Your profile is set up and ready to accept jobs.
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Vehicle Type</h5>
                        <h3><?= htmlspecialchars($profile['vehicle_type'] ?? 'Not Set') ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Capacity</h5>
                        <h3><?= $profile ? number_format($profile['max_capacity_kg']) . ' kg' : 'Not Set' ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">License Plate</h5>
                        <h3><?= htmlspecialchars($profile['license_plate'] ?? 'Not Set') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>transporter/profile.php" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-user"></i> Manage Profile
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>transporter/jobs.php" class="btn btn-success w-100 mb-2">
                    <i class="fas fa-briefcase"></i> View Available Jobs
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>transporter/my-bids.php" class="btn btn-info w-100 mb-2">
                    <i class="fas fa-hand-holding-usd"></i> My Bids
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= $BASE_URL ?>transporter/my-deliveries.php" class="btn btn-warning w-100 mb-2">
                    <i class="fas fa-truck"></i> My Deliveries
                </a>
            </div>
        </div>

        <!-- API Information -->
        <?php if ($profile): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-code"></i> API Endpoints Available</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">All transporter operations are available via RESTful API endpoints:</p>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> <code>GET /api/transporter/jobs.php</code> - List available jobs</li>
                            <li><i class="fas fa-check text-success"></i> <code>POST /api/transporter/bids/create.php</code> - Create bid</li>
                            <li><i class="fas fa-check text-success"></i> <code>GET /api/transporter/bids.php</code> - List my bids</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> <code>GET /api/transporter/deliveries.php</code> - List my deliveries</li>
                            <li><i class="fas fa-check text-success"></i> <code>POST /api/transporter/deliveries/update.php</code> - Update delivery status</li>
                        </ul>
                    </div>
                </div>
                <a href="<?= $BASE_URL ?>api/transporter/README.md" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="fas fa-book"></i> View API Documentation
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

