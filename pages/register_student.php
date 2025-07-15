<?php 
require_once '../config/config.php';
include '../includes/header.php';
include '../includes/sidebar.php';

// Initialize variables
$message = '';
$message_type = '';

// Get database connection
$db = getDB();
$pdo = $db->getConnection();

// Handle CSV upload
if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
    $csv_file = $_FILES['csv_file'];
    
    // Check if file was uploaded without errors
    if ($csv_file['error'] == 0) {
        $file_extension = pathinfo($csv_file['name'], PATHINFO_EXTENSION);
        
        // Validate file extension
        if (strtolower($file_extension) == 'csv') {
            $file_path = $csv_file['tmp_name'];
            
            // Open and read CSV file
            if (($handle = fopen($file_path, 'r')) !== FALSE) {
                $row_count = 0;
                $success_count = 0;
                $error_count = 0;
                $errors = [];
                
                // Skip header row
                $header = fgetcsv($handle, 1000, ',');
                
                // Begin transaction to ensure data consistency
                $pdo->beginTransaction();
                
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $row_count++;
                    
                    // Map CSV columns to variables
                    $registration_number = trim($data[0]);
                    $first_name = trim($data[1]);
                    $last_name = trim($data[2]);
                    $email = trim($data[3]);
                    $phone = trim($data[4]);
                    $department = trim($data[5]);
                    $program = trim($data[6]);
                    $year_of_study = intval($data[7]);
                    $gender = strtolower(trim($data[8]));
                    $date_of_birth = trim($data[9]);
                    $address = trim($data[10]);
                   
                    $emergency_contact = trim($data[11]);
                    $emergency_phone = trim($data[12]);
                     $serial_number=trim($data[13]);
                    
                    // Validate required fields
                    if (empty($registration_number) || empty($first_name) || empty($last_name) || 
                        empty($email) || empty($department) || empty($program) || $year_of_study == 0) {
                        $errors[] = "Row $row_count: Missing required fields";
                        $error_count++;
                        continue;
                    }
                    
                    // Validate email format
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Row $row_count: Invalid email format";
                        $error_count++;
                        continue;
                    }
                    
                    // Validate gender
                    if (!in_array($gender, ['male', 'female', 'other'])) {
                        $gender = null;
                    }
                    
                    // Validate and format date of birth
                    $formatted_dob = null;
                    if (!empty($date_of_birth)) {
                        $date_obj = DateTime::createFromFormat('Y-m-d', $date_of_birth);
                        if (!$date_obj) {
                            $date_obj = DateTime::createFromFormat('d/m/Y', $date_of_birth);
                        }
                        if (!$date_obj) {
                            $date_obj = DateTime::createFromFormat('m/d/Y', $date_of_birth);
                        }
                        if ($date_obj) {
                            $formatted_dob = $date_obj->format('Y-m-d');
                        }
                    }
                    
                    // Validate registration_number and phone
                    if (!preg_match('/^\d{1,15}$/', $registration_number)) {
                        $errors[] = "Row $row_count: Registration number must be numeric and less than 16 digits.";
                        $error_count++;
                        continue;
                    }
                    if (!preg_match('/^\d{10,}$/', $phone)) {
                        $errors[] = "Row $row_count: Phone number must be numeric and at least 10 digits.";
                        $error_count++;
                        continue;
                    }
                    
                    // Generate student card number
                    $student_card_number = 'SC' . str_pad($row_count, 6, '0', STR_PAD_LEFT) . date('Y');
                    
                    // Check if student already exists
                    try {
                        $check_query = "SELECT id FROM students WHERE registration_number = ? OR email = ?";
                        $check_stmt = $pdo->prepare($check_query);
                        $check_stmt->execute([$registration_number, $email]);
                        
                        if ($check_stmt->rowCount() > 0) {
                            $errors[] = "Row $row_count: Student with registration number '$registration_number' or email '$email' already exists";
                            $error_count++;
                            continue;
                        }
                        
                        // Check if user already exists
                        $check_user_query = "SELECT id FROM users WHERE username = ? OR email = ?";
                        $check_user_stmt = $pdo->prepare($check_user_query);
                        $check_user_stmt->execute([$registration_number, $email]);
                        
                        if ($check_user_stmt->rowCount() > 0) {
                            $errors[] = "Row $row_count: User with username '$registration_number' or email '$email' already exists";
                            $error_count++;
                            continue;
                        }
                        
                    } catch (PDOException $e) {
                        $errors[] = "Row $row_count: Database check error - " . $e->getMessage();
                        $error_count++;
                        continue;
                    }
                    
                    // Insert student into database
                    $insert_query = "INSERT INTO students (
                        registration_number, first_name, last_name, email, phone, 
                        department, program, year_of_study, Student_card_number, gender, 
                        date_of_birth, address, emergency_contact, emergency_phone,serial_number
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
                    
                    $insert_stmt = $pdo->prepare($insert_query);
                    
                    try {
                        $insert_stmt->execute([
                            $registration_number,
                            $first_name,
                            $last_name,
                            $email,
                            $phone,
                            $department,
                            $program,
                            $year_of_study,
                            $student_card_number,
                            $gender,
                            $formatted_dob,
                            $address,
                            $emergency_contact,
                            $emergency_phone,
                            $serial_number
                        ]);
                        
                        // Get the inserted student ID
                        $student_id = $pdo->lastInsertId();
                        
                        // Generate default password (you can customize this logic)
                        $default_password = $registration_number . '123'; // Simple default password
                        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                        
                        // Insert user account for the student
                        $insert_user_query = "INSERT INTO users (
                            username, password, email, first_name, last_name, role_id, 
                            student_id, phone, department, program, year_of_study, 
                            gender, date_of_birth, address, emergency_contact, 
                            emergency_phone, is_active, is_first_login
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $insert_user_stmt = $pdo->prepare($insert_user_query);
                        
                        // Assuming role_id 3 is for students (adjust as needed)
                        $student_role_id = 4;
                        
                        $insert_user_stmt->execute([
                            $registration_number, // username is registration number
                            $hashed_password,
                            $email,
                            $first_name,
                            $last_name,
                            $student_role_id,
                            $student_id,
                            $phone,
                            $department,
                            $program,
                            $year_of_study,
                            $gender,
                            $formatted_dob,
                            $address,
                            $emergency_contact,
                            $emergency_phone,
                            1, // is_active
                            1  // is_first_login
                        ]);
                        
                        $success_count++;
                        
                    } catch (PDOException $e) {
                        $errors[] = "Row $row_count: Database error - " . $e->getMessage();
                        $error_count++;
                        // If user creation fails, we should also remove the student record
                        // This will be handled by the transaction rollback
                    }
                }
                
                fclose($handle);
                
                // Commit or rollback transaction based on success
                if ($error_count == 0) {
                    $pdo->commit();
                } else {
                    $pdo->rollback();
                }
                
                // Set success/error messages
                if ($success_count > 0) {
                    $message = "Successfully imported $success_count student(s) with user accounts created.";
                    $message_type = 'success';
                }
                
                if ($error_count > 0) {
                    $message .= " $error_count error(s) encountered.";
                    $message_type = $success_count > 0 ? 'warning' : 'danger';
                }
                
            } else {
                $message = "Error: Unable to read CSV file.";
                $message_type = 'danger';
            }
        } else {
            $message = "Error: Please upload a valid CSV file.";
            $message_type = 'danger';
        }
    } else {
        $message = "Error: File upload failed.";
        $message_type = 'danger';
    }
}
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Register New Student</div>
            <div class="page-subtitle">Add a student or upload a CSV list</div>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <?php if (!empty($errors)): ?>
                <hr>
                <strong>Error Details:</strong>
                <ul class="mb-0">
                    <?php foreach (array_slice($errors, 0, 10) as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                    <?php if (count($errors) > 10): ?>
                        <li>... and <?php echo count($errors) - 10; ?> more errors</li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- CSV Upload Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-upload"></i> Bulk Student Upload (CSV)</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> This process will create both student records and user accounts. 
                    Default login credentials will be: <strong>Username:</strong> registration_number, <strong>Password:</strong> registration_number + "123"
                </div>
                
                <form method="POST" enctype="multipart/form-data" action="">
                    <div class="form-group">
                        <label for="csv_file">Upload CSV File</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control" required>
                        <small class="form-text text-muted">
                            <strong>Expected columns (in order):</strong><br>
                            registration_number, first_name, last_name, email, phone, department, program, year_of_study, Student_card_number, gender, date_of_birth, address, emergency_contact, emergency_phone, serial_number
                            <br><br>
                            <strong>Notes:</strong>
                            <ul class="mb-0">
                                <li>Date format: YYYY-MM-DD, DD/MM/YYYY, or MM/DD/YYYY</li>
                                <li>Gender: male, female, or other</li>
                                <li>Year of study: numeric value</li>
                                <li>Required fields: registration_number, first_name, last_name, email, department, program, year_of_study</li>
                                <li>User accounts will be created automatically with registration_number as username</li>
                            </ul>
                        </small>
                    </div>
                    <button type="submit" name="upload_csv" class="btn btn-success mt-2">
                        <i class="fas fa-file-import"></i> Import Students & Create User Accounts
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Sample CSV Format -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Sample CSV Format</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>registration_number</th>
                                <th>first_name</th>
                                <th>last_name</th>
                                <th>email</th>
                                <th>phone</th>
                                <th>department</th>
                                <th>program</th>
                                <th>year_of_study</th>
                                <th>Student_card_number</th>
                                <th>gender</th>
                                <th>date_of_birth</th>
                                <th>address</th>
                                <th>emergency_contact</th>
                                <th>emergency_phone</th>
                                <th>serial_number</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>UR/CE/2021/001</td>
                                <td>Eric</td>
                                <td>MUSENGIMANA</td>
                                <td>eric@ur.ac.rw</td>
                                <td>788000001</td>
                                <td>Languages and Humanities</td>
                                <td>Bachelor of Education in English</td>
                                <td>3</td>
                                <td>SC0000012025</td>
                                <td>male</td>
                                <td>2000-01-01</td>
                                <td>Nyagatare</td>
                                <td>Jean</td>
                                <td>0788000001</td>
                                <td>LAPTOP001</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <h5>Default User Account Settings:</h5>
                    <ul>
                        <li><strong>Username:</strong> Same as registration_number</li>
                        <li><strong>Password:</strong> registration_number + "123" (e.g., 2024001123)</li>
                        <li><strong>Role:</strong> Student (role_id: 3)</li>
                        <li><strong>Status:</strong> Active, First login required</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>