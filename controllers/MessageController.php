<?php
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Order.php';

class MessageController {
    private $messageModel;
    private $orderModel;

    public function __construct($pdo) {
        $this->messageModel = new Message($pdo);
        $this->orderModel   = new Order($pdo);
    }

    /**
     * Ensure the given order belongs to the current farmer.
     */
    public function ensureFarmerOwnsOrder($farmer_id, $order_id, $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :oid AND farmer_id = :fid");
        $stmt->execute([':oid' => $order_id, ':fid' => $farmer_id]);
        return $stmt->fetch();
    }

    public function getMessagesForOrder($order_id) {
        return $this->messageModel->getForOrder($order_id);
    }

    public function sendMessage($order_id, $sender_id, $receiver_id, $content) {
        if (trim($content) === '') {
            return false;
        }
        return $this->messageModel->create($order_id, $sender_id, $receiver_id, $content);
    }
}


