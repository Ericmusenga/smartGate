<?php
// Simple test to verify web server access
echo "<h1>Web Server Test</h1>";
echo "<p>✅ PHP is working on the web server</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Current directory: " . __DIR__ . "</p>";

// Test database connection
echo "<h2>Database Test</h2>";
try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDB();
    echo "<p>✅ Database connection successful</p>";
    
    $count = $db->fetch("SELECT COUNT(*) as total FROM entry_exit_logs");
    echo "<p>✅ Total logs: " . $count['total'] . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Links</h2>";
echo "<p><a href='logs.php'>Go to logs.php</a></p>";
echo "<p><a href='simple_test_logs.php'>Run simple test</a></p>";
echo "<p><a href='create_test_data.php'>Create test data</a></p>";
?> 