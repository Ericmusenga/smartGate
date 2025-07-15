<?php echo ($settings['require_password_change'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="require_password_change">
                    Require Password Change on First Login
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Security Settings
            </button>
        </form>
    </div>
</div>
</div>

<!-- Gate Management Settings -->
<div class="tab-pane fade" id="gate" role="tabpanel">
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-door-open"></i> Gate Management Settings</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="update_gate">
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="auto_logout_hours" class="form-label">Auto Logout Hours</label>
                    <input type="number" class="form-control" id="auto_logout_hours" name="auto_logout_hours" 
                           value="<?php echo htmlspecialchars($settings['auto_logout_hours'] ?? '24'); ?>" min="1" max="168">
                    <small class="form-text text-muted">Hours before automatic logout (max 168 = 1 week)</small>
                </div>
                <div class="col-md-4">
                    <label for="rfid_timeout_seconds" class="form-label">RFID Timeout (seconds)</label>
                    <input type="number" class="form-control" id="rfid_timeout_seconds" name="rfid_timeout_seconds" 
                           value="<?php echo htmlspecialchars($settings['rfid_timeout_seconds'] ?? '30'); ?>" min="10" max="300">
                    <small class="form-text text-muted">Time to wait for RFID response</small>
                </div>
                <div class="col-md-4">
                    <label for="max_concurrent_entries" class="form-label">Max Concurrent Entries</label>
                    <input type="number" class="form-control" id="max_concurrent_entries" name="max_concurrent_entries" 
                           value="<?php echo htmlspecialchars($settings['max_concurrent_entries'] ?? '100'); ?>" min="50" max="1000">
                    <small class="form-text text-muted">Maximum simultaneous entries allowed</small>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="allow_manual_entries" name="allow_manual_entries" 
                               <?php echo ($settings['allow_manual_entries'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="allow_manual_entries">
                            Allow Manual Entries
                        </label>
                        <small class="form-text text-muted d-block">Allow staff to manually log entries</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="require_exit_logging" name="require_exit_logging" 
                               <?php echo ($settings['require_exit_logging'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="require_exit_logging">
                            Require Exit Logging
                        </label>
                        <small class="form-text text-muted d-block">Require users to log out when leaving</small>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Gate Settings
            </button>
        </form>
    </div>
</div>
</div>

<!-- Notifications Settings -->
<div class="tab-pane fade" id="notifications" role="tabpanel">
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-bell"></i> Notification Settings</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="update_notifications">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                               <?php echo ($settings['email_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="email_notifications">
                            Enable Email Notifications
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications" 
                               <?php echo ($settings['sms_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sms_notifications">
                            Enable SMS Notifications
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notification_email" class="form-label">Notification Email Address</label>
                <input type="email" class="form-control" id="notification_email" name="notification_email" 
                       value="<?php echo htmlspecialchars($settings['notification_email'] ?? ''); ?>" 
                       placeholder="Enter email address for notifications">
                <small class="form-text text-muted">Email address to receive system notifications</small>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="alert_unauthorized_access" name="alert_unauthorized_access" 
                               <?php echo ($settings['alert_unauthorized_access'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="alert_unauthorized_access">
                            Alert on Unauthorized Access
                        </label>
                        <small class="form-text text-muted d-block">Send alerts when unauthorized access is detected</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="alert_device_offline" name="alert_device_offline" 
                               <?php echo ($settings['alert_device_offline'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="alert_device_offline">
                            Alert on Device Offline
                        </label>
                        <small class="form-text text-muted d-block">Send alerts when gate devices go offline</small>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Notification Settings
            </button>
        </form>
    </div>
</div>
</div>

<!-- Backup & Maintenance -->
<div class="tab-pane fade" id="backup" role="tabpanel">
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-database"></i> Database Backup</span>
            </div>
            <div class="card-body">
                <p class="card-text">Create a backup of the entire database including all tables and data.</p>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="create_backup">
                    <button type="submit" class="btn btn-success" id="backup-btn">
                        <i class="fas fa-download"></i> Create Backup
                    </button>
                </form>
                <button type="button" class="btn btn-info ms-2" id="backup-ajax-btn">
                    <i class="fas fa-sync"></i> Quick Backup (AJAX)
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-wrench"></i> System Maintenance</span>
            </div>
            <div class="card-body">
                <p class="card-text">Perform system maintenance tasks including cleanup and optimization.</p>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="run_maintenance">
                    <button type="submit" class="btn btn-warning" id="maintenance-btn">
                        <i class="fas fa-tools"></i> Run Maintenance
                    </button>
                </form>
                <button type="button" class="btn btn-secondary ms-2" id="maintenance-ajax-btn">
                    <i class="fas fa-sync"></i> Quick Maintenance (AJAX)
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-info-circle"></i> Maintenance Information</span>
            </div>
            <div class="card-body">
                <h6>Maintenance Tasks Include:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success"></i> Cleanup old user sessions (older than 24 hours)</li>
                    <li><i class="fas fa-check text-success"></i> Remove old access logs (older than 30 days)</li>
                    <li><i class="fas fa-check text-success"></i> Clear old login attempts (older than 7 days)</li>
                    <li><i class="fas fa-check text-success"></i> Optimize database tables for better performance</li>
                </ul>
                
                <h6 class="mt-3">Backup Information:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-info text-info"></i> Backups are stored in the <code>../backups/</code> directory</li>
                    <li><i class="fas fa-info text-info"></i> Backup files include timestamp in filename</li>
                    <li><i class="fas fa-info text-info"></i> Regular backups are recommended before major updates</li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load system status
    loadSystemStatus();
    
    // Auto-refresh system status every 30 seconds
    setInterval(loadSystemStatus, 30000);
    
    // AJAX backup function
    document.getElementById('backup-ajax-btn').addEventListener('click', function() {
        const btn = this;
        const originalHtml = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Backup...';
        btn.disabled = true;
        
        fetch('?ajax=backup')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Error creating backup: ' + error.message);
            })
            .finally(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
    });
    
    // AJAX maintenance function
    document.getElementById('maintenance-ajax-btn').addEventListener('click', function() {
        const btn = this;
        const originalHtml = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running Maintenance...';
        btn.disabled = true;
        
        fetch('?ajax=maintenance')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    loadSystemStatus(); // Refresh system status
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Error running maintenance: ' + error.message);
            })
            .finally(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
    });
    
    // Form submission handlers with loading states
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
            }
        });
    });
});

function loadSystemStatus() {
    fetch('?ajax=system_status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('db-size').textContent = data.status.db_size;
                document.getElementById('users-count').textContent = data.status.users_count;
                document.getElementById('students-count').textContent = data.status.students_count;
                document.getElementById('today-entries').textContent = data.status.today_entries;
            }
        })
        .catch(error => {
            console.error('Error loading system status:', error);
        });
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert alert at the top of the main content
    const mainContent = document.querySelector('.main-content .content-wrapper');
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = alertHtml;
    mainContent.insertBefore(tempDiv.firstElementChild, mainContent.firstElementChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }
    }, 5000);
}

// Validate form inputs
document.addEventListener('DOMContentLoaded', function() {
    // Password length validation
    const passwordLengthInput = document.getElementById('password_min_length');
    if (passwordLengthInput) {
        passwordLengthInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value < 6) {
                this.setCustomValidity('Password length must be at least 6 characters');
            } else if (value > 20) {
                this.setCustomValidity('Password length cannot exceed 20 characters');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Session timeout validation
    const sessionTimeoutInput = document.getElementById('session_timeout');
    if (sessionTimeoutInput) {
        sessionTimeoutInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value < 300) {
                this.setCustomValidity('Session timeout must be at least 5 minutes (300 seconds)');
            } else if (value > 86400) {
                this.setCustomValidity('Session timeout cannot exceed 24 hours (86400 seconds)');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Email validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.setCustomValidity('Please enter a valid email address');
            } else {
                this.setCustomValidity('');
            }
        });
    });
});
</script>

<?php
// Include footer if available
if (file_exists('../includes/footer.php')) {
    include '../includes/footer.php';
} else {
    // Close HTML if header wasn't included
    if (!$header_included) {
        echo '</body></html>';
    }
}
?>