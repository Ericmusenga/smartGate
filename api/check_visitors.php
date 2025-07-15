<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if (!$start_date || !$end_date) {
    echo json_encode(['hasData' => false]);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['hasData' => false]);
    exit;
}

$sql = "SELECT COUNT(*) as cnt FROM vistor WHERE created_at BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$start = $start_date . ' 00:00:00';
$end = $end_date . ' 23:59:59';
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$stmt->bind_result($cnt);
$stmt->fetch();
$stmt->close();
$conn->close();

if ($cnt > 0) {
    echo json_encode(['hasData' => true]);
} else {
    echo json_encode(['hasData' => false]);
} 