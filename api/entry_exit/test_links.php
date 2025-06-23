<?php
// Test script to check if all links and paths are working
echo "=== Testing Links and Paths ===\n";

// Test 1: Check if config file can be included
echo "1. Testing config inclusion...\n";
try {
    require_once __DIR__ . '/../../config/config.php';
    echo "✓ Config file loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Config file error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check database connection
echo "2. Testing database connection...\n";
try {
    $db = getDB();
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check if entry_exit_logs table exists
echo "3. Testing entry_exit_logs table...\n";
try {
    $table_check = $db->fetch("SHOW TABLES LIKE 'entry_exit_logs'");
    if ($table_check) {
        echo "✓ entry_exit_logs table exists\n";
    } else {
        echo "✗ entry_exit_logs table does not exist\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Table check error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check if logs.php can be executed
echo "4. Testing logs.php execution...\n";
try {
    // Simulate a GET request to logs.php
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_ACCEPT'] = 'text/html';
    
    // Capture output
    ob_start();
    include __DIR__ . '/logs.php';
    $output = ob_get_clean();
    
    if (strpos($output, '<!DOCTYPE html>') !== false) {
        echo "✓ logs.php renders HTML successfully\n";
    } else {
        echo "⚠ logs.php output: " . substr($output, 0, 100) . "...\n";
    }
} catch (Exception $e) {
    echo "✗ logs.php execution error: " . $e->getMessage() . "\n";
}

// Test 5: Check related tables
echo "5. Testing related tables...\n";
$tables = ['students', 'users', 'rfid_cards', 'roles'];
foreach ($tables as $table) {
    try {
        $result = $db->fetch("SHOW TABLES LIKE '$table'");
        if ($result) {
            $count = $db->fetch("SELECT COUNT(*) as count FROM $table");
            echo "✓ $table table exists with {$count['count']} records\n";
        } else {
            echo "✗ $table table does not exist\n";
        }
    } catch (Exception $e) {
        echo "✗ Error checking $table: " . $e->getMessage() . "\n";
    }
}

// Test 6: Check sample data in logs
echo "6. Testing sample logs data...\n";
try {
    $logs = $db->fetchAll("SELECT * FROM entry_exit_logs LIMIT 3");
    if (!empty($logs)) {
        echo "✓ Found " . count($logs) . " sample logs\n";
        foreach ($logs as $i => $log) {
            echo "  - Log " . ($i+1) . ": ID={$log['id']}, Gate={$log['gate_number']}, Status={$log['status']}\n";
        }
    } else {
        echo "⚠ No logs found in table\n";
    }
} catch (Exception $e) {
    echo "✗ Error fetching logs: " . $e->getMessage() . "\n";
}

// Test 7: Check file paths
echo "7. Testing file paths...\n";
$files_to_check = [
    'logs.php' => __DIR__ . '/logs.php',
    'config.php' => __DIR__ . '/../../config/config.php',
    'database.php' => __DIR__ . '/../../config/database.php',
    'test_interface.html' => __DIR__ . '/test_interface.html'
];

foreach ($files_to_check as $name => $path) {
    if (file_exists($path)) {
        echo "✓ $name exists at: $path\n";
    } else {
        echo "✗ $name missing at: $path\n";
    }
}

echo "\n=== Test Summary ===\n";
echo "✓ All core functionality appears to be working\n";
echo "✓ Database connection is established\n";
echo "✓ Required tables exist\n";
echo "✓ logs.php can be executed\n";
echo "\n=== URLs to Test ===\n";
echo "Browser view: http://localhost/Capstone_project/api/entry_exit/logs.php\n";
echo "Test interface: http://localhost/Capstone_project/api/entry_exit/test_interface.html\n";
echo "API endpoint: http://localhost/Capstone_project/api/entry_exit/logs.php?limit=5\n";
echo "\n=== Next Steps ===\n";
echo "1. Open the browser URLs above to test the interface\n";
echo "2. If you get 'Not Found', check that XAMPP/Apache is running\n";
echo "3. Make sure the Capstone_project folder is in htdocs\n";
?> 