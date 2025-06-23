<?php
// Simple test to see what the AJAX endpoint returns
$url = "http://localhost/Capstone_project/pages/edit_student_ajax.php?id=1&ajax=true";

echo "Testing URL: $url\n\n";

// Use file_get_contents to get the raw response
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: Test Script'
    ]
]);

$response = file_get_contents($url, false, $context);

echo "Response length: " . strlen($response) . " characters\n\n";
echo "Raw response (first 1000 characters):\n";
echo "----------------------------------------\n";
echo substr($response, 0, 1000);
echo "\n----------------------------------------\n\n";

// Check for BOM characters
$bom = pack('H*','EFBBBF');
if (strpos($response, $bom) === 0) {
    echo "BOM detected at start of response!\n";
    $response = substr($response, 3);
}

// Try to find JSON start
$json_start = strpos($response, '{');
if ($json_start !== false) {
    echo "JSON starts at position: $json_start\n";
    $json_part = substr($response, $json_start);
    echo "JSON part: $json_part\n\n";
    
    $decoded = json_decode($json_part, true);
    if ($decoded) {
        echo "JSON parsed successfully!\n";
        print_r($decoded);
    } else {
        echo "JSON parsing failed: " . json_last_error_msg() . "\n";
    }
} else {
    echo "No JSON found in response\n";
}
?> 