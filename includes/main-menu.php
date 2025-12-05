<?php
// main-menu.php
// Uses $BASE_URL from config.php (already loaded in header.php)
?>

<nav class="main-menu d-flex align-items-center">
    <ul>
        <!-- Public site links -->
        <li><a href="<?= $BASE_URL ?>index.php">Home</a></li>
        <li><a href="<?= $BASE_URL ?>about.php">About</a></li>
        <li><a href="<?= $BASE_URL ?>shop.php">Marketplace</a></li>

        <!-- Farmer area -->
        <li class="has-dropdown">
            <a href="javascript:void(0)">Farmer Panel</a>
            <ul class="sub-menu">
                <li><a href="<?= $BASE_URL ?>farmer/dashboard.php">Dashboard</a></li>
                <li><a href="<?= $BASE_URL ?>farmer/profile.php">My Profile</a></li>
                <li><a href="<?= $BASE_URL ?>farmer/products.php">My Products</a></li>
                <li><a href="<?= $BASE_URL ?>farmer/orders.php">Orders</a></li>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'FARMER'): ?>
                    <li><a href="<?= $BASE_URL ?>farmer/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?= $BASE_URL ?>farmer/login.php">Login</a></li>
                    <li><a href="<?= $BASE_URL ?>farmer/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </li>

        <!-- Buyer area -->
        <li class="has-dropdown">
            <a href="javascript:void(0)">Buyer Panel</a>
            <ul class="sub-menu">
                <li><a href="<?= $BASE_URL ?>buyer/dashboard.php">Dashboard</a></li>
                <li><a href="<?= $BASE_URL ?>buyer/orders.php">My Orders</a></li>
                <li><a href="<?= $BASE_URL ?>buyer/profile.php">My Profile</a></li>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'BUYER'): ?>
                    <li><a href="<?= $BASE_URL ?>buyer/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?= $BASE_URL ?>buyer/login.php">Login</a></li>
                    <li><a href="<?= $BASE_URL ?>buyer/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </li>

        <!-- Admin area -->
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'ADMIN'): ?>
        <li class="has-dropdown">
            <a href="javascript:void(0)">Admin Panel</a>
            <ul class="sub-menu">
                <li><a href="<?= $BASE_URL ?>admin/dashboard.php">Dashboard</a></li>
                <li><a href="<?= $BASE_URL ?>admin/users.php">Users</a></li>
                <li><a href="<?= $BASE_URL ?>admin/products.php">Products</a></li>
                <li><a href="<?= $BASE_URL ?>admin/disputes.php">Disputes</a></li>
                <li><a href="<?= $BASE_URL ?>admin/reviews.php">Reviews</a></li>
                <li><a href="<?= $BASE_URL ?>admin/logout.php">Logout</a></li>
            </ul>
        </li>
        <?php else: ?>
        <li><a href="<?= $BASE_URL ?>admin/login.php">Admin Login</a></li>
        <?php endif; ?>

        <!-- Transporter area -->
        <li class="has-dropdown">
            <a href="javascript:void(0)">Transporter Panel</a>
            <ul class="sub-menu">
                <li><a href="<?= $BASE_URL ?>transporter/dashboard.php">Dashboard</a></li>
                <li><a href="<?= $BASE_URL ?>transporter/profile.php">My Profile</a></li>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'TRANSPORTER'): ?>
                    <li><a href="<?= $BASE_URL ?>transporter/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?= $BASE_URL ?>transporter/login.php">Login</a></li>
                    <li><a href="<?= $BASE_URL ?>transporter/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </li>
    </ul>
</nav>
