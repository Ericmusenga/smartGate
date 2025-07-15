<?php
// Updated Database Configuration
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'gate_management_system'; // Your database

// API endpoint (still for fetching into passenger_data if needed elsewhere, but not directly for displaying rfid_logs)
$apiUrl = 'https://ibitaro.jftech.rw/pazzo/fyp.txt';

// Connect to database for API data insertion (passenger_data table)
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Corrected path for log file if needed, relative to the script
    file_put_contents(__DIR__ . '/fetch_error.log', date('Y-m-d H:i:s') . " - PDO DB Connection Failed: " . $e->getMessage() . "\n", FILE_APPEND);
    // Log and continue, as this might be a background process
}

// Fetch data from API and insert into passenger_data table
// This block remains to ensure data from the API is still being processed and stored.
// If 'passenger_data' is no longer needed in your system at all, this block can be removed entirely.
if (isset($pdo)) {
    $apiData = @file_get_contents($apiUrl);
    if ($apiData === false) {
        file_put_contents(__DIR__ . '/fetch_error.log', date('Y-m-d H:i:s') . " - Failed to fetch data from API\n", FILE_APPEND);
    } else {
        $lines = explode("\n", trim($apiData));
        $insertCount = 0;

        foreach ($lines as $line) {
            if (empty($line)) continue;

            $data = json_decode($line, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                file_put_contents(__DIR__ . '/fetch_error.log', date('Y-m-d H:i:s') . " - JSON Error: " . json_last_error_msg() . "\n", FILE_APPEND);
                continue;
            }

            // Validate required fields
            if (!isset($data['server_timestamp'], $data['device_timestamp'], $data['event'],
                       $data['rfid_uid'], $data['passenger_count'], $data['status'])) {
                file_put_contents(__DIR__ . '/fetch_error.log', date('Y-m-d H:i:s') . " - Missing fields: " . json_encode($data) . "\n", FILE_APPEND);
                continue;
            }

            try {
                // Avoid duplicate entries
                $check = $pdo->prepare("SELECT id FROM passenger_data WHERE server_timestamp = :timestamp AND rfid_uid = :uid");
                $check->execute([
                    ':timestamp' => $data['server_timestamp'],
                    ':uid' => $data['rfid_uid']
                ]);

                if ($check->rowCount() > 0) continue;

                // Insert data
                $stmt = $pdo->prepare("INSERT INTO passenger_data
                    (server_timestamp, device_timestamp, event, rfid_uid, passenger_count, status)
                    VALUES (:server_ts, :device_ts, :event, :uid, :count, :status)");

                $stmt->execute([
                    ':server_ts' => $data['server_timestamp'],
                    ':device_ts' => $data['device_timestamp'],
                    ':event'     => $data['event'],
                    ':uid'       => $data['rfid_uid'],
                    ':count'     => (int)$data['passenger_count'],
                    ':status'    => $data['status']
                ]);

                $insertCount++;
            } catch (PDOException $e) {
                file_put_contents(__DIR__ . '/fetch_error.log', date('Y-m-d H:i:s') . " - Insert Error: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
        file_put_contents(__DIR__ . '/fetch_success.log', date('Y-m-d H:i:s') . " - Inserted $insertCount records\n", FILE_APPEND);
    }
}

// Handle AJAX requests for checking unauthorized cards
if (isset($_GET['check_unauthorized']) && $_GET['check_unauthorized'] === '1') {
    try {
        // Check for unauthorized cards in the last 10 seconds
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM entry_exit_logs 
            WHERE status = 'unauthorized' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        header('Content-Type: application/json');
        echo json_encode([
            'has_unauthorized' => $result['count'] > 0,
            'count' => $result['count']
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Handle AJAX requests for table updates
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    try {
        // Fetch recent RFID logs with student information (including unauthorized)
        $stmt = $pdo->prepare("
            SELECT 
                eel.created_at,
                eel.status,
                eel.entry_method,
                eel.notes,
                COALESCE(rc.card_number, SUBSTRING_INDEX(eel.notes, 'Card UID: ', -1)) as uid,
                s.registration_number,
                s.first_name,
                s.last_name,
                s.department,
                s.program,
                s.year_of_study,
                s.phone,
                s.serial_number
            FROM entry_exit_logs eel
            LEFT JOIN rfid_cards rc ON eel.rfid_card_id = rc.id
            LEFT JOIN students s ON rc.student_id = s.id
            WHERE eel.entry_method = 'rfid'
            ORDER BY eel.created_at DESC
            LIMIT 20
        ");
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($logs)) {
            echo "<tr><td colspan='7' style='text-align: center; color: #666;'>No RFID logs found</td></tr>";
        } else {
            foreach ($logs as $log) {
                $is_unauthorized = $log['status'] === 'unauthorized';
                
                if ($is_unauthorized) {
                    // Handle unauthorized cards
                    $student_name = 'UNAUTHORIZED CARD';
                    $status_class = 'log-status-unauthorized';
                    $status_text = "<span style='color:blue;font-weight:bold;'>Not Yet</span>";
                    $registration_number = 'N/A';
                    $department = 'N/A';
                    $serial_number = 'N/A';
                    $phone = 'N/A';
                    $year = 'N/A';
                } else {
                    // Handle authorized cards
                    $student_name = $log['first_name'] && $log['last_name'] 
                        ? $log['first_name'] . ' ' . $log['last_name'] 
                        : 'Unknown Student';
                    if ($log['status'] === 'entered') {
                        $status_class = 'log-status-entry';
                        $status_text = "<span style='color:green;font-weight:bold;'>IN</span>";
                    } elseif ($log['status'] === 'exited') {
                        $status_class = 'log-status-exit';
                        $status_text = "<span style='color:red;font-weight:bold;'>OUT</span>";
                    } else {
                        $status_class = '';
                        $status_text = htmlspecialchars($log['status'] ?? '');
                    }
                    $registration_number = htmlspecialchars($log['registration_number'] ?? 'N/A');
                    $department = htmlspecialchars($log['department'] ?? 'N/A');
                    $serial_number = htmlspecialchars($log['serial_number'] ?? 'N/A');
                    $phone = htmlspecialchars($log['phone'] ?? 'N/A');
                    $year = htmlspecialchars($log['year_of_study'] ?? 'N/A');
                }
                
                echo "<tr>";
                echo "<td>" . date('H:i:s', strtotime($log['created_at'])) . "</td>";
                echo "<td>" . htmlspecialchars($student_name) . "</td>";
                echo "<td>" . $serial_number . "</td>";
                echo "<td>" . $registration_number . "</td>";
                echo "<td>" . $department . "</td>";
                echo "<td>" . $phone . "</td>";
                echo "<td>" . $year . "</td>";
                echo "<td>$status_text</td>";
                echo "<td><code>" . htmlspecialchars($log['uid'] ?? 'N/A') . "</code></td>";
                echo "<td>" . strtoupper(htmlspecialchars($log['entry_method'])) . "</td>";
                echo "</tr>";
            }
        }
        exit;
    } catch (PDOException $e) {
        echo "<tr><td colspan='7' style='text-align: center; color: red;'>Error loading logs: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        exit;
    }
}

// Handle POST requests from hardware (RFID scans)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get POST data from hardware
        $event = $_POST['event'] ?? 'RFID_SCAN';
        $count = intval($_POST['count'] ?? 0);
        $status = $_POST['status'] ?? 'UNKNOWN';
        // Map IN/OUT to entered/exited for database consistency
        if (strtoupper($status) === 'IN') {
            $status = 'entered';
        } elseif (strtoupper($status) === 'OUT') {
            $status = 'exited';
        }
        $timestamp = $_POST['timestamp'] ?? date('H:i:s');
        $uid = $_POST['uid'] ?? 'UNKNOWN';
        
        // Check if this is an unauthorized card
        $unauthorized = isset($_POST['unauthorized']) && $_POST['unauthorized'] === 'true';
        $error_type = $_POST['error_type'] ?? '';
        
        // Student information from hardware (only for authorized cards)
        $student_id = intval($_POST['student_id'] ?? 0);
        $registration_number = $_POST['registration_number'] ?? '';
        $student_name = $_POST['student_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $department = $_POST['department'] ?? '';
        $program = $_POST['program'] ?? '';
        $year_of_study = $_POST['year_of_study'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $card_type = $_POST['card_type'] ?? '';
        $has_account = intval($_POST['has_account'] ?? 0);
        $username = $_POST['username'] ?? '';
        $is_first_login = intval($_POST['is_first_login'] ?? 0);
        
        // Get RFID card ID
        $rfid_card_id = null;
        if (!empty($uid)) {
            $stmt = $pdo->prepare("SELECT id FROM rfid_cards WHERE card_number = ?");
            $stmt->execute([$uid]);
            $card_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $rfid_card_id = $card_result ? $card_result['id'] : null;
        }
        
        // Get user ID if student has account
        $user_id = null;
        if ($has_account && !empty($username)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $user_result ? $user_result['id'] : null;
        }
        
        if ($unauthorized) {
            // Handle unauthorized card
            $entry_status = 'unauthorized';
            $entry_time = null;
            $exit_time = null;
            $notes = "UNAUTHORIZED RFID CARD - Card UID: $uid - Error: $error_type";
            
            // Insert unauthorized card log
            $stmt = $pdo->prepare("INSERT INTO entry_exit_logs (
                user_id, rfid_card_id, entry_time, exit_time, gate_number, 
                entry_method, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                null, // No user_id for unauthorized cards
                null, // No rfid_card_id for unauthorized cards
                $entry_time,
                $exit_time,
                1, // Default gate number
                'rfid',
                $notes,
                $entry_status
            ]);
            
            // Log unauthorized access
            file_put_contents(__DIR__ . '/unauthorized_access.log', date('Y-m-d H:i:s') . " - Unauthorized Card: $uid - Error: $error_type\n", FILE_APPEND);
            
        } else {
            // Handle authorized card
            $entry_status = ($status === 'entered') ? 'entered' : 'exited';
            $entry_time = ($status === 'entered') ? date('Y-m-d H:i:s') : null;
            $exit_time = ($status === 'exited') ? date('Y-m-d H:i:s') : null;
            
            // Insert into entry_exit_logs table
            $stmt = $pdo->prepare("INSERT INTO entry_exit_logs (
                user_id, rfid_card_id, entry_time, exit_time, gate_number, 
                entry_method, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $notes = "RFID Scan - Student: $student_name ($registration_number) - Department: $department";
            
            $stmt->execute([
                $user_id,
                $rfid_card_id,
                $entry_time,
                $exit_time,
                1, // Default gate number
                'rfid',
                $notes,
                $entry_status
            ]);
        }
        
        // Log success
        file_put_contents(__DIR__ . '/rfid_success.log', date('Y-m-d H:i:s') . " - RFID Log: $student_name ($registration_number) - $status\n", FILE_APPEND);
        
        // Return JSON response for hardware
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'RFID scan logged successfully',
            'data' => [
                'student_name' => $student_name,
                'registration_number' => $registration_number,
                'status' => $status,
                'timestamp' => $timestamp
            ]
        ]);
        exit;
        
    } catch (PDOException $e) {
        // Log error
        file_put_contents(__DIR__ . '/rfid_error.log', date('Y-m-d H:i:s') . " - RFID Log Error: " . $e->getMessage() . "\n", FILE_APPEND);
        
        // Return error response for hardware
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Scan Logs - Security Dashboard</title>
    <link rel="stylesheet" href="/Capstone_project/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>

        /* Keep all your CSS here, or move relevant parts to style.css */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }

        .sidebar { position: fixed; left: 0; top: 70px; bottom: 0; width: 250px; background: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.07); z-index: 999; transition: transform 0.3s; }
        .sidebar.closed { transform: translateX(-100%); }
        .sidebar-menu { padding: 2rem 0; }
        .menu-section { margin-bottom: 2rem; }
        .menu-section h3 { color: #7f8c8d; font-size: 0.8rem; text-transform: uppercase; padding: 0 2rem 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid #eee; }
        .menu-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; color: #2c3e50; text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .menu-item:hover, .menu-item.active { background: #f4f6fb; border-left-color: #3498db; color: #3498db; }
        .menu-item i { font-size: 1.2rem; width: 20px; text-align: center; }

        .header { position: fixed; top: 0; left: 0; right: 0; background: #667eea; color: #fff; z-index: 1000; display: flex; align-items: center; justify-content: space-between; padding: 1rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
        .header .sidebar-toggle { background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; margin-right: 1rem; }
        .header .user-info { display: flex; align-items: center; gap: 1rem; }
        .header .user-avatar { width: 35px; height: 35px; border-radius: 50%; background: #764ba2; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; }

        .main-content { margin-left: 250px; margin-top: 70px; padding: 2rem 1rem 4rem 1rem; min-height: calc(100vh - 70px - 80px); transition: margin-left 0.3s; background: rgb(8, 78, 147); }
        .sidebar.closed ~ .main-content { margin-left: 0; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 1rem 2rem; text-align: center; color: #7f8c8d; border-top: 1px solid rgba(0, 0, 0, 0.1); z-index: 1000; font-size: 0.9rem; font-weight: 500; }

        .page-header { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px; margin-bottom: 30px; }
        .page-title { font-size: 2.2rem; font-weight: 700; color: #2c3e50; margin: 0; }
        .page-subtitle { color: #7f8c8d; font-size: 1.1rem; margin: 5px 0 0 0; }

        .tabs { display: flex; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); margin-bottom: 30px; overflow: hidden; }
        .tab { flex: 1; padding: 20px; background: #f8f9fa; border: none; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; color: #6c757d; }
        .tab.active { background: #28a745; color: #fff; }
        .tab:last-child.active { background: #dc3545; }
        .tab i { margin-right: 8px; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .form-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px; margin-bottom: 30px; }
        .form-section { margin-bottom: 30px; }
        .form-section h3 { color: #2c3e5015; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; color: #2c3e50; margin-bottom: 8px; }
        .form-control { border: 2px solid #e9ecef; border-radius: 8px; padding: 12px; font-size: 1rem; transition: all 0.2s; }
        .form-control:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }

        .visitor-search { margin-bottom: 20px; }
        .search-input { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1rem; }
        .search-results { max-height: 200px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 8px; margin-top: 10px; background: #fff; }
        .search-result-item { padding: 12px; border-bottom: 1px solid #e9ecef; cursor: pointer; transition: background 0.2s; }
        .search-result-item:hover { background: #f8f9fa; }
        .search-result-item:last-child { border-bottom: none; }

        .equipment-section { margin-bottom: 20px; }
        .equipment-item { display: flex; align-items: center; gap: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; }
        .equipment-item input[type="text"] { flex: 1; }
        .equipment-item button { background: #dc3545; color: #fff; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; }

        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 1rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-primary:hover { background: #0056b3; transform: translateY(-1px); }
        .btn-success { background: #28a745; color: #fff; }
        .btn-success:hover { background: #1e7e34; transform: translateY(-1px); }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #c82333; transform: translateY(-1px); }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-secondary:hover { background: #545b62; transform: translateY(-1px); }

        .table-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px; margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
        .table th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        .table tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; }
            .sidebar { transform: translateX(-100%); }
            .tabs { flex-direction: column; }
            .tab { text-align: center; }
        }

        .log-table {
            border-collapse: collapse;
            border: 2px solid #2c3e50;
            width: 100%;
            table-layout: fixed;
        }
        .log-table th, .log-table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-right: 2px solid #2c3e50;
            border-bottom: 1px solid #e9ecef;
            background: #fff;
            padding: 8px;
        }
        .log-table th:last-child, .log-table td:last-child {
            border-right: none;
        }
        .log-table th {
            background: #f8f9fa;
        }
        .log-table th:nth-child(1), .log-table td:nth-child(1) { width: 90px; }
        .log-table th:nth-child(2), .log-table td:nth-child(2) { width: 120px; }
        .log-table th:nth-child(3), .log-table td:nth-child(3) { width: 80px; }
        .log-table th:nth-child(4), .log-table td:nth-child(4) { width: 60px; }
        .log-table th:nth-child(5), .log-table td:nth-child(5) { width: 100px; }
        .log-table th:nth-child(6), .log-table td:nth-child(6) { width: 150px; }
        .log-table th:nth-child(7), .log-table td:nth-child(7) { width: 150px; }

        .log-status-entry {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }

        .log-status-exit {
            background-color: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }

        .log-status-unauthorized {
            background-color: #dc3545;
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.5; }
        }

        /* Student Cards Styles */
        .student-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .student-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }

        .student-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #007bff;
        }

        .student-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .student-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .student-status .badge {
            font-size: 0.75rem;
        }

        .student-card .card-body {
            padding: 1.5rem;
        }

        .student-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .student-reg-number {
            color: #007bff;
            font-size: 1rem;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 6px;
            text-align: center;
        }

        .student-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .info-item i {
            width: 16px;
            color: #007bff;
        }

        .student-card .card-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }

        .account-status {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .account-status .badge {
            font-size: 0.7rem;
        }

        .search-filters {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -0.5rem;
        }

        .col-md-2, .col-md-3 {
            padding: 0 0.5rem;
            margin-bottom: 1rem;
        }

        .col-md-2 { flex: 0 0 16.666667%; }
        .col-md-3 { flex: 0 0 25%; }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: block;
        }

        .d-flex {
            display: flex;
        }

        .align-items-end {
            align-items: flex-end;
        }

        .me-2 {
            margin-right: 0.5rem;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        .modal.show {
            display: block;
        }

        .modal-dialog {
            max-width: 800px;
            margin: 2rem auto;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .modal-header {
            background: #343a40;
            color: white;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h5 {
            margin: 0;
            font-size: 1.1rem;
        }

        .btn-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.8;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .student-details-modal {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .detail-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
        }

        .detail-section h6 {
            color: #495057;
            margin-bottom: 1rem;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
        }

        .detail-item {
            display: flex;
            margin-bottom: 0.75rem;
            align-items: center;
        }

        .detail-item label {
            font-weight: 600;
            min-width: 150px;
            color: #495057;
        }

        .detail-item .value {
            flex: 1;
            color: #212529;
        }

        .text-center {
            text-align: center;
        }

        .spinner-border {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }

        @keyframes spinner-border {
            to { transform: rotate(360deg); }
        }

        .text-primary {
            color: #007bff !important;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .bg-primary {
            background-color: #007bff !important;
            color: white;
        }

        .bg-success {
            background-color: #28a745 !important;
            color: white;
        }

        .bg-danger {
            background-color: #dc3545 !important;
            color: white;
        }

        .bg-info {
            background-color: #17a2b8 !important;
            color: white;
        }

        .bg-secondary {
            background-color: #6c757d !important;
            color: white;
        }

        .bg-warning {
            background-color: #ffc107 !important;
            color: #212529;
        }
    </style>

</head>
<body>
    <header class="header">
        <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <div style="font-size: 1.2rem; font-weight: bold;">Gate Management System - Security</div>
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <span>Security Officer</span>
            <a href="/Capstone_project/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <div class="menu-section">
                <h3>Security</h3>
                <a href="/Capstone_project/pages/dashboard_security.php" class="menu-item"><i class="fas fa-shield-alt"></i> Dashboard</a>
                <a href="/Capstone_project/pages/visitor_form.php" class="menu-item"><i class="fas fa-user-plus"></i> Register Visitor</a>
                <a href="/Capstone_project/pages/visitors.php" class="menu-item"><i class="fas fa-users"></i> Manage Visitors</a>
                <a href="/Capstone_project/pages/logs.php" class="menu-item active"><i class="fas fa-clipboard-list"></i> RFID Scan Logs</a>
                <a href="/Capstone_project/pages/visitor_logs.php" class="menu-item"><i class="fas fa-address-book"></i> Visitor Entry/Exit Logs</a>
            </div>
            <div class="menu-section">
                <h3>Account</h3>
                <a href="/Capstone_project/change_password.php" class="menu-item"><i class="fas fa-key"></i> Change Password</a>
                <a href="/Capstone_project/logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">Entry/Exit Logs</div>
            <div class="page-subtitle">Monitor student entry/exit and view student information</div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('logs')">
                <i class="fas fa-history"></i> RFID Scan Logs
            </button>
            <button class="tab" onclick="showTab('students')">
                <i class="fas fa-id-card"></i> Student Cards
            </button>
        </div>

        <!-- RFID Logs Tab -->
        <div id="logs" class="tab-content active">
            <div class="form-container">
                <div class="form-header">
                    <h1 class="form-title"><i class="fas fa-id-card"></i> RFID Card Scan Log</h1>
                    <p class="form-subtitle">Displaying raw RFID scan data</p>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3><i class="fas fa-history"></i> Recent RFID Scan Logs</h3>
                        <div>
                            <a href="/Capstone_project/pages/logs.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-list"></i> Refresh Logs
                            </a>
                        </div>
                    </div>

                    <!-- RFID Logs Display -->
                    <div class="table-container">
                        <table class="log-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>Time</th>
                                    <th class="student-col">Student</th>
                                    <th class="serial-col">Serial Number</th>
                                    <th class="regno-col">Reg. No</th>
                                    <th>Department</th>
                                    <th class="phone-col">Phone</th>
                                    <th class="year-col">Year</th>
                                    <th class="status-col">Status</th>
                                    <th>Card UID</th>
                                    <th class="method-col">Method</th>
                                </tr>
                            </thead>
                            <tbody id="studentLogsTable">
                                <?php
                                try {
                                    // Fetch recent RFID logs with student information (including unauthorized)
                                    $stmt = $pdo->prepare("
                                        SELECT 
                                            eel.created_at,
                                            eel.status,
                                            eel.entry_method,
                                            eel.notes,
                                            COALESCE(rc.card_number, SUBSTRING_INDEX(eel.notes, 'Card UID: ', -1)) as uid,
                                            s.registration_number,
                                            s.first_name,
                                            s.last_name,
                                            s.department,
                                            s.program,
                                            s.year_of_study,
                                            s.phone,
                                            s.serial_number
                                        FROM entry_exit_logs eel
                                        LEFT JOIN rfid_cards rc ON eel.rfid_card_id = rc.id
                                        LEFT JOIN students s ON rc.student_id = s.id
                                        ORDER BY eel.created_at DESC
                                        LIMIT 20
                                    ");
                                    $stmt->execute();
                                    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (empty($logs)) {
                                        echo "<tr><td colspan='7' style='text-align: center; color: #666;'>No RFID logs found</td></tr>";
                                    } else {
                                        $sn = 1;
                                        foreach ($logs as $log) {
                                            $is_unauthorized = $log['status'] === 'unauthorized';
                                            
                                            if ($is_unauthorized) {
                                                // Handle unauthorized cards
                                                $student_name = 'UNAUTHORIZED CARD';
                                                $status_class = 'log-status-unauthorized';
                                                $status_text = "<span style='color:blue;font-weight:bold;'>Not Yet</span>";
                                                $registration_number = 'N/A';
                                                $department = 'N/A';
                                                $serial_number = 'N/A';
                                                $phone = 'N/A';
                                                $year = 'N/A';
                                            } else {
                                                // Handle authorized cards
                                                $student_name = $log['first_name'] && $log['last_name'] 
                                                    ? $log['first_name'] . ' ' . $log['last_name'] 
                                                    : 'Unknown Student';
                                                if ($log['status'] === 'entered') {
                                                    $status_class = 'log-status-entry';
                                                    $status_text = "<span style='color:green;font-weight:bold;'>IN</span>";
                                                } elseif ($log['status'] === 'exited') {
                                                    $status_class = 'log-status-exit';
                                                    $status_text = "<span style='color:red;font-weight:bold;'>OUT</span>";
                                                } else {
                                                    $status_class = '';
                                                    $status_text = htmlspecialchars($log['status'] ?? '');
                                                }
                                                $registration_number = htmlspecialchars($log['registration_number'] ?? 'N/A');
                                                $department = htmlspecialchars($log['department'] ?? 'N/A');
                                                $serial_number = htmlspecialchars($log['serial_number'] ?? 'N/A');
                                                $phone = htmlspecialchars($log['phone'] ?? 'N/A');
                                                $year = htmlspecialchars($log['year_of_study'] ?? 'N/A');
                                            }
                                            
                                            echo "<tr>";
                                            echo "<td>" . $sn++ . "</td>";
                                            echo "<td>" . date('H:i:s', strtotime($log['created_at'])) . "</td>";
                                            echo "<td class='student-col'>" . htmlspecialchars($student_name) . "</td>";
                                            echo "<td class='serial-col'>" . $serial_number . "</td>";
                                            echo "<td class='regno-col'>" . $registration_number . "</td>";
                                            echo "<td>" . $department . "</td>";
                                            echo "<td class='phone-col'>" . $phone . "</td>";
                                            echo "<td class='year-col'>" . $year . "</td>";
                                            echo "<td class='status-col'><span class='$status_class'>$status_text</span></td>";
                                            echo "<td><code>" . htmlspecialchars($log['uid'] ?? 'N/A') . "</code></td>";
                                            echo "<td class='method-col'>" . strtoupper(htmlspecialchars($log['entry_method'])) . "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='7' style='text-align: center; color: red;'>Error loading logs: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Cards Tab -->
        <div id="students" class="tab-content">
            <div class="form-container">
                <div class="form-header">
                    <h1 class="form-title"><i class="fas fa-users"></i> Student Cards</h1>
                    <p class="form-subtitle">Click any student card to view details</p>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters mb-4">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                   placeholder="Name, Reg No, Email...">
                        </div>
                        <div class="col-md-2">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-control" id="department" name="department">
                                <option value="">All Departments</option>
                                <?php 
                                try {
                                    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
                                    $stmt = $pdo->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL ORDER BY department");
                                    while ($row = $stmt->fetch()) {
                                        $selected = ($_GET['department'] ?? '') === $row['department'] ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['department']) . "' $selected>" . htmlspecialchars($row['department']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    // Handle error silently
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-control" id="year" name="year">
                                <option value="">All Years</option>
                                <?php 
                                try {
                                    $stmt = $pdo->query("SELECT DISTINCT year_of_study FROM students WHERE year_of_study IS NOT NULL ORDER BY year_of_study");
                                    while ($row = $stmt->fetch()) {
                                        $selected = ($_GET['year'] ?? '') === $row['year_of_study'] ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['year_of_study']) . "' $selected>Year " . htmlspecialchars($row['year_of_study']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    // Handle error silently
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="1" <?php echo ($_GET['status'] ?? '') === '1' ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo ($_GET['status'] ?? '') === '0' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="/Capstone_project/pages/logs.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Student Cards Grid -->
                <div id="studentCardsContainer">
                    <!-- Student cards will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalLabel">Student Details</h5>
                    <button type="button" class="btn-close" onclick="closeModal()" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body" id="studentModalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                    <button type="button" class="btn btn-primary" id="viewFullProfileBtn">View Full Profile</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
    </footer>

    <script>
        // Toggle sidebar function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('closed');

            // For mobile view
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            }
        }

        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Load student cards if students tab is selected
            if (tabName === 'students') {
                loadStudentCards();
            }
        }

        // Load student cards
        function loadStudentCards() {
            const container = document.getElementById('studentCardsContainer');
            const searchParams = new URLSearchParams(window.location.search);
            
            // Show loading state
            container.innerHTML = `
                <div class="text-center" style="padding: 2rem;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading student cards...</p>
                </div>
            `;
            
            // Build query string
            const queryString = searchParams.toString();
            const url = `/Capstone_project/api/get_students_cards.php${queryString ? '?' + queryString : ''}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        container.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.error}
                            </div>
                        `;
                    } else {
                        displayStudentCards(data.students, container);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Error loading student cards. Please try again.
                        </div>
                    `;
                });
        }

        // Display student cards
        function displayStudentCards(students, container) {
            if (!students || students.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4>No students found</h4>
                        <p class="text-muted">No students match your search criteria.</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="student-cards-grid">';
            students.forEach(student => {
                html += `
                    <div class="student-card" onclick="viewStudentDetails('${student.registration_number}')">
                        <div class="card-header">
                            <div class="student-avatar">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="student-status">
                                ${student.is_active ? 
                                    '<span class="badge bg-success">Active</span>' : 
                                    '<span class="badge bg-danger">Inactive</span>'
                                }
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="student-name">
                                ${student.first_name} ${student.last_name}
                            </h5>
                            <div class="student-reg-number">
                                <strong>${student.registration_number}</strong>
                            </div>
                            <div class="student-info">
                                <div class="info-item">
                                    <i class="fas fa-envelope"></i>
                                    <span>${student.email}</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>${student.department}</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Year ${student.year_of_study}</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-laptop"></i>
                                    <span>${student.device_count || 0} device${(student.device_count || 0) != 1 ? 's' : ''}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="account-status">
                                ${student.username ? 
                                    '<span class="badge bg-info"><i class="fas fa-check"></i> Account</span>' : 
                                    '<span class="badge bg-secondary"><i class="fas fa-times"></i> No Account</span>'
                                }
                                ${student.is_first_login ? 
                                    '<span class="badge bg-warning">First Login</span>' : ''
                                }
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        // View student details
        function viewStudentDetails(registrationNumber) {
            const modal = document.getElementById('studentModal');
            const modalBody = document.getElementById('studentModalBody');
            const viewFullProfileBtn = document.getElementById('viewFullProfileBtn');
            
            // Show loading state
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading student details...</p>
                </div>
            `;
            
            // Show modal
            modal.classList.add('show');
            
            // Fetch student details
            fetch(`/Capstone_project/pages/view_student.php?ajax=true&registration_number=${encodeURIComponent(registrationNumber)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.error}
                            </div>
                        `;
                        viewFullProfileBtn.style.display = 'none';
                    } else {
                        displayStudentDetails(data.student, modalBody);
                        viewFullProfileBtn.style.display = 'inline-block';
                        viewFullProfileBtn.onclick = () => {
                            window.open(`/Capstone_project/pages/view_student.php?registration_number=${encodeURIComponent(registrationNumber)}`, '_blank');
                        };
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Error loading student details. Please try again.
                        </div>
                    `;
                    viewFullProfileBtn.style.display = 'none';
                });
        }

        // Display student details in modal
        function displayStudentDetails(student, container) {
            container.innerHTML = `
                <div class="student-details-modal">
                    <!-- Personal Information -->
                    <div class="detail-section">
                        <h6><i class="fas fa-user"></i> Personal Information</h6>
                        <div class="detail-item">
                            <label>Registration Number:</label>
                            <div class="value">${student.registration_number}</div>
                        </div>
                        <div class="detail-item">
                            <label>Full Name:</label>
                            <div class="value">${student.first_name} ${student.last_name}</div>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <div class="value">
                                <a href="mailto:${student.email}">${student.email}</a>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <div class="value">
                                ${student.phone ? `<a href="tel:${student.phone}">${student.phone}</a>` : '<span class="text-muted">Not provided</span>'}
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Gender:</label>
                            <div class="value">
                                ${student.gender ? student.gender.charAt(0).toUpperCase() + student.gender.slice(1) : '<span class="text-muted">Not specified</span>'}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academic Information -->
                    <div class="detail-section">
                        <h6><i class="fas fa-graduation-cap"></i> Academic Information</h6>
                        <div class="detail-item">
                            <label>Department:</label>
                            <div class="value">${student.department}</div>
                        </div>
                        <div class="detail-item">
                            <label>Program:</label>
                            <div class="value">${student.program}</div>
                        </div>
                        <div class="detail-item">
                            <label>Year of Study:</label>
                            <div class="value">
                                <span class="badge bg-primary">Year ${student.year_of_study}</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <div class="value">
                                <span class="badge ${student.is_active ? 'bg-success' : 'bg-danger'}">
                                    ${student.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Information -->
                    <div class="detail-section">
                        <h6><i class="fas fa-user-circle"></i> Account Information</h6>
                        <div class="detail-item">
                            <label>Account Status:</label>
                            <div class="value">
                                ${student.username ? 
                                    '<span class="badge bg-info"><i class="fas fa-check"></i> Account</span>' : 
                                    '<span class="badge bg-secondary"><i class="fas fa-times"></i> No Account</span>'
                                }
                            </div>
                        </div>
                        ${student.username ? `
                            <div class="detail-item">
                                <label>Username:</label>
                                <div class="value">${student.username}</div>
                            </div>
                            ${student.is_first_login ? `
                                <div class="detail-item">
                                    <label>Login Status:</label>
                                    <div class="value">
                                        <span class="badge bg-warning">First Login Required</span>
                                    </div>
                                </div>
                            ` : ''}
                        ` : ''}
                        <div class="detail-item">
                            <label>Registered Devices:</label>
                            <div class="value">
                                <span class="badge bg-secondary">${student.device_count || 0} device${(student.device_count || 0) != 1 ? 's' : ''}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('studentModal');
            modal.classList.remove('show');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('studentModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Auto-update logs table every 5 seconds without full page refresh
        function fetchStudentLogs() {
            fetch('/Capstone_project/pages/logs.php?ajax=1')
                .then(response => response.text())
                .then(data => {
                    const currentTableBody = document.getElementById('studentLogsTable');
                    if (currentTableBody) {
                        // Parse the response and update only the table body
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(data, 'text/html');
                        const newTableBody = doc.querySelector('#studentLogsTable');
                        if (newTableBody) {
                            currentTableBody.innerHTML = newTableBody.innerHTML;
                        }
                    }
                })
                .catch(error => console.error('Error fetching RFID logs:', error));
        }

        // Initialize when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Set interval for auto-updating logs
            setInterval(fetchStudentLogs, 5000); // Fetch and update every 5 seconds
            
            // Add immediate refresh when unauthorized cards are detected
            // Check for unauthorized cards every 2 seconds
            setInterval(function() {
                fetch('/Capstone_project/pages/logs.php?check_unauthorized=1')
                    .then(response => response.json())
                    .then(data => {
                        if (data.has_unauthorized) {
                            // Immediately refresh the logs table if unauthorized card detected
                            fetchStudentLogs();
                            // Show alert
                            showUnauthorizedAlert();
                        }
                    })
                    .catch(error => console.error('Error checking unauthorized cards:', error));
            }, 2000);
        });

        // Show unauthorized alert
        function showUnauthorizedAlert() {
            // Create alert element
            const alert = document.createElement('div');
            alert.className = 'unauthorized-alert';
            alert.innerHTML = `
                <div style="position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 15px; border-radius: 8px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                    <strong>⚠️ UNAUTHORIZED CARD DETECTED!</strong><br>
                    An unauthorized RFID card was scanned.
                </div>
            `;
            document.body.appendChild(alert);
            
            // Remove alert after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 5000);
        }
    </script>
</body>
</html>