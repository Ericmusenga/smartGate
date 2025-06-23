<?php
require_once '../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = get_pdo();
    
    switch ($action) {
        case 'list':
            // Get list of students for dropdowns
            $stmt = $pdo->prepare("
                SELECT id, registration_number, first_name, last_name, program, email, phone
                FROM students 
                WHERE status = 'active'
                ORDER BY first_name, last_name
            ");
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'students' => $students
            ]);
            break;
            
        case 'search':
            // Search students by registration number or name
            $search = $_GET['q'] ?? '';
            if (empty($search)) {
                echo json_encode(['success' => false, 'message' => 'Search term required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT id, registration_number, first_name, last_name, program, email, phone
                FROM students 
                WHERE status = 'active' 
                AND (registration_number LIKE :search 
                     OR first_name LIKE :search 
                     OR last_name LIKE :search 
                     OR CONCAT(first_name, ' ', last_name) LIKE :search)
                ORDER BY first_name, last_name
                LIMIT 20
            ");
            $stmt->execute(['search' => "%$search%"]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'students' => $students
            ]);
            break;
            
        case 'get':
            // Get specific student details
            $student_id = $_GET['id'] ?? '';
            if (empty($student_id)) {
                echo json_encode(['success' => false, 'message' => 'Student ID required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT s.*, u.username, u.email as user_email
                FROM students s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.id = :id AND s.status = 'active'
            ");
            $stmt->execute(['id' => $student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'student' => $student
            ]);
            break;
            
        case 'stats':
            // Get student statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_students,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_students,
                    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_students
                FROM students
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get students by program
            $stmt = $pdo->prepare("
                SELECT program, COUNT(*) as count
                FROM students 
                WHERE status = 'active'
                GROUP BY program
                ORDER BY count DESC
            ");
            $stmt->execute();
            $by_program = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'by_program' => $by_program
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database error in students API: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 