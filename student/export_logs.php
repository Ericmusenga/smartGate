<?php
session_start();
include '../db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="entry_exit_logs.csv"');

$output = fopen("php://output", "w");

// Column Headers
fputcsv($output, ['Entry Time', 'Exit Time']);

// Fetch logs
$stmt = $conn->prepare("SELECT entry_time, exit_time FROM entry_exit_logs WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Output rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['entry_time'], $row['exit_time'] ?? '---']);
}

fclose($output);
exit;
?>
