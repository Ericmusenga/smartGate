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
$devices = [];
$total_devices = 0;
$total_pages = 0;
$page = 1;

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $device_id = (int)$_GET['id'];
    
    try {
        $db = getDB();
        
        switch ($action) {
            case 'toggle_status':
                $device = $db->fetch("SELECT device_name, is_registered FROM devices WHERE id = ?", [$device_id]);
                if ($device) {
                    $new_status = $device['is_registered'] ? 0 : 1;
                    $db->query("UPDATE devices SET is_registered = ? WHERE id = ?", [$new_status, $device_id]);
                    $status_text = $new_status ? 'registered' : 'unregistered';
                    $success_message = "Device '{$device['device_name']}' has been {$status_text} successfully.";
                } else {
                    $error_message = "Device not found.";
                }
                break;
                
            case 'delete':
                $device = $db->fetch("SELECT device_name FROM devices WHERE id = ?", [$device_id]);
                if ($device) {
                    $db->query("DELETE FROM devices WHERE id = ?", [$device_id]);
                    $success_message = "Device '{$device['device_name']}' has been deleted successfully.";
                } else {
                    $error_message = "Device not found.";
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
$device_type_filter = sanitize_input($_GET['device_type'] ?? '');
$status_filter = sanitize_input($_GET['status'] ?? '');
$user_filter = sanitize_input($_GET['user'] ?? '');

try {
    $db = getDB();
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(d.device_name LIKE ? OR d.serial_number LIKE ? OR d.brand LIKE ? OR d.model LIKE ? OR u.username LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($device_type_filter)) {
        $where_conditions[] = "d.device_type = ?";
        $params[] = $device_type_filter;
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "d.is_registered = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($user_filter)) {
        $where_conditions[] = "(u.username LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
        $user_param = "%{$user_filter}%";
        $params[] = $user_param;
        $params[] = $user_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM devices d 
                  JOIN users u ON d.user_id = u.id 
                  $where_clause";
    $total_result = $db->fetch($count_sql, $params);
    $total_devices = $total_result['total'];
    $total_pages = ceil($total_devices / $per_page);
    
    // Get devices with pagination
    $sql = "SELECT d.*, u.username, u.first_name, u.last_name, u.email, r.role_name
            FROM devices d 
            JOIN users u ON d.user_id = u.id 
            JOIN roles r ON u.role_id = r.id
            $where_clause 
            ORDER BY d.registration_date DESC 
            LIMIT $per_page OFFSET $offset";
    
    $devices = $db->fetchAll($sql, $params);
    
    // Get device types for filter
    $device_types = $db->fetchAll("SELECT DISTINCT device_type FROM devices ORDER BY device_type");
    
} catch (Exception $e) {
    $error_message = 'Error loading devices: ' . $e->getMessage();
    $devices = [];
    $total_devices = 0;
    $total_pages = 0;
    $device_types = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Devices Management</div>
            <div class="page-subtitle">View and manage all registered devices</div>
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
            <a href="../device/register_device.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Register New Device
            </a>
            <a href="devices.php?export=csv" class="btn btn-secondary">
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
                               placeholder="Search devices..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="search-item">
                        <select id="device_type" name="device_type" class="form-control form-control-sm">
                            <option value="">All Types</option>
                            <?php foreach ($device_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['device_type']); ?>" 
                                        <?php echo $device_type_filter === $type['device_type'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($type['device_type'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-item">
                        <select id="status" name="status" class="form-control form-control-sm">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Registered</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Unregistered</option>
                        </select>
                    </div>
                    <div class="search-item">
                        <input type="text" id="user" name="user" class="form-control form-control-sm" 
                               placeholder="Search by user..." 
                               value="<?php echo htmlspecialchars($user_filter); ?>">
                    </div>
                    <div class="search-item">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="search-item">
                        <a href="devices.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Results Summary -->
        <div class="results-summary">
            <p>Showing <?php echo count($devices); ?> of <?php echo $total_devices; ?> devices</p>
        </div>
        
        <!-- Devices Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-laptop"></i> Devices List</h3>
            </div>
            <div class="card-body">
                <?php if (empty($devices)): ?>
                    <div class="no-results">
                        <i class="fas fa-laptop"></i>
                        <h4>No devices found</h4>
                        <p>No devices match your search criteria.</p>
                        <?php if ($user_type === 'admin'): ?>
                        <a href="../device/register_device.php" class="btn btn-primary">Register First Device</a>
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
                                    <th>Owner</th>
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
                                            <div class="user-info">
                                                <strong><?php echo htmlspecialchars($device['first_name'] . ' ' . $device['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($device['username']); ?></small>
                                                <br><span class="badge bg-secondary"><?php echo ucfirst(htmlspecialchars($device['role_name'])); ?></span>
                                            </div>
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
                                                <?php if ($user_type === 'admin'): ?>
                                                <a href="#" class="btn btn-sm btn-warning edit-device-btn" title="Edit" data-device-id="<?php echo $device['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="devices.php?action=toggle_status&id=<?php echo $device['id']; ?>" 
                                                   class="btn btn-sm <?php echo $device['is_registered'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                                   title="<?php echo $device['is_registered'] ? 'Unregister' : 'Register'; ?>"
                                                   onclick="return confirm('Are you sure you want to <?php echo $device['is_registered'] ? 'unregister' : 'register'; ?> this device?')">
                                                    <i class="fas fa-<?php echo $device['is_registered'] ? 'ban' : 'check'; ?>"></i>
                                                </a>
                                                <a href="devices.php?action=delete&id=<?php echo $device['id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Delete" 
                                                   onclick="return confirm('Are you sure you want to delete this device? This action cannot be undone.')">
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
                            <nav aria-label="Devices pagination">
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
                <?php if ($user_type === 'admin'): ?>
                <a href="#" id="editDeviceBtn" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Device
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Device Modal -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" role="dialog" aria-labelledby="editDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDeviceModalLabel">
                    <i class="fas fa-edit"></i> Edit Device
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editDeviceModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading device information...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveDeviceBtn" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentDeviceId = null;
    
    // View device details
    document.querySelectorAll('.view-device-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const deviceId = this.getAttribute('data-device-id');
            loadDeviceDetails(deviceId);
        });
    });
    
    // Edit device
    document.querySelectorAll('.edit-device-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const deviceId = this.getAttribute('data-device-id');
            openEditModal(deviceId);
        });
    });
    
    // Save device changes
    document.getElementById('saveDeviceBtn').addEventListener('click', saveDeviceChanges);
    
    // Load device details via AJAX
    function loadDeviceDetails(deviceId) {
        currentDeviceId = deviceId;
        const modal = new bootstrap.Modal(document.getElementById('deviceModal'));
        const modalBody = document.getElementById('deviceModalBody');
        const editBtn = document.getElementById('editDeviceBtn');
        
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
        fetch(`../device/device_ajax.php?id=${deviceId}&ajax=true`)
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
                    if (editBtn) {
                        editBtn.href = `#`;
                        editBtn.onclick = () => openEditModal(deviceId);
                    }
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
    
    // Open edit modal
    function openEditModal(deviceId) {
        currentDeviceId = deviceId;
        const modal = new bootstrap.Modal(document.getElementById('editDeviceModal'));
        const modalBody = document.getElementById('editDeviceModalBody');
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading device information...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Load device data for editing
        fetch(`../device/device_ajax.php?id=${deviceId}&ajax=true`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        </div>
                    `;
                } else {
                    displayEditForm(data.device, modalBody);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading device information.
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
                
                <!-- Owner Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-user"></i> Owner Information</h6>
                    <div class="detail-item">
                        <label>Owner:</label>
                        <div class="value">${device.first_name} ${device.last_name}</div>
                    </div>
                    <div class="detail-item">
                        <label>Username:</label>
                        <div class="value">${device.username}</div>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <div class="value">
                            <a href="mailto:${device.email}">${device.email}</a>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Role:</label>
                        <div class="value">
                            <span class="badge bg-secondary">${device.role_name.charAt(0).toUpperCase() + device.role_name.slice(1)}</span>
                        </div>
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
    
    // Display edit form
    function displayEditForm(device, container) {
        container.innerHTML = `
            <form id="editDeviceForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_device_name" class="form-label">Device Name *</label>
                            <input type="text" id="edit_device_name" name="device_name" class="form-control" 
                                   value="${device.device_name}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_device_type" class="form-label">Device Type *</label>
                            <select id="edit_device_type" name="device_type" class="form-control" required>
                                <option value="laptop" ${device.device_type === 'laptop' ? 'selected' : ''}>Laptop</option>
                                <option value="tablet" ${device.device_type === 'tablet' ? 'selected' : ''}>Tablet</option>
                                <option value="phone" ${device.device_type === 'phone' ? 'selected' : ''}>Phone</option>
                                <option value="other" ${device.device_type === 'other' ? 'selected' : ''}>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_serial_number" class="form-label">Serial Number *</label>
                            <input type="text" id="edit_serial_number" name="serial_number" class="form-control" 
                                   value="${device.serial_number}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_brand" class="form-label">Brand</label>
                            <input type="text" id="edit_brand" name="brand" class="form-control" 
                                   value="${device.brand || ''}">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_model" class="form-label">Model</label>
                            <input type="text" id="edit_model" name="model" class="form-control" 
                                   value="${device.model || ''}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_color" class="form-label">Color</label>
                            <input type="text" id="edit_color" name="color" class="form-control" 
                                   value="${device.color || ''}">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea id="edit_description" name="description" class="form-control" rows="3">${device.description || ''}</textarea>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" id="edit_is_registered" name="is_registered" class="form-check-input" 
                                       ${device.is_registered ? 'checked' : ''}>
                                <label for="edit_is_registered" class="form-check-label">Device Registered</label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        `;
    }
    
    // Save device changes
    function saveDeviceChanges() {
        if (!currentDeviceId) return;
        
        const form = document.getElementById('editDeviceForm');
        const formData = new FormData(form);
        formData.append('id', currentDeviceId);
        
        const saveBtn = document.getElementById('saveDeviceBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        saveBtn.disabled = true;
        
        fetch('../device/device_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const modalBody = document.getElementById('editDeviceModalBody');
            
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
                        <button type="button" class="btn btn-secondary" onclick="openEditModal(${currentDeviceId})">Try Again</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const modalBody = document.getElementById('editDeviceModalBody');
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error saving device: ${error.message}
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

.user-info {
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