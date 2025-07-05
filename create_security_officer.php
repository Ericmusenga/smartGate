<?php
require_once 'config/config.php';

echo "<h2>Creating Security Officer Account</h2>";

try {
    $db = getDB();
    
    // Security officer details
    $security_code = 'SEC001';
    $first_name = 'John';
    $last_name = 'Doe';
    $email = 'john.doe@security.com';
    $phone = '1234567890';
    $password = $security_code; // Default password is the security code
    
    echo "<p>Creating security officer with code: $security_code</p>";
    
    // Check if security officer already exists
    $existing_officer = $db->fetch("SELECT id FROM security_officers WHERE security_code = ?", [$security_code]);
    
    if ($existing_officer) {
        echo "<p style='color: orange;'>Security officer $security_code already exists!</p>";
        $officer_id = $existing_officer['id'];
    } else {
        // Insert security officer
        $db->query("INSERT INTO security_officers (security_code, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?)",
            [$security_code, $first_name, $last_name, $email, $phone]);
        
        $officer_id = $db->lastInsertId();
        echo "<p style='color: green;'>Security officer created with ID: $officer_id</p>";
    }
    
    // Get security role ID
    $role = $db->fetch("SELECT id FROM roles WHERE role_name = 'security'");
    
    if (!$role) {
        echo "<p style='color: red;'>Error: Security role not found in roles table!</p>";
        exit;
    }
    
    $role_id = $role['id'];
    echo "<p>Found security role with ID: $role_id</p>";
    
    // Check if user account already exists
    $existing_user = $db->fetch("SELECT id FROM users WHERE username = ?", [$security_code]);
    
    if ($existing_user) {
        echo "<p style='color: orange;'>User account for $security_code already exists!</p>";
    } else {
        // Create user account
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $db->query("INSERT INTO users (username, password, email, first_name, last_name, role_id, security_officer_id, is_first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$security_code, $hashed_password, $email, $first_name, $last_name, $role_id, $officer_id, TRUE]);
        
        echo "<p style='color: green;'>User account created successfully!</p>";
    }
    
    // Display login information
    echo "<hr>";
    echo "<h3>Login Information:</h3>";
    echo "<p><strong>Username:</strong> $security_code</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>Login URL:</strong> <a href='login.php'>login.php</a></p>";
    
    // Verify the account
    echo "<hr>";
    echo "<h3>Verification:</h3>";
    
    $user = $db->fetch("
        SELECT u.*, r.role_name, so.security_code 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        LEFT JOIN security_officers so ON u.security_officer_id = so.id 
        WHERE u.username = ?
    ", [$security_code]);
    
    if ($user) {
        echo "<p style='color: green;'>✓ User account verified!</p>";
        echo "<p><strong>Name:</strong> {$user['first_name']} {$user['last_name']}</p>";
        echo "<p><strong>Role:</strong> {$user['role_name']}</p>";
        echo "<p><strong>Security Code:</strong> {$user['security_code']}</p>";
        echo "<p><strong>Email:</strong> {$user['email']}</p>";
    } else {
        echo "<p style='color: red;'>✗ User account verification failed!</p>";
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. Go to <a href='login.php'>login.php</a></p>";
    echo "<p>2. Login with username: <strong>$security_code</strong> and password: <strong>$password</strong></p>";
    echo "<p>3. You should be redirected to the security dashboard</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
hr { margin: 20px 0; border: 1px solid #ddd; }
</style> 