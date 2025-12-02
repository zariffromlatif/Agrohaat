<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ---- REGISTER FARMER ----
    public function registerFarmer($full_name, $phone_number, $email, $password) {

        // Hash password
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users 
                (full_name, phone_number, email, password_hash, role, is_verified, is_deleted) 
                VALUES 
                (:full_name, :phone_number, :email, :password_hash, 'FARMER', 0, 0)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':full_name'    => $full_name,
            ':phone_number' => $phone_number,
            ':email'        => $email,
            ':password_hash'=> $hash
        ]);
    }

    // ---- LOGIN FARMER ----
    public function loginFarmer($email, $password) {

        $sql = "SELECT * FROM users WHERE email = :email AND role = 'FARMER' AND is_deleted = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }

    // ---- REGISTER BUYER ----
    public function registerBuyer($full_name, $phone_number, $email, $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users 
                (full_name, phone_number, email, password_hash, role, is_verified, is_deleted) 
                VALUES 
                (:full_name, :phone_number, :email, :password_hash, 'BUYER', 0, 0)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':full_name'    => $full_name,
            ':phone_number' => $phone_number,
            ':email'        => $email,
            ':password_hash'=> $hash
        ]);
    }

    // ---- LOGIN BUYER ----
    public function loginBuyer($email, $password) {
        $sql = "SELECT * FROM users WHERE email = :email AND role = 'BUYER' AND is_deleted = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }
}
