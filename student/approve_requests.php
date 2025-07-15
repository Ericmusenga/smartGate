<?php
session_start();
include '../db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$msg = "";

// Handle Approve
if (isset($_GET['approve_id'])) {
    $id = $_GET['approve_id'];
    $conn->query("UPDATE computer_lending SET status = 'active' WHERE id = $id AND lender_id = $student_id");
    $msg = "Lending approved successfully.";
}

// Handle Reject
if (isset($_GET['reject_id'])) {
    $id = $_GET['reject_id'];
    $conn->query("UPDATE computer_lending SET status = 'rejected' WHERE id = $id AND lender_id = $student_id");
    $msg = "Lending request rejected.";
}

// Get pending requests
$sql = "
    SELECT cl.id, cl.borrower_regno, d.device_name, d.serial_number, cl.lending_date
    FROM computer_lending cl
    JOIN devices d ON cl.device_id = d.id
    WHERE cl.lender_id = $student_id AND cl.status = 'pending'
";
$requests = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lending Requests</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h2>Pending Lending Requests</h2>

    <?php if ($msg): ?>
        <p style="color: green;"><?php echo $msg; ?></p>
    <?php endif; ?>

    <?php if ($requests->num_rows > 0): ?>
        <table border="1" cellpadding="10">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Serial</th>
                    <th>Borrower Reg. No</th>
                    <th>Lending Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['device_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['borrower_regno']); ?></td>
                        <td><?php echo $row['lending_date']; ?></td>
                        <td>
                            <a href="?approve_id=<?php echo $row['id']; ?>">✅ Approve</a> |
                            <a href="?reject_id=<?php echo $row['id']; ?>" onclick="return confirm('Reject this request?');">❌ Reject</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No pending lending requests.</p>
    <?php endif; ?>
</div>

<footer class="footer">
    &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
</footer>
</body>
</html>
