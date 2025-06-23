<?php
// Simple script to create test data for logs
header('Content-Type: text/html; charset=utf-8');

try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDB();
    
    echo "<h2>Creating Test Data for Entry/Exit Logs</h2>";
    
    // Check if we have students
    $students = $db->fetchAll("SELECT id, first_name, last_name, registration_number FROM students LIMIT 5");
    if (empty($students)) {
        echo "<p style='color: red;'>No students found. Please add some students first.</p>";
        exit;
    }
    
    // Check if we have users
    $users = $db->fetchAll("SELECT id FROM users WHERE role_id IN (SELECT id FROM roles WHERE role_name = 'student') LIMIT 5");
    if (empty($users)) {
        echo "<p style='color: red;'>No student users found. Please add some users first.</p>";
        exit;
    }
    
    // Check if we have RFID cards
    $cards = $db->fetchAll("SELECT id FROM rfid_cards WHERE is_active = 1 LIMIT 5");
    if (empty($cards)) {
        echo "<p style='color: red;'>No active RFID cards found. Please add some cards first.</p>";
        exit;
    }
    
    echo "<p>Found " . count($students) . " students, " . count($users) . " users, and " . count($cards) . " RFID cards.</p>";
    
    // Clear existing test data
    $db->query("DELETE FROM entry_exit_logs WHERE notes LIKE '%TEST DATA%'");
    echo "<p>✓ Cleared existing test data</p>";
    
    // Create test logs
    $test_logs = [];
    
    // Generate logs for the last 7 days
    for ($day = 6; $day >= 0; $day--) {
        $date = date('Y-m-d', strtotime("-$day days"));
        
        // Generate 2-5 logs per day
        $logs_per_day = rand(2, 5);
        
        for ($i = 0; $i < $logs_per_day; $i++) {
            $student_index = array_rand($students);
            $user_index = array_rand($users);
            $card_index = array_rand($cards);
            
            $student = $students[$student_index];
            $user = $users[$user_index];
            $card = $cards[$card_index];
            
            // Random time during the day
            $hour = rand(7, 18);
            $minute = rand(0, 59);
            $entry_time = "$date $hour:" . str_pad($minute, 2, '0', STR_PAD_LEFT) . ":00";
            
            // Exit time (1-6 hours later)
            $exit_hour = min(22, $hour + rand(1, 6));
            $exit_minute = rand(0, 59);
            $exit_time = "$date $exit_hour:" . str_pad($exit_minute, 2, '0', STR_PAD_LEFT) . ":00";
            
            $gate_number = rand(1, 3);
            $entry_method = rand(0, 1) ? 'rfid' : 'manual';
            
            $test_logs[] = [
                'user_id' => $user['id'],
                'rfid_card_id' => $card['id'],
                'entry_time' => $entry_time,
                'exit_time' => $exit_time,
                'gate_number' => $gate_number,
                'entry_method' => $entry_method,
                'status' => 'exited',
                'notes' => 'TEST DATA - Generated for testing purposes',
                'created_at' => $entry_time
            ];
            
            // Also create some "currently inside" entries (no exit time)
            if (rand(0, 2) === 0) { // 33% chance
                $current_hour = rand(8, 16);
                $current_minute = rand(0, 59);
                $current_time = date('Y-m-d') . " $current_hour:" . str_pad($current_minute, 2, '0', STR_PAD_LEFT) . ":00";
                
                $test_logs[] = [
                    'user_id' => $user['id'],
                    'rfid_card_id' => $card['id'],
                    'entry_time' => $current_time,
                    'exit_time' => null,
                    'gate_number' => rand(1, 3),
                    'entry_method' => 'rfid',
                    'status' => 'entered',
                    'notes' => 'TEST DATA - Currently inside campus',
                    'created_at' => $current_time
                ];
            }
        }
    }
    
    // Insert test data
    $inserted = 0;
    foreach ($test_logs as $log) {
        try {
            $sql = "INSERT INTO entry_exit_logs (
                user_id, rfid_card_id, entry_time, exit_time, gate_number, 
                entry_method, status, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $log['user_id'],
                $log['rfid_card_id'],
                $log['entry_time'],
                $log['exit_time'],
                $log['gate_number'],
                $log['entry_method'],
                $log['status'],
                $log['notes'],
                $log['created_at']
            ];
            
            $db->query($sql, $params);
            $inserted++;
        } catch (Exception $e) {
            echo "<p style='color: orange;'>Warning: Could not insert log: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p style='color: green;'>✓ Successfully created $inserted test entry/exit logs</p>";
    
    // Show summary
    $total_logs = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs");
    $inside_logs = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs WHERE status = 'entered' AND exit_time IS NULL");
    $today_logs = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs WHERE DATE(created_at) = CURDATE()");
    
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li>Total logs in database: " . $total_logs['count'] . "</li>";
    echo "<li>Students currently inside: " . $inside_logs['count'] . "</li>";
    echo "<li>Logs from today: " . $today_logs['count'] . "</li>";
    echo "</ul>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<p><a href='logs.php' class='btn btn-primary'>View Entry/Exit Logs</a></p>";
    echo "<p><a href='../../index.php' class='btn btn-secondary'>Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 