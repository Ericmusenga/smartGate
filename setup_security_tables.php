<?php
require_once 'config/config.php';

echo "<h2>Security Dashboard Setup</h2>";

try {
    $db = getDB();
    
    // Check if visitors table exists
    $result = $db->fetch("SHOW TABLES LIKE 'visitors'");
    if (!$result) {
        echo "<p>Creating visitors table...</p>";
        
        $db->query("
            CREATE TABLE visitors (
                id INT PRIMARY KEY AUTO_INCREMENT,
                visitor_name VARCHAR(100) NOT NULL,
                telephone VARCHAR(20) NOT NULL,
                email VARCHAR(100) NULL,
                purpose VARCHAR(100) NOT NULL,
                person_to_visit VARCHAR(100) NOT NULL,
                department VARCHAR(100) NULL,
                id_number VARCHAR(50) NULL,
                notes TEXT NULL,
                status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Create indexes
        $db->query("CREATE INDEX idx_visitors_created_at ON visitors(created_at)");
        $db->query("CREATE INDEX idx_visitors_status ON visitors(status)");
        $db->query("CREATE INDEX idx_visitors_purpose ON visitors(purpose)");
        $db->query("CREATE INDEX idx_visitors_department ON visitors(department)");
        
        echo "<p style='color: green;'>✓ Visitors table created successfully!</p>";
    } else {
        echo "<p style='color: blue;'>✓ Visitors table already exists.</p>";
    }
    
    // Check if entry_exit_logs table exists
    $result = $db->fetch("SHOW TABLES LIKE 'entry_exit_logs'");
    if (!$result) {
        echo "<p style='color: red;'>✗ Entry/Exit logs table is missing. Please run the main database setup first.</p>";
    } else {
        echo "<p style='color: blue;'>✓ Entry/Exit logs table exists.</p>";
    }
    
    // Check if students table exists
    $result = $db->fetch("SHOW TABLES LIKE 'students'");
    if (!$result) {
        echo "<p style='color: red;'>✗ Students table is missing. Please run the main database setup first.</p>";
    } else {
        echo "<p style='color: blue;'>✓ Students table exists.</p>";
    }
    
    // Check if users table exists
    $result = $db->fetch("SHOW TABLES LIKE 'users'");
    if (!$result) {
        echo "<p style='color: red;'>✗ Users table is missing. Please run the main database setup first.</p>";
    } else {
        echo "<p style='color: blue;'>✓ Users table exists.</p>";
    }
    
    // Check if devices table exists
    $result = $db->fetch("SHOW TABLES LIKE 'devices'");
    if (!$result) {
        echo "<p style='color: red;'>✗ Devices table is missing. Please run the main database setup first.</p>";
    } else {
        echo "<p style='color: blue;'>✓ Devices table exists.</p>";
    }
    
    // Check if rfid_cards table exists
    $result = $db->fetch("SHOW TABLES LIKE 'rfid_cards'");
    if (!$result) {
        echo "<p style='color: red;'>✗ RFID cards table is missing. Please run the main database setup first.</p>";
    } else {
        echo "<p style='color: blue;'>✓ RFID cards table exists.</p>";
    }
    
    // Check if roles table exists
    $result = $db->fetch("SHOW TABLES LIKE 'roles'");
    if (!$result) {
        echo "<p style='color: red;'>✗ Roles table is missing. Please run the main database setup first.</p>";
    } else {
        echo "<p style='color: blue;'>✓ Roles table exists.</p>";
    }
    
    // Check if security role exists
    $result = $db->fetch("SELECT * FROM roles WHERE role_name = 'security'");
    if (!$result) {
        echo "<p style='color: red;'>✗ Security role is missing. Please run the main database setup first.</p>";
    } else {
        echo "<p style='color: blue;'>✓ Security role exists.</p>";
    }
    
    echo "<hr>";
    echo "<h3>Setup Complete!</h3>";
    echo "<p>The security dashboard should now work properly.</p>";
    echo "<p><a href='pages/dashboard_security.php'>Go to Security Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 