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
$dashboard_data = [];

try {
    $db = getDB();
    
    // Get user's student information
    $user = $db->fetch("SELECT student_id FROM users WHERE id = ?", [$user_id]);
    
    if ($user && $user['student_id']) {
        // Get devices count
        $device_count = $db->fetch("SELECT COUNT(*) as count FROM devices WHERE user_id = ?", [$user_id]);
        $dashboard_data['device_count'] = $device_count['count'];
        
        // Get cards count
        $card_count = $db->fetch("SELECT COUNT(*) as count FROM rfid_cards WHERE student_id = ? AND is_active = 1", [$user['student_id']]);
        $dashboard_data['card_count'] = $card_count['count'];
        
        // Get borrowed computers count
        $borrowed_count = $db->fetch("SELECT COUNT(*) as count FROM devices WHERE user_id = ? AND owner_id != ?", [$user_id, $user_id]);
        $dashboard_data['borrowed_count'] = $borrowed_count['count'];
        
        // Get total entry/exit logs
        $logs_count = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs WHERE user_id = ?", [$user_id]);
        $dashboard_data['logs_count'] = $logs_count['count'];
        
        // Get today's logs
        $today_logs = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$user_id]);
        $dashboard_data['today_logs'] = $today_logs['count'];
        
        // Get recent entry/exit logs for the last 7 days
        $recent_logs = $db->fetchAll("
            SELECT DATE(created_at) as date, COUNT(*) as count, status
            FROM entry_exit_logs 
            WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at), status
            ORDER BY date DESC
        ", [$user_id]);
        
        // Process data for charts
        $chart_data = [];
        $entry_data = [];
        $exit_data = [];
        
        // Initialize last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $entry_data[$date] = 0;
            $exit_data[$date] = 0;
        }
        
        // Fill in actual data
        foreach ($recent_logs as $log) {
            $date = $log['date'];
            if ($log['status'] === 'entered') {
                $entry_data[$date] = $log['count'];
            } elseif ($log['status'] === 'exited') {
                $exit_data[$date] = $log['count'];
            }
        }
        
        $dashboard_data['chart_labels'] = array_keys($entry_data);
        $dashboard_data['entry_data'] = array_values($entry_data);
        $dashboard_data['exit_data'] = array_values($exit_data);
        
        // Get monthly activity for the current month
        $monthly_activity = $db->fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_entries,
                SUM(CASE WHEN status = 'entered' THEN 1 ELSE 0 END) as entries,
                SUM(CASE WHEN status = 'exited' THEN 1 ELSE 0 END) as exits
            FROM entry_exit_logs 
            WHERE user_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ", [$user_id]);
        
        $dashboard_data['monthly_activity'] = $monthly_activity;
        
        // Get gate usage statistics
        $gate_stats = $db->fetchAll("
            SELECT gate_number, COUNT(*) as usage_count
            FROM entry_exit_logs 
            WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY gate_number
            ORDER BY usage_count DESC
        ", [$user_id]);
        
        $dashboard_data['gate_stats'] = $gate_stats;
        
        // Get last login time
        $last_login = $db->fetch("SELECT last_login FROM users WHERE id = ?", [$user_id]);
        $dashboard_data['last_login'] = $last_login['last_login'];
        
        // Get profile completion status
        $profile_info = $db->fetch("
            SELECT 
                CASE WHEN phone IS NOT NULL AND phone != '' THEN 1 ELSE 0 END +
                CASE WHEN address IS NOT NULL AND address != '' THEN 1 ELSE 0 END +
                CASE WHEN emergency_contact IS NOT NULL AND emergency_contact != '' THEN 1 ELSE 0 END +
                CASE WHEN emergency_phone IS NOT NULL AND emergency_phone != '' THEN 1 ELSE 0 END as completed_fields
            FROM users WHERE id = ?
        ", [$user_id]);
        
        $dashboard_data['profile_completion'] = round(($profile_info['completed_fields'] / 4) * 100);
        
    } else {
        $error_message = 'Student information not found.';
        $dashboard_data = [
            'device_count' => 0,
            'card_count' => 0,
            'borrowed_count' => 0,
            'logs_count' => 0,
            'today_logs' => 0,
            'profile_completion' => 0
        ];
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading dashboard data: ' . $e->getMessage();
    $dashboard_data = [
        'device_count' => 0,
        'card_count' => 0,
        'borrowed_count' => 0,
        'logs_count' => 0,
        'today_logs' => 0,
        'profile_completion' => 0
    ];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Student'); ?>!</div>
            <div class="page-subtitle">Your Dashboard & Quick Actions</div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <a href="student_info.php" class="card clickable-card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-user-circle"></i> My Information</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #007bff;">
                        <i class="fas fa-user"></i>
                    </span>
                    <div>View Complete Profile</div>
                </div>
            </a>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-laptop"></i> My Devices</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #007bff;"><?php echo $dashboard_data['device_count']; ?></span>
                    <div>Registered Devices</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-laptop-house"></i> Borrowed Computers</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #28a745;"><?php echo $dashboard_data['borrowed_count'] ?? 0; ?></span>
                    <div>Currently Borrowed</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-credit-card"></i> My Cards</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #28a745;"><?php echo $dashboard_data['card_count']; ?></span>
                    <div>Active RFID Cards</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-clipboard-list"></i> Total Logs</span>
                </div>
                <div class="card-body text-center">
                    <span class="page-title" style="font-size:2rem; color: #ffc107;"><?php echo $dashboard_data['logs_count']; ?></span>
                    <div>Entry/Exit Logs</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row">
            <!-- Weekly Activity Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Weekly Entry/Exit Activity</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="weeklyActivityChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Gate Usage Chart -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> Gate Usage (Last 30 Days)</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="gateUsageChart" width="300" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Today's Activity</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <span class="display-4 text-primary"><?php echo $dashboard_data['today_logs']; ?></span>
                            <p class="text-muted">Entry/Exit records today</p>
                        </div>
                        <?php if ($dashboard_data['last_login']): ?>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> Last login: <?php echo date('M j, Y g:i A', strtotime($dashboard_data['last_login'])); ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-tasks"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../device/my_devices.php" class="btn btn-outline-primary">
                                <i class="fas fa-laptop"></i> View My Devices
                            </a>
                            <a href="lend_computer.php" class="btn btn-outline-success">
                                <i class="fas fa-arrow-circle-right"></i> Borrow Computer
                            </a>
                            <a href="return_computer.php" class="btn btn-outline-warning">
                                <i class="fas fa-arrow-circle-left"></i> Return Computer
                            </a>
                            <a href="my_borrowed_computers.php" class="btn btn-outline-info">
                                <i class="fas fa-laptop-house"></i> My Borrowed Computers
                            </a>
                            <a href="../cards/my_cards.php" class="btn btn-outline-success">
                                <i class="fas fa-credit-card"></i> View My Cards
                            </a>
                            <a href="my_logs.php" class="btn btn-outline-info">
                                <i class="fas fa-clipboard-list"></i> View Entry/Exit Logs
                            </a>
                            <a href="profile.php" class="btn btn-outline-warning">
                                <i class="fas fa-user-edit"></i> Update Profile
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
// Weekly Activity Chart
const weeklyCtx = document.getElementById('weeklyActivityChart').getContext('2d');
const weeklyChart = new Chart(weeklyCtx, {
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
                text: 'Last 7 Days Activity'
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

// Gate Usage Chart
const gateCtx = document.getElementById('gateUsageChart').getContext('2d');
const gateData = <?php echo json_encode($dashboard_data['gate_stats'] ?? []); ?>;

if (gateData.length > 0) {
    const gateChart = new Chart(gateCtx, {
        type: 'doughnut',
        data: {
            labels: gateData.map(item => 'Gate ' + item.gate_number),
            datasets: [{
                data: gateData.map(item => item.usage_count),
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6f42c1',
                    '#fd7e14'
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
                },
                title: {
                    display: true,
                    text: 'Gate Usage Distribution'
                }
            }
        }
    });
} else {
    // Show message if no gate data
    gateCtx.font = '16px Arial';
    gateCtx.fillStyle = '#666';
    gateCtx.textAlign = 'center';
    gateCtx.fillText('No gate usage data available', 150, 100);
}
</script>

<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.clickable-card {
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: all 0.3s ease;
}

.clickable-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
    text-decoration: none;
    color: inherit;
}

.clickable-card .card-header {
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
}

.clickable-card .card-title {
    color: white;
}

.display-4 {
    font-weight: bold;
}

.btn-outline-primary:hover,
.btn-outline-success:hover,
.btn-outline-info:hover,
.btn-outline-warning:hover {
    transform: translateY(-1px);
}
</style>

<?php include '../includes/footer.php'; ?> 