<?php
// Test script to debug logs.php issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Database Connection and Logs Table</h2>";

// Test 1: Check if database file exists
echo "<h3>1. Checking database configuration file...</h3>";
$db_file = __DIR__ . '/../../config/database.php';
if (file_exists($db_file)) {
    echo "✅ Database config file exists: " . $db_file . "<br>";
} else {
    echo "❌ Database config file NOT found: " . $db_file . "<br>";
    exit;
}

// Test 2: Try to include database file
echo "<h3>2. Testing database connection...</h3>";
try {
    require_once $db_file;
    echo "✅ Database config file loaded successfully<br>";
    
    $db = getDB();
    echo "✅ Database connection established<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 3: Check if entry_exit_logs table exists
echo "<h3>3. Checking entry_exit_logs table...</h3>";
try {
    $tables = $db->fetchAll("SHOW TABLES LIKE 'entry_exit_logs'");
    if (count($tables) > 0) {
        echo "✅ entry_exit_logs table exists<br>";
    } else {
        echo "❌ entry_exit_logs table does NOT exist<br>";
        echo "Available tables:<br>";
        $all_tables = $db->fetchAll("SHOW TABLES");
        foreach ($all_tables as $table) {
            echo "- " . array_values($table)[0] . "<br>";
        }
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
    exit;
}

// Test 4: Check table structure
echo "<h3>4. Checking table structure...</h3>";
try {
    $columns = $db->fetchAll("DESCRIBE entry_exit_logs");
    echo "✅ Table structure:<br>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} catch (Exception $e) {
    echo "❌ Error checking table structure: " . $e->getMessage() . "<br>";
    exit;
}

// Test 5: Check if there's any data
echo "<h3>5. Checking for data...</h3>";
try {
    $count = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs");
    echo "✅ Total records in entry_exit_logs: " . $count['count'] . "<br>";
    
    if ($count['count'] > 0) {
        $sample = $db->fetch("SELECT * FROM entry_exit_logs LIMIT 1");
        echo "✅ Sample record:<br>";
        echo "<pre>" . print_r($sample, true) . "</pre>";
    } else {
        echo "⚠️ No data found in entry_exit_logs table<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking data: " . $e->getMessage() . "<br>";
    exit;
}

// Test 6: Test the actual query from logs.php
echo "<h3>6. Testing the logs.php query...</h3>";
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
    
    echo "✅ Query executed successfully<br>";
    echo "✅ Found " . count($logs) . " records<br>";
    
    if (count($logs) > 0) {
        echo "✅ Sample result:<br>";
        echo "<pre>" . print_r($logs[0], true) . "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>✅ All tests passed! The logs.php page should work.</h3>";
echo "<p><a href='logs.php'>Go to logs.php</a></p>";
?> 