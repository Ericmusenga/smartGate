<?php
// Fresh, simple logs.php that will definitely work
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database
require_once __DIR__ . '/../../config/database.php';

// Get data
try {
    $db = getDB();
    $logs = $db->fetchAll("
        SELECT 
            eel.id,
            eel.entry_time,
            eel.exit_time,
            eel.gate_number,
            eel.entry_method,
            eel.status,
            eel.notes,
            eel.created_at,
            s.first_name,
            s.last_name,
            s.registration_number,
            s.department,
            s.program
        FROM entry_exit_logs eel 
        LEFT JOIN users u ON eel.user_id = u.id 
        LEFT JOIN students s ON u.student_id = s.id
        ORDER BY eel.created_at DESC 
        LIMIT 100
    ");
} catch (Exception $e) {
    $error = $e->getMessage();
    $logs = [];
}

// After fetching $logs
if (!empty($logs)) {
    $first_reg_no = $logs[0]['registration_number'];
    echo "<script>window.open('/Capstone_project/pages/student_info_card.php?reg_no=" . urlencode($first_reg_no) . "', '_blank');</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Entry/Exit Logs</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .error { background: #ffebee; color: #c62828; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .info { background: #e3f2fd; color: #1565c0; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #2196f3; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f5f5f5; }
        .status-entered { background: #c8e6c9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .status-exited { background: #fff3e0; color: #ef6c00; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .gate { background: #e0e0e0; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
        .actions { text-align: center; margin-top: 20px; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 4px; color: white; }
        .btn-primary { background: #2196f3; }
        .btn-success { background: #4caf50; }
        .btn-info { background: #00bcd4; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Entry/Exit Logs</h1>
        <p style="text-align: center; color: #666;">University of Rwanda - Rukara Campus</p>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <strong>Database Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (empty($logs)): ?>
            <div class="info">
                <strong>No logs found!</strong><br>
                There are no entry/exit logs in the database.<br><br>
                <a href="create_test_data.php" class="btn btn-success">Create Test Data</a>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 20px;">
                <strong>Total Records:</strong> <?= count($logs) ?> | 
                <strong>Last Updated:</strong> <?= date('M d, Y H:i:s') ?>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Registration #</th>
                        <th>Gate</th>
                        <th>Status</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th>Department</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $i => $log): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong>
                                    <?php 
                                    $name = '';
                                    if (!empty($log['first_name']) && !empty($log['last_name'])) {
                                        $name = $log['first_name'] . ' ' . $log['last_name'];
                                    } else {
                                        $name = 'Unknown Student';
                                    }
                                    echo htmlspecialchars($name);
                                    ?>
                                </strong>
                            </td>
                            <td><?= htmlspecialchars($log['registration_number'] ?? 'N/A') ?></td>
                            <td><span class="gate">Gate <?= htmlspecialchars($log['gate_number'] ?? 'N/A') ?></span></td>
                            <td>
                                <span class="status-<?= htmlspecialchars($log['status'] ?? 'unknown') ?>">
                                    <?= ucfirst(htmlspecialchars($log['status'] ?? 'unknown')) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['entry_time']): ?>
                                    <?= date('M d, H:i', strtotime($log['entry_time'])) ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['exit_time']): ?>
                                    <?= date('M d, H:i', strtotime($log['exit_time'])) ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($log['department'] ?? 'N/A') ?></td>
                            <td><?= strtoupper(htmlspecialchars($log['entry_method'] ?? 'unknown')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="actions">
            <a href="create_test_data.php" class="btn btn-success">➕ Add Test Data</a>
            <a href="debug_logs.php" class="btn btn-info">🔍 Debug</a>
            <a href="raw_logs.php" class="btn btn-primary">📋 Raw Data</a>
            <a href="../../index.php" class="btn btn-primary">🏠 Back to Dashboard</a>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 