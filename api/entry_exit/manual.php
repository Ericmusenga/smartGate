<?php
require_once '../../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is security
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (get_user_type() !== 'security') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Security officers only.']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit;
}

// Get form data
$student_id = $_POST['student_id'] ?? '';
$action_type = $_POST['action_type'] ?? '';
$device_id = $_POST['device_id'] ?? null;
$notes = $_POST['notes'] ?? '';

// Validate required fields
if (empty($student_id) || empty($action_type)) {
    echo json_encode(['success' => false, 'message' => 'Student ID and action type are required']);
    exit;
}

if (!in_array($action_type, ['entry', 'exit'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action type. Must be "entry" or "exit"']);
    exit;
}

try {
    $pdo = get_pdo();
    
    // Check if student exists and is active
    $stmt = $pdo->prepare("
        SELECT id, registration_number, first_name, last_name, program
        FROM students 
        WHERE id = :id AND status = 'active'
    ");
    $stmt->execute(['id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found or inactive']);
        exit;
    }
    
    // Check if device exists (if provided)
    if (!empty($device_id)) {
        $stmt = $pdo->prepare("
            SELECT id, name, status
            FROM devices 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $device_id]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$device) {
            echo json_encode(['success' => false, 'message' => 'Device not found']);
            exit;
        }
        
        if ($device['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Device is not active']);
            exit;
        }
    }
    
    // Handle entry
    if ($action_type === 'entry') {
        // Check if student is already on campus (has entry without exit today)
        $stmt = $pdo->prepare("
            SELECT id FROM entry_exit_logs 
            WHERE student_id = :student_id 
            AND DATE(entry_time) = CURDATE() 
            AND exit_time IS NULL
        ");
        $stmt->execute(['student_id' => $student_id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Student is already on campus']);
            exit;
        }
        
        // Create new entry log
        $stmt = $pdo->prepare("
            INSERT INTO entry_exit_logs (
                student_id, device_id, entry_time, entry_method, 
                entry_notes, created_by, created_at
            ) VALUES (
                :student_id, :device_id, NOW(), 'manual', 
                :notes, :created_by, NOW()
            )
        ");
        
        $stmt->execute([
            'student_id' => $student_id,
            'device_id' => $device_id,
            'notes' => $notes,
            'created_by' => $_SESSION['user_id']
        ]);
        
        $log_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Entry recorded successfully',
            'log_id' => $log_id,
            'student' => $student,
            'action' => 'entry',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Handle exit
    else if ($action_type === 'exit') {
        // Find the most recent entry without exit for today
        $stmt = $pdo->prepare("
            SELECT id, entry_time 
            FROM entry_exit_logs 
            WHERE student_id = :student_id 
            AND DATE(entry_time) = CURDATE() 
            AND exit_time IS NULL
            ORDER BY entry_time DESC
            LIMIT 1
        ");
        $stmt->execute(['student_id' => $student_id]);
        $entry_log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry_log) {
            echo json_encode(['success' => false, 'message' => 'No active entry found for this student today']);
            exit;
        }
        
        // Update the entry log with exit time
        $stmt = $pdo->prepare("
            UPDATE entry_exit_logs 
            SET exit_time = NOW(), 
                exit_method = 'manual', 
                exit_notes = :notes,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :log_id
        ");
        
        $stmt->execute([
            'notes' => $notes,
            'updated_by' => $_SESSION['user_id'],
            'log_id' => $entry_log['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Exit recorded successfully',
            'log_id' => $entry_log['id'],
            'student' => $student,
            'action' => 'exit',
            'entry_time' => $entry_log['entry_time'],
            'exit_time' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in manual entry/exit API: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 