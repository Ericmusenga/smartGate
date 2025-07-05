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
$dashboard_data = [];

// Handle security officer registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_security') {
    $security_code = trim($_POST['security_code'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$security_code || !$first_name || !$last_name || !$email) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $db = getDB();
            // Check for duplicate security code or email
            $exists = $db->fetch("SELECT id FROM security_officers WHERE security_code = ? OR email = ?", [$security_code, $email]);
            if ($exists) {
                $error_message = 'A security officer with this code or email already exists.';
            } else {
                // Insert into security_officers
                $db->query("INSERT INTO security_officers (security_code, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?)",
                    [$security_code, $first_name, $last_name, $email, $phone]);
                
                // Get the inserted officer's ID
                $officer_id = $db->lastInsertId();
                
                // Create user account for the security officer
                $role_id = $db->fetch("SELECT id FROM roles WHERE role_name = 'security'")['id'];
                $default_password = password_hash($security_code, PASSWORD_DEFAULT); // Default password is the security code
                
                $db->query("INSERT INTO users (username, password, email, first_name, last_name, role_id, security_officer_id, is_first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$security_code, $default_password, $email, $first_name, $last_name, $role_id, $officer_id, TRUE]);
                
                $success_message = 'Security officer registered successfully! User account created with default password (security code).';
            }
        } catch (Exception $e) {
            $error_message = 'Error registering security officer: ' . $e->getMessage();
        }
    }
}

