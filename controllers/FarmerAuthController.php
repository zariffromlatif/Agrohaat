<?php
require_once __DIR__ . '/../models/User.php';

class FarmerAuthController {

    private $userModel;
    public $error = "";

    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }

    public function handleRegister() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $full_name    = $_POST['full_name'];
            $phone_number = $_POST['phone_number'];
            $email        = $_POST['email'];
            $password     = $_POST['password'];
            $cpassword    = $_POST['confirm_password'];

            if ($password !== $cpassword) {
                $this->error = "Passwords do not match.";
                return;
            }

            if ($this->userModel->registerFarmer($full_name, $phone_number, $email, $password)) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $this->error = "Registration failed.";
            }
        }
    }

    public function handleLogin() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $email = $_POST['email'];
            $password = $_POST['password'];

            $user = $this->userModel->loginFarmer($email, $password);

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

