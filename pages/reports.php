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

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">Reports & Analytics</div>
            <div class="page-subtitle">Generate comprehensive reports and view analytics</div>
        </div>

        <!-- Report Types -->
        <div class="row">
            <!-- Entry/Exit Reports -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-sign-in-alt"></i> Entry/Exit Reports</span>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Generate detailed entry and exit logs with filtering options.</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="showReportModal('entry_exit')">
                                <i class="fas fa-file-alt"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Attendance -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-user-graduate"></i> Student Attendance</span>
                    </div>
                    <div class="card-body">
                        <p class="card-text">View student attendance patterns and statistics.</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="showReportModal('attendance')">
                                <i class="fas fa-chart-bar"></i> View Attendance
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device Usage -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-laptop"></i> Device Usage</span>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Analyze device usage patterns and performance.</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-info" onclick="showReportModal('device_usage')">
                                <i class="fas fa-chart-line"></i> Device Analytics
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RFID Card Reports -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-id-card"></i> RFID Card Reports</span>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Track RFID card usage and registration status.</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-warning" onclick="showReportModal('rfid_cards')">
                                <i class="fas fa-credit-card"></i> Card Reports
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Reports -->
            <?php if ($user_type === 'admin' || $user_type === 'security'): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-shield-alt"></i> Security Reports</span>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Security incident reports and access logs.</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-danger" onclick="showReportModal('security')">
                                <i class="fas fa-exclamation-triangle"></i> Security Logs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Summary Dashboard -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-tachometer-alt"></i> Summary Dashboard</span>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Overview of key metrics and statistics.</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-secondary" onclick="showReportModal('summary')">
                                <i class="fas fa-chart-pie"></i> View Summary
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card mb-4">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-chart-bar"></i> Quick Statistics</span>
            </div>
            <div class="card-body">
                <div class="row" id="quickStats">
                    <div class="col-md-3 text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-history"></i> Recent Reports</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Report Type</th>
                                <th>Generated By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recentReports">
                            <tr>
                                <td colspan="5" class="text-center text-muted">No recent reports</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Report Generation Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalTitle">Generate Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    <input type="hidden" id="reportType" name="report_type">
                    
                    <!-- Date Range -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="end_date" required>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div id="reportFilters">
                        <!-- Dynamic filters will be loaded here -->
                    </div>

                    <!-- Format Selection -->
                    <div class="mb-3">
                        <label class="form-label">Report Format</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" id="formatPDF" value="pdf" checked>
                            <label class="form-check-label" for="formatPDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" id="formatExcel" value="excel">
                            <label class="form-check-label" for="formatExcel">
                                <i class="fas fa-file-excel"></i> Excel
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" id="formatCSV" value="csv">
                            <label class="form-check-label" for="formatCSV">
                                <i class="fas fa-file-csv"></i> CSV
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateReport()">
                    <i class="fas fa-download"></i> Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    
    document.getElementById('startDate').value = lastMonth.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];
    
    // Load quick stats
    loadQuickStats();
});

function showReportModal(reportType) {
    document.getElementById('reportType').value = reportType;
    
    // Set modal title based on report type
    const titles = {
        'entry_exit': 'Entry/Exit Report',
        'attendance': 'Student Attendance Report',
        'device_usage': 'Device Usage Report',
        'rfid_cards': 'RFID Card Report',
        'security': 'Security Report',
        'summary': 'Summary Dashboard'
    };
    
    document.getElementById('reportModalTitle').textContent = titles[reportType] || 'Generate Report';
    
    // Load specific filters for the report type
    loadReportFilters(reportType);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    modal.show();
}

function loadReportFilters(reportType) {
    const filtersContainer = document.getElementById('reportFilters');
    let filtersHTML = '';
    
    switch (reportType) {
        case 'entry_exit':
            filtersHTML = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="studentFilter" class="form-label">Student</label>
                        <select class="form-select" id="studentFilter" name="student_id">
                            <option value="">All Students</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="deviceFilter" class="form-label">Device</label>
                        <select class="form-select" id="deviceFilter" name="device_id">
                            <option value="">All Devices</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="entryMethodFilter" class="form-label">Entry Method</label>
                        <select class="form-select" id="entryMethodFilter" name="entry_method">
                            <option value="">All Methods</option>
                            <option value="rfid">RFID</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="programFilter" class="form-label">Program</label>
                        <select class="form-select" id="programFilter" name="program">
                            <option value="">All Programs</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'attendance':
            filtersHTML = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="attendanceStudentFilter" class="form-label">Student</label>
                        <select class="form-select" id="attendanceStudentFilter" name="student_id">
                            <option value="">All Students</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="attendanceProgramFilter" class="form-label">Program</label>
                        <select class="form-select" id="attendanceProgramFilter" name="program">
                            <option value="">All Programs</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'device_usage':
            filtersHTML = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="deviceTypeFilter" class="form-label">Device Type</label>
                        <select class="form-select" id="deviceTypeFilter" name="device_type">
                            <option value="">All Types</option>
                            <option value="rfid_reader">RFID Reader</option>
                            <option value="gate_controller">Gate Controller</option>
                            <option value="camera">Camera</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="deviceLocationFilter" class="form-label">Location</label>
                        <select class="form-select" id="deviceLocationFilter" name="location">
                            <option value="">All Locations</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        default:
            filtersHTML = '<p class="text-muted">No additional filters available for this report type.</p>';
    }
    
    filtersContainer.innerHTML = filtersHTML;
    
    // Load dropdown data if needed
    if (reportType === 'entry_exit' || reportType === 'attendance') {
        loadStudentsForFilter();
        loadProgramsForFilter();
    }
    
    if (reportType === 'entry_exit') {
        loadDevicesForFilter();
    }
    
    if (reportType === 'device_usage') {
        loadDeviceTypesForFilter();
        loadLocationsForFilter();
    }
}

function loadQuickStats() {
    fetch('../api/reports.php?action=quick_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                document.getElementById('quickStats').innerHTML = `
                    <div class="col-md-3 text-center">
                        <h3 class="text-primary">${stats.total_students}</h3>
                        <p class="text-muted">Total Students</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-success">${stats.active_devices}</h3>
                        <p class="text-muted">Active Devices</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-info">${stats.today_entries}</h3>
                        <p class="text-muted">Today's Entries</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-warning">${stats.avg_daily_entries}</h3>
                        <p class="text-muted">Avg Daily Entries</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading quick stats:', error);
            document.getElementById('quickStats').innerHTML = '<div class="col-12 text-center text-muted">Error loading statistics</div>';
        });
}

function loadStudentsForFilter() {
    fetch('../api/students.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.students) {
                const selects = ['studentFilter', 'attendanceStudentFilter'];
                selects.forEach(selectId => {
                    const select = document.getElementById(selectId);
                    if (select) {
                        data.students.forEach(student => {
                            const option = document.createElement('option');
                            option.value = student.id;
                            option.textContent = `${student.registration_number} - ${student.first_name} ${student.last_name}`;
                            select.appendChild(option);
                        });
                    }
                });
            }
        })
        .catch(error => console.error('Error loading students:', error));
}

function loadDevicesForFilter() {
    fetch('../api/devices.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.devices) {
                const select = document.getElementById('deviceFilter');
                if (select) {
                    data.devices.forEach(device => {
                        const option = document.createElement('option');
                        option.value = device.id;
                        option.textContent = device.name;
                        select.appendChild(option);
                    });
                }
            }
        })
        .catch(error => console.error('Error loading devices:', error));
}

function loadProgramsForFilter() {
    fetch('../api/students.php?action=programs')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.programs) {
                const selects = ['programFilter', 'attendanceProgramFilter'];
                selects.forEach(selectId => {
                    const select = document.getElementById(selectId);
                    if (select) {
                        data.programs.forEach(program => {
                            const option = document.createElement('option');
                            option.value = program.program;
                            option.textContent = program.program;
                            select.appendChild(option);
                        });
                    }
                });
            }
        })
        .catch(error => console.error('Error loading programs:', error));
}

function loadDeviceTypesForFilter() {
    fetch('../api/devices.php?action=types')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.types) {
                const select = document.getElementById('deviceTypeFilter');
                if (select) {
                    data.types.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.device_type;
                        option.textContent = type.device_type;
                        select.appendChild(option);
                    });
                }
            }
        })
        .catch(error => console.error('Error loading device types:', error));
}

function loadLocationsForFilter() {
    fetch('../api/devices.php?action=locations')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.locations) {
                const select = document.getElementById('deviceLocationFilter');
                if (select) {
                    data.locations.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.location;
                        option.textContent = location.location;
                        select.appendChild(option);
                    });
                }
            }
        })
        .catch(error => console.error('Error loading locations:', error));
}

function generateReport() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    // Show loading state
    const generateBtn = document.querySelector('#reportModal .btn-primary');
    const originalText = generateBtn.innerHTML;
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    generateBtn.disabled = true;
    
    fetch('../api/reports.php?action=generate', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        throw new Error('Report generation failed');
    })
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `report_${formData.get('report_type')}_${new Date().toISOString().split('T')[0]}.${formData.get('format')}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
        modal.hide();
        
        showAlert('Report generated successfully!', 'success');
    })
    .catch(error => {
        console.error('Error generating report:', error);
        showAlert('Error generating report. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.content-wrapper').insertBefore(alertDiv, document.querySelector('.content-wrapper').firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<?php include '../includes/footer.php'; ?> 