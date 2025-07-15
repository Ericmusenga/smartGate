<?php
// Prevent any output before JSON response
ob_start();

// Include configuration
require_once '../config/config.php';

// Clear any output buffer immediately
ob_clean();

// Set JSON content type
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in and is admin
if (!is_logged_in()) {
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

if (get_user_type() !== 'admin') {
    echo json_encode(['error' => 'Admin access required']);
    exit();
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    echo json_encode(['error' => 'Password change required']);
    exit();
}

// Handle POST requests (saving student data)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['id'] ?? 0);
    
    if ($student_id <= 0) {
        echo json_encode(['error' => 'Invalid student ID']);
        exit();
    }
    
    // Get form data
    $registration_number = sanitize_input($_POST['registration_number'] ?? '');
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');
    $program = sanitize_input($_POST['program'] ?? '');
    $year_of_study = (int)($_POST['year_of_study'] ?? 1);
    $gender = sanitize_input($_POST['gender'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $address = sanitize_input($_POST['address'] ?? '');
    $emergency_contact = sanitize_input($_POST['emergency_contact'] ?? '');
    $emergency_phone = sanitize_input($_POST['emergency_phone'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($registration_number) || empty($first_name) || empty($last_name) || empty($email) || empty($department) || empty($program)) {
        echo json_encode(['error' => 'Please fill in all required fields.']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Please enter a valid email address.']);
        exit();
    }
    
    if ($year_of_study < 1 || $year_of_study > 6) {
        echo json_encode(['error' => 'Year of study must be between 1 and 6.']);
        exit();
    }
    
    if (!preg_match('/^\d{1,15}$/', $registration_number)) {
        echo json_encode(['error' => 'Registration number must be numeric and less than 16 digits.']);
        exit();
    }
    if (!preg_match('/^\d{10,}$/', $phone)) {
        echo json_encode(['error' => 'Phone number must be numeric and at least 10 digits.']);
        exit();
    }
    
    try {
        $db = getDB();
        
        // Check if registration number already exists (excluding current student)
        $existing_student = $db->fetch("SELECT id FROM students WHERE registration_number = ? AND id != ?", [$registration_number, $student_id]);
        if ($existing_student) {
            echo json_encode(['error' => 'Registration number already exists.']);
            exit();
        }
        
        // Check if email already exists (excluding current student)
        $existing_email = $db->fetch("SELECT id FROM students WHERE email = ? AND id != ?", [$email, $student_id]);
        if ($existing_email) {
            echo json_encode(['error' => 'Email address already exists.']);
            exit();
        }
        
        // Update student data
        $update_result = $db->query("UPDATE students SET 
                   registration_number = ?, first_name = ?, last_name = ?, email = ?, 
                   phone = ?, department = ?, program = ?, year_of_study = ?, 
                   gender = ?, date_of_birth = ?, address = ?, emergency_contact = ?, 
                   emergency_phone = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP 
                   WHERE id = ?", 
                  [$registration_number, $first_name, $last_name, $email, $phone, 
                   $department, $program, $year_of_study, $gender, $date_of_birth, 
                   $address, $emergency_contact, $emergency_phone, $is_active, $student_id]);
        
        if ($update_result) {
            echo json_encode(['success' => 'Student updated successfully!']);
            error_log("Student updated successfully via AJAX: ID=" . $student_id . ", Name=" . $first_name . " " . $last_name);
        } else {
            echo json_encode(['error' => 'Failed to update student. Please try again.']);
            error_log("Failed to update student via AJAX: ID=" . $student_id);
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error updating student: ' . $e->getMessage()]);
        error_log("Student update AJAX error: " . $e->getMessage());
    }
    
    exit();
}

// Handle GET requests (loading student data)
$student_id = (int)($_GET['id'] ?? 0);

// Check if this is an AJAX request
if (!isset($_GET['ajax']) || $_GET['ajax'] !== 'true') {
    echo json_encode(['error' => 'Invalid request type']);
    exit();
}

if ($student_id <= 0) {
    echo json_encode(['error' => 'Invalid student ID: ' . $student_id]);
    exit();
}

try {
    $db = getDB();
    
    // SQL query to get student details with user account info
    $student_sql = "SELECT s.*, 
                    u.username, u.is_first_login, u.last_login,
                    (SELECT COUNT(*) FROM devices d 
                     JOIN users u2 ON d.user_id = u2.id 
                     WHERE u2.student_id = s.id) as device_count
                    FROM students s 
                    LEFT JOIN users u ON s.id = u.student_id 
                    WHERE s.id = ?";
    
    // Debug: Log the request
    error_log("AJAX request for student ID: " . $student_id);
    
    // Get student details with user account info
    $student = $db->fetch($student_sql, [$student_id]);
    
    if (!$student) {
        echo json_encode(['error' => 'Student not found with ID: ' . $student_id]);
        error_log("Student not found: ID=" . $student_id);
    } else {
        echo json_encode(['student' => $student]);
        error_log("Student loaded successfully via AJAX: ID=" . $student_id . ", Name=" . $student['first_name'] . " " . $student['last_name']);
    }
    
} catch (Exception $e) {
    $error_msg = 'Error loading student details: ' . $e->getMessage();
    echo json_encode(['error' => $error_msg]);
    error_log("Student edit AJAX error: " . $e->getMessage() . " | Student ID: " . $student_id);
}

exit();
?> 