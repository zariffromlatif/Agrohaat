<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'BUYER') {
    redirect('buyer/login.php');
}

$site_title  = "Buyer Dashboard | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Buyer') ?></h2>

        <p>This is a starter dashboard. Your teammate can add:</p>
        <ul>
            <li>Quick links to marketplace / shop</li>
            <li>Recent orders and tracking info</li>
            <li>Links to profile and payment methods</li>
        </ul>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>


