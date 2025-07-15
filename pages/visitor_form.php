<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$servername = "localhost";
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$dbname = "gate_management_system";

$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create connection
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get form data
        $visitor_name = trim($_POST['visitor_name']);
        $id_number = trim($_POST['id_number']);
        $email = trim($_POST['email']) ?: null; // Set to null if empty
        $telephone = trim($_POST['telephone']);
        $department = $_POST['department'];
        $person_to_visit = trim($_POST['person_to_visit']) ?: null;
        $purpose = trim($_POST['purpose']);
        
        // Process equipment data
        $equipment_brought = "";
        $other_equipment_details = null;
        
        if (isset($_POST['equipment']) && is_array($_POST['equipment'])) {
            $equipment_array = $_POST['equipment'];
            
            // Check if "Other" is selected and get details
            if (in_array('Other', $equipment_array) && !empty($_POST['other_equipment_details'])) {
                $other_equipment_details = trim($_POST['other_equipment_details']);
                // Replace "Other" with the actual details in the array
                $key = array_search('Other', $equipment_array);
                $equipment_array[$key] = 'Other: ' . $other_equipment_details;
            }
            
            $equipment_brought = implode(', ', $equipment_array);
        }
        
        // Validate required fields
        if (empty($visitor_name) || empty($id_number) || empty($telephone) || empty($department) || empty($purpose)) {
            throw new Exception("Please fill in all required fields.");
        }
        // Validate id_number and telephone
        if (!preg_match('/^\d{1,15}$/', $id_number)) {
            throw new Exception("ID Number must be numeric and less than 16 digits.");
        }
        if (!preg_match('/^\d{10,}$/', $telephone)) {
            throw new Exception("Phone number must be numeric and at least 10 digits.");
        }
        
        // Check if equipment is selected
        if (empty($equipment_brought)) {
            throw new Exception("Please select at least one equipment option.");
        }
        
        // Check if visitor with same ID already exists and is still active
        $check_sql = "SELECT id FROM vistor WHERE id_number = :id_number AND status = 'active'";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':id_number', $id_number);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            throw new Exception("A visitor with ID number '$id_number' is already registered and active in the system.");
        }
        
        // Prepare SQL statement
        $sql = "INSERT INTO vistor (
            visitor_name, 
            id_number, 
            email, 
            telephone, 
            department, 
            person_to_visit, 
            purpose, 
            equipment_brought, 
            other_equipment_details, 
            registration_date, 
            status, 
            created_at, 
            updated_at
        ) VALUES (
            :visitor_name, 
            :id_number, 
            :email, 
            :telephone, 
            :department, 
            :person_to_visit, 
            :purpose, 
            :equipment_brought, 
            :other_equipment_details, 
            NOW(), 
            'active', 
            NOW(), 
            NOW()
        )";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':visitor_name', $visitor_name);
        $stmt->bindParam(':id_number', $id_number);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':person_to_visit', $person_to_visit);
        $stmt->bindParam(':purpose', $purpose);
        $stmt->bindParam(':equipment_brought', $equipment_brought);
        $stmt->bindParam(':other_equipment_details', $other_equipment_details);
        
        // Execute the statement
        if ($stmt->execute()) {
            $visitor_id = $pdo->lastInsertId();
            $success_message = "Visitor registered successfully! Registration ID: #" . str_pad($visitor_id, 6, '0', STR_PAD_LEFT);
            
            // Optional: Log the registration
            error_log("New visitor registered: ID=$visitor_id, Name=$visitor_name, Department=$department");
            
            // Clear form data after successful submission
            $_POST = array();
        } else {
            throw new Exception("Error registering visitor. Please try again.");
        }
        
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
        error_log("Database error in visitor registration: " . $e->getMessage());
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Visitor - Security Dashboard</title>
    <link rel="stylesheet" href="/Capstone_project/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar { position: fixed; left: 0; top: 70px; bottom: 0; width: 250px; background: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.07); z-index: 999; transition: transform 0.3s; }
        .sidebar.closed { transform: translateX(-100%); }
        .sidebar-menu { padding: 2rem 0; }
        .menu-section { margin-bottom: 2rem; }
        .menu-section h3 { color: #7f8c8d; font-size: 0.8rem; text-transform: uppercase; padding: 0 2rem 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid #eee; }
        .menu-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; color: #2c3e50; text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .menu-item:hover, .menu-item.active { background: #f4f6fb; border-left-color: #3498db; color: #3498db; }
        .menu-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        .header { position: fixed; top: 0; left: 0; right: 0; background: #667eea; color: #fff; z-index: 1000; display: flex; align-items: center; justify-content: space-between; padding: 1rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
        .header .sidebar-toggle { background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; margin-right: 1rem; }
        .header .user-info { display: flex; align-items: center; gap: 1rem; }
        .header .user-avatar { width: 35px; height: 35px; border-radius: 50%; background: #764ba2; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 1rem 2rem; text-align: center; color: #7f8c8d; border-top: 1px solid rgba(0, 0, 0, 0.1); z-index: 1000; font-size: 0.9rem; font-weight: 500; }
        .main-content { margin-left: 250px; margin-top: 70px; padding: 2rem 1rem 4rem 1rem; min-height: calc(100vh - 70px - 80px); transition: margin-left 0.3s; background: rgb(8, 78, 147); }
        .sidebar.closed ~ .main-content { margin-left: 0; }
        
        /* Form Specific Styles */
        .form-container { background: #f8f9fa; min-height: 100vh; padding: 30px 0; }
        .form-header { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px 30px 20px 30px; margin-bottom: 30px; text-align: center; }
        .form-title { font-size: 2.2rem; font-weight: 700; color: #2c3e50; margin-bottom: 8px; }
        .form-subtitle { color: #7f8c8d; font-size: 1.1rem; }
        .form-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px; max-width: 800px; margin: 0 auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
        .form-group { margin-bottom: 25px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; color: #2c3e50; margin-bottom: 8px; display: block; font-size: 1rem; }
        .form-group label .required { color: #dc3545; margin-left: 3px; }
        .form-control { border: 2px solid #e9ecef; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.2s; width: 100%; box-sizing: border-box; }
        .form-control:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
        .form-control:invalid { border-color: #dc3545; }
        .form-control::placeholder { color: #6c757d; }
        .textarea-control { min-height: 100px; resize: vertical; }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 8px; }
        .checkbox-item { display: flex; align-items: center; gap: 8px; }
        .checkbox-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: #007bff; }
        .checkbox-item label { margin-bottom: 0; font-weight: 500; color: #495057; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; border-top: 1px solid #e9ecef; padding-top: 25px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 1rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; }
        .btn-primary:hover { background: #0056b3; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,123,255,0.3); }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-secondary:hover { background: #5a6268; }
        .alert { border-radius: 10px; border: none; padding: 15px 20px; margin-bottom: 25px; font-weight: 500; }
        .alert-success { background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; }
        .alert-danger { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; }
        .alert i { margin-right: 8px; }
        .form-help { font-size: 0.875rem; color: #6c757d; margin-top: 5px; }
        .equipment-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .department-select { position: relative; }
        .other-equipment { margin-top: 10px; display: none; }
        .other-equipment.show { display: block; }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; gap: 20px; }
            .form-card { padding: 20px; margin: 0 15px; }
            .form-header { padding: 20px 15px; margin: 0 15px 20px 15px; }
            .form-title { font-size: 1.8rem; }
            .form-actions { flex-direction: column; }
            .btn { justify-content: center; }
            .equipment-grid { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('closed');
        }
        
        function toggleOtherEquipment() {
            const otherCheckbox = document.getElementById('equipment_other');
            const otherInput = document.getElementById('other_equipment_input');
            
            if (otherCheckbox.checked) {
                otherInput.classList.add('show');
                otherInput.querySelector('input').required = true;
            } else {
                otherInput.classList.remove('show');
                otherInput.querySelector('input').required = false;
                otherInput.querySelector('input').value = '';
            }
        }
        
        function validateForm() {
            const form = document.getElementById('visitorForm');
            const formData = new FormData(form);
            
            // Check if at least one equipment is selected
            const equipmentInputs = form.querySelectorAll('input[name="equipment[]"]');
            const isEquipmentSelected = Array.from(equipmentInputs).some(input => input.checked);
            
            if (!isEquipmentSelected) {
                alert('Please select at least one equipment option or check "None" if visitor has no equipment.');
                return false;
            }
            
            var idNumber = document.getElementById('id_number').value.trim();
            var phone = document.getElementById('telephone').value.trim();
            if (!/^\d{1,15}$/.test(idNumber)) {
                alert('ID Number must be numeric and less than 16 digits.');
                return false;
            }
            if (!/^\d{10,}$/.test(phone)) {
                alert('Phone number must be numeric and at least 10 digits.');
                return false;
            }
            
            return true;
        }
        
        // Auto-dismiss success message after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.style.opacity = '0';
                    setTimeout(function() {
                        successAlert.remove();
                    }, 300);
                }, 5000);
            }
        });
    </script>
</head>
<body>
    <header class="header">
        <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <div style="font-size: 1.3rem; font-weight: bold; letter-spacing: 1px;">Gate Management System - Security</div>
        <div class="user-info">
            <div class="user-avatar">S</div>
            <span>Security Officer</span>
            <a href="/Capstone_project/logout.php" class="logout-btn" style="background:#e74c3c; color:#fff; padding:0.5rem 1rem; border-radius:20px; text-decoration:none;">Logout</a>
        </div>
    </header>
    
    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <div class="menu-section">
                <h3>Security</h3>
                <a href="/Capstone_project/pages/dashboard_security.php" class="menu-item"><i class="fas fa-shield-alt"></i> Dashboard</a>
                <a href="/Capstone_project/pages/visitor_form.php" class="menu-item active"><i class="fas fa-user-plus"></i> Register Visitor</a>
                <a href="/Capstone_project/pages/visitors.php" class="menu-item"><i class="fas fa-users"></i> Manage Visitors</a>
                <a href="/Capstone_project/pages/logs.php" class="menu-item"><i class="fas fa-clipboard-list"></i> Student Entry/Exit Logs</a>
                <a href="/Capstone_project/pages/visitor_logs.php" class="menu-item"><i class="fas fa-address-book"></i> Visitor Entry/Exit Logs</a>
            </div>
            <div class="menu-section">
                <h3>Account</h3>
                <a href="/Capstone_project/change_password.php" class="menu-item"><i class="fas fa-key"></i> Change Password</a>
                <a href="/Capstone_project/logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </aside>
    
    <div class="main-content">
        <div class="form-container">
            <div class="form-header">
                <h1 class="form-title"><i class="fas fa-user-plus"></i> Register New Visitor</h1>
                <p class="form-subtitle">Please fill in the visitor information below</p>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-card">
                <form method="POST" action="" id="visitorForm" onsubmit="return validateForm()">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="visitor_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="visitor_name" name="visitor_name" class="form-control" 
                                   placeholder="Enter visitor's full name" 
                                   value="<?php echo isset($_POST['visitor_name']) ? htmlspecialchars($_POST['visitor_name']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_number">ID Number <span class="required">*</span></label>
                            <input type="text" id="id_number" name="id_number" class="form-control" 
                                   placeholder="Enter ID number" 
                                   value="<?php echo isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="Enter email address (optional)"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <div class="form-help">Optional - Leave blank if not available</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone">Telephone <span class="required">*</span></label>
                            <input type="tel" id="telephone" name="telephone" class="form-control" 
                                   placeholder="Enter phone number" 
                                   value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="department">Department to Visit <span class="required">*</span></label>
                            <select id="department" name="department" class="form-control" required>
                                <option value="">Select Department</option>
                                <option value="Administration" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Administration') ? 'selected' : ''; ?>>Administration</option>
                                <option value="Academic Affairs" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Academic Affairs') ? 'selected' : ''; ?>>Academic Affairs</option>
                                <option value="Student Affairs" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Student Affairs') ? 'selected' : ''; ?>>Student Affairs</option>
                                <option value="Finance" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                <option value="Human Resources" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Human Resources') ? 'selected' : ''; ?>>Human Resources</option>
                                <option value="ICT Department" <?php echo (isset($_POST['department']) && $_POST['department'] == 'ICT Department') ? 'selected' : ''; ?>>ICT Department</option>
                                <option value="Library" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Library') ? 'selected' : ''; ?>>Library</option>
                                <option value="Maintenance" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Security" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Security') ? 'selected' : ''; ?>>Security</option>
                                <option value="Health Center" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Health Center') ? 'selected' : ''; ?>>Health Center</option>
                                <option value="Faculty of Education" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Faculty of Education') ? 'selected' : ''; ?>>Faculty of Education</option>
                                <option value="Faculty of Science" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Faculty of Science') ? 'selected' : ''; ?>>Faculty of Science</option>
                                <option value="Faculty of Arts" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Faculty of Arts') ? 'selected' : ''; ?>>Faculty of Arts</option>
                                <option value="Other" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="person_to_visit">Person to Visit</label>
                            <input type="text" id="person_to_visit" name="person_to_visit" class="form-control" 
                                   placeholder="Enter name of person to visit (optional)"
                                   value="<?php echo isset($_POST['person_to_visit']) ? htmlspecialchars($_POST['person_to_visit']) : ''; ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="purpose">Purpose of Visit <span class="required">*</span></label>
                            <textarea id="purpose" name="purpose" class="form-control textarea-control" 
                                      placeholder="Please describe the purpose of your visit" required><?php echo isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Equipment/Items Brought <span class="required">*</span></label>
                            <div class="form-help">Please select all items the visitor is bringing</div>
                            <div class="equipment-grid">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="equipment_laptop" name="equipment[]" value="Laptop"
                                           <?php echo (isset($_POST['equipment']) && in_array('Laptop', $_POST['equipment'])) ? 'checked' : ''; ?>>
                                    <label for="equipment_laptop">Laptop</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="equipment_phone" name="equipment[]" value="Mobile Phone"
                                           <?php echo (isset($_POST['equipment']) && in_array('Mobile Phone', $_POST['equipment'])) ? 'checked' : ''; ?>>
                                    <label for="equipment_phone">Mobile Phone</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="equipment_tablet" name="equipment[]" value="Tablet"
                                           <?php echo (isset($_POST['equipment']) && in_array('Tablet', $_POST['equipment'])) ? 'checked' : ''; ?>>
                                    <label for="equipment_tablet">Tablet</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="equipment_camera" name="equipment[]" value="Camera"
                                           <?php echo (isset($_POST['equipment']) && in_array('Camera', $_POST['equipment'])) ? 'checked' : ''; ?>>
                                    <label for="equipment_camera">Camera</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="equipment_bag" name="equipment[]" value="Bag/Backpack"
                                           <?php echo (isset($_POST['equipment']) && in_array('Bag/Backpack', $_POST['equipment'])) ? 'checked' : ''; ?>>
                                    <label for="equipment_bag">Bag/Backpack</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="equipment_documents" name="equipment[]" value="Documents"
                                           <?php echo (isset($_POST['equipment']) && in_array('Documents', $_POST['equipment'])) ? 'checked' : ''; ?>>
                                    <label for="equipment_documents">Documents</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="equipment_none" name="equipment[]" value="None"
                                           <?php echo (isset($_POST['equipment']) && in_array('None', $_POST['equipment'])) ? 'checked' : ''; ?>>
                                    <label for="equipment_none">None</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="equipment_other" name="equipment[]" value="Other" 
                                           <?php echo (isset($_POST['equipment']) && in_array('Other', $_POST['equipment'])) ? 'checked' : ''; ?>
                                           onchange="toggleOtherEquipment()">
                                    <label for="equipment_other">Other</label>
                                </div>
                            </div>
                            <div class="other-equipment <?php echo (isset($_POST['equipment']) && in_array('Other', $_POST['equipment'])) ? 'show' : ''; ?>" id="other_equipment_input">
                                <input type="text" name="other_equipment_details" class="form-control" 
                                       placeholder="Please specify other equipment"
                                       value="<?php echo isset($_POST['other_equipment_details']) ? htmlspecialchars($_POST['other_equipment_details']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='/Capstone_project/pages/dashboard_security.php'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Register Visitor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
    </footer>
</body>
</html>