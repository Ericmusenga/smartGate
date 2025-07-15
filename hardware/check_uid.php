<?php
// Set headers to allow cross-origin requests (for ESP8266) and specify JSON content type
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// --- Database Connection Details ---
$host = 'localhost'; // Usually 'localhost' if MySQL is on the same machine as Apache
$dbname = 'gate_management_system'; // Your database name
$user = 'root'; // Your MySQL username (e.g., 'root' for XAMPP default)
$pass = ''; // Your MySQL password (empty for XAMPP default, but secure installations should have one)

// --- Get and Normalize Card Number (UID) ---
// The ESP8266 sends the UID via a GET parameter named 'uid'
$cardNumber = isset($_GET['uid']) ? strtoupper(trim($_GET['uid'])) : '';

// --- Input Validation ---
if (empty($cardNumber)) {
    // If UID is missing, return an error JSON response
    echo json_encode([
        'allowed' => false,
        'message' => 'Card number (UID) is missing from the request.'
    ]);
    exit; // Stop script execution
}

// --- Database Interaction ---
try {
    // Create PDO (PHP Data Objects) connection
    // DSN (Data Source Name) specifies connection details
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    // Set PDO error mode to exception for robust error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL query to select card information with student details
    $stmt = $pdo->prepare("SELECT rc.id, rc.student_id, rc.card_number, rc.card_type, rc.is_active,
                                  s.registration_number, s.first_name, s.last_name, s.email, 
                                  s.department, s.program, s.year_of_study, s.phone, s.gender,
                                  u.username, u.is_first_login
                           FROM rfid_cards rc
                           JOIN students s ON rc.student_id = s.id
                           LEFT JOIN users u ON s.id = u.student_id
                           WHERE UPPER(rc.card_number) = :cardNumber AND rc.is_active = 1");

    // Execute the prepared statement with the sanitized card number
    $stmt->execute([':cardNumber' => $cardNumber]);

    // Check if any row was returned
    if ($stmt->rowCount() > 0) {
        // Card found and is active, fetch its details with student information
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as an associative array

        // Return success JSON response with student information
        echo json_encode([
            'allowed' => true,
            'student_id' => $row['student_id'],
            'card_type' => $row['card_type'],
            'card_number' => $row['card_number'],
            'student' => [
                'registration_number' => $row['registration_number'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'full_name' => $row['first_name'] . ' ' . $row['last_name'],
                'email' => $row['email'],
                'department' => $row['department'],
                'program' => $row['program'],
                'year_of_study' => $row['year_of_study'],
                'phone' => $row['phone'],
                'gender' => $row['gender'],
                'username' => $row['username'],
                'has_account' => !empty($row['username']),
                'is_first_login' => $row['is_first_login'] ? true : false
            ]
        ]);
    } else {
        // Card not found or is inactive
        echo json_encode([
            'allowed' => false,
            'message' => 'Card number not registered or inactive.',
            'card_number' => $cardNumber,
            'error_type' => 'unauthorized_card'
        ]);
    }
} catch (PDOException $e) {
    // Catch any database connection or query errors
    error_log("Database connection failed or query error in check_uid.php: " . $e->getMessage()); // Log error for debugging
    echo json_encode([
        'allowed' => false,
        'error' => 'Database operation failed.', // Generic message for client
        'message' => 'Internal server error. Please try again later.' // More user-friendly message
    ]);
}
?>