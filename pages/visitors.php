<?php
require_once '../config/config.php';
if (!is_logged_in()) { redirect('../login.php'); }
$user_type = get_user_type();
if ($user_type !== 'admin' && $user_type !== 'security') { redirect('../unauthorized.php'); }
// Database configuration
$host = 'localhost';
$dbname = 'gate_management_system';
$username = 'root'; // Change this to your database username
$password = '';     // Change this to your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Handle filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($department_filter)) {
    $where_conditions[] = "department = :department";
    $params[':department'] = $department_filter;
}

if (!empty($search_filter)) {
    $where_conditions[] = "(visitor_name LIKE :search OR id_number LIKE :search OR telephone LIKE :search)";
    $params[':search'] = '%' . $search_filter . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM vistor $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get visitors data
$sql = "SELECT * FROM vistor $where_clause ORDER BY registration_date DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_visitors,
        SUM(CASE WHEN DATE(registration_date) = CURDATE() THEN 1 ELSE 0 END) as visitors_today,
        SUM(CASE WHEN status = 'inside' THEN 1 ELSE 0 END) as currently_inside,
        SUM(CASE WHEN status = 'exited' AND DATE(registration_date) = CURDATE() THEN 1 ELSE 0 END) as exited_today
    FROM vistor
";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Handle AJAX requests for check-out and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action']) && isset($_POST['visitor_id'])) {
        $visitor_id = (int)$_POST['visitor_id'];
        
        if ($_POST['action'] === 'checkout') {
            $update_sql = "UPDATE vistor SET status = 'exited', updated_at = NOW() WHERE id = :id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([':id' => $visitor_id]);
            
            echo json_encode(['success' => true, 'message' => 'Visitor checked out successfully']);
            exit;
        }
        
        if ($_POST['action'] === 'delete') {
            $delete_sql = "DELETE FROM vistor WHERE id = :id";
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->execute([':id' => $visitor_id]);
            
            echo json_encode(['success' => true, 'message' => 'Visitor record deleted successfully']);
            exit;
        }
    }
}

// Get visitor details for modal
if (isset($_GET['visitor_details']) && isset($_GET['visitor_id'])) {
    $visitor_id = (int)$_GET['visitor_id'];
    $detail_sql = "SELECT * FROM vistor WHERE id = :id";
    $detail_stmt = $pdo->prepare($detail_sql);
    $detail_stmt->execute([':id' => $visitor_id]);
    $visitor_detail = $detail_stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($visitor_detail);
    exit;
}

// Helper function to format status badge
function getStatusBadge($status) {
    switch(strtolower($status)) {
        case 'inside':
            return '<span class="status-badge status-inside">Inside</span>';
        case 'exited':
            return '<span class="status-badge status-exited">Exited</span>';
        case 'overdue':
            return '<span class="status-badge status-overdue">Overdue</span>';
        default:
            return '<span class="status-badge status-inside">Inside</span>';
    }
}

