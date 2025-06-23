<?php
// Simple verification script
echo "=== Link Verification ===\n";

// Check if files exist
$files = [
    'logs.php' => 'api/entry_exit/logs.php',
    'test_interface.html' => 'api/entry_exit/test_interface.html',
    'config.php' => 'config/config.php',
    'database.php' => 'config/database.php'
];

echo "Checking file existence:\n";
foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "✓ $name: EXISTS\n";
    } else {
        echo "✗ $name: MISSING\n";
    }
}

echo "\nChecking database connection:\n";
try {
    require_once 'config/database.php';
    $db = getDB();
    echo "✓ Database connection: OK\n";
    
    // Check logs table
    $result = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs");
    echo "✓ Logs table: OK ({$result['count']} records)\n";
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
echo "All links should be working correctly!\n";
echo "\n=== TEST URLs ===\n";
echo "1. http://localhost/Capstone_project/api/entry_exit/logs.php\n";
echo "2. http://localhost/Capstone_project/api/entry_exit/test_interface.html\n";
echo "\nIf you get 'Not Found' error:\n";
echo "1. Make sure XAMPP/Apache is running\n";
echo "2. Check that Capstone_project is in C:\\xampp\\htdocs\\\n";
echo "3. Try restarting Apache in XAMPP Control Panel\n";
?> 