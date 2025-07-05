<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../config/config.php';

// Only require login, not admin, for testing
if (!is_logged_in()) {
    redirect('../unauthorized.php');
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $security_code = trim($_POST['security_code'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$security_code || !$first_name || !$last_name || !$email) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $db = getDB();
            // Check for duplicate security code or email
            $exists = $db->fetch("SELECT id FROM security_officers WHERE security_code = ? OR email = ?", [$security_code, $email]);
            if ($exists) {
                $error_message = 'A security officer with this code or email already exists.';
            } else {
                // Insert into security_officers
                $db->query("INSERT INTO security_officers (security_code, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?)",
                    [$security_code, $first_name, $last_name, $email, $phone]);
                
                // Get the inserted officer's ID
                $officer_id = $db->lastInsertId();
                
                // Create user account for the security officer
                $role_id = $db->fetch("SELECT id FROM roles WHERE role_name = 'security'")['id'];
                $default_password = password_hash($security_code, PASSWORD_DEFAULT); // Default password is the security code
                
                $db->query("INSERT INTO users (username, password, email, first_name, last_name, role_id, security_officer_id, is_first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$security_code, $default_password, $email, $first_name, $last_name, $role_id, $officer_id, TRUE]);
                
                $success_message = 'Security officer registered successfully! User account created with default password (security code).';
            }
        } catch (Exception $e) {
            $error_message = 'Error registering security officer: ' . $e->getMessage();
        }
    }
}
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Register Security Officer</div>
            <div class="page-subtitle">Add a new security officer to the system</div>
        </div>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <form method="POST" action="" class="card" style="max-width:600px;margin:auto;">
            <div class="card-body">
                <div class="mb-3">
                    <label for="security_code" class="form-label">Security Code *</label>
                    <input type="text" id="security_code" name="security_code" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</button>
            </div>
        </form>
    </div>
</main>
<?php require_once '../includes/footer.php'; ?> 