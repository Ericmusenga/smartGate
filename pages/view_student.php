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
$student = null;

// Get student ID or registration number from URL
$student_id = (int)($_GET['id'] ?? 0);
$registration_number = $_GET['registration_number'] ?? '';

if ($student_id <= 0 && empty($registration_number)) {
    $error_message = 'Invalid student ID or registration number.';
} else {
    try {
        $db = getDB();
        
        // Build query based on search type
        if (!empty($registration_number)) {
            // Search by registration number
            $sql = "SELECT s.*, 
                           u.username, 
                           u.is_first_login,
                           u.last_login,
                           u.created_at as user_created_at,
                           COUNT(d.id) as device_count
                    FROM students s 
                    LEFT JOIN users u ON s.id = u.student_id 
                    LEFT JOIN devices d ON u.id = d.user_id AND d.is_registered = TRUE
                    WHERE s.registration_number = ?
                    GROUP BY s.id";
            $params = [$registration_number];
        } else {
            // Search by student ID
            $sql = "SELECT s.*, 
                           u.username, 
                           u.is_first_login,
                           u.last_login,
                           u.created_at as user_created_at,
                           COUNT(d.id) as device_count
                    FROM students s 
                    LEFT JOIN users u ON s.id = u.student_id 
                    LEFT JOIN devices d ON u.id = d.user_id AND d.is_registered = TRUE
                    WHERE s.id = ?
                    GROUP BY s.id";
            $params = [$student_id];
        }
        
        $student = $db->fetch($sql, $params);
        
        if (!$student) {
            $error_message = !empty($registration_number) 
                ? 'Student with registration number "' . htmlspecialchars($registration_number) . '" not found.'
                : 'Student not found.';
        }
        
    } catch (Exception $e) {
        $error_message = 'Error loading student details.';
        error_log("Student view error: " . $e->getMessage());
    }
}

// If this is an AJAX request, return JSON
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    header('Content-Type: application/json');
    if ($error_message) {
        error_log("Student view AJAX error: " . $error_message);
        echo json_encode(['error' => $error_message]);
    } else {
        echo json_encode(['student' => $student]);
    }
    exit();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Student Details</div>
            <div class="page-subtitle">View complete student information</div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <a href="students.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        <?php elseif ($student): ?>
            <div class="action-buttons">
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Student
                </a>
            </div>
            
            <div class="student-details-grid">
                <!-- Personal Information -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <div class="detail-item">
                                <label>Registration Number:</label>
                                <span class="value"><?php echo htmlspecialchars($student['registration_number']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Full Name:</label>
                                <span class="value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-item">
                                <label>Email:</label>
                                <span class="value">
                                    <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>">
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </a>
                                </span>
                            </div>
                            <div class="detail-item">
                                <label>Phone:</label>
                                <span class="value">
                                    <?php if ($student['phone']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($student['phone']); ?>">
                                            <?php echo htmlspecialchars($student['phone']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-item">
                                <label>Gender:</label>
                                <span class="value">
                                    <?php if ($student['gender']): ?>
                                        <?php echo ucfirst($student['gender']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <label>Date of Birth:</label>
                                <span class="value">
                                    <?php if ($student['date_of_birth']): ?>
                                        <?php echo date('F j, Y', strtotime($student['date_of_birth'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($student['address']): ?>
                            <div class="detail-row">
                                <div class="detail-item full-width">
                                    <label>Address:</label>
                                    <span class="value"><?php echo nl2br(htmlspecialchars($student['address'])); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Academic Information -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <div class="detail-item">
                                <label>Department:</label>
                                <span class="value"><?php echo htmlspecialchars($student['department']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Program:</label>
                                <span class="value"><?php echo htmlspecialchars($student['program']); ?></span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-item">
                                <label>Year of Study:</label>
                                <span class="value">
                                    <span class="year-badge">Year <?php echo $student['year_of_study']; ?></span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <label>Status:</label>
                                <span class="value">
                                    <?php if ($student['is_active']): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-key"></i> Account Information</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($student['username']): ?>
                            <div class="detail-row">
                                <div class="detail-item">
                                    <label>Username:</label>
                                    <span class="value"><?php echo htmlspecialchars($student['username']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Account Status:</label>
                                    <span class="value">
                                        <?php if ($student['is_first_login']): ?>
                                            <span class="account-badge account-first-login">
                                                <i class="fas fa-exclamation-triangle"></i> First Login Required
                                            </span>
                                        <?php else: ?>
                                            <span class="account-badge account-active">
                                                <i class="fas fa-check"></i> Active
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-item">
                                    <label>Last Login:</label>
                                    <span class="value">
                                        <?php if ($student['last_login']): ?>
                                            <?php echo date('F j, Y g:i A', strtotime($student['last_login'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never logged in</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <label>Account Created:</label>
                                    <span class="value">
                                        <?php echo date('F j, Y', strtotime($student['user_created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="detail-row">
                                <div class="detail-item full-width">
                                    <span class="account-badge account-missing">
                                        <i class="fas fa-times"></i> No Login Account Created
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Emergency Contact -->
                <?php if ($student['emergency_contact'] || $student['emergency_phone']): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-phone"></i> Emergency Contact</h3>
                        </div>
                        <div class="card-body">
                            <div class="detail-row">
                                <div class="detail-item">
                                    <label>Contact Name:</label>
                                    <span class="value">
                                        <?php echo htmlspecialchars($student['emergency_contact']); ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <label>Contact Phone:</label>
                                    <span class="value">
                                        <?php if ($student['emergency_phone']): ?>
                                            <a href="tel:<?php echo htmlspecialchars($student['emergency_phone']); ?>">
                                                <?php echo htmlspecialchars($student['emergency_phone']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- System Information -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> System Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <div class="detail-item">
                                <label>Registered Devices:</label>
                                <span class="value">
                                    <span class="device-count-badge">
                                        <?php echo $student['device_count']; ?> device<?php echo $student['device_count'] != 1 ? 's' : ''; ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <label>Student Since:</label>
                                <span class="value">
                                    <?php echo date('F j, Y', strtotime($student['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
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

.student-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0;
}

.card-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 1.5rem;
}

.detail-row {
    display: flex;
    gap: 2rem;
    margin-bottom: 1rem;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-item {
    flex: 1;
}

.detail-item.full-width {
    flex: 1 1 100%;
}

.detail-item label {
    display: block;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.detail-item .value {
    color: #212529;
    font-size: 1rem;
}

.detail-item .value a {
    color: #007bff;
    text-decoration: none;
}

.detail-item .value a:hover {
    text-decoration: underline;
}

.year-badge {
    background: #007bff;
    color: white;
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.account-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.account-active {
    background: #d1ecf1;
    color: #0c5460;
}

.account-first-login {
    background: #fff3cd;
    color: #856404;
}

.account-missing {
    background: #f8d7da;
    color: #721c24;
}

.device-count-badge {
    background: #e9ecef;
    color: #495057;
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.text-muted {
    color: #6c757d !important;
}

@media (max-width: 768px) {
    .student-details-grid {
        grid-template-columns: 1fr;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?> 