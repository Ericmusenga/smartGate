<?php
// Simple test version of logs.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Logs Test</h1>";

// Step 1: Check if we can access the database file
echo "<h2>Step 1: Database File Check</h2>";
$db_path = __DIR__ . '/../../config/database.php';
echo "Looking for database file at: " . $db_path . "<br>";

if (file_exists($db_path)) {
    echo "✅ Database file exists<br>";
} else {
    echo "❌ Database file NOT found<br>";
    echo "Current directory: " . __DIR__ . "<br>";
    echo "Files in current directory:<br>";
    $files = scandir(__DIR__);
    foreach ($files as $file) {
        echo "- $file<br>";
    }
    exit;
}

// Step 2: Try to include the database file
echo "<h2>Step 2: Database Connection Test</h2>";
try {
    require_once $db_path;
    echo "✅ Database file included successfully<br>";
    
    $db = getDB();
    echo "✅ Database connection created<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 3: Check if the table exists
echo "<h2>Step 3: Table Check</h2>";
try {
    $result = $db->fetchAll("SHOW TABLES LIKE 'entry_exit_logs'");
    if (count($result) > 0) {
        echo "✅ entry_exit_logs table exists<br>";
    } else {
        echo "❌ entry_exit_logs table does NOT exist<br>";
        echo "Available tables:<br>";
        $tables = $db->fetchAll("SHOW TABLES");
        foreach ($tables as $table) {
            echo "- " . array_values($table)[0] . "<br>";
        }
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
    exit;
}

// Step 4: Try a simple query
echo "<h2>Step 4: Simple Query Test</h2>";
try {
    $count = $db->fetch("SELECT COUNT(*) as total FROM entry_exit_logs");
    echo "✅ Total records: " . $count['total'] . "<br>";
    
    if ($count['total'] > 0) {
        $sample = $db->fetch("SELECT * FROM entry_exit_logs LIMIT 1");
        echo "✅ Sample record:<br>";
        echo "<pre>" . print_r($sample, true) . "</pre>";
    } else {
        echo "⚠️ No records found in table<br>";
    }
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 5: Test the complex query from logs.php
echo "<h2>Step 5: Complex Query Test</h2>";
try {
    $logs = $db->fetchAll("
        SELECT 
            eel.id,
            eel.entry_time,
            eel.exit_time,
            eel.gate_number,
            eel.entry_method,
            eel.status,
            eel.notes,
            eel.created_at,
            s.first_name,
            s.last_name,
            s.registration_number,
            s.department,
            s.program
        FROM entry_exit_logs eel 
        LEFT JOIN users u ON eel.user_id = u.id 
        LEFT JOIN students s ON u.student_id = s.id
        ORDER BY eel.created_at DESC 
        LIMIT 5
    ");
    
    echo "✅ Complex query successful<br>";
    echo "✅ Found " . count($logs) . " records<br>";
    
    if (count($logs) > 0) {
        echo "✅ First record:<br>";
        echo "<pre>" . print_r($logs[0], true) . "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Complex query failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>✅ All Tests Passed!</h2>";
echo "<p>The logs.php page should work. <a href='logs.php'>Try logs.php now</a></p>";
echo "<p><a href='create_test_data.php'>Create test data</a></p>";
?> 