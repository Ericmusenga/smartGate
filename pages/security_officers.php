<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../login.php');
}

// Check user type - only admin can access this page
$user_type = get_user_type();
if ($user_type !== 'admin') {
    redirect('../unauthorized.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

$error_message = '';
$success_message = '';
$security_officers = [];
$total_officers = 0;
$total_pages = 0;
$page = 1;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $security_code = trim($_POST['security_code'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$security_code || !$first_name || !$last_name || !$email) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $db = getDB();
            // Check for duplicate security code or email
            $exists = $db->fetch("SELECT id FROM security_officers WHERE security_code = ? OR email = ?", [$security_code, $email]);
            if ($exists) {
                $error_message = 'A security officer with this code or email already exists.';
            } else {
                // Insert into security_officers
                $db->query("INSERT INTO security_officers (security_code, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?)",
                    [$security_code, $first_name, $last_name, $email, $phone]);
                
                // Get the inserted officer's ID
                $officer_id = $db->lastInsertId();
                
                // Create user account for the security officer
                $role_id = $db->fetch("SELECT id FROM roles WHERE role_name = 'security'")['id'];
                $default_password = password_hash($security_code, PASSWORD_DEFAULT); // Default password is the security code
                
                $db->query("INSERT INTO users (username, password, email, first_name, last_name, role_id, security_officer_id, is_first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$security_code, $default_password, $email, $first_name, $last_name, $role_id, $officer_id, TRUE]);
                
                $success_message = 'Security officer registered successfully! User account created with default password (security code).';
            }
        } catch (Exception $e) {
            $error_message = 'Error registering security officer: ' . $e->getMessage();
        }
    }
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $officer_id = (int)$_GET['id'];
    
    try {
        $db = getDB();
        
        if ($action === 'toggle_status') {
            // Toggle active status
            $current_status = $db->fetch("SELECT is_active FROM security_officers WHERE id = ?", [$officer_id]);
            if ($current_status) {
                $new_status = $current_status['is_active'] ? 0 : 1;
                $db->query("UPDATE security_officers SET is_active = ? WHERE id = ?", [$new_status, $officer_id]);
                
                // Also update the corresponding user account
                $db->query("UPDATE users SET is_active = ? WHERE security_officer_id = ?", [$new_status, $officer_id]);
                
                $status_text = $new_status ? 'activated' : 'deactivated';
                $success_message = "Security officer $status_text successfully!";
            }
        } elseif ($action === 'delete') {
            // Check if officer has any associated logs
            $has_logs = $db->fetch("SELECT COUNT(*) as count FROM entry_exit_logs WHERE security_officer_id = ?", [$officer_id]);
            if ($has_logs['count'] > 0) {
                $error_message = "Cannot delete security officer. They have associated entry/exit logs.";
            } else {
                // Delete the officer and associated user account
                $db->query("DELETE FROM users WHERE security_officer_id = ?", [$officer_id]);
                $db->query("DELETE FROM security_officers WHERE id = ?", [$officer_id]);
                $success_message = "Security officer deleted successfully!";
            }
        }
    } catch (Exception $e) {
        $error_message = 'Error performing action: ' . $e->getMessage();
    }
}

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get search parameters
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

try {
    $db = getDB();
    
    // Very simple query - just get all security officers
    $sql = "SELECT * FROM security_officers ORDER BY created_at DESC";
    $all_officers = $db->fetchAll($sql);
    
    // Get total count
    $total_officers = count($all_officers);
    $total_pages = ceil($total_officers / $per_page);
    
    // Apply pagination manually
    $security_officers = array_slice($all_officers, $offset, $per_page);
    
    // Add user information and log count separately
    foreach ($security_officers as &$officer) {
        try {
            $user = $db->fetch("SELECT username, is_active, last_login FROM users WHERE security_officer_id = ?", [$officer['id']]);
            $officer['username'] = $user ? $user['username'] : null;
            $officer['user_active'] = $user ? $user['is_active'] : null;
            $officer['last_login'] = $user ? $user['last_login'] : null;
        } catch (Exception $e) {
            $officer['username'] = null;
            $officer['user_active'] = null;
            $officer['last_login'] = null;
        }
        
        $officer['log_count'] = 0; // Default to 0 for now
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading security officers: ' . $e->getMessage();
    $security_officers = [];
    $total_officers = 0;
    $total_pages = 0;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Security Officers</div>
            <div class="page-subtitle">Manage security personnel</div>
            <div class="page-actions">
                <button type="button" class="btn btn-primary" onclick="toggleRegistrationForm()">
                    <i class="fas fa-user-plus"></i> Add New Officer
                </button>
            </div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <!-- Debug Information (remove in production) -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Debug Info:</strong> Total Officers: <?php echo $total_officers; ?>, 
            Current Page: <?php echo $page; ?>, 
            Officers on this page: <?php echo count($security_officers); ?>
        </div>
        
        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by code, name, or email">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <a href="security_officers.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Registration Form (Hidden by default) -->
        <div id="registrationForm" class="card mb-4" style="display: none;">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus"></i> Register New Security Officer
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <input type="hidden" name="action" value="register">
                    <div class="col-md-6">
                        <label for="security_code" class="form-label">Security Code *</label>
                        <input type="text" id="security_code" name="security_code" class="form-control" required>
                        <div class="form-text">Unique identifier for the security officer</div>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control">
                        <div class="form-text">Optional contact number</div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> A user account will be automatically created with the security code as the username and default password.
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Register Officer
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleRegistrationForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_officers; ?></div>
                        <div class="stat-label">Total Officers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?php 
                            $active_count = array_reduce($security_officers, function($carry, $officer) {
                                return $carry + ($officer['is_active'] ? 1 : 0);
                            }, 0);
                            echo $active_count;
                            ?>
                        </div>
                        <div class="stat-label">Active Officers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?php 
                            $online_count = array_reduce($security_officers, function($carry, $officer) {
                                if ($officer['last_login']) {
                                    $last_login = strtotime($officer['last_login']);
                                    $now = time();
                                    return $carry + (($now - $last_login) < 3600 ? 1 : 0); // Online if logged in within 1 hour
                                }
                                return $carry;
                            }, 0);
                            echo $online_count;
                            ?>
                        </div>
                        <div class="stat-label">Online Now</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?php 
                            $total_logs = array_reduce($security_officers, function($carry, $officer) {
                                return $carry + $officer['log_count'];
                            }, 0);
                            echo $total_logs;
                            ?>
                        </div>
                        <div class="stat-label">Total Logs</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Officers Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-shield-alt"></i> Security Officers List
                    <?php if ($search || $status_filter !== ''): ?>
                        <span class="badge bg-secondary ms-2">Filtered</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($security_officers)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>No Security Officers Found</h5>
                        <p class="text-muted">
                            <?php if ($search || $status_filter !== ''): ?>
                                No officers match your search criteria.
                            <?php else: ?>
                                No security officers have been registered yet.
                            <?php endif; ?>
                        </p>
                        <a href="register_security.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add First Officer
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Security Code</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>User Account</th>
                                    <th>Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($security_officers as $officer): ?>
                                    <tr>
                                        <td>
                                            <code class="text-primary"><?php echo htmlspecialchars($officer['security_code']); ?></code>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-3">
                                                    <i class="fas fa-user-shield fa-2x text-primary"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?></div>
                                                    <small class="text-muted">ID: <?php echo $officer['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div><i class="fas fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($officer['email']); ?></div>
                                                <?php if ($officer['phone']): ?>
                                                    <div><i class="fas fa-phone text-muted me-1"></i> <?php echo htmlspecialchars($officer['phone']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($officer['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($officer['username']): ?>
                                                <div>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($officer['username']); ?></span>
                                                </div>
                                                <?php if ($officer['user_active']): ?>
                                                    <span class="badge bg-success">Account Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Account Inactive</span>
                                                <?php endif; ?>
                                                <?php if ($officer['last_login']): ?>
                                                    <div class="small text-muted">
                                                        Last login: <?php echo date('M j, g:i A', strtotime($officer['last_login'])); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="small text-muted">Never logged in</div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning">No Account</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <div class="fw-bold"><?php echo $officer['log_count']; ?></div>
                                                <small class="text-muted">Logs</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewOfficer(<?php echo $officer['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="edit_security.php?id=<?php echo $officer['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-<?php echo $officer['is_active'] ? 'warning' : 'success'; ?>" 
                                                        onclick="toggleStatus(<?php echo $officer['id']; ?>, '<?php echo $officer['is_active'] ? 'deactivate' : 'activate'; ?>')">
                                                    <i class="fas fa-<?php echo $officer['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                                <?php if ($officer['log_count'] == 0): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteOfficer(<?php echo $officer['id']; ?>, '<?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Security officers pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
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

<!-- View Officer Modal -->
<div class="modal fade" id="viewOfficerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Security Officer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="officerDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function toggleRegistrationForm() {
    const form = document.getElementById('registrationForm');
    if (form.style.display === 'none') {
        form.style.display = 'block';
        // Scroll to form
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        form.style.display = 'none';
    }
}

function viewOfficer(officerId) {
    // Load officer details via AJAX
    fetch(`security_officer_ajax.php?id=${officerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('officerDetails').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('viewOfficerModal')).show();
            } else {
                alert('Error loading officer details: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading officer details');
        });
}

function toggleStatus(officerId, action) {
    if (confirm(`Are you sure you want to ${action} this security officer?`)) {
        window.location.href = `security_officers.php?action=toggle_status&id=${officerId}`;
    }
}

function deleteOfficer(officerId, officerName) {
    if (confirm(`Are you sure you want to delete security officer "${officerName}"? This action cannot be undone.`)) {
        window.location.href = `security_officers.php?action=delete&id=${officerId}`;
    }
}

// Auto-hide success message after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.display = 'none';
        }, 5000);
    }
});
</script>

<?php include '../includes/footer.php'; ?> 