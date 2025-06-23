<?php
require_once '../config.php';

// Validate API key
validate_api_key();

$method = get_request_method();

if ($method === 'GET') {
    $student_id = $_GET['student_id'] ?? null;
    $gate_number = $_GET['gate_number'] ?? null;
    
    try {
        $db = getDB();
        
        if ($student_id) {
            // Get specific student status
            $status = check_student_status($student_id);
            
            if ($status['status'] === 'inside') {
                // Get student details
                $student = $db->fetch("
                    SELECT 
                        s.id as student_id,
                        s.registration_number,
                        s.first_name,
                        s.last_name,
                        s.email,
                        s.department,
                        s.program,
                        s.year_of_study
                    FROM students s
                    WHERE s.id = ?
                ", [$student_id]);
                
                if ($student) {
                    api_success([
                        'student' => $student,
                        'status' => 'inside',
                        'entry_time' => $status['entry_time'],
                        'gate_number' => $status['gate_number'],
                        'duration' => time() - strtotime($status['entry_time'])
                    ], 'Student is currently inside');
                } else {
                    api_error('Student not found', 404);
                }
            } else {
                api_success([
                    'status' => 'outside',
                    'message' => 'Student is not currently inside the campus'
                ], 'Student is outside');
            }
            
        } else {
            // Get overall campus status
            $where_conditions = [];
            $params = [];
            
            if ($gate_number) {
                $where_conditions[] = "eel.gate_number = ?";
                $params[] = $gate_number;
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            // Get currently inside students
            $inside_sql = "SELECT 
                            s.id as student_id,
                            s.registration_number,
                            s.first_name,
                            s.last_name,
                            s.department,
                            s.program,
                            s.year_of_study,
                            eel.entry_time,
                            eel.gate_number
                          FROM entry_exit_logs eel 
                          JOIN users u ON eel.user_id = u.id 
                          JOIN students s ON u.student_id = s.id 
                          $where_clause
                          AND eel.status = 'entered' 
                          AND eel.exit_time IS NULL
                          ORDER BY eel.entry_time DESC";
            
            $inside_students = $db->fetchAll($inside_sql, $params);
            
            // Get gate-wise statistics
            $gate_stats_sql = "SELECT 
                                gate_number,
                                COUNT(*) as inside_count
                              FROM entry_exit_logs 
                              WHERE status = 'entered' 
                              AND exit_time IS NULL
                              GROUP BY gate_number";
            
            $gate_stats = $db->fetchAll($gate_stats_sql);
            
            // Get department-wise statistics
            $dept_stats_sql = "SELECT 
                                s.department,
                                COUNT(*) as inside_count
                              FROM entry_exit_logs eel 
                              JOIN users u ON eel.user_id = u.id 
                              JOIN students s ON u.student_id = s.id 
                              WHERE eel.status = 'entered' 
                              AND eel.exit_time IS NULL
                              GROUP BY s.department
                              ORDER BY inside_count DESC";
            
            $dept_stats = $db->fetchAll($dept_stats_sql);
            
            // Get recent entries (last 10 minutes)
            $recent_entries_sql = "SELECT 
                                    s.first_name,
                                    s.last_name,
                                    s.registration_number,
                                    eel.gate_number,
                                    eel.entry_time,
                                    eel.entry_method
                                  FROM entry_exit_logs eel 
                                  JOIN users u ON eel.user_id = u.id 
                                  JOIN students s ON u.student_id = s.id 
                                  WHERE eel.created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                                  ORDER BY eel.created_at DESC
                                  LIMIT 10";
            
            $recent_entries = $db->fetchAll($recent_entries_sql);
            
            api_success([
                'total_inside' => count($inside_students),
                'inside_students' => $inside_students,
                'gate_statistics' => $gate_stats,
                'department_statistics' => $dept_stats,
                'recent_entries' => $recent_entries,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'Campus status retrieved successfully');
        }
        
    } catch (Exception $e) {
        api_error('Database error: ' . $e->getMessage(), 500);
    }
    
} else {
    api_error('Method not allowed', 405);
}
?> 