<?php
/**
 * Main Configuration File
 * Gate Management System - UR Rukara Campus
 */

// Application settings
define('APP_NAME', 'Gate Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Capstone_project');
define('APP_ROOT', dirname(__DIR__));

// Session settings
define('SESSION_NAME', 'gate_management_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// Security settings
define('HASH_COST', 12);
define('CSRF_TOKEN_NAME', 'csrf_token');

// File upload settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Email settings (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@ur.ac.rw');
define('FROM_NAME', 'UR Rukara Campus');

// Pagination settings
define('ITEMS_PER_PAGE', 20);

// Timezone
date_default_timezone_set('Africa/Kigali');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Include database configuration
require_once APP_ROOT . '/config/database.php';

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_type() {
    return $_SESSION['user_type'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        redirect(APP_URL . '/login.php');
    }
}

function require_admin() {
    require_login();
    if (get_user_type() !== 'admin') {
        redirect(APP_URL . '/unauthorized.php');
    }
}
?> 