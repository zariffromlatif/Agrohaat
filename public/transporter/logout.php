<?php
require_once '../../config/config.php';
require_once '../../controllers/TransporterAuthController.php';

$auth = new TransporterAuthController($pdo);
$auth->logout();

