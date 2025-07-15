<?php
require_once '../config/config.php';
if (!is_logged_in()) { redirect('../login.php'); }
if (get_user_type() !== 'admin') { redirect('../unauthorized.php'); }

// Fetch dashboard data (stats, analytics, etc.)
$error_message = '';
$success_message = '';
$dashboard_data = [];

try {
    $db = getDB();
    // System-wide statistics
    $stats = $db->fetch("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_users,
            (SELECT COUNT(*) FROM students WHERE is_active = 1) as total_students,
            (SELECT COUNT(*) FROM devices WHERE is_registered = 1) as total_devices,
            (SELECT COUNT(*) FROM entry_exit_logs) as total_logs,
            (SELECT COUNT(*) FROM entry_exit_logs WHERE DATE(created_at) = CURDATE()) as today_logs,
            (SELECT COUNT(*) FROM reports) as total_reports,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today
    ");
    $dashboard_data['stats'] = $stats;
    // User type distribution
    $user_types = $db->fetchAll("
        SELECT r.role_name, COUNT(u.id) as count
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.is_active = 1
        GROUP BY r.role_name
        ORDER BY count DESC
    ");
    $dashboard_data['user_types'] = $user_types;
    // Daily entry/exit activity for last 30 days
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
    // Gate usage statistics
    $gate_usage = $db->fetchAll("
        SELECT gate_number, COUNT(*) as usage_count
        FROM entry_exit_logs 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY gate_number
        ORDER BY usage_count DESC
    ");
    $dashboard_data['gate_usage'] = $gate_usage;
    // Department statistics
    $department_stats = $db->fetchAll("
        SELECT department, COUNT(*) as student_count
        FROM students 
        WHERE is_active = 1
        GROUP BY department
        ORDER BY student_count DESC
        LIMIT 10
    ");
    $dashboard_data['department_stats'] = $department_stats;
    // Recent system activity (last 10 entries)
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
    // Monthly user registration trends
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
    // Device type distribution
    $device_types = $db->fetchAll("
        SELECT device_type, COUNT(*) as count
        FROM devices 
        WHERE is_registered = 1
        GROUP BY device_type
        ORDER BY count DESC
    ");
    $dashboard_data['device_types'] = $device_types;
    // System performance metrics
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
            <div class="page-title">Welcome, <?php echo $_SESSION['username']; ?>!</div>
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
                    <span class="page-title" style="font-size:2rem; color: #dc3545;"><?php echo $dashboard_data['stats']['total_cards'] ?? 0; ?></span>
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
                    <small class="text-muted">Last hour: <?php echo $dashboard_data['performance']['last_hour_entries'] ?? 0; ?></small>
                </div>
            </div>
        </div>
        <!-- Add more dashboard sections here: charts, analytics, quick actions, etc. -->
    </div>
</main>
<?php include '../includes/footer.php'; ?> 