// Helper function to format equipment list
function formatEquipment($equipment, $other_details = '') {
    $equipment_text = $equipment;
    if (!empty($other_details)) {
        $equipment_text .= ', ' . $other_details;
    }
    return !empty($equipment_text) ? $equipment_text : 'None';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Visitors - Security Dashboard</title>
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
        
        /* Page Header */
        .page-header { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 2.2rem; font-weight: 700; color: #2c3e50; margin: 0; }
        .page-subtitle { color: #7f8c8d; font-size: 1.1rem; margin: 5px 0 0 0; }
        .header-actions { display: flex; gap: 15px; }
        .btn { padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; }
        .btn-primary:hover { background: #0056b3; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,123,255,0.3); }
        .btn-success { background: linear-gradient(135deg, #28a745, #1e7e34); color: #fff; }
        .btn-success:hover { background: #1e7e34; transform: translateY(-1px); }
        .btn-warning { background: linear-gradient(135deg, #ffc107, #e0a800); color: #212529; }
        .btn-warning:hover { background: #e0a800; transform: translateY(-1px); }
        .btn-danger { background: linear-gradient(135deg, #dc3545, #c82333); color: #fff; }
        .btn-danger:hover { background: #c82333; transform: translateY(-1px); }
        .btn-sm { padding: 8px 12px; font-size: 0.8rem; }
        
        /* Filters and Search */
        .filters-section { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 25px; margin-bottom: 25px; }
        .filters-grid { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 20px; align-items: end; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-weight: 600; color: #2c3e50; margin-bottom: 5px; font-size: 0.9rem; }
        .filter-control { border: 2px solid #e9ecef; border-radius: 6px; padding: 10px 12px; font-size: 0.9rem; transition: all 0.2s; }
        .filter-control:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
        
        /* Statistics Cards */
        .stats-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 25px; }
        .stat-card .stat-value { font-size: 2.5rem; font-weight: 700; margin-bottom: 5px; }
        .stat-card .stat-label { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 15px; }
        .stat-card .stat-icon { font-size: 2rem; opacity: 0.3; float: right; margin-top: -50px; }
        .stat-card.visitors-today .stat-value { color: #28a745; }
        .stat-card.visitors-total .stat-value { color: #007bff; }
        .stat-card.currently-inside .stat-value { color: #ffc107; }
        .stat-card.visitors-exited .stat-value { color: #6c757d; }
        
        /* Visitors Table */
        .table-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); overflow: hidden; }
        .table-header { background: #f8f9fa; padding: 20px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; }
        .table-title { font-size: 1.3rem; font-weight: 600; color: #2c3e50; margin: 0; }
        .table-actions { display: flex; gap: 10px; }
        .table-wrapper { overflow-x: auto; }
        .visitors-table { width: 100%; border-collapse: collapse; }
        .visitors-table th, .visitors-table td { padding: 15px 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
        .visitors-table th { background: #f8f9fa; font-weight: 600; color: #2c3e50; font-size: 0.9rem; }
        .visitors-table tr:hover { background: #f8f9fa; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-inside { background: #d4edda; color: #155724; }
        .status-exited { background: #f8d7da; color: #721c24; }
        .status-overdue { background: #fff3cd; color: #856404; }
        .equipment-list { font-size: 0.8rem; color: #6c757d; max-width: 150px; }
        .actions-cell { display: flex; gap: 5px; }
        
        /* Pagination */
        .pagination-wrapper { padding: 20px; border-top: 1px solid #e9ecef; display: flex; justify-content: between; align-items: center; }
        .pagination-info { color: #6c757d; font-size: 0.9rem; }
        .pagination { display: flex; gap: 5px; margin-left: auto; }
        .page-btn { padding: 8px 12px; border: 1px solid #e9ecef; background: #fff; color: #007bff; text-decoration: none; border-radius: 5px; font-size: 0.9rem; }
        .page-btn:hover { background: #f8f9fa; }
        .page-btn.active { background: #007bff; color: #fff; }
        .page-btn.disabled { color: #6c757d; pointer-events: none; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-header { padding: 20px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 1.3rem; font-weight: 600; color: #2c3e50; margin: 0; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6c757d; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 20px; border-top: 1px solid #e9ecef; display: flex; gap: 10px; justify-content: flex-end; }
        .visitor-detail { margin-bottom: 15px; }
        .visitor-detail label { font-weight: 600; color: #2c3e50; display: block; margin-bottom: 5px; }
        .visitor-detail span { color: #495057; }
        
        @media (max-width: 768px) {
            .filters-grid { grid-template-columns: 1fr; }
            .stats-section { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; gap: 20px; text-align: center; }
            .header-actions { width: 100%; justify-content: center; }
            .table-header { flex-direction: column; gap: 15px; }
            .visitors-table { font-size: 0.8rem; }
            .visitors-table th, .visitors-table td { padding: 10px 8px; }
        }
    </style>
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
                <a href="/Capstone_project/pages/visitor_form.php" class="menu-item"><i class="fas fa-user-plus"></i> Register Visitor</a>
                <a href="/Capstone_project/pages/visitors.php" class="menu-item active"><i class="fas fa-users"></i> Manage Visitors</a>
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
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fas fa-users"></i> Manage Visitors</h1>
                <p class="page-subtitle">View and manage all registered visitors</p>
            </div>
            <div class="header-actions">
                <a href="/Capstone_project/pages/visitor_form.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Register New Visitor
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-section">
            <div class="stat-card visitors-today">
                <div class="stat-value"><?php echo $stats['visitors_today']; ?></div>
                <div class="stat-label">Visitors Today</div>
                <i class="fas fa-calendar-day stat-icon"></i>
            </div>
            <div class="stat-card visitors-total">
                <div class="stat-value"><?php echo $stats['total_visitors']; ?></div>
                <div class="stat-label">Total Visitors</div>
                <i class="fas fa-users stat-icon"></i>
            </div>
            <div class="stat-card currently-inside">
                <div class="stat-value"><?php echo $stats['currently_inside']; ?></div>
                <div class="stat-label">Currently Inside</div>
                <i class="fas fa-sign-in-alt stat-icon"></i>
            </div>
            <div class="stat-card visitors-exited">
                <div class="stat-value"><?php echo $stats['exited_today']; ?></div>
                <div class="stat-label">Exited Today</div>
                <i class="fas fa-sign-out-alt stat-icon"></i>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="filter-control">
                            <option value="">All Status</option>
                            <option value="inside" <?php echo $status_filter === 'inside' ? 'selected' : ''; ?>>Currently Inside</option>
                            <option value="exited" <?php echo $status_filter === 'exited' ? 'selected' : ''; ?>>Exited</option>
                            <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="department">Department</label>
                        <select name="department" id="department" class="filter-control">
                            <option value="">All Departments</option>
                            <option value="Administration" <?php echo $department_filter === 'Administration' ? 'selected' : ''; ?>>Administration</option>
                            <option value="Academic Affairs" <?php echo $department_filter === 'Academic Affairs' ? 'selected' : ''; ?>>Academic Affairs</option>
                            <option value="Student Affairs" <?php echo $department_filter === 'Student Affairs' ? 'selected' : ''; ?>>Student Affairs</option>
                            <option value="Finance" <?php echo $department_filter === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                            <option value="Human Resources" <?php echo $department_filter === 'Human Resources' ? 'selected' : ''; ?>>Human Resources</option>
                            <option value="ICT Department" <?php echo $department_filter === 'ICT Department' ? 'selected' : ''; ?>>ICT Department</option>
                            <option value="Library" <?php echo $department_filter === 'Library' ? 'selected' : ''; ?>>Library</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="filter-control" 
                               placeholder="Search by name, ID, or phone..." value="<?php echo htmlspecialchars($search_filter); ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Visitors Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Visitor Records</h2>
                <div class="table-actions">
                    <form method="GET" action="" style="display: inline;">
                        <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                        <input type="hidden" name="department" value="<?php echo $department_filter; ?>">
                        <input type="hidden" name="search" value="<?php echo $search_filter; ?>">
                        <select name="limit" class="filter-control" onchange="this.form.submit()">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 per page</option>
                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 per page</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 per page</option>
                        </select>
                    </form>
                </div>
            </div>
             <div class="table-wrapper">
                <table class="visitors-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID Number</th>
                            <th>Department</th>
                            <th>Person to Visit</th>
                            <th>Entry Time</th>
                            <th>Status</th>
                            <th>Equipment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitors as $visitor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($visitor['visitor_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($visitor['id_number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($visitor['department'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($visitor['person_to_visit'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($visitor['registration_date'] ?? ''); ?></td>
                            <td><?php echo getStatusBadge($visitor['status'] ?? 'inside'); ?></td>
                            <td>
                                <div class="equipment-list">
                                    <?php echo htmlspecialchars(formatEquipment($visitor['equipment_brought'] ?? '', $visitor['other_equipment_details'] ?? '')); ?>
                                </div>
                            </td>
                            <td class="actions-cell">
                                <button onclick="showVisitorDetails(<?php echo $visitor['id']; ?>)" class="btn btn-primary btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (($visitor['status'] ?? 'inside') === 'inside'): ?>
                                <button onclick="checkOutVisitor(<?php echo $visitor['id']; ?>)" class="btn btn-warning btn-sm" title="Check Out">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                                <?php endif; ?>
                                <button onclick="editVisitor(<?php echo $visitor['id']; ?>)" class="btn btn-success btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteVisitor(<?php echo $visitor['id']; ?>)" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($visitors)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                                <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 20px;"></i><br>
                                No visitors found matching your criteria.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing 1-5 of 15 visitors
                </div>
                <div class="pagination">
                    <a href="#" class="page-btn disabled">Previous</a>
                    <a href="#" class="page-btn active">1</a>
                    <a href="#" class="page-btn">2</a>
                    <a href="#" class="page-btn">3</a>
                    <a href="#" class="page-btn">Next</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Visitor Details Modal -->
    <div id="visitorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Visitor Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="visitor-detail">
                    <label>Full Name:</label>
                    <span><?php echo htmlspecialchars($visitor_detail['visitor_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="visitor-detail">
                    <label>ID Number:</label>
                    <span><?php echo htmlspecialchars($visitor_detail['id_number'] ?? 'N/A'); ?></span>
                </div>
                <div class="visitor-detail">
                    <label>Email:</label>
                    <span><?php echo htmlspecialchars($visitor_detail['email'] ?? 'N/A'); ?></span>
                </div>
                <div class="visitor-detail">
                    <label>Phone:</label>
                    <span><?php echo htmlspecialchars($visitor_detail['telephone'] ?? 'N/A'); ?></span>
                </div>
                <div class="visitor-detail">
                    <label>Department:</label>
                    <span><?php echo htmlspecialchars($visitor_detail['department'] ?? 'N/A'); ?></span>
                </div>
                <div class="visitor-detail">
                    <label>Person to Visit:</label>
                    <span><?php echo htmlspecialchars($visitor_detail['person_to_visit'] ?? 'N/A'); ?></span>
                </div>
                <div class="visitor-detail">
                    <label>Purpose:</label>
                    <span><?php echo htmlspecialchars($visitor_detail['visit_purpose'] ?? 'N/A'); ?></span>
                </div>
                <div class="visitor-detail">
                    <label>Equipment:</label>
                    <span><?php echo htmlspecialchars(formatEquipmentJS($visitor_detail['equipment_brought'] ?? '', $visitor_detail['other_equipment_details'] ?? '')); ?></span>
                </div>
                <div class="visitor-detail">
                    <label>Entry Time:</label>
                    <span><?php echo htmlspecialchars(formatDateTime($visitor_detail['registration_date'] ?? '')); ?></span>
                </div>
                <div class="visitor-detail">
                    <label>Status:</label>
                    <span class="status-badge <?php echo getStatusClass($visitor_detail['status'] ?? 'inside'); ?>">
                        <?php echo htmlspecialchars($visitor_detail['status'] ?? 'Inside'); ?>
                    </span>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" class="btn btn-secondary">Close</button>
                <button onclick="editVisitor(<?php echo $visitor_detail['id'] ?? 0; ?>)" class="btn btn-primary">Edit Visitor</button>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; 2024 Gate Management System - UR College of Education, Rukara Campus
    </footer>
    // Add this JavaScript code before the closing </body> tag

<script>
// Toggle sidebar function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('closed');
}

// Show visitor details modal
function showVisitorDetails(visitorId) {
    fetch(`?visitor_details=1&visitor_id=${visitorId}`)
        .then(response => response.json())
        .then(data => {
            if (data) {
                // Update modal content with visitor data
                const modalBody = document.querySelector('#visitorModal .modal-body');
                modalBody.innerHTML = `
                    <div class="visitor-detail">
                        <label>Full Name:</label>
                        <span>${htmlspecialchars(data.visitor_name ?? 'N/A')}</span>
                    </div>
                    <div class="visitor-detail">
                        <label>ID Number:</label>
                        <span>${htmlspecialchars(data.id_number ?? 'N/A')}</span>
                    </div>
                    <div class="visitor-detail">
                        <label>Email:</label>
                        <span>${htmlspecialchars(data.email ?? 'N/A')}</span>
                    </div>
                    <div class="visitor-detail">
                        <label>Phone:</label>
                        <span>${htmlspecialchars(data.telephone ?? 'N/A')}</span>
                    </div>
                    <div class="visitor-detail">
                        <label>Department:</label>
                        <span>${htmlspecialchars(data.department ?? 'N/A')}</span>
                    </div>
                    <div class="visitor-detail">
                        <label>Person to Visit:</label>
                        <span>${htmlspecialchars(data.person_to_visit ?? 'N/A')}</span>
                    </div>
                    <div class="visitor-detail">
                        <label>Purpose:</label>
                        <span>${htmlspecialchars(data.visit_purpose ?? 'N/A')}</span>
                    </div>
                    <div class="visitor-detail">
                        <label>Equipment:</label>
                        <span>${formatEquipmentJS(data.equipment_brought, data.other_equipment_details)}</span>
                    </div>
                    <div class="visitor-detail">
                        <label>Entry Time:</label>
                        <span>${formatDateTime(data.registration_date)}</span>
                    </div>
                    <div class="visitor-detail">
                        <label>Status:</label>
                        <span class="status-badge ${getStatusClass(data.status)}">${htmlspecialchars(data.status ?? 'Inside')}</span>
                    </div>
                `;
                
                // Update modal footer buttons
                const modalFooter = document.querySelector('#visitorModal .modal-footer');
                modalFooter.innerHTML = `
                    <button onclick="closeModal()" class="btn btn-secondary">Close</button>
                    <button onclick="editVisitor(${visitorId})" class="btn btn-primary">Edit Visitor</button>
                `;
                
                // Show modal
                document.getElementById('visitorModal').classList.add('show');
            }
        })
        .catch(error => {
            console.error('Error fetching visitor details:', error);
            alert('Error loading visitor details. Please try again.');
        });
}

// Edit visitor function
function editVisitor(visitorId) {
    // Redirect to edit page with visitor ID
    window.location.href = `/Capstone_project/pages/edit_visitor.php?id=${visitorId}`;
}

// Delete visitor function
function deleteVisitor(visitorId) {
    if (confirm('Are you sure you want to delete this visitor record? This action cannot be undone.')) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('visitor_id', visitorId);
        
        // Send delete request
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Reload the page to update the table
                window.location.reload();
            } else {
                alert('Error deleting visitor record. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error deleting visitor:', error);
            alert('Error deleting visitor record. Please try again.');
        });
    }
}

// Check out visitor function
function checkOutVisitor(visitorId) {
    if (confirm('Are you sure you want to check out this visitor?')) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'checkout');
        formData.append('visitor_id', visitorId);
        
        // Send checkout request
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Reload the page to update the table
                window.location.reload();
            } else {
                alert('Error checking out visitor. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error checking out visitor:', error);
            alert('Error checking out visitor. Please try again.');
        });
    }
}

// Close modal function
function closeModal() {
    document.getElementById('visitorModal').classList.remove('show');
}

// Helper function to format equipment
function formatEquipmentJS(equipment, otherDetails) {
    let equipmentText = equipment || '';
    if (otherDetails) {
        equipmentText += (equipmentText ? ', ' : '') + otherDetails;
    }
    return equipmentText || 'None';
}

// Helper function to format date time
function formatDateTime(dateTime) {
    if (!dateTime) return 'N/A';
    const date = new Date(dateTime);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

// Helper function to get status class
function getStatusClass(status) {
    switch((status || 'inside').toLowerCase()) {
        case 'inside':
            return 'status-inside';
        case 'exited':
            return 'status-exited';
        case 'overdue':
            return 'status-overdue';
        default:
            return 'status-inside';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('visitorModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>
</body>
</html>