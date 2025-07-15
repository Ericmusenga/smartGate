<?php
require_once '../config/database.php';

function fetch_logs($type, $action, $start_date, $end_date) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Database connection failed');
    }
    $logs = [];
    $date_filter = '';
    $params = [];
    if ($start_date && $end_date) {
        $date_filter = "WHERE created_at BETWEEN ? AND ?";
        $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
    }
    if ($type === 'student' && $action === 'entry') {
        $sql = "SELECT e.entry_time AS event_time, s.first_name, s.last_name, s.department, e.created_at FROM entry_student e LEFT JOIN students s ON e.student_id = s.id ".($date_filter ? $date_filter : '');
    } elseif ($type === 'student' && $action === 'exit') {
        $sql = "SELECT x.exit_time AS event_time, s.first_name, s.last_name, s.department, x.created_at FROM exit_student x LEFT JOIN students s ON x.student_id = s.id ".($date_filter ? $date_filter : '');
    } elseif ($type === 'visitor' && $action === 'entry') {
        if ($conn->query("SHOW TABLES LIKE 'entry_visitor'")->num_rows) {
            $sql = "SELECT v.entry_time AS event_time, v.visitor_name AS first_name, '' AS last_name, v.department, v.purpose, v.created_at FROM entry_visitor v ".($date_filter ? $date_filter : '');
        } else {
            $conn->close();
            return [];
        }
    } elseif ($type === 'visitor' && $action === 'exit') {
        if ($conn->query("SHOW TABLES LIKE 'exit_visitor'")->num_rows) {
            $sql = "SELECT v.exit_time AS event_time, v.visitor_name AS first_name, '' AS last_name, v.department, v.purpose, v.created_at FROM exit_visitor v ".($date_filter ? $date_filter : '');
        } else {
            $conn->close();
            return [];
        }
    } else {
        $conn->close();
        return [];
    }
    $stmt = $conn->prepare($sql);
    if ($date_filter) $stmt->bind_param('ss', ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $logs;
}

