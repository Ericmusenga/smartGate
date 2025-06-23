# Login and Logout System Documentation

## Overview
The Gate Management System includes a comprehensive login and logout system with role-based access control and first-time password change functionality.

## Features

### 1. Multi-Role Authentication
- **Students**: Login using registration number (e.g., "2023/001")
- **Security Officers**: Login using security code (e.g., "SEC001")
- **Administrators**: Login using admin credentials

### 2. First-Time Login Password Change
- All users must change their password on first login
- System enforces password requirements (minimum 8 characters)
- Users are redirected to change password page automatically

### 3. Session Management
- Secure session handling with configurable lifetime
- Automatic logout on session expiration
- CSRF protection for forms

### 4. Role-Based Access Control
- Different dashboards for different user types
- Unauthorized access prevention
- Proper redirects based on user role

## Files Created

### Core Files
- `login.php` - Main login page with authentication logic
- `logout.php` - Logout functionality with session cleanup
- `change_password.php` - First-time password change page
- `index.php` - Entry point with role-based redirects
- `unauthorized.php` - Access denied page

### Updated Files
- `includes/header.php` - Added user info display and logout link
- `pages/dashboard_*.php` - Added authentication checks
- `config/config.php` - Already had necessary helper functions

## Usage

### For Students
1. Navigate to the login page
2. Enter your registration number as username
3. Enter your default password (same as registration number)
4. Change password on first login
5. Access student dashboard

### For Security Officers
1. Navigate to the login page
2. Enter your security code as username
3. Enter your default password (same as security code)
4. Change password on first login
5. Access security dashboard

### For Administrators
1. Navigate to the login page
2. Enter admin credentials
3. Change password on first login
4. Access admin dashboard

## Demo Credentials

### Admin User
- Username: `admin`
- Password: `admin123`

### Student User
- Username: `2023/001`
- Password: `2023/001`

### Security Officer
- Username: `SEC001`
- Password: `SEC001`

## Security Features

### Password Security
- Passwords are hashed using PHP's `password_hash()` function
- Minimum 8 character requirement for new passwords
- Password change enforced on first login

### Session Security
- Secure session configuration
- Session timeout handling
- Proper session cleanup on logout

### Access Control
- Role-based page access
- Unauthorized access prevention
- Proper redirects for unauthenticated users

## Database Integration

The login system integrates with the existing database schema:

- `users` table stores user credentials and role information
- `students` table linked to users for student-specific data
- `security_officers` table linked to users for security-specific data
- `roles` table defines available user roles

## Error Handling

- Invalid credentials display appropriate error messages
- Database errors are logged and user-friendly messages shown
- Session errors redirect to login page
- Unauthorized access shows dedicated error page

## Styling

The login system uses:
- Modern, responsive design
- Gradient backgrounds and glass-morphism effects
- FontAwesome icons for visual appeal
- Consistent styling with the main application

## Future Enhancements

Potential improvements:
- Password reset functionality
- Remember me feature
- Two-factor authentication
- Account lockout after failed attempts
- Email verification for new accounts 