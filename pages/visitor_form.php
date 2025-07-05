<?php
require_once '../config/config.php';

// Check if user is logged in and is security
if (!is_logged_in()) {
    redirect('../login.php');
}

if (get_user_type() !== 'security') {
    redirect('../login.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

$error_message = '';
$success_message = '';
$today_visitors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_name = trim($_POST['visitor_name'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $person_to_visit = trim($_POST['person_to_visit'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');

    if (!$visitor_name || !$telephone || !$purpose) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $db = getDB();
            
            $db->query("INSERT INTO visitors (visitor_name, telephone, email, purpose, person_to_visit, department, id_number, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [$visitor_name, $telephone, $email, $purpose, $person_to_visit, $department, $id_number]);
            
            $success_message = 'Visitor registered successfully!';
            
            // Clear form data after successful submission
            $_POST = array();
            
        } catch (Exception $e) {
            $error_message = 'Error registering visitor: ' . $e->getMessage();
        }
    }
}



include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-container">
    <main class="main-content">
        <div class="content-wrapper">
            <div class="page-header">
                <div class="page-title">Visitor Registration</div>
                <div class="page-subtitle">Register new visitors to the campus</div>
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
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus"></i> Register New Visitor
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="visitor_name" class="form-label">Visitor Name *</label>
                                    <input type="text" id="visitor_name" name="visitor_name" 
                                           class="form-control" required
                                           value="<?php echo htmlspecialchars($_POST['visitor_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telephone" class="form-label">Telephone *</label>
                                    <input type="tel" id="telephone" name="telephone" 
                                           class="form-control" required
                                           value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" 
                                           class="form-control"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label">ID Number</label>
                                    <input type="text" id="id_number" name="id_number" 
                                           class="form-control"
                                           value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="purpose" class="form-label">Purpose of Visit *</label>
                                    <select id="purpose" name="purpose" class="form-select" required>
                                        <option value="">Select Purpose</option>
                                        <option value="Exam" <?php echo ($_POST['purpose'] ?? '') === 'Exam' ? 'selected' : ''; ?>>Exam</option>
                                        <option value="Meeting" <?php echo ($_POST['purpose'] ?? '') === 'Meeting' ? 'selected' : ''; ?>>Meeting</option>
                                        <option value="Prayer in church" <?php echo ($_POST['purpose'] ?? '') === 'Prayer in church' ? 'selected' : ''; ?>>Prayer in church</option>
                                        <option value="Maintenance" <?php echo ($_POST['purpose'] ?? '') === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="Delivery" <?php echo ($_POST['purpose'] ?? '') === 'Delivery' ? 'selected' : ''; ?>>Delivery</option>
                                        <option value="Consultation" <?php echo ($_POST['purpose'] ?? '') === 'Consultation' ? 'selected' : ''; ?>>Consultation</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="person_to_visit" class="form-label">Person to Visit</label>
                                    <input type="text" id="person_to_visit" name="person_to_visit" 
                                           class="form-control"
                                           value="<?php echo htmlspecialchars($_POST['person_to_visit'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <select id="department" name="department" class="form-select">
                                        <option value="">Select Department</option>
                                        <option value="Maths and Biology with Education" <?php echo ($_POST['department'] ?? '') === 'Maths and Biology with Education' ? 'selected' : ''; ?>>Maths and Biology with Education</option>
                                        <option value="Maths and Chemistry with Education" <?php echo ($_POST['department'] ?? '') === 'Maths and Chemistry with Education' ? 'selected' : ''; ?>>Maths and Chemistry with Education</option>
                                        <option value="Maths and Computer with Education" <?php echo ($_POST['department'] ?? '') === 'Maths and Computer with Education' ? 'selected' : ''; ?>>Maths and Computer with Education</option>
                                        <option value="Maths and Physics with Education" <?php echo ($_POST['department'] ?? '') === 'Maths and Physics with Education' ? 'selected' : ''; ?>>Maths and Physics with Education</option>
                                        <option value="Biology and Chemistry with Education" <?php echo ($_POST['department'] ?? '') === 'Biology and Chemistry with Education' ? 'selected' : ''; ?>>Biology and Chemistry with Education</option>
                                        <option value="Sport and Culture with Education" <?php echo ($_POST['department'] ?? '') === 'Sport and Culture with Education' ? 'selected' : ''; ?>>Sport and Culture with Education</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Register Visitor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Auto-format telephone number
document.getElementById('telephone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 0) {
        if (value.length <= 3) {
            value = value;
        } else if (value.length <= 6) {
            value = value.slice(0, 3) + '-' + value.slice(3);
        } else {
            value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
        }
    }
    e.target.value = value;
});

// Auto-clear success message after 5 seconds
<?php if ($success_message): ?>
setTimeout(function() {
    const alert = document.querySelector('.alert-success');
    if (alert) {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }
}, 5000);
<?php endif; ?>
</script>

<style>
/* Page container for proper footer positioning */
.page-container {
    min-height: calc(100vh - 80px);
    display: flex;
    flex-direction: column;
    padding-bottom: 60px; /* Add padding to prevent content from being hidden behind fixed footer */
}

.main-content {
    flex: 1;
}

/* Fixed footer styles */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 1rem 2rem;
    text-align: center;
    color: #7f8c8d;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.visitor-item {
    transition: all 0.3s ease;
}

.visitor-item:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.form-control:focus,
.form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.alert {
    transition: opacity 0.3s ease;
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
</style>

<?php include '../includes/footer.php'; ?> 