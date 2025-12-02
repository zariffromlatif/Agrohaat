<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

// TODO: Implement job marketplace
// This page should show available delivery jobs (paid orders needing shipping)

$site_title = "Available Jobs | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Available Delivery Jobs</h2>
        
        <div class="alert alert-info">
            <strong>Coming Soon:</strong> This feature will display all available delivery jobs that need shipping.
            <br>Jobs will be filtered by pickup/drop-off location and payment status.
        </div>
        
        <p>This page will show:</p>
        <ul>
            <li>Orders with payment_status = 'PAID'</li>
            <li>Pickup location (farmer's location)</li>
            <li>Drop-off location (buyer's shipping address)</li>
            <li>Order amount and product details</li>
            <li>Option to place a bid</li>
        </ul>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

