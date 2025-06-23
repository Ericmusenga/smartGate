<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../login.php');
}

// Check user type - only admin and security can access this page
$user_type = get_user_type();
if (!in_array($user_type, ['admin', 'security'])) {
    redirect('../unauthorized.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

$error_message = '';
$success_message = '';
$logs = [];
$total_logs = 0;
$total_pages = 0;
$current_page = 1;
$filters = [];

// Handle manual entry submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'manual_entry') {
        try {
            $db = getDB();
            
            $registration_number = trim($_POST['registration_number']);
            $status = $_POST['status'];
            $gate_number = (int)$_POST['gate_number'];
            $entry_method = $_POST['entry_method'];
            $notes = trim($_POST['notes']);
            $entry_time = $_POST['entry_time'];
            $exit_time = $_POST['exit_time'];
            
            // Validate required fields
            if (empty($registration_number)) {
                throw new Exception('Registration number is required.');
            }
            
            if (empty($status)) {
                throw new Exception('Status is required.');
            }
            
            if ($gate_number <= 0) {
                throw new Exception('Valid gate number is required.');
            }
            
            // Find student by registration number
            $student = $db->fetch("SELECT s.*, u.id as user_id FROM students s 
                                  LEFT JOIN users u ON s.id = u.student_id 
                                  WHERE s.registration_number = ?", [$registration_number]);
            
            if (!$student) {
                throw new Exception('Student with registration number "' . $registration_number . '" not found.');
            }
            
            // Insert the log entry
            $log_data = [
                'user_id' => $student['user_id'],
                'status' => $status,
                'gate_number' => $gate_number,
                'entry_method' => $entry_method,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($status === 'entered' && !empty($entry_time)) {
                $log_data['entry_time'] = $entry_time;
            }
            
            if ($status === 'exited' && !empty($exit_time)) {
                $log_data['exit_time'] = $exit_time;
            }
            
            $db->query("INSERT INTO entry_exit_logs (user_id, status, gate_number, entry_method, notes, entry_time, exit_time, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                       [$log_data['user_id'], $log_data['status'], $log_data['gate_number'], 
                        $log_data['entry_method'], $log_data['notes'], $log_data['entry_time'] ?? null, 
                        $log_data['exit_time'] ?? null, $log_data['created_at']]);
            
            $success_message = "Manual entry logged successfully for " . $student['first_name'] . " " . $student['last_name'];
            
        } catch (Exception $e) {
            $error_message = 'Error creating manual entry: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$filters['search'] = $_GET['search'] ?? '';
$filters['date_from'] = $_GET['date_from'] ?? '';
$filters['date_to'] = $_GET['date_to'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$filters['gate'] = $_GET['gate'] ?? '';
$filters['method'] = $_GET['method'] ?? '';
$filters['limit'] = min((int)($_GET['limit'] ?? 50), 100);
$current_page = max(1, (int)($_GET['page'] ?? 1));

try {
    $db = getDB();
    
    // Build WHERE clause for filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.registration_number LIKE ?)";
        $search_term = '%' . $filters['search'] . '%';
        $params = array_merge($params, [$search_term, $search_term, $search_term]);
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(eel.created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(eel.created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['status'])) {
        $where_conditions[] = "eel.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['gate'])) {
        $where_conditions[] = "eel.gate_number = ?";
        $params[] = $filters['gate'];
    }
    
    if (!empty($filters['method'])) {
        $where_conditions[] = "eel.entry_method = ?";
        $params[] = $filters['method'];
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM entry_exit_logs eel 
        LEFT JOIN users u ON eel.user_id = u.id 
        LEFT JOIN students s ON u.student_id = s.id
        $where_clause
    ";
    
    $count_result = $db->fetch($count_sql, $params);
    $total_logs = $count_result['total'];
    $total_pages = ceil($total_logs / $filters['limit']);
    $current_page = min($current_page, $total_pages);
    $offset = ($current_page - 1) * $filters['limit'];
    
    $limit = (int)$filters['limit'];
    $offset = (int)$offset;
    
    // Get logs data
    $logs_sql = "
        SELECT 
            eel.id,
            eel.entry_time,
            eel.exit_time,
            eel.gate_number,
            eel.entry_method,
            eel.status,
            eel.notes,
            eel.created_at,
            u.first_name,
            u.last_name,
            s.registration_number,
            s.department,
            s.program,
            rc.card_number
        FROM entry_exit_logs eel 
        LEFT JOIN users u ON eel.user_id = u.id 
        LEFT JOIN students s ON u.student_id = s.id
        LEFT JOIN rfid_cards rc ON eel.rfid_card_id = rc.id
        $where_clause
        ORDER BY eel.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $logs = $db->fetchAll($logs_sql, $params);
    
    // Get filter options for dropdowns
    $gates = $db->fetchAll("SELECT DISTINCT gate_number FROM entry_exit_logs ORDER BY gate_number");
    $methods = $db->fetchAll("SELECT DISTINCT entry_method FROM entry_exit_logs ORDER BY entry_method");
    
} catch (Exception $e) {
    $error_message = 'Error loading logs: ' . $e->getMessage();
    $logs = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Entry/Exit Logs</div>
            <div class="page-subtitle">Manage entry and exit activities</div>
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
        
        <!-- Manual Entry Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Add Manual Entry/Exit</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <input type="hidden" name="action" value="manual_entry">
                    
                    <div class="col-md-3">
                        <label for="registration_number" class="form-label">Registration Number *</label>
                        <input type="text" id="registration_number" name="registration_number" class="form-control" 
                               placeholder="Enter registration number" required>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status *</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="entered">Entered</option>
                            <option value="exited">Exited</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="gate_number" class="form-label">Gate Number *</label>
                        <select id="gate_number" name="gate_number" class="form-select" required>
                            <option value="">Select Gate</option>
                            <option value="1">Gate 1</option>
                            <option value="2">Gate 2</option>
                            <option value="3">Gate 3</option>
                            <option value="4">Gate 4</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="entry_method" class="form-label">Method</label>
                        <select id="entry_method" name="entry_method" class="form-select">
                            <option value="manual">Manual</option>
                            <option value="rfid">RFID Card</option>
                            <option value="biometric">Biometric</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="entry_time" class="form-label">Entry Time</label>
                        <input type="datetime-local" id="entry_time" name="entry_time" class="form-control">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="exit_time" class="form-label">Exit Time</label>
                        <input type="datetime-local" id="exit_time" name="exit_time" class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="2" 
                                  placeholder="Additional notes or comments"></textarea>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Entry/Exit
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Search Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-search"></i> Quick Student Search</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="quick_search" class="form-label">Search by Registration Number</label>
                        <div class="input-group">
                            <input type="text" id="quick_search" class="form-control" 
                                   placeholder="Enter registration number">
                            <button class="btn btn-outline-primary" type="button" onclick="searchStudent()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="student_info" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-filter"></i> Search & Filters</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['search']); ?>" 
                               placeholder="Name, Registration #, etc.">
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All</option>
                            <option value="entered" <?php echo $filters['status'] === 'entered' ? 'selected' : ''; ?>>Entered</option>
                            <option value="exited" <?php echo $filters['status'] === 'exited' ? 'selected' : ''; ?>>Exited</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="gate" class="form-label">Gate</label>
                        <select id="gate" name="gate" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($gates as $gate): ?>
                                <option value="<?php echo $gate['gate_number']; ?>" 
                                        <?php echo $filters['gate'] == $gate['gate_number'] ? 'selected' : ''; ?>>
                                    Gate <?php echo $gate['gate_number']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label for="limit" class="form-label">Per Page</label>
                        <select id="limit" name="limit" class="form-select">
                            <option value="25" <?php echo $filters['limit'] == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $filters['limit'] == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $filters['limit'] == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="?" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Entry/Exit Logs Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-list"></i> Entry/Exit Records (<?php echo $total_logs; ?> total)</h3>
                <?php if ($total_pages > 1): ?>
                    <span class="badge bg-secondary">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4>No logs found</h4>
                        <p class="text-muted">No entry/exit logs match your current filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Student Name</th>
                                    <th>Registration #</th>
                                    <th>Department</th>
                                    <th>Gate</th>
                                    <th>Status</th>
                                    <th>Method</th>
                                    <th>Entry Time</th>
                                    <th>Exit Time</th>
                                    <th>RFID Card</th>
                                    <th>Date/Time</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['registration_number']); ?></td>
                                        <td>
                                            <?php if (!empty($log['department'])): ?>
                                                <?php echo htmlspecialchars($log['department']); ?>
                                                <?php if (!empty($log['program'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['program']); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">Gate <?php echo $log['gate_number']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $log['status'] === 'entered' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($log['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo strtoupper($log['entry_method']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($log['entry_time']): ?>
                                                <i class="fas fa-sign-in-alt text-success"></i>
                                                <?php echo date('M j, H:i', strtotime($log['entry_time'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log['exit_time']): ?>
                                                <i class="fas fa-sign-out-alt text-warning"></i>
                                                <?php echo date('M j, H:i', strtotime($log['exit_time'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['card_number'])): ?>
                                                <i class="fas fa-credit-card"></i>
                                                <small><?php echo htmlspecialchars($log['card_number']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo date('M j, Y', strtotime($log['created_at'])); ?>
                                                <br>
                                                <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['notes'])): ?>
                                                <small><?php echo htmlspecialchars($log['notes']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Logs pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">Next</a>
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

<script>
// Search student by registration number
function searchStudent() {
    const regNumber = document.getElementById('quick_search').value.trim();
    if (!regNumber) {
        alert('Please enter a registration number');
        return;
    }
    
    fetch(`../api/entry_exit/student_info.php?registration_number=${encodeURIComponent(regNumber)}`)
        .then(response => response.json())
        .then(data => {
            const studentInfo = document.getElementById('student_info');
            if (data.success) {
                const student = data.student;
                studentInfo.innerHTML = `
                    <div class="alert alert-success">
                        <h6>Student Found:</h6>
                        <strong>${student.first_name} ${student.last_name}</strong><br>
                        <small>Registration: ${student.registration_number}</small><br>
                        <small>Department: ${student.department}</small><br>
                        <small>Program: ${student.program}</small>
                    </div>
                `;
                
                // Auto-fill the manual entry form
                document.getElementById('registration_number').value = student.registration_number;
            } else {
                studentInfo.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Student not found with registration number: ${regNumber}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('student_info').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> Error searching for student
                </div>
            `;
        });
}

// Auto-fill entry/exit times based on status
document.getElementById('status').addEventListener('change', function() {
    const status = this.value;
    const entryTime = document.getElementById('entry_time');
    const exitTime = document.getElementById('exit_time');
    
    if (status === 'entered') {
        entryTime.value = new Date().toISOString().slice(0, 16);
        exitTime.value = '';
    } else if (status === 'exited') {
        exitTime.value = new Date().toISOString().slice(0, 16);
        entryTime.value = '';
    }
});
</script>

<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.table th {
    font-weight: 600;
    background: #343a40;
    color: white;
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

.form-label {
    font-weight: 500;
}

.alert {
    border-radius: 8px;
}
</style>

<?php include '../includes/footer.php'; ?> 