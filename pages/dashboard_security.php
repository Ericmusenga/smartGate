<?php
require_once '../config/config.php';

// Fetch dashboard data for security
$dashboard_data = [
    'recent_entries' => [],
    'recent_exits' => [],
    'today_visitors_list' => []
];

try {
    $db = getDB();
    // Recent entries (today)
    $dashboard_data['recent_entries'] = $db->fetchAll('
        SELECT s.first_name, s.last_name, s.registration_number, s.department, eel.created_at
        FROM entry_exit_logs eel
        JOIN students s ON eel.student_id = s.id
        WHERE eel.status = "entered" AND DATE(eel.created_at) = CURDATE()
        ORDER BY eel.created_at DESC
        LIMIT 10
    ');
    // Recent exits (today)
    $dashboard_data['recent_exits'] = $db->fetchAll('
        SELECT s.first_name, s.last_name, s.registration_number, s.department, eel.created_at
        FROM entry_exit_logs eel
        JOIN students s ON eel.student_id = s.id
        WHERE eel.status = "exited" AND DATE(eel.created_at) = CURDATE()
        ORDER BY eel.created_at DESC
        LIMIT 10
    ');
    // Today's visitors
    $dashboard_data['today_visitors_list'] = $db->fetchAll('
        SELECT visitor_name, telephone, purpose, person_to_visit, created_at
        FROM vistor
        WHERE DATE(created_at) = CURDATE()
        ORDER BY created_at DESC
        LIMIT 10
    ');
} catch (Exception $e) {
    // If DB error, leave dashboard_data empty
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard</title>
    <link rel="stylesheet" href="/Capstone_project/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --header-height: 70px;
            --footer-height: 60px;
        }
        .sidebar { position: fixed; left: 0; top: var(--header-height); bottom: var(--footer-height); width: 250px; background: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.07); z-index: 999; transition: transform 0.3s; }
        .sidebar.closed { transform: translateX(-100%); }
        .sidebar-menu { padding: 2rem 0; }
        .menu-section { margin-bottom: 2rem; }
        .menu-section h3 { color: #7f8c8d; font-size: 0.8rem; text-transform: uppercase; padding: 0 2rem 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid #eee; }
        .menu-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; color: #2c3e50; text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .menu-item:hover, .menu-item.active { background:rgb(225, 226, 230); border-left-color: #3498db; color: #3498db; }
        .menu-item i { font-size: 1.2rem; width: 20px; text-align: center; }
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
        .header .sidebar-toggle { background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; margin-right: 1rem; }
        .header .user-info { display: flex; align-items: center; gap: 1rem; }
        .header .user-avatar { width: 35px; height: 35px; border-radius: 50%; background:rgb(73, 91, 209); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--footer-height);
            background: #fff;
            border-top: 1px solid #e9ecef;
            padding: 1rem 2rem;
            text-align: center;
            color: #2c3e50;
            z-index: 1000;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
        }
        .main-content { margin-left: 250px; margin-top: var(--header-height); padding: 2rem 1rem; min-height: calc(100vh - var(--header-height) - var(--footer-height)); transition: margin-left 0.3s; background: rgb(8, 78, 147); overflow-y: auto; padding-bottom: calc(var(--footer-height) + 2rem); }
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
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('closed');
        }
    </script>
</head>
<body>
    <header class="header">
        <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <div style="font-size: 1.3rem; font-weight: bold; letter-spacing: 1px;">Gate Management System - Security Dashboard</div>
        <div class="user-info">
            <div class="user-avatar">S</div>
            <span>Security</span>
            <a href="/Capstone_project/logout.php" class="logout-btn" style="background:#e74c3c; color:#fff; padding:0.5rem 1rem; border-radius:20px; text-decoration:none;">Logout</a>
        </div>
    </header>
    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <div class="menu-section">
                <h3>Security</h3>
                <a href="/Capstone_project/pages/dashboard_security.php" class="menu-item active"><i class="fas fa-shield-alt"></i> Dashboard</a>
                <a href="/Capstone_project/pages/visitor_form.php" class="menu-item"><i class="fas fa-user-plus"></i> Register Visitor</a>
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
        <!-- Place your dashboard content here (stats, quick actions, cards, etc.) -->
      
        
        
        <!-- Main Content Two Columns -->
        <div class="dashboard-main-row" style="display: flex; gap: 30px;">
            <div class="dashboard-main-col" style="flex: 1 1 0; min-width: 320px;">
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><i class="fas fa-sign-in-alt"></i> Recent Entries</div>
                    <div class="dashboard-card-body">
                        <?php if (empty($dashboard_data['recent_entries'])): ?>
                            <div class="dashboard-empty-state">
                                <i class="fas fa-inbox"></i>
                                <div>No entries recorded today</div>
                            </div>
                        <?php else: ?>
                            <table class="dashboard-table">
                                <thead>
                                    <tr><th>Student</th><th>Department</th><th>Time</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboard_data['recent_entries'] as $entry): ?>
                                        <tr>
                                            <td>
                                                <div class="student-name"><?php echo htmlspecialchars(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? '')); ?></div>
                                                <div class="student-id"><?php echo htmlspecialchars($entry['registration_number'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td><span class="badge badge-dept"><?php echo htmlspecialchars($entry['department'] ?? 'N/A'); ?></span></td>
                                            <td><span class="badge badge-time"><?php echo date('H:i', strtotime($entry['created_at'])); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><i class="fas fa-sign-out-alt"></i> Recent Exits</div>
                    <div class="dashboard-card-body">
                        <?php if (empty($dashboard_data['recent_exits'])): ?>
                            <div class="dashboard-empty-state">
                                <i class="fas fa-inbox"></i>
                                <div>No exits recorded today</div>
                            </div>
                        <?php else: ?>
                            <table class="dashboard-table">
                                <thead>
                                    <tr><th>Student</th><th>Department</th><th>Time</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboard_data['recent_exits'] as $exit): ?>
                                        <tr>
                                            <td>
                                                <div class="student-name"><?php echo htmlspecialchars(($exit['first_name'] ?? '') . ' ' . ($exit['last_name'] ?? '')); ?></div>
                                                <div class="student-id"><?php echo htmlspecialchars($exit['registration_number'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td><span class="badge badge-dept"><?php echo htmlspecialchars($exit['department'] ?? 'N/A'); ?></span></td>
                                            <td><span class="badge badge-time"><?php echo date('H:i', strtotime($exit['created_at'])); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="dashboard-main-col" style="flex: 1 1 0; min-width: 320px;">
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><i class="fas fa-user-friends"></i> Today's Visitors</div>
                    <div class="dashboard-card-body">
                        <?php if (empty($dashboard_data['today_visitors_list'])): ?>
                            <div class="dashboard-empty-state">
                                <i class="fas fa-users"></i>
                                <div>No visitors registered today</div>
                            </div>
                        <?php else: ?>
                            <table class="dashboard-table">
                                <thead>
                                    <tr><th>Name</th><th>Phone</th><th>Purpose</th><th>To Visit</th><th>Time</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboard_data['today_visitors_list'] as $visitor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($visitor['visitor_name'] ?? ''); ?></td>
                                            <td><span class="badge badge-phone"><?php echo htmlspecialchars($visitor['telephone'] ?? ''); ?></span></td>
                                            <td><span class="badge badge-purpose"><?php echo htmlspecialchars($visitor['purpose'] ?? ''); ?></span></td>
                                            <td><span class="badge badge-person"><?php echo htmlspecialchars($visitor['person_to_visit'] ?? ''); ?></span></td>
                                            <td><span class="badge badge-time"><?php echo date('H:i', strtotime($visitor['created_at'])); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><i class="fas fa-edit"></i> Manual Entry/Exit</div>
                    <div class="dashboard-card-body">
                        <form method="POST" action="../api/entry_exit/manual.php" class="dashboard-form-row">
                            <div class="form-group">
                                <label for="registration_number">Registration Number</label>
                                <input type="text" id="registration_number" name="registration_number" class="form-control" placeholder="Enter student registration number" required>
                            </div>
                            <div class="form-group">
                                <label for="status">Action</label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="">Select Action</option>
                                    <option value="entered">Entry</option>
                                    <option value="exited">Exit</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="gate_number">Gate</label>
                                <select id="gate_number" name="gate_number" class="form-control" required>
                                    <option value="">Select Gate</option>
                                    <option value="1">Gate 1</option>
                                    <option value="2">Gate 2</option>
                                    <option value="3">Gate 3</option>
                                    <option value="4">Gate 4</option>
                                </select>
                            </div>
                            <div class="form-group form-group-btn">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Record</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
    </footer>
    <style>
        .dashboard-main { background:rgb(8, 78, 147); min-height: 100vh; padding: 30px 0; }
        .dashboard-header { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px 30px 20px 30px; margin-bottom: 30px; text-align: center; }
        .dashboard-title { font-size: 2.2rem; font-weight: 700; color: #2c3e50; margin-bottom: 8px; }
        .dashboard-welcome { color: #7f8c8d; font-size: 1.1rem; }
        .dashboard-stats-row { display: flex; gap: 18px; margin-bottom: 30px; flex-wrap: wrap; }
        .dashboard-stat-card { flex: 1 1 180px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 22px 0 18px 0; text-align: center; position: relative; min-width: 150px; }
        .stat-icon { font-size: 2.1rem; margin-bottom: 8px; opacity: 0.18; position: absolute; top: 12px; right: 18px; }
        .stat-value { font-size: 2.1rem; font-weight: 700; color: #2c3e50; }
        .stat-label { font-size: 0.95rem; color:rgb(97, 127, 172); margin-top: 2px; }
        .stat-blue { border-top: 4px solid #007bff; }
        .stat-green { border-top: 4px solid #28a745; }
        .stat-yellow { border-top: 4px solid #ffc107; }
        .stat-red { border-top: 4px solid #dc3545; }
        .stat-cyan { border-top: 4px solid #17a2b8; }
        .stat-dark { border-top: 4px solid #343a40; }
        .dashboard-actions-row { display: flex; gap: 18px; margin-bottom: 30px; flex-wrap: wrap; }
        .dashboard-action-btn { flex: 1 1 180px; background:rgb(93, 113, 163); border-radius: 10px; padding: 18px 0; text-align: center; font-weight: 600; color: #444; font-size: 1.05rem; text-decoration: none; transition: all 0.2s; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .dashboard-action-btn i { font-size: 1.4rem; margin-bottom: 6px; display: block; }
        .dashboard-action-btn:hover { background:rgb(5, 67, 129); color: #007bff; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .action-blue { color: #007bff; }
        .action-cyan { color: #17a2b8; }
        .action-purple { color: #6f42c1; }
        .action-green { color: #28a745; }
        .dashboard-main-row { display: flex; gap: 30px; }
        .dashboard-main-col { flex: 1 1 0; min-width: 320px; }
        .dashboard-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); margin-bottom: 30px; }
        .dashboard-card-header { font-size: 1.1rem; font-weight: 600; color: #2c3e50; background:rgb(231, 234, 237); border-bottom: 1px solid #e9ecef; padding: 18px 22px; border-radius: 12px 12px 0 0; }
        .dashboard-card-header i { margin-right: 8px; color: #007bff; }
        .dashboard-card-body { padding: 22px 22px 18px 22px; }
        .dashboard-empty-state { text-align: center; color: #b0b4b9; padding: 30px 0; }
        .dashboard-empty-state i { font-size: 2.5rem; margin-bottom: 10px; display: block; }
        .dashboard-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .dashboard-table th { background:rgb(226, 229, 233); color: #2c3e50; font-weight: 600; font-size: 0.95rem; padding: 10px 8px; border: none; }
        .dashboard-table td { padding: 10px 8px; border: none; font-size: 0.97rem; vertical-align: middle; }
        .dashboard-table tr { transition: background 0.2s; }
        .dashboard-table tr:hover { background:rgb(81, 103, 161); }
        .student-name { font-weight: 600; color: #2c3e50; font-size: 1rem; }
        .student-id { font-size: 0.85rem; color: #7f8c8d; }
        .badge { display: inline-block; border-radius: 6px; padding: 4px 10px; font-size: 0.85rem; font-weight: 500; }
        .badge-dept { background: #e9ecef; color: #495057; }
        .badge-time { background: #d4edda; color: #155724; }
        .badge-phone { background: #d1ecf1; color: #0c5460; }
        .badge-purpose { background: #f8d7da; color: #721c24; }
        .badge-person { background: #e2e3e5; color: #383d41; }
        .dashboard-form-row { display: flex; gap: 18px; flex-wrap: wrap; align-items: flex-end; }
        .form-group { flex: 1 1 180px; min-width: 150px; margin-bottom: 0; }
        .form-group label { font-weight: 600; color: #2c3e50; margin-bottom: 6px; display: block; }
        .form-control { border: 2px solid #e9ecef; border-radius: 8px; padding: 10px 14px; font-size: 1rem; transition: border 0.2s; width: 100%; }
        .form-control:focus { border-color: #007bff; outline: none; }
        .form-group-btn { flex: 0 0 120px; min-width: 120px; }
        .btn-primary { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: 12px 20px; transition: all 0.2s; }
        .btn-primary:hover { background: #0056b3; color: #fff; }
        @media (max-width: 1100px) { .dashboard-main-row { flex-direction: column; } }
        @media (max-width: 768px) {
            .dashboard-header { padding: 18px 8px; }
            .dashboard-title { font-size: 1.3rem; }
            .dashboard-stats-row, .dashboard-actions-row, .dashboard-main-row { flex-direction: column; gap: 12px; }
            .dashboard-stat-card, .dashboard-action-btn, .dashboard-main-col { min-width: 0; }
            .dashboard-card-header, .dashboard-card-body { padding: 12px 8px; }
            .dashboard-form-row { flex-direction: column; gap: 10px; }
        }
        .alert { border-radius: 10px; border: none; padding: 15px 20px; margin-bottom: 25px; font-weight: 500; }
        .alert-danger { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; }
        .alert i { margin-right: 8px; }
    </style>
</body>
</html>
