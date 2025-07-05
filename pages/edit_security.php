<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../login.php');
}

// Check user type - only admin can access this page
$user_type = get_user_type();
if ($user_type !== 'admin') {
    redirect('../unauthorized.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

$error_message = '';
$success_message = '';
$officer = null;

// Get officer ID
$officer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$officer_id) {
    redirect('security_officers.php');
}

try {
    $db = getDB();
    
    // Get officer details
    $officer = $db->fetch("
        SELECT so.*, u.username, u.is_active as user_active
        FROM security_officers so
        LEFT JOIN users u ON so.id = u.security_officer_id
        WHERE so.id = ?
    ", [$officer_id]);
    
    if (!$officer) {
        redirect('security_officers.php');
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading officer details: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $security_code = trim($_POST['security_code'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!$security_code || !$first_name || !$last_name || !$email) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            // Check for duplicate security code or email (excluding current officer)
            $exists = $db->fetch("
                SELECT id FROM security_officers 
                WHERE (security_code = ? OR email = ?) AND id != ?
            ", [$security_code, $email, $officer_id]);
            
            if ($exists) {
                $error_message = 'A security officer with this code or email already exists.';
            } else {
                // Update security officer
                $db->query("
                    UPDATE security_officers 
                    SET security_code = ?, first_name = ?, last_name = ?, email = ?, phone = ?, is_active = ?
                    WHERE id = ?
                ", [$security_code, $first_name, $last_name, $email, $phone, $is_active, $officer_id]);
                
                // Update corresponding user account if it exists
                if ($officer['username']) {
                    $db->query("
                        UPDATE users 
                        SET username = ?, email = ?, first_name = ?, last_name = ?, is_active = ?
                        WHERE security_officer_id = ?
                    ", [$security_code, $email, $first_name, $last_name, $is_active, $officer_id]);
                }
                
                $success_message = 'Security officer updated successfully!';
                
                // Refresh officer data
                $officer = $db->fetch("
                    SELECT so.*, u.username, u.is_active as user_active
                    FROM security_officers so
                    LEFT JOIN users u ON so.id = u.security_officer_id
                    WHERE so.id = ?
                ", [$officer_id]);
            }
        } catch (Exception $e) {
            $error_message = 'Error updating security officer: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Edit Security Officer</div>
            <div class="page-subtitle">Update security officer information</div>
            <div class="page-actions">
                <a href="security_officers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit"></i> Edit Security Officer Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="security_code" class="form-label">Security Code *</label>
                                        <input type="text" id="security_code" name="security_code" 
                                               class="form-control" required
                                               value="<?php echo htmlspecialchars($officer['security_code']); ?>">
                                        <div class="form-text">Unique identifier for the security officer</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="is_active" class="form-label">Status</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                                   <?php echo $officer['is_active'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_active">
                                                Active
                                            </label>
                                        </div>
                                        <div class="form-text">Inactive officers cannot access the system</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" id="first_name" name="first_name" 
                                               class="form-control" required
                                               value="<?php echo htmlspecialchars($officer['first_name']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" id="last_name" name="last_name" 
                                               class="form-control" required
                                               value="<?php echo htmlspecialchars($officer['last_name']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" id="email" name="email" 
                                               class="form-control" required
                                               value="<?php echo htmlspecialchars($officer['email']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" id="phone" name="phone" 
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($officer['phone'] ?? ''); ?>">
                                        <div class="form-text">Optional contact number</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Officer
                                </button>
                                <a href="security_officers.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- User Account Information -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-cog"></i> User Account
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($officer['username']): ?>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <div class="form-control-plaintext">
                                    <code><?php echo htmlspecialchars($officer['username']); ?></code>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Account Status</label>
                                <div>
                                    <?php if ($officer['user_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> The username will be automatically updated to match the security code when you save changes.
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                <p>No user account has been created for this security officer yet.</p>
                                <button type="button" class="btn btn-sm btn-primary" onclick="createUserAccount()">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tools"></i> Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="security_officers.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list"></i> View All Officers
                            </a>
                            <a href="register_security.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus"></i> Add New Officer
                            </a>
                            <?php if ($officer['username']): ?>
                                <button type="button" class="btn btn-outline-warning" onclick="resetPassword()">
                                    <i class="fas fa-key"></i> Reset Password
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function createUserAccount() {
    if (confirm('Create a user account for this security officer? The default password will be the security code.')) {
        // This would typically be an AJAX call to create the user account
        alert('User account creation feature will be implemented here.');
    }
}

function resetPassword() {
    if (confirm('Reset the password for this security officer? The new password will be the security code.')) {
        // This would typically be an AJAX call to reset the password
        alert('Password reset feature will be implemented here.');
    }
}
</script>

<?php include '../includes/footer.php'; ?> 