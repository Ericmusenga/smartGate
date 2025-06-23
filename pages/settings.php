<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in()) {
    redirect('../login.php');
}

if (get_user_type() !== 'admin') {
    redirect('../unauthorized.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = get_pdo();
        
        switch ($action) {
            case 'update_general':
                // Update general settings
                $settings = [
                    'site_name' => $_POST['site_name'] ?? '',
                    'site_description' => $_POST['site_description'] ?? '',
                    'admin_email' => $_POST['admin_email'] ?? '',
                    'timezone' => $_POST['timezone'] ?? 'UTC',
                    'date_format' => $_POST['date_format'] ?? 'Y-m-d',
                    'time_format' => $_POST['time_format'] ?? 'H:i:s'
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value, updated_at) 
                        VALUES (:key, :value, NOW())
                        ON DUPLICATE KEY UPDATE 
                        setting_value = :value, updated_at = NOW()
                    ");
                    $stmt->execute(['key' => $key, 'value' => $value]);
                }
                
                $success_message = 'General settings updated successfully!';
                break;
                
            case 'update_security':
                // Update security settings
                $settings = [
                    'session_timeout' => $_POST['session_timeout'] ?? '3600',
                    'max_login_attempts' => $_POST['max_login_attempts'] ?? '5',
                    'lockout_duration' => $_POST['lockout_duration'] ?? '900',
                    'require_password_change' => isset($_POST['require_password_change']) ? '1' : '0',
                    'password_min_length' => $_POST['password_min_length'] ?? '8',
                    'password_complexity' => isset($_POST['password_complexity']) ? '1' : '0'
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value, updated_at) 
                        VALUES (:key, :value, NOW())
                        ON DUPLICATE KEY UPDATE 
                        setting_value = :value, updated_at = NOW()
                    ");
                    $stmt->execute(['key' => $key, 'value' => $value]);
                }
                
                $success_message = 'Security settings updated successfully!';
                break;
                
            case 'update_gate':
                // Update gate management settings
                $settings = [
                    'auto_logout_hours' => $_POST['auto_logout_hours'] ?? '24',
                    'allow_manual_entries' => isset($_POST['allow_manual_entries']) ? '1' : '0',
                    'require_exit_logging' => isset($_POST['require_exit_logging']) ? '1' : '0',
                    'rfid_timeout_seconds' => $_POST['rfid_timeout_seconds'] ?? '30',
                    'max_concurrent_entries' => $_POST['max_concurrent_entries'] ?? '100'
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value, updated_at) 
                        VALUES (:key, :value, NOW())
                        ON DUPLICATE KEY UPDATE 
                        setting_value = :value, updated_at = NOW()
                    ");
                    $stmt->execute(['key' => $key, 'value' => $value]);
                }
                
                $success_message = 'Gate management settings updated successfully!';
                break;
                
            case 'update_notifications':
                // Update notification settings
                $settings = [
                    'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0',
                    'sms_notifications' => isset($_POST['sms_notifications']) ? '1' : '0',
                    'notification_email' => $_POST['notification_email'] ?? '',
                    'alert_unauthorized_access' => isset($_POST['alert_unauthorized_access']) ? '1' : '0',
                    'alert_device_offline' => isset($_POST['alert_device_offline']) ? '1' : '0'
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value, updated_at) 
                        VALUES (:key, :value, NOW())
                        ON DUPLICATE KEY UPDATE 
                        setting_value = :value, updated_at = NOW()
                    ");
                    $stmt->execute(['key' => $key, 'value' => $value]);
                }
                
                $success_message = 'Notification settings updated successfully!';
                break;
        }
        
    } catch (PDOException $e) {
        error_log("Database error in settings: " . $e->getMessage());
        $error_message = 'Error updating settings. Please try again.';
    }
}

