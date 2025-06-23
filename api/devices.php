<?php
require_once '../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = get_pdo();
    
    switch ($action) {
        case 'list':
            // Get list of devices for dropdowns
            $stmt = $pdo->prepare("
                SELECT id, name, device_type, location, status, ip_address, mac_address
                FROM devices 
                WHERE status = 'active'
                ORDER BY name
            ");
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'devices' => $devices
            ]);
            break;
            
        case 'search':
            // Search devices by name or location
            $search = $_GET['q'] ?? '';
            if (empty($search)) {
                echo json_encode(['success' => false, 'message' => 'Search term required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT id, name, device_type, location, status, ip_address, mac_address
                FROM devices 
                WHERE status = 'active' 
                AND (name LIKE :search OR location LIKE :search OR device_type LIKE :search)
                ORDER BY name
                LIMIT 20
            ");
            $stmt->execute(['search' => "%$search%"]);
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'devices' => $devices
            ]);
            break;
            
        case 'get':
            // Get specific device details
            $device_id = $_GET['id'] ?? '';
            if (empty($device_id)) {
                echo json_encode(['success' => false, 'message' => 'Device ID required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM devices WHERE id = :id
            ");
            $stmt->execute(['id' => $device_id]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$device) {
                echo json_encode(['success' => false, 'message' => 'Device not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'device' => $device
            ]);
            break;
            
        case 'stats':
            // Get device statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_devices,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_devices,
                    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_devices,
                    COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_devices
                FROM devices
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get devices by type
            $stmt = $pdo->prepare("
                SELECT device_type, COUNT(*) as count
                FROM devices 
                WHERE status = 'active'
                GROUP BY device_type
                ORDER BY count DESC
            ");
            $stmt->execute();
            $by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get devices by location
            $stmt = $pdo->prepare("
                SELECT location, COUNT(*) as count
                FROM devices 
                WHERE status = 'active'
                GROUP BY location
                ORDER BY count DESC
            ");
            $stmt->execute();
            $by_location = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'by_type' => $by_type,
                'by_location' => $by_location
            ]);
            break;
            
        case 'status':
            // Update device status
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
                exit;
            }
            
            $device_id = $_POST['device_id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if (empty($device_id) || empty($status)) {
                echo json_encode(['success' => false, 'message' => 'Device ID and status required']);
                exit;
            }
            
            $valid_statuses = ['active', 'inactive', 'maintenance'];
            if (!in_array($status, $valid_statuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE devices 
                SET status = :status, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['status' => $status, 'id' => $device_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Device status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Device not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database error in devices API: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 