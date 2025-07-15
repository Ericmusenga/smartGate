<?php
// edit_visitor.php
// Database configuration
$host = 'localhost';
$dbname = 'gate_management_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get visitor ID from URL
$visitor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($visitor_id <= 0) {
    header('Location: /Capstone_project/pages/visitors.php');
    exit;
}

// Fetch visitor data
$sql = "SELECT * FROM vistor WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $visitor_id]);
$visitor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$visitor) {
    header('Location: /Capstone_project/pages/visitors.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_name = trim($_POST['visitor_name']);
    $id_number = trim($_POST['id_number']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $department = $_POST['department'];
    $person_to_visit = trim($_POST['person_to_visit']);
    $visit_purpose = trim($_POST['visit_purpose']);
    $equipment_brought = isset($_POST['equipment_brought']) ? implode(', ', $_POST['equipment_brought']) : '';
    $other_equipment_details = trim($_POST['other_equipment_details']);
    $status = $_POST['status'];
    
    // Validate required fields
    if (empty($visitor_name) || empty($id_number) || empty($telephone) || empty($department) || empty($person_to_visit)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $update_sql = "UPDATE vistor SET 
                visitor_name = :visitor_name,
                id_number = :id_number,
                email = :email,
                telephone = :telephone,
                department = :department,
                person_to_visit = :person_to_visit,
                purpose = :purpose,
                equipment_brought = :equipment_brought,
                other_equipment_details = :other_equipment_details,
                status = :status,
                updated_at = NOW()
                WHERE id = :id";
            
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                ':visitor_name' => $visitor_name,
                ':id_number' => $id_number,
                ':email' => $email,
                ':telephone' => $telephone,
                ':department' => $department,
                ':person_to_visit' => $person_to_visit,
                ':purpose' => $visit_purpose,
                ':equipment_brought' => $equipment_brought,
                ':other_equipment_details' => $other_equipment_details,
                ':status' => $status,
                ':id' => $visitor_id
            ]);
            
            $success = "Visitor information updated successfully!";
            
            // Refresh visitor data
            $stmt->execute([':id' => $visitor_id]);
            $visitor = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $error = "Error updating visitor: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Visitor - Gate Management System</title>
    <link rel="stylesheet" href="/Capstone_project/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; margin-top: 20px; }
        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h1 { color: #2c3e50; margin-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 6px; font-size: 16px; transition: border-color 0.2s; }
        .form-control:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
        .btn { padding: 12px 25px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px; transition: all 0.2s; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-secondary:hover { background: #545b62; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .checkbox-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px; }
        .checkbox-item { display: flex; align-items: center; gap: 8px; padding: 8px; border: 1px solid #e9ecef; border-radius: 4px; }
        .checkbox-item input[type="checkbox"] { margin: 0; }
        .form-actions { text-align: center; margin-top: 30px; }
        
        /* Fixed footer styles */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            text-align: center;
            color: #7f8c8d;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 1000;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Adjust container for fixed footer */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            margin-bottom: 80px; /* Add bottom margin for fixed footer */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <div class="form-header">
                <h1><i class="fas fa-user-edit"></i> Edit Visitor</h1>
                <p>Update visitor information</p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="visitor_name">Full Name *</label>
                    <input type="text" id="visitor_name" name="visitor_name" class="form-control" 
                           value="<?php echo htmlspecialchars($visitor['visitor_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="id_number">ID Number *</label>
                    <input type="text" id="id_number" name="id_number" class="form-control" 
                           value="<?php echo htmlspecialchars($visitor['id_number']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($visitor['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="telephone">Phone Number *</label>
                    <input type="tel" id="telephone" name="telephone" class="form-control" 
                           value="<?php echo htmlspecialchars($visitor['telephone']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="department">Department to Visit *</label>
                    <select id="department" name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <option value="Administration" <?php echo $visitor['department'] === 'Administration' ? 'selected' : ''; ?>>Administration</option>
                        <option value="Academic Affairs" <?php echo $visitor['department'] === 'Academic Affairs' ? 'selected' : ''; ?>>Academic Affairs</option>
                        <option value="Student Affairs" <?php echo $visitor['department'] === 'Student Affairs' ? 'selected' : ''; ?>>Student Affairs</option>
                        <option value="Finance" <?php echo $visitor['department'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                        <option value="Human Resources" <?php echo $visitor['department'] === 'Human Resources' ? 'selected' : ''; ?>>Human Resources</option>
                        <option value="ICT Department" <?php echo $visitor['department'] === 'ICT Department' ? 'selected' : ''; ?>>ICT Department</option>
                        <option value="Library" <?php echo $visitor['department'] === 'Library' ? 'selected' : ''; ?>>Library</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="person_to_visit">Person to Visit *</label>
                    <input type="text" id="person_to_visit" name="person_to_visit" class="form-control" 
                           value="<?php echo htmlspecialchars($visitor['person_to_visit']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="visit_purpose">Purpose of Visit</label>
                    <textarea id="visit_purpose" name="visit_purpose" class="form-control" rows="3"><?php echo htmlspecialchars($visitor['purpose']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="inside" <?php echo ($visitor['status'] ?? 'inside') === 'inside' ? 'selected' : ''; ?>>Inside</option>
                        <option value="exited" <?php echo ($visitor['status'] ?? 'inside') === 'exited' ? 'selected' : ''; ?>>Exited</option>
                        <option value="overdue" <?php echo ($visitor['status'] ?? 'inside') === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Equipment Brought</label>
                    <div class="checkbox-group">
                        <?php
                        $equipment_options = ['Laptop', 'Mobile Phone', 'Tablet', 'Camera', 'Recording Device', 'Documents', 'USB Drive', 'External Hard Drive'];
                        $selected_equipment = explode(', ', $visitor['equipment_brought'] ?? '');
                        
                        foreach ($equipment_options as $equipment):
                        ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="equipment_<?php echo strtolower(str_replace(' ', '_', $equipment)); ?>" 
                                       name="equipment_brought[]" value="<?php echo $equipment; ?>"
                                       <?php echo in_array($equipment, $selected_equipment) ? 'checked' : ''; ?>>
                                <label for="equipment_<?php echo strtolower(str_replace(' ', '_', $equipment)); ?>"><?php echo $equipment; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="other_equipment_details">Other Equipment Details</label>
                    <textarea id="other_equipment_details" name="other_equipment_details" class="form-control" rows="2"><?php echo htmlspecialchars($visitor['other_equipment_details']); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Visitor
                    </button>
                    <a href="/Capstone_project/pages/visitors.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Visitors
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
    </footer>
</body>
</html>