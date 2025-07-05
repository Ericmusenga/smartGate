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
$purpose_filter = $_GET['purpose'] ?? '';
$department_filter = $_GET['department'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$error_message = '';
$success_message = '';
$visitors = [];
$total_visitors = 0;
$total_pages = 0;

// Handle visitor status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $visitor_id = intval($_POST['visitor_id']);
        $new_status = $_POST['status'];
        
        try {
            $db = getDB();
            $db->query("UPDATE visitors SET status = ?, updated_at = NOW() WHERE id = ?", [$new_status, $visitor_id]);
            $status_class = 'bg-secondary';
            $status_text = 'Unknown';
            switch ($new_status) {
                case 'active':
                    $status_class = 'bg-success';
                    $status_text = 'On Campus';
                    break;
                case 'completed':
                    $status_class = 'bg-info';
                    $status_text = 'Completed';
                    break;
                case 'cancelled':
                    $status_class = 'bg-danger';
                    $status_text = 'Cancelled';
                    break;
            }
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'status_class' => $status_class,
                    'status_text' => $status_text
                ]);
                exit;
            }
            $success_message = 'Visitor status updated successfully!';
        } catch (Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error updating visitor status: ' . $e->getMessage()
                ]);
                exit;
            }
            $error_message = 'Error updating visitor status: ' . $e->getMessage();
        }
    }
}

