<?php
require_once '../config/config.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">RFID Card Scan Log Report</div>
            <div class="page-subtitle">Displaying raw RFID scan data</div>
            <a href="reports.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Reports</a>
        </div>
        <?php if ($start_date && $end_date): ?>
            <div class="card">
                <div class="card-header">
                    <strong>Recent RFID Scan Logs</strong>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $db = getDB()->getConnection();
                        // Main query
                        $sql = "SELECT eel.*, 
                                rc.card_number AS card_uid, 
                                s.first_name, s.last_name, s.registration_number, s.department, s.phone, s.year_of_study,
                                d.serial_number
                            FROM entry_exit_logs eel
                            LEFT JOIN rfid_cards rc ON eel.rfid_card_id = rc.id
                            LEFT JOIN students s ON rc.student_id = s.id
                            LEFT JOIN devices d ON eel.device_id = d.id
                            WHERE DATE(eel.entry_time) BETWEEN ? AND ?
                              AND (LOWER(eel.status) = 'entered' OR LOWER(eel.status) = 'exited')
                              AND (eel.entry_method = 'rfid' OR eel.entry_method = 'both')
                            ORDER BY eel.entry_time ASC";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$start_date, $end_date]);
                        $logs = $stmt->fetchAll();
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        $logs = [];
                    }

                    if (!empty($logs)): ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>Time</th>
                                    <th>Student</th>
                                    <th>Serial</th>
                                    <th>Reg. No</th>
                                    <th>Department</th>
                                    <th>Phone</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Card UID</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $sn = 1; foreach ($logs as $row): ?>
                                    <tr>
                                        <td><?php echo $sn++; ?></td>
                                        <td><?php echo htmlspecialchars(isset($row['entry_time']) ? date('H:i:s', strtotime($row['entry_time'])) : ''); ?></td>
                                        <td><?php echo htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($row['serial_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['registration_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['department'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['year_of_study'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            $status = strtolower($row['status'] ?? '');
                                            if ($status === 'entered') {
                                                echo '<span style="color:green;font-weight:bold;">IN</span>';
                                            } elseif ($status === 'exited') {
                                                echo '<span style="color:green;font-weight:bold;">OUT</span>';
                                            } elseif ($status === 'unauthorized') {
                                                echo '<span style="color:orange;font-weight:bold;">Not Yet</span>';
                                            } else {
                                                echo htmlspecialchars($row['status'] ?? '');
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['card_uid'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(strtoupper($row['entry_method'] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-warning">No RFID scan logs found for the selected date range.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Invalid date range. Please go back and select dates.</div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script>
// Auto-refresh the page every 5 seconds
setInterval(function() {
    location.reload();
}, 5000);
</script>