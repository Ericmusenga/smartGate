<?php
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../login.php');
}

// Check user type - students and staff can access this page
$user_type = get_user_type();
if (!in_array($user_type, ['student', 'staff'])) {
    redirect('../unauthorized.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}

$error_message = '';
$success_message = '';
$cards = [];

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // Get user's student ID
    $user = $db->fetch("SELECT student_id FROM users WHERE id = ?", [$user_id]);
    
    if ($user && $user['student_id']) {
        // Get student's cards
        $sql = "SELECT rc.*, s.registration_number, s.first_name, s.last_name, s.email, s.department, s.program, s.year_of_study
                FROM rfid_cards rc 
                JOIN students s ON rc.student_id = s.id
                WHERE rc.student_id = ? 
                ORDER BY rc.issued_date DESC";
        
        $cards = $db->fetchAll($sql, [$user['student_id']]);
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading cards: ' . $e->getMessage();
    $cards = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">My Cards</div>
            <div class="page-subtitle">View your registered RFID cards</div>
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
        
        <!-- Cards Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-credit-card"></i> My Cards List</h3>
            </div>
            <div class="card-body">
                <?php if (empty($cards)): ?>
                    <div class="no-results">
                        <i class="fas fa-credit-card"></i>
                        <h4>No cards found</h4>
                        <p>You don't have any registered RFID cards yet.</p>
                        <p class="text-muted">Please contact the administration to register your cards.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Card Number</th>
                                    <th>Type</th>
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
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View card details
    document.querySelectorAll('.view-card-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const cardId = this.getAttribute('data-card-id');
            loadCardDetails(cardId);
        });
    });
    
    // Load card details via AJAX
    function loadCardDetails(cardId) {
        const modal = new bootstrap.Modal(document.getElementById('cardModal'));
        const modalBody = document.getElementById('cardModalBody');
        
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
        fetch(`card_ajax.php?id=${cardId}&ajax=true`)
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
});
</script>

<style>
.no-results {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.no-results i {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: #dee2e6;
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
</style>

<?php include '../includes/footer.php'; ?> 