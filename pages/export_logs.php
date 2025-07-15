<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in()) {
    redirect('../login.php');
}

if (get_user_type() !== 'admin') {
    redirect('../login.php');
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'gate_management_system');

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter parameters
$date_filter = $_GET['date'] ?? date('Y-m-d');
$type_filter = $_GET['type'] ?? 'all';
$gate_filter = $_GET['gate'] ?? 'all';

// Build the query based on filters
$where_conditions = [];
$params = [];

if ($date_filter) {
    $where_conditions[] = "DATE(entry_time) = ? OR DATE(exit_time) = ?";
    $params[] = $date_filter;
    $params[] = $date_filter;
}

if ($gate_filter !== 'all') {
    $where_conditions[] = "gate_number = ?";
    $params[] = $gate_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fetch student entry logs
$student_entry_sql = "
    SELECT 
        'Student Entry' as log_type,
        es.entry_time as time,
        CONCAT(s.first_name, ' ', s.last_name) as name,
        s.registration_number as id_number,
        'Student' as person_type,
        CONCAT(s.department, ' - ', s.program) as department_purpose,
        'Entry' as action,
        es.gate_number,
        es.notes,
        es.created_at
    FROM entry_student es
    LEFT JOIN students s ON es.student_id = s.id
    $where_clause
";

// Fetch student exit logs
$student_exit_sql = "
    SELECT 
        'Student Exit' as log_type,
        exs.exit_time as time,
        CONCAT(s.first_name, ' ', s.last_name) as name,
        s.registration_number as id_number,
        'Student' as person_type,
        CONCAT(s.department, ' - ', s.program) as department_purpose,
        'Exit' as action,
        exs.gate_number,
        exs.notes,
        exs.created_at
    FROM exit_student exs
    LEFT JOIN students s ON exs.student_id = s.id
    $where_clause
";

// Fetch visitor entry logs
$visitor_entry_sql = "
    SELECT 
        'Visitor Entry' as log_type,
        ev.entry_time as time,
        v.visitor_name as name,
        v.id_number,
        'Visitor' as person_type,
        v.purpose as department_purpose,
        'Entry' as action,
        ev.gate_number,
        ev.notes,
        ev.created_at
    FROM entry_visitor ev
    LEFT JOIN visitors v ON ev.visitor_id = v.id
    $where_clause
";

// Fetch visitor exit logs
$visitor_exit_sql = "
    SELECT 
        'Visitor Exit' as log_type,
        exv.exit_time as time,
        v.visitor_name as name,
        v.id_number,
        'Visitor' as person_type,
        v.purpose as department_purpose,
        'Exit' as action,
        exv.gate_number,
        exv.notes,
        exv.created_at
    FROM exit_visitor exv
    LEFT JOIN visitors v ON exv.visitor_id = v.id
    $where_clause
";

// Combine all queries
$combined_sql = "
    ($student_entry_sql)
    UNION ALL
    ($student_exit_sql)
    UNION ALL
    ($visitor_entry_sql)
    UNION ALL
    ($visitor_exit_sql)
    ORDER BY created_at DESC
";

// Prepare and execute the query
$stmt = $conn->prepare($combined_sql);
if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $logs = [];
}

$conn->close();

// Set headers for CSV download
$filename = 'entry_exit_logs_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = [
    'Log Type',
    'Date',
    'Time',
    'Name',
    'ID/Registration Number',
    'Person Type',
    'Department/Purpose',
    'Action',
    'Gate Number',
    'Notes',
    'Created At'
];

// Write headers
fputcsv($output, $headers);

// Write data rows
foreach ($logs as $log) {
    $date = $log['time'] ? date('Y-m-d', strtotime($log['time'])) : '';
    $time = $log['time'] ? date('H:i:s', strtotime($log['time'])) : '';
    
    $row = [
        $log['log_type'],
        $date,
        $time,
        $log['name'],
        $log['id_number'],
        $log['person_type'],
        $log['department_purpose'],
        $log['action'],
        $log['gate_number'],
        $log['notes'],
        $log['created_at']
    ];
    
    fputcsv($output, $row);
}

// Close the file pointer
fclose($output);
exit;
?> 