<?php
require_once 'config/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test if students table exists
    $result = $db->fetch("SELECT COUNT(*) as count FROM students");
    echo "<p style='color: green;'>✓ Students table accessible. Total students: " . $result['count'] . "</p>";
    
    // Test the specific query used in edit_student.php
    $test_sql = "SELECT s.*, 
                u.username, u.is_first_login, u.last_login,
                (SELECT COUNT(*) FROM devices d 
                 JOIN users u2 ON d.user_id = u2.id 
                 WHERE u2.student_id = s.id) as device_count
                FROM students s 
                LEFT JOIN users u ON s.id = u.student_id 
                WHERE s.id = ?";
    
    // Get first student for testing
    $first_student = $db->fetch("SELECT id FROM students LIMIT 1");
    if ($first_student) {
        $test_result = $db->fetch($test_sql, [$first_student['id']]);
        if ($test_result) {
            echo "<p style='color: green;'>✓ Edit student query works. Test student: " . $test_result['first_name'] . " " . $test_result['last_name'] . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Edit student query failed for student ID: " . $first_student['id'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No students found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>PHP Error Log Location:</h3>";
echo "<p>Check your PHP error log for detailed error messages. Common locations:</p>";
echo "<ul>";
echo "<li>XAMPP: C:\\xampp\\php\\logs\\php_error_log</li>";
echo "<li>Apache: C:\\xampp\\apache\\logs\\error.log</li>";
echo "</ul>";

echo "<h3>Recent Error Log Entries:</h3>";
$log_file = "C:\\xampp\\php\\logs\\php_error_log";
if (file_exists($log_file)) {
    $lines = file($log_file);
    $recent_lines = array_slice($lines, -10); // Last 10 lines
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    foreach ($recent_lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>Error log file not found at: " . $log_file . "</p>";
}
?> 