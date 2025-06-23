<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check user type - admins and security can access this
$user_type = get_user_type();
if (!in_array($user_type, ['admin', 'security'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Handle GET request (view device details)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        $device_id = (int)$_GET['id'];
        
        $sql = "SELECT d.*, u.username, u.first_name, u.last_name, u.email, r.role_name
                FROM devices d 
                JOIN users u ON d.user_id = u.id 
                JOIN roles r ON u.role_id = r.id
                WHERE d.id = ?";
        
        $device = $db->fetch($sql, [$device_id]);
        
        if (!$device) {
            echo json_encode(['error' => 'Device not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'device' => $device]);
        exit;
    }
    
    // Handle POST request (update device)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Only admins can edit devices
        if ($user_type !== 'admin') {
            echo json_encode(['error' => 'Only administrators can edit devices']);
            exit;
        }
        
        $device_id = (int)($_POST['id'] ?? 0);
        $device_name = sanitize_input($_POST['device_name'] ?? '');
        $device_type = sanitize_input($_POST['device_type'] ?? '');
        $serial_number = sanitize_input($_POST['serial_number'] ?? '');
        $brand = sanitize_input($_POST['brand'] ?? '');
        $model = sanitize_input($_POST['model'] ?? '');
        $color = sanitize_input($_POST['color'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $is_registered = isset($_POST['is_registered']) ? 1 : 0;
        
        // Validation
        if (!$device_id || !$device_name || !$device_type || !$serial_number) {
            echo json_encode(['error' => 'Please fill in all required fields']);
            exit;
        }
        
        // Check if device exists
        $existing_device = $db->fetch("SELECT id, device_name FROM devices WHERE id = ?", [$device_id]);
        if (!$existing_device) {
            echo json_encode(['error' => 'Device not found']);
            exit;
        }
        
        // Check for duplicate serial number (excluding current device)
        $duplicate = $db->fetch("SELECT id FROM devices WHERE serial_number = ? AND id != ?", [$serial_number, $device_id]);
        if ($duplicate) {
            echo json_encode(['error' => 'A device with this serial number already exists']);
            exit;
        }
        
        // Update device
        $db->query("UPDATE devices SET device_name = ?, device_type = ?, serial_number = ?, brand = ?, model = ?, color = ?, description = ?, is_registered = ? WHERE id = ?",
            [$device_name, $device_type, $serial_number, $brand, $model, $color, $description, $is_registered, $device_id]);
        
        echo json_encode(['success' => 'Device updated successfully']);
        exit;
    }
    
    // Invalid request
    echo json_encode(['error' => 'Invalid request']);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 