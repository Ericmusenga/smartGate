<?php
// API Settings Configuration

// Valid API Keys (in production, store these in database)
$API_KEYS = [
    'gate_system_2024' => [
        'name' => 'Main Gate System',
        'permissions' => ['read', 'write', 'admin'],
        'rate_limit' => 1000, // requests per hour
        'active' => true
    ],
    'security_api_key' => [
        'name' => 'Security Officer API',
        'permissions' => ['read', 'write'],
        'rate_limit' => 500,
        'active' => true
    ],
    'admin_api_key' => [
        'name' => 'Administrator API',
        'permissions' => ['read', 'write', 'admin', 'delete'],
        'rate_limit' => 2000,
        'active' => true
    ],
    'device_api_key' => [
        'name' => 'Device Integration API',
        'permissions' => ['read', 'write'],
        'rate_limit' => 100,
        'active' => true
    ]
];

// Rate Limiting Settings
$RATE_LIMIT_SETTINGS = [
    'enabled' => true,
    'window' => 3600, // 1 hour in seconds
    'max_requests' => 1000,
    'storage_path' => '../temp/rate_limit/'
];

// Database Settings
$DB_SETTINGS = [
    'connection_timeout' => 30,
    'query_timeout' => 10,
    'max_connections' => 100
];

// Logging Settings
$LOGGING_SETTINGS = [
    'enabled' => true,
    'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
    'file_path' => '../logs/api.log',
    'max_file_size' => 10485760, // 10MB
    'max_files' => 5
];

// Security Settings
$SECURITY_SETTINGS = [
    'cors_enabled' => true,
    'allowed_origins' => ['*'], // In production, specify actual domains
    'max_request_size' => 1048576, // 1MB
    'session_timeout' => 3600, // 1 hour
    'password_min_length' => 8,
    'require_https' => false // Set to true in production
];

// Gate Configuration
$GATE_SETTINGS = [
    'max_gates' => 10,
    'default_gate' => 1,
    'gate_names' => [
        1 => 'Main Entrance',
        2 => 'Library Side',
        3 => 'Parking Area',
        4 => 'Sports Complex',
        5 => 'Student Center',
        6 => 'Faculty Building',
        7 => 'Cafeteria',
        8 => 'Dormitory',
        9 => 'Laboratory',
        10 => 'Emergency Exit'
    ]
];

// Card Settings
$CARD_SETTINGS = [
    'max_card_length' => 20,
    'min_card_length' => 5,
    'card_prefixes' => ['RFID', 'CARD', 'STUDENT'],
    'default_expiry_years' => 4,
    'auto_deactivate_expired' => true
];

// Notification Settings
$NOTIFICATION_SETTINGS = [
    'email_enabled' => false,
    'sms_enabled' => false,
    'push_enabled' => false,
    'admin_email' => 'admin@university.edu',
    'security_email' => 'security@university.edu'
];

// Cache Settings
$CACHE_SETTINGS = [
    'enabled' => true,
    'driver' => 'file', // file, redis, memcached
    'ttl' => 300, // 5 minutes
    'path' => '../temp/cache/'
];

// Error Reporting
$ERROR_SETTINGS = [
    'display_errors' => false, // Set to false in production
    'log_errors' => true,
    'error_reporting' => E_ALL & ~E_DEPRECATED & ~E_STRICT
];

// API Version
$API_VERSION = '1.0.0';

// Helper Functions
function getApiKeyInfo($key) {
    global $API_KEYS;
    return $API_KEYS[$key] ?? null;
}

function isApiKeyValid($key) {
    $info = getApiKeyInfo($key);
    return $info && $info['active'];
}

function hasPermission($key, $permission) {
    $info = getApiKeyInfo($key);
    return $info && in_array($permission, $info['permissions']);
}

function getRateLimit($key) {
    $info = getApiKeyInfo($key);
    return $info ? $info['rate_limit'] : 100;
}

function getGateName($gateNumber) {
    global $GATE_SETTINGS;
    return $GATE_SETTINGS['gate_names'][$gateNumber] ?? "Gate $gateNumber";
}

function validateGateNumber($gateNumber) {
    global $GATE_SETTINGS;
    return $gateNumber >= 1 && $gateNumber <= $GATE_SETTINGS['max_gates'];
}

function validateCardNumber($cardNumber) {
    global $CARD_SETTINGS;
    $length = strlen($cardNumber);
    return $length >= $CARD_SETTINGS['min_card_length'] && 
           $length <= $CARD_SETTINGS['max_card_length'];
}

// Load settings into global scope
function loadApiSettings() {
    global $API_KEYS, $RATE_LIMIT_SETTINGS, $DB_SETTINGS, $LOGGING_SETTINGS;
    global $SECURITY_SETTINGS, $GATE_SETTINGS, $CARD_SETTINGS;
    global $NOTIFICATION_SETTINGS, $CACHE_SETTINGS, $ERROR_SETTINGS;
    
    // Apply error reporting settings
    error_reporting($ERROR_SETTINGS['error_reporting']);
    ini_set('display_errors', $ERROR_SETTINGS['display_errors']);
    ini_set('log_errors', $ERROR_SETTINGS['log_errors']);
    
    // Create necessary directories
    $directories = [
        $RATE_LIMIT_SETTINGS['storage_path'],
        $LOGGING_SETTINGS['file_path'],
        $CACHE_SETTINGS['path']
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Initialize settings
loadApiSettings();
?> 