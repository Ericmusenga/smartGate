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
    $notes = $_POST['notes'] ?? '';
    $exit_gate = "Main Gate";
    
    // Validate required fields
    if (empty($visitor_id) || empty($notes)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }
    
    // Prepare SQL statement (FIXED: removed extra closing parenthesis)
    $stmt = $conn->prepare("INSERT INTO exit_visitor (visitor_id, exit_time, exit_gate, notes) VALUES (?, NOW(), ?, ?)");
    
    if ($stmt) {
        // Bind parameters
        $stmt->bind_param("iss", $visitor_id, $exit_gate, $notes);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo "<script>
                alert('Visitor exit recorded successfully!');
                window.location.href = 'visitor_logs.php';
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