// Determine which section to show after submit
$section_to_show = $_POST['section_to_show'] ?? '';
$view = $_POST['view'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$logs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $view) {
    if ($view === 'entry_student') {
        $logs = fetch_logs('student', 'entry', $start_date, $end_date);
    } elseif ($view === 'exit_student') {
        $logs = fetch_logs('student', 'exit', $start_date, $end_date);
    } elseif ($view === 'entry_visitor') {
        $logs = fetch_logs('visitor', 'entry', $start_date, $end_date);
    } elseif ($view === 'exit_visitor') {
        $logs = fetch_logs('visitor', 'exit', $start_date, $end_date);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Entry/Exit Logs</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
    function showSection(sectionId) {
        document.querySelectorAll('.log-section').forEach(s => s.style.display = 'none');
        document.getElementById(sectionId).style.display = 'block';
        // Set hidden input in all forms
        document.querySelectorAll('input[name=section_to_show]').forEach(i => i.value = sectionId);
    }
    window.onload = function() {
        // Hide all sections by default
        document.querySelectorAll('.log-section').forEach(s => s.style.display = 'none');
        // Show the section if set by PHP after submit
        var sectionToShow = "<?= htmlspecialchars($section_to_show) ?>";
        if (sectionToShow && document.getElementById(sectionToShow)) {
            document.getElementById(sectionToShow).style.display = 'block';
        }
    };
    </script>
</head>
<body>
<div class="container mt-4">
    <h2>All Entry/Exit Logs</h2>
    <div class="mb-3">
        <button class="btn btn-outline-primary me-2" onclick="showSection('section_entry_student')">Entry Logs for Students</button>
        <button class="btn btn-outline-primary me-2" onclick="showSection('section_exit_student')">Exit Logs for Students</button>
        <button class="btn btn-outline-success me-2" onclick="showSection('section_entry_visitor')">Entry Logs for Visitors</button>
        <button class="btn btn-outline-success" onclick="showSection('section_exit_visitor')">Exit Logs for Visitors</button>
    </div>
    <!-- Entry Student Section -->
    <div id="section_entry_student" class="log-section" style="display:none;">
        <form method="post">
            <input type="hidden" name="view" value="entry_student">
            <input type="hidden" name="section_to_show" value="section_entry_student">
            <div class="row g-3 mb-3">
                <div class="col-auto">
                    <label for="start_date_entry_student" class="form-label">Start Date</label>
                    <input type="date" id="start_date_entry_student" name="start_date" class="form-control" value="<?= htmlspecialchars($view==='entry_student'?$start_date:'') ?>">
                </div>
                <div class="col-auto">
                    <label for="end_date_entry_student" class="form-label">End Date</label>
                    <input type="date" id="end_date_entry_student" name="end_date" class="form-control" value="<?= htmlspecialchars($view==='entry_student'?$end_date:'') ?>">
                </div>
                <div class="col-auto align-self-end">
                    <button type="submit" class="btn btn-primary">View Logs</button>
                </div>
            </div>
        </form>
        <?php if ($view==='entry_student' && $_SERVER['REQUEST_METHOD']==='POST'): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Entry Time</th>
                        <th>Name</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['event_time']) ?></td>
                        <td><?= htmlspecialchars(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars($log['department']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="3" class="text-center">No logs found for the selected date range.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <!-- Exit Student Section -->
    <div id="section_exit_student" class="log-section" style="display:none;">
        <form method="post">
            <input type="hidden" name="view" value="exit_student">
            <input type="hidden" name="section_to_show" value="section_exit_student">
            <div class="row g-3 mb-3">
                <div class="col-auto">
                    <label for="start_date_exit_student" class="form-label">Start Date</label>
                    <input type="date" id="start_date_exit_student" name="start_date" class="form-control" value="<?= htmlspecialchars($view==='exit_student'?$start_date:'') ?>">
                </div>
                <div class="col-auto">
                    <label for="end_date_exit_student" class="form-label">End Date</label>
                    <input type="date" id="end_date_exit_student" name="end_date" class="form-control" value="<?= htmlspecialchars($view==='exit_student'?$end_date:'') ?>">
                </div>
                <div class="col-auto align-self-end">
                    <button type="submit" class="btn btn-primary">View Logs</button>
                </div>
            </div>
        </form>
        <?php if ($view==='exit_student' && $_SERVER['REQUEST_METHOD']==='POST'): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Exit Time</th>
                        <th>Name</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['event_time']) ?></td>
                        <td><?= htmlspecialchars(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars($log['department']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="3" class="text-center">No logs found for the selected date range.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <!-- Entry Visitor Section -->
    <div id="section_entry_visitor" class="log-section" style="display:none;">
        <form method="post">
            <input type="hidden" name="view" value="entry_visitor">
            <input type="hidden" name="section_to_show" value="section_entry_visitor">
            <div class="row g-3 mb-3">
                <div class="col-auto">
                    <label for="start_date_entry_visitor" class="form-label">Start Date</label>
                    <input type="date" id="start_date_entry_visitor" name="start_date" class="form-control" value="<?= htmlspecialchars($view==='entry_visitor'?$start_date:'') ?>">
                </div>
                <div class="col-auto">
                    <label for="end_date_entry_visitor" class="form-label">End Date</label>
                    <input type="date" id="end_date_entry_visitor" name="end_date" class="form-control" value="<?= htmlspecialchars($view==='entry_visitor'?$end_date:'') ?>">
                </div>
                <div class="col-auto align-self-end">
                    <button type="submit" class="btn btn-primary">View Logs</button>
                </div>
            </div>
        </form>
        <?php if ($view==='entry_visitor' && $_SERVER['REQUEST_METHOD']==='POST'): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Entry Time</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['event_time']) ?></td>
                        <td><?= htmlspecialchars($log['first_name']) ?></td>
                        <td><?= htmlspecialchars($log['department']) ?></td>
                        <td><?= htmlspecialchars($log['purpose']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="4" class="text-center">No logs found for the selected date range.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <!-- Exit Visitor Section -->
    <div id="section_exit_visitor" class="log-section" style="display:none;">
        <form method="post">
            <input type="hidden" name="view" value="exit_visitor">
            <input type="hidden" name="section_to_show" value="section_exit_visitor">
            <div class="row g-3 mb-3">
                <div class="col-auto">
                    <label for="start_date_exit_visitor" class="form-label">Start Date</label>
                    <input type="date" id="start_date_exit_visitor" name="start_date" class="form-control" value="<?= htmlspecialchars($view==='exit_visitor'?$start_date:'') ?>">
                </div>
                <div class="col-auto">
                    <label for="end_date_exit_visitor" class="form-label">End Date</label>
                    <input type="date" id="end_date_exit_visitor" name="end_date" class="form-control" value="<?= htmlspecialchars($view==='exit_visitor'?$end_date:'') ?>">
                </div>
                <div class="col-auto align-self-end">
                    <button type="submit" class="btn btn-primary">View Logs</button>
                </div>
            </div>
        </form>
        <?php if ($view==='exit_visitor' && $_SERVER['REQUEST_METHOD']==='POST'): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Exit Time</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['event_time']) ?></td>
                        <td><?= htmlspecialchars($log['first_name']) ?></td>
                        <td><?= htmlspecialchars($log['department']) ?></td>
                        <td><?= htmlspecialchars($log['purpose']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="4" class="text-center">No logs found for the selected date range.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <div class="mt-3">
        <a href="reports.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Reports</a>
    </div>
</div>
</body>
</html> 