// Load current settings
try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Database error loading settings: " . $e->getMessage());
    $settings = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">System Settings</div>
            <div class="page-subtitle">Configure system parameters and preferences</div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Settings Navigation -->
        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                    <i class="fas fa-cog"></i> General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                    <i class="fas fa-shield-alt"></i> Security
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="gate-tab" data-bs-toggle="tab" data-bs-target="#gate" type="button" role="tab">
                    <i class="fas fa-door-open"></i> Gate Management
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                    <i class="fas fa-bell"></i> Notifications
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                    <i class="fas fa-database"></i> Backup & Maintenance
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsTabContent">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-cog"></i> General Settings</span>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_general">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="site_name" class="form-label">Site Name</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" 
                                           value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Gate Management System'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="admin_email" class="form-label">Admin Email</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                           value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="site_description" class="form-label">Site Description</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? 'University of Rwanda College of Education - Gate Management System'); ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <option value="UTC" <?php echo ($settings['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                        <option value="Africa/Kigali" <?php echo ($settings['timezone'] ?? '') === 'Africa/Kigali' ? 'selected' : ''; ?>>Africa/Kigali</option>
                                        <option value="Africa/Nairobi" <?php echo ($settings['timezone'] ?? '') === 'Africa/Nairobi' ? 'selected' : ''; ?>>Africa/Nairobi</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="date_format" class="form-label">Date Format</label>
                                    <select class="form-select" id="date_format" name="date_format">
                                        <option value="Y-m-d" <?php echo ($settings['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                        <option value="d/m/Y" <?php echo ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                        <option value="m/d/Y" <?php echo ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="time_format" class="form-label">Time Format</label>
                                    <select class="form-select" id="time_format" name="time_format">
                                        <option value="H:i:s" <?php echo ($settings['time_format'] ?? 'H:i:s') === 'H:i:s' ? 'selected' : ''; ?>>24-hour</option>
                                        <option value="h:i:s A" <?php echo ($settings['time_format'] ?? '') === 'h:i:s A' ? 'selected' : ''; ?>>12-hour</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save General Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-shield-alt"></i> Security Settings</span>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_security">
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="session_timeout" class="form-label">Session Timeout (seconds)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                           value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '3600'); ?>" min="300" max="86400">
                                    <small class="form-text text-muted">Default: 3600 (1 hour)</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                    <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                           value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '5'); ?>" min="3" max="10">
                                </div>
                                <div class="col-md-4">
                                    <label for="lockout_duration" class="form-label">Lockout Duration (seconds)</label>
                                    <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" 
                                           value="<?php echo htmlspecialchars($settings['lockout_duration'] ?? '900'); ?>" min="300" max="3600">
                                    <small class="form-text text-muted">Default: 900 (15 minutes)</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password_min_length" class="form-label">Minimum Password Length</label>
                                    <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                           value="<?php echo htmlspecialchars($settings['password_min_length'] ?? '8'); ?>" min="6" max="20">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="password_complexity" name="password_complexity" 
                                               <?php echo ($settings['password_complexity'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="password_complexity">
                                            Require Complex Passwords
                                        </label>
                                        <small class="form-text text-muted d-block">Requires uppercase, lowercase, numbers, and special characters</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="require_password_change" name="require_password_change" 
                                       <?php echo ($settings['require_password_change'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="require_password_change">
                                    Require Password Change on First Login
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Security Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Gate Management Settings -->
            <div class="tab-pane fade" id="gate" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-door-open"></i> Gate Management Settings</span>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_gate">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="auto_logout_hours" class="form-label">Auto Logout Hours</label>
                                    <input type="number" class="form-control" id="auto_logout_hours" name="auto_logout_hours" 
                                           value="<?php echo htmlspecialchars($settings['auto_logout_hours'] ?? '24'); ?>" min="1" max="168">
                                    <small class="form-text text-muted">Automatically log out students after specified hours</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="rfid_timeout_seconds" class="form-label">RFID Timeout (seconds)</label>
                                    <input type="number" class="form-control" id="rfid_timeout_seconds" name="rfid_timeout_seconds" 
                                           value="<?php echo htmlspecialchars($settings['rfid_timeout_seconds'] ?? '30'); ?>" min="10" max="300">
                                    <small class="form-text text-muted">Timeout for RFID card reads</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="max_concurrent_entries" class="form-label">Max Concurrent Entries</label>
                                    <input type="number" class="form-control" id="max_concurrent_entries" name="max_concurrent_entries" 
                                           value="<?php echo htmlspecialchars($settings['max_concurrent_entries'] ?? '100'); ?>" min="10" max="1000">
                                    <small class="form-text text-muted">Maximum number of students allowed on campus simultaneously</small>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="allow_manual_entries" name="allow_manual_entries" 
                                       <?php echo ($settings['allow_manual_entries'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_manual_entries">
                                    Allow Manual Entry/Exit Logging
                                </label>
                                <small class="form-text text-muted d-block">Allow security officers to manually log entries and exits</small>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="require_exit_logging" name="require_exit_logging" 
                                       <?php echo ($settings['require_exit_logging'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="require_exit_logging">
                                    Require Exit Logging
                                </label>
                                <small class="form-text text-muted d-block">Require students to log out when leaving campus</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Gate Management Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-bell"></i> Notification Settings</span>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_notifications">
                            
                            <div class="mb-3">
                                <label for="notification_email" class="form-label">Notification Email</label>
                                <input type="email" class="form-control" id="notification_email" name="notification_email" 
                                       value="<?php echo htmlspecialchars($settings['notification_email'] ?? ''); ?>">
                                <small class="form-text text-muted">Email address for system notifications and alerts</small>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                       <?php echo ($settings['email_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_notifications">
                                    Enable Email Notifications
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications" 
                                       <?php echo ($settings['sms_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_notifications">
                                    Enable SMS Notifications
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="alert_unauthorized_access" name="alert_unauthorized_access" 
                                       <?php echo ($settings['alert_unauthorized_access'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="alert_unauthorized_access">
                                    Alert on Unauthorized Access Attempts
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="alert_device_offline" name="alert_device_offline" 
                                       <?php echo ($settings['alert_device_offline'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="alert_device_offline">
                                    Alert when Devices Go Offline
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Notification Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Backup & Maintenance -->
            <div class="tab-pane fade" id="backup" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-database"></i> Backup & Maintenance</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Database Backup</h5>
                                        <p class="card-text">Create a backup of the database for safekeeping.</p>
                                        <button class="btn btn-primary" onclick="createBackup()">
                                            <i class="fas fa-download"></i> Create Backup
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">System Maintenance</h5>
                                        <p class="card-text">Clean up old logs and optimize database performance.</p>
                                        <button class="btn btn-warning" onclick="runMaintenance()">
                                            <i class="fas fa-tools"></i> Run Maintenance
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>System Information</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td><strong>PHP Version:</strong></td>
                                            <td><?php echo PHP_VERSION; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Database:</strong></td>
                                            <td>MySQL</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Server Time:</strong></td>
                                            <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>System Status:</strong></td>
                                            <td><span class="badge bg-success">Online</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function createBackup() {
    if (confirm('Are you sure you want to create a database backup?')) {
        window.location.href = '../api/backup.php?action=create';
    }
}

function runMaintenance() {
    if (confirm('Are you sure you want to run system maintenance? This may take a few minutes.')) {
        fetch('../api/maintenance.php?action=run', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Maintenance completed successfully!', 'success');
            } else {
                showAlert(data.message || 'Error running maintenance', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error running maintenance', 'error');
        });
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.content-wrapper').insertBefore(alertDiv, document.querySelector('.content-wrapper').firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<?php include '../includes/footer.php'; ?> 