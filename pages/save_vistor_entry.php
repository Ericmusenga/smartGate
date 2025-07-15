<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'gate_management_system');

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $visitor_id = $_POST['vname'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $department = $_POST['dept'] ?? '';
    $equipment = $_POST['equipment'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Validate required fields
    if (empty($visitor_id) || empty($purpose) || empty($department)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }
    
    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO entry_visitor (visitor_id, purpose, department, equipment, notes, entry_time, entry_date) VALUES (?, ?, ?, ?, ?, NOW(), CURDATE())");
    
    if ($stmt) {
        // Bind parameters
        $stmt->bind_param("issss", $visitor_id, $purpose, $department, $equipment, $notes);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo "<script>
                alert('Visitor entry recorded successfully!');
                window.location.href = '../pages/visitor_logs.php'; // Redirect to main page
            </script>";
        } else {
            echo "<script>
                alert('Error: " . $stmt->error . "');
                window.history.back();
            </script>";
        }
        
        // Close statement
        $stmt->close();
    } else {
        echo "<script>
            alert('Error preparing statement: " . $conn->error . "');
            window.history.back();
        </script>";
    }
} else {
    echo "<script>
        alert('Invalid request method.');
        window.history.back();
    </script>";
}

// Close connection
$conn->close();
?>