try {
    $db = getDB();
    
    // Build the WHERE clause
    $where_conditions = [];
    $params = [];
    
    if ($date_filter) {
        $where_conditions[] = "DATE(created_at) = ?";
        $params[] = $date_filter;
    }
    
    if ($purpose_filter) {
        $where_conditions[] = "purpose = ?";
        $params[] = $purpose_filter;
    }
    
    if ($department_filter) {
        $where_conditions[] = "department = ?";
        $params[] = $department_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(visitor_name LIKE ? OR person_to_visit LIKE ? OR telephone LIKE ?)";
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
    $count_sql = "SELECT COUNT(*) as total FROM visitors $where_clause";
    $total_result = $db->fetch($count_sql, $params);
    $total_visitors = $total_result['total'];
    $total_pages = ceil($total_visitors / $per_page);
    
    // Get visitors with pagination
    $visitors_sql = "
        SELECT * FROM visitors 
        $where_clause
        ORDER BY created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $visitors = $db->fetchAll($visitors_sql, $params);
    
    // Get available purposes and departments for filters
    $purposes = $db->fetchAll("SELECT DISTINCT purpose FROM visitors WHERE purpose IS NOT NULL ORDER BY purpose");
    $departments = $db->fetchAll("SELECT DISTINCT department FROM visitors WHERE department IS NOT NULL ORDER BY department");
    
} catch (Exception $e) {
    $error_message = 'Error loading visitors: ' . $e->getMessage();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="page-container">
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Visitors Management</div>
            <div class="page-subtitle">View and manage campus visitors</div>
            <div class="page-actions">
                <a href="visitor_form.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Register New Visitor
                </a>
            </div>
        </div>
        
        <!-- Visitor Report Generation Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-csv"></i> Generate Visitor Report
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="../api/visitors_report.php" class="row g-3" target="_blank">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-download"></i> Download CSV Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <script>
                // Auto-close any open status modal after a short delay
                setTimeout(function() {
                    var modals = document.querySelectorAll('.modal.show');
                    modals.forEach(function(modal) {
                        var modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    });
                }, 1000); // 1 second delay
            </script>
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
                        <label for="purpose" class="form-label">Purpose</label>
                        <select id="purpose" name="purpose" class="form-select">
                            <option value="">All Purposes</option>
                            <?php foreach ($purposes as $purpose): ?>
                                <option value="<?php echo $purpose['purpose']; ?>" 
                                        <?php echo $purpose_filter === $purpose['purpose'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($purpose['purpose']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="department" class="form-label">Department</label>
                        <select id="department" name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department']; ?>" 
                                        <?php echo $department_filter === $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="Visitor name, person to visit, or phone"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="visitors.php" class="btn btn-secondary">
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
                        <h5 class="card-title text-primary"><?php echo $total_visitors; ?></h5>
                        <p class="card-text">Total Visitors</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success">
                            <?php 
                            $today_visitors_count = 0;
                            try {
                                $today_visitors_count = $db->fetch("SELECT COUNT(*) as count FROM visitors WHERE DATE(created_at) = CURDATE()")['count'];
                            } catch (Exception $e) {}
                            echo $today_visitors_count;
                            ?>
                        </h5>
                        <p class="card-text">Today's Visitors</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning">
                            <?php 
                            $active_visitors = 0;
                            try {
                                $active_visitors = $db->fetch("SELECT COUNT(*) as count FROM visitors WHERE status = 'active'")['count'];
                            } catch (Exception $e) {}
                            echo $active_visitors;
                            ?>
                        </h5>
                        <p class="card-text">Currently on Campus</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">
                            <?php 
                            $completed_visits = 0;
                            try {
                                $completed_visits = $db->fetch("SELECT COUNT(*) as count FROM visitors WHERE status = 'completed'")['count'];
                            } catch (Exception $e) {}
                            echo $completed_visits;
                            ?>
                        </h5>
                        <p class="card-text">Completed Visits</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Visitors Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users"></i> Visitors List
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($visitors)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No visitors found</h5>
                        <p class="text-muted">Try adjusting your filters or search criteria</p>
                        <a href="visitor_form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Register First Visitor
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Visitor Name</th>
                                    <th>Contact Info</th>
                                    <th>Purpose</th>
                                    <th>Person to Visit</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visitors as $visitor): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo date('M j, Y', strtotime($visitor['created_at'])); ?></div>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($visitor['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($visitor['visitor_name']); ?></div>
                                            <?php if ($visitor['id_number']): ?>
                                                <small class="text-muted">ID: <?php echo htmlspecialchars($visitor['id_number']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($visitor['telephone']); ?></div>
                                            <?php if ($visitor['email']): ?>
                                                <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($visitor['email']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($visitor['purpose']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($visitor['person_to_visit']); ?></td>
                                        <td>
                                            <?php if ($visitor['department']): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($visitor['department']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'bg-secondary';
                                            $status_text = 'Unknown';
                                            switch ($visitor['status'] ?? 'active') {
                                                case 'active':
                                                    $status_class = 'bg-success';
                                                    $status_text = 'On Campus';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'bg-info';
                                                    $status_text = 'Completed';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-danger';
                                                    $status_text = 'Cancelled';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" data-bs-target="#visitorModal<?php echo $visitor['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $visitor['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal for visitor details -->
                                    <div class="modal fade" id="visitorModal<?php echo $visitor['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Visitor Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Visitor Name:</strong><br>
                                                            <?php echo htmlspecialchars($visitor['visitor_name']); ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>ID Number:</strong><br>
                                                            <?php echo htmlspecialchars($visitor['id_number'] ?? 'N/A'); ?>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Telephone:</strong><br>
                                                            <?php echo htmlspecialchars($visitor['telephone']); ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Email:</strong><br>
                                                            <?php echo htmlspecialchars($visitor['email'] ?? 'N/A'); ?>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Purpose:</strong><br>
                                                            <?php echo htmlspecialchars($visitor['purpose']); ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Person to Visit:</strong><br>
                                                            <?php echo htmlspecialchars($visitor['person_to_visit']); ?>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Department:</strong><br>
                                                            <?php echo htmlspecialchars($visitor['department'] ?? 'N/A'); ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Status:</strong><br>
                                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($visitor['notes'])): ?>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <strong>Notes:</strong><br>
                                                                <?php echo nl2br(htmlspecialchars($visitor['notes'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Registration Time:</strong><br>
                                                            <?php echo date('M j, Y g:i A', strtotime($visitor['created_at'])); ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Last Updated:</strong><br>
                                                            <?php echo !empty($visitor['updated_at']) ? date('M j, Y g:i A', strtotime($visitor['updated_at'])) : 'N/A'; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal for status update -->
                                    <div class="modal fade" id="statusModal<?php echo $visitor['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Visitor Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="" class="status-update-form">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="visitor_id" value="<?php echo $visitor['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="status<?php echo $visitor['id']; ?>" class="form-label">Status</label>
                                                            <select class="form-select" id="status<?php echo $visitor['id']; ?>" name="status" required>
                                                                <option value="active" <?php echo ($visitor['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>On Campus</option>
                                                                <option value="completed" <?php echo ($visitor['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                <option value="cancelled" <?php echo ($visitor['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle"></i>
                                                            <strong>Current Status:</strong> <?php echo $status_text; ?>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Visitors pagination" class="mt-4">
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

.btn-group .btn {
    margin-right: 2px;
}

.modal-lg {
    max-width: 800px;
}
</style>

<script>
document.querySelectorAll('.status-update-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var modal = form.closest('.modal');
        var formData = new FormData(form);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        // Update the badge in the table
                        var row = document.querySelector('button[data-bs-target="#statusModal' + formData.get('visitor_id') + '"]').closest('tr');
                        var badge = row.querySelector('td:nth-child(7) .badge');
                        badge.className = 'badge ' + res.status_class;
                        badge.textContent = res.status_text;
                        // Close the modal
                        var modalInstance = bootstrap.Modal.getInstance(modal);
                        modalInstance.hide();
                    } else {
                        alert(res.message || 'Failed to update status.');
                    }
                } catch (e) {
                    alert('Unexpected response from server.');
                }
            } else {
                alert('Server error.');
            }
        };
        xhr.send(formData);
    });
});
</script>

<?php include '../includes/footer.php'; ?> 