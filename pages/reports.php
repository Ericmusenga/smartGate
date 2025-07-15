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
                        <div class="mb-2">
                            <label for="entryExitStartDate" class="form-label">Start Date</label>
                            <input type="date" id="entryExitStartDate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="entryExitEndDate" class="form-label">End Date</label>
                            <input type="date" id="entryExitEndDate" class="form-control">
                        </div>
                        <div class="d-grid gap-2 mb-2">
                            <button class="btn btn-secondary" onclick="checkAndRedirectEntryExit()">
                                <i class="fas fa-search"></i> Find
                            </button>
                        </div>
                        <div id="entryExitCheckMsg" class="mt-2"></div>
                    </div>
                </div>
            </div>
            <!-- Students Reports -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-user-graduate"></i> Students Reports</span>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Generate a detailed report of all students with all fields.</p>
                        <div class="mb-2">
                            <label for="studentsStartDate" class="form-label">Start Date</label>
                            <input type="date" id="studentsStartDate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="studentsEndDate" class="form-label">End Date</label>
                            <input type="date" id="studentsEndDate" class="form-control">
                        </div>
                        <div class="d-grid gap-2 mb-2">
                            <button class="btn btn-secondary" onclick="checkAndRedirectStudents()">
                                <i class="fas fa-search"></i> Find
                            </button>
                        </div>
                        <div id="studentsCheckMsg" class="mt-2"></div>
                    </div>
                </div>
            </div>
            <!-- Visitors Report -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-users"></i> Visitors Report</span>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Track visitor entries, exits, and duration statistics.</p>
                        <div class="mb-2">
                            <label for="visitorsStartDate" class="form-label">Start Date</label>
                            <input type="date" id="visitorsStartDate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="visitorsEndDate" class="form-label">End Date</label>
                            <input type="date" id="visitorsEndDate" class="form-control">
                        </div>
                        <div class="d-grid gap-2 mb-2">
                            <button class="btn btn-secondary" onclick="checkAndRedirectVisitors()">
                                <i class="fas fa-search"></i> Find
                            </button>
                        </div>
                        <div id="visitorsCheckMsg" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>



<?php include '../includes/footer.php'; ?>
<script>
function generateEntryExitReport() {
    const start = document.getElementById('entryExitStartDate').value;
    const end = document.getElementById('entryExitEndDate').value;
    let url = '../api/export_exit_logs.php';
    if (start && end) {
        url += `?start_date=${start}&end_date=${end}`;
    }
    window.open(url, '_blank');
}
function generateStudentsReport() {
    const start = document.getElementById('studentsStartDate').value;
    const end = document.getElementById('studentsEndDate').value;
    let url = '../api/export_students_pdf.php';
    if (start && end) {
        url += `?start_date=${start}&end_date=${end}`;
    }
    window.open(url, '_blank');
}
function generateVisitorsReport() {
    const start = document.getElementById('visitorsStartDate').value;
    const end = document.getElementById('visitorsEndDate').value;
    let url = '../api/export_visitors_pdf.php';
    if (start && end) {
        url += `?start_date=${start}&end_date=${end}`;
    }
    window.open(url, '_blank');
}

function findStudents() {
    const start = document.getElementById('studentsStartDate').value;
    const end = document.getElementById('studentsEndDate').value;
    let url = '../api/get_students.php?ajax=1';
    if (start) url += `&start_date=${start}`;
    if (end) url += `&end_date=${end}`;
    fetch(url)
        .then(res => res.json())
        .then(data => {
            const resultsDiv = document.getElementById('studentsFindResults');
            if (data.students && data.students.length > 0) {
                let html = '<table class="table table-sm table-bordered"><thead><tr><th>Reg. No</th><th>Name</th><th>Email</th><th>Department</th><th>Year</th><th>Created At</th></tr></thead><tbody>';
                data.students.forEach(s => {
                    html += `<tr><td>${s.registration_number}</td><td>${s.first_name} ${s.last_name}</td><td>${s.email}</td><td>${s.department}</td><td>${s.year_of_study}</td><td>${s.created_at}</td></tr>`;
                });
                html += '</tbody></table>';
                resultsDiv.innerHTML = html;
            } else {
                resultsDiv.innerHTML = '<div class="alert alert-warning">There are no data recorded on this date you have selected.</div>';
            }
        })
        .catch(() => {
            document.getElementById('studentsFindResults').innerHTML = '<div class="alert alert-danger">Error fetching students.</div>';
        });
}

function checkAndRedirectStudents() {
    const start = document.getElementById('studentsStartDate').value;
    const end = document.getElementById('studentsEndDate').value;
    const msgDiv = document.getElementById('studentsCheckMsg');
    msgDiv.innerHTML = '';
    if (!start || !end) {
        msgDiv.innerHTML = '<div class="alert alert-warning">Please select both start and end dates.</div>';
        return;
    }
    fetch(`../api/check_students.php?start_date=${start}&end_date=${end}`)
        .then(res => res.json())
        .then(data => {
            if (data.hasData) {
                window.location.href = `students_report_view.php?start_date=${start}&end_date=${end}`;
            } else {
                msgDiv.innerHTML = '<div class="alert alert-danger">There is no data recorded on this date you have selected.</div>';
            }
        })
        .catch(() => {
            msgDiv.innerHTML = '<div class="alert alert-danger">Error checking data. Please try again.</div>';
        });
}

function checkAndRedirectVisitors() {
    const start = document.getElementById('visitorsStartDate').value;
    const end = document.getElementById('visitorsEndDate').value;
    const msgDiv = document.getElementById('visitorsCheckMsg');
    msgDiv.innerHTML = '';
    if (!start || !end) {
        msgDiv.innerHTML = '<div class="alert alert-warning">Please select both start and end dates.</div>';
        return;
    }
    fetch(`../api/check_visitors.php?start_date=${start}&end_date=${end}`)
        .then(res => res.json())
        .then(data => {
            if (data.hasData) {
                window.location.href = `visitors_report_view.php?start_date=${start}&end_date=${end}`;
            } else {
                msgDiv.innerHTML = '<div class="alert alert-danger">There is no data recorded on this date you have selected.</div>';
            }
        })
        .catch(() => {
            msgDiv.innerHTML = '<div class="alert alert-danger">Error checking data. Please try again.</div>';
        });
}

function checkAndRedirectEntryExit() {
    const start = document.getElementById('entryExitStartDate').value;
    const end = document.getElementById('entryExitEndDate').value;
    const msgDiv = document.getElementById('entryExitCheckMsg');
    msgDiv.innerHTML = '';
    if (!start || !end) {
        msgDiv.innerHTML = '<div class="alert alert-warning">Please select both start and end dates.</div>';
        return;
    }
    fetch(`../api/check_entry_exit.php?start_date=${start}&end_date=${end}`)
        .then(res => res.json())
        .then(data => {
            if (data.hasData) {
                window.location.href = `entry_exit_report_view.php?start_date=${start}&end_date=${end}`;
            } else {
                msgDiv.innerHTML = '<div class="alert alert-danger">There is no data recorded on this date you have selected.</div>';
            }
        })
        .catch(() => {
            msgDiv.innerHTML = '<div class="alert alert-danger">Error checking data. Please try again.</div>';
        });
}
</script>