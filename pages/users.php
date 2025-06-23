<?php
require_once '../config/config.php';
require_admin();

// Exclude the default super user (assume username is 'superadmin')
$exclude_username = 'superadmin';

$error_message = '';
$success_message = '';
$users = [];

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $user_id = (int)$_GET['id'];
    
    try {
        $db = getDB();
        
        switch ($action) {
            case 'toggle_status':
                $user = $db->fetch("SELECT username, is_active FROM users WHERE id = ? AND username != ?", [$user_id, $exclude_username]);
                if ($user) {
                    $new_status = $user['is_active'] ? 0 : 1;
                    $db->query("UPDATE users SET is_active = ? WHERE id = ?", [$new_status, $user_id]);
                    $status_text = $new_status ? 'activated' : 'deactivated';
                    $success_message = "User '{$user['username']}' has been {$status_text} successfully.";
                } else {
                    $error_message = "User not found or cannot modify super user.";
                }
                break;
                
            case 'delete':
                $user = $db->fetch("SELECT username FROM users WHERE id = ? AND username != ?", [$user_id, $exclude_username]);
                if ($user) {
                    $db->query("DELETE FROM users WHERE id = ?", [$user_id]);
                    $success_message = "User '{$user['username']}' has been deleted successfully.";
                } else {
                    $error_message = "User not found or cannot delete super user.";
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = 'Error performing action: ' . $e->getMessage();
    }
}

try {
    $db = getDB();
    $users = $db->fetchAll("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username != ? ORDER BY u.id DESC", [$exclude_username]);
} catch (Exception $e) {
    $error_message = 'Error loading users: ' . $e->getMessage();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Manage Users</div>
            <div class="page-subtitle">View and manage all system users (except super user)</div>
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
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Users List</h3>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="no-results">
                        <i class="fas fa-users"></i>
                        <h4>No users found</h4>
                        <p>No users (except super user) are registered in the system.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></a></td>
                                        <td><?php echo htmlspecialchars(ucfirst($user['role_name'])); ?></td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-info view-user-btn" title="View" data-user-id="<?php echo $user['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-warning edit-user-btn" title="Edit" data-user-id="<?php echo $user['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="users.php?action=toggle_status&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm <?php echo $user['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                               title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                               onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </a>
                                            <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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

<!-- User Details Modal -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">
                    <i class="fas fa-user"></i> User Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading user details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="editUserBtn" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit User
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="fas fa-user-edit"></i> Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editUserModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading user information...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveUserBtn" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentUserId = null;
    
    // View user details
    document.querySelectorAll('.view-user-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            loadUserDetails(userId);
        });
    });
    
    // Edit user
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            openEditModal(userId);
        });
    });
    
    // Save user changes
    document.getElementById('saveUserBtn').addEventListener('click', saveUserChanges);
    
    // Load user details via AJAX
    function loadUserDetails(userId) {
        currentUserId = userId;
        const modal = new bootstrap.Modal(document.getElementById('userModal'));
        const modalBody = document.getElementById('userModalBody');
        const editBtn = document.getElementById('editUserBtn');
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading user details...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Fetch user details
        fetch(`user_ajax.php?id=${userId}&ajax=true`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        </div>
                    `;
                } else {
                    displayUserDetails(data.user, modalBody);
                    editBtn.href = `#`;
                    editBtn.onclick = () => openEditModal(userId);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading user details.
                    </div>
                `;
            });
    }
    
    // Open edit modal
    function openEditModal(userId) {
        currentUserId = userId;
        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        const modalBody = document.getElementById('editUserModalBody');
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading user information...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Load user data for editing
        fetch(`user_ajax.php?id=${userId}&ajax=true`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        </div>
                    `;
                } else {
                    displayEditForm(data.user, modalBody);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading user information.
                    </div>
                `;
            });
    }
    
    // Display user details in modal
    function displayUserDetails(user, container) {
        container.innerHTML = `
            <div class="user-details-modal">
                <!-- Personal Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-user"></i> Personal Information</h6>
                    <div class="detail-item">
                        <label>Username:</label>
                        <div class="value">${user.username}</div>
                    </div>
                    <div class="detail-item">
                        <label>Full Name:</label>
                        <div class="value">${user.first_name} ${user.last_name}</div>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <div class="value">
                            <a href="mailto:${user.email}">${user.email}</a>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Phone:</label>
                        <div class="value">
                            ${user.phone ? `<a href="tel:${user.phone}">${user.phone}</a>` : '<span class="text-muted">Not provided</span>'}
                        </div>
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-key"></i> Account Information</h6>
                    <div class="detail-item">
                        <label>Role:</label>
                        <div class="value">${user.role_name}</div>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <div class="value">
                            <span class="status-badge ${user.is_active ? 'status-active' : 'status-inactive'}">
                                ${user.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>First Login Required:</label>
                        <div class="value">
                            <span class="account-badge ${user.is_first_login ? 'account-first-login' : 'account-active'}">
                                <i class="fas fa-${user.is_first_login ? 'exclamation-triangle' : 'check'}"></i>
                                ${user.is_first_login ? 'Yes' : 'No'}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Last Login:</label>
                        <div class="value">
                            ${user.last_login ? new Date(user.last_login).toLocaleString('en-US') : '<span class="text-muted">Never logged in</span>'}
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="detail-section">
                    <h6><i class="fas fa-info-circle"></i> System Information</h6>
                    <div class="detail-item">
                        <label>Created:</label>
                        <div class="value">
                            ${new Date(user.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Last Updated:</label>
                        <div class="value">
                            ${new Date(user.updated_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Display edit form
    function displayEditForm(user, container) {
        container.innerHTML = `
            <form id="editUserForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_username" class="form-label">Username *</label>
                            <input type="text" id="edit_username" name="username" class="form-control" 
                                   value="${user.username}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_email" class="form-label">Email Address *</label>
                            <input type="email" id="edit_email" name="email" class="form-control" 
                                   value="${user.email}" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_first_name" class="form-label">First Name *</label>
                            <input type="text" id="edit_first_name" name="first_name" class="form-control" 
                                   value="${user.first_name}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_last_name" class="form-label">Last Name *</label>
                            <input type="text" id="edit_last_name" name="last_name" class="form-control" 
                                   value="${user.last_name}" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="edit_role_id" class="form-label">Role *</label>
                            <select id="edit_role_id" name="role_id" class="form-control" required>
                                <option value="1" ${user.role_id == 1 ? 'selected' : ''}>Admin</option>
                                <option value="2" ${user.role_id == 2 ? 'selected' : ''}>Security</option>
                                <option value="3" ${user.role_id == 3 ? 'selected' : ''}>Staff</option>
                                <option value="4" ${user.role_id == 4 ? 'selected' : ''}>Student</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" id="edit_is_active" name="is_active" class="form-check-input" 
                                       ${user.is_active ? 'checked' : ''}>
                                <label for="edit_is_active" class="form-check-label">Active User</label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        `;
    }
    
    // Save user changes
    function saveUserChanges() {
        if (!currentUserId) return;
        
        const form = document.getElementById('editUserForm');
        const formData = new FormData(form);
        formData.append('id', currentUserId);
        
        const saveBtn = document.getElementById('saveUserBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        saveBtn.disabled = true;
        
        fetch('user_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const modalBody = document.getElementById('editUserModalBody');
            
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
                        <button type="button" class="btn btn-secondary" onclick="openEditModal(${currentUserId})">Try Again</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const modalBody = document.getElementById('editUserModalBody');
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error saving user: ${error.message}
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

.account-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.account-first-login {
    background-color: #fff3cd;
    color: #856404;
}

.account-active {
    background-color: #d4edda;
    color: #155724;
}
</style>

<?php include '../includes/footer.php'; ?> 