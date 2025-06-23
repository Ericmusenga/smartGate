<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check user type - admins and security can access this
$user_type = get_user_type();
if (!in_array($user_type, ['admin', 'security'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Handle GET request (view card details)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        $card_id = (int)$_GET['id'];
        
        $sql = "SELECT rc.*, s.registration_number, s.first_name, s.last_name, s.email, s.department, s.program, s.year_of_study
                FROM rfid_cards rc 
                JOIN students s ON rc.student_id = s.id
                WHERE rc.id = ?";
        
        $card = $db->fetch($sql, [$card_id]);
        
        if (!$card) {
            echo json_encode(['error' => 'Card not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'card' => $card]);
        exit;
    }
    
    // Handle POST request (update card)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Only admins can edit cards
        if ($user_type !== 'admin') {
            echo json_encode(['error' => 'Only administrators can edit cards']);
            exit;
        }
        
        $card_id = (int)($_POST['id'] ?? 0);
        $card_number = sanitize_input($_POST['card_number'] ?? '');
        $card_type = sanitize_input($_POST['card_type'] ?? '');
        $expiry_date = sanitize_input($_POST['expiry_date'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$card_id || !$card_number || !$card_type) {
            echo json_encode(['error' => 'Please fill in all required fields']);
            exit;
        }
        
        // Check if card exists
        $existing_card = $db->fetch("SELECT id, card_number FROM rfid_cards WHERE id = ?", [$card_id]);
        if (!$existing_card) {
            echo json_encode(['error' => 'Card not found']);
            exit;
        }
        
        // Check for duplicate card number (excluding current card)
        $duplicate = $db->fetch("SELECT id FROM rfid_cards WHERE card_number = ? AND id != ?", [$card_number, $card_id]);
        if ($duplicate) {
            echo json_encode(['error' => 'A card with this number already exists']);
            exit;
        }
        
        // Update card
        $db->query("UPDATE rfid_cards SET card_number = ?, card_type = ?, expiry_date = ?, is_active = ? WHERE id = ?",
            [$card_number, $card_type, $expiry_date ?: null, $is_active, $card_id]);
        
        echo json_encode(['success' => 'Card updated successfully']);
        exit;
    }
    
    // Invalid request
    echo json_encode(['error' => 'Invalid request']);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 