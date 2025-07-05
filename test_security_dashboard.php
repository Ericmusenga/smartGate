<?php
require_once 'config/config.php';

echo "<h2>Security Dashboard Test</h2>";
echo "<p>Testing database connectivity and required tables...</p>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test each table
    $tables = [
        'entry_exit_logs' => 'Entry/Exit Logs',
        'students' => 'Students',
        'users' => 'Users',
        'devices' => 'Devices',
        'rfid_cards' => 'RFID Cards',
        'visitors' => 'Visitors',
        'roles' => 'Roles'
    ];
    
    foreach ($tables as $table => $name) {
        try {
            $result = $db->fetch("SHOW TABLES LIKE '$table'");
            if ($result) {
                echo "<p style='color: green;'>✓ $name table exists</p>";
                
                // Count records
                $count = $db->fetch("SELECT COUNT(*) as count FROM $table");
                echo "<p style='color: blue;'>  - Records: " . $count['count'] . "</p>";
            } else {
                echo "<p style='color: red;'>✗ $name table missing</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error checking $name table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test specific queries
    echo "<hr><h3>Testing Dashboard Queries</h3>";
    
    // Test today's logs
    try {
        $result = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs WHERE DATE(created_at) = CURDATE()");
        echo "<p style='color: green;'>✓ Today's logs query: " . $result['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Today's logs query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test on campus count
    try {
        $result = $db->fetch("SELECT COUNT(DISTINCT user_id) as count FROM entry_exit_logs WHERE DATE(created_at) = CURDATE() AND status = 'entered'");
        echo "<p style='color: green;'>✓ On campus query: " . $result['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ On campus query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test recent entries
    try {
        $result = $db->fetchAll("
            SELECT eel.*, u.first_name, u.last_name, s.registration_number, s.department
            FROM entry_exit_logs eel
            LEFT JOIN users u ON eel.user_id = u.id
            LEFT JOIN students s ON u.student_id = s.id
            WHERE DATE(eel.created_at) = CURDATE() AND eel.status = 'entered'
            ORDER BY eel.created_at DESC LIMIT 5
        ");
        echo "<p style='color: green;'>✓ Recent entries query: " . count($result) . " records</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Recent entries query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test visitors
    try {
        $result = $db->fetch("SELECT COUNT(*) as count FROM visitors WHERE DATE(created_at) = CURDATE()");
        echo "<p style='color: green;'>✓ Today's visitors query: " . $result['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Today's visitors query failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>Test Complete!</h3>";
    echo "<p><a href='pages/dashboard_security.php'>Go to Security Dashboard</a></p>";
    echo "<p><a href='setup_security_tables.php'>Run Setup Script</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/config.php</p>";
}
?> 