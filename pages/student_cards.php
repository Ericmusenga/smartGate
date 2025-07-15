<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../login.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

// Get user type for role-based access
$user_type = get_user_type();

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? trim($_GET['department']) : '';
$year_filter = isset($_GET['year']) ? trim($_GET['year']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12; // Show 12 cards per page
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(s.registration_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
        $search_param = "%{$search}%";
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
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM students s $where_clause";
    $total_result = $db->fetch($count_sql, $params);
    $total_students = $total_result['total'];
    $total_pages = ceil($total_students / $per_page);
    
    // Get students with pagination
    $sql = "SELECT s.*, 
                   u.username, 
                   u.is_first_login,
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
    
    // Get years for filter
    $years = $db->fetchAll("SELECT DISTINCT year_of_study FROM students WHERE year_of_study IS NOT NULL ORDER BY year_of_study");
    
} catch (Exception $e) {
    $error_message = "Error loading students. Please try again.";
    error_log("Students loading error: " . $e->getMessage());
    $students = [];
    $total_students = 0;
    $total_pages = 0;
    $departments = [];
    $years = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Student Cards</div>
            <div class="page-subtitle">View students in card format - Click any card to view details</div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name, Reg No, Email...">
                    </div>
                    <div class="col-md-2">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                        <?php echo $department_filter === $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="year" class="form-label">Year</label>
                        <select class="form-select" id="year" name="year">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo htmlspecialchars($year['year_of_study']); ?>" 
                                        <?php echo $year_filter === $year['year_of_study'] ? 'selected' : ''; ?>>
                                    Year <?php echo htmlspecialchars($year['year_of_study']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="student_cards.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary mb-3">
            <span class="text-muted">
                Showing <?php echo count($students); ?> of <?php echo $total_students; ?> students
                <?php if (!empty($search) || !empty($department_filter) || !empty($year_filter) || $status_filter !== ''): ?>
                    (filtered)
                <?php endif; ?>
            </span>
        </div>

        <!-- Student Cards Grid -->
        <?php if (empty($students)): ?>
            <div class="no-results text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4>No students found</h4>
                <p class="text-muted">No students match your search criteria.</p>
                <a href="register_student.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add First Student
                </a>
            </div>
        <?php else: ?>
            <div class="student-cards-grid">
                <?php foreach ($students as $student): ?>
                    <div class="student-card" onclick="viewStudentDetails('<?php echo htmlspecialchars($student['registration_number']); ?>')">
                        <div class="card-header">
                            <div class="student-avatar">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="student-status">
                                <?php if ($student['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="student-name">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                            </h5>
                            <div class="student-reg-number">
                                <strong><?php echo htmlspecialchars($student['registration_number']); ?></strong>
                            </div>
                            <div class="student-info">
                                <div class="info-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($student['email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?php echo htmlspecialchars($student['department']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Year <?php echo htmlspecialchars($student['year_of_study']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-laptop"></i>
                                    <span><?php echo $student['device_count']; ?> device<?php echo $student['device_count'] != 1 ? 's' : ''; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="account-status">
                                <?php if ($student['username']): ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-check"></i> Account
                                    </span>
                                    <?php if ($student['is_first_login']): ?>
                                        <span class="badge bg-warning">First Login</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-times"></i> No Account
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Student cards pagination" class="mt-4">
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
</main>

<!-- Student Details Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viewFullProfileBtn">View Full Profile</button>
            </div>
        </div>
    </div>
</div>

<style>
.student-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.student-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    overflow: hidden;
}

.student-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #007bff;
}

.student-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.student-avatar {
    width: 50px;
    height: 50px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.student-status .badge {
    font-size: 0.75rem;
}

.student-card .card-body {
    padding: 1.5rem;
}

.student-name {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.student-reg-number {
    color: #007bff;
    font-size: 1rem;
    margin-bottom: 1rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 6px;
    text-align: center;
}

.student-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #6c757d;
}

.info-item i {
    width: 16px;
    color: #007bff;
}

.student-card .card-footer {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.account-status {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.account-status .badge {
    font-size: 0.7rem;
}

.results-summary {
    color: #6c757d;
    font-size: 0.9rem;
}

.no-results {
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .student-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .student-card {
        margin-bottom: 1rem;
    }
}

/* Modal styles */
.modal-dialog {
    max-width: 800px;
}

.modal-header {
    background: #343a40;
    color: white;
    border-bottom: 1px solid #495057;
}

.modal-header .btn-close {
    color: white;
    opacity: 0.8;
}

.modal-header .btn-close:hover {
    opacity: 1;
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
    margin-bottom: 1rem;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}

.detail-item {
    display: flex;
    margin-bottom: 0.75rem;
    align-items: center;
}

.detail-item label {
    font-weight: 600;
    min-width: 150px;
    color: #495057;
}

.detail-item .value {
    flex: 1;
    color: #212529;
}
</style>

<?php include '../includes/footer.php'; ?>

<script>
function viewStudentDetails(registrationNumber) {
    const modal = new bootstrap.Modal(document.getElementById('studentModal'));
    const modalBody = document.getElementById('studentModalBody');
    const viewFullProfileBtn = document.getElementById('viewFullProfileBtn');
    
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
    fetch(`view_student.php?ajax=true&registration_number=${encodeURIComponent(registrationNumber)}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${data.error}
                    </div>
                `;
                viewFullProfileBtn.style.display = 'none';
            } else {
                displayStudentDetails(data.student, modalBody);
                viewFullProfileBtn.style.display = 'inline-block';
                viewFullProfileBtn.onclick = () => {
                    window.open(`view_student.php?registration_number=${encodeURIComponent(registrationNumber)}`, '_blank');
                };
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error loading student details. Please try again.
                </div>
            `;
            viewFullProfileBtn.style.display = 'none';
        });
}

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
                        <span class="badge bg-primary">Year ${student.year_of_study}</span>
                    </div>
                </div>
                <div class="detail-item">
                    <label>Status:</label>
                    <div class="value">
                        <span class="badge ${student.is_active ? 'bg-success' : 'bg-danger'}">
                            ${student.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="detail-section">
                <h6><i class="fas fa-user-circle"></i> Account Information</h6>
                <div class="detail-item">
                    <label>Account Status:</label>
                    <div class="value">
                        ${student.username ? 
                            `<span class="badge bg-info"><i class="fas fa-check"></i> Account Created</span>` : 
                            `<span class="badge bg-secondary"><i class="fas fa-times"></i> No Account</span>`
                        }
                    </div>
                </div>
                ${student.username ? `
                    <div class="detail-item">
                        <label>Username:</label>
                        <div class="value">${student.username}</div>
                    </div>
                    ${student.is_first_login ? `
                        <div class="detail-item">
                            <label>Login Status:</label>
                            <div class="value">
                                <span class="badge bg-warning">First Login Required</span>
                            </div>
                        </div>
                    ` : ''}
                ` : ''}
                <div class="detail-item">
                    <label>Registered Devices:</label>
                    <div class="value">
                        <span class="badge bg-secondary">${student.device_count} device${student.device_count != 1 ? 's' : ''}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}
</script> 