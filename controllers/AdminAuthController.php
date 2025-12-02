<?php
require_once __DIR__ . '/../models/User.php';

class AdminAuthController {
    private $userModel;
    public $error = "";

    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }

    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $user = $this->userModel->loginAdmin($email, $password);

            if ($user) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['full_name'];

                header("Location: dashboard.php");
                exit;
            } else {
                $this->error = "Invalid email or password";
            }
        }
    }

    public function logout() {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

