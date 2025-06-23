<?php
require_once '../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (get_user_type() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Administrators only.']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = get_pdo();
    
    switch ($action) {
        case 'run':
            // Run system maintenance
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
                exit;
            }
            
            $maintenance_tasks = $_POST['tasks'] ?? ['clean_logs', 'optimize_db', 'clean_sessions'];
            $results = [];
            
            foreach ($maintenance_tasks as $task) {
                switch ($task) {
                    case 'clean_logs':
                        $results['clean_logs'] = cleanOldLogs($pdo);
                        break;
                        
                    case 'optimize_db':
                        $results['optimize_db'] = optimizeDatabase($pdo);
                        break;
                        
                    case 'clean_sessions':
                        $results['clean_sessions'] = cleanExpiredSessions($pdo);
                        break;
                        
                    case 'clean_backups':
                        $results['clean_backups'] = cleanOldBackups();
                        break;
                        
                    case 'update_statistics':
                        $results['update_statistics'] = updateStatistics($pdo);
                        break;
                }
            }
            
            // Log maintenance completion
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (action, details, user_id, created_at) 
                VALUES ('maintenance_run', :details, :user_id, NOW())
            ");
            $stmt->execute([
                'details' => 'System maintenance completed: ' . implode(', ', $maintenance_tasks),
                'user_id' => $_SESSION['user_id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Maintenance completed successfully',
                'results' => $results
            ]);
            break;
            
        case 'status':
            // Get system status and statistics
            $status = getSystemStatus($pdo);
            echo json_encode([
                'success' => true,
                'status' => $status
            ]);
            break;
            
        case 'clean_logs':
            // Clean old logs only
            $result = cleanOldLogs($pdo);
            echo json_encode([
                'success' => true,
                'message' => 'Logs cleaned successfully',
                'result' => $result
            ]);
            break;
            
        case 'optimize_db':
            // Optimize database only
            $result = optimizeDatabase($pdo);
            echo json_encode([
                'success' => true,
                'message' => 'Database optimized successfully',
                'result' => $result
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database error in maintenance API: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

function cleanOldLogs($pdo) {
    // Clean entry/exit logs older than 1 year
    $stmt = $pdo->prepare("
        DELETE FROM entry_exit_logs 
        WHERE entry_time < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    $stmt->execute();
    $deleted_logs = $stmt->rowCount();
    
    // Clean system logs older than 6 months
    $stmt = $pdo->prepare("
        DELETE FROM system_logs 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)
    ");
    $stmt->execute();
    $deleted_system_logs = $stmt->rowCount();
    
    return [
        'deleted_entry_logs' => $deleted_logs,
        'deleted_system_logs' => $deleted_system_logs
    ];
}

function optimizeDatabase($pdo) {
    $tables = [
        'users', 'students', 'security_officers', 'devices', 
        'entry_exit_logs', 'rfid_cards', 'system_logs', 'settings'
    ];
    
    $results = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("OPTIMIZE TABLE {$table}");
            $stmt->execute();
            $results[$table] = 'optimized';
        } catch (PDOException $e) {
            $results[$table] = 'error: ' . $e->getMessage();
        }
    }
    
    return $results;
}

function cleanExpiredSessions($pdo) {
    // Clean expired sessions (older than 24 hours)
    $stmt = $pdo->prepare("
        DELETE FROM sessions 
        WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $deleted_sessions = $stmt->fetch();
    
    return [
        'deleted_sessions' => $deleted_sessions
    ];
}

function cleanOldBackups() {
    $backup_dir = '../backups/';
    $deleted_backups = 0;
    
    if (is_dir($backup_dir)) {
        $files = glob($backup_dir . '*.sql');
        foreach ($files as $file) {
            // Delete backups older than 30 days
            if (filemtime($file) < strtotime('-30 days')) {
                if (unlink($file)) {
                    $deleted_backups++;
                }
            }
        }
    }
    
    return [
        'deleted_backups' => $deleted_backups
    ];
}

function updateStatistics($pdo) {
    // Update various statistics
    $stats = [];
    
    // Total students
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $stmt->execute();
    $stats['total_students'] = $stmt->fetch()['count'];
    
    // Total devices
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM devices WHERE status = 'active'");
    $stmt->execute();
    $stats['total_devices'] = $stmt->fetch()['count'];
    
    // Today's entries
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM entry_exit_logs WHERE DATE(entry_time) = CURDATE()");
    $stmt->execute();
    $stats['today_entries'] = $stmt->fetch()['count'];
    
    // Currently on campus
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT student_id) as count 
        FROM entry_exit_logs 
        WHERE DATE(entry_time) = CURDATE() AND exit_time IS NULL
    ");
    $stmt->execute();
    $stats['on_campus'] = $stmt->fetch()['count'];
    
    // Store statistics in settings table
    foreach ($stats as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_at) 
            VALUES (:key, :value, NOW())
            ON DUPLICATE KEY UPDATE 
            setting_value = :value, updated_at = NOW()
        ");
        $stmt->execute(['key' => 'stat_' . $key, 'value' => $value]);
    }
    
    return $stats;
}

function getSystemStatus($pdo) {
    $status = [];
    
    // Database connection status
    try {
        $pdo->query("SELECT 1");
        $status['database'] = 'connected';
    } catch (PDOException $e) {
        $status['database'] = 'error';
    }
    
    // Disk space
    $disk_free = disk_free_space('../');
    $disk_total = disk_total_space('../');
    $disk_used_percent = round((($disk_total - $disk_free) / $disk_total) * 100, 2);
    
    $status['disk_space'] = [
        'free' => formatBytes($disk_free),
        'total' => formatBytes($disk_total),
        'used_percent' => $disk_used_percent
    ];
    
    // Database size
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $status['database_size'] = $result['size_mb'] . ' MB';
    } catch (PDOException $e) {
        $status['database_size'] = 'unknown';
    }
    
    // System uptime
    $status['uptime'] = getSystemUptime();
    
    // PHP version
    $status['php_version'] = PHP_VERSION;
    
    // Memory usage
    $memory_usage = memory_get_usage(true);
    $memory_peak = memory_get_peak_usage(true);
    $status['memory'] = [
        'current' => formatBytes($memory_usage),
        'peak' => formatBytes($memory_peak)
    ];
    
    return $status;
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function getSystemUptime() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        return [
            'load_1min' => $load[0],
            'load_5min' => $load[1],
            'load_15min' => $load[2]
        ];
    }
    
    return 'unavailable';
}
?> 