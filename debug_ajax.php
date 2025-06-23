<?php
/**
 * Debug AJAX Endpoint
 * This file helps debug AJAX requests and database queries
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Include configuration
require_once 'config/config.php';

// Get the requested action
$action = $_GET['action'] ?? '';

try {
    $db = getDB();
    
    switch ($action) {
        case 'test_connection':
            echo json_encode([
                'status' => 'success',
                'message' => 'Database connection successful',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'test_student_query':
            $student_id = (int)($_GET['id'] ?? 0);
            if ($student_id <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid student ID: ' . $student_id
                ]);
                break;
            }
            
            // Test the exact query from edit_student.php
            $test_sql = "SELECT s.*, 
                        u.username, u.is_first_login, u.last_login,
                        (SELECT COUNT(*) FROM devices d 
                         JOIN users u2 ON d.user_id = u2.id 
                         WHERE u2.student_id = s.id) as device_count
                        FROM students s 
                        LEFT JOIN users u ON s.id = u.student_id 
                        WHERE s.id = ?";
            
            $student = $db->fetch($test_sql, [$student_id]);
            
            if ($student) {
                echo json_encode([
                    'status' => 'success',
                    'student' => $student,
                    'query' => $test_sql,
                    'params' => [$student_id]
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Student not found with ID: ' . $student_id,
                    'query' => $test_sql,
                    'params' => [$student_id]
                ]);
            }
            break;
            
        case 'list_students':
            $students = $db->fetchAll("SELECT id, registration_number, first_name, last_name FROM students LIMIT 10");
            echo json_encode([
                'status' => 'success',
                'students' => $students,
                'count' => count($students)
            ]);
            break;
            
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action. Available actions: test_connection, test_student_query, list_students',
                'usage' => [
                    'test_connection' => 'debug_ajax.php?action=test_connection',
                    'test_student_query' => 'debug_ajax.php?action=test_student_query&id=1',
                    'list_students' => 'debug_ajax.php?action=list_students'
                ]
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?> 