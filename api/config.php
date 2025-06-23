<?php
// API Configuration and Common Functions
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include main config
require_once '../config/config.php';

// API Response Functions
function api_response($data = null, $status = 200, $message = 'Success') {
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function api_error($message = 'Error', $status = 400) {
    api_response(null, $status, $message);
}

function api_success($data = null, $message = 'Success') {
    api_response($data, 200, $message);
}

// API Authentication Functions
function validate_api_key() {
    $headers = getallheaders();
    $api_key = $headers['X-API-Key'] ?? $_GET['api_key'] ?? null;
    
    if (!$api_key) {
        api_error('API key required', 401);
    }
    
    // For now, use a simple API key validation
    // In production, you should store API keys in database
    $valid_keys = [
        'gate_system_2024',
        'security_api_key',
        'admin_api_key'
    ];
    
    if (!in_array($api_key, $valid_keys)) {
        api_error('Invalid API key', 401);
    }
    
    return true;
}

// Input Validation Functions
function validate_rfid_card($card_number) {
    if (empty($card_number)) {
        api_error('Card number is required');
    }
    
    try {
        $db = getDB();
        $card = $db->fetch("
            SELECT rc.*, s.registration_number, s.first_name, s.last_name, s.email, 
                   s.department, s.program, s.year_of_study, s.phone
            FROM rfid_cards rc 
            JOIN students s ON rc.student_id = s.id
            WHERE rc.card_number = ? AND rc.is_active = 1
        ", [$card_number]);
        
        if (!$card) {
            api_error('Invalid or inactive card', 404);
        }
        
        // Check if card is expired
        if ($card['expiry_date'] && strtotime($card['expiry_date']) < time()) {
            api_error('Card has expired', 403);
        }
        
        return $card;
    } catch (Exception $e) {
        api_error('Database error: ' . $e->getMessage(), 500);
    }
}

function validate_gate_number($gate_number) {
    $gate_number = (int)$gate_number;
    if ($gate_number < 1 || $gate_number > 10) { // Assuming max 10 gates
        api_error('Invalid gate number');
    }
    return $gate_number;
}

function validate_security_officer($officer_id) {
    if (!$officer_id) {
        return null; // Optional field
    }
    
    try {
        $db = getDB();
        $officer = $db->fetch("
            SELECT u.id, u.username, u.first_name, u.last_name, u.email
            FROM users u 
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? AND r.role_name = 'security' AND u.is_active = 1
        ", [$officer_id]);
        
        if (!$officer) {
            api_error('Invalid security officer', 404);
        }
        
        return $officer;
    } catch (Exception $e) {
        api_error('Database error: ' . $e->getMessage(), 500);
    }
}

// Log Entry/Exit Function
function log_entry_exit($data) {
    try {
        $db = getDB();
        
        $sql = "INSERT INTO entry_exit_logs (
            user_id, device_id, rfid_card_id, entry_time, exit_time, 
            gate_number, security_officer_id, entry_method, notes, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['user_id'] ?? null,
            $data['device_id'] ?? null,
            $data['rfid_card_id'] ?? null,
            $data['entry_time'] ?? null,
            $data['exit_time'] ?? null,
            $data['gate_number'],
            $data['security_officer_id'] ?? null,
            $data['entry_method'] ?? 'rfid',
            $data['notes'] ?? null,
            $data['status'] ?? 'entered'
        ];
        
        $db->query($sql, $params);
        return $db->lastInsertId();
    } catch (Exception $e) {
        api_error('Error logging entry/exit: ' . $e->getMessage(), 500);
    }
}

// Get Student Information by Card
function get_student_by_card($card_number) {
    try {
        $db = getDB();
        $student = $db->fetch("
            SELECT 
                s.id as student_id,
                s.registration_number,
                s.first_name,
                s.last_name,
                s.email,
                s.phone,
                s.department,
                s.program,
                s.year_of_study,
                s.gender,
                rc.id as card_id,
                rc.card_number,
                rc.card_type,
                rc.is_active as card_active,
                rc.expiry_date,
                u.id as user_id,
                u.username,
                u.is_active as user_active
            FROM rfid_cards rc 
            JOIN students s ON rc.student_id = s.id
            LEFT JOIN users u ON s.id = u.student_id
            WHERE rc.card_number = ? AND rc.is_active = 1
        ", [$card_number]);
        
        return $student;
    } catch (Exception $e) {
        api_error('Database error: ' . $e->getMessage(), 500);
    }
}

// Check if student is already inside
function check_student_status($student_id) {
    try {
        $db = getDB();
        $last_log = $db->fetch("
            SELECT id, entry_time, exit_time, status, gate_number
            FROM entry_exit_logs 
            WHERE user_id = (SELECT id FROM users WHERE student_id = ?)
            ORDER BY created_at DESC 
            LIMIT 1
        ", [$student_id]);
        
        if (!$last_log) {
            return ['status' => 'outside', 'message' => 'Student not found in recent logs'];
        }
        
        if ($last_log['status'] === 'entered' && !$last_log['exit_time']) {
            return [
                'status' => 'inside',
                'message' => 'Student is currently inside',
                'entry_time' => $last_log['entry_time'],
                'gate_number' => $last_log['gate_number']
            ];
        }
        
        return ['status' => 'outside', 'message' => 'Student is outside'];
    } catch (Exception $e) {
        api_error('Database error: ' . $e->getMessage(), 500);
    }
}

// Sanitize API input
function sanitize_api_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_api_input', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Get request method
function get_request_method() {
    return $_SERVER['REQUEST_METHOD'];
}

// Get request body
function get_request_body() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

// Validate required fields
function validate_required_fields($data, $required_fields) {
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            api_error("Missing required field: {$field}");
        }
    }
    return true;
}
?> 