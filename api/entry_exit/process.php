<?php
require_once '../config.php';

// Validate API key
validate_api_key();

$method = get_request_method();

if ($method === 'POST') {
    $input = get_request_body();
    $input = sanitize_api_input($input);
    
    // Validate required fields
    validate_required_fields($input, ['card_number', 'gate_number']);
    
    $card_number = $input['card_number'];
    $gate_number = validate_gate_number($input['gate_number']);
    $security_officer_id = $input['security_officer_id'] ?? null;
    $notes = $input['notes'] ?? null;
    $entry_method = $input['entry_method'] ?? 'rfid';
    
    try {
        // Validate RFID card and get student information
        $card = validate_rfid_card($card_number);
        $student = get_student_by_card($card_number);
        
        if (!$student) {
            api_error('Student not found for this card', 404);
        }
        
        // Check current student status
        $current_status = check_student_status($student['student_id']);
        
        // Determine if this is an entry or exit
        $is_entry = $current_status['status'] === 'outside';
        
        // Prepare log data
        $log_data = [
            'user_id' => $student['user_id'],
            'rfid_card_id' => $student['card_id'],
            'gate_number' => $gate_number,
            'security_officer_id' => $security_officer_id,
            'entry_method' => $entry_method,
            'notes' => $notes
        ];
        
        if ($is_entry) {
            // Student is entering
            $log_data['entry_time'] = date('Y-m-d H:i:s');
            $log_data['status'] = 'entered';
            
            $log_id = log_entry_exit($log_data);
            
            api_success([
                'action' => 'entry',
                'log_id' => $log_id,
                'student' => [
                    'id' => $student['student_id'],
                    'registration_number' => $student['registration_number'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'email' => $student['email'],
                    'department' => $student['department'],
                    'program' => $student['program'],
                    'year_of_study' => $student['year_of_study'],
                    'phone' => $student['phone']
                ],
                'card' => [
                    'number' => $student['card_number'],
                    'type' => $student['card_type']
                ],
                'entry_time' => $log_data['entry_time'],
                'gate_number' => $gate_number,
                'message' => 'Entry successful'
            ], 'Entry recorded successfully');
            
        } else {
            // Student is exiting
            $log_data['exit_time'] = date('Y-m-d H:i:s');
            $log_data['status'] = 'exited';
            
            $log_id = log_entry_exit($log_data);
            
            api_success([
                'action' => 'exit',
                'log_id' => $log_id,
                'student' => [
                    'id' => $student['student_id'],
                    'registration_number' => $student['registration_number'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'email' => $student['email'],
                    'department' => $student['department'],
                    'program' => $student['program'],
                    'year_of_study' => $student['year_of_study'],
                    'phone' => $student['phone']
                ],
                'card' => [
                    'number' => $student['card_number'],
                    'type' => $student['card_type']
                ],
                'exit_time' => $log_data['exit_time'],
                'gate_number' => $gate_number,
                'previous_entry' => $current_status['entry_time'] ?? null,
                'message' => 'Exit successful'
            ], 'Exit recorded successfully');
        }
        
    } catch (Exception $e) {
        api_error('Processing error: ' . $e->getMessage(), 500);
    }
    
} else {
    api_error('Method not allowed', 405);
}
?> 