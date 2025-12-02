<?php
require_once '../../config/config.php';
require_once '../../controllers/AdminAuthController.php';

$auth = new AdminAuthController($pdo);
$auth->logout();