try {
    $db = getDB();
    
    // Get system-wide statistics
    $stats = $db->fetch("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_users,
            (SELECT COUNT(*) FROM students WHERE is_active = 1) as total_students,
            (SELECT COUNT(*) FROM devices WHERE is_registered = 1) as total_devices,
            (SELECT COUNT(*) FROM rfid_cards WHERE is_active = 1) as total_cards,
            (SELECT COUNT(*) FROM entry_exit_logs) as total_logs,
            (SELECT COUNT(*) FROM entry_exit_logs WHERE DATE(created_at) = CURDATE()) as today_logs,
            (SELECT COUNT(*) FROM reports) as total_reports,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today
    ");
    
    $dashboard_data['stats'] = $stats;
    
    // Get user type distribution
    $user_types = $db->fetchAll("
        SELECT r.role_name, COUNT(u.id) as count
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.is_active = 1
        GROUP BY r.role_name
        ORDER BY count DESC
    ");
    
    $dashboard_data['user_types'] = $user_types;
    
    // Get daily entry/exit activity for last 30 days
    $daily_activity = $db->fetchAll("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total_entries,
            SUM(CASE WHEN status = 'entered' THEN 1 ELSE 0 END) as entries,
            SUM(CASE WHEN status = 'exited' THEN 1 ELSE 0 END) as exits
        FROM entry_exit_logs 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    
    // Process daily activity data for charts
    $chart_labels = [];
    $entry_data = [];
    $exit_data = [];
    
    foreach (array_reverse($daily_activity) as $activity) {
        $chart_labels[] = date('M j', strtotime($activity['date']));
        $entry_data[] = $activity['entries'];
        $exit_data[] = $activity['exits'];
    }
    
    $dashboard_data['chart_labels'] = $chart_labels;
    $dashboard_data['entry_data'] = $entry_data;
    $dashboard_data['exit_data'] = $exit_data;
    
    // Get gate usage statistics
    $gate_usage = $db->fetchAll("
        SELECT gate_number, COUNT(*) as usage_count
        FROM entry_exit_logs 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY gate_number
        ORDER BY usage_count DESC
    ");
    
    $dashboard_data['gate_usage'] = $gate_usage;
    
    // Get department statistics
    $department_stats = $db->fetchAll("
        SELECT department, COUNT(*) as student_count
        FROM students 
        WHERE is_active = 1
        GROUP BY department
        ORDER BY student_count DESC
        LIMIT 10
    ");
    
    $dashboard_data['department_stats'] = $department_stats;
    
    // Get recent system activity (last 10 entries)
    $recent_activity = $db->fetchAll("
        SELECT 
            eel.created_at,
            eel.status,
            eel.gate_number,
            eel.entry_method,
            u.first_name,
            u.last_name,
            s.registration_number,
            r.role_name
        FROM entry_exit_logs eel
        LEFT JOIN users u ON eel.user_id = u.id
        LEFT JOIN students s ON u.student_id = s.id
        LEFT JOIN roles r ON u.role_id = r.id
        ORDER BY eel.created_at DESC
        LIMIT 10
    ");
    
    $dashboard_data['recent_activity'] = $recent_activity;
    
    // Get monthly user registration trends
    $monthly_registrations = $db->fetchAll("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_users
        FROM users 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    
    $dashboard_data['monthly_registrations'] = $monthly_registrations;
    
    // Get device type distribution
    $device_types = $db->fetchAll("
        SELECT device_type, COUNT(*) as count
        FROM devices 
        WHERE is_registered = 1
        GROUP BY device_type
        ORDER BY count DESC
    ");
    
    $dashboard_data['device_types'] = $device_types;
    
    // Get system performance metrics
    $performance_metrics = $db->fetch("
        SELECT 
            (SELECT COUNT(*) FROM entry_exit_logs WHERE DATE(created_at) = CURDATE()) as today_entries,
            (SELECT COUNT(DISTINCT user_id) FROM entry_exit_logs WHERE DATE(created_at) = CURDATE()) as active_users_today,
            (SELECT COUNT(*) FROM entry_exit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)) as last_hour_entries,
            (SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as users_last_24h
    ");
    
    $dashboard_data['performance'] = $performance_metrics;
    
} catch (Exception $e) {
    $error_message = 'Error loading dashboard data: ' . $e->getMessage();
    $dashboard_data = [
        'stats' => [
            'total_users' => 0,
            'total_students' => 0,
            'total_devices' => 0,
            'total_cards' => 0,
            'total_logs' => 0,
            'today_logs' => 0,
            'total_reports' => 0,
            'new_users_today' => 0
        ]
    ];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?>!</div>
            <div class="page-subtitle">System Overview & Analytics Dashboard</div>
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
        
        <!-- System Statistics Cards -->
        <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-users"></i> Total Users</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #007bff;"><?php echo $dashboard_data['stats']['total_users']; ?></span>
                    <div>Registered Users</div>
                    <small class="text-muted">+<?php echo $dashboard_data['stats']['new_users_today']; ?> today</small>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-graduation-cap"></i> Students</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #28a745;"><?php echo $dashboard_data['stats']['total_students']; ?></span>
                    <div>Active Students</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-laptop"></i> Devices</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #ffc107;"><?php echo $dashboard_data['stats']['total_devices']; ?></span>
                    <div>Registered Devices</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-credit-card"></i> Cards</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #dc3545;"><?php echo $dashboard_data['stats']['total_cards']; ?></span>
                    <div>Active RFID Cards</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-clipboard-list"></i> Today's Logs</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #6f42c1;"><?php echo $dashboard_data['stats']['today_logs']; ?></span>
                    <div>Entry/Exit Records</div>
                    <small class="text-muted">Last hour: <?php echo $dashboard_data['performance']['last_hour_entries']; ?></small>
                </div>
            </div>
        </div>
        
        <!-- Security Officer Registration Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-shield"></i> Register New Security Officer
                    <button type="button" class="btn btn-sm btn-outline-primary float-end" onclick="toggleSecurityForm()">
                        <i class="fas fa-plus"></i> Toggle Form
                    </button>
                </h5>
            </div>
            <div class="card-body" id="securityRegistrationForm" style="display: none;">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="register_security">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="security_code" class="form-label">Security Code *</label>
                                <input type="text" id="security_code" name="security_code" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['security_code'] ?? ''); ?>">
                                <div class="form-text">Unique identifier for the security officer</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" id="email" name="email" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" id="first_name" name="first_name" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" id="phone" name="phone" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                <div class="form-text">Optional contact number</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Register Security Officer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> A user account will be automatically created with the security code as the username and default password.
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Charts Row 1 -->
        <div class="row">
            <!-- Daily Activity Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Daily Entry/Exit Activity (Last 30 Days)</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyActivityChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- User Type Distribution -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> User Type Distribution</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="userTypeChart" width="300" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row 2 -->
        <div class="row mt-4">
            <!-- Gate Usage Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Gate Usage (Last 30 Days)</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="gateUsageChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Department Distribution -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-doughnut"></i> Student Department Distribution</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="departmentChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Information Row -->
        <div class="row mt-4">
            <!-- Recent Activity -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent System Activity</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Gate</th>
                                        <th>Method</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboard_data['recent_activity'] as $activity): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $user_name = '';
                                            if ($activity['first_name'] && $activity['last_name']) {
                                                $user_name = $activity['first_name'] . ' ' . $activity['last_name'];
                                            } elseif ($activity['registration_number']) {
                                                $user_name = 'Student: ' . $activity['registration_number'];
                                            } else {
                                                $user_name = 'Unknown User';
                                            }
                                            echo htmlspecialchars($user_name);
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $activity['status'] === 'entered' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($activity['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">Gate <?php echo $activity['gate_number']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo strtoupper($activity['entry_method']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions & Performance -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-tachometer-alt"></i> System Performance</h3>
                    </div>
                    <div class="card-body">
                        <div class="performance-item">
                            <label>Active Users (24h):</label>
                            <div class="value"><?php echo $dashboard_data['performance']['users_last_24h']; ?></div>
                        </div>
                        <div class="performance-item">
                            <label>Today's Entries:</label>
                            <div class="value"><?php echo $dashboard_data['performance']['today_entries']; ?></div>
                        </div>
                        <div class="performance-item">
                            <label>Last Hour Activity:</label>
                            <div class="value"><?php echo $dashboard_data['performance']['last_hour_entries']; ?></div>
                        </div>
                        <div class="performance-item">
                            <label>Active Users Today:</label>
                            <div class="value"><?php echo $dashboard_data['performance']['active_users_today']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h3><i class="fas fa-tasks"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="users.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                            <a href="students.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-graduation-cap"></i> Manage Students
                            </a>
                            <a href="devices.php" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-laptop"></i> Manage Devices
                            </a>
                            <a href="cards.php" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-credit-card"></i> Manage Cards
                            </a>
                            <a href="logs.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-clipboard-list"></i> View Logs
                            </a>
                            <a href="reports.php" class="btn btn-outline-dark btn-sm">
                                <i class="fas fa-chart-bar"></i> Generate Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Daily Activity Chart
const dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
const dailyChart = new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dashboard_data['chart_labels'] ?? []); ?>,
        datasets: [{
            label: 'Entries',
            data: <?php echo json_encode($dashboard_data['entry_data'] ?? []); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Exits',
            data: <?php echo json_encode($dashboard_data['exit_data'] ?? []); ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'System Activity Trends'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// User Type Distribution Chart
const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
const userTypeData = <?php echo json_encode($dashboard_data['user_types'] ?? []); ?>;

if (userTypeData.length > 0) {
    const userTypeChart = new Chart(userTypeCtx, {
        type: 'doughnut',
        data: {
            labels: userTypeData.map(item => item.role_name.charAt(0).toUpperCase() + item.role_name.slice(1)),
            datasets: [{
                data: userTypeData.map(item => item.count),
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6f42c1'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

// Gate Usage Chart
const gateCtx = document.getElementById('gateUsageChart').getContext('2d');
const gateData = <?php echo json_encode($dashboard_data['gate_usage'] ?? []); ?>;

if (gateData.length > 0) {
    const gateChart = new Chart(gateCtx, {
        type: 'bar',
        data: {
            labels: gateData.map(item => 'Gate ' + item.gate_number),
            datasets: [{
                label: 'Usage Count',
                data: gateData.map(item => item.usage_count),
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6f42c1',
                    '#fd7e14'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Department Distribution Chart
const deptCtx = document.getElementById('departmentChart').getContext('2d');
const deptData = <?php echo json_encode($dashboard_data['department_stats'] ?? []); ?>;

if (deptData.length > 0) {
    const deptChart = new Chart(deptCtx, {
        type: 'doughnut',
        data: {
            labels: deptData.map(item => item.department),
            datasets: [{
                data: deptData.map(item => item.student_count),
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6f42c1',
                    '#fd7e14',
                    '#20c997',
                    '#e83e8c',
                    '#6c757d',
                    '#343a40'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12
                    }
                }
            }
        }
    });
}

// Function to toggle security registration form
function toggleSecurityForm() {
    const form = document.getElementById('securityRegistrationForm');
    const button = event.target.closest('button');
    const icon = button.querySelector('i');
    
    if (form.style.display === 'none') {
        form.style.display = 'block';
        icon.className = 'fas fa-minus';
        button.innerHTML = '<i class="fas fa-minus"></i> Hide Form';
    } else {
        form.style.display = 'none';
        icon.className = 'fas fa-plus';
        button.innerHTML = '<i class="fas fa-plus"></i> Toggle Form';
    }
}
</script>

<style>
.card {
    transition: transform 0.2s ease-in-out;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.performance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.performance-item:last-child {
    border-bottom: none;
}

.performance-item label {
    font-weight: 600;
    color: #666;
    font-size: 0.9rem;
}

.performance-item .value {
    font-weight: bold;
    color: #007bff;
    font-size: 1.1rem;
}

.btn-outline-primary:hover,
.btn-outline-success:hover,
.btn-outline-warning:hover,
.btn-outline-info:hover,
.btn-outline-secondary:hover,
.btn-outline-dark:hover {
    transform: translateY(-1px);
}

.table-sm td, .table-sm th {
    padding: 0.5rem;
    font-size: 0.9rem;
}

.badge {
    font-size: 0.75rem;
}
</style>

<?php include '../includes/footer.php'; ?> 