<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../login.php');
}

// Check user type - admins and security can access this page
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
$cards = [];
$total_cards = 0;
$total_pages = 0;
$page = 1;

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $card_id = (int)$_GET['id'];
    
    try {
        $db = getDB();
        
        switch ($action) {
            case 'toggle_status':
                $card = $db->fetch("SELECT card_number, is_active FROM rfid_cards WHERE id = ?", [$card_id]);
                if ($card) {
                    $new_status = $card['is_active'] ? 0 : 1;
                    $db->query("UPDATE rfid_cards SET is_active = ? WHERE id = ?", [$new_status, $card_id]);
                    $status_text = $new_status ? 'activated' : 'deactivated';
                    $success_message = "Card '{$card['card_number']}' has been {$status_text} successfully.";
                } else {
                    $error_message = "Card not found.";
                }
                break;
                
            case 'delete':
                $card = $db->fetch("SELECT card_number FROM rfid_cards WHERE id = ?", [$card_id]);
                if ($card) {
                    $db->query("DELETE FROM rfid_cards WHERE id = ?", [$card_id]);
                    $success_message = "Card '{$card['card_number']}' has been deleted successfully.";
                } else {
                    $error_message = "Card not found.";
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = 'Error performing action: ' . $e->getMessage();
    }
}

// Pagination
$per_page = ITEMS_PER_PAGE;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Search and filters
$search = sanitize_input($_GET['search'] ?? '');
$card_type_filter = sanitize_input($_GET['card_type'] ?? '');
$status_filter = sanitize_input($_GET['status'] ?? '');
$student_filter = sanitize_input($_GET['student'] ?? '');

try {
    $db = getDB();
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(rc.card_number LIKE ? OR s.registration_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($card_type_filter)) {
        $where_conditions[] = "rc.card_type = ?";
        $params[] = $card_type_filter;
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "rc.is_active = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($student_filter)) {
        $where_conditions[] = "(s.registration_number LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ?)";
        $student_param = "%{$student_filter}%";
        $params[] = $student_param;
        $params[] = $student_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM rfid_cards rc 
                  JOIN students s ON rc.student_id = s.id 
                  $where_clause";
    $total_result = $db->fetch($count_sql, $params);
    $total_cards = $total_result['total'];
    $total_pages = ceil($total_cards / $per_page);
    
    // Get cards with pagination
    $sql = "SELECT rc.*, s.registration_number, s.first_name, s.last_name, s.email, s.department, s.program, s.year_of_study
            FROM rfid_cards rc 
            JOIN students s ON rc.student_id = s.id 
            $where_clause 
            ORDER BY rc.issued_date DESC 
            LIMIT $per_page OFFSET $offset";
    
    $cards = $db->fetchAll($sql, $params);
    
} catch (Exception $e) {
    $error_message = 'Error loading cards: ' . $e->getMessage();
    $cards = [];
    $total_cards = 0;
    $total_pages = 0;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Cards Management</div>
            <div class="page-subtitle">View and manage all RFID cards</div>
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
        <?php if ($user_type === 'admin'): ?>
        <div class="action-buttons">
            <a href="../cards/register_card.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Register New Card
            </a>
            <a href="cards.php?export=csv" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Search and Filters -->
        <div class="search-filters-compact">
            <form method="GET" action="" class="search-form-compact">
                <div class="search-row">
                    <div class="search-item">
                        <input type="text" id="search" name="search" class="form-control form-control-sm" 
                               placeholder="Search cards..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="search-item">
                        <select id="card_type" name="card_type" class="form-control form-control-sm">
                            <option value="">All Types</option>
                            <option value="student_id" <?php echo $card_type_filter === 'student_id' ? 'selected' : ''; ?>>Student ID</option>
                            <option value="library_card" <?php echo $card_type_filter === 'library_card' ? 'selected' : ''; ?>>Library Card</option>
                            <option value="other" <?php echo $card_type_filter === 'other' ? 'selected' : ''; ?>>Other</option>
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
                        <input type="text" id="student" name="student" class="form-control form-control-sm" 
                               placeholder="Search by student..." 
                               value="<?php echo htmlspecialchars($student_filter); ?>">
                    </div>
                    <div class="search-item">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="search-item">
                        <a href="cards.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Results Summary -->
        <div class="results-summary">
            <p>Showing <?php echo count($cards); ?> of <?php echo $total_cards; ?> cards</p>
        </div>
        
        <!-- Cards Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-credit-card"></i> Cards List</h3>
            </div>
            <div class="card-body">
                <?php if (empty($cards)): ?>
                    <div class="no-results">
                        <i class="fas fa-credit-card"></i>
                        <h4>No cards found</h4>
                        <p>No cards match your search criteria.</p>
                        <?php if ($user_type === 'admin'): ?>
                        <a href="../cards/register_card.php" class="btn btn-primary">Register First Card</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Card Number</th>
                                    <th>Type</th>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Issued Date</th>
                                    <th>Expiry Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cards as $card): ?>
                                    <tr>
                                        <td>
                                            <strong><code><?php echo htmlspecialchars($card['card_number']); ?></code></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($card['card_type']))); ?></span>
                                        </td>
                                        <td>
                                            <div class="student-info">
                                                <strong><?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($card['registration_number']); ?></small>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($card['department']); ?> - Year <?php echo $card['year_of_study']; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($card['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($card['issued_date'])); ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($card['issued_date'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($card['expiry_date']): ?>
                                                <?php 
                                                $expiry_date = new DateTime($card['expiry_date']);
                                                $today = new DateTime();
                                                $is_expired = $expiry_date < $today;
                                                ?>
                                                <span class="<?php echo $is_expired ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo date('M j, Y', strtotime($card['expiry_date'])); ?>
                                                </span>
                                                <?php if ($is_expired): ?>
                                                    <br><small class="text-danger">Expired</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No expiry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="#" class="btn btn-sm btn-info view-card-btn" title="View Details" data-card-id="<?php echo $card['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($user_type === 'admin'): ?>
                                                <a href="#" class="btn btn-sm btn-warning edit-card-btn" title="Edit" data-card-id="<?php echo $card['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="cards.php?action=toggle_status&id=<?php echo $card['id']; ?>" 
                                                   class="btn btn-sm <?php echo $card['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                                   title="<?php echo $card['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                                   onclick="return confirm('Are you sure you want to <?php echo $card['is_active'] ? 'deactivate' : 'activate'; ?> this card?')">
                                                    <i class="fas fa-<?php echo $card['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                </a>
                                                <a href="cards.php?action=delete&id=<?php echo $card['id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Delete" 
                                                   onclick="return confirm('Are you sure you want to delete this card? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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
                        <div class="pagination-wrapper">
                            <nav aria-label="Cards pagination">
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

<!-- Card Details Modal -->
<div class="modal fade" id="cardModal" tabindex="-1" role="dialog" aria-labelledby="cardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cardModalLabel">
                    <i class="fas fa-credit-card"></i> Card Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cardModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading card details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <?php if ($user_type === 'admin'): ?>
                <a href="#" id="editCardBtn" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Card
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Card Modal -->
<div class="modal fade" id="editCardModal" tabindex="-1" role="dialog" aria-labelledby="editCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCardModalLabel">
                    <i class="fas fa-edit"></i> Edit Card
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editCardModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading card information...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveCardBtn" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentCardId = null;
    
    // View card details
    document.querySelectorAll('.view-card-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const cardId = this.getAttribute('data-card-id');
            loadCardDetails(cardId);
        });
    });
    
    // Edit card
    document.querySelectorAll('.edit-card-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const cardId = this.getAttribute('data-card-id');
            openEditModal(cardId);
        });
    });
    
    // Save card changes
    document.getElementById('saveCardBtn').addEventListener('click', saveCardChanges);
    
    // Load card details via AJAX
    function loadCardDetails(cardId) {
        currentCardId = cardId;
        const modal = new bootstrap.Modal(document.getElementById('cardModal'));
        const modalBody = document.getElementById('cardModalBody');
        const editBtn = document.getElementById('editCardBtn');
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading card details...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Fetch card details
        fetch(`../cards/card_ajax.php?id=${cardId}&ajax=true`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        </div>
                    `;
                } else {
                    displayCardDetails(data.card, modalBody);
                    if (editBtn) {
                        editBtn.href = `#`;
                        editBtn.onclick = () => openEditModal(cardId);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading card details.
                    </div>
                `;
            });
    }
    
    // Open edit modal
    function openEditModal(cardId) {
        currentCardId = cardId;
        const modal = new bootstrap.Modal(document.getElementById('editCardModal'));
        const modalBody = document.getElementById('editCardModalBody');
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading card information...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Load card data for editing
        fetch(`../cards/card_ajax.php?id=${cardId}&ajax=true`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        </div>
                    `;
                } else {
                    displayEditForm(data.card, modalBody);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading card information.
                    </div>
                `;
            });
    }
    
    // Display card details in modal
    function displayCardDetails(card, container) {
        const expiryDate = card.expiry_date ? new Date(card.expiry_date) : null;
        const isExpired = expiryDate && expiryDate < new Date();
        
        container.innerHTML = `
            <div class="card-details-modal">
                <!-- Card Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-credit-card"></i> Card Information</h6>
                    <div class="detail-item">
                        <label>Card Number:</label>
                        <div class="value"><code>${card.card_number}</code></div>
                    </div>
                    <div class="detail-item">
                        <label>Type:</label>
                        <div class="value">
                            <span class="badge bg-info">${card.card_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <div class="value">
                            <span class="status-badge ${card.is_active ? 'status-active' : 'status-inactive'}">
                                ${card.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Issued Date:</label>
                        <div class="value">
                            ${new Date(card.issued_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Expiry Date:</label>
                        <div class="value">
                            ${expiryDate ? 
                                `<span class="${isExpired ? 'text-danger' : 'text-success'}">${expiryDate.toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}</span>` : 
                                '<span class="text-muted">No expiry date</span>'
                            }
                        </div>
                    </div>
                </div>
                
                <!-- Student Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-user"></i> Student Information</h6>
                    <div class="detail-item">
                        <label>Student Name:</label>
                        <div class="value">${card.first_name} ${card.last_name}</div>
                    </div>
                    <div class="detail-item">
                        <label>Registration Number:</label>
                        <div class="value">${card.registration_number}</div>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <div class="value">
                            <a href="mailto:${card.email}">${card.email}</a>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Department:</label>
                        <div class="value">${card.department}</div>
                    </div>
                    <div class="detail-item">
                        <label>Program:</label>
                        <div class="value">${card.program}</div>
                    </div>
                    <div class="detail-item">
                        <label>Year of Study:</label>
                        <div class="value">Year ${card.year_of_study}</div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Display edit form
    function displayEditForm(card, container) {
        container.innerHTML = `
            <form id="editCardForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_card_number" class="form-label">Card Number *</label>
                            <input type="text" id="edit_card_number" name="card_number" class="form-control" 
                                   value="${card.card_number}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_card_type" class="form-label">Card Type *</label>
                            <select id="edit_card_type" name="card_type" class="form-control" required>
                                <option value="student_id" ${card.card_type === 'student_id' ? 'selected' : ''}>Student ID</option>
                                <option value="library_card" ${card.card_type === 'library_card' ? 'selected' : ''}>Library Card</option>
                                <option value="other" ${card.card_type === 'other' ? 'selected' : ''}>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" id="edit_expiry_date" name="expiry_date" class="form-control" 
                                   value="${card.expiry_date || ''}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" id="edit_is_active" name="is_active" class="form-check-input" 
                                       ${card.is_active ? 'checked' : ''}>
                                <label for="edit_is_active" class="form-check-label">Card Active</label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        `;
    }
    
    // Save card changes
    function saveCardChanges() {
        if (!currentCardId) return;
        
        const form = document.getElementById('editCardForm');
        const formData = new FormData(form);
        formData.append('id', currentCardId);
        
        const saveBtn = document.getElementById('saveCardBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        saveBtn.disabled = true;
        
        fetch('../cards/card_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const modalBody = document.getElementById('editCardModalBody');
            
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
                        <button type="button" class="btn btn-secondary" onclick="openEditModal(${currentCardId})">Try Again</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const modalBody = document.getElementById('editCardModalBody');
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error saving card: ${error.message}
                </div>
            `;
        })
        .finally(() => {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        });
    }
});
</script>

<style>
.action-buttons {
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

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

.results-summary {
    margin: 1rem 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.student-info {
    line-height: 1.2;
}

.detail-section {
    margin-bottom: 2rem;
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background-color: #f8f9fa;
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

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.pagination-wrapper {
    margin-top: 2rem;
    display: flex;
    justify-content: center;
}
</style>

<?php include '../includes/footer.php'; ?> 