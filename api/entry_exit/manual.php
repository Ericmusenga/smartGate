<?php
require_once '../../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is security
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (get_user_type() !== 'security') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Handle POST requests for manual entry/exit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_number = trim($_POST['registration_number'] ?? '');
    $status = $_POST['status'] ?? '';
    $gate_number = intval($_POST['gate_number'] ?? 0);
    
    if (empty($registration_number) || empty($status) || $gate_number <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (!in_array($status, ['entered', 'exited'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status. Must be "entered" or "exited"']);
        exit;
    }
    
    try {
        $db = getDB();
        
        // Find student by registration number
        $student = $db->fetch("
            SELECT s.*, u.id as user_id 
            FROM students s 
            LEFT JOIN users u ON s.id = u.student_id 
            WHERE s.registration_number = ?
        ", [$registration_number]);
        
        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found with registration number: ' . $registration_number]);
            exit;
        }
        
        if (!$student['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Student account not found. Please contact administrator.']);
            exit;
        }
        
        // Insert the log entry
        $db->query("
            INSERT INTO entry_exit_logs (user_id, status, gate_number, entry_method, created_at) 
            VALUES (?, ?, ?, 'manual', NOW())
        ", [$student['user_id'], $status, $gate_number]);
        
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($status) . ' recorded successfully for ' . $student['first_name'] . ' ' . $student['last_name']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error recording entry/exit: ' . $e->getMessage()]);
    }
    exit;
}

// For GET requests, return error
echo json_encode(['success' => false, 'message' => 'POST method required']);
?> 