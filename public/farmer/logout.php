<?php
require_once '../../config/config.php';
require_once '../../controllers/FarmerAuthController.php';

$auth = new FarmerAuthController($pdo);
$auth->logout();
