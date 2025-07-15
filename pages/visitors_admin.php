<?php
require_once '../config/config.php';
if (!is_logged_in()) { redirect('../login.php'); }
if (get_user_type() !== 'admin') { redirect('../unauthorized.php'); }

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

$stmt = $pdo->query("SELECT * FROM vistor ORDER BY registration_date DESC");
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Visitors - Admin</title>
    <link rel="stylesheet" href="/Capstone_project/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .main-content { margin-left: 250px; margin-top: 70px; padding: 2rem 1rem 4rem 1rem; background: #f5f7fa; }
        .visitors-table { width: 100%; border-collapse: collapse; background: #fff; }
        .visitors-table th, .visitors-table td { padding: 12px; border-bottom: 1px solid #e9ecef; text-align: left; }
        .visitors-table th { background: #f8f9fa; font-weight: 600; }
        .visitors-table tr:hover { background: #f1f3f6; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 500; }
        .status-inside { background: #d4edda; color: #155724; }
        .status-exited { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<header class="header">
    <div style="font-size: 1.3rem; font-weight: bold; letter-spacing: 1px;">Gate Management System - Admin Panel</div>
    <div class="user-info">
        <a href="/Capstone_project/logout.php" class="logout-btn" style="background:#e74c3c; color:#fff; padding:0.5rem 1rem; border-radius:20px; text-decoration:none;">Logout</a>
    </div>
</header>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <h2 style="margin-bottom: 1.5rem;">All Visitors</h2>
    <div style="overflow-x:auto;">
        <table class="visitors-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID Number</th>
                    <th>Department</th>
                    <th>Person to Visit</th>
                    <th>Purpose</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Registration Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visitors as $visitor): ?>
                <tr>
                    <td><?php echo htmlspecialchars($visitor['visitor_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($visitor['id_number'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($visitor['department'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($visitor['person_to_visit'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($visitor['purpose'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($visitor['telephone'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($visitor['email'] ?? ''); ?></td>
                    <td>
                        <span class="status-badge <?php echo ($visitor['status'] ?? '') === 'exited' ? 'status-exited' : 'status-inside'; ?>">
                            <?php echo htmlspecialchars(ucfirst($visitor['status'] ?? 'Inside')); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($visitor['registration_date'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<footer class="footer">
    &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
</footer>
<style>
:root {
    --header-height: 70px;
    --footer-height: 60px;
}
.header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--header-height);
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    font-family: inherit;
}
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: var(--footer-height);
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    backdrop-filter: blur(10px);
    padding: 1rem 2rem;
    text-align: center;
    color: #fff;
    z-index: 1000;
    font-size: 0.9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: inherit;
}
.main-content {
    margin-left: 250px;
    margin-top: var(--header-height);
    padding: 2rem 1rem;
    min-height: calc(100vh - var(--header-height) - var(--footer-height));
    transition: margin-left 0.3s;
    background: #f5f7fa;
    overflow-y: auto;
    padding-bottom: calc(var(--footer-height) + 2rem);
}
.sidebar.closed ~ .main-content { margin-left: 0; }
@media (max-width: 900px) {
    .sidebar { width: 200px; }
    .main-content { margin-left: 200px; }
}
@media (max-width: 700px) {
    .sidebar { top: var(--header-height); width: 180px; }
    .main-content { margin-left: 0; margin-top: var(--header-height); }
}
</style>
</body>
</html> 