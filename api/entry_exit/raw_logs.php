<?php
// Raw logs - simplest possible version
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>RAW LOGS DATA</h1>";

try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDB();
    
    // Simple query - just get everything from the table
    $logs = $db->fetchAll("SELECT * FROM entry_exit_logs ORDER BY created_at DESC LIMIT 10");
    
    echo "<h2>Total Records: " . count($logs) . "</h2>";
    
    if (count($logs) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        
        // Headers
        foreach (array_keys($logs[0]) as $header) {
            echo "<th style='padding: 5px;'>" . $header . "</th>";
        }
        echo "</tr>";
        
        // Data rows
        foreach ($logs as $log) {
            echo "<tr>";
            foreach ($log as $value) {
                echo "<td style='padding: 5px;'>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>NO DATA FOUND!</p>";
        echo "<p>Click here to create test data: <a href='create_test_data.php'>Create Test Data</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='logs.php'>Go to styled logs page</a></p>";
echo "<p><a href='debug_logs.php'>Run debug test</a></p>";
?> 