<?php
require_once '../config/config.php';

// Only admins can register cards
if (!is_logged_in() || get_user_type() !== 'admin') {
    redirect('../unauthorized.php');
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $card_number = sanitize_input($_POST['card_number'] ?? '');
    $card_type = sanitize_input($_POST['card_type'] ?? '');
    $expiry_date = sanitize_input($_POST['expiry_date'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (!$student_id || !$card_number || !$card_type) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $db = getDB();
            // Check for duplicate card number
            $exists = $db->fetch("SELECT id FROM rfid_cards WHERE card_number = ?", [$card_number]);
            if ($exists) {
                $error_message = 'A card with this number already exists.';
            } else {
                // Check if student already has a card of this type
                $existing_card = $db->fetch("SELECT id FROM rfid_cards WHERE student_id = ? AND card_type = ?", [$student_id, $card_type]);
                if ($existing_card) {
                    $error_message = 'This student already has a ' . str_replace('_', ' ', $card_type) . ' card.';
                } else {
                    $db->query("INSERT INTO rfid_cards (student_id, card_number, card_type, expiry_date, is_active) VALUES (?, ?, ?, ?, ?)",
                        [$student_id, $card_number, $card_type, $expiry_date ?: null, $is_active]);
                    $success_message = 'Card registered successfully!';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Error registering card: ' . $e->getMessage();
        }
    }
}

// Fetch students for selection
try {
    $db = getDB();
    $students = $db->fetchAll("SELECT id, registration_number, first_name, last_name, email, department, program, year_of_study FROM students ORDER BY first_name, last_name");
} catch (Exception $e) {
    $students = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Register New Card</div>
            <div class="page-subtitle">Register a new RFID card for a student</div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="card" style="max-width:700px;margin:auto;">
            <div class="card-body">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student *</label>
                    <select id="student_id" name="student_id" class="form-select" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php if (!empty($student_id) && $student_id == $student['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['registration_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="card_number" class="form-label">Card Number *</label>
                        <input type="text" id="card_number" name="card_number" class="form-control" 
                               value="<?php echo htmlspecialchars($card_number ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="card_type" class="form-label">Card Type *</label>
                        <select id="card_type" name="card_type" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            <option value="student_id" <?php if (!empty($card_type) && $card_type == 'student_id') echo 'selected'; ?>>Student ID</option>
                            <option value="library_card" <?php if (!empty($card_type) && $card_type == 'library_card') echo 'selected'; ?>>Library Card</option>
                            <option value="other" <?php if (!empty($card_type) && $card_type == 'other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="expiry_date" class="form-label">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-control" 
                               value="<?php echo htmlspecialchars($expiry_date ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" id="is_active" name="is_active" class="form-check-input" <?php if (!empty($is_active)) echo 'checked'; ?>>
                            <label for="is_active" class="form-check-label">Mark as Active</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Register Card
                </button>
                <a href="../pages/cards.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?> 