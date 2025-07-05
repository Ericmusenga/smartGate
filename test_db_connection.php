<?php
require_once 'config/config.php';

echo "<h1>Database Connection Test</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test basic query
    $result = $db->fetch("SELECT COUNT(*) as count FROM security_officers");
    echo "<p>✅ Security officers table exists. Count: " . $result['count'] . "</p>";
    
    // Test users table
    $result = $db->fetch("SELECT COUNT(*) as count FROM users");
    echo "<p>✅ Users table exists. Count: " . $result['count'] . "</p>";
    
    // Test if entry_exit_logs table exists
    try {
        $result = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs");
        echo "<p>✅ Entry/exit logs table exists. Count: " . $result['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Entry/exit logs table does not exist or has issues: " . $e->getMessage() . "</p>";
    }
    
    // Test the actual query from security_officers.php
    echo "<h2>Testing Security Officers Query</h2>";
    $sql = "SELECT so.*, 
                   u.username, u.is_active as user_active, u.last_login
            FROM security_officers so
            LEFT JOIN users u ON so.id = u.security_officer_id
            ORDER BY so.created_at DESC
            LIMIT 5";
    
    $officers = $db->fetchAll($sql);
    echo "<p>✅ Security officers query successful. Found " . count($officers) . " officers.</p>";
    
    if (count($officers) > 0) {
        echo "<h3>Sample Data:</h3>";
        echo "<pre>";
        print_r($officers[0]);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?> 