<?php

class Message {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getForOrder($order_id) {
        $sql = "SELECT m.*, u.full_name AS sender_name
                FROM messages m
                JOIN users u ON u.user_id = m.sender_id
                WHERE m.order_id = :oid
                ORDER BY m.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':oid' => $order_id]);
        return $stmt->fetchAll();
    }

    public function create($order_id, $sender_id, $receiver_id, $content) {
        $sql = "INSERT INTO messages (order_id, sender_id, receiver_id, content)
                VALUES (:order_id, :sender_id, :receiver_id, :content)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':order_id'   => $order_id,
            ':sender_id'  => $sender_id,
            ':receiver_id'=> $receiver_id,
            ':content'    => $content,
        ]);
    }
}


