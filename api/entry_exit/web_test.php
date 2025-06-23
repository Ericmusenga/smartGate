<?php
// Simple web test
echo "=== Web Server Test ===\n";
echo "If you can see this, the web server is working!\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "\n=== File Paths ===\n";
echo "Current file: " . __FILE__ . "\n";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "\n=== Test Complete ===\n";
echo "All systems are working correctly!\n";
?> 