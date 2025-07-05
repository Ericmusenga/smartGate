<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check user type - only admin can access this
$user_type = get_user_type();
if ($user_type !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Handle GET request (view officer details)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        $officer_id = (int)$_GET['id'];
        
        $sql = "SELECT so.*, 
                       u.username, u.is_active as user_active, u.last_login, u.created_at as user_created,
                       (SELECT COUNT(*) FROM entry_exit_logs WHERE security_officer_id = so.id) as log_count,
                       (SELECT COUNT(*) FROM entry_exit_logs WHERE security_officer_id = so.id AND DATE(created_at) = CURDATE()) as today_logs
                FROM security_officers so
                LEFT JOIN users u ON so.id = u.security_officer_id
                WHERE so.id = ?";
        
        $officer = $db->fetch($sql, [$officer_id]);
        
        if (!$officer) {
            echo json_encode(['error' => 'Security officer not found']);
            exit;
        }
        
        // Get recent activity
        $recent_activity = $db->fetchAll("
            SELECT eel.*, 
                   s.registration_number, s.first_name, s.last_name,
                   u.username as user_username, u.first_name as user_first_name, u.last_name as user_last_name
            FROM entry_exit_logs eel
            LEFT JOIN users u ON eel.user_id = u.id
            LEFT JOIN students s ON u.student_id = s.id
            WHERE eel.security_officer_id = ?
            ORDER BY eel.created_at DESC
            LIMIT 10
        ", [$officer_id]);
        
        // Generate HTML for modal
        $html = '
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-user-shield"></i> Basic Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Security Code:</strong></td>
                        <td><code>' . htmlspecialchars($officer['security_code']) . '</code></td>
                    </tr>
                    <tr>
                        <td><strong>Full Name:</strong></td>
                        <td>' . htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td>' . htmlspecialchars($officer['email']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Phone:</strong></td>
                        <td>' . ($officer['phone'] ? htmlspecialchars($officer['phone']) : '<span class="text-muted">Not provided</span>') . '</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>' . ($officer['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>') . '</td>
                    </tr>
                    <tr>
                        <td><strong>Registered:</strong></td>
                        <td>' . date('M j, Y g:i A', strtotime($officer['created_at'])) . '</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-user-cog"></i> User Account</h6>
                <table class="table table-sm">';
        
        if ($officer['username']) {
            $html .= '
                    <tr>
                        <td><strong>Username:</strong></td>
                        <td><code>' . htmlspecialchars($officer['username']) . '</code></td>
                    </tr>
                    <tr>
                        <td><strong>Account Status:</strong></td>
                        <td>' . ($officer['user_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-warning">Inactive</span>') . '</td>
                    </tr>
                    <tr>
                        <td><strong>Account Created:</strong></td>
                        <td>' . date('M j, Y g:i A', strtotime($officer['user_created'])) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Last Login:</strong></td>
                        <td>' . ($officer['last_login'] ? date('M j, Y g:i A', strtotime($officer['last_login'])) : '<span class="text-muted">Never</span>') . '</td>
                    </tr>';
        } else {
            $html .= '
                    <tr>
                        <td colspan="2" class="text-center text-muted">
                            <i class="fas fa-exclamation-triangle"></i> No user account created yet
                        </td>
                    </tr>';
        }
        
        $html .= '
                </table>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <h6><i class="fas fa-chart-bar"></i> Activity Statistics</h6>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-primary">' . $officer['log_count'] . '</div>
                            <small class="text-muted">Total Logs</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-success">' . $officer['today_logs'] . '</div>
                            <small class="text-muted">Today\'s Logs</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-info">' . ($officer['last_login'] ? 'Yes' : 'No') . '</div>
                            <small class="text-muted">Has Logged In</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-warning">' . ($officer['is_active'] ? 'Active' : 'Inactive') . '</div>
                            <small class="text-muted">Current Status</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        if (!empty($recent_activity)) {
            $html .= '
        <div class="row mt-3">
            <div class="col-md-12">
                <h6><i class="fas fa-history"></i> Recent Activity</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Person</th>
                                <th>Action</th>
                                <th>Gate</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            foreach ($recent_activity as $activity) {
                $person_name = '';
                if ($activity['first_name'] && $activity['last_name']) {
                    // This is student data
                    $person_name = $activity['first_name'] . ' ' . $activity['last_name'];
                    if ($activity['registration_number']) {
                        $person_name .= ' (' . $activity['registration_number'] . ')';
                    }
                } elseif ($activity['user_first_name'] && $activity['user_last_name']) {
                    // This is user data (non-student)
                    $person_name = $activity['user_first_name'] . ' ' . $activity['user_last_name'];
                    if ($activity['user_username']) {
                        $person_name .= ' (' . $activity['user_username'] . ')';
                    }
                } elseif ($activity['user_username']) {
                    $person_name = $activity['user_username'];
                } else {
                    $person_name = 'Unknown';
                }
                
                $action_type = '';
                $action_class = '';
                if ($activity['entry_time'] && $activity['exit_time']) {
                    $action_type = 'Both';
                    $action_class = 'info';
                } elseif ($activity['entry_time']) {
                    $action_type = 'Entry';
                    $action_class = 'success';
                } elseif ($activity['exit_time']) {
                    $action_type = 'Exit';
                    $action_class = 'warning';
                } else {
                    $action_type = 'Unknown';
                    $action_class = 'secondary';
                }
                
                $html .= '
                            <tr>
                                <td>' . date('M j, g:i A', strtotime($activity['created_at'])) . '</td>
                                <td>' . htmlspecialchars($person_name) . '</td>
                                <td><span class="badge bg-' . $action_class . '">' . $action_type . '</span></td>
                                <td>Gate ' . $activity['gate_number'] . '</td>
                            </tr>';
            }
            
            $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
        }
        
        echo json_encode(['success' => true, 'html' => $html]);
        exit;
    }
    
    echo json_encode(['error' => 'Invalid request method']);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 