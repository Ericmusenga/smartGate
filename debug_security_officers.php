<?php
require_once 'config/config.php';

echo "<h1>Security Officers Debug</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test 1: Check if security_officers table exists
    echo "<h2>Test 1: Check security_officers table</h2>";
    try {
        $result = $db->fetch("SELECT COUNT(*) as count FROM security_officers");
        echo "<p>✅ Security officers table exists. Count: " . $result['count'] . "</p>";
        
        if ($result['count'] > 0) {
            // Show sample data
            $sample = $db->fetch("SELECT * FROM security_officers LIMIT 1");
            echo "<h3>Sample Security Officer:</h3>";
            echo "<pre>";
            print_r($sample);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error with security_officers table: " . $e->getMessage() . "</p>";
    }
    
    // Test 2: Check if users table exists
    echo "<h2>Test 2: Check users table</h2>";
    try {
        $result = $db->fetch("SELECT COUNT(*) as count FROM users");
        echo "<p>✅ Users table exists. Count: " . $result['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error with users table: " . $e->getMessage() . "</p>";
    }
    
    // Test 3: Test the exact query from security_officers.php
    echo "<h2>Test 3: Test main query</h2>";
    try {
        $sql = "SELECT * FROM security_officers ORDER BY created_at DESC LIMIT 5";
        $officers = $db->fetchAll($sql);
        echo "<p>✅ Main query successful. Found " . count($officers) . " officers.</p>";
        
        if (count($officers) > 0) {
            echo "<h3>All Security Officers:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Security Code</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Created</th></tr>";
            foreach ($officers as $officer) {
                echo "<tr>";
                echo "<td>" . $officer['id'] . "</td>";
                echo "<td>" . htmlspecialchars($officer['security_code']) . "</td>";
                echo "<td>" . htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($officer['email']) . "</td>";
                echo "<td>" . htmlspecialchars($officer['phone'] ?? 'N/A') . "</td>";
                echo "<td>" . ($officer['is_active'] ? 'Active' : 'Inactive') . "</td>";
                echo "<td>" . $officer['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error with main query: " . $e->getMessage() . "</p>";
    }
    
    // Test 4: Test user relationship
    echo "<h2>Test 4: Test user relationship</h2>";
    try {
        $sql = "SELECT so.*, u.username, u.is_active as user_active, u.last_login 
                FROM security_officers so 
                LEFT JOIN users u ON so.id = u.security_officer_id 
                ORDER BY so.created_at DESC LIMIT 3";
        $officers_with_users = $db->fetchAll($sql);
        echo "<p>✅ User relationship query successful. Found " . count($officers_with_users) . " officers with user data.</p>";
        
        if (count($officers_with_users) > 0) {
            echo "<h3>Officers with User Accounts:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Security Code</th><th>Name</th><th>Username</th><th>User Active</th><th>Last Login</th></tr>";
            foreach ($officers_with_users as $officer) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($officer['security_code']) . "</td>";
                echo "<td>" . htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($officer['username'] ?? 'No account') . "</td>";
                echo "<td>" . ($officer['user_active'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($officer['last_login'] ?? 'Never') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error with user relationship: " . $e->getMessage() . "</p>";
    }
    
    // Test 5: Check database structure
    echo "<h2>Test 5: Check table structure</h2>";
    try {
        $result = $db->fetchAll("DESCRIBE security_officers");
        echo "<p>✅ Security officers table structure:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($result as $field) {
            echo "<tr>";
            echo "<td>" . $field['Field'] . "</td>";
            echo "<td>" . $field['Type'] . "</td>";
            echo "<td>" . $field['Null'] . "</td>";
            echo "<td>" . $field['Key'] . "</td>";
            echo "<td>" . $field['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking table structure: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection error: " . $e->getMessage() . "</p>";
}
?> 