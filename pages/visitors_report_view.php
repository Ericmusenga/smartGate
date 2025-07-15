<?php
require_once '../config/database.php';

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$visitors = [];
// Always fetch all visitors, regardless of date range
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn->connect_error) {
    $sql = "SELECT * FROM vistor ORDER BY created_at DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $visitors[] = $row;
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visitors Report View</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>All Visitors (Date range selected: <span class="text-primary"><?php echo htmlspecialchars($start_date); ?></span> to <span class="text-primary"><?php echo htmlspecialchars($end_date); ?></span>)</h2>
    <?php if (empty($visitors)): ?>
        <div class="alert alert-warning mt-4">There are no visitors recorded in the system.</div>
    <?php else: ?>
        <div class="mb-3 mt-3">
            <a class="btn btn-primary" href="../api/export_visitors_pdf.php" target="_blank">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>ID Number</th>
                        <th>Email</th>
                        <th>Telephone</th>
                        <th>Department</th>
                        <th>Person to Visit</th>
                        <th>Purpose</th>
                        <th>Equipment</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($visitors as $v): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($v['visitor_name']); ?></td>
                        <td><?php echo htmlspecialchars($v['id_number']); ?></td>
                        <td><?php echo htmlspecialchars($v['email']); ?></td>
                        <td><?php echo htmlspecialchars($v['telephone']); ?></td>
                        <td><?php echo htmlspecialchars($v['department']); ?></td>
                        <td><?php echo htmlspecialchars($v['person_to_visit']); ?></td>
                        <td><?php echo htmlspecialchars($v['purpose']); ?></td>
                        <td><?php echo htmlspecialchars($v['equipment_brought']); ?></td>
                        <td><?php echo htmlspecialchars($v['status']); ?></td>
                        <td><?php echo htmlspecialchars($v['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <div class="mt-3">
        <a href="reports.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Reports</a>
    </div>
</div>
</body>
</html> 