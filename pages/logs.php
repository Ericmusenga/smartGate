<?php
require_once '../config/config.php';

// Check if user is logged in and is security
if (!is_logged_in()) {
    redirect('../login.php');
}

if (get_user_type() !== 'security') {
    redirect('../login.php');
}

// Get filter parameters
$date_filter = $_GET['date'] ?? '';
$status_filter = $_GET['status'] ?? '';
$gate_filter = $_GET['gate'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$error_message = '';
$logs = [];
$total_logs = 0;
$total_pages = 0;

try {
    $db = getDB();
    
    // Build the WHERE clause
    $where_conditions = [];
    $params = [];
    
    if ($date_filter) {
        $where_conditions[] = "DATE(eel.created_at) = ?";
        $params[] = $date_filter;
    }
    
    if ($status_filter) {
        $where_conditions[] = "eel.status = ?";
        $params[] = $status_filter;
    }
    
    if ($gate_filter) {
        $where_conditions[] = "eel.gate_number = ?";
        $params[] = $gate_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR s.registration_number LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total
        FROM entry_exit_logs eel
        LEFT JOIN users u ON eel.user_id = u.id
        LEFT JOIN students s ON u.student_id = s.id
        $where_clause
    ";
    
    $total_result = $db->fetch($count_sql, $params);
    $total_logs = $total_result['total'];
    $total_pages = ceil($total_logs / $per_page);
    
    // Get logs with pagination
    $logs_sql = "
        SELECT eel.*, u.first_name, u.last_name, u.email, s.registration_number, s.department, r.role_name
        FROM entry_exit_logs eel
        LEFT JOIN users u ON eel.user_id = u.id
        LEFT JOIN students s ON u.student_id = s.id
        LEFT JOIN roles r ON u.role_id = r.id
        $where_clause
        ORDER BY eel.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $logs = $db->fetchAll($logs_sql, $params);
    
    // Get available gates for filter
    $gates = $db->fetchAll("SELECT DISTINCT gate_number FROM entry_exit_logs WHERE gate_number IS NOT NULL ORDER BY gate_number");
    
} catch (Exception $e) {
    $error_message = 'Error loading logs: ' . $e->getMessage();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-container">
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Entry/Exit Logs</div>
            <div class="page-subtitle">View and manage campus entry/exit records</div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter"></i> Filters
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-2">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" id="date" name="date" class="form-control" 
                               value="<?php echo htmlspecialchars($date_filter); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All</option>
                            <option value="entered" <?php echo $status_filter === 'entered' ? 'selected' : ''; ?>>Entry</option>
                            <option value="exited" <?php echo $status_filter === 'exited' ? 'selected' : ''; ?>>Exit</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="gate" class="form-label">Gate</label>
                        <select id="gate" name="gate" class="form-select">
                            <option value="">All Gates</option>
                            <?php foreach ($gates as $gate): ?>
                                <option value="<?php echo $gate['gate_number']; ?>" 
                                        <?php echo $gate_filter == $gate['gate_number'] ? 'selected' : ''; ?>>
                                    Gate <?php echo $gate['gate_number']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="Name or Registration Number"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="logs.php" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?php echo $total_logs; ?></h5>
                        <p class="card-text">Total Records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success">
                            <?php 
                            $today_entries = 0;
                            try {
                                $today_entries = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs WHERE DATE(created_at) = CURDATE() AND status = 'entered'")['count'];
                            } catch (Exception $e) {}
                            echo $today_entries;
                            ?>
                        </h5>
                        <p class="card-text">Today's Entries</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning">
                            <?php 
                            $today_exits = 0;
                            try {
                                $today_exits = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs WHERE DATE(created_at) = CURDATE() AND status = 'exited'")['count'];
                            } catch (Exception $e) {}
                            echo $today_exits;
                            ?>
                        </h5>
                        <p class="card-text">Today's Exits</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">
                            <?php 
                            $on_campus = 0;
                            try {
                                $on_campus = $db->fetch("SELECT COUNT(DISTINCT user_id) as count FROM entry_exit_logs WHERE DATE(created_at) = CURDATE() AND status = 'entered'")['count'];
                            } catch (Exception $e) {}
                            echo $on_campus;
                            ?>
                        </h5>
                        <p class="card-text">Currently on Campus</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list"></i> Entry/Exit Records
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No logs found</h5>
                        <p class="text-muted">Try adjusting your filters or search criteria</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Person</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Gate</th>
                                    <th>Method</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo date('M j, Y', strtotime($log['created_at'])); ?></div>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($log['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php 
                                                if ($log['first_name'] && $log['last_name']) {
                                                    echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']);
                                                } else {
                                                    echo 'Unknown User';
                                                }
                                                ?>
                                            </div>
                                            <?php if ($log['registration_number']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['registration_number']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($log['department']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($log['department']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo ucfirst($log['role_name'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($log['status'] === 'entered'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-sign-in-alt"></i> Entry
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-sign-out-alt"></i> Exit
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                Gate <?php echo $log['gate_number'] ?? 'N/A'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark">
                                                <?php echo strtoupper($log['entry_method'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#logModal<?php echo $log['id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal for log details -->
                                    <div class="modal fade" id="logModal<?php echo $log['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Log Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Person:</strong><br>
                                                            <?php 
                                                            if ($log['first_name'] && $log['last_name']) {
                                                                echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']);
                                                            } else {
                                                                echo 'Unknown User';
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Registration Number:</strong><br>
                                                            <?php echo htmlspecialchars($log['registration_number'] ?? 'N/A'); ?>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Department:</strong><br>
                                                            <?php echo htmlspecialchars($log['department'] ?? 'N/A'); ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Role:</strong><br>
                                                            <?php echo ucfirst($log['role_name'] ?? 'Unknown'); ?>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Status:</strong><br>
                                                            <?php if ($log['status'] === 'entered'): ?>
                                                                <span class="badge bg-success">Entry</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Exit</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Gate:</strong><br>
                                                            Gate <?php echo $log['gate_number'] ?? 'N/A'; ?>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Method:</strong><br>
                                                            <?php echo strtoupper($log['entry_method'] ?? 'Unknown'); ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Date & Time:</strong><br>
                                                            <?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Logs pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</div>

<style>
.page-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding-bottom: 60px; /* Prevent content from being hidden behind the fixed footer */
}

.main-content {
    flex: 1;
}

.footer {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 1rem 2rem;
    text-align: center;
    color: #7f8c8d;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75rem;
}

.pagination .page-link {
    color: #007bff;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}

.form-control:focus,
.form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>

<?php include '../includes/footer.php'; ?> 