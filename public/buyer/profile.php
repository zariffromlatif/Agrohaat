<?php
require_once '../../config/config.php';
require_once '../../models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'BUYER') {
    redirect('buyer/login.php');
}

$userModel = new User($pdo);
$message = '';
$error = '';

// Get current user data
$sql = "SELECT * FROM users WHERE user_id = :uid";
$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $email = $_POST['email'] ?? '';
    $district = $_POST['district'] ?? '';
    $upazila = $_POST['upazila'] ?? '';
    $address_details = $_POST['address_details'] ?? '';

    $sql = "UPDATE users SET 
            full_name = :full_name,
            phone_number = :phone_number,
            email = :email,
            district = :district,
            upazila = :upazila,
            address_details = :address_details
            WHERE user_id = :uid AND role = 'BUYER'";

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([
        ':full_name' => $full_name,
        ':phone_number' => $phone_number,
        ':email' => $email,
        ':district' => $district,
        ':upazila' => $upazila,
        ':address_details' => $address_details,
        ':uid' => $_SESSION['user_id']
    ])) {
        $message = 'Profile updated successfully!';
        $_SESSION['name'] = $full_name;
        // Reload user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :uid");
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = 'Failed to update profile.';
    }
}

$site_title  = "My Profile | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="mb-4">My Profile</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">District</label>
                            <input type="text" name="district" class="form-control" value="<?= htmlspecialchars($user['district'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upazila</label>
                            <input type="text" name="upazila" class="form-control" value="<?= htmlspecialchars($user['upazila'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address Details</label>
                        <textarea name="address_details" class="form-control" rows="3"><?= htmlspecialchars($user['address_details'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="theme-btn style-one">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

