<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

// TODO: Implement delivery management
$site_title = "My Deliveries | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">My Deliveries</h2>
        
        <div class="alert alert-info">
            <strong>Coming Soon:</strong> This page will show your assigned deliveries and allow status updates.
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

