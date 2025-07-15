<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Connect to the database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$sql = "SELECT id, registration_number, first_name, last_name, email, phone, department, program, year_of_study, gender, date_of_birth, address, emergency_contact, emergency_phone, is_active, created_at, updated_at FROM students";
$result = $conn->query($sql);

$students = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

$conn->close();
echo json_encode(['students' => $students]); 