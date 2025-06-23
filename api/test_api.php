<?php
// Test script for API endpoints
require_once 'config.php';

echo "<h1>API Test Results</h1>";

// Test API keys
$api_keys = ['gate_system_2024', 'security_api_key', 'admin_api_key'];

// Test endpoints
$endpoints = [
    'test.php' => 'GET',
    'entry_exit/student_info.php?card_number=RFID2023001' => 'GET',
    'entry_exit/status.php' => 'GET',
    'entry_exit/logs.php?limit=5' => 'GET'
];

// Test POST endpoints with sample data
$post_endpoints = [
    'entry_exit/process.php' => [
        'card_number' => 'RFID2023001',
        'gate_number' => 1
    ],
    'entry_exit/manual.php' => [
        'student_id' => 1,
        'gate_number' => 1,
        'action' => 'entry'
    ]
];

function testEndpoint($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    $headers = [
        'X-API-Key: gate_system_2024',
        'Content-Type: application/json'
    ];
    
    if ($method === 'POST' && $data) {
        $url = 'http://localhost/Capstone_project/api/' . $url;
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } else {
        $url = 'http://localhost/Capstone_project/api/' . $url;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => $http_code];
    }
    
    $json_response = json_decode($response, true);
    return [
        'success' => $json_response !== null,
        'http_code' => $http_code,
        'response' => $json_response,
        'raw_response' => $response
    ];
}

echo "<h2>Testing GET Endpoints</h2>";
foreach ($endpoints as $endpoint => $method) {
    echo "<h3>Testing: {$endpoint}</h3>";
    $result = testEndpoint($endpoint, $method);
    
    if (isset($result['error'])) {
        echo "<p style='color: red;'>Error: {$result['error']}</p>";
    } else {
        echo "<p>HTTP Code: {$result['http_code']}</p>";
        if ($result['success']) {
            echo "<p style='color: green;'>✓ Success</p>";
            echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p style='color: red;'>✗ Failed to parse JSON</p>";
            echo "<pre>{$result['raw_response']}</pre>";
        }
    }
    echo "<hr>";
}

echo "<h2>Testing POST Endpoints</h2>";
foreach ($post_endpoints as $endpoint => $data) {
    echo "<h3>Testing: {$endpoint}</h3>";
    echo "<p>Data: " . json_encode($data) . "</p>";
    
    $result = testEndpoint($endpoint, 'POST', $data);
    
    if (isset($result['error'])) {
        echo "<p style='color: red;'>Error: {$result['error']}</p>";
    } else {
        echo "<p>HTTP Code: {$result['http_code']}</p>";
        if ($result['success']) {
            echo "<p style='color: green;'>✓ Success</p>";
            echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p style='color: red;'>✗ Failed to parse JSON</p>";
            echo "<pre>{$result['raw_response']}</pre>";
        }
    }
    echo "<hr>";
}

echo "<h2>API Key Validation Test</h2>";
foreach ($api_keys as $key) {
    echo "<h3>Testing API Key: {$key}</h3>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Capstone_project/api/test.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-Key: {$key}",
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Code: {$http_code}</p>";
    if ($http_code === 200) {
        echo "<p style='color: green;'>✓ Valid API Key</p>";
    } else {
        echo "<p style='color: red;'>✗ Invalid API Key</p>";
    }
    echo "<pre>{$response}</pre>";
    echo "<hr>";
}

echo "<h2>Test Complete</h2>";
echo "<p>Check the results above to verify all API endpoints are working correctly.</p>";
?> 