<?php
require_once 'config/config.php';

echo "<h2>Users Management Test</h2>";

try {
    $db = getDB();
    
    // Test 1: Check all users including super user
    echo "<h3>Test 1: All Users (including super user)</h3>";
    $all_users = $db->fetchAll("SELECT u.id, u.username, u.email, u.first_name, u.last_name, r.role_name, u.is_active FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id");
    
    if (empty($all_users)) {
        echo "<p style='color: red;'>✗ No users found in database</p>";
    } else {
        echo "<p style='color: green;'>✓ Found " . count($all_users) . " users in database</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr>";
        foreach ($all_users as $user) {
            $row_style = ($user['username'] == 'superadmin') ? 'background-color: #ffe6e6;' : '';
            echo "<tr style='{$row_style}'>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role_name']}</td>";
            echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><small><em>Note: Super user (username: superadmin) is highlighted in red and will be excluded from management.</em></small></p>";
    }
    
    // Test 2: Check users excluding super user
    echo "<h3>Test 2: Users Excluding Super User (superadmin)</h3>";
    $exclude_username = 'superadmin';
    $filtered_users = $db->fetchAll("SELECT u.id, u.username, u.email, u.first_name, u.last_name, r.role_name, u.is_active 
                                    FROM users u 
                                    JOIN roles r ON u.role_id = r.id 
                                    WHERE u.username != ?
                                    ORDER BY u.id", [$exclude_username]);
    
    if (empty($filtered_users)) {
        echo "<p style='color: orange;'>⚠ No users found after excluding super user</p>";
    } else {
        echo "<p style='color: green;'>✓ Found " . count($filtered_users) . " users after excluding super user</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr>";
        foreach ($filtered_users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role_name']}</td>";
            echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 3: Test AJAX endpoint
    echo "<h3>Test 3: AJAX Endpoint Test</h3>";
    if (!empty($filtered_users)) {
        $test_user_id = $filtered_users[0]['id'];
        echo "<p>Testing AJAX endpoint with user ID: {$test_user_id}</p>";
        
        // Test the AJAX endpoint
        $url = "http://localhost/Capstone_project/pages/user_ajax.php?id={$test_user_id}&ajax=true";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: Test Script'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            echo "<p style='color: red;'>✗ Failed to access AJAX endpoint</p>";
        } else {
            $decoded = json_decode($response, true);
            if ($decoded) {
                if (isset($decoded['error'])) {
                    echo "<p style='color: orange;'>⚠ AJAX returned error: " . $decoded['error'] . "</p>";
                } else if (isset($decoded['user'])) {
                    echo "<p style='color: green;'>✓ AJAX endpoint working correctly</p>";
                    echo "<p>User loaded: " . $decoded['user']['username'] . " (" . $decoded['user']['first_name'] . " " . $decoded['user']['last_name'] . ")</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ AJAX endpoint returned invalid JSON</p>";
                echo "<p>Response: " . htmlspecialchars(substr($response, 0, 200)) . "</p>";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠ No users to test AJAX endpoint</p>";
    }
    
    // Test 4: Check roles
    echo "<h3>Test 4: Available Roles</h3>";
    $roles = $db->fetchAll("SELECT * FROM roles ORDER BY id");
    if (empty($roles)) {
        echo "<p style='color: red;'>✗ No roles found in database</p>";
    } else {
        echo "<p style='color: green;'>✓ Found " . count($roles) . " roles</p>";
        echo "<ul>";
        foreach ($roles as $role) {
            echo "<li>ID: {$role['id']} - {$role['role_name']}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>If you see users in Test 2, the Manage Users page should work correctly</li>";
echo "<li>If the AJAX endpoint returns an error, you may need to log in as admin first</li>";
echo "<li>The super user (username: superadmin) is automatically excluded from management</li>";
echo "<li>All other users including admin users can be managed normally</li>";
echo "</ul>";

echo "<p><a href='pages/users.php'>Go to Manage Users Page</a></p>";
?> 