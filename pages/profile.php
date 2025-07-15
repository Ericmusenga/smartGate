<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../login.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

$user_id = $_SESSION['user_id'];
$user_type = get_user_type();
$error_message = '';
$success_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $emergency_contact = sanitize_input($_POST['emergency_contact'] ?? '');
    $emergency_phone = sanitize_input($_POST['emergency_phone'] ?? '');

    // Validation
    if (!$first_name || !$last_name || !$email) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            $db = getDB();
            
            // Check if email is already taken by another user
            $existing_user = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
            if ($existing_user) {
                $error_message = 'This email address is already in use by another user.';
            } else {
                // Update user profile
                $db->query("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, emergency_contact = ?, emergency_phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                    [$first_name, $last_name, $email, $phone, $address, $emergency_contact, $emergency_phone, $user_id]);
                
                // Update session data
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                
                $success_message = 'Profile updated successfully!';
            }
        } catch (Exception $e) {
            $error_message = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// Get user data
try {
    $db = getDB();
    
    // Get user information with role details
    $user = $db->fetch("
        SELECT u.*, r.role_name, r.role_description
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ", [$user_id]);
    
    if (!$user) {
        redirect('../unauthorized.php');
    }
    
    // Get additional data based on user type
    $additional_data = [];
    
    if ($user_type === 'student' && $user['student_id']) {
        // Get student-specific information
        $student = $db->fetch("SELECT * FROM students WHERE id = ?", [$user['student_id']]);
        if ($student) {
            $additional_data['student'] = $student;
        }
        
        // Get student's devices count
        $device_count = $db->fetch("SELECT COUNT(*) as count FROM devices WHERE user_id = ?", [$user_id]);
        $additional_data['device_count'] = $device_count['count'];
        
        // Get student's cards count
        $card_count = $db->fetch("SELECT COUNT(*) as count FROM rfid_cards WHERE student_id = ?", [$user['student_id']]);
        $additional_data['card_count'] = $card_count['count'];
        
        // Get recent entry/exit logs
        $recent_logs = $db->fetchAll("
            SELECT eel.*, s.first_name, s.last_name, s.registration_number
            FROM entry_exit_logs eel 
            LEFT JOIN users u ON eel.user_id = u.id 
            LEFT JOIN students s ON u.student_id = s.id
            WHERE eel.user_id = ? 
            ORDER BY eel.created_at DESC 
            LIMIT 5
        ", [$user_id]);
        $additional_data['recent_logs'] = $recent_logs;
        
    } elseif ($user_type === 'security' && $user['security_officer_id']) {
        // Get security officer-specific information
        $security_officer = $db->fetch("SELECT * FROM security_officers WHERE id = ?", [$user['security_officer_id']]);
        if ($security_officer) {
            $additional_data['security_officer'] = $security_officer;
        }
        
        // Get recent security activities
        $recent_activities = $db->fetchAll("
            SELECT eel.*, u.first_name, u.last_name, s.first_name as student_first_name, s.last_name as student_last_name
            FROM entry_exit_logs eel 
            LEFT JOIN users u ON eel.user_id = u.id 
            LEFT JOIN students s ON u.student_id = s.id
            WHERE eel.security_officer_id = ? 
            ORDER BY eel.created_at DESC 
            LIMIT 5
        ", [$user_id]);
        $additional_data['recent_activities'] = $recent_activities;
        
    } elseif ($user_type === 'admin') {
        // Get admin statistics
        $stats = $db->fetch("SELECT 
            (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_users,
            (SELECT COUNT(*) FROM students WHERE is_active = 1) as total_students,
            (SELECT COUNT(*) FROM devices WHERE is_registered = 1) as total_devices,
            (SELECT COUNT(*) FROM entry_exit_logs WHERE DATE(created_at) = CURDATE()) as today_logs
        ");
        $additional_data['stats'] = $stats;
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading profile: ' . $e->getMessage();
    $user = [];
    $additional_data = [];
}

include '../includes/header.php';

?>
<header>
    <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f5f5;
      color: #333;
    }

    .header {
      background-color: #2196F3;
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .header h1 {
      font-size: 1.5rem;
      font-weight: 600;
    }

    .header-info {
      font-size: 0.9rem;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #1976D2;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: white;
    }

    .logout-btn {
      background-color: #f44336;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background-color 0.3s;
    }

    .logout-btn:hover {
      background-color: #d32f2f;
    }

    .container {
      display: flex;
      min-height: calc(100vh - 80px);
    }

    .sidebar {
      width: 250px;
      background-color: white;
      box-shadow: 2px 0 4px rgba(0,0,0,0.1);
      padding: 1rem 0;
    }

    .sidebar-section {
      margin-bottom: 2rem;
    }

    .sidebar-title {
      color: #666;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      margin-bottom: 1rem;
      padding: 0 1rem;
    }

    .sidebar-item {
      display: flex;
      align-items: center;
      padding: 0.75rem 1rem;
      color: #333;
      text-decoration: none;
      transition: background-color 0.3s;
      cursor: pointer;
      border-left: 3px solid transparent;
    }

    .sidebar-item:hover {
      background-color: #f5f5f5;
    }

    .sidebar-item.active {
      background-color: #e3f2fd;
      color: #2196F3;
      border-left-color: #2196F3;
    }

    .sidebar-item i {
      margin-right: 0.75rem;
      font-size: 1.1rem;
    }

    .main-content {
      flex: 1;
      padding: 2rem;
      background-color: #f5f5f5;
    }

    .form-container {
      background-color: white;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      margin: auto;
    }

    h2 {
      margin-bottom: 1.5rem;
      text-align: center;
      color: #2196F3;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #333;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1rem;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #2196F3;
      box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
    }

    .btn {
      background-color: #2196F3;
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: background-color 0.3s;
      width: 100%;
      margin-top: 1rem;
    }

    .btn:hover {
      background-color: #1976D2;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 1rem;
      text-decoration: none;
      color: #2196F3;
      font-weight: 500;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        position: fixed;
        left: -100%;
        top: 80px;
        height: calc(100vh - 80px);
        z-index: 999;
        transition: left 0.3s;
      }

      .sidebar.open {
        left: 0;
      }

      .main-content {
        padding: 1rem;
      }

      .form-container {
        margin: 0 auto;
      }
    }
    </style>
</header>

<main class="main-content">
    <nav class="sidebar">
      <div class="sidebar-section">
        <div class="sidebar-title">Student Portal</div>
        <a class="sidebar-item" href="/Capstone_project/pages/dashboard_student.php">
          <i class="fas fa-tachometer-alt"></i>
          Dashboard
        </a>
        <a class="sidebar-item" href="/Capstone_project/pages/lend_computer.php">
          <i class="fas fa-share-alt"></i>
          Lend My Computer
        </a>
        <a class="sidebar-item" href="/Capstone_project/pages/return_computer.php">
          <i class="fas fa-undo"></i>
          Return My Computer
        </a>
        <a class="sidebar-item" href="/Capstone_project/pages/Approve.php">
          <i class="fas fa-laptop-house"></i>
          Approve
        </a>
      </div>
      <div class="sidebar-section">
        <div class="sidebar-title">Account</div>
        <a class="sidebar-item active" href="/Capstone_project/pages/profile.php">
          <i class="fas fa-user"></i>
          Profile
        </a>
      </div>
    </nav>
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">My Profile</div>
            <div class="page-subtitle">View and manage your account information</div>
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
        
        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                    <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="emergency_phone" class="form-label">Emergency Phone</label>
                                    <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                                <a href="../change_password.php" class="btn btn-secondary">
                                    <i class="fas fa-key"></i> Change Password
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            
        </div>
        
       
        
        <?php if ($user_type === 'security' && isset($additional_data['recent_activities']) && !empty($additional_data['recent_activities'])): ?>
        <!-- Recent Security Activities -->
        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-list"></i> Recent Security Activities</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Person</th>
                                <th>Gate</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($additional_data['recent_activities'] as $activity): ?>
                            <tr>
                                <td><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $person_name = '';
                                    if ($activity['student_first_name'] && $activity['student_last_name']) {
                                        $person_name = $activity['student_first_name'] . ' ' . $activity['student_last_name'];
                                    } elseif ($activity['first_name'] && $activity['last_name']) {
                                        $person_name = $activity['first_name'] . ' ' . $activity['last_name'];
                                    } else {
                                        $person_name = 'Unknown';
                                    }
                                    echo htmlspecialchars($person_name);
                                    ?>
                                </td>
                                <td><span class="badge bg-secondary">Gate <?php echo $activity['gate_number']; ?></span></td>
                                <td>
                                    <span class="badge bg-<?php echo $activity['status'] === 'entered' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($activity['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($user_type === 'admin' && isset($additional_data['stats'])): ?>
        <!-- Admin Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> System Statistics</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-item text-center">
                            <div class="stat-number"><?php echo $additional_data['stats']['total_users']; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item text-center">
                            <div class="stat-number"><?php echo $additional_data['stats']['total_students']; ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item text-center">
                            <div class="stat-number"><?php echo $additional_data['stats']['total_devices']; ?></div>
                            <div class="stat-label">Registered Devices</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item text-center">
                            <div class="stat-number"><?php echo $additional_data['stats']['today_logs']; ?></div>
                            <div class="stat-label">Today's Logs</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<style>
.info-item {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.info-item label {
    font-weight: 600;
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 5px;
    display: block;
}

.info-item .value {
    color: #333;
    font-size: 1rem;
}

.stat-item {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 15px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}
</style>

<?php include '../includes/footer.php'; ?> 