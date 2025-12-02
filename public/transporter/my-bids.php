<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

// TODO: Implement bid history
$site_title = "My Bids | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">My Bids</h2>
        
        <div class="alert alert-info">
            <strong>Coming Soon:</strong> This page will show your bid history and status.
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

