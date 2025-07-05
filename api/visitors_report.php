<?php
require_once '../config/config.php';

// Only allow security users
if (!is_logged_in() || get_user_type() !== 'security') {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

if (!$start_date || !$end_date) {
    echo 'Start date and end date are required.';
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="visitor_report_' . $start_date . '_to_' . $end_date . '.csv"');

$output = fopen('php://output', 'w');

// CSV header
fputcsv($output, ['Name', 'Telephone', 'Email', 'Purpose', 'Person to Visit', 'Department', 'ID Number', 'Status', 'Created At']);

try {
    $db = getDB();
    $visitors = $db->fetchAll(
        "SELECT visitor_name, telephone, email, purpose, person_to_visit, department, id_number, status, created_at
         FROM visitors
         WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
         ORDER BY created_at ASC",
        [$start_date, $end_date]
    );
    foreach ($visitors as $row) {
        fputcsv($output, [
            $row['visitor_name'],
            $row['telephone'],
            $row['email'],
            $row['purpose'],
            $row['person_to_visit'],
            $row['department'],
            $row['id_number'],
            ucfirst($row['status']),
            $row['created_at'],
        ]);
    }
} catch (Exception $e) {
    fputcsv($output, ['Error: ' . $e->getMessage()]);
}

fclose($output);
exit; 