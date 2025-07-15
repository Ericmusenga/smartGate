<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in()) {
    redirect('../login.php');
}

if (get_user_type() !== 'admin') {
    redirect('../login.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

$error_message = '';
$success_message = '';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $student_id = (int)$_GET['id'];
    try {
        $db = getDB();
        $db->query("DELETE FROM students WHERE id = ?", [$student_id]);
        $success_message = "Student deleted successfully!";
    } catch (Exception $e) {
        $error_message = "Error deleting student. Please try again.";
        error_log("Student deletion error: " . $e->getMessage());
    }
}

// Handle toggle active status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $student_id = (int)$_GET['id'];
    try {
        $db = getDB();
        $current_status = $db->fetch("SELECT is_active FROM students WHERE id = ?", [$student_id]);
        if ($current_status) {
            $new_status = $current_status['is_active'] ? 0 : 1;
            $db->query("UPDATE students SET is_active = ? WHERE id = ?", [$new_status, $student_id]);
            $status_text = $new_status ? 'activated' : 'deactivated';
            $success_message = "Student $status_text successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Error updating student status. Please try again.";
        error_log("Student status update error: " . $e->getMessage());
    }
}

// Get search and filter parameters
$search = sanitize_input($_GET['search'] ?? '');
$department_filter = sanitize_input($_GET['department'] ?? '');
$year_filter = sanitize_input($_GET['year'] ?? '');
$status_filter = sanitize_input($_GET['status'] ?? '');

// Build WHERE conditions for main query (with table aliases)
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $search_param = "%$search%";
    $where_conditions[] = "(s.registration_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($department_filter)) {
    $where_conditions[] = "s.department = ?";
    $params[] = $department_filter;
}

if (!empty($year_filter)) {
    $where_conditions[] = "s.year_of_study = ?";
    $params[] = $year_filter;
}

if ($status_filter !== '') {
    $where_conditions[] = "s.is_active = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Build WHERE conditions for count query (without table aliases)
$count_where_conditions = [];
$count_params = [];

if (!empty($search)) {
    $search_param = "%$search%";
    $count_where_conditions[] = "(registration_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($department_filter)) {
    $count_where_conditions[] = "department = ?";
    $count_params[] = $department_filter;
}

if (!empty($year_filter)) {
    $count_where_conditions[] = "year_of_study = ?";
    $count_params[] = $year_filter;
}

if ($status_filter !== '') {
    $count_where_conditions[] = "is_active = ?";
    $count_params[] = $status_filter;
}

$count_where_clause = !empty($count_where_conditions) ? 'WHERE ' . implode(' AND ', $count_where_conditions) : '';

// Get students with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM students $count_where_clause";
    $total_result = $db->fetch($count_sql, $count_params);
    $total_students = $total_result['total'];
    $total_pages = ceil($total_students / $per_page);
    
    // Get students - Fix: Use LIMIT and OFFSET directly in SQL, not as parameters
    $sql = "SELECT s.*, 
                   u.username, 
                   u.is_first_login,
                   u.last_login,
                   COUNT(d.id) as device_count
            FROM students s 
            LEFT JOIN users u ON s.id = u.student_id 
            LEFT JOIN devices d ON u.id = d.user_id AND d.is_registered = TRUE
            $where_clause 
            GROUP BY s.id 
            ORDER BY s.registration_number 
            LIMIT $per_page OFFSET $offset";
    
    $students = $db->fetchAll($sql, $params);
    
    // Get departments for filter
    $departments = $db->fetchAll("SELECT DISTINCT department FROM students WHERE department IS NOT NULL ORDER BY department");
    
} catch (Exception $e) {
    $error_message = "Error loading students. Please try again.";
    error_log("Students loading error: " . $e->getMessage());
    $students = [];
    $total_students = 0;
    $total_pages = 0;
    $departments = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Students Management</div>
            <div class="page-subtitle">View and manage all registered students</div>
        </div>
        
        <!-- Quick View Student Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-search"></i> Quick View Student</h5>
            </div>
            <div class="card-body">
                <form id="quickViewForm" class="quick-view-form">
                    <div class="form-row">
                        <div class="col-md-4">
                            <label for="searchType">Search by:</label>
                            <select class="form-control" id="searchType" name="searchType">
                                <option value="registration_number">Registration Number</option>
                                <option value="student_id">Student ID</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="searchValue">Enter value:</label>
                            <input type="text" class="form-control" id="searchValue" name="searchValue" 
                                   placeholder="Enter registration number or student ID" required>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </div>
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
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="register_student.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New Student
            </a>
            <a href="students.php?export=csv" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
        
        <!-- Search and Filters -->
        <div class="search-filters-compact">
            <form method="GET" action="" class="search-form-compact">
                <div class="search-row">
                    <div class="search-item">
                        <input type="text" id="search" name="search" class="form-control form-control-sm" 
                               placeholder="Search students..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="search-item">
                        <select id="department" name="department" class="form-control form-control-sm">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                        <?php echo $department_filter === $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-item">
                        <select id="year" name="year" class="form-control form-control-sm">
                            <option value="">All Years</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $year_filter == $i ? 'selected' : ''; ?>>
                                    Year <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="search-item">
                        <select id="status" name="status" class="form-control form-control-sm">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="search-item">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="search-item">
                        <a href="students.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Results Summary -->
        <div class="results-summary">
            <p>Showing <?php echo count($students); ?> of <?php echo $total_students; ?> students</p>
        </div>
        
        <!-- Students Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Students List</h3>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <div class="no-results">
                        <i class="fas fa-users"></i>
                        <h4>No students found</h4>
                        <p>No students match your search criteria.</p>
                        <a href="register_student.php" class="btn btn-primary">Add First Student</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Registration #</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Account</th>
                                    <th>Devices</th>
                                    <th>S.Number</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (
                                    $students as $student): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['registration_number']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="student-name">
                                                <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                <?php if ($student['gender']): ?>
                                                    <span class="badge bg-secondary ms-1"><?php echo ucfirst($student['gender']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>">
                                                <?php echo htmlspecialchars($student['email']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['department']); ?></td>
                                        <td><?php echo htmlspecialchars($student['program']); ?></td>
                                        <td>
                                            <span class="badge bg-primary">Year <?php echo $student['year_of_study']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($student['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['username']): ?>
                                                <span class="badge bg-info"><i class="fas fa-check"></i> Account</span>
                                                <?php if ($student['is_first_login']): ?>
                                                    <span class="text-warning">First login</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-times"></i> No Account</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="device-count">
                                                <?php echo $student['device_count']; ?> device<?php echo $student['device_count'] != 1 ? 's' : ''; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['serial_number']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="#" 
                                                   class="btn btn-sm btn-info view-student-btn" 
                                                   title="View Details"
                                                   data-student-id="<?php echo $student['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_student.php?id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="students.php?action=toggle_status&id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-sm <?php echo $student['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                                   title="<?php echo $student['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                                   onclick="return confirm('Are you sure you want to <?php echo $student['is_active'] ? 'deactivate' : 'activate'; ?> this student?')">
                                                    <i class="fas fa-<?php echo $student['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                </a>
                                                <a href="students.php?action=delete&id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <nav aria-label="Students pagination">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
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
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Student Details Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" role="dialog" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel">
                    <i class="fas fa-user"></i> Student Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading student details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="editStudentBtn" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Student
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" role="dialog" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">
                    <i class="fas fa-user-edit"></i> Edit Student
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editStudentModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading student information...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveStudentBtn" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.action-buttons {
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Compact Search & Filters */
.search-filters-compact {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.search-form-compact {
    margin: 0;
}

.search-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.search-item {
    flex: 1;
    min-width: 120px;
}

.search-item:last-child,
.search-item:nth-last-child(2) {
    flex: 0 0 auto;
}

.form-control-sm {
    height: 35px;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
    height: 35px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.search-form {
    margin-bottom: 0;
}

.results-summary {
    margin: 1rem 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.table {
    margin-bottom: 0;
    border-collapse: collapse;
    width: 100%;
}

.table th {
    background: #343a40;
    color: white;
    border-bottom: 2px solid #495057;
    font-weight: 600;
    padding: 12px 8px;
    text-align: left;
    font-size: 0.9rem;
}

.table td {
    padding: 12px 8px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.table tbody tr:nth-child(even) {
    background-color: #ffffff;
}

.table tbody tr:nth-child(odd) {
    background-color: #f8f9fa;
}

.student-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.gender-badge {
    background: #e9ecef;
    color: #6c757d;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
}

.year-badge {
    background: #007bff;
    color: white;
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.account-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.account-exists {
    background: #d1ecf1;
    color: #0c5460;
}

.account-missing {
    background: #f8d7da;
    color: #721c24;
}

.device-count {
    color: #6c757d;
    font-size: 0.9rem;
}

.action-buttons .btn {
    margin: 0 0.2rem;
}

.no-results {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.no-results i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #dee2e6;
}

.no-results h4 {
    margin-bottom: 0.5rem;
}

.pagination-wrapper {
    margin-top: 2rem;
    display: flex;
    justify-content: center;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
}

.alert-danger {
    background: rgba(231, 76, 60, 0.1);
    border-color: #e74c3c;
    color: #e74c3c;
}

.alert-success {
    background: rgba(39, 174, 96, 0.1);
    border-color: #27ae60;
    color: #27ae60;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .search-row {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .search-item {
        width: 100%;
        min-width: auto;
    }
    
    .search-item:last-child,
    .search-item:nth-last-child(2) {
        flex: 1;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Modal Styles */
.modal-dialog {
    max-width: 800px;
}

.modal-header {
    background: #343a40;
    color: white;
    border-bottom: 1px solid #495057;
}

.modal-header .close {
    color: white;
    opacity: 0.8;
}

.modal-header .close:hover {
    opacity: 1;
}

.modal-body {
    padding: 1.5rem;
}

.student-details-modal {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.detail-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
}

.detail-section h6 {
    color: #495057;
    margin-bottom: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-item {
    margin-bottom: 0.75rem;
}

.detail-item:last-child {
    margin-bottom: 0;
}

.detail-item label {
    font-weight: 600;
    color: #495057;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
    display: block;
}

.detail-item .value {
    color: #212529;
    font-size: 0.9rem;
}

.detail-item .value a {
    color: #007bff;
    text-decoration: none;
}

.detail-item .value a:hover {
    text-decoration: underline;
}

.year-badge {
    background: #007bff;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge {
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.account-badge {
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.account-active {
    background: #d1ecf1;
    color: #0c5460;
}

.account-first-login {
    background: #fff3cd;
    color: #856404;
}

.account-missing {
    background: #f8d7da;
    color: #721c24;
}

.device-count-badge {
    background: #e9ecef;
    color: #495057;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.text-muted {
    color: #6c757d !important;
}

@media (max-width: 768px) {
    .student-details-modal {
        grid-template-columns: 1fr;
    }
}

/* Quick View Form Styles */
.quick-view-form {
    margin-bottom: 0;
}

.quick-view-form .form-row {
    align-items: end;
}

.quick-view-form .form-control {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
}

.quick-view-form .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.quick-view-form label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.quick-view-form .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.quick-view-form .btn i {
    margin-right: 0.25rem;
}

@media (max-width: 768px) {
    .quick-view-form .form-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .quick-view-form .col-md-2,
    .quick-view-form .col-md-4,
    .quick-view-form .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .quick-view-form .col-md-2:last-child {
        margin-bottom: 0;
    }
}
</style>

<script>
// Student Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    let currentStudentId = null;
    
    // Handle view student button clicks
    document.querySelectorAll('.view-student-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('data-student-id');
            loadStudentDetails(studentId);
        });
    });
    
    // Handle edit student button clicks in view modal
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'editStudentBtn') {
            e.preventDefault();
            if (currentStudentId) {
                openEditModal(currentStudentId);
            }
        }
    });
    
    // Handle edit student button clicks in table
    document.querySelectorAll('.btn-warning[title="Edit"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.closest('tr').querySelector('.view-student-btn').getAttribute('data-student-id');
            openEditModal(studentId);
        });
    });
    
    // Handle save button click
    document.getElementById('saveStudentBtn').addEventListener('click', function() {
        saveStudentChanges();
    });
    
    // Handle quick view form submission
    const quickViewForm = document.getElementById('quickViewForm');
    if (quickViewForm) {
        quickViewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Quick view form submitted');
            
            const searchType = document.getElementById('searchType').value;
            const searchValue = document.getElementById('searchValue').value.trim();
            
            console.log('Search type:', searchType);
            console.log('Search value:', searchValue);
            
            if (!searchValue) {
                alert('Please enter a value to search for.');
                return;
            }
            
            // Search for student by registration number or ID
            searchAndViewStudent(searchType, searchValue);
        });
    }
    
    // Open edit modal
    function openEditModal(studentId) {
        currentStudentId = studentId;
        const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
        const modalBody = document.getElementById('editStudentModalBody');
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading student information...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Load student data for editing
        console.log('Loading student data for ID:', studentId);
        fetch(`edit_student_ajax.php?id=${studentId}&ajax=true`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.error) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    `;
                } else if (data.student) {
                    displayEditForm(data.student, modalBody);
                } else {
                    modalBody.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Unexpected response format.
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading student:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading student information: ${error.message}
                    </div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                `;
            });
    }
    
    // Display edit form
    function displayEditForm(student, container) {
        container.innerHTML = `
            <form id="editStudentForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_registration_number" class="form-label">Registration Number *</label>
                            <input type="text" id="edit_registration_number" name="registration_number" class="form-control" 
                                   value="${student.registration_number}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_email" class="form-label">Email Address *</label>
                            <input type="email" id="edit_email" name="email" class="form-control" 
                                   value="${student.email}" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_first_name" class="form-label">First Name *</label>
                            <input type="text" id="edit_first_name" name="first_name" class="form-control" 
                                   value="${student.first_name}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_last_name" class="form-label">Last Name *</label>
                            <input type="text" id="edit_last_name" name="last_name" class="form-control" 
                                   value="${student.last_name}" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_department" class="form-label">Department *</label>
                            <input type="text" id="edit_department" name="department" class="form-control" 
                                   value="${student.department}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_program" class="form-label">Program *</label>
                            <input type="text" id="edit_program" name="program" class="form-control" 
                                   value="${student.program}" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_year_of_study" class="form-label">Year of Study *</label>
                            <select id="edit_year_of_study" name="year_of_study" class="form-control" required>
                                ${[1,2,3,4,5,6].map(year => 
                                    `<option value="${year}" ${student.year_of_study == year ? 'selected' : ''}>Year ${year}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" id="edit_is_active" name="is_active" class="form-check-input" 
                                       ${student.is_active ? 'checked' : ''}>
                                <label for="edit_is_active" class="form-check-label">Active Student</label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        `;
    }
    
    // Save student changes
    function saveStudentChanges() {
        if (!currentStudentId) return;
        
        const form = document.getElementById('editStudentForm');
        const formData = new FormData(form);
        formData.append('id', currentStudentId);
        
        const saveBtn = document.getElementById('saveStudentBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        saveBtn.disabled = true;
        
        fetch('edit_student_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Save response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Save response data:', data);
            const modalBody = document.getElementById('editStudentModalBody');
            
            if (data.success) {
                modalBody.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> ${data.success}
                    </div>
                `;
                setTimeout(() => location.reload(), 1500);
            } else if (data.error) {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${data.error}
                    </div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-secondary" onclick="openEditModal(${currentStudentId})">Try Again</button>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Unexpected response format.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error saving student:', error);
            const modalBody = document.getElementById('editStudentModalBody');
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error saving student: ${error.message}
                </div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-secondary" onclick="openEditModal(${currentStudentId})">Try Again</button>
                </div>
            `;
        })
        .finally(() => {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        });
    }
    
    // Search for student and open modal
    function searchAndViewStudent(searchType, searchValue) {
        console.log('Searching for student:', searchType, searchValue);
        
        const modal = new bootstrap.Modal(document.getElementById('studentModal'));
        const modalBody = document.getElementById('studentModalBody');
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Searching for student...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Build search URL
        let searchUrl = 'view_student.php?ajax=true';
        if (searchType === 'registration_number') {
            searchUrl += `&registration_number=${encodeURIComponent(searchValue)}`;
        } else {
            searchUrl += `&id=${encodeURIComponent(searchValue)}`;
        }
        
        console.log('Search URL:', searchUrl);
        
        // Fetch student details
        fetch(searchUrl)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.error) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    `;
                } else {
                    displayStudentDetails(data.student, modalBody);
                    const editBtn = document.getElementById('editStudentBtn');
                    editBtn.href = `edit_student.php?id=${data.student.id}`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error searching for student.
                    </div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                `;
            });
    }
    
    // Load student details via AJAX
    function loadStudentDetails(studentId) {
        currentStudentId = studentId;
        const modal = new bootstrap.Modal(document.getElementById('studentModal'));
        const modalBody = document.getElementById('studentModalBody');
        const editBtn = document.getElementById('editStudentBtn');
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading student details...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Fetch student details
        fetch(`view_student.php?id=${studentId}&ajax=true`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        </div>
                    `;
                } else {
                    displayStudentDetails(data.student, modalBody);
                    editBtn.href = `edit_student.php?id=${studentId}`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading student details.
                    </div>
                `;
            });
    }
    
    // Display student details in modal
    function displayStudentDetails(student, container) {
        container.innerHTML = `
            <div class="student-details-modal">
                <!-- Personal Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-user"></i> Personal Information</h6>
                    <div class="detail-item">
                        <label>Registration Number:</label>
                        <div class="value">${student.registration_number}</div>
                    </div>
                    <div class="detail-item">
                        <label>Full Name:</label>
                        <div class="value">${student.first_name} ${student.last_name}</div>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <div class="value">
                            <a href="mailto:${student.email}">${student.email}</a>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Phone:</label>
                        <div class="value">
                            ${student.phone ? `<a href="tel:${student.phone}">${student.phone}</a>` : '<span class="text-muted">Not provided</span>'}
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Gender:</label>
                        <div class="value">
                            ${student.gender ? student.gender.charAt(0).toUpperCase() + student.gender.slice(1) : '<span class="text-muted">Not specified</span>'}
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Date of Birth:</label>
                        <div class="value">
                            ${student.date_of_birth ? new Date(student.date_of_birth).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : '<span class="text-muted">Not provided</span>'}
                        </div>
                    </div>
                </div>
                
                <!-- Academic Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-graduation-cap"></i> Academic Information</h6>
                    <div class="detail-item">
                        <label>Department:</label>
                        <div class="value">${student.department}</div>
                    </div>
                    <div class="detail-item">
                        <label>Program:</label>
                        <div class="value">${student.program}</div>
                    </div>
                    <div class="detail-item">
                        <label>Year of Study:</label>
                        <div class="value">
                            <span class="year-badge">Year ${student.year_of_study}</span>
                        </div>
                         ${student.S.Number ? `
                        <div class="detail-item">
                            <label>Username:</label>
                            <div class="value">${student.S.Number}</div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <div class="value">
                            <span class="status-badge ${student.is_active ? 'status-active' : 'status-inactive'}">
                                ${student.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-key"></i> Account Information</h6>
                    ${student.username ? `
                        <div class="detail-item">
                            <label>Username:</label>
                            <div class="value">${student.username}</div>
                        </div>
                        <div class="detail-item">
                            <label>Account Status:</label>
                            <div class="value">
                                <span class="account-badge ${student.is_first_login ? 'account-first-login' : 'account-active'}">
                                    <i class="fas fa-${student.is_first_login ? 'exclamation-triangle' : 'check'}"></i>
                                    ${student.is_first_login ? 'First Login Required' : 'Active'}
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Last Login:</label>
                            <div class="value">
                                ${student.last_login ? new Date(student.last_login).toLocaleString('en-US') : '<span class="text-muted">Never logged in</span>'}
                            </div>
                        </div>
                    ` : `
                        <div class="detail-item">
                            <span class="account-badge account-missing">
                                <i class="fas fa-times"></i> No Login Account Created
                            </span>
                        </div>
                    `}
                </div>
                
                <!-- System Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-info-circle"></i> System Information</h6>
                    <div class="detail-item">
                        <label>Registered Devices:</label>
                        <div class="value">
                            <span class="device-count-badge">
                                ${student.device_count} device${student.device_count != 1 ? 's' : ''}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Student Since:</label>
                        <div class="value">
                            ${new Date(student.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
});
</script>

<?php include '../includes/footer.php'; ?>