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
        case 'quick_stats':
            // Get quick statistics for dashboard
            $stats = [];
            
            // Total students
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
            $stmt->execute();
            $stats['total_students'] = $stmt->fetch()['count'];
            
            // Active devices
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM devices WHERE status = 'active'");
            $stmt->execute();
            $stats['active_devices'] = $stmt->fetch()['count'];
            
            // Today's entries
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM entry_exit_logs WHERE DATE(entry_time) = CURDATE()");
            $stmt->execute();
            $stats['today_entries'] = $stmt->fetch()['count'];
            
            // Average daily entries (last 30 days)
            $stmt = $pdo->prepare("
                SELECT AVG(daily_count) as avg_entries 
                FROM (
                    SELECT DATE(entry_time) as date, COUNT(*) as daily_count
                    FROM entry_exit_logs 
                    WHERE entry_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY DATE(entry_time)
                ) as daily_counts
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['avg_daily_entries'] = round($result['avg_entries'] ?? 0);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'generate':
            // Generate report based on parameters
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
                exit;
            }
            
            $report_type = $_POST['report_type'] ?? '';
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $format = $_POST['format'] ?? 'pdf';
            
            if (empty($report_type) || empty($start_date) || empty($end_date)) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }
            
            // Validate date range
            if (strtotime($start_date) > strtotime($end_date)) {
                echo json_encode(['success' => false, 'message' => 'Start date cannot be after end date']);
                exit;
            }
            
            // Generate report based on type
            $report_data = generateReportData($pdo, $report_type, $start_date, $end_date, $_POST);
            
            if ($report_data === false) {
                echo json_encode(['success' => false, 'message' => 'Error generating report data']);
                exit;
            }
            
            // Set appropriate headers for file download
            $filename = "report_{$report_type}_{$start_date}_to_{$end_date}";
            
            switch ($format) {
                case 'csv':
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                    outputCSV($report_data);
                    break;
                    
                case 'excel':
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
                    outputExcel($report_data);
                    break;
                    
                case 'pdf':
                default:
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
                    outputPDF($report_data, $report_type);
                    break;
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database error in reports API: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

function generateReportData($pdo, $report_type, $start_date, $end_date, $filters) {
    switch ($report_type) {
        case 'entry_exit':
            return generateEntryExitReport($pdo, $start_date, $end_date, $filters);
            
        case 'attendance':
            return generateAttendanceReport($pdo, $start_date, $end_date, $filters);
            
        case 'device_usage':
            return generateDeviceUsageReport($pdo, $start_date, $end_date, $filters);
            
        case 'rfid_cards':
            return generateRFIDCardReport($pdo, $start_date, $end_date, $filters);
            
        case 'security':
            return generateSecurityReport($pdo, $start_date, $end_date, $filters);
            
        case 'summary':
            return generateSummaryReport($pdo, $start_date, $end_date, $filters);
            
        default:
            return false;
    }
}

function generateEntryExitReport($pdo, $start_date, $end_date, $filters) {
    $where_conditions = ["DATE(eel.entry_time) BETWEEN :start_date AND :end_date"];
    $params = ['start_date' => $start_date, 'end_date' => $end_date];
    
    // Add filters
    if (!empty($filters['student_id'])) {
        $where_conditions[] = "eel.student_id = :student_id";
        $params['student_id'] = $filters['student_id'];
    }
    
    if (!empty($filters['device_id'])) {
        $where_conditions[] = "eel.device_id = :device_id";
        $params['device_id'] = $filters['device_id'];
    }
    
    if (!empty($filters['entry_method'])) {
        $where_conditions[] = "eel.entry_method = :entry_method";
        $params['entry_method'] = $filters['entry_method'];
    }
    
    if (!empty($filters['program'])) {
        $where_conditions[] = "s.program = :program";
        $params['program'] = $filters['program'];
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "
        SELECT 
            eel.id,
            s.registration_number,
            s.first_name,
            s.last_name,
            s.program,
            eel.entry_time,
            eel.exit_time,
            eel.entry_method,
            eel.exit_method,
            eel.entry_notes,
            eel.exit_notes,
            d.name as device_name,
            d.location as device_location,
            TIMESTAMPDIFF(MINUTE, eel.entry_time, eel.exit_time) as duration_minutes
        FROM entry_exit_logs eel
        JOIN students s ON eel.student_id = s.id
        LEFT JOIN devices d ON eel.device_id = d.id
        WHERE {$where_clause}
        ORDER BY eel.entry_time DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'title' => 'Entry/Exit Report',
        'period' => "{$start_date} to {$end_date}",
        'headers' => [
            'ID', 'Registration Number', 'First Name', 'Last Name', 'Program',
            'Entry Time', 'Exit Time', 'Entry Method', 'Exit Method',
            'Entry Notes', 'Exit Notes', 'Device', 'Location', 'Duration (min)'
        ],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function generateAttendanceReport($pdo, $start_date, $end_date, $filters) {
    $where_conditions = ["DATE(eel.entry_time) BETWEEN :start_date AND :end_date"];
    $params = ['start_date' => $start_date, 'end_date' => $end_date];
    
    if (!empty($filters['student_id'])) {
        $where_conditions[] = "eel.student_id = :student_id";
        $params['student_id'] = $filters['student_id'];
    }
    
    if (!empty($filters['program'])) {
        $where_conditions[] = "s.program = :program";
        $params['program'] = $filters['program'];
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "
        SELECT 
            s.registration_number,
            s.first_name,
            s.last_name,
            s.program,
            COUNT(DISTINCT DATE(eel.entry_time)) as days_present,
            COUNT(eel.id) as total_entries,
            MIN(eel.entry_time) as first_entry,
            MAX(eel.entry_time) as last_entry,
            AVG(TIMESTAMPDIFF(MINUTE, eel.entry_time, eel.exit_time)) as avg_duration
        FROM students s
        LEFT JOIN entry_exit_logs eel ON s.id = eel.student_id AND {$where_clause}
        WHERE s.status = 'active'
        GROUP BY s.id, s.registration_number, s.first_name, s.last_name, s.program
        ORDER BY s.first_name, s.last_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'title' => 'Student Attendance Report',
        'period' => "{$start_date} to {$end_date}",
        'headers' => [
            'Registration Number', 'First Name', 'Last Name', 'Program',
            'Days Present', 'Total Entries', 'First Entry', 'Last Entry', 'Avg Duration (min)'
        ],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function generateDeviceUsageReport($pdo, $start_date, $end_date, $filters) {
    $where_conditions = ["DATE(eel.entry_time) BETWEEN :start_date AND :end_date"];
    $params = ['start_date' => $start_date, 'end_date' => $end_date];
    
    if (!empty($filters['device_type'])) {
        $where_conditions[] = "d.device_type = :device_type";
        $params['device_type'] = $filters['device_type'];
    }
    
    if (!empty($filters['location'])) {
        $where_conditions[] = "d.location = :location";
        $params['location'] = $filters['location'];
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "
        SELECT 
            d.name,
            d.device_type,
            d.location,
            d.status,
            COUNT(eel.id) as total_entries,
            COUNT(DISTINCT eel.student_id) as unique_students,
            COUNT(DISTINCT DATE(eel.entry_time)) as active_days,
            MIN(eel.entry_time) as first_usage,
            MAX(eel.entry_time) as last_usage
        FROM devices d
        LEFT JOIN entry_exit_logs eel ON d.id = eel.device_id AND {$where_clause}
        WHERE d.status = 'active'
        GROUP BY d.id, d.name, d.device_type, d.location, d.status
        ORDER BY total_entries DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'title' => 'Device Usage Report',
        'period' => "{$start_date} to {$end_date}",
        'headers' => [
            'Device Name', 'Type', 'Location', 'Status', 'Total Entries',
            'Unique Students', 'Active Days', 'First Usage', 'Last Usage'
        ],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function generateRFIDCardReport($pdo, $start_date, $end_date, $filters) {
    $sql = "
        SELECT 
            rc.card_number,
            rc.status,
            rc.issue_date,
            rc.expiry_date,
            s.registration_number,
            s.first_name,
            s.last_name,
            s.program,
            COUNT(eel.id) as usage_count,
            MAX(eel.entry_time) as last_used
        FROM rfid_cards rc
        JOIN students s ON rc.student_id = s.id
        LEFT JOIN entry_exit_logs eel ON rc.card_number = eel.rfid_card_number 
            AND DATE(eel.entry_time) BETWEEN :start_date AND :end_date
        WHERE s.status = 'active'
        GROUP BY rc.id, rc.card_number, rc.status, rc.issue_date, rc.expiry_date,
                 s.registration_number, s.first_name, s.last_name, s.program
        ORDER BY s.first_name, s.last_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    
    return [
        'title' => 'RFID Card Report',
        'period' => "{$start_date} to {$end_date}",
        'headers' => [
            'Card Number', 'Status', 'Issue Date', 'Expiry Date',
            'Registration Number', 'First Name', 'Last Name', 'Program',
            'Usage Count', 'Last Used'
        ],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function generateSecurityReport($pdo, $start_date, $end_date, $filters) {
    $sql = "
        SELECT 
            eel.id,
            s.registration_number,
            s.first_name,
            s.last_name,
            eel.entry_time,
            eel.exit_time,
            eel.entry_method,
            eel.exit_method,
            eel.entry_notes,
            eel.exit_notes,
            d.name as device_name,
            d.location as device_location,
            CASE 
                WHEN eel.exit_time IS NULL AND DATE(eel.entry_time) < CURDATE() THEN 'Overdue Exit'
                WHEN eel.entry_method = 'manual' THEN 'Manual Entry'
                ELSE 'Normal'
            END as security_flag
        FROM entry_exit_logs eel
        JOIN students s ON eel.student_id = s.id
        LEFT JOIN devices d ON eel.device_id = d.id
        WHERE DATE(eel.entry_time) BETWEEN :start_date AND :end_date
        AND (
            eel.exit_time IS NULL AND DATE(eel.entry_time) < CURDATE()
            OR eel.entry_method = 'manual'
            OR eel.exit_method = 'manual'
        )
        ORDER BY eel.entry_time DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    
    return [
        'title' => 'Security Report',
        'period' => "{$start_date} to {$end_date}",
        'headers' => [
            'ID', 'Registration Number', 'First Name', 'Last Name',
            'Entry Time', 'Exit Time', 'Entry Method', 'Exit Method',
            'Entry Notes', 'Exit Notes', 'Device', 'Location', 'Security Flag'
        ],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function generateSummaryReport($pdo, $start_date, $end_date, $filters) {
    // Get summary statistics
    $stats = [];
    
    // Total entries
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM entry_exit_logs 
        WHERE DATE(entry_time) BETWEEN :start_date AND :end_date
    ");
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $stats['total_entries'] = $stmt->fetch()['count'];
    
    // Unique students
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT student_id) as count 
        FROM entry_exit_logs 
        WHERE DATE(entry_time) BETWEEN :start_date AND :end_date
    ");
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $stats['unique_students'] = $stmt->fetch()['count'];
    
    // Average daily entries
    $stmt = $pdo->prepare("
        SELECT AVG(daily_count) as avg_entries 
        FROM (
            SELECT DATE(entry_time) as date, COUNT(*) as daily_count
            FROM entry_exit_logs 
            WHERE DATE(entry_time) BETWEEN :start_date AND :end_date
            GROUP BY DATE(entry_time)
        ) as daily_counts
    ");
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $result = $stmt->fetch();
    $stats['avg_daily_entries'] = round($result['avg_entries'] ?? 0);
    
    // Device usage breakdown
    $stmt = $pdo->prepare("
        SELECT d.name, COUNT(eel.id) as usage_count
        FROM devices d
        LEFT JOIN entry_exit_logs eel ON d.id = eel.device_id 
            AND DATE(eel.entry_time) BETWEEN :start_date AND :end_date
        WHERE d.status = 'active'
        GROUP BY d.id, d.name
        ORDER BY usage_count DESC
    ");
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $stats['device_usage'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'title' => 'Summary Report',
        'period' => "{$start_date} to {$end_date}",
        'summary_stats' => $stats,
        'headers' => ['Metric', 'Value'],
        'data' => [
            ['Total Entries', $stats['total_entries']],
            ['Unique Students', $stats['unique_students']],
            ['Average Daily Entries', $stats['avg_daily_entries']]
        ]
    ];
}

function outputCSV($report_data) {
    $output = fopen('php://output', 'w');
    
    // Add title and period
    fputcsv($output, [$report_data['title']]);
    fputcsv($output, ['Period: ' . $report_data['period']]);
    fputcsv($output, []); // Empty row
    
    // Add headers
    fputcsv($output, $report_data['headers']);
    
    // Add data
    foreach ($report_data['data'] as $row) {
        fputcsv($output, array_values($row));
    }
    
    fclose($output);
}

function outputExcel($report_data) {
    echo "<table border='1'>";
    
    // Add title and period
    echo "<tr><td colspan='" . count($report_data['headers']) . "'><strong>{$report_data['title']}</strong></td></tr>";
    echo "<tr><td colspan='" . count($report_data['headers']) . "'>Period: {$report_data['period']}</td></tr>";
    echo "<tr></tr>"; // Empty row
    
    // Add headers
    echo "<tr>";
    foreach ($report_data['headers'] as $header) {
        echo "<th><strong>{$header}</strong></th>";
    }
    echo "</tr>";
    
    // Add data
    foreach ($report_data['data'] as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>{$value}</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
}

function outputPDF($report_data, $report_type) {
    // For now, output as HTML that can be converted to PDF
    // In a real implementation, you would use a library like TCPDF or FPDF
    
    echo "<html><head><title>{$report_data['title']}</title></head><body>";
    echo "<h1>{$report_data['title']}</h1>";
    echo "<p><strong>Period:</strong> {$report_data['period']}</p>";
    echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
    if (isset($report_data['summary_stats'])) {
        echo "<h2>Summary Statistics</h2>";
        echo "<ul>";
        echo "<li>Total Entries: {$report_data['summary_stats']['total_entries']}</li>";
        echo "<li>Unique Students: {$report_data['summary_stats']['unique_students']}</li>";
        echo "<li>Average Daily Entries: {$report_data['summary_stats']['avg_daily_entries']}</li>";
        echo "</ul>";
        
        if (!empty($report_data['summary_stats']['device_usage'])) {
            echo "<h3>Device Usage</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Device</th><th>Usage Count</th></tr>";
            foreach ($report_data['summary_stats']['device_usage'] as $device) {
                echo "<tr><td>{$device['name']}</td><td>{$device['usage_count']}</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        foreach ($report_data['headers'] as $header) {
            echo "<th style='padding: 8px; background-color: #f2f2f2;'>{$header}</th>";
        }
        echo "</tr>";
        
        foreach ($report_data['data'] as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td style='padding: 8px;'>{$value}</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "</body></html>";
}
?> 