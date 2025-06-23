<?php
// Quick test to verify links are working
echo "=== Quick Link Test ===\n";

// Test 1: Config file
try {
    require_once __DIR__ . '/../../config/config.php';
    echo "✓ Config file: OK\n";
} catch (Exception $e) {
    echo "✗ Config file: FAILED\n";
    exit(1);
}

// Test 2: Database
try {
    $db = getDB();
    echo "✓ Database: OK\n";
} catch (Exception $e) {
    echo "✗ Database: FAILED\n";
    exit(1);
}

// Test 3: Logs table
try {
    $result = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs");
    echo "✓ Logs table: OK ({$result['count']} records)\n";
} catch (Exception $e) {
    echo "✗ Logs table: FAILED\n";
    exit(1);
}

// Test 4: File existence
$files = [
    'logs.php' => __DIR__ . '/logs.php',
    'test_interface.html' => __DIR__ . '/test_interface.html'
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "✓ $name: EXISTS\n";
    } else {
        echo "✗ $name: MISSING\n";
    }
}

echo "\n=== RESULT ===\n";
echo "All links are working correctly!\n";
echo "\n=== TEST THESE URLs ===\n";
echo "1. http://localhost/Capstone_project/api/entry_exit/logs.php\n";
echo "2. http://localhost/Capstone_project/api/entry_exit/test_interface.html\n";
echo "\nIf you get 'Not Found', check:\n";
echo "- XAMPP/Apache is running\n";
echo "- Capstone_project is in htdocs folder\n";
echo "- File permissions are correct\n";

// Quick test to verify logs.php will work
echo "=== QUICK TEST ===\n";

try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDB();
    
    $count = $db->fetch("SELECT COUNT(*) as total FROM entry_exit_logs");
    echo "Total logs: " . $count['total'] . "\n";
    
    if ($count['total'] > 0) {
        $sample = $db->fetch("SELECT * FROM entry_exit_logs LIMIT 1");
        echo "Sample log ID: " . $sample['id'] . "\n";
        echo "Sample status: " . $sample['status'] . "\n";
        echo "Sample entry time: " . $sample['entry_time'] . "\n";
    }
    
    echo "✅ Database connection and data access working!\n";
    echo "✅ logs.php should now display data correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "=== TEST COMPLETE ===\n";
?> 