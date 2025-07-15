<?php
require_once 'config/config.php';

// Check if user is already logged in
if (is_logged_in()) {
    $user_type = get_user_type();
    if ($user_type === 'admin') {
        redirect('pages/dashboard_admin.php');
    } elseif ($user_type === 'security') {
        redirect('pages/dashboard_security.php');
    } else {
        redirect('pages/dashboard_student.php');
    }
}

$error_message = '';
$success_message = '';

// Handle logout success message
if (isset($_GET['logout']) && $_GET['logout'] === 'success' && isset($_GET['message'])) {
    $success_message = urldecode($_GET['message']);
}

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

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            $db = getDB();
            
            // First, try to find the user by username
            $sql = "SELECT u.*, r.role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    WHERE u.username = ? AND u.is_active = TRUE";
            
            $user = $db->fetch($sql, [$username]);
            
            if ($user) {
                // Verify password (supports both hashed and plain text)
                if (verifyPassword($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_type'] = $user['role_name'];
                    $_SESSION['role_id'] = $user['role_id'];
                    
                    // Update last login
                    $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                    
                    // Check if first login (password change required)
                    if ($user['is_first_login']) {
                        $_SESSION['require_password_change'] = true;
                        redirect('change_password.php');
                    }
                    
                    // Redirect based on role
                    if ($user['role_name'] === 'admin') {
                        redirect('pages/dashboard_admin.php');
                    } elseif ($user['role_name'] === 'security') {
                        redirect('pages/dashboard_security.php');
                    } else {
                        redirect('pages/dashboard_student.php');
                    }
                } else {
                    $error_message = 'Incorrect password.';
                }
            } else {
                // If user not found by username, try to find by registration number or security code
                $sql = "SELECT u.*, r.role_name, s.*, so.security_code 
                        FROM users u 
                        JOIN roles r ON u.role_id = r.id 
                        LEFT JOIN students s ON u.student_id = s.id 
                        LEFT JOIN security_officers so ON u.security_officer_id = so.id 
                        WHERE (s.registration_number = ? OR so.security_code = ?) AND u.is_active = TRUE";
                
                $user = $db->fetch($sql, [$username, $username]);
                
                if ($user) {
                    if (verifyPassword($password, $user['password'])) {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_type'] = $user['role_name'];
                        $_SESSION['role_id'] = $user['role_id'];
                        
                        // Update last login
                        $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                        
                        // Check if first login (password change required)
                        if ($user['is_first_login']) {
                            $_SESSION['require_password_change'] = true;
                            redirect('change_password.php');
                        }
                        
                        // Redirect based on role
                        if ($user['role_name'] === 'admin') {
                            redirect('pages/dashboard_admin.php');
                        } elseif ($user['role_name'] === 'security') {
                            redirect('pages/dashboard_security.php');
                        } else {
                            redirect('pages/dashboard_student.php');
                        }
                    } else {
                        $error_message = 'Incorrect password.';
                    }
                } else {
                    $error_message = 'Username not found.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Login error. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #3498db, #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }
        
        .login-title {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
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
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
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
            color: #7f8c8d;
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }
        
        .login-help {
            text-align: center;
            margin-top: 2rem;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .login-help a {
            color: #3498db;
            text-decoration: none;
        }
        
        .login-help a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            border-color: #e74c3c;
            color: #e74c3c;
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            border-color: #27ae60;
            color: #27ae60;
        }
        
        .demo-credentials {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.2);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
        }
        
        .demo-credentials h4 {
            color: #3498db;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .demo-credentials p {
            margin: 0.25rem 0;
            font-size: 0.8rem;
            color: #2c3e50;
        }
        
        .setup-links {
            margin-top: 1rem;
            text-align: center;
        }
        
        .setup-links a {
            display: inline-block;
            margin: 0.5rem;
            padding: 0.5rem 1rem;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        
        .setup-links a:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1 class="login-title"><?php echo APP_NAME; ?></h1>
                <p class="login-subtitle">UR College of Education, Rukara Campus</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate>
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" class="form-control" 
                               placeholder="Enter your username" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Enter your password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
           
            
            <div class="login-help">
                <p>Need help? Contact system administrator</p>
                <p><a href="mailto:admin@ur.ac.rw">admin@ur.ac.rw</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Simple form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return false;
            }
        });
        
        // Show/hide password functionality
        document.querySelector('.input-icon').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash input-icon';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-lock input-icon';
            }
        });
    </script>
</body>
</html> 
