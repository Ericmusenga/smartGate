<?php
// Debug version of logs.php - shows exactly what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 DEBUG: Entry/Exit Logs</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Step 1: Check if we can access the database file
echo "<h2>Step 1: Database File Check</h2>";
$db_path = __DIR__ . '/../../config/database.php';
echo "Looking for: " . $db_path . "<br>";

if (file_exists($db_path)) {
    echo "✅ Database file exists<br>";
} else {
    echo "❌ Database file NOT found<br>";
    echo "Current directory: " . __DIR__ . "<br>";
    exit;
}

// Step 2: Try to include database file
echo "<h2>Step 2: Database Connection</h2>";
try {
    require_once $db_path;
    echo "✅ Database file loaded<br>";
    
    $db = getDB();
    echo "✅ Database connection created<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 3: Check if table exists
echo "<h2>Step 3: Table Check</h2>";
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
    echo "❌ Table check error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 4: Count records
echo "<h2>Step 4: Record Count</h2>";
try {
    $count = $db->fetch("SELECT COUNT(*) as total FROM entry_exit_logs");
    echo "✅ Total records: " . $count['total'] . "<br>";
    
    if ($count['total'] == 0) {
        echo "⚠️ No records found - this is why you see nothing!<br>";
        echo "<a href='create_test_data.php' class='btn btn-primary'>Create Test Data</a><br>";
    }
} catch (Exception $e) {
    echo "❌ Count error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 5: Try to get actual data
echo "<h2>Step 5: Data Retrieval</h2>";
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
        echo "<h3>Sample Data:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Entry Time</th><th>Exit Time</th><th>Status</th><th>Student Name</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>" . $log['id'] . "</td>";
            echo "<td>" . ($log['entry_time'] ?? 'NULL') . "</td>";
            echo "<td>" . ($log['exit_time'] ?? 'NULL') . "</td>";
            echo "<td>" . ($log['status'] ?? 'NULL') . "</td>";
            echo "<td>" . (($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Data retrieval error: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>✅ Debug Complete!</h2>";
echo "<p><a href='logs.php'>Go to actual logs page</a></p>";
echo "<p><a href='create_test_data.php'>Create test data</a></p>";
?> 