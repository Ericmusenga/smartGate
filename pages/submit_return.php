<?php

require_once '../config/config.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "gate_management_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $reg_number = isset($_POST['reg_number']) ? $_POST['reg_number'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Validate required fields
    if (empty($reg_number)) {
        $_SESSION['error'] = "Please select a registration number.";
        header("Location: return_computer.php");
        exit();
    }
    
    // Get the borrower's information from students table
    $borrower_query = "SELECT * FROM students WHERE id = ?";
    $stmt = $conn->prepare($borrower_query);
    $stmt->bind_param("i", $reg_number);
    $stmt->execute();
    $borrower_result = $stmt->get_result();
    
    if ($borrower_result->num_rows == 0) {
        $_SESSION['error'] = "Selected student not found.";
        header("Location: lend_computer.php");
        exit();
    }
    
    $borrower = $borrower_result->fetch_assoc();
    
    // For this example, assuming the lender is the current logged-in user
    // You might want to get this from session or modify based on your authentication system
    $lender_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to 1 for demo
    
    // Insert lending record into database
    // Assuming you have a table called 'computer_lending' or similar
    $insert_query = "INSERT INTO computer_return (lender_id, borrower_id, notes) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iss", $lender_id, $reg_number, $notes);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Computer successfully lent to " . htmlspecialchars($borrower['first_name']) . " " . htmlspecialchars($borrower['last_name']) . " (Reg: " . htmlspecialchars($borrower['registration_number']) . ")";
        header("Location: dashboard_student.php");
        exit();
    } else {
        $_SESSION['error'] = "Error lending computer: " . $conn->error;
        header("Location: lend_computer.php");
        exit();
    }
    
    $stmt->close();
} else {
    // If not POST request, redirect back to form
    header("Location: lend_computer.php");
    exit();
}

$conn->close();
?>