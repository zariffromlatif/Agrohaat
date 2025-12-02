<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    redirect('farmer/login.php');
}

// Load current farmer data
$stmt = $pdo->prepare("SELECT full_name, phone_number, email, division, district, upazila, address_details FROM users WHERE user_id = :uid");
$stmt->execute([':uid' => $_SESSION['user_id']]);
$farmer = $stmt->fetch();

$success = null;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name       = $_POST['full_name'];
    $phone_number    = $_POST['phone_number'];
    $division        = $_POST['division'] ?: null;
    $district        = $_POST['district'] ?: null;
    $upazila         = $_POST['upazila'] ?: null;
    $address_details = $_POST['address_details'] ?: null;

    $sql = "UPDATE users
            SET full_name = :full_name,
                phone_number = :phone_number,
                division = :division,
                district = :district,
                upazila = :upazila,
                address_details = :address_details
            WHERE user_id = :uid AND role = 'FARMER'";

    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ':full_name'       => $full_name,
        ':phone_number'    => $phone_number,
        ':division'        => $division,
        ':district'        => $district,
        ':upazila'         => $upazila,
        ':address_details' => $address_details,
        ':uid'             => $_SESSION['user_id'],
    ]);

    if ($ok) {
        $_SESSION['name'] = $full_name;
        $success = "Profile updated successfully.";
        $farmer['full_name']       = $full_name;
        $farmer['phone_number']    = $phone_number;
        $farmer['division']        = $division;
        $farmer['district']        = $district;
        $farmer['upazila']         = $upazila;
        $farmer['address_details'] = $address_details;
    } else {
        $error = "Failed to update profile. Please try again.";
    }
}

$site_title  = "My Profile | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <h2 class="mb-4">My Profile</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required
                       value="<?= htmlspecialchars($farmer['full_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone_number" class="form-control" required
                       value="<?= htmlspecialchars($farmer['phone_number'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Division</label>
                <input type="text" name="division" class="form-control"
                       value="<?= htmlspecialchars($farmer['division'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">District</label>
                <input type="text" name="district" class="form-control"
                       value="<?= htmlspecialchars($farmer['district'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Upazila</label>
                <input type="text" name="upazila" class="form-control"
                       value="<?= htmlspecialchars($farmer['upazila'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Address Details</label>
                <textarea name="address_details" class="form-control" rows="3"><?= htmlspecialchars($farmer['address_details'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="theme-btn style-one">Save Changes</button>
                <a href="<?= $BASE_URL ?>farmer/dashboard.php" class="theme-btn style-two ms-2">Back to Dashboard</a>
            </div>
        </form>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>


