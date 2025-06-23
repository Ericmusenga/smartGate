<?php
/**
 * Test AJAX Response
 * This script helps debug what's being returned by AJAX requests
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing AJAX Response</h2>";

// Test the edit_student.php AJAX endpoint
$student_id = 1; // Test with first student
$url = "http://localhost/Capstone_project/pages/edit_student.php?id={$student_id}&ajax=true";

echo "<h3>Testing URL: {$url}</h3>";

// Method 1: Using cURL
echo "<h4>Method 1: cURL Test</h4>";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> {$http_code}</p>";
    echo "<p><strong>Response Length:</strong> " . strlen($response) . " characters</p>";
    echo "<p><strong>First 500 characters:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow: auto;'>";
    echo htmlspecialchars(substr($response, 0, 500));
    echo "</pre>";
    
    // Try to parse as JSON
    $json_start = strpos($response, '{');
    if ($json_start !== false) {
        $json_part = substr($response, $json_start);
        $decoded = json_decode($json_part, true);
        if ($decoded) {
            echo "<p style='color: green;'>✓ JSON parsed successfully</p>";
            echo "<pre>" . print_r($decoded, true) . "</pre>";
        } else {
            echo "<p style='color: red;'>✗ JSON parsing failed</p>";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ No JSON found in response</p>";
    }
} else {
    echo "<p style='color: orange;'>cURL not available</p>";
}

// Method 2: Using file_get_contents
echo "<h4>Method 2: file_get_contents Test</h4>";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: Test Script'
    ]
]);

try {
    $response2 = file_get_contents($url, false, $context);
    if ($response2 === false) {
        echo "<p style='color: red;'>✗ Failed to get response</p>";
    } else {
        echo "<p><strong>Response Length:</strong> " . strlen($response2) . " characters</p>";
        echo "<p><strong>First 500 characters:</strong></p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow: auto;'>";
        echo htmlspecialchars(substr($response2, 0, 500));
        echo "</pre>";
        
        // Try to parse as JSON
        $decoded2 = json_decode($response2, true);
        if ($decoded2) {
            echo "<p style='color: green;'>✓ JSON parsed successfully</p>";
            echo "<pre>" . print_r($decoded2, true) . "</pre>";
        } else {
            echo "<p style='color: red;'>✗ JSON parsing failed</p>";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
}

echo "<h3>Debugging Tips:</h3>";
echo "<ul>";
echo "<li>Check if there are any PHP warnings or notices being output</li>";
echo "<li>Look for BOM (Byte Order Mark) characters at the start of the file</li>";
echo "<li>Check if there's any whitespace before &lt;?php or after ?&gt;</li>";
echo "<li>Verify that the session is working properly</li>";
echo "</ul>";

echo "<p><a href='debug_ajax.php?action=test_student_query&id=1'>Test with debug_ajax.php</a></p>";
?> 