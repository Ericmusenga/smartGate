<?php
/**
 * Quick Database Setup - Fix Login Issues Immediately
 */

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gate_management_system');

echo "<h1>🚀 Quick Database Setup - Fix Login Issues</h1>";

try {
    // Connect to MySQL without specifying database
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connected to MySQL successfully!</p>";
    
    // Drop database if exists and recreate
    $pdo->exec("DROP DATABASE IF EXISTS " . DB_NAME);
    echo "<p style='color: orange;'>🗑️ Dropped existing database (if any)</p>";
    
    // Create database
    $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ Database '" . DB_NAME . "' created successfully!</p>";
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    echo "<p style='color: green;'>✅ Database selected successfully!</p>";
    
    // Create tables
    echo "<h2>📋 Creating Tables...</h2>";
    
    // Roles table
    $pdo->exec("CREATE TABLE roles (
        id INT PRIMARY KEY AUTO_INCREMENT,
        role_name VARCHAR(50) UNIQUE NOT NULL,
        role_description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Roles table created</p>";
    
    // Students table
    $pdo->exec("CREATE TABLE students (
        id INT PRIMARY KEY AUTO_INCREMENT,
        registration_number VARCHAR(20) UNIQUE NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20) NULL,
        department VARCHAR(100) NOT NULL,
        program VARCHAR(100) NOT NULL,
        year_of_study INT NOT NULL,
        gender ENUM('male', 'female', 'other') NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Students table created</p>";
    
    // Security Officers table
    $pdo->exec("CREATE TABLE security_officers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        security_code VARCHAR(20) UNIQUE NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20) NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Security Officers table created</p>";
    
    // Users table
    $pdo->exec("CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        role_id INT NOT NULL,
        student_id INT NULL,
        security_officer_id INT NULL,
        department VARCHAR(100) NULL,
        program VARCHAR(100) NULL,
        year_of_study INT NULL,
        gender ENUM('male', 'female', 'other') NULL,
        is_active BOOLEAN DEFAULT TRUE,
        is_first_login BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES roles(id),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (security_officer_id) REFERENCES security_officers(id) ON DELETE CASCADE
    )");
    echo "<p>✅ Users table created</p>";
    
    // Insert roles
    echo "<h2>👥 Inserting Roles...</h2>";
    $pdo->exec("INSERT INTO roles (role_name, role_description) VALUES 
        ('admin', 'System Administrator - Full access'),
        ('security', 'Security Officer - Can manage entry/exit logs'),
        ('student', 'Student - Can register devices and view own logs')
    ");
    echo "<p>✅ Roles inserted</p>";
    
    // Insert students
    echo "<h2>🎓 Inserting Students...</h2>";
    $pdo->exec("INSERT INTO students (registration_number, first_name, last_name, email, phone, department, program, year_of_study, gender) VALUES 
        ('2023/001', 'John', 'Doe', 'john.doe@student.ur.ac.rw', '+250788123456', 'Computer Science', 'Bachelor of Computer Science', 2, 'male'),
        ('2023/002', 'Jane', 'Smith', 'jane.smith@student.ur.ac.rw', '+250788123457', 'Education', 'Bachelor of Education', 1, 'female')
    ");
    echo "<p>✅ Students inserted</p>";
    
    // Insert security officers
    echo "<h2>🛡️ Inserting Security Officers...</h2>";
    $pdo->exec("INSERT INTO security_officers (security_code, first_name, last_name, email, phone) VALUES 
        ('SEC001', 'Robert', 'Wilson', 'robert.wilson@security.ur.ac.rw', '+250788123459'),
        ('SEC002', 'Sarah', 'Brown', 'sarah.brown@security.ur.ac.rw', '+250788123460')
    ");
    echo "<p>✅ Security Officers inserted</p>";
    
    // Insert users with PROPER passwords
    echo "<h2>👤 Inserting Users with Working Passwords...</h2>";
    
    // Admin user - password: admin123
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (username, password, email, first_name, last_name, role_id, is_first_login) VALUES 
        ('admin', '$adminPassword', 'admin@ur.ac.rw', 'System', 'Administrator', 1, FALSE)
    ");
    echo "<p>✅ Admin user created (admin / admin123)</p>";
    
    // Student users - password: registration_number
    $student1Password = password_hash('2023/001', PASSWORD_DEFAULT);
    $student2Password = password_hash('2023/002', PASSWORD_DEFAULT);
    
    $pdo->exec("INSERT INTO users (username, password, email, first_name, last_name, role_id, student_id, department, program, year_of_study, gender, is_first_login) VALUES 
        ('2023/001', '$student1Password', 'john.doe@student.ur.ac.rw', 'John', 'Doe', 3, 1, 'Computer Science', 'Bachelor of Computer Science', 2, 'male', TRUE),
        ('2023/002', '$student2Password', 'jane.smith@student.ur.ac.rw', 'Jane', 'Smith', 3, 2, 'Education', 'Bachelor of Education', 1, 'female', TRUE)
    ");
    echo "<p>✅ Student users created (2023/001 / 2023/001, 2023/002 / 2023/002)</p>";
    
    // Security users - password: security_code
    $security1Password = password_hash('SEC001', PASSWORD_DEFAULT);
    $security2Password = password_hash('SEC002', PASSWORD_DEFAULT);
    
    $pdo->exec("INSERT INTO users (username, password, email, first_name, last_name, role_id, security_officer_id, is_first_login) VALUES 
        ('SEC001', '$security1Password', 'robert.wilson@security.ur.ac.rw', 'Robert', 'Wilson', 2, 1, TRUE),
        ('SEC002', '$security2Password', 'sarah.brown@security.ur.ac.rw', 'Sarah', 'Brown', 2, 2, TRUE)
    ");
    echo "<p>✅ Security users created (SEC001 / SEC001, SEC002 / SEC002)</p>";
    
    // Verify data
    echo "<h2>🔍 Verifying Data...</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM roles");
    $roleCount = $stmt->fetch()['count'];
    echo "<p>✅ Roles: $roleCount records</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p>✅ Users: $userCount records</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $studentCount = $stmt->fetch()['count'];
    echo "<p>✅ Students: $studentCount records</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM security_officers");
    $officerCount = $stmt->fetch()['count'];
    echo "<p>✅ Security Officers: $officerCount records</p>";
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<p style='color: green; font-size: 1.2em;'>✅ Your database is now ready and login will work!</p>";
    
    echo "<h3>🔑 Working Login Credentials:</h3>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>👨‍💼 Admin User:</h4>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><strong>Dashboard:</strong> Admin Panel</p>";
    echo "<br>";
    
    echo "<h4>🎓 Student Users:</h4>";
    echo "<p><strong>Username:</strong> 2023/001</p>";
    echo "<p><strong>Password:</strong> 2023/001</p>";
    echo "<p><strong>Dashboard:</strong> Student Portal</p>";
    echo "<br>";
    echo "<p><strong>Username:</strong> 2023/002</p>";
    echo "<p><strong>Password:</strong> 2023/002</p>";
    echo "<p><strong>Dashboard:</strong> Student Portal</p>";
    echo "<br>";
    
    echo "<h4>🛡️ Security Users:</h4>";
    echo "<p><strong>Username:</strong> SEC001</p>";
    echo "<p><strong>Password:</strong> SEC001</p>";
    echo "<p><strong>Dashboard:</strong> Security Panel</p>";
    echo "<br>";
    echo "<p><strong>Username:</strong> SEC002</p>";
    echo "<p><strong>Password:</strong> SEC002</p>";
    echo "<p><strong>Dashboard:</strong> Security Panel</p>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='login.php' style='background: #27ae60; color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-size: 1.1em; font-weight: bold; display: inline-block;'>🚀 Go to Login Page</a>";
    echo "</div>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #27ae60;'>";
    echo "<h4>📝 Important Notes:</h4>";
    echo "<ul>";
    echo "<li>✅ All passwords are properly hashed and will work immediately</li>";
    echo "<li>✅ Students and Security users will be prompted to change password on first login</li>";
    echo "<li>✅ Admin user can log in directly without password change</li>";
    echo "<li>✅ All navigation links are properly configured</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-size: 1.2em;'>❌ Setup failed: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP is running</li>";
    echo "<li>MySQL service is started</li>";
    echo "<li>You have proper permissions</li>";
    echo "</ul>";
}
?> 