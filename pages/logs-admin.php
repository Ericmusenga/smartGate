<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in()) {
    redirect('../login.php');
}

if (get_user_type() !== 'admin') {
    redirect('../login.php');
}

// Check if password change is required
if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
    redirect('../change_password.php');
}


include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <!-- <div class="page-title">Students Management</div> -->
            <div class="page-subtitle">Views All Entry/Exit</div>
        </div>

      
        
        <!-- Vistor Exit Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i>Exit Logs For Vistors</h3>
            </div>
            <div class="card-body">
               
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time #</th>
                                    <th>Name</th>           
                                    <th>ID Number</th>
                                    
                                    <th>Status</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                        $conn=new mysqli("localhost","root","","gate_management_system");
                        $sql="SELECT * from exit_visitor e,vistor v where e.visitor_id=v.id";
                        $result=$conn->query($sql);
                        while ($row=$result->fetch_assoc())
                         {?>
                              <tr>
                            <td><?php echo $row['exit_time'];?></td>
                            <td><?php echo $row['visitor_name'];?></td>
                            <td><?php echo $row['id_number'];?></td>
                            <td>OutSide</td>
                        </tr>
                     <?php   }?>
                              
                            </tbody>
                        </table>
                    </div>
                
            </div>
        </div>
        <!-- ENtry table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i>Entry Logs For Vistors</h3>
            </div>
            <div class="card-body">
               
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time #</th>
                                    <th>Name</th>           
                                    <th>ID Number</th>
                                    
                                    <th>Status</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                        $conn=new mysqli("localhost","root","","gate_management_system");
                        $sql="SELECT * from entry_visitor e,vistor v where e.visitor_id=v.id";
                        $result=$conn->query($sql);
                        while ($row=$result->fetch_assoc())
                         {?>
                              <tr>
                            <td><?php echo $row['entry_time'];?></td>
                            <td><?php echo $row['visitor_name'];?></td>
                            <td><?php echo $row['id_number'];?></td>
                            <td>Inside</td>
                        </tr>
                     <?php   }?>
                              
                            </tbody>
                        </table>
                    </div>
                
            </div>
        </div>
        <!-- Student Entry -->
         <!-- ENtry table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i>Entry Logs For Student</h3>
            </div>
            <div class="card-body">
               
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time #</th>
                                    <th>Name</th>           
                                    <th>ID Number</th>
                                    
                                    <th>Status</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                        $conn=new mysqli("localhost","root","","gate_management_system");
                        $sql="SELECT * from entry_student e,users v where e.student_id=v.id";
                        $result=$conn->query($sql);
                        while ($row=$result->fetch_assoc())
                         {?>
                              <tr>
                            <td><?php echo $row['entry_time'];?></td>
                            <td><?php echo $row['first_name'];?></td>
                            <td><?php echo $row['id_number'];?></td>
                            <td>Inside</td>
                        </tr>
                     <?php   }?>
                              
                            </tbody>
                        </table>
                    </div>
                
            </div>
        </div>
        <!-- Exit Student -->
         <!-- ENtry table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i>Exit Logs For Student</h3>
            </div>
            <div class="card-body">
               
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time #</th>
                                    <th>Name</th>           
                                    <th>ID Number</th>
                                    
                                    <th>Status</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                        $conn=new mysqli("localhost","root","","gate_management_system");
                        $sql="SELECT * from exit_student e,users v where e.student_id=v.id";
                        $result=$conn->query($sql);
                        while ($row=$result->fetch_assoc())
                         {?>
                              <tr>
                            <td><?php echo $row['entry_time'];?></td>
                            <td><?php echo $row['first_name'];?></td>
                            <td><?php echo $row['id_number'];?></td>
                            <td>Inside</td>
                        </tr>
                     <?php   }?>
                              
                            </tbody>
                        </table>
                    </div>
                
            </div>
        </div>
    </div>
</main>


<style>
.action-buttons {
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Compact Search & Filters */
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

.search-form {
    margin-bottom: 0;
}

.results-summary {
    margin: 1rem 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.table {
    margin-bottom: 0;
    border-collapse: collapse;
    width: 100%;
}

.table th {
    background: #343a40;
    color: white;
    border-bottom: 2px solid #495057;
    font-weight: 600;
    padding: 12px 8px;
    text-align: left;
    font-size: 0.9rem;
}

.table td {
    padding: 12px 8px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.table tbody tr:nth-child(even) {
    background-color: #ffffff;
}

.table tbody tr:nth-child(odd) {
    background-color: #f8f9fa;
}

.student-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.gender-badge {
    background: #e9ecef;
    color: #6c757d;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
}

.year-badge {
    background: #007bff;
    color: white;
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.account-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.account-exists {
    background: #d1ecf1;
    color: #0c5460;
}

.account-missing {
    background: #f8d7da;
    color: #721c24;
}

.device-count {
    color: #6c757d;
    font-size: 0.9rem;
}

.action-buttons .btn {
    margin: 0 0.2rem;
}

.no-results {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.no-results i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #dee2e6;
}

.no-results h4 {
    margin-bottom: 0.5rem;
}

.pagination-wrapper {
    margin-top: 2rem;
    display: flex;
    justify-content: center;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
}

.alert-danger {
    background: rgba(231, 76, 60, 0.1);
    border-color: #e74c3c;
    color: #e74c3c;
}

.alert-success {
    background: rgba(39, 174, 96, 0.1);
    border-color: #27ae60;
    color: #27ae60;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .search-row {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .search-item {
        width: 100%;
        min-width: auto;
    }
    
    .search-item:last-child,
    .search-item:nth-last-child(2) {
        flex: 1;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Modal Styles */
.modal-dialog {
    max-width: 800px;
}

.modal-header {
    background: #343a40;
    color: white;
    border-bottom: 1px solid #495057;
}

.modal-header .close {
    color: white;
    opacity: 0.8;
}

.modal-header .close:hover {
    opacity: 1;
}

.modal-body {
    padding: 1.5rem;
}

.student-details-modal {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.detail-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
}

.detail-section h6 {
    color: #495057;
    margin-bottom: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-item {
    margin-bottom: 0.75rem;
}

.detail-item:last-child {
    margin-bottom: 0;
}

.detail-item label {
    font-weight: 600;
    color: #495057;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
    display: block;
}

.detail-item .value {
    color: #212529;
    font-size: 0.9rem;
}

.detail-item .value a {
    color: #007bff;
    text-decoration: none;
}

.detail-item .value a:hover {
    text-decoration: underline;
}

.year-badge {
    background: #007bff;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge {
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.account-badge {
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.account-active {
    background: #d1ecf1;
    color: #0c5460;
}

.account-first-login {
    background: #fff3cd;
    color: #856404;
}

.account-missing {
    background: #f8d7da;
    color: #721c24;
}

.device-count-badge {
    background: #e9ecef;
    color: #495057;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.text-muted {
    color: #6c757d !important;
}

@media (max-width: 768px) {
    .student-details-modal {
        grid-template-columns: 1fr;
    }
}

/* Quick View Form Styles */
.quick-view-form {
    margin-bottom: 0;
}

.quick-view-form .form-row {
    align-items: end;
}

.quick-view-form .form-control {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
}

.quick-view-form .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.quick-view-form label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.quick-view-form .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.quick-view-form .btn i {
    margin-right: 0.25rem;
}

@media (max-width: 768px) {
    .quick-view-form .form-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .quick-view-form .col-md-2,
    .quick-view-form .col-md-4,
    .quick-view-form .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .quick-view-form .col-md-2:last-child {
        margin-bottom: 0;
    }
}
</style>


<?php include '../includes/footer.php'; ?>