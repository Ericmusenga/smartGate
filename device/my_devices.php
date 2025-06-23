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
$devices = [];

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // Get user's devices
    $sql = "SELECT d.*, u.username, u.first_name, u.last_name, u.email, r.role_name
            FROM devices d 
            JOIN users u ON d.user_id = u.id 
            JOIN roles r ON u.role_id = r.id
            WHERE d.user_id = ? 
            ORDER BY d.registration_date DESC";
    
    $devices = $db->fetchAll($sql, [$user_id]);
    
} catch (Exception $e) {
    $error_message = 'Error loading devices: ' . $e->getMessage();
    $devices = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">My Devices</div>
            <div class="page-subtitle">View your registered devices</div>
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
            <?php if ($user_type !== 'student'): ?>
            <a href="register_device.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Register New Device
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Devices Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-laptop"></i> My Devices List</h3>
            </div>
            <div class="card-body">
                <?php if (empty($devices)): ?>
                    <div class="no-results">
                        <i class="fas fa-laptop"></i>
                        <h4>No devices found</h4>
                        <p>You haven't registered any devices yet.</p>
                        <?php if ($user_type !== 'student'): ?>
                        <a href="register_device.php" class="btn btn-primary">Register Your First Device</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Device Name</th>
                                    <th>Type</th>
                                    <th>Serial Number</th>
                                    <th>Brand/Model</th>
                                    <th>Status</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
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
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst(htmlspecialchars($device['device_type'])); ?></span>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($device['serial_number']); ?></code>
                                        </td>
                                        <td>
                                            <?php if ($device['brand'] || $device['model']): ?>
                                                <?php echo htmlspecialchars($device['brand'] ?? ''); ?>
                                                <?php if ($device['brand'] && $device['model']): ?> / <?php endif; ?>
                                                <?php echo htmlspecialchars($device['model'] ?? ''); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($device['is_registered']): ?>
                                                <span class="badge bg-success">Registered</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Unregistered</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($device['registration_date'])); ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($device['registration_date'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="#" class="btn btn-sm btn-info view-device-btn" title="View Details" data-device-id="<?php echo $device['id']; ?>">
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

<!-- Device Details Modal -->
<div class="modal fade" id="deviceModal" tabindex="-1" role="dialog" aria-labelledby="deviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deviceModalLabel">
                    <i class="fas fa-laptop"></i> Device Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deviceModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading device details...</p>
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
    // View device details
    document.querySelectorAll('.view-device-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const deviceId = this.getAttribute('data-device-id');
            loadDeviceDetails(deviceId);
        });
    });
    
    // Load device details via AJAX
    function loadDeviceDetails(deviceId) {
        const modal = new bootstrap.Modal(document.getElementById('deviceModal'));
        const modalBody = document.getElementById('deviceModalBody');
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading device details...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Fetch device details
        fetch(`device_ajax.php?id=${deviceId}&ajax=true`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        </div>
                    `;
                } else {
                    displayDeviceDetails(data.device, modalBody);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading device details.
                    </div>
                `;
            });
    }
    
    // Display device details in modal
    function displayDeviceDetails(device, container) {
        container.innerHTML = `
            <div class="device-details-modal">
                <!-- Device Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-laptop"></i> Device Information</h6>
                    <div class="detail-item">
                        <label>Device Name:</label>
                        <div class="value">${device.device_name}</div>
                    </div>
                    <div class="detail-item">
                        <label>Type:</label>
                        <div class="value">
                            <span class="badge bg-info">${device.device_type.charAt(0).toUpperCase() + device.device_type.slice(1)}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Serial Number:</label>
                        <div class="value"><code>${device.serial_number}</code></div>
                    </div>
                    <div class="detail-item">
                        <label>Brand:</label>
                        <div class="value">${device.brand || '<span class="text-muted">Not specified</span>'}</div>
                    </div>
                    <div class="detail-item">
                        <label>Model:</label>
                        <div class="value">${device.model || '<span class="text-muted">Not specified</span>'}</div>
                    </div>
                    <div class="detail-item">
                        <label>Color:</label>
                        <div class="value">${device.color || '<span class="text-muted">Not specified</span>'}</div>
                    </div>
                    <div class="detail-item">
                        <label>Description:</label>
                        <div class="value">${device.description || '<span class="text-muted">No description</span>'}</div>
                    </div>
                </div>
                
                <!-- Registration Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-info-circle"></i> Registration Information</h6>
                    <div class="detail-item">
                        <label>Status:</label>
                        <div class="value">
                            <span class="status-badge ${device.is_registered ? 'status-active' : 'status-inactive'}">
                                ${device.is_registered ? 'Registered' : 'Unregistered'}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Registration Date:</label>
                        <div class="value">
                            ${new Date(device.registration_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Last Updated:</label>
                        <div class="value">
                            ${new Date(device.updated_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                        </div>
                    </div>
                </div>
            </div>
        `;
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