<?php
require_once 'config.php';

// Simple test endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    api_success([
        'message' => 'API is working correctly',
        'version' => '1.0.0',
        'endpoints' => [
            'entry_exit/process.php' => 'Process RFID card entry/exit',
            'entry_exit/manual.php' => 'Manual entry/exit logging',
            'entry_exit/student_info.php' => 'Get student information by card',
            'entry_exit/logs.php' => 'Get entry/exit logs',
            'entry_exit/status.php' => 'Get campus status'
        ],
        'valid_api_keys' => [
            'gate_system_2024',
            'security_api_key', 
            'admin_api_key'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], 'API Test Successful');
} else {
    api_error('Method not allowed', 405);
}
?> 