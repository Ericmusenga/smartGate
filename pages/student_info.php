<?php
require_once '../config/config.php';

// Check if user is logged in and is student
if (!is_logged_in()) {
    redirect('../login.php');
}

if (get_user_type() !== 'student') {
    redirect('../login.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$student_info = [];
$devices = [];
$cards = [];
$recent_logs = [];
$borrowed_computers = [];

try {
    $db = getDB();
    
    // Get comprehensive student information
    $student_info = $db->fetch("
        SELECT 
            u.*,
            s.student_id,
            s.registration_number,
            s.department,
            s.year_of_study,
            s.semester
        FROM users u
        LEFT JOIN students s ON u.student_id = s.id
        WHERE u.id = ?
    ", [$user_id]);
    
    if ($student_info) {
        // Get student's devices
        $devices = $db->fetchAll("
            SELECT * FROM devices 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ", [$user_id]);
        
        // Get student's RFID cards
        $cards = $db->fetchAll("
            SELECT * FROM rfid_cards 
            WHERE student_id = ? 
            ORDER BY created_at DESC
        ", [$student_info['student_id']]);
        
        // Get recent entry/exit logs (last 10)
        $recent_logs = $db->fetchAll("
            SELECT * FROM entry_exit_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ", [$user_id]);
        
        // Get borrowed computers
        $borrowed_computers = $db->fetchAll("
            SELECT d.*, u.first_name, u.last_name as owner_name
            FROM devices d
            JOIN users u ON d.owner_id = u.id
            WHERE d.user_id = ? AND d.owner_id != ?
            ORDER BY d.created_at DESC
        ", [$user_id, $user_id]);
        
        // Get statistics
        $stats = $db->fetch("
            SELECT 
                COUNT(DISTINCT d.id) as total_devices,
                COUNT(DISTINCT rc.id) as total_cards,
                COUNT(DISTINCT eel.id) as total_logs,
                COUNT(DISTINCT CASE WHEN eel.created_at >= CURDATE() THEN eel.id END) as today_logs,
                COUNT(DISTINCT CASE WHEN d.owner_id != ? THEN d.id END) as borrowed_devices
            FROM users u
            LEFT JOIN devices d ON u.id = d.user_id
            LEFT JOIN rfid_cards rc ON u.student_id = rc.student_id
            LEFT JOIN entry_exit_logs eel ON u.id = eel.user_id
            WHERE u.id = ?
        ", [$user_id, $user_id]);
        
    } else {
        $error_message = 'Student information not found.';
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading student information: ' . $e->getMessage();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-container">
    <main class="main-content">
        <div class="content-wrapper">
            <div class="page-header">
                <div class="page-title">Student Profile</div>
                <div class="page-subtitle">Your Complete Profile & Activity Details</div>
                <div class="page-actions">
                    <a href="dashboard_student.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="mb-4">
                <button id="btnStudentInfo" class="btn btn-primary me-2" onclick="showSection('student')">Student Information</button>
                <button id="btnAccountInfo" class="btn btn-outline-primary" onclick="showSection('account')">Account Information</button>
            </div>
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($student_info): ?>
            <div id="studentInfoSection">
                <!-- Student Information Section -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-circle"></i> Student Information
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="student-avatar mb-3">
                                    <i class="fas fa-user fa-4x text-primary"></i>
                                </div>
                                <h4><?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></h4>
                                <div class="student-details">
                                    <div class="detail-item">
                                        <strong>Student ID:</strong> <?php echo htmlspecialchars($student_info['student_id'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Registration:</strong> <?php echo htmlspecialchars($student_info['registration_number'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Department:</strong> <?php echo htmlspecialchars($student_info['department'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Year:</strong> <?php echo htmlspecialchars($student_info['year_of_study'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Semester:</strong> <?php echo htmlspecialchars($student_info['semester'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: linear-gradient(45deg, #007bff, #0056b3);">
                                        <i class="fas fa-laptop"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $stats['total_devices'] ?? 0; ?></div>
                                        <div class="stat-label">My Devices</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: linear-gradient(45deg, #28a745, #1e7e34);">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $stats['total_cards'] ?? 0; ?></div>
                                        <div class="stat-label">RFID Cards</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <div class="stat-icon" style="background: linear-gradient(45deg, #ffc107, #e0a800);">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $stats['total_logs'] ?? 0; ?></div>
                                        <div class="stat-label">Total Logs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Recent Activity -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock"></i> Recent Entry/Exit Activity
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_logs)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No recent activity found</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Status</th>
                                                    <th>Gate</th>
                                                    <th>Card ID</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_logs as $log): ?>
                                                    <tr>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo $log['status'] === 'entered' ? 'bg-success' : 'bg-danger'; ?>">
                                                                <?php echo ucfirst($log['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($log['gate_number'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($log['card_id'] ?? 'N/A'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="accountInfoSection" style="display:none;">
                <!-- Account Information Section -->
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-cog"></i> Account Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="detail-item">
                                    <strong>Username:</strong> <?php echo htmlspecialchars($student_info['username'] ?? 'N/A'); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($student_info['email'] ?? 'N/A'); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($student_info['phone'] ?? 'Not provided'); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Account Created:</strong> <?php echo isset($student_info['created_at']) ? date('M j, Y', strtotime($student_info['created_at'])) : 'N/A'; ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Status:</strong> <span class="badge <?php echo $student_info['is_active'] ? 'bg-success' : 'bg-danger'; ?>"><?php echo $student_info['is_active'] ? 'Active' : 'Inactive'; ?></span>
                                </div>
                                <div class="mt-3">
                                    <a href="../change_password.php" class="btn btn-warning btn-sm">
                                        <i class="fas fa-key"></i> Change Password
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Devices, Cards, and Borrowed Computers remain always visible below -->
            <div class="row mt-4">
                <!-- My Devices -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-laptop"></i> My Registered Devices
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($devices)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-laptop fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">No devices registered</p>
                                    <a href="../device/register_device.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Register Device
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="device-list">
                                    <?php foreach ($devices as $device): ?>
                                        <div class="device-item mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($device['device_name']); ?></h6>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-microchip"></i> <?php echo htmlspecialchars($device['serial_number']); ?>
                                                    </p>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-calendar"></i> Registered: <?php echo date('M j, Y', strtotime($device['created_at'])); ?>
                                                    </p>
                                                </div>
                                                <span class="badge bg-success">Active</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- My Cards -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-credit-card"></i> My RFID Cards
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($cards)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-credit-card fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">No RFID cards registered</p>
                                    <a href="../cards/register_card.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Register Card
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="card-list">
                                    <?php foreach ($cards as $card): ?>
                                        <div class="card-item mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Card #<?php echo htmlspecialchars($card['card_number']); ?></h6>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($card['card_id']); ?>
                                                    </p>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-calendar"></i> Registered: <?php echo date('M j, Y', strtotime($card['created_at'])); ?>
                                                    </p>
                                                </div>
                                                <span class="badge <?php echo $card['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $card['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Borrowed Computers Section -->
            <?php if (!empty($borrowed_computers)): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-laptop-house"></i> Currently Borrowed Computers
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Device Name</th>
                                                <th>Serial Number</th>
                                                <th>Owner</th>
                                                <th>Borrowed Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($borrowed_computers as $device): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($device['device_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($device['serial_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($device['owner_name']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($device['created_at'])); ?></td>
                                                    <td>
                                                        <a href="return_computer.php?device_id=<?php echo $device['id']; ?>" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-arrow-left"></i> Return
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
function showSection(section) {
    var studentSection = document.getElementById('studentInfoSection');
    var accountSection = document.getElementById('accountInfoSection');
    var btnStudent = document.getElementById('btnStudentInfo');
    var btnAccount = document.getElementById('btnAccountInfo');
    if (section === 'student') {
        studentSection.style.display = '';
        accountSection.style.display = 'none';
        btnStudent.classList.add('btn-primary');
        btnStudent.classList.remove('btn-outline-primary');
        btnAccount.classList.remove('btn-primary');
        btnAccount.classList.add('btn-outline-primary');
    } else {
        studentSection.style.display = 'none';
        accountSection.style.display = '';
        btnStudent.classList.remove('btn-primary');
        btnStudent.classList.add('btn-outline-primary');
        btnAccount.classList.add('btn-primary');
        btnAccount.classList.remove('btn-outline-primary');
    }
}
</script>

<style>
/* Page container for proper footer positioning */
.page-container {
    min-height: calc(100vh - 80px);
    display: flex;
    flex-direction: column;
    padding-bottom: 60px;
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

.student-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(45deg, #007bff, #0056b3);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
}

.student-details {
    text-align: left;
    margin-top: 1rem;
}

.detail-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.detail-item:last-child {
    border-bottom: none;
}

.device-item, .card-item {
    transition: all 0.3s ease;
}

.device-item:hover, .card-item:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.stat-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
    margin-bottom: 1rem;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
    font-weight: 500;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}
</style>

<?php include '../includes/footer.php'; ?> 