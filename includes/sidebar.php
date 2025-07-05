<?php
// includes/sidebar.php
$user_type = get_user_type() ?? 'student'; // Get actual user type from session
?>
<aside class="sidebar">
    <nav class="sidebar-menu">
        <?php if ($user_type === 'admin'): ?>
            <div class="menu-section">
                <h3>Admin Panel</h3>
                <a href="/Capstone_project/pages/dashboard_admin.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard_admin.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/Capstone_project/pages/students.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> View All Students
                </a>
                <a href="/Capstone_project/pages/register_student.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'register_student.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i> Register New Student
                </a>
                <a href="/Capstone_project/pages/users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="/Capstone_project/pages/devices.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'devices.php' ? 'active' : ''; ?>">
                    <i class="fas fa-laptop"></i> Devices
                </a>
                <a href="/Capstone_project/pages/cards.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'cards.php' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> Cards
                </a>
                <a href="/Capstone_project/pages/logs.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Entry/Exit Logs
                </a>
                <a href="/Capstone_project/pages/reports.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="/Capstone_project/pages/settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i> Settings
                </a>
                <a href="/Capstone_project/pages/security_officers.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'security_officers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i> Security Officers
                </a>
                <a href="/Capstone_project/pages/register_security.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'register_security.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i> Register Security Officer
                </a>
            </div>
        <?php elseif ($user_type === 'security'): ?>
            <div class="menu-section">
                <h3>Security</h3>
                <a href="/Capstone_project/pages/visitor_form.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'visitor_form.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i> Register Visitor
                </a>
                <a href="/Capstone_project/pages/logs.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Students
                </a>
                <a href="/Capstone_project/pages/visitors.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'visitors.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Manage Visitors
                </a>
            </div>
        <?php else: ?>
            <div class="menu-section">
                <h3>Student Portal</h3>
                <a href="/Capstone_project/pages/dashboard_student.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard_student.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/Capstone_project/device/my_devices.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'my_devices.php' ? 'active' : ''; ?>">
                    <i class="fas fa-laptop"></i> My Devices
                </a>
                <a href="/Capstone_project/pages/lend_computer.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'lend_computer.php' ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-circle-right"></i> Lend Computer
                </a>
                <a href="/Capstone_project/pages/return_computer.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'return_computer.php' ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-circle-left"></i> Return Computer
                </a>
                <a href="/Capstone_project/pages/my_borrowed_computers.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'my_borrowed_computers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-laptop-house"></i> My Borrowed Computers
                </a>
                <a href="/Capstone_project/cards/my_cards.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'my_cards.php' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> My Cards
                </a>
                <a href="/Capstone_project/api/entry_exit/logs.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Entry/Exit Logs
                </a>
                <a href="/Capstone_project/pages/profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Common menu items for all users -->
        <div class="menu-section">
            <h3>Account</h3>
            <a href="/Capstone_project/change_password.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'change_password.php' ? 'active' : ''; ?>">
                <i class="fas fa-key"></i> Change Password
            </a>
            <a href="/Capstone_project/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
</aside> 