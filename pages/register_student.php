<?php
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $create_user_account = isset($_POST['create_user_account']) ? true : false;
    $password_option = $_POST['password_option'] ?? 'registration_number';
    $custom_password = sanitize_input($_POST['custom_password'] ?? '');
    
    // Validation
    if (empty($registration_number) || empty($first_name) || empty($last_name) || empty($email) || empty($department) || empty($program)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif ($year_of_study < 1 || $year_of_study > 6) {
        $error_message = 'Year of study must be between 1 and 6.';
    } elseif ($create_user_account && $password_option === 'custom' && strlen($custom_password) < 8) {
        $error_message = 'Custom password must be at least 8 characters long.';
    } else {
        try {
            $db = getDB();
            
            // Check if registration number already exists
            $existing_student = $db->fetch("SELECT id FROM students WHERE registration_number = ?", [$registration_number]);
            if ($existing_student) {
                $error_message = 'Registration number already exists.';
            } else {
                // Check if email already exists
                $existing_email = $db->fetch("SELECT id FROM students WHERE email = ?", [$email]);
                if ($existing_email) {
                    $error_message = 'Email address already exists.';
                } else {
                    // Insert student
                    $db->query("INSERT INTO students (registration_number, first_name, last_name, email, phone, department, program, year_of_study, gender, date_of_birth, address, emergency_contact, emergency_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                              [$registration_number, $first_name, $last_name, $email, $phone, $department, $program, $year_of_study, $gender, $date_of_birth, $address, $emergency_contact, $emergency_phone]);
                    
                    $student_id = $db->lastInsertId();
                    
                    // Create user account if requested
                    if ($create_user_account) {
                        // Determine password
                        if ($password_option === 'registration_number') {
                            $password = $registration_number;
                        } elseif ($password_option === 'custom') {
                            $password = $custom_password;
                        } else {
                            $password = 'student123'; // default
                        }
                        
                        // Get student role ID
                        $student_role = $db->fetch("SELECT id FROM roles WHERE role_name = 'student'");
                        $role_id = $student_role['id'];
                        
                        // Insert user account
                        $db->query("INSERT INTO users (username, password, email, first_name, last_name, role_id, student_id, department, program, year_of_study, gender, is_first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)", 
                                  [$registration_number, $password, $email, $first_name, $last_name, $role_id, $student_id, $department, $program, $year_of_study, $gender]);
                        
                        $success_message = "Student registered successfully! User account created with username: $registration_number and password: $password";
                    } else {
                        $success_message = "Student registered successfully! No user account created.";
                    }
                    
                    // Clear form data on success
                    $_POST = array();
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error registering student. Please try again.';
            error_log("Student registration error: " . $e->getMessage());
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Register New Student</div>
            <div class="page-subtitle">Add a new student to the system</div>
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
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-plus"></i> Student Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="registration_number" class="form-label">Registration Number *</label>
                                <input type="text" id="registration_number" name="registration_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['registration_number'] ?? ''); ?>" required>
                                <small class="form-text">Format: YYYY/NNN (e.g., 2023/001)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-control">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="department" class="form-label">Department *</label>
                                <input type="text" id="department" name="department" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="program" class="form-label">Program *</label>
                                <input type="text" id="program" name="program" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['program'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="year_of_study" class="form-label">Year of Study *</label>
                                <select id="year_of_study" name="year_of_study" class="form-control" required>
                                    <option value="">Select Year</option>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($_POST['year_of_study'] ?? '') == $i ? 'selected' : ''; ?>>
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
                                       value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="emergency_contact" class="form-label">Emergency Contact Name</label>
                                <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="emergency_phone" class="form-label">Emergency Contact Phone</label>
                                <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="create_user_account" name="create_user_account" 
                                   <?php echo isset($_POST['create_user_account']) ? 'checked' : ''; ?>>
                            <label for="create_user_account">
                                <strong>Create User Account</strong><br>
                                <small>Create a login account for this student</small>
                            </label>
                        </div>
                    </div>
                    
                    <div id="password-options" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Password Option</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" id="password_registration" name="password_option" value="registration_number" 
                                           <?php echo ($_POST['password_option'] ?? 'registration_number') === 'registration_number' ? 'checked' : ''; ?>>
                                    <label for="password_registration">Use registration number as password</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="password_custom" name="password_option" value="custom" 
                                           <?php echo ($_POST['password_option'] ?? '') === 'custom' ? 'checked' : ''; ?>>
                                    <label for="password_custom">Use custom password</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="password_default" name="password_option" value="default" 
                                           <?php echo ($_POST['password_option'] ?? '') === 'default' ? 'checked' : ''; ?>>
                                    <label for="password_default">Use default password (student123)</label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="custom-password-field" style="display: none;">
                            <div class="form-group">
                                <label for="custom_password" class="form-label">Custom Password</label>
                                <input type="password" id="custom_password" name="custom_password" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['custom_password'] ?? ''); ?>">
                                <small class="form-text">Minimum 8 characters</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Register Student
                        </button>
                        <a href="dashboard_admin.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
.checkbox-group {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: rgba(52, 152, 219, 0.1);
    border-radius: 10px;
    border: 1px solid rgba(52, 152, 219, 0.2);
}

.checkbox-group input[type="checkbox"] {
    margin-right: 0.5rem;
    transform: scale(1.2);
}

.checkbox-group label {
    color: #2c3e50;
    font-size: 0.9rem;
    cursor: pointer;
}

.radio-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.radio-item {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 5px;
}

.radio-item input[type="radio"] {
    margin-right: 0.5rem;
}

.radio-item label {
    margin: 0;
    cursor: pointer;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    gap: 1rem;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
}

.alert-danger {
    background: rgba(231, 76, 60, 0.1);
    border-color: #e74c3c;
    color: #e74c3c;
}

.alert-success {
    background: rgba(39, 174, 96, 0.1);
    border-color: #27ae60;
    color: #27ae60;
}
</style>

<script>
// Show/hide password options based on checkbox
document.getElementById('create_user_account').addEventListener('change', function() {
    const passwordOptions = document.getElementById('password-options');
    if (this.checked) {
        passwordOptions.style.display = 'block';
    } else {
        passwordOptions.style.display = 'none';
    }
});

// Show/hide custom password field based on radio selection
document.querySelectorAll('input[name="password_option"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const customField = document.getElementById('custom-password-field');
        if (this.value === 'custom') {
            customField.style.display = 'block';
        } else {
            customField.style.display = 'none';
        }
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const createAccountCheckbox = document.getElementById('create_user_account');
    const passwordOptions = document.getElementById('password-options');
    const customField = document.getElementById('custom-password-field');
    const customRadio = document.getElementById('password_custom');
    
    if (createAccountCheckbox.checked) {
        passwordOptions.style.display = 'block';
    }
    
    if (customRadio.checked) {
        customField.style.display = 'block';
    }
});
</script>

<?php include '../includes/footer.php'; ?> 