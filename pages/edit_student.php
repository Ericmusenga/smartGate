<?php
// Start output buffering to prevent any output before JSON response
ob_start();

require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in()) {
    redirect('../login.php');
}

if (get_user_type() !== 'admin') {
    redirect('../login.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

$error_message = '';
$success_message = '';
$student = null;

// Define the SQL query for fetching student details
$student_sql = "SELECT s.*, 
                u.username, u.is_first_login, u.last_login,
                (SELECT COUNT(*) FROM devices d 
                 JOIN users u2 ON d.user_id = u2.id 
                 WHERE u2.student_id = s.id) as device_count
                FROM students s 
                LEFT JOIN users u ON s.id = u.student_id 
                WHERE s.id = ?";

// Get student ID from URL
$student_id = (int)($_GET['id'] ?? 0);

// If this is an AJAX request, return JSON
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    // Clear any output buffer
    ob_clean();
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    if ($student_id <= 0) {
        echo json_encode(['error' => 'Invalid student ID: ' . $student_id]);
        exit();
    }
    
    try {
        $db = getDB();
        
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
}

if ($student_id <= 0) {
    $error_message = 'Invalid student ID.';
} else {
    try {
        $db = getDB();
        
        // Get student details with user account info
        $student = $db->fetch($student_sql, [$student_id]);
        
        if (!$student) {
            $error_message = 'Student not found with ID: ' . $student_id;
        } else {
            // Debug: Log successful student loading
            error_log("Student loaded successfully: ID=" . $student_id . ", Name=" . $student['first_name'] . " " . $student['last_name']);
        }
        
    } catch (Exception $e) {
        $error_message = 'Error loading student details: ' . $e->getMessage();
        error_log("Student edit error: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student) {
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
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif ($year_of_study < 1 || $year_of_study > 6) {
        $error_message = 'Year of study must be between 1 and 6.';
    } elseif (!preg_match('/^\d{1,15}$/', $registration_number)) {
        $error_message = 'Registration number must be numeric and less than 16 digits.';
    } elseif (!preg_match('/^\d{10,}$/', $phone)) {
        $error_message = 'Phone number must be numeric and at least 10 digits.';
    } else {
        try {
            $db = getDB();
            
            // Check if registration number already exists (excluding current student)
            $existing_student = $db->fetch("SELECT id FROM students WHERE registration_number = ? AND id != ?", [$registration_number, $student_id]);
            if ($existing_student) {
                $error_message = 'Registration number already exists.';
            } else {
                // Check if email already exists (excluding current student)
                $existing_email = $db->fetch("SELECT id FROM students WHERE email = ? AND id != ?", [$email, $student_id]);
                if ($existing_email) {
                    $error_message = 'Email address already exists.';
                } else {
                    // Update student data only
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
                        $success_message = "Student updated successfully!";
                        error_log("Student updated successfully: ID=" . $student_id . ", Name=" . $first_name . " " . $last_name);
                        
                        // Reload student data to show updated information
                        $updated_student = $db->fetch($student_sql, [$student_id]);
                        if ($updated_student) {
                            $student = $updated_student;
                            error_log("Student data reloaded successfully after update");
                        } else {
                            error_log("Failed to reload student data after update");
                        }
                    } else {
                        $error_message = 'Failed to update student. Please try again.';
                        error_log("Failed to update student: ID=" . $student_id);
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error updating student. Please try again.';
            error_log("Student update error: " . $e->getMessage());
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Edit Student</div>
            <div class="page-subtitle">Update student information</div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$student): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Student not found.
            </div>
            <a href="students.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        <?php else: ?>
            <div class="action-buttons">
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
                <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> View Student
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-edit"></i> Student Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_number" class="form-label">Registration Number *</label>
                                    <input type="text" id="registration_number" name="registration_number" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['registration_number'] ?? $student['registration_number']); ?>" required>
                                    <small class="form-text">Format: YYYY/NNN (e.g., 2023/001)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? $student['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? $student['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? $student['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? $student['phone']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select id="gender" name="gender" class="form-control">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo ($_POST['gender'] ?? $student['gender']) === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($_POST['gender'] ?? $student['gender']) === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($_POST['gender'] ?? $student['gender']) === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department" class="form-label">Department *</label>
                                    <input type="text" id="department" name="department" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['department'] ?? $student['department']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="program" class="form-label">Program *</label>
                                    <input type="text" id="program" name="program" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['program'] ?? $student['program']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="year_of_study" class="form-label">Year of Study *</label>
                                    <select id="year_of_study" name="year_of_study" class="form-control" required>
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($_POST['year_of_study'] ?? $student['year_of_study']) == $i ? 'selected' : ''; ?>>
                                                Year <?php echo $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                           value="<?php echo $_POST['date_of_birth'] ?? $student['date_of_birth']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? $student['address']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="emergency_contact" class="form-label">Emergency Contact Name</label>
                                    <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? $student['emergency_contact']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="emergency_phone" class="form-label">Emergency Contact Phone</label>
                                    <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['emergency_phone'] ?? $student['emergency_phone']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" 
                                               <?php echo ($_POST['is_active'] ?? $student['is_active']) ? 'checked' : ''; ?>>
                                        <label for="is_active" class="form-check-label">Active Student</label>
                                    </div>
                                    <small class="form-text">Uncheck to deactivate this student</small>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Student
                            </button>
                            <a href="students.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.action-buttons {
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.user-account-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.user-account-section h4 {
    color: #495057;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .action-buttons,
    .form-actions {
        flex-direction: column;
    }
    
    .action-buttons .btn,
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
