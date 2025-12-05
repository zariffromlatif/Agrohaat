<?php
require_once '../../config/config.php';
require_once '../../models/TransporterProfile.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORTER') {
    redirect('transporter/login.php');
}

$user_id = $_SESSION['user_id'];
$profileModel = new TransporterProfile($pdo);
$existing_profile = $profileModel->getByUserId($user_id);

// Get user details
$stmt = $pdo->prepare("SELECT full_name, phone_number FROM users WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_type = $_POST['vehicle_type'];
    $license_plate = $_POST['license_plate'];
    $max_capacity_kg = intval($_POST['max_capacity_kg']);
    $service_area_districts = $_POST['service_area_districts'] ?? '';

    try {
        if ($profileModel->save($user_id, $vehicle_type, $license_plate, $max_capacity_kg, $service_area_districts)) {
            $success_message = $existing_profile ? "Profile updated successfully!" : "Profile created successfully!";
            // Refresh profile data
            $existing_profile = $profileModel->getByUserId($user_id);
        } else {
            $error_message = "Failed to save profile. Please try again.";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'license_plate') !== false) {
            $error_message = "This license plate is already registered!";
        } else {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

$site_title  = "Transporter Profile | AgroHaat";
$special_css = "innerpage";
include '../../includes/header.php';
?>

<section class="pt-80 pb-80">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="mb-4">🚚 Transporter Profile</h2>
                <p class="mb-4">Register your vehicle and start accepting delivery jobs</p>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Account Information</h5>
                        <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone_number']) ?></p>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        ✓ <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        ✗ <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($existing_profile): ?>
                    <div class="alert alert-info">
                        <h5>✓ Profile Active</h5>
                        <p class="mb-0">You can update your profile information below</p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Vehicle Type *</label>
                        <select name="vehicle_type" class="form-control" required>
                            <option value="">-- Select Vehicle Type --</option>
                            <option value="TRUCK" <?= ($existing_profile && $existing_profile['vehicle_type'] === 'TRUCK') ? 'selected' : '' ?>>Truck</option>
                            <option value="PICKUP" <?= ($existing_profile && $existing_profile['vehicle_type'] === 'PICKUP') ? 'selected' : '' ?>>Pick-up</option>
                            <option value="VAN" <?= ($existing_profile && $existing_profile['vehicle_type'] === 'VAN') ? 'selected' : '' ?>>Van</option>
                            <option value="CNG" <?= ($existing_profile && $existing_profile['vehicle_type'] === 'CNG') ? 'selected' : '' ?>>CNG</option>
                            <option value="BOAT" <?= ($existing_profile && $existing_profile['vehicle_type'] === 'BOAT') ? 'selected' : '' ?>>Boat</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">License Plate Number *</label>
                        <input 
                            type="text" 
                            name="license_plate" 
                            class="form-control"
                            placeholder="e.g., DHK-T-1234" 
                            value="<?= $existing_profile ? htmlspecialchars($existing_profile['license_plate']) : '' ?>"
                            pattern="[A-Z]{3}-[A-Z]-[0-9]{4}"
                            title="Format: ABC-X-1234 (e.g., DHK-T-1234)"
                            required>
                        <small class="form-text text-muted">Format: DHK-T-1234 (will be converted to uppercase)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Maximum Capacity (KG) *</label>
                        <input 
                            type="number" 
                            name="max_capacity_kg" 
                            class="form-control"
                            placeholder="e.g., 5000" 
                            value="<?= $existing_profile ? $existing_profile['max_capacity_kg'] : '' ?>"
                            min="1"
                            max="50000"
                            required>
                        <small class="form-text text-muted">Enter the maximum weight your vehicle can carry in kilograms</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Service Area (Districts)</label>
                        <textarea 
                            name="service_area_districts" 
                            class="form-control"
                            rows="3"
                            placeholder="Enter districts you service, separated by commas (e.g., Dhaka, Tangail, Gazipur)"><?= $existing_profile ? htmlspecialchars($existing_profile['service_area_districts']) : '' ?></textarea>
                        <small class="form-text text-muted">List all districts where you provide delivery services</small>
                    </div>

                    <button type="submit" class="theme-btn style-one w-100">
                        <?= $existing_profile ? 'Update Profile' : 'Create Profile' ?>
                    </button>
                </form>

                <div class="mt-3">
                    <a href="<?= $BASE_URL ?>transporter/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../includes/footer.php'; ?>

