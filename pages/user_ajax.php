<?php
// Prevent any output before JSON response
ob_start();

// Include configuration
require_once '../config/config.php';

// Set JSON content type
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in and is admin
if (!is_logged_in()) {
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

if (get_user_type() !== 'admin') {
    echo json_encode(['error' => 'Admin access required']);
    exit();
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    echo json_encode(['error' => 'Password change required']);
    exit();
}

// Clear any output buffer
ob_clean();

// Exclude the default super user
$exclude_username = 'superadmin';

// Handle POST requests (saving user data)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['error' => 'Invalid user ID']);
        exit();
    }
    
    // Get form data
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 1);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
        echo json_encode(['error' => 'Please fill in all required fields.']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Please enter a valid email address.']);
        exit();
    }
    
    if ($role_id < 1 || $role_id > 4) {
        echo json_encode(['error' => 'Invalid role selected.']);
        exit();
    }
    
    try {
        $db = getDB();
        
        // Check if username already exists (excluding current user and super user)
        $existing_user = $db->fetch("SELECT id FROM users WHERE username = ? AND id != ? AND username != ?", [$username, $user_id, $exclude_username]);
        if ($existing_user) {
            echo json_encode(['error' => 'Username already exists.']);
            exit();
        }
        
        // Check if email already exists (excluding current user and super user)
        $existing_email = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ? AND username != ?", [$email, $user_id, $exclude_username]);
        if ($existing_email) {
            echo json_encode(['error' => 'Email address already exists.']);
            exit();
        }
        
        // Update user data
        $update_result = $db->query("UPDATE users SET 
                   username = ?, email = ?, first_name = ?, last_name = ?, 
                   role_id = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP 
                   WHERE id = ? AND username != ?", 
                  [$username, $email, $first_name, $last_name, $role_id, $is_active, $user_id, $exclude_username]);
        
        if ($update_result) {
            echo json_encode(['success' => 'User updated successfully!']);
            error_log("User updated successfully via AJAX: ID=" . $user_id . ", Username=" . $username);
        } else {
            echo json_encode(['error' => 'Failed to update user. Please try again.']);
            error_log("Failed to update user via AJAX: ID=" . $user_id);
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error updating user: ' . $e->getMessage()]);
        error_log("User update AJAX error: " . $e->getMessage());
    }
    
    exit();
}

// Handle GET requests (loading user data)
$user_id = (int)($_GET['id'] ?? 0);

// Check if this is an AJAX request
if (!isset($_GET['ajax']) || $_GET['ajax'] !== 'true') {
    echo json_encode(['error' => 'Invalid request type']);
    exit();
}

if ($user_id <= 0) {
    echo json_encode(['error' => 'Invalid user ID: ' . $user_id]);
    exit();
}

try {
    $db = getDB();
    
    // SQL query to get user details with role info
    $user_sql = "SELECT u.*, r.role_name 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ? AND u.username != ?";
    
    // Debug: Log the request
    error_log("AJAX request for user ID: " . $user_id);
    
    // Get user details
    $user = $db->fetch($user_sql, [$user_id, $exclude_username]);
    
    if (!$user) {
        echo json_encode(['error' => 'User not found with ID: ' . $user_id]);
        error_log("User not found: ID=" . $user_id);
    } else {
        echo json_encode(['user' => $user]);
        error_log("User loaded successfully via AJAX: ID=" . $user_id . ", Username=" . $user['username']);
    }
    
} catch (Exception $e) {
    $error_msg = 'Error loading user details: ' . $e->getMessage();
    echo json_encode(['error' => $error_msg]);
    error_log("User edit AJAX error: " . $e->getMessage() . " | User ID: " . $user_id);
}

exit();
?> 