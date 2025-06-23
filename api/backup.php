<?php
require_once '../config/config.php';

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
    switch ($action) {
        case 'create':
            // Create database backup
            $backup_dir = '../backups/';
            
            // Create backup directory if it doesn't exist
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            // Get database configuration
            $db_config = get_database_config();
            $host = $db_config['host'];
            $dbname = $db_config['database'];
            $username = $db_config['username'];
            $password = $db_config['password'];
            
            // Generate backup filename
            $timestamp = date('Y-m-d_H-i-s');
            $backup_filename = "backup_{$dbname}_{$timestamp}.sql";
            $backup_path = $backup_dir . $backup_filename;
            
            // Create backup using mysqldump
            $command = "mysqldump --host={$host} --user={$username} --password={$password} {$dbname} > {$backup_path}";
            
            // Execute backup command
            $output = [];
            $return_var = 0;
            exec($command, $output, $return_var);
            
            if ($return_var === 0 && file_exists($backup_path)) {
                // Log backup creation
                $pdo = get_pdo();
                $stmt = $pdo->prepare("
                    INSERT INTO system_logs (action, details, user_id, created_at) 
                    VALUES ('backup_created', :details, :user_id, NOW())
                ");
                $stmt->execute([
                    'details' => "Database backup created: {$backup_filename}",
                    'user_id' => $_SESSION['user_id']
                ]);
                
                // Set headers for file download
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $backup_filename . '"');
                header('Content-Length: ' . filesize($backup_path));
                
                // Output file content
                readfile($backup_path);
                
                // Clean up backup file after download
                unlink($backup_path);
                
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creating backup']);
            }
            break;
            
        case 'list':
            // List available backups
            $backup_dir = '../backups/';
            $backups = [];
            
            if (is_dir($backup_dir)) {
                $files = glob($backup_dir . '*.sql');
                foreach ($files as $file) {
                    $backups[] = [
                        'filename' => basename($file),
                        'size' => filesize($file),
                        'created' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'backups' => $backups
            ]);
            break;
            
        case 'delete':
            // Delete a backup file
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
                exit;
            }
            
            $filename = $_POST['filename'] ?? '';
            if (empty($filename)) {
                echo json_encode(['success' => false, 'message' => 'Filename required']);
                exit;
            }
            
            // Validate filename to prevent directory traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                echo json_encode(['success' => false, 'message' => 'Invalid filename']);
                exit;
            }
            
            $backup_path = '../backups/' . $filename;
            
            if (file_exists($backup_path) && unlink($backup_path)) {
                // Log backup deletion
                $pdo = get_pdo();
                $stmt = $pdo->prepare("
                    INSERT INTO system_logs (action, details, user_id, created_at) 
                    VALUES ('backup_deleted', :details, :user_id, NOW())
                ");
                $stmt->execute([
                    'details' => "Database backup deleted: {$filename}",
                    'user_id' => $_SESSION['user_id']
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Backup deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting backup']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error in backup API: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error occurred during backup operation']);
}

function get_database_config() {
    // This function should return database configuration
    // You may need to adjust this based on your config structure
    return [
        'host' => 'localhost',
        'database' => 'gate_management',
        'username' => 'root',
        'password' => ''
    ];
}
?> 