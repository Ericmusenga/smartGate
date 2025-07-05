<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../config/config.php';

// Check if user is logged in and is student
if (!is_logged_in() || get_user_type() !== 'student') {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';

try {
    $db = getDB();
    // Fetch devices currently borrowed by the student with loan history
    $devices = $db->fetchAll("
        SELECT d.device_name, d.device_type, d.brand, d.model, d.color, d.description,
               u.first_name AS owner_first, u.last_name AS owner_last, u.email AS owner_email,
               u.username AS owner_username,
               lh.loan_date, lh.notes
        FROM devices d 
        JOIN users u ON d.owner_id = u.id 
        LEFT JOIN loan_history lh ON d.id = lh.device_id AND lh.borrower_id = ? AND lh.status = 'borrowed'
        WHERE d.user_id = ? AND d.owner_id != ?
        ORDER BY d.device_name
    ", [$user_id, $user_id, $user_id]);
    
    // Fetch loan history for this student
    $loan_history = $db->fetchAll("
        SELECT lh.*, d.device_name, d.device_type, d.brand, d.model,
               lender.first_name as lender_first, lender.last_name as lender_last,
               lender.username as lender_username
        FROM loan_history lh
        JOIN devices d ON lh.device_id = d.id
        JOIN users lender ON lh.lender_id = lender.id
        WHERE lh.borrower_id = ?
        ORDER BY lh.loan_date DESC
        LIMIT 10
    ", [$user_id]);
    
} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
}
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-laptop-house"></i> My Borrowed Computers
            </div>
            <div class="page-subtitle">View all computers you have currently borrowed and loan history</div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($devices)): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Currently Borrowed Computers</h3>
                    <p class="text-muted mb-0">You have borrowed <?php echo count($devices); ?> computer(s)</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="fas fa-laptop"></i> Device Name</th>
                                    <th><i class="fas fa-tag"></i> Type</th>
                                    <th><i class="fas fa-info-circle"></i> Brand/Model</th>
                                    <th><i class="fas fa-user"></i> Owner (Student)</th>
                                    <th><i class="fas fa-calendar"></i> Borrowed Date</th>
                                    <th><i class="fas fa-cogs"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($devices as $device): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($device['device_name']); ?></strong>
                                        <?php if ($device['color']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($device['color']); ?></small>
                                        <?php endif; ?>
                                        <?php if ($device['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($device['description'], 0, 50)) . (strlen($device['description']) > 50 ? '...' : ''); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo ucfirst(htmlspecialchars($device['device_type'])); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($device['brand'] || $device['model']): ?>
                                            <strong><?php echo htmlspecialchars($device['brand'] ?? ''); ?></strong>
                                            <?php if ($device['brand'] && $device['model']): ?> / <?php endif; ?>
                                            <?php echo htmlspecialchars($device['model'] ?? ''); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($device['owner_first'] . ' ' . $device['owner_last']); ?></strong>
                                            <br><small class="text-muted">@<?php echo htmlspecialchars($device['owner_username']); ?></small>
                                            <?php if ($device['owner_email']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($device['owner_email']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($device['loan_date']): ?>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($device['loan_date'])); ?>
                                                <br><?php echo date('g:i A', strtotime($device['loan_date'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">Unknown</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="return_computer.php" class="btn btn-warning btn-sm">
                                            <i class="fas fa-undo"></i> Return
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Loan History Section -->
            <?php if (!empty($loan_history)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent Loan History</h3>
                    <p class="text-muted mb-0">Your recent computer borrowing and returning activities</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="fas fa-laptop"></i> Device</th>
                                    <th><i class="fas fa-user"></i> Lent By</th>
                                    <th><i class="fas fa-calendar-plus"></i> Borrowed Date</th>
                                    <th><i class="fas fa-calendar-minus"></i> Returned Date</th>
                                    <th><i class="fas fa-info-circle"></i> Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loan_history as $loan): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($loan['device_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo ucfirst(htmlspecialchars($loan['device_type'])); ?></small>
                                        <?php if ($loan['brand'] || $loan['model']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($loan['brand'] ?? '') . ' ' . htmlspecialchars($loan['model'] ?? ''); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($loan['lender_first'] . ' ' . $loan['lender_last']); ?></strong>
                                        <br><small class="text-muted">@<?php echo htmlspecialchars($loan['lender_username']); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($loan['loan_date'])); ?>
                                            <br><?php echo date('g:i A', strtotime($loan['loan_date'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($loan['return_date']): ?>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($loan['return_date'])); ?>
                                                <br><?php echo date('g:i A', strtotime($loan['return_date'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">Not returned yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($loan['status'] === 'borrowed'): ?>
                                            <span class="badge bg-warning">Currently Borrowed</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Returned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-circle-left" style="font-size: 2rem; color: #ffc107; margin-bottom: 1rem;"></i>
                            <h5>Return Computers</h5>
                            <p class="text-muted">Return borrowed computers to their student owners</p>
                            <a href="return_computer.php" class="btn btn-warning">
                                <i class="fas fa-undo"></i> Return Computers
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-circle-right" style="font-size: 2rem; color: #28a745; margin-bottom: 1rem;"></i>
                            <h5>Borrow More</h5>
                            <p class="text-muted">Borrow additional computers if needed</p>
                            <a href="lend_computer.php" class="btn btn-success">
                                <i class="fas fa-hand-holding"></i> Borrow More
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-laptop-house" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h4>No Borrowed Computers</h4>
                    <p class="text-muted">You have not borrowed any computers at this time.</p>
                    <div class="mt-3">
                        <a href="lend_computer.php" class="btn btn-primary me-2">
                            <i class="fas fa-arrow-circle-right"></i> Borrow a Computer
                        </a>
                        <a href="dashboard_student.php" class="btn btn-secondary">
                            <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?> 