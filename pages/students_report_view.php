<?php
require_once '../config/database.php';

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$students = [];
// Always fetch all students, regardless of date range
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn->connect_error) {
    $sql = "SELECT * FROM students ORDER BY created_at DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students Report View</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>All Students Registered (Date range selected: <span class="text-primary"><?php echo htmlspecialchars($start_date); ?></span> to <span class="text-primary"><?php echo htmlspecialchars($end_date); ?></span>)</h2>
    <?php if (empty($students)): ?>
        <div class="alert alert-warning mt-4">There are no students recorded in the system.</div>
    <?php else: ?>
        <div class="mb-3 mt-3">
            <a class="btn btn-primary" href="../api/export_students_pdf.php" target="_blank">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reg. No</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Program</th>
                        <th>Year</th>
                        <th>Student Card No</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                        <th>Address</th>
                        <th>Emergency Contact</th>
                        <th>Emergency Phone</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['id']) ?></td>
                        <td><?= htmlspecialchars($s['registration_number']) ?></td>
                        <td><?= htmlspecialchars($s['first_name']) ?></td>
                        <td><?= htmlspecialchars($s['last_name']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td><?= htmlspecialchars($s['phone']) ?></td>
                        <td><?= htmlspecialchars($s['department']) ?></td>
                        <td><?= htmlspecialchars($s['program']) ?></td>
                        <td><?= htmlspecialchars($s['year_of_study']) ?></td>
                        <td><?= htmlspecialchars($s['Student_card_number']) ?></td>
                        <td><?= htmlspecialchars($s['gender']) ?></td>
                        <td><?= htmlspecialchars($s['date_of_birth']) ?></td>
                        <td><?= htmlspecialchars($s['address']) ?></td>
                        <td><?= htmlspecialchars($s['emergency_contact']) ?></td>
                        <td><?= htmlspecialchars($s['emergency_phone']) ?></td>
                        <td><?= htmlspecialchars($s['serial_number']) ?></td>
                        <td><?= $s['is_active'] ? 'Active' : 'Inactive' ?></td>
                        <td><?= htmlspecialchars($s['created_at']) ?></td>
                        <td><?= htmlspecialchars($s['updated_at']) ?></td>
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