<?php
require_once '../../config/config.php';
require_once '../../controllers/BuyerAuthController.php';

$auth = new BuyerAuthController($pdo);
$auth->logout();

