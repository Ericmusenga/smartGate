-- Gate Management System Database Schema
-- University of Rwanda College of Education, Rukara Campus
-- Version: 3.1

-- Create database
CREATE DATABASE IF NOT EXISTS gate_management_system;
USE gate_management_system;

-- Drop existing tables if they exist (for clean installation)
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS security_shifts;
DROP TABLE IF EXISTS entry_exit_logs;
DROP TABLE IF EXISTS devices;
DROP TABLE IF EXISTS rfid_cards;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS security_officers;

-- Roles table (for user permissions)
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    role_description TEXT,
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table (for student information)
CREATE TABLE students (
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
    date_of_birth DATE NULL,
    address TEXT NULL,
    emergency_contact VARCHAR(100) NULL,
    emergency_phone VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Security Officers table (for security personnel information)
CREATE TABLE security_officers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    security_code VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- RFID Cards table (for student RFID cards)
CREATE TABLE rfid_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    card_number VARCHAR(50) UNIQUE NOT NULL,
    card_type ENUM('student_id', 'library_card', 'other') DEFAULT 'student_id',
    is_active BOOLEAN DEFAULT TRUE,
    issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Users table (for authentication and system access)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role_id INT NOT NULL,
    student_id INT NULL, -- Foreign key to students table
    security_officer_id INT NULL, -- Foreign key to security_officers table
    phone VARCHAR(20) NULL,
    department VARCHAR(100) NULL,
    program VARCHAR(100) NULL, -- For students
    year_of_study INT NULL, -- For students
    gender ENUM('male', 'female', 'other') NULL,
    date_of_birth DATE NULL,
    address TEXT NULL,
    emergency_contact VARCHAR(100) NULL,
    emergency_phone VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_first_login BOOLEAN DEFAULT TRUE, -- Track if user needs to change password
    last_login TIMESTAMP NULL,
    password_changed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (security_officer_id) REFERENCES security_officers(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Devices table
CREATE TABLE devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    device_type ENUM('laptop', 'tablet', 'phone', 'other') NOT NULL,
    device_name VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100) UNIQUE NOT NULL,
    brand VARCHAR(50) NULL,
    model VARCHAR(50) NULL,
    color VARCHAR(30) NULL,
    description TEXT NULL,
    is_registered BOOLEAN DEFAULT TRUE,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Entry/Exit logs table
CREATE TABLE entry_exit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL, -- Can be null for RFID entries
    device_id INT NULL,
    rfid_card_id INT NULL, -- For RFID-based entries
    entry_time TIMESTAMP NULL,
    exit_time TIMESTAMP NULL,
    gate_number INT NOT NULL,
    security_officer_id INT NULL,
    entry_method ENUM('manual', 'rfid', 'both') DEFAULT 'manual',
    notes TEXT NULL,
    status ENUM('entered', 'exited', 'both') DEFAULT 'entered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (security_officer_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (rfid_card_id) REFERENCES rfid_cards(id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Security officers table (for tracking who is on duty)
CREATE TABLE security_shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    security_officer_id INT NOT NULL,
    gate_number INT NOT NULL,
    shift_start TIMESTAMP NOT NULL,
    shift_end TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (security_officer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Reports table (for storing generated reports)
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    report_name VARCHAR(100) NOT NULL,
    generated_by INT NOT NULL,
    report_data JSON NULL,
    file_path VARCHAR(255) NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Settings table (for system configuration)
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO roles (role_name, role_description, permissions) VALUES
('admin', 'System Administrator - Full access to all features', '{"users": "all", "devices": "all", "logs": "all", "reports": "all", "settings": "all", "students": "all", "security": "all", "rfid": "all"}'),
('security', 'Security Officer - Can manage entry/exit logs and view devices', '{"users": "view", "devices": "view", "logs": "all", "reports": "view", "students": "view", "rfid": "view"}'),
('staff', 'Staff Member - Can register devices and view own logs', '{"users": "own", "devices": "own", "logs": "own", "reports": "own"}'),
('student', 'Student - Can register devices and view own logs', '{"users": "own", "devices": "own", "logs": "own", "reports": "own"}');

-- Insert sample students (for testing)
INSERT INTO students (registration_number, first_name, last_name, email, phone, department, program, year_of_study, gender) VALUES
('2023/001', 'John', 'Doe', 'john.doe@student.ur.ac.rw', '+250788123456', 'Computer Science', 'Bachelor of Computer Science', 2, 'male'),
('2023/002', 'Jane', 'Smith', 'jane.smith@student.ur.ac.rw', '+250788123457', 'Education', 'Bachelor of Education', 1, 'female'),
('2023/003', 'Mike', 'Johnson', 'mike.johnson@student.ur.ac.rw', '+250788123458', 'Mathematics', 'Bachelor of Mathematics', 3, 'male');

-- Insert sample security officers (for testing)
INSERT INTO security_officers (security_code, first_name, last_name, email, phone) VALUES
('SEC001', 'Robert', 'Wilson', 'robert.wilson@security.ur.ac.rw', '+250788123459'),
('SEC002', 'Sarah', 'Brown', 'sarah.brown@security.ur.ac.rw', '+250788123460'),
('SEC003', 'David', 'Miller', 'david.miller@security.ur.ac.rw', '+250788123461');

-- Insert sample RFID cards for students
INSERT INTO rfid_cards (student_id, card_number, card_type) VALUES
(1, 'RFID2023001', 'student_id'),
(2, 'RFID2023002', 'student_id'),
(3, 'RFID2023003', 'student_id');

-- Insert default admin user (password: admin123)
-- Password is hashed using bcrypt with cost 12
INSERT INTO users (username, password, email, first_name, last_name, role_id, is_first_login) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ur.ac.rw', 'System', 'Administrator', 1, FALSE);

-- Insert sample users for students (username = registration_number, password = registration_number)
INSERT INTO users (username, password, email, first_name, last_name, role_id, student_id, department, program, year_of_study, gender, is_first_login) VALUES
('2023/001', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john.doe@student.ur.ac.rw', 'John', 'Doe', 4, 1, 'Computer Science', 'Bachelor of Computer Science', 2, 'male', TRUE),
('2023/002', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane.smith@student.ur.ac.rw', 'Jane', 'Smith', 4, 2, 'Education', 'Bachelor of Education', 1, 'female', TRUE),
('2023/003', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mike.johnson@student.ur.ac.rw', 'Mike', 'Johnson', 4, 3, 'Mathematics', 'Bachelor of Mathematics', 3, 'male', TRUE);

-- Insert sample users for security officers (username = security_code, password = security_code)
INSERT INTO users (username, password, email, first_name, last_name, role_id, security_officer_id, is_first_login) VALUES
('SEC001', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'robert.wilson@security.ur.ac.rw', 'Robert', 'Wilson', 2, 1, TRUE),
('SEC002', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sarah.brown@security.ur.ac.rw', 'Sarah', 'Brown', 2, 2, TRUE),
('SEC003', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'david.miller@security.ur.ac.rw', 'David', 'Miller', 2, 3, TRUE);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Gate Management System - UR Rukara Campus', 'Website name'),
('max_devices_per_user', '3', 'Maximum number of devices a user can register'),
('session_timeout', '3600', 'Session timeout in seconds'),
('enable_notifications', 'true', 'Enable email notifications'),
('maintenance_mode', 'false', 'Enable maintenance mode'),
('gate_count', '2', 'Number of gates in the system'),
('auto_logout_time', '1800', 'Auto logout time in seconds'),
('enable_device_photos', 'true', 'Enable device photo uploads'),
('max_photo_size', '5242880', 'Maximum photo size in bytes (5MB)'),
('force_password_change', 'true', 'Force password change on first login'),
('password_expiry_days', '90', 'Password expiry in days'),
('enable_rfid', 'true', 'Enable RFID card functionality'),
('rfid_timeout', '30', 'RFID card timeout in seconds');

-- Create indexes for better performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role_id ON users(role_id);
CREATE INDEX idx_users_student_id ON users(student_id);
CREATE INDEX idx_users_security_officer_id ON users(security_officer_id);
CREATE INDEX idx_users_is_first_login ON users(is_first_login);
CREATE INDEX idx_students_registration_number ON students(registration_number);
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_security_officers_security_code ON security_officers(security_code);
CREATE INDEX idx_security_officers_email ON security_officers(email);
CREATE INDEX idx_rfid_cards_card_number ON rfid_cards(card_number);
CREATE INDEX idx_rfid_cards_student_id ON rfid_cards(student_id);
CREATE INDEX idx_rfid_cards_is_active ON rfid_cards(is_active);
CREATE INDEX idx_devices_user_id ON devices(user_id);
CREATE INDEX idx_devices_serial_number ON devices(serial_number);
CREATE INDEX idx_devices_device_type ON devices(device_type);
CREATE INDEX idx_entry_exit_logs_user_id ON entry_exit_logs(user_id);
CREATE INDEX idx_entry_exit_logs_device_id ON entry_exit_logs(device_id);
CREATE INDEX idx_entry_exit_logs_rfid_card_id ON entry_exit_logs(rfid_card_id);
CREATE INDEX idx_entry_exit_logs_entry_time ON entry_exit_logs(entry_time);
CREATE INDEX idx_entry_exit_logs_exit_time ON entry_exit_logs(exit_time);
CREATE INDEX idx_entry_exit_logs_gate_number ON entry_exit_logs(gate_number);
CREATE INDEX idx_entry_exit_logs_entry_method ON entry_exit_logs(entry_method);
CREATE INDEX idx_security_shifts_officer_id ON security_shifts(security_officer_id);
CREATE INDEX idx_security_shifts_active ON security_shifts(is_active);
CREATE INDEX idx_reports_generated_by ON reports(generated_by);
CREATE INDEX idx_reports_report_type ON reports(report_type);
CREATE INDEX idx_roles_role_name ON roles(role_name);

-- Create views for common queries
CREATE VIEW user_summary AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.first_name,
    u.last_name,
    r.role_name,
    u.department,
    u.program,
    u.year_of_study,
    u.is_active,
    u.is_first_login,
    u.last_login,
    COUNT(d.id) as device_count,
    u.created_at,
    CASE 
        WHEN u.student_id IS NOT NULL THEN 'student'
        WHEN u.security_officer_id IS NOT NULL THEN 'security'
        ELSE 'admin'
    END as user_type
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN devices d ON u.id = d.user_id AND d.is_registered = TRUE
GROUP BY u.id;

CREATE VIEW student_summary AS
SELECT 
    s.id,
    s.registration_number,
    s.first_name,
    s.last_name,
    s.email,
    s.phone,
    s.department,
    s.program,
    s.year_of_study,
    s.gender,
    s.is_active,
    u.is_first_login,
    u.last_login,
    COUNT(d.id) as device_count,
    COUNT(rf.id) as rfid_card_count
FROM students s
LEFT JOIN users u ON s.id = u.student_id
LEFT JOIN devices d ON u.id = d.user_id AND d.is_registered = TRUE
LEFT JOIN rfid_cards rf ON s.id = rf.student_id AND rf.is_active = TRUE
GROUP BY s.id;

CREATE VIEW security_summary AS
SELECT 
    so.id,
    so.security_code,
    so.first_name,
    so.last_name,
    so.email,
    so.phone,
    so.is_active,
    u.is_first_login,
    u.last_login
FROM security_officers so
LEFT JOIN users u ON so.id = u.security_officer_id;

CREATE VIEW rfid_summary AS
SELECT 
    rf.id,
    rf.card_number,
    rf.card_type,
    rf.is_active,
    rf.issued_date,
    rf.expiry_date,
    s.registration_number,
    s.first_name,
    s.last_name,
    s.email,
    s.department,
    s.program
FROM rfid_cards rf
JOIN students s ON rf.student_id = s.id;

CREATE VIEW device_summary AS
SELECT 
    d.id,
    d.device_name,
    d.device_type,
    d.serial_number,
    d.brand,
    d.model,
    d.color,
    u.first_name,
    u.last_name,
    u.username,
    r.role_name,
    d.registration_date,
    d.is_registered
FROM devices d
JOIN users u ON d.user_id = u.id
JOIN roles r ON u.role_id = r.id;

CREATE VIEW entry_exit_summary AS
SELECT 
    eel.id,
    u.first_name,
    u.last_name,
    u.username,
    d.device_name,
    d.device_type,
    rf.card_number as rfid_card,
    eel.entry_time,
    eel.exit_time,
    eel.gate_number,
    eel.entry_method,
    eel.status,
    eel.created_at
FROM entry_exit_logs eel
LEFT JOIN users u ON eel.user_id = u.id
LEFT JOIN devices d ON eel.device_id = d.id
LEFT JOIN rfid_cards rf ON eel.rfid_card_id = rf.id;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE GetUserDevices(IN user_id_param INT)
BEGIN
    SELECT 
        d.*,
        u.first_name,
        u.last_name,
        u.username
    FROM devices d
    JOIN users u ON d.user_id = u.id
    WHERE d.user_id = user_id_param AND d.is_registered = TRUE
    ORDER BY d.registration_date DESC;
END //

CREATE PROCEDURE GetUserLogs(IN user_id_param INT, IN days_back INT)
BEGIN
    SELECT 
        eel.*,
        d.device_name,
        d.device_type,
        d.serial_number,
        rf.card_number as rfid_card
    FROM entry_exit_logs eel
    LEFT JOIN devices d ON eel.device_id = d.id
    LEFT JOIN rfid_cards rf ON eel.rfid_card_id = rf.id
    WHERE eel.user_id = user_id_param 
    AND eel.created_at >= DATE_SUB(NOW(), INTERVAL days_back DAY)
    ORDER BY eel.created_at DESC;
END //

CREATE PROCEDURE GetDailyStats(IN date_param DATE)
BEGIN
    SELECT 
        COUNT(DISTINCT eel.user_id) as unique_users,
        COUNT(eel.id) as total_entries,
        COUNT(CASE WHEN eel.status = 'entered' THEN 1 END) as entries,
        COUNT(CASE WHEN eel.status = 'exited' THEN 1 END) as exits,
        COUNT(CASE WHEN eel.status = 'both' THEN 1 END) as both_entries,
        COUNT(CASE WHEN eel.entry_method = 'rfid' THEN 1 END) as rfid_entries,
        COUNT(CASE WHEN eel.entry_method = 'manual' THEN 1 END) as manual_entries
    FROM entry_exit_logs eel
    WHERE DATE(eel.created_at) = date_param;
END //

CREATE PROCEDURE CreateStudentUser(IN registration_number_param VARCHAR(20))
BEGIN
    DECLARE student_id_var INT;
    DECLARE student_email VARCHAR(100);
    DECLARE student_first_name VARCHAR(50);
    DECLARE student_last_name VARCHAR(50);
    DECLARE student_department VARCHAR(100);
    DECLARE student_program VARCHAR(100);
    DECLARE student_year INT;
    DECLARE student_gender VARCHAR(10);
    
    -- Get student information
    SELECT id, email, first_name, last_name, department, program, year_of_study, gender
    INTO student_id_var, student_email, student_first_name, student_last_name, student_department, student_program, student_year, student_gender
    FROM students 
    WHERE registration_number = registration_number_param;
    
    -- Create user account if student exists and user doesn't exist
    IF student_id_var IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE student_id = student_id_var) THEN
        INSERT INTO users (username, password, email, first_name, last_name, role_id, student_id, department, program, year_of_study, gender, is_first_login)
        VALUES (registration_number_param, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', student_email, student_first_name, student_last_name, 4, student_id_var, student_department, student_program, student_year, student_gender, TRUE);
        
        SELECT 'User created successfully' as message;
    ELSE
        SELECT 'Student not found or user already exists' as message;
    END IF;
END //

CREATE PROCEDURE CreateSecurityUser(IN security_code_param VARCHAR(20))
BEGIN
    DECLARE security_id_var INT;
    DECLARE security_email VARCHAR(100);
    DECLARE security_first_name VARCHAR(50);
    DECLARE security_last_name VARCHAR(50);
    
    -- Get security officer information
    SELECT id, email, first_name, last_name 
    INTO security_id_var, security_email, security_first_name, security_last_name
    FROM security_officers 
    WHERE security_code = security_code_param;
    
    -- Create user account if security officer exists and user doesn't exist
    IF security_id_var IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE security_officer_id = security_id_var) THEN
        INSERT INTO users (username, password, email, first_name, last_name, role_id, security_officer_id, is_first_login)
        VALUES (security_code_param, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', security_email, security_first_name, security_last_name, 2, security_id_var, TRUE);
        
        SELECT 'User created successfully' as message;
    ELSE
        SELECT 'Security officer not found or user already exists' as message;
    END IF;
END //

CREATE PROCEDURE CreateRFIDEntry(IN card_number_param VARCHAR(50), IN gate_number_param INT)
BEGIN
    DECLARE rfid_id_var INT;
    DECLARE student_id_var INT;
    DECLARE user_id_var INT;
    
    -- Get RFID card information
    SELECT rf.id, rf.student_id, u.id
    INTO rfid_id_var, student_id_var, user_id_var
    FROM rfid_cards rf
    LEFT JOIN users u ON rf.student_id = u.student_id
    WHERE rf.card_number = card_number_param AND rf.is_active = TRUE;
    
    -- Create entry log if RFID card exists
    IF rfid_id_var IS NOT NULL THEN
        INSERT INTO entry_exit_logs (user_id, rfid_card_id, entry_time, gate_number, entry_method, status)
        VALUES (user_id_var, rfid_id_var, NOW(), gate_number_param, 'rfid', 'entered');
        
        SELECT 'Entry logged successfully' as message;
    ELSE
        SELECT 'RFID card not found or inactive' as message;
    END IF;
END //

DELIMITER ;

-- Grant permissions (adjust as needed for your MySQL setup)
-- GRANT ALL PRIVILEGES ON gate_management_system.* TO 'your_user'@'localhost';
-- FLUSH PRIVILEGES; 