<?php
require_once '../config/config.php';

// Only admins can register devices
if (!is_logged_in() || get_user_type() !== 'admin') {
    redirect('../unauthorized.php');
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $device_type = sanitize_input($_POST['device_type'] ?? '');
    $device_name = sanitize_input($_POST['device_name'] ?? '');
    $serial_number = sanitize_input($_POST['serial_number'] ?? '');
    $brand = sanitize_input($_POST['brand'] ?? '');
    $model = sanitize_input($_POST['model'] ?? '');
    $color = sanitize_input($_POST['color'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $is_registered = isset($_POST['is_registered']) ? 1 : 0;

    // Validation
    if (!$user_id || !$device_type || !$device_name || !$serial_number) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $db = getDB();
            // Check for duplicate serial number
            $exists = $db->fetch("SELECT id FROM devices WHERE serial_number = ?", [$serial_number]);
            if ($exists) {
                $error_message = 'A device with this serial number already exists.';
            } else {
                $db->query("INSERT INTO devices (user_id, device_type, device_name, serial_number, brand, model, color, description, is_registered) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$user_id, $device_type, $device_name, $serial_number, $brand, $model, $color, $description, $is_registered]);
                $success_message = 'Device registered successfully!';
            }
        } catch (Exception $e) {
            $error_message = 'Error registering device: ' . $e->getMessage();
        }
    }
}

// Fetch users for owner selection
try {
    $db = getDB();
    $users = $db->fetchAll("SELECT id, username, first_name, last_name, email FROM users WHERE is_active = 1 ORDER BY first_name, last_name");
} catch (Exception $e) {
    $users = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Register New Device</div>
            <div class="page-subtitle">Fill in the details to register a new device</div>
        </div>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <form method="POST" action="" class="card" style="max-width:700px;margin:auto;">
            <div class="card-body">
                <div class="mb-3">
                    <label for="user_id" class="form-label">Device Owner *</label>
                    <select id="user_id" name="user_id" class="form-select" required>
                        <option value="">-- Select Owner --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php if (!empty($user_id) && $user_id == $user['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['username'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="device_type" class="form-label">Device Type *</label>
                        <select id="device_type" name="device_type" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            <option value="laptop" <?php if (!empty($device_type) && $device_type == 'laptop') echo 'selected'; ?>>Laptop</option>
                            <option value="tablet" <?php if (!empty($device_type) && $device_type == 'tablet') echo 'selected'; ?>>Tablet</option>
                            <option value="phone" <?php if (!empty($device_type) && $device_type == 'phone') echo 'selected'; ?>>Phone</option>
                            <option value="other" <?php if (!empty($device_type) && $device_type == 'other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="device_name" class="form-label">Device Name *</label>
                        <input type="text" id="device_name" name="device_name" class="form-control" value="<?php echo htmlspecialchars($device_name ?? ''); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="serial_number" class="form-label">Serial Number *</label>
                        <input type="text" id="serial_number" name="serial_number" class="form-control" value="<?php echo htmlspecialchars($serial_number ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="brand" class="form-label">Brand</label>
                        <input type="text" id="brand" name="brand" class="form-control" value="<?php echo htmlspecialchars($brand ?? ''); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="model" class="form-label">Model</label>
                        <input type="text" id="model" name="model" class="form-control" value="<?php echo htmlspecialchars($model ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="color" class="form-label">Color</label>
                        <input type="text" id="color" name="color" class="form-control" value="<?php echo htmlspecialchars($color ?? ''); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="2"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" id="is_registered" name="is_registered" class="form-check-input" <?php if (!empty($is_registered)) echo 'checked'; ?>>
                    <label for="is_registered" class="form-check-label">Mark as Registered</label>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Register Device</button>
                <a href="../pages/devices.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php include '../includes/footer.php'; ?> 