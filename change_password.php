<?php
require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$error_message = '';
$success_message = '';

// Function to verify password (supports both hashed and plain text)
function verifyPassword($inputPassword, $storedPassword) {
    // First try password_verify (for hashed passwords)
    if (password_verify($inputPassword, $storedPassword)) {
        return true;
    }
    // If that fails, check if it's a plain text match
    if ($inputPassword === $storedPassword) {
        return true;
    }
    return false;
}

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'Please fill in all fields.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'New password must be at least 8 characters long.';
    } else {
        try {
            $db = getDB();
            // Verify current password (supports both hashed and plain text)
            $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$_SESSION['user_id']]);
            if ($user && verifyPassword($current_password, $user['password'])) {
                // Always store new password as a hash
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $password_to_store = $new_password_hash;
                // Update password and mark as not first login
                $db->query("UPDATE users SET password = ?, is_first_login = FALSE WHERE id = ?", 
                          [$password_to_store, $_SESSION['user_id']]);
                // Remove password change requirement from session if present
                unset($_SESSION['require_password_change']);
                $success_message = 'Password changed successfully! Redirecting to dashboard...';
                // Redirect after 2 seconds
                header("refresh:2;url=" . get_dashboard_url());
                exit();
            } else {
                $error_message = 'Current password is incorrect.';
            }
        } catch (Exception $e) {
            $error_message = 'Error changing password. Please try again.';
            error_log("Password change error: " . $e->getMessage());
        }
    }
}

function get_dashboard_url() {
    $user_type = get_user_type();
    if ($user_type === 'admin') {
        return 'pages/dashboard_admin.php';
    } elseif ($user_type === 'security') {
        return 'pages/dashboard_security.php';
    } else {
        return 'pages/dashboard_student.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .change-password-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
        }
        .change-password-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }
        .change-password-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .change-password-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }
        .change-password-title {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .change-password-subtitle {
            color: #7f8c8d;
            font-size: 1rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        .form-control:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        .input-group {
            position: relative;
        }
        .input-group .form-control {
            padding-right: 3rem;
        }
        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="change-password-card">
            <div class="change-password-header">
                <div class="change-password-icon">
                    <i class="fas fa-key"></i>
                </div>
                <div class="change-password-title">Change Password</div>
                <div class="change-password-subtitle">Update your account password below</div>
            </div>
            <?php if ($error_message): ?>
                <div class="alert alert-danger" style="margin-bottom:1rem;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success" style="margin-bottom:1rem;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <div class="input-group">
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                        <span class="input-icon" onclick="togglePassword('current_password')"><i class="fas fa-eye"></i></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                        <span class="input-icon" onclick="togglePassword('new_password')"><i class="fas fa-eye"></i></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                        <span class="input-icon" onclick="togglePassword('confirm_password')"><i class="fas fa-eye"></i></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100" style="font-weight:600;">
                    <i class="fas fa-save"></i> Change Password
                </button>
            </form>
        </div>
    </div>
    <script>
    function togglePassword(fieldId) {
        var field = document.getElementById(fieldId);
        if (field.type === "password") {
            field.type = "text";
        } else {
            field.type = "password";
        }
    }
    </script>
</body>
</html> 