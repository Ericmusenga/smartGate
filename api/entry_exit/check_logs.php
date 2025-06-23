<?php
// Simple check for logs table
echo "=== Checking Logs Table ===\n";

try {
    // Include main config which includes database
    require_once __DIR__ . '/../../config/config.php';
    $db = getDB();
    
    // Check if table exists
    $result = $db->fetch("SHOW TABLES LIKE 'entry_exit_logs'");
    if ($result) {
        echo "✓ entry_exit_logs table exists\n";
        
        // Count logs
        $count_result = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs");
        $count = $count_result['count'];
        echo "✓ Found $count logs in table\n";
        
        // Show sample data
        $sample_data = $db->fetchAll("SELECT * FROM entry_exit_logs LIMIT 3");
        if (!empty($sample_data)) {
            echo "✓ Sample data:\n";
            foreach ($sample_data as $row) {
                echo "  - ID: {$row['id']}, Gate: {$row['gate_number']}, Status: {$row['status']}\n";
            }
        } else {
            echo "⚠ No sample data found\n";
        }
        
    } else {
        echo "✗ entry_exit_logs table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?> 