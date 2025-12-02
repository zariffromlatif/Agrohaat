<?php
// main-menu.php
// Uses $BASE_URL from config.php (already loaded in header.php)
?>

<nav class="main-menu d-flex align-items-center">
    <ul>
        <!-- Public site links -->
        <li><a href="<?= $BASE_URL ?>index.php">Home</a></li>
        <li><a href="<?= $BASE_URL ?>about.php">About</a></li>
        <li><a href="<?= $BASE_URL ?>services.php">Services</a></li>
        <li><a href="<?= $BASE_URL ?>shop.php">Marketplace</a></li>
        <li><a href="<?= $BASE_URL ?>contact.php">Contact</a></li>

        <!-- Farmer area -->
        <li class="has-dropdown">
            <a href="javascript:void(0)">Farmer Panel</a>
            <ul class="sub-menu">
                <li><a href="<?= $BASE_URL ?>farmer/dashboard.php">Dashboard</a></li>
                <li><a href="<?= $BASE_URL ?>farmer/profile.php">My Profile</a></li>
                <li><a href="<?= $BASE_URL ?>farmer/products.php">My Products</a></li>
                <li><a href="<?= $BASE_URL ?>farmer/orders.php">Orders</a></li>
                <li><a href="<?= $BASE_URL ?>farmer/login.php">Login</a></li>
                <li><a href="<?= $BASE_URL ?>farmer/register.php">Register</a></li>
            </ul>
        </li>

        <!-- Buyer area -->
        <li class="has-dropdown">
            <a href="javascript:void(0)">Buyer Panel</a>
            <ul class="sub-menu">
                <li><a href="<?= $BASE_URL ?>buyer/dashboard.php">Dashboard</a></li>
                <li><a href="<?= $BASE_URL ?>buyer/login.php">Login</a></li>
                <li><a href="<?= $BASE_URL ?>buyer/register.php">Register</a></li>
            </ul>
        </li>

        <!-- TODO (later): Transporter / Admin menus for other modules -->
    </ul>
</nav>
