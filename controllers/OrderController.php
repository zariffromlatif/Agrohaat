<?php
// Handles order management for farmers
require_once __DIR__ . '/../models/Order.php';

class OrderController {
    private $model;

    public function __construct($pdo) {
        $this->model = new Order($pdo);
    }

    public function getFarmerOrders($farmer_id) {
        return $this->model->getForFarmer($farmer_id);
    }
}

