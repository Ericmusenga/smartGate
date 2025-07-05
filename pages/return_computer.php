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
$message_type = '';

try {
    $db = getDB();
    // Handle return action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['device_id'])) {
        $device_id = intval($_POST['device_id']);
        
        // Check if device is still borrowed by this student and get device info
        $device_check = $db->fetch("
            SELECT d.device_name, d.owner_id, u.first_name as owner_first, u.last_name as owner_last 
            FROM devices d 
            JOIN users u ON d.owner_id = u.id 
            WHERE d.id = ? AND d.user_id = ? AND d.owner_id != ?
        ", [$device_id, $user_id, $user_id]);
        
        if ($device_check) {
            // Start transaction
            $db->beginTransaction();
            
            try {
                // Update device to return to owner
                $db->query("UPDATE devices SET user_id = owner_id WHERE id = ? AND user_id = ? AND owner_id != ?", 
                    [$device_id, $user_id, $user_id]);
                
                // Update loan history to mark as returned
                $db->query("
                    UPDATE loan_history 
                    SET status = 'returned', return_date = CURRENT_TIMESTAMP, notes = CONCAT(notes, ' - Returned on ', NOW())
                    WHERE device_id = ? AND borrower_id = ? AND status = 'borrowed'
                    ORDER BY loan_date DESC LIMIT 1
                ", [$device_id, $user_id]);
                
                $db->commit();
                $message = 'Device "' . $device_check['device_name'] . '" successfully returned to ' . 
                          $device_check['owner_first'] . ' ' . $device_check['owner_last'] . '!';
                $message_type = 'success';
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        } else {
            $message = 'Device is no longer in your possession.';
            $message_type = 'warning';
        }
    }
    
    // Fetch devices currently borrowed by the student
    $devices = $db->fetchAll("
        SELECT d.id, d.device_name, d.device_type, d.brand, d.model, d.color, d.description,
               u.first_name AS owner_first, u.last_name AS owner_last, u.email AS owner_email,
               u.username AS owner_username,
               lh.loan_date
        FROM devices d 
        JOIN users u ON d.owner_id = u.id 
        LEFT JOIN loan_history lh ON d.id = lh.device_id AND lh.borrower_id = ? AND lh.status = 'borrowed'
        WHERE d.user_id = ? AND d.owner_id != ?
        ORDER BY d.device_name
    ", [$user_id, $user_id, $user_id]);
    
} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
    $message_type = 'danger';
}
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-arrow-circle-left"></i> Return a Computer
            </div>
            <div class="page-subtitle">Return borrowed computers to their student owners</div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($devices)): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-laptop-house"></i> My Borrowed Computers</h3>
                    <p class="text-muted mb-0">Click "Return" to give the computer back to its student owner</p>
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
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to return this computer to <?php echo htmlspecialchars($device['owner_first'] . ' ' . $device['owner_last']); ?>?')">
                                            <button type="submit" name="device_id" value="<?php echo $device['id']; ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-undo"></i> Return
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-laptop-house" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h4>No Borrowed Computers</h4>
                    <p class="text-muted">You have not borrowed any computers at this time.</p>
                    <a href="lend_computer.php" class="btn btn-primary">
                        <i class="fas fa-arrow-circle-right"></i> Borrow a Computer
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
// Auto-hide alerts after 5 seconds
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        var bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php require_once '../includes/footer.php'; ?> 