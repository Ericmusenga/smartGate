<?php
require_once '../config.php';

// Validate API key
validate_api_key();

$method = get_request_method();

if ($method === 'GET') {
    $card_number = $_GET['card_number'] ?? null;
    
    if (!$card_number) {
        api_error('Card number is required');
    }
    
    try {
        // Get student information by card
        $student = get_student_by_card($card_number);
        
        if (!$student) {
            api_error('Student not found for this card', 404);
        }
        
        // Check current status
        $current_status = check_student_status($student['student_id']);
        
        // Get recent entry/exit logs
        $db = getDB();
        $recent_logs = $db->fetchAll("
            SELECT 
                eel.id,
                eel.entry_time,
                eel.exit_time,
                eel.gate_number,
                eel.entry_method,
                eel.status,
                eel.created_at
            FROM entry_exit_logs eel
            WHERE eel.user_id = ?
            ORDER BY eel.created_at DESC
            LIMIT 5
        ", [$student['user_id']]);
        
        // Get student's devices
        $devices = $db->fetchAll("
            SELECT 
                d.id,
                d.device_name,
                d.device_type,
                d.serial_number,
                d.brand,
                d.model,
                d.is_registered
            FROM devices d
            WHERE d.user_id = ? AND d.is_registered = 1
        ", [$student['user_id']]);
        
        api_success([
            'student' => [
                'id' => $student['student_id'],
                'registration_number' => $student['registration_number'],
                'name' => $student['first_name'] . ' ' . $student['last_name'],
                'email' => $student['email'],
                'phone' => $student['phone'],
                'department' => $student['department'],
                'program' => $student['program'],
                'year_of_study' => $student['year_of_study'],
                'gender' => $student['gender']
            ],
            'card' => [
                'id' => $student['card_id'],
                'number' => $student['card_number'],
                'type' => $student['card_type'],
                'is_active' => $student['card_active'],
                'expiry_date' => $student['expiry_date']
            ],
            'current_status' => $current_status,
            'devices' => $devices,
            'recent_logs' => $recent_logs
        ], 'Student information retrieved successfully');
        
    } catch (Exception $e) {
        api_error('Database error: ' . $e->getMessage(), 500);
    }
    
} else {
    api_error('Method not allowed', 405);
}
?> 