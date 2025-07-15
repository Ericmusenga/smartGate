<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Database configuration
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'gate_management_system';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get search parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $department_filter = isset($_GET['department']) ? trim($_GET['department']) : '';
    $year_filter = isset($_GET['year']) ? trim($_GET['year']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(s.registration_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($department_filter)) {
        $where_conditions[] = "s.department = ?";
        $params[] = $department_filter;
    }
    
    if (!empty($year_filter)) {
        $where_conditions[] = "s.year_of_study = ?";
        $params[] = $year_filter;
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "s.is_active = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get students with device count
    $sql = "SELECT s.*, 
                   u.username, 
                   u.is_first_login,
                   COUNT(d.id) as device_count
            FROM students s 
            LEFT JOIN users u ON s.id = u.student_id 
            LEFT JOIN devices d ON u.id = d.user_id AND d.is_registered = TRUE
            $where_clause 
            GROUP BY s.id 
            ORDER BY s.registration_number 
            LIMIT 50"; // Limit to 50 students for performance
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['students' => $students]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 