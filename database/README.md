# Gate Management System - Database Documentation

## Overview
This document provides comprehensive information about the database structure for the Gate Management System at the University of Rwanda College of Education, Rukara Campus.

## Database Information
- **Database Name**: `gate_management_system`
- **Version**: 2.0
- **Engine**: MySQL 8.0+
- **Character Set**: utf8mb4
- **Collation**: utf8mb4_unicode_ci

## Table Structure

### 1. Roles Table
Stores user roles and permissions for the system.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| role_name | VARCHAR(50) | Unique role name (admin, security, staff, student) |
| role_description | TEXT | Description of the role |
| permissions | JSON | JSON object containing role permissions |
| is_active | BOOLEAN | Whether the role is active |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

**Default Roles:**
- `admin`: Full system access
- `security`: Entry/exit log management
- `staff`: Own device and log management
- `student`: Own device and log management

### 2. Users Table
Stores all user information including students, staff, security officers, and administrators.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| username | VARCHAR(50) | Unique username |
| password | VARCHAR(255) | Bcrypt hashed password |
| email | VARCHAR(100) | Unique email address |
| first_name | VARCHAR(50) | User's first name |
| last_name | VARCHAR(50) | User's last name |
| role_id | INT | Foreign key to roles table |
| student_id | VARCHAR(20) | Student ID (for students) |
| staff_id | VARCHAR(20) | Staff ID (for staff) |
| phone | VARCHAR(20) | Phone number |
| department | VARCHAR(100) | Department name |
| course | VARCHAR(100) | Course name (for students) |
| year_of_study | INT | Year of study (for students) |
| is_active | BOOLEAN | Whether user account is active |
| last_login | TIMESTAMP | Last login timestamp |
| created_at | TIMESTAMP | Account creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

**Default Admin User:**
- Username: `admin`
- Password: `admin123`
- Email: `admin@ur.ac.rw`

### 3. Devices Table
Stores information about devices registered by users.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users table |
| device_type | ENUM | Device type (laptop, tablet, phone, other) |
| device_name | VARCHAR(100) | Name given to the device |
| serial_number | VARCHAR(100) | Unique device serial number |
| brand | VARCHAR(50) | Device brand |
| model | VARCHAR(50) | Device model |
| color | VARCHAR(30) | Device color |
| description | TEXT | Additional device description |
| is_registered | BOOLEAN | Whether device is currently registered |
| registration_date | TIMESTAMP | Device registration date |
| updated_at | TIMESTAMP | Last update timestamp |

### 4. Entry/Exit Logs Table
Records all entry and exit activities at the gates.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users table |
| device_id | INT | Foreign key to devices table (nullable) |
| entry_time | TIMESTAMP | Entry timestamp |
| exit_time | TIMESTAMP | Exit timestamp |
| gate_number | INT | Gate number where entry/exit occurred |
| security_officer_id | INT | Foreign key to users table (security officer) |
| notes | TEXT | Additional notes |
| status | ENUM | Status (entered, exited, both) |
| created_at | TIMESTAMP | Log creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### 5. Security Shifts Table
Tracks security officer shifts and assignments.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| security_officer_id | INT | Foreign key to users table |
| gate_number | INT | Gate number assigned |
| shift_start | TIMESTAMP | Shift start time |
| shift_end | TIMESTAMP | Shift end time |
| is_active | BOOLEAN | Whether shift is currently active |
| created_at | TIMESTAMP | Shift creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### 6. Reports Table
Stores generated reports and their metadata.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| report_type | ENUM | Report type (daily, weekly, monthly, custom) |
| report_name | VARCHAR(100) | Report name |
| generated_by | INT | Foreign key to users table |
| report_data | JSON | Report data in JSON format |
| file_path | VARCHAR(255) | Path to report file (if saved) |
| generated_at | TIMESTAMP | Report generation timestamp |

### 7. Settings Table
Stores system configuration settings.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| setting_key | VARCHAR(50) | Unique setting key |
| setting_value | TEXT | Setting value |
| description | TEXT | Setting description |
| updated_at | TIMESTAMP | Last update timestamp |

## Database Relationships

### Foreign Key Constraints
1. **users.role_id** → **roles.id** (RESTRICT on DELETE, CASCADE on UPDATE)
2. **devices.user_id** → **users.id** (CASCADE on DELETE/UPDATE)
3. **entry_exit_logs.user_id** → **users.id** (CASCADE on DELETE/UPDATE)
4. **entry_exit_logs.device_id** → **devices.id** (SET NULL on DELETE, CASCADE on UPDATE)
5. **entry_exit_logs.security_officer_id** → **users.id** (SET NULL on DELETE, CASCADE on UPDATE)
6. **security_shifts.security_officer_id** → **users.id** (CASCADE on DELETE/UPDATE)
7. **reports.generated_by** → **users.id** (CASCADE on DELETE/UPDATE)

## Views

### 1. user_summary
Provides a summary of users with their role and device count.

### 2. device_summary
Provides device information with owner details.

### 3. entry_exit_summary
Provides entry/exit log information with user and device details.

## Stored Procedures

### 1. GetUserDevices(user_id_param INT)
Retrieves all devices for a specific user.

### 2. GetUserLogs(user_id_param INT, days_back INT)
Retrieves entry/exit logs for a specific user within a specified number of days.

### 3. GetDailyStats(date_param DATE)
Retrieves daily statistics for entry/exit activities.

## Useful SQL Queries

### User Management

```sql
-- Get all users with their roles
SELECT u.*, r.role_name, r.role_description
FROM users u
JOIN roles r ON u.role_id = r.id
ORDER BY u.created_at DESC;

-- Get users by role
SELECT u.*, r.role_name
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE r.role_name = 'student';

-- Get active users
SELECT * FROM users WHERE is_active = TRUE;

-- Get users who haven't logged in recently
SELECT * FROM users 
WHERE last_login < DATE_SUB(NOW(), INTERVAL 30 DAY)
AND is_active = TRUE;
```

### Device Management

```sql
-- Get all registered devices
SELECT d.*, u.first_name, u.last_name, u.username
FROM devices d
JOIN users u ON d.user_id = u.id
WHERE d.is_registered = TRUE;

-- Get devices by type
SELECT d.*, u.first_name, u.last_name
FROM devices d
JOIN users u ON d.user_id = u.id
WHERE d.device_type = 'laptop';

-- Get devices registered today
SELECT d.*, u.first_name, u.last_name
FROM devices d
JOIN users u ON d.user_id = u.id
WHERE DATE(d.registration_date) = CURDATE();

-- Get users with most devices
SELECT u.first_name, u.last_name, COUNT(d.id) as device_count
FROM users u
LEFT JOIN devices d ON u.id = d.user_id AND d.is_registered = TRUE
GROUP BY u.id
ORDER BY device_count DESC;
```

### Entry/Exit Logs

```sql
-- Get today's entry/exit logs
SELECT eel.*, u.first_name, u.last_name, d.device_name
FROM entry_exit_logs eel
JOIN users u ON eel.user_id = u.id
LEFT JOIN devices d ON eel.device_id = d.id
WHERE DATE(eel.created_at) = CURDATE()
ORDER BY eel.created_at DESC;

-- Get logs by gate
SELECT eel.*, u.first_name, u.last_name
FROM entry_exit_logs eel
JOIN users u ON eel.user_id = u.id
WHERE eel.gate_number = 1
ORDER BY eel.created_at DESC;

-- Get users currently on campus (entered but not exited)
SELECT u.first_name, u.last_name, eel.entry_time
FROM entry_exit_logs eel
JOIN users u ON eel.user_id = u.id
WHERE eel.entry_time IS NOT NULL 
AND eel.exit_time IS NULL
ORDER BY eel.entry_time DESC;

-- Get daily entry/exit statistics
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_logs,
    COUNT(CASE WHEN status = 'entered' THEN 1 END) as entries,
    COUNT(CASE WHEN status = 'exited' THEN 1 END) as exits
FROM entry_exit_logs
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### Security Management

```sql
-- Get active security shifts
SELECT ss.*, u.first_name, u.last_name
FROM security_shifts ss
JOIN users u ON ss.security_officer_id = u.id
WHERE ss.is_active = TRUE;

-- Get security officers on duty
SELECT u.first_name, u.last_name, ss.gate_number, ss.shift_start
FROM security_shifts ss
JOIN users u ON ss.security_officer_id = u.id
WHERE ss.is_active = TRUE
AND NOW() BETWEEN ss.shift_start AND COALESCE(ss.shift_end, NOW());
```

### Reports and Analytics

```sql
-- Get system statistics
SELECT 
    (SELECT COUNT(*) FROM users WHERE is_active = TRUE) as active_users,
    (SELECT COUNT(*) FROM devices WHERE is_registered = TRUE) as registered_devices,
    (SELECT COUNT(*) FROM entry_exit_logs WHERE DATE(created_at) = CURDATE()) as today_logs,
    (SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'student')) as student_count;

-- Get monthly entry/exit trends
SELECT 
    YEAR(created_at) as year,
    MONTH(created_at) as month,
    COUNT(*) as total_logs,
    COUNT(CASE WHEN status = 'entered' THEN 1 END) as entries,
    COUNT(CASE WHEN status = 'exited' THEN 1 END) as exits
FROM entry_exit_logs
GROUP BY YEAR(created_at), MONTH(created_at)
ORDER BY year DESC, month DESC;

-- Get device registration trends
SELECT 
    DATE(registration_date) as date,
    COUNT(*) as registrations,
    device_type
FROM devices
GROUP BY DATE(registration_date), device_type
ORDER BY date DESC;
```

### Settings Management

```sql
-- Get all settings
SELECT * FROM settings ORDER BY setting_key;

-- Update a setting
UPDATE settings 
SET setting_value = 'new_value', updated_at = NOW()
WHERE setting_key = 'setting_name';

-- Get specific setting
SELECT setting_value FROM settings WHERE setting_key = 'site_name';
```

## Installation Instructions

1. **Create Database:**
   ```sql
   CREATE DATABASE gate_management_system;
   USE gate_management_system;
   ```

2. **Run Schema:**
   ```bash
   mysql -u your_username -p gate_management_system < database/schema.sql
   ```

3. **Verify Installation:**
   ```sql
   SHOW TABLES;
   SELECT * FROM roles;
   SELECT * FROM users WHERE username = 'admin';
   ```

## Backup and Maintenance

### Backup Database
```bash
mysqldump -u your_username -p gate_management_system > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore Database
```bash
mysql -u your_username -p gate_management_system < backup_file.sql
```

### Optimize Tables
```sql
OPTIMIZE TABLE users, devices, entry_exit_logs, security_shifts, reports;
```

## Security Considerations

1. **Password Hashing**: All passwords are hashed using bcrypt with cost 12
2. **SQL Injection**: Use prepared statements in application code
3. **Access Control**: Implement role-based access control using the roles table
4. **Audit Trail**: All tables include created_at and updated_at timestamps
5. **Data Integrity**: Foreign key constraints ensure data consistency

## Performance Optimization

1. **Indexes**: Strategic indexes on frequently queried columns
2. **Views**: Pre-computed views for common queries
3. **Stored Procedures**: Optimized procedures for complex operations
4. **Partitioning**: Consider partitioning entry_exit_logs by date for large datasets

## Troubleshooting

### Common Issues

1. **Foreign Key Constraint Errors**: Ensure referenced records exist before inserting
2. **Duplicate Entry Errors**: Check unique constraints on username, email, serial_number
3. **Performance Issues**: Monitor slow query log and optimize indexes
4. **Connection Issues**: Verify database credentials and connection settings

### Useful Diagnostic Queries

```sql
-- Check for orphaned records
SELECT d.id, d.user_id FROM devices d 
LEFT JOIN users u ON d.user_id = u.id 
WHERE u.id IS NULL;

-- Check for duplicate usernames
SELECT username, COUNT(*) as count 
FROM users 
GROUP BY username 
HAVING count > 1;

-- Check for devices without owners
SELECT d.* FROM devices d 
LEFT JOIN users u ON d.user_id = u.id 
WHERE u.id IS NULL;
```

## Support

For database-related issues or questions, refer to:
- MySQL Documentation: https://dev.mysql.com/doc/
- Database Schema Version: 2.0
- Last Updated: [Current